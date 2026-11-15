<?php

namespace App\Traits;

use App\Models\Log;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

trait System
{
    public function can(string $action): bool
    {
        try {
            $thisUser = auth()->user();
            if ($thisUser->hasRole('Super Admin')) {
                return true;
            } elseif ($thisUser->hasPermissionTo($action)) {
                return true;
            } else {
                return false;
            }
        } catch (PermissionDoesNotExist $e) {
            return false;
        }
    }

    public function canAny(array $action): bool
    {
        try {
            $thisUser = auth()->user();
            if ($thisUser->hasRole('Super Admin')) {
                return true;
            } elseif ($thisUser->hasAnyPermission($action)) {
                return true;
            } else {
                return false;
            }
        } catch (PermissionDoesNotExist $e) {
            return false;
        }
    }

    public function passed(string $message): void
    {
        Log::create([
            'action' => $message,
            'user' => auth()->user()->username,
        ]);
    }

    public function failed(object $th): void
    {
        Log::create([
            'action' => $th->getMessage(),
            'state' => 'failed',
            'user' => auth()->user()->username,
        ]);
    }
}
