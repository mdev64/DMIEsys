<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->put(route('user-profile.update', $user->id), [
                '_type' => 'profile_update_request',
                'title' => 'Mr',
                'name' => 'John Doe Updated',
                'email' => 'updated@email.com',
                'phone' => '0771234567',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('toast_success', 'Email updated successfully.');

        $user->refresh();
        $this->assertEquals('John Doe Updated', $user->name);
        $this->assertEquals('updated@email.com', $user->email);
    }
}
