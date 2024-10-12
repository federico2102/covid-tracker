<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Models\User;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertionHelper;
use Tests\Support\LocationTestHelper;

class AdminLocationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_location()
    {
        $admin = LocationTestHelper::createAdminUser();
        $response = LocationTestHelper::createLocationRequest($this->actingAs($admin), [
            'name' => 'New Location',
            'address' => '123 Test St',
            'geolocation' => '41.3851, 2.1734',
            'max_capacity' => 50,
        ]);

        AssertionHelper::assertSuccessfulResponse($response, route('locations'));
        $this->assertDatabaseHas('locations', ['name' => 'New Location']);
    }

    public function test_admin_can_edit_location()
    {
        $admin = LocationTestHelper::createAdminUser();
        $location = LocationTestHelper::createLocation();

        $response = LocationTestHelper::updateLocationRequest($this->actingAs($admin), $location->id, [
            'name' => 'Updated Location',
            'address' => 'Updated Address',
            'geolocation' => '41.3851, 2.1734',
            'max_capacity' => 100,
        ]);

        AssertionHelper::assertSuccessfulResponse($response, route('locations'));
        $this->assertDatabaseHas('locations', ['name' => 'Updated Location', 'max_capacity' => 100]);
    }

    public function test_admin_can_delete_location()
    {
        $admin = LocationTestHelper::createAdminUser();
        $location = LocationTestHelper::createLocation();

        $response = LocationTestHelper::deleteLocationRequest($this->actingAs($admin), $location->id);

        AssertionHelper::assertSuccessfulResponse($response, route('locations'));
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }

    public function test_non_admin_user_cannot_create_or_edit_location()
    {
        $user = LocationTestHelper::createNonAdminUser();

        // Create location
        $response = LocationTestHelper::createLocationRequest($this->actingAs($user), [
            'name' => 'Non-Admin Location',
            'address' => 'Test Address',
            'geolocation' => '41.3851, 2.1734',
            'max_capacity' => 50,
        ]);
        AssertionHelper::assertForbiddenResponse($response, 'Forbidden');

        // Edit location
        $location = LocationTestHelper::createLocation();
        $response = LocationTestHelper::updateLocationRequest($this->actingAs($user), $location->id, [
            'name' => 'Updated Location',
            'address' => 'Updated Address',
            'geolocation' => '41.3851, 2.1798',
            'max_capacity' => 100,
        ]);
        AssertionHelper::assertForbiddenResponse($response, 'Forbidden');
    }

    public function test_non_admin_user_cannot_delete_location()
    {
        $user = LocationTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        $response = LocationTestHelper::deleteLocationRequest($this->actingAs($user), $location->id);

        AssertionHelper::assertForbiddenResponse($response, 'Forbidden');
    }

    public function test_admin_can_view_locations_list()
    {
        $admin = LocationTestHelper::createAdminUser();
        $location = LocationTestHelper::createLocation();

        $response = $this->actingAs($admin)->get(route('locations'));

        $response->assertStatus(200);
        $response->assertViewIs('locations.index');
        $response->assertSee($location->name);
    }

    public function test_admin_can_view_location_details()
    {
        $admin = LocationTestHelper::createAdminUser();
        $location = LocationTestHelper::createLocation();

        $response = $this->actingAs($admin)->get(route('locations.show', $location->id));

        $response->assertStatus(200);
        $response->assertViewIs('locations.show');
        $response->assertSee($location->name);
    }

    public function test_non_admin_can_view_locations_list()
    {
        $user = LocationTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        $response = $this->actingAs($user)->get(route('locations'));

        $response->assertStatus(200);
        $response->assertViewIs('locations.index');
        $response->assertSee($location->name);
        $response->assertDontSee('Edit'); // Ensure non-admins can't see admin options
    }

    public function test_non_admin_can_view_location_details()
    {
        $user = LocationTestHelper::createNonAdminUser();
        $location = LocationTestHelper::createLocation();

        $response = $this->actingAs($user)->get(route('locations.show', $location->id));

        $response->assertStatus(200);
        $response->assertViewIs('locations.show');
        $response->assertSee($location->name);
        $response->assertDontSee('Edit'); // Ensure non-admins can't see edit option
    }

    public function test_location_form_validation()
    {
        $admin = LocationTestHelper::createAdminUser();

        $response = LocationTestHelper::createLocationRequest($this->actingAs($admin), [
            'name' => '',
            'address' => '',
            'geolocation' => '',
            'max_capacity' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'address', 'geolocation', 'max_capacity']);
    }

    public function test_non_admin_cannot_access_create_form()
    {
        $user = LocationTestHelper::createNonAdminUser();

        $response = $this->actingAs($user)->get(route('locations.create'));

        AssertionHelper::assertForbiddenResponse($response, 'Forbidden');
    }

    public function test_admin_can_access_create_form()
    {
        $admin = LocationTestHelper::createAdminUser();

        $response = $this->actingAs($admin)->get(route('locations.create'));

        $response->assertStatus(200); // Admins should be able to access the form
        $response->assertViewIs('locations.create');
    }

    public function test_invalid_geolocation_format()
    {
        $admin = LocationTestHelper::createAdminUser();

        $response = LocationTestHelper::createLocationRequest($this->actingAs($admin), [
            'name' => 'Test Location',
            'address' => '123 Test St',
            'geolocation' => 'invalid_format', // Invalid geolocation
            'max_capacity' => 100,
        ]);

        $response->assertSessionHasErrors(['geolocation']);
    }

    public function test_admin_can_upload_valid_image()
    {
        $admin = LocationTestHelper::createAdminUser();
        $file = UploadedFile::fake()->image('location.jpg');

        $response = LocationTestHelper::createLocationRequest($this->actingAs($admin), [
            'name' => 'Location with Image',
            'address' => '123 Image St',
            'geolocation' => '41.3851, 2.1734',
            'max_capacity' => 50,
            'picture' => $file,
        ]);

        AssertionHelper::assertSuccessfulResponse($response, route('locations'));
        $this->assertDatabaseHas('locations', ['name' => 'Location with Image']);
    }
}
