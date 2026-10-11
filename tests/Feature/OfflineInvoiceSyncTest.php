<?php

namespace Tests\Feature;

use App\Models\Facture;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class OfflineInvoiceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_list_includes_a_section_for_offline_invoices(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('factures.index'))
            ->assertOk()
            ->assertSee('id="offline-invoices"', false)
            ->assertSeeText('Factures non synchronisées')
            ->assertSee('data-offline-invoices-list', false);
    }

    public function test_an_offline_invoice_is_created_from_its_signed_service_catalog(): void
    {
        $user = User::factory()->create();
        $service = Service::create(['nom' => 'Consultation', 'prix' => 125.50]);
        $payload = $this->payload($service, '123e4567-e89b-42d3-a456-426614174000');
        $service->update(['prix' => 999]);

        $this->actingAs($user)
            ->postJson(route('factures.synchronize'), $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Facture synchronisée.');

        $invoice = Facture::firstOrFail();
        $this->assertSame($user->id, $invoice->user_id);
        $this->assertSame('Amadou Diallo', $invoice->patient_nom);
        $this->assertSame('123e4567-e89b-42d3-a456-426614174000', $invoice->offline_uuid);
        $this->assertEquals(251, $invoice->total);
        $this->assertDatabaseHas('facture_lignes', [
            'facture_id' => $invoice->id,
            'designation' => 'Consultation',
            'prix' => 125.50,
            'quantite' => 2,
        ]);
    }

    public function test_retrying_the_same_offline_invoice_does_not_duplicate_it(): void
    {
        $user = User::factory()->create();
        $service = Service::create(['nom' => 'Consultation', 'prix' => 125.50]);
        $payload = $this->payload($service, '123e4567-e89b-42d3-a456-426614174000');

        $this->actingAs($user)->postJson(route('factures.synchronize'), $payload)->assertOk();
        $this->postJson(route('factures.synchronize'), $payload)->assertOk();

        $this->assertDatabaseCount('factures', 1);
        $this->assertDatabaseCount('facture_lignes', 1);
    }

    public function test_offline_invoice_cannot_use_a_service_missing_from_its_signed_catalog(): void
    {
        $user = User::factory()->create();
        $service = Service::create(['nom' => 'Consultation', 'prix' => 125.50]);
        $payload = $this->payload($service, '123e4567-e89b-42d3-a456-426614174000');
        $payload['lignes'][0]['service_id'] = $service->id + 1;

        $this->actingAs($user)
            ->postJson(route('factures.synchronize'), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Une prestation n’appartient pas au catalogue chargé. Reconnectez-vous et rechargez la page.');

        $this->assertDatabaseCount('factures', 0);
    }

    public function test_offline_invoice_cannot_be_synchronized_by_a_different_user(): void
    {
        $firstUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $service = Service::create(['nom' => 'Consultation', 'prix' => 125.50]);
        $payload = $this->payload($service, '123e4567-e89b-42d3-a456-426614174000');

        $this->actingAs($firstUser)->postJson(route('factures.synchronize'), $payload)->assertOk();

        $this->actingAs($otherUser)
            ->postJson(route('factures.synchronize'), $payload)
            ->assertConflict();

        $this->assertDatabaseCount('factures', 1);
    }

    public function test_reusing_an_idempotency_key_for_a_different_invoice_is_rejected(): void
    {
        $user = User::factory()->create();
        $service = Service::create(['nom' => 'Consultation', 'prix' => 125.50]);
        $payload = $this->payload($service, '123e4567-e89b-42d3-a456-426614174000');

        $this->actingAs($user)->postJson(route('factures.synchronize'), $payload)->assertOk();

        $payload['patient_nom'] = 'Different patient';
        $this->postJson(route('factures.synchronize'), $payload)->assertConflict();

        $this->assertDatabaseCount('factures', 1);
    }

    private function payload(Service $service, string $idempotencyKey): array
    {
        $catalog = [[
            'id' => $service->id,
            'nom' => $service->nom,
            'prix' => (string) $service->prix,
        ]];

        return [
            'idempotency_key' => $idempotencyKey,
            'patient_nom' => 'Amadou Diallo',
            'patient_telephone' => '770000000',
            'catalog_proof' => Crypt::encryptString(json_encode($catalog, JSON_THROW_ON_ERROR)),
            'lignes' => [
                ['service_id' => $service->id, 'quantite' => 2],
            ],
        ];
    }
}
