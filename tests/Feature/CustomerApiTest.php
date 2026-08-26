<?php

namespace Tests\Feature;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A valid payload for creating a customer.
     *
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Amina',
            'last_name' => 'Traore',
            'email' => 'amina.traore@example.com',
            'telephone' => '+225 07 11 22 33',
            'adresse' => 'Cocody, Riviera 3, Abidjan',
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // Creation
    // ---------------------------------------------------------------

    public function test_it_creates_a_customer_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/customers', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.firstName', 'Amina')
            ->assertJsonPath('data.lastName', 'Traore')
            ->assertJsonPath('data.email', 'amina.traore@example.com')
            ->assertJsonPath('data.status', CustomerStatus::Active->value);

        $this->assertDatabaseHas('customers', [
            'email' => 'amina.traore@example.com',
            'first_name' => 'Amina',
            'status' => CustomerStatus::Active->value,
        ]);
    }

    public function test_it_defaults_a_new_customer_to_the_active_status(): void
    {
        $this->postJson('/api/v1/customers', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_it_generates_a_public_ulid_and_never_exposes_the_internal_id(): void
    {
        $response = $this->postJson('/api/v1/customers', $this->validPayload())
            ->assertCreated();

        $customer = Customer::sole();

        // L'identifiant expose est le public_id, jamais l'auto-increment.
        $response->assertJsonPath('data.id', $customer->public_id);
        $this->assertSame(26, strlen($response->json('data.id')));
        $this->assertArrayNotHasKey('id', $response->json('data') === null ? [] : array_diff_key(
            $response->json('data'),
            ['id' => null],
        ));
        $this->assertNotSame((string) $customer->id, $response->json('data.id'));
    }

    // ---------------------------------------------------------------
    // Email deja utilise
    // ---------------------------------------------------------------

    public function test_it_rejects_a_duplicate_email(): void
    {
        Customer::factory()->create(['email' => 'amina.traore@example.com']);

        $response = $this->postJson('/api/v1/customers', $this->validPayload());

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors('email');

        // Aucun second client n'a ete cree.
        $this->assertSame(1, Customer::count());
    }

    public function test_it_rejects_a_creation_missing_required_fields(): void
    {
        $this->postJson('/api/v1/customers', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email']);

        $this->assertDatabaseCount('customers', 0);
    }

    // ---------------------------------------------------------------
    // Client inexistant
    // ---------------------------------------------------------------

    public function test_it_returns_404_when_consulting_an_unknown_customer(): void
    {
        $this->getJson('/api/v1/customers/01FZ1G5X9K0J8Y2Q3W4E5R6T7U8V')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND')
            ->assertJsonPath('message', 'La ressource demandee est introuvable.');
    }

    public function test_an_existing_customer_is_resolved_by_its_public_id(): void
    {
        $customer = Customer::factory()->create();

        $this->getJson("/api/v1/customers/{$customer->public_id}")
            ->assertOk()
            ->assertJsonPath('data.id', $customer->public_id)
            ->assertJsonPath('data.email', $customer->email);

        // L'auto-increment interne ne resout aucune route.
        $this->getJson("/api/v1/customers/{$customer->id}")
            ->assertNotFound();
    }

    // ---------------------------------------------------------------
    // Structure ApiResponse (succes)
    // ---------------------------------------------------------------

    public function test_a_single_resource_follows_the_api_response_envelope(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/v1/customers/{$customer->public_id}")
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id', 'firstName', 'lastName', 'email',
                    'telephone', 'adresse', 'status',
                    'orders', 'createdAt', 'updatedAt',
                ],
            ]);

        $this->assertTrue($response->json('success'));

        // Une reponse en succes ne porte jamais les cles d'erreur.
        $body = $response->json();
        $this->assertArrayNotHasKey('error_code', $body);
        $this->assertArrayNotHasKey('errors', $body);
    }

    public function test_a_created_resource_carries_a_message_and_a_201(): void
    {
        $this->postJson('/api/v1/customers', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Client cree avec succes.')
            ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_a_paginated_list_exposes_meta_and_links(): void
    {
        Customer::factory()->count(18)->create();

        $response = $this->getJson('/api/v1/customers?per_page=5')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [['id', 'firstName', 'email', 'status']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);

        $this->assertCount(5, $response->json('data'));
        $this->assertSame(18, $response->json('meta.total'));
        $this->assertSame(4, $response->json('meta.last_page'));

        // La pagination de Laravel n'est pas recopiee telle quelle :
        // la liste verbeuse des boutons de page est retiree.
        $this->assertArrayNotHasKey('links', $response->json('meta'));
    }

    public function test_a_deletion_returns_204_with_an_empty_body(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/api/v1/customers/{$customer->public_id}")
            ->assertNoContent();

        $this->assertSame('', $response->getContent());
        $this->assertDatabaseCount('customers', 0);
    }

    // ---------------------------------------------------------------
    // Structure ErrorResponse (erreur)
    // ---------------------------------------------------------------

    public function test_a_validation_error_follows_the_error_response_envelope(): void
    {
        $response = $this->postJson('/api/v1/customers', ['email' => 'pas-un-email'])
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error_code',
                'errors' => ['email'],
            ]);

        $this->assertFalse($response->json('success'));
        $this->assertIsString($response->json('message'));
        $this->assertSame('VALIDATION_ERROR', $response->json('error_code'));

        // Une reponse en erreur ne porte jamais de data.
        $this->assertArrayNotHasKey('data', $response->json());
    }

    public function test_a_not_found_error_follows_the_error_response_envelope(): void
    {
        $response = $this->getJson('/api/v1/customers/inexistant')
            ->assertNotFound()
            ->assertJsonStructure(['success', 'message', 'error_code']);

        $this->assertFalse($response->json('success'));
        $this->assertSame('RESOURCE_NOT_FOUND', $response->json('error_code'));

        // Pas de details de champ sur un 404 : la cle errors est omise.
        $this->assertArrayNotHasKey('errors', $response->json());
        $this->assertArrayNotHasKey('data', $response->json());
    }

    public function test_an_unknown_status_filter_is_rejected_rather_than_ignored(): void
    {
        Customer::factory()->count(3)->create();

        $this->getJson('/api/v1/customers?status=nimportequoi')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors('status');
    }

    public function test_the_error_code_is_stable_across_error_categories(): void
    {
        $cases = [
            // [methode, uri, payload, statut attendu, error_code attendu]
            ['postJson', '/api/v1/customers', [], 422, 'VALIDATION_ERROR'],
            ['getJson', '/api/v1/customers/inexistant', null, 404, 'RESOURCE_NOT_FOUND'],
            ['putJson', '/api/v1/customers', [], 405, 'METHOD_NOT_ALLOWED'],
        ];

        foreach ($cases as [$method, $uri, $payload, $status, $errorCode]) {
            $response = $payload === null
                ? $this->{$method}($uri)
                : $this->{$method}($uri, $payload);

            $response->assertStatus($status)
                ->assertJsonPath('success', false)
                ->assertJsonPath('error_code', $errorCode);
        }
    }
}
