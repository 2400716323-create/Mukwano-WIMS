<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Services\AlertGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class GenerateInventoryAlerts extends Command
{
    protected $signature = 'inventory:generate-alerts';

    protected $description = 'Generate and optionally email new inventory alerts.';

    public function handle(AlertGenerationService $alertGenerationService): int
    {
        $newAlerts = $alertGenerationService->generate();
        $recipient = config('mail.alert_recipient');
        $mailer = config('mail.default');
        if (is_string($recipient) && $recipient !== '' && ! in_array($mailer, ['log', 'array'], true)) {
            foreach ($newAlerts as $alert) {
                try {
                    $this->emailAlert($alert, $recipient);
                } catch (Throwable $exception) {
                    report($exception);
                    $this->error("Unable to email alert #{$alert->id}; the alert remains available in the alert centre.");
                }
            }
        }

        $this->info(sprintf(
            'Inventory alerts checked. %d new or reactivated alert(s).',
            $newAlerts->count(),
        ));

        return self::SUCCESS;
    }

    private function emailAlert(Alert $alert, string $recipient): void
    {
        Mail::raw(
            "{$alert->title}\n\n{$alert->description}\n\n".($alert->action_url ?? ''),
            fn ($message) => $message->to($recipient)->subject("Mukwano WIMS alert: {$alert->title}"),
        );
    }
}
