<?php

namespace Tests;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait CreatesUser
{
    /**
     * Creates the application.
     */
    public function createUser(string $permission = 'see notification', string $role = 'Test User'): User
    {
        Role::create([
            'name' => $role,
        ]);

        Permission::create([
            'name' => $permission,
        ]);

        $user = new User;
        $user->title = 'Mr';
        $user->name = 'TestUser';
        $user->username = Str::random(15);
        $user->email = Str::random(10).'@email.com';
        $user->password = Hash::make('password');
        $user->newuser = 0;
        $user->active_status = 1;
        $user->save();
        $user->assignRole($role);
        $user->givePermissionTo($permission);

        return $user;
    }

    /**
     * Creates an additional active user without any role or permission.
     */
    public function createPlainUser(string $username): User
    {
        $user = new User;
        $user->title = 'Mr';
        $user->name = $username;
        $user->username = $username;
        $user->email = $username.'@email.com';
        $user->password = Hash::make('password');
        $user->newuser = 0;
        $user->active_status = 1;
        $user->save();

        return $user;
    }
}
