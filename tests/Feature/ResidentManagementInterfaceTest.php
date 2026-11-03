<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentManagementInterfaceTest extends TestCase
{
    use RefreshDatabase;

    private function formData(array $overrides = []): array
    {
        return array_merge([
            'firstName' => 'Maria',
            'lastName' => 'Santos',
            'address' => 'Barangay Santo Tomas',
            'contactNumber' => '09171234567',
            'email' => 'maria@example.com',
        ], $overrides);
    }

    private function resident(array $overrides = []): Resident
    {
        return (new ResidentRepository)->save(new Resident(array_merge(
            $this->formData(),
            ['status' => Resident::STATUS_ACTIVE],
            $overrides,
        )));
    }

    public function test_management_page_loads(): void
    {
        $this->get('/residents')->assertOk()
            ->assertSee('Resident Management')
            ->assertSee('Register Resident')
            ->assertSee('Search Residents');
    }

    public function test_home_page_links_to_resident_management(): void
    {
        $this->get('/')->assertOk()->assertSee('Open Resident Management');
    }

    public function test_list_displays_persisted_residents_and_required_information_in_t05_order(): void
    {
        $santos = $this->resident();
        $cruz = $this->resident(['firstName' => 'Juan', 'lastName' => 'Cruz', 'email' => 'juan@example.com']);

        $response = $this->get('/residents')->assertOk()
            ->assertSee((string) $santos->id)
            ->assertSee('Maria')->assertSee('Santos')
            ->assertSee('Barangay Santo Tomas')
            ->assertSee('09171234567')
            ->assertSee('maria@example.com')
            ->assertSee('Active')
            ->assertSee('Edit')
            ->assertSee('Deactivate');

        $this->assertLessThan(
            strpos($response->getContent(), 'Santos'),
            strpos($response->getContent(), 'Cruz'),
        );
        $response->assertSee((string) $cruz->id);
    }

    public function test_search_uses_partial_case_insensitive_first_or_last_name_matching(): void
    {
        $this->resident();
        $this->resident(['firstName' => 'Juan', 'lastName' => 'Cruz', 'email' => 'juan@example.com']);

        $this->get('/residents?search=%20%20sAn%20%20')->assertOk()
            ->assertSee('Maria')->assertDontSee('Juan');
        $this->get('/residents?search=mAr')->assertOk()
            ->assertSee('Maria')->assertDontSee('Juan');
    }

    public function test_blank_search_lists_all_residents(): void
    {
        $this->resident();
        $this->resident(['firstName' => 'Juan', 'lastName' => 'Cruz', 'email' => 'juan@example.com']);

        $this->get('/residents?search=%20%20%20')->assertOk()
            ->assertSee('Maria')->assertSee('Juan');
    }

    public function test_empty_or_unmatched_list_shows_clear_message(): void
    {
        $this->get('/residents')->assertOk()->assertSee('No Residents found.');
        $this->resident();
        $this->get('/residents?search=Nobody')->assertOk()->assertSee('No Residents found.');
    }

    public function test_registration_form_has_five_labeled_fields_without_id_or_status_input(): void
    {
        $this->get('/residents/create')->assertOk()
            ->assertSee('Register Resident')
            ->assertSee('First Name')->assertSee('Last Name')
            ->assertSee('Address')->assertSee('Contact Number')->assertSee('Email')
            ->assertDontSee('name="id"', false)
            ->assertDontSee('name="status"', false);
    }

    public function test_valid_registration_persists_active_resident_and_redirects_to_list(): void
    {
        $this->post('/residents', $this->formData())
            ->assertRedirect('/residents')
            ->assertSessionHas('success');

        $resident = Resident::query()->firstOrFail();
        $this->assertGreaterThan(0, $resident->id);
        $this->assertSame('Active', $resident->status);
        $this->get('/residents')->assertOk()->assertSee('Maria Santos');
    }

    public function test_invalid_registration_shows_errors_preserves_input_and_does_not_persist(): void
    {
        $this->from('/residents/create')->post('/residents', $this->formData([
            'firstName' => '', 'email' => 'invalid',
        ]))->assertRedirect('/residents/create')
            ->assertSessionHasErrors(['first_name', 'email'])
            ->assertSessionHas('_old_input.lastName', 'Santos');

        $this->assertDatabaseCount('residents', 0);
    }

    public function test_edit_form_shows_saved_values_and_read_only_identity_and_status(): void
    {
        $resident = $this->resident();

        $this->get("/residents/{$resident->id}/edit")->assertOk()
            ->assertSee('Edit Resident')
            ->assertSee('value="Maria"', false)
            ->assertSee('value="Santos"', false)
            ->assertSee('value="09171234567"', false)
            ->assertSee('value="maria@example.com"', false)
            ->assertSee('Barangay Santo Tomas')
            ->assertSee('Active')
            ->assertDontSee('name="id"', false)
            ->assertDontSee('name="status"', false);
    }

    public function test_valid_update_changes_only_editable_information(): void
    {
        $resident = $this->resident(['status' => 'Inactive']);

        $this->patch("/residents/{$resident->id}", $this->formData([
            'firstName' => 'Ana', 'address' => 'New address',
            'status' => 'Active', 'id' => 99999,
        ]))->assertRedirect('/residents')->assertSessionHas('success');

        $resident->refresh();
        $this->assertSame('Ana', $resident->firstName);
        $this->assertSame('New address', $resident->address);
        $this->assertSame('Inactive', $resident->status);
        $this->assertDatabaseHas('residents', ['id' => $resident->id, 'firstName' => 'Ana']);
    }

    public function test_invalid_update_shows_errors_and_preserves_saved_information(): void
    {
        $resident = $this->resident();

        $this->from("/residents/{$resident->id}/edit")
            ->patch("/residents/{$resident->id}", $this->formData([
                'firstName' => 'Changed', 'contactNumber' => 'bad',
            ]))->assertRedirect("/residents/{$resident->id}/edit")
            ->assertSessionHasErrors(['contact_number'])
            ->assertSessionHas('_old_input.firstName', 'Changed');

        $this->assertSame('Maria', $resident->fresh()->firstName);
        $this->assertSame('09171234567', $resident->fresh()->contactNumber);
    }

    public function test_missing_resident_edit_returns_404_without_creating_record(): void
    {
        $this->get('/residents/99999/edit')->assertNotFound();
        $this->assertDatabaseCount('residents', 0);
    }

    public function test_post_deactivation_preserves_record_and_shows_inactive_status(): void
    {
        $resident = $this->resident();

        $this->post("/residents/{$resident->id}/deactivate")
            ->assertRedirect('/residents')->assertSessionHas('success');

        $this->assertSame('Inactive', $resident->fresh()->status);
        $this->assertDatabaseCount('residents', 1);
        $this->get('/residents')->assertOk()->assertSee('Maria Santos')->assertSee('Inactive');
    }

    public function test_inactive_resident_remains_searchable_without_deactivate_action(): void
    {
        $resident = $this->resident(['status' => 'Inactive']);

        $this->get('/residents?search=santos')->assertOk()
            ->assertSee('Maria Santos')
            ->assertSee('Inactive')
            ->assertDontSee("/residents/{$resident->id}/deactivate", false);
    }

    public function test_get_requests_never_deactivate_resident(): void
    {
        $resident = $this->resident();

        $this->get('/residents')->assertOk();
        $this->get('/residents?search=Maria')->assertOk();
        $this->get('/residents/create')->assertOk();
        $this->get("/residents/{$resident->id}/edit")->assertOk();
        $this->get("/residents/{$resident->id}/deactivate")->assertStatus(405);

        $this->assertSame('Active', $resident->fresh()->status);
    }

    public function test_repeated_deactivation_is_safe_and_missing_mutations_return_404(): void
    {
        $resident = $this->resident();
        $this->post("/residents/{$resident->id}/deactivate")->assertRedirect('/residents');
        $this->post("/residents/{$resident->id}/deactivate")->assertRedirect('/residents');
        $this->assertSame('Inactive', $resident->fresh()->status);
        $this->patch('/residents/99999', $this->formData())->assertNotFound();
        $this->post('/residents/99999/deactivate')->assertNotFound();
    }
}
