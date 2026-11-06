<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\InventorySetting;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_assign_and_deactivate_users_with_audit_entries(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $this->assertSame('admin', $administrator->role);
        $this->assertTrue($administrator->is_active);
        $this->actingAs($administrator)
            ->post(route('admin.users.store'), [
                'name' => 'Warehouse supervisor',
                'email' => 'supervisor@example.test',
                'password' => 'A-strong-password-123',
                'password_confirmation' => 'A-strong-password-123',
                'role' => 'user',
            ])
            ->assertRedirect(route('admin.users.index'));

        $managedUser = User::query()->where('email', 'supervisor@example.test')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $managedUser->getMorphClass(),
            'auditable_id' => $managedUser->id,
            'event' => 'created',
        ]);

        $this->patch(route('admin.users.role', $managedUser), ['role' => 'supervisor'])
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $managedUser->id,
            'event' => 'role_changed',
        ]);

        $this->patch(route('admin.users.status', $managedUser))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $managedUser->id,
            'event' => 'deactivated',
        ]);

        $this->patch(route('admin.users.status', $managedUser))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $managedUser->id,
            'event' => 'activated',
        ]);
    }

    public function test_audit_and_administration_pages_are_not_available_to_ordinary_users(): void
    {
        $user = User::factory()->create();
        $log = AuditLog::query()->create([
            'user_id' => $user->id,
            'auditable_type' => 'system',
            'auditable_id' => 0,
            'event' => 'test_event',
            'new_values' => ['sample' => true],
        ]);
        $this->actingAs($user);

        $this->get(route('admin.audit.index'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->post(route('admin.audit.index'))->assertMethodNotAllowed();
        $this->patch(route('admin.audit.index', $log))->assertMethodNotAllowed();
        $this->delete(route('admin.audit.index', $log))->assertMethodNotAllowed();

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id, 'event' => 'test_event']);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_the_last_active_administrator_cannot_be_demoted_or_deactivated(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $this->actingAs($administrator);

        $this->patch(route('admin.users.role', $administrator), ['role' => 'user'])
            ->assertSessionHasErrors('role');
        $this->patch(route('admin.users.status', $administrator))
            ->assertSessionHasErrors('user');

        $this->assertSame('admin', $administrator->fresh()->role);
        $this->assertTrue($administrator->fresh()->is_active);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_administrator_can_view_filtered_audit_logs_and_settings_changes_are_audited(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $this->actingAs($administrator)
            ->put(route('admin.settings.update'), [
                'company_name' => 'Mukwano Industries',
                'variance_threshold_percent' => '7.500',
                'accuracy_tolerance_percent' => '1.000',
                'expiry_soon_days' => 20,
                'expiry_warning_days' => 45,
                'expiry_notice_days' => 75,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $administrator->id,
            'event' => 'system_settings_updated',
        ]);

        $this->get(route('admin.audit.index', ['event' => 'system_settings_updated']))
            ->assertOk()
            ->assertSee('System settings updated');
        $this->get(route('admin.roles.index'))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_company_logo_upload_is_stored_and_included_in_the_settings_audit(): void
    {
        Storage::fake('public');
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)
            ->put(route('admin.settings.update'), [
                'company_name' => 'Mukwano Industries',
                'logo' => UploadedFile::fake()->image('company-logo.png'),
                'variance_threshold_percent' => '5.000',
                'accuracy_tolerance_percent' => '0.000',
                'expiry_soon_days' => 30,
                'expiry_warning_days' => 60,
                'expiry_notice_days' => 90,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $settings = InventorySetting::query()->firstOrFail();
        $this->assertNotNull($settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => InventorySetting::class,
            'auditable_id' => $settings->id,
            'event' => 'system_settings_updated',
            'user_id' => $administrator->id,
        ]);
    }

    public function test_profile_and_password_changes_are_audited_without_storing_the_password(): void
    {
        $user = User::factory()->create(['password' => 'Old-password-123']);
        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Updated Warehouse User',
                'email' => $user->email,
            ])
            ->assertRedirect(route('profile.edit'));

        $this->put(route('profile.password'), [
            'current_password' => 'Old-password-123',
            'password' => 'New-password-456',
            'password_confirmation' => 'New-password-456',
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $user->id,
            'event' => 'profile_updated',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $user->id,
            'event' => 'password_changed',
            'new_values->password_changed' => true,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'new_values->password' => 'New-password-456',
        ]);
    }

    public function test_successful_login_is_written_to_the_audit_log(): void
    {
        Event::dispatch(new Login('web', User::factory()->create(), false));

        $this->assertDatabaseHas('audit_logs', ['event' => 'login']);
    }
}
