<?php

namespace Tests\Feature;

use App\Models\Facture;
use App\Models\FactureLigne;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_access_admin_dashboard_or_manage_services(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->get(route('admin.services.index'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_staff_only_sees_and_opens_their_own_invoices(): void
    {
        $staff = User::factory()->create();
        $anotherStaff = User::factory()->create();
        $ownInvoice = $this->createInvoice($staff, 'My invoice');
        $otherInvoice = $this->createInvoice($anotherStaff, 'Private invoice');

        $this->actingAs($staff)
            ->get(route('factures.index'))
            ->assertOk()
            ->assertSeeText('My invoice')
            ->assertDontSeeText('Private invoice');

        $this->get(route('factures.show', $otherInvoice))->assertForbidden();
        $this->get(route('factures.show', $ownInvoice))->assertOk();
        $this->get(route('admin.factures.edit', $ownInvoice))->assertForbidden();
    }

    public function test_admin_can_view_all_invoices_and_edit_invoice_lines(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create();
        $invoice = $this->createInvoice($staff, 'Original patient');
        $line = $invoice->lignes()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('factures.index'))
            ->assertOk()
            ->assertSeeText('Original patient');

        $this->get(route('admin.factures.edit', $invoice))->assertOk();

        $this->put(route('admin.factures.update', $invoice), [
            'patient_nom' => 'Updated patient',
            'patient_telephone' => '123456',
            'lignes' => [
                [
                    'id' => $line->id,
                    'designation' => 'Updated service',
                    'prix' => 125.50,
                    'quantite' => 2,
                ],
                [
                    'designation' => 'Additional service',
                    'prix' => 30,
                    'quantite' => 1,
                ],
            ],
        ])->assertRedirect(route('factures.show', $invoice));

        $invoice->refresh();
        $this->assertSame('Updated patient', $invoice->patient_nom);
        $this->assertSame('123456', $invoice->patient_telephone);
        $this->assertEquals(281, $invoice->total);
        $this->assertDatabaseHas('facture_lignes', [
            'id' => $line->id,
            'designation' => 'Updated service',
            'prix' => 125.50,
            'quantite' => 2,
        ]);
        $this->assertDatabaseHas('facture_lignes', [
            'facture_id' => $invoice->id,
            'designation' => 'Additional service',
            'prix' => 30,
            'quantite' => 1,
        ]);
    }

    public function test_admin_can_create_staff_accounts_but_cannot_delete_the_last_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Staff',
                'email' => 'new-staff@example.com',
                'role' => 'staff',
                'password' => 'safe-password-123',
                'password_confirmation' => 'safe-password-123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new-staff@example.com',
            'role' => 'staff',
        ]);

        $this->patch(route('admin.users.status', $admin))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_admin_dashboard_and_user_forms_are_available_to_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Tableau de bord')
            ->assertSeeText('Comptes de l’équipe');

        $this->get(route('admin.users.create'))->assertOk()->assertSeeText('Créer un compte');
        $this->get(route('admin.services.index'))->assertOk();
    }

    public function test_login_redirects_admins_to_their_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_dashboard_route_redirects_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_deactivate_and_reactivate_a_staff_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $staff))
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($staff->fresh()->is_active);

        $this->post(route('logout'));
        $this->post(route('login'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $staff))
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($staff->fresh()->is_active);
    }

    public function test_a_disabled_user_is_logged_out_on_the_next_request(): void
    {
        $staff = User::factory()->create(['is_active' => false]);

        $this->actingAs($staff)
            ->get(route('factures.create'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_admin_bootstrap_command_can_promote_an_existing_user(): void
    {
        $user = User::factory()->create();

        $this->artisan('app:create-admin', ['email' => $user->email])
            ->expectsConfirmation("Promouvoir {$user->email} au rôle administrateur ?", 'yes')
            ->assertExitCode(0);

        $this->assertTrue($user->fresh()->isAdmin());
    }

    private function createInvoice(User $user, string $patientName): Facture
    {
        $invoice = Facture::create([
            'numero' => 'INV-'.uniqid(),
            'patient_nom' => $patientName,
            'total' => 100,
            'user_id' => $user->id,
        ]);

        FactureLigne::create([
            'facture_id' => $invoice->id,
            'designation' => 'Initial service',
            'prix' => 100,
            'quantite' => 1,
        ]);

        return $invoice;
    }
}
