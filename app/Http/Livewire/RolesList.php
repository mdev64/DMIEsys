<?php

namespace App\Http\Livewire;

use App\Models\User;
use App\Traits\Notify;
use App\Traits\System;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesList extends Component
{
    use Notify, System, WithPagination;

    /** @var string|null */
    public $newRole = null;

    /** @var string|null */
    public $role = null;

    /** @var string|null */
    public $user = null;

    /** @var string|null */
    public $userRole = null;

    /** @var array */
    protected $rules = ([
        'newRole' => 'required|string|max:200',
    ]);

    public function render(): View
    {
        return view('livewire.roles-list', [
            'roles' => Role::where('name', '!=', 'Super Admin')->orderBy('id')->paginate(5),
            'all_roles' => Role::where('name', '!=', 'Super Admin')->get(),
            'all_users' => User::active()->with('roles')->get(),
            'permissions' => Permission::orderBy('id')->get(),
            'grouped_permissions' => $this->getGroupedPermissions(),
            'selectRole' => $this->role ? Role::find($this->role) : null,
            'selectUser' => $this->user ? User::find($this->user) : null,
        ]);
    }

    public function Submit(): RedirectResponse
    {
        abort_unless($this->can('change user permission'), 404);
        $this->validate();

        try {
            Role::create([
                'name' => $this->newRole,
            ]);
            $this->passed($this->newRole.' role created');

            return back()->with('toast_success', 'new role added successfully!');
        } catch (\Throwable $th) {
            $this->failed($th);

            return back()->withErrors('Something went wrong!');
        }
    }

    public function DeleteRole(int $role_id): RedirectResponse
    {
        abort_unless($this->can('change user permission'), 404);

        try {
            $role = Role::find($role_id);
            $role->delete();
            $this->passed($role->name.' role deleted');
            $this->notifyHOD($role->name.' role deleted by '.auth()->user()->name);

            return back()->with('toast_success', 'Role deleted successfully!');
        } catch (\Throwable $th) {
            $this->failed($th);

            return back()->withErrors('Something went wrong!');
        }
    }

    public function SetPermissionForRole(string $permission): void
    {
        abort_unless($this->can('change user permission'), 404);

        $role = Role::find($this->role);
        if ($role->hasPermissionTo($permission)) {
            $role->revokePermissionTo($permission);
            $this->passed($role->name.' lost permission to <b>'.$permission.'</b>');
        } else {
            $role->givePermissionTo($permission);
            $this->passed($role->name.' got permission to <b>'.$permission.'</b>');
        }
    }

    public function SetPermissionForUser(string $permission): void
    {
        abort_unless($this->can('change user permission'), 404);

        $user = User::find($this->user);
        if ($user->hasPermissionTo($permission)) {
            $user->revokePermissionTo($permission);
            $this->passed($user->name.' lost permission to <b>'.$permission.'</b>');
            $this->notifyUser($user, 'You lost the permission to <b>'.$permission.'</b>');
        } else {
            $user->givePermissionTo($permission);
            $this->passed($user->name.' got permission to <b>'.$permission.'</b>');
            $this->notifyUser($user, 'Now you have permission to <b>'.$permission.'</b>');
        }
    }

    public function getPermissionDescription(string $name): string
    {
        $descriptions = [
            // Academic
            'add base course' => 'Allows adding a base syllabus course to the system.',
            'see base course' => 'Allows viewing the details of base courses.',
            'edit base course' => 'Allows editing/modifying base course details.',
            'delete base course' => 'Allows deleting a base course from the database.',
            'add course' => 'Allows adding a new course offering for the semester.',
            'see course' => 'Allows viewing active course offerings.',
            'edit course' => 'Allows modifying active course details.',
            'delete course' => 'Allows removing active course offerings.',
            'add mark' => 'Allows entering student exam/assignment marks.',
            'see mark' => 'Allows viewing student academic marks.',
            'edit mark' => 'Allows modifying student academic marks.',
            'delete mark' => 'Allows deleting student academic marks.',
            'add student' => 'Allows registering new students in the system.',
            'see student' => 'Allows viewing student profiles and details.',
            'edit student' => 'Allows editing student profile details.',
            'delete student' => 'Allows removing student records.',
            'add student advisor' => 'Allows assigning advisors to students.',
            'student counselor' => 'Designates user as a student counselor.',
            'add advisory comments' => 'Allows adding counseling/advisory notes to student profiles.',
            'see attainment report' => 'Allows viewing course outcome attainment reports.',
            'see ilo achievement' => 'Allows viewing Intended Learning Outcome achievements.',

            // Laboratory
            'add laboratory' => 'Allows registering new laboratory rooms.',
            'see laboratory' => 'Allows viewing laboratories.',
            'edit laboratory' => 'Allows modifying laboratory room details.',
            'delete laboratory' => 'Allows deleting laboratory rooms.',
            'add machine' => 'Allows adding new equipment/machines to labs.',
            'see machine' => 'Allows viewing list of lab machines.',
            'edit machine' => 'Allows modifying machine details.',
            'delete machine' => 'Allows removing machines.',
            'add maintenance' => 'Allows creating new maintenance records.',
            'update maintenance' => 'Allows updating current maintenance status.',
            'see maintenance' => 'Allows viewing maintenance logs.',
            'edit maintenance' => 'Allows editing maintenance requests.',
            'delete maintenance' => 'Allows deleting maintenance logs.',
            'add inventory' => 'Allows adding items to laboratory inventories.',
            'see inventory' => 'Allows viewing laboratory inventories.',
            'edit inventory' => 'Allows modifying inventory items.',
            'delete inventory' => 'Allows deleting inventory items.',
            'approve booking' => 'Allows approving laboratory space or tool bookings.',

            // General
            'manage dmielib' => 'Allows managing library digital assets.',
            'see dmielib' => 'Allows viewing library resources.',
            'access dmielib' => 'Allows downloading library files and materials.',
            'see pg registration' => 'Allows viewing postgraduate student registrations.',
            'add user' => 'Allows registering new system users.',
            'see user' => 'Allows viewing lists of users and profiles.',
            'edit user' => 'Allows updating user profiles.',
            'deactivate user' => 'Allows suspending/deactivating user accounts.',
            'change user permission' => 'Allows assigning/revoking specific user permissions.',
            'change user role' => 'Allows assigning/revoking user roles.',
            'add new hod' => 'Allows designating a user as the Head of Department.',
            'set pg registration' => 'Allows open/close postgraduate registrations.',
            'see logs' => 'Allows viewing system activity logs.',

            // Other
            'see notification' => 'Allows receiving system notifications.',
            'can chat' => 'Allows using the internal chat system.',
            'update profile picture' => 'Allows users to upload custom profile pictures.',
        ];

        return $descriptions[$name] ?? 'Provides permission to '.$name.'.';
    }

    public function getGroupedPermissions(): array
    {
        $permissions = Permission::orderBy('id')->get();
        $grouped = [
            'view' => [],
            'add' => [],
            'edit' => [],
            'delete' => [],
            'general' => [],
        ];

        foreach ($permissions as $permission) {
            $name = $permission->name;
            if (str_contains($name, 'add')) {
                $grouped['add'][] = $permission;
            } elseif (str_contains($name, 'see') || str_contains($name, 'access')) {
                $grouped['view'][] = $permission;
            } elseif (str_contains($name, 'edit') || str_contains($name, 'update') || str_contains($name, 'change')) {
                $grouped['edit'][] = $permission;
            } elseif (str_contains($name, 'delete') || str_contains($name, 'deactivate')) {
                $grouped['delete'][] = $permission;
            } else {
                $grouped['general'][] = $permission;
            }
        }

        return $grouped;
    }

    public function getGroupStyle(string $group): array
    {
        $styles = [
            'add' => [
                'name' => 'Add Actions',
                'bg' => 'bg-emerald-50/50 dark:bg-emerald-950/10',
                'border' => 'border-emerald-200 dark:border-emerald-800/30',
                'text' => 'text-emerald-800 dark:text-emerald-300',
                'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                'icon' => 'bi-plus-circle-fill text-emerald-600 dark:text-emerald-400',
            ],
            'view' => [
                'name' => 'View / Read Actions',
                'bg' => 'bg-blue-50/50 dark:bg-blue-950/10',
                'border' => 'border-blue-200 dark:border-blue-800/30',
                'text' => 'text-blue-800 dark:text-blue-300',
                'badge' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                'icon' => 'bi-eye-fill text-blue-600 dark:text-blue-400',
            ],
            'edit' => [
                'name' => 'Edit / Modify Actions',
                'bg' => 'bg-amber-50/50 dark:bg-amber-950/10',
                'border' => 'border-amber-200 dark:border-amber-800/30',
                'text' => 'text-amber-800 dark:text-amber-300',
                'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                'icon' => 'bi-pencil-square text-amber-600 dark:text-amber-400',
            ],
            'delete' => [
                'name' => 'Delete / Deactivate Actions',
                'bg' => 'bg-rose-50/50 dark:bg-rose-950/10',
                'border' => 'border-rose-200 dark:border-rose-800/30',
                'text' => 'text-rose-800 dark:text-rose-300',
                'badge' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                'icon' => 'bi-trash-fill text-rose-600 dark:text-rose-400',
            ],
            'general' => [
                'name' => 'General / Other Actions',
                'bg' => 'bg-purple-50/50 dark:bg-purple-950/10',
                'border' => 'border-purple-200 dark:border-purple-800/30',
                'text' => 'text-purple-800 dark:text-purple-300',
                'badge' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                'icon' => 'bi-gear-wide-connected text-purple-600 dark:text-purple-400',
            ],
        ];

        return $styles[$group] ?? $styles['general'];
    }

    public function getPermissionBadgeStyle(string $name): string
    {
        if (str_contains($name, 'add')) {
            return 'bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/30';
        } elseif (str_contains($name, 'see') || str_contains($name, 'access')) {
            return 'bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800/30';
        } elseif (str_contains($name, 'edit') || str_contains($name, 'update') || str_contains($name, 'change')) {
            return 'bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/30';
        } elseif (str_contains($name, 'delete') || str_contains($name, 'deactivate')) {
            return 'bg-rose-100 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/30';
        } else {
            return 'bg-purple-100 text-purple-800 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800/30';
        }
    }

    public function toggleGroupPermissionsForRole(string $group): void
    {
        abort_unless($this->can('change user permission'), 404);

        $role = Role::find($this->role);
        if (! $role) {
            return;
        }

        $grouped = $this->getGroupedPermissions();
        $groupPerms = $grouped[$group] ?? [];
        if (empty($groupPerms)) {
            return;
        }

        $allGranted = true;
        foreach ($groupPerms as $perm) {
            if (! $role->hasPermissionTo($perm->name)) {
                $allGranted = false;
                break;
            }
        }

        foreach ($groupPerms as $perm) {
            if ($allGranted) {
                $role->revokePermissionTo($perm->name);
            } else {
                if (! $role->hasPermissionTo($perm->name)) {
                    $role->givePermissionTo($perm->name);
                }
            }
        }

        $action = $allGranted ? 'revoked' : 'granted';
        $this->passed("All {$group} permissions have been {$action} for role {$role->name}.");
    }

    public function toggleGroupPermissionsForUser(string $group): void
    {
        abort_unless($this->can('change user permission'), 404);

        $user = User::find($this->user);
        if (! $user) {
            return;
        }

        $grouped = $this->getGroupedPermissions();
        $groupPerms = $grouped[$group] ?? [];
        if (empty($groupPerms)) {
            return;
        }

        $allGranted = true;
        foreach ($groupPerms as $perm) {
            if (! $user->hasPermissionTo($perm->name)) {
                $allGranted = false;
                break;
            }
        }

        foreach ($groupPerms as $perm) {
            if ($allGranted) {
                if ($user->hasDirectPermission($perm->name)) {
                    $user->revokePermissionTo($perm->name);
                }
            } else {
                if (! $user->hasPermissionTo($perm->name)) {
                    $user->givePermissionTo($perm->name);
                }
            }
        }

        $action = $allGranted ? 'revoked' : 'granted';
        $this->passed("All direct {$group} permissions have been {$action} for user {$user->name}.");
    }
}
