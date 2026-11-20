<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\User;
use App\Notifications\InventoryAlertNotification;
use App\Services\AlertGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class GenerateInventoryAlerts extends Command
{
    protected $signature = 'inventory:generate-alerts';

    protected $description = 'Generate inventory alerts and notify active users.';

    public function handle(AlertGenerationService $alertGenerationService): int
    {
        $newAlerts = $alertGenerationService->generate();

        $users = User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get();

        $notificationsSent = 0;
        $deliveryFailures = 0;

        foreach ($newAlerts as $alert) {
            foreach ($users as $user) {
                try {
                    $user->notify(new InventoryAlertNotification($alert));
                    $notificationsSent++;
                } catch (Throwable $exception) {
                    report($exception);
                    $deliveryFailures++;

                    $this->warn(
                        "Unable to notify {$user->email} about alert #{$alert->id}."
                    );
                }
            }
        }

        // Preserve the existing configured recipient email.
        $recipient = config('mail.alert_recipient');
        $mailer = config('mail.default');

        if (
            is_string($recipient)
            && $recipient !== ''
            && ! in_array($mailer, ['log', 'array'], true)
            && ! $users->contains(
                fn (User $user): bool =>
                    strcasecmp($user->email, $recipient) === 0
            )
        ) {
            foreach ($newAlerts as $alert) {
                try {
                    $this->emailAlert($alert, $recipient);
                } catch (Throwable $exception) {
                    report($exception);
                    $deliveryFailures++;

                    $this->error(
                        "Unable to email configured recipient about alert #{$alert->id}."
                    );
                }
            }
        }

        $this->info("New or reactivated alerts: {$newAlerts->count()}");
        $this->info("User notifications delivered: {$notificationsSent}");
        $this->info("Delivery failures: {$deliveryFailures}");

        return self::SUCCESS;
    }

    private function emailAlert(Alert $alert, string $recipient): void
    {
        Mail::raw(
            "{$alert->title}\n\n{$alert->description}\n\n"
                .($alert->action_url ?? ''),
            fn ($message) => $message
                ->to($recipient)
                ->subject("Mukwano WIMS alert: {$alert->title}")
        );
    }
}
