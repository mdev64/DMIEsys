<?php

namespace Tests\Feature;

use App\Http\Livewire\RolesList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_access_permissions_page(): void
    {
        $user = $this->createUser('change user permission');

        $response = $this->actingAs($user)
            ->get('/dmiesys/user-permissions');

        $response->assertStatus(200);
    }

    public function test_permissions_page_works_when_user_has_no_role(): void
    {
        $user = $this->createUser('change user permission');

        // Create an active user with no role
        $noRoleUser = new User;
        $noRoleUser->title = 'Mr';
        $noRoleUser->name = 'No Role User';
        $noRoleUser->username = 'norole';
        $noRoleUser->email = 'norole@email.com';
        $noRoleUser->password = Hash::make('password');
        $noRoleUser->newuser = 0;
        $noRoleUser->active_status = 1;
        $noRoleUser->save();

        $response = $this->actingAs($user)
            ->get('/dmiesys/user-permissions');

        $response->assertStatus(200);
    }

    public function test_roles_list_renders_and_selects_role_and_user(): void
    {
        $user = $this->createUser('change user permission');
        $role = Role::create(['name' => 'TestRole']);
        $permission = Permission::create(['name' => 'test permission']);

        Livewire::actingAs($user)
            ->test(RolesList::class)
            ->assertSet('role', null)
            ->set('role', $role->id)
            ->assertSee('test permission')
            ->set('user', $user->id)
            ->assertSee('test permission');
    }
}
