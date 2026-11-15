<div class="flex flex-col justify-center h-full">
    <!-- Table -->
    <div
        class="w-full mx-auto bg-white dark:bg-gray-800 shadow-md dark:shadow-gray-900/80 rounded-sm border border-gray-200 dark:border-gray-600">

        <div class="m-10">

            <span class="text-2xl font-semibold dark:text-gray-400">Add a Permission to a Role</span>

            <div class="col-md-12 mb-4 mt-4">
                <label for="role" class="form-label">Select a Role</label>
                <select id="role" class="form-select w-full" wire:model.live="role" required>
                    <option value="{{ null }}" selected>-- Select a role --</option>
                    @foreach ($all_roles as $all_role)
                        <option value="{{ $all_role->id }}">{{ $all_role->name }}</option>
                    @endforeach
                </select>
            </div>


            @if ($selectRole)
                <div class="space-y-4 mb-6">
                    @foreach ($grouped_permissions as $group => $groupPerms)
                        @if (count($groupPerms) > 0)
                            @php
                                $groupStyle = $this->getGroupStyle($group);
                            @endphp
                            <div class="p-4 rounded-xl border {{ $groupStyle['bg'] }} {{ $groupStyle['border'] }} shadow-sm">
                                <div class="flex items-center justify-between mb-3 border-b border-gray-200/50 dark:border-gray-700/50 pb-2">
                                    <div class="flex items-center space-x-2">
                                        <i class="bi {{ $groupStyle['icon'] }} text-lg"></i>
                                        <span class="text-sm font-semibold {{ $groupStyle['text'] }}">{{ $groupStyle['name'] }}</span>
                                    </div>
                                    <button type="button" 
                                            wire:click="toggleGroupPermissionsForRole('{{ $group }}')"
                                            class="text-xs px-2.5 py-1 rounded-lg border {{ $groupStyle['border'] }} hover:bg-white dark:hover:bg-gray-800 transition duration-150 ease-in-out font-medium {{ $groupStyle['text'] }} shadow-sm">
                                        Toggle All
                                    </button>
                                </div>
                                <div class="grid 2xl:grid-cols-4 lg:grid-cols-3 md:grid-cols-2 gap-y-3 gap-x-4 pl-2">
                                    @foreach ($groupPerms as $permission)
                                        <div class="flex items-center space-x-1.5 min-h-[1.5rem]">
                                            <input class="form-check cursor-pointer" type="checkbox" value="{{ $permission->name }}"
                                                @if ($selectRole->hasPermissionTo($permission->name)) checked @endif
                                                wire:click="SetPermissionForRole('{{ $permission->name }}')"
                                                id="checkbox_role_{{ $group }}_{{ $loop->index }}">
                                            <label class="form-check-label flex items-center cursor-pointer select-none text-gray-700 dark:text-gray-300" for="checkbox_role_{{ $group }}_{{ $loop->index }}">
                                                <span>{{ $permission->name }}</span>
                                            </label>
                                            <div x-data="{ open: false }" class="relative inline-flex items-center">
                                                <button type="button" @mouseenter="open = true" @mouseleave="open = false" @focus="open = true" @blur="open = false" class="focus:outline-none text-gray-400 hover:text-blue-600 transition-colors duration-150">
                                                    <i class="bi bi-info-circle text-xs cursor-help"></i>
                                                </button>
                                                <div x-show="open" 
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0 translate-y-1"
                                                     x-transition:enter-end="opacity-100 translate-y-0"
                                                     x-transition:leave="transition ease-in duration-150"
                                                     x-transition:leave-start="opacity-100 translate-y-0"
                                                     x-transition:leave-end="opacity-0 translate-y-1"
                                                     class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-56 p-2.5 bg-gray-950/95 dark:bg-gray-900/95 backdrop-blur-sm text-white text-xs font-normal rounded-xl shadow-xl z-50 border border-gray-800 pointer-events-none"
                                                     style="display: none;">
                                                    <div class="font-semibold text-blue-400 mb-1 capitalize">{{ str_replace(' ', ' ', $permission->name) }}</div>
                                                    <div class="text-gray-300">{{ $this->getPermissionDescription($permission->name) }}</div>
                                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-950/95 dark:border-t-gray-900/95"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
            <hr>
            <br>
            <span class="text-2xl font-semibold  dark:text-gray-400">Add a Permission to a User</span>

            <div class="col-md-12 mb-3 mt-4">
                <label for="user" class="form-label">Select a User</label>
                <select id="user" class="form-select w-full" wire:model.live="user" required>
                    <option value="{{ null }}" selected>-- Select a user --</option>
                    @foreach ($all_users as $all_user)
                        <option value="{{ $all_user->id }}">{{ $all_user->name }}
                            ({{ $all_user->getRoleNames()->first() ?? 'No Role' }})
                        </option>
                    @endforeach
                </select>
            </div>


            @if ($selectUser)
                <div class="p-4 mb-4 text-sm text-yellow-700 bg-yellow-100 rounded-lg dark:bg-yellow-200 dark:text-yellow-800"
                    role="alert">
                    <strong class="font-bold">Note! </strong>
                    <span class="block sm:inline">You can't remove the permissions which are assigned to the
                        user by their Role. They are colored in gray.</span>
                </div>
                <div class="space-y-4 mb-6">
                    @foreach ($grouped_permissions as $group => $groupPerms)
                        @if (count($groupPerms) > 0)
                            @php
                                $groupStyle = $this->getGroupStyle($group);
                            @endphp
                            <div class="p-4 rounded-xl border {{ $groupStyle['bg'] }} {{ $groupStyle['border'] }} shadow-sm">
                                <div class="flex items-center justify-between mb-3 border-b border-gray-200/50 dark:border-gray-700/50 pb-2">
                                    <div class="flex items-center space-x-2">
                                        <i class="bi {{ $groupStyle['icon'] }} text-lg"></i>
                                        <span class="text-sm font-semibold {{ $groupStyle['text'] }}">{{ $groupStyle['name'] }}</span>
                                    </div>
                                    <button type="button" 
                                            wire:click="toggleGroupPermissionsForUser('{{ $group }}')"
                                            class="text-xs px-2.5 py-1 rounded-lg border {{ $groupStyle['border'] }} hover:bg-white dark:hover:bg-gray-800 transition duration-150 ease-in-out font-medium {{ $groupStyle['text'] }} shadow-sm">
                                        Toggle All
                                    </button>
                                </div>
                                <div class="grid 2xl:grid-cols-4 lg:grid-cols-3 md:grid-cols-2 gap-y-3 gap-x-4 pl-2">
                                    @foreach ($groupPerms as $permission)
                                        <div class="flex items-center space-x-1.5 min-h-[1.5rem]">
                                            <input
                                                class="form-check cursor-pointer @if (!$selectUser->hasDirectPermission($permission->name)) checked:bg-gray-400 dark:checked:bg-gray-400 @else @endif"
                                                type="checkbox" value="{{ $permission->name }}"
                                                @if ($selectUser->hasPermissionTo($permission->name)) checked @if (!$selectUser->hasDirectPermission($permission->name)) disabled @endif
                                                @endif
                                                wire:click="SetPermissionForUser('{{ $permission->name }}')"
                                                id="checkbox_user_{{ $group }}_{{ $loop->index }}">
                                            <label class="form-check-label flex items-center cursor-pointer select-none @if (!$selectUser->hasDirectPermission($permission->name) && $selectUser->hasPermissionTo($permission->name)) text-gray-400 @else text-gray-700 dark:text-gray-300 @endif" for="checkbox_user_{{ $group }}_{{ $loop->index }}">
                                                <span>{{ $permission->name }}</span>
                                            </label>
                                            <div x-data="{ open: false }" class="relative inline-flex items-center">
                                                <button type="button" @mouseenter="open = true" @mouseleave="open = false" @focus="open = true" @blur="open = false" class="focus:outline-none text-gray-400 hover:text-blue-600 transition-colors duration-150">
                                                    <i class="bi bi-info-circle text-xs cursor-help"></i>
                                                </button>
                                                <div x-show="open" 
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0 translate-y-1"
                                                     x-transition:enter-end="opacity-100 translate-y-0"
                                                     x-transition:leave="transition ease-in duration-150"
                                                     x-transition:leave-start="opacity-100 translate-y-0"
                                                     x-transition:leave-end="opacity-0 translate-y-1"
                                                     class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-56 p-2.5 bg-gray-950/95 dark:bg-gray-900/95 backdrop-blur-sm text-white text-xs font-normal rounded-xl shadow-xl z-50 border border-gray-800 pointer-events-none"
                                                     style="display: none;">
                                                    <div class="font-semibold text-blue-400 mb-1 capitalize">{{ str_replace(' ', ' ', $permission->name) }}</div>
                                                    <div class="text-gray-300">{{ $this->getPermissionDescription($permission->name) }}</div>
                                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-950/95 dark:border-t-gray-900/95"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
            <hr>



            <div class="py-4 border-b border-gray-100 grid grid-cols-1">
                <div class="text-gray-800  dark:text-gray-400 flex-none text-2xl py-auto font-semibold mb-3">
                    <span class="align-middle">User Roles</span>
                </div>
                <div class="text-right flex-auto">
                    <form wire:submit.prevent="Submit" method="post">
                        <input type="text" class="form-input mb-3" wire:model="newRole" required>
                        <button type="submit" class="btn btn-green">
                            <i class="bi bi-plus-circle"></i> Add new role</button>
                    </form>
                </div>
            </div>


            <div class="overflow-x-auto mt-2">

                @include('components.error-message')
                <div class="my-3">
                    {{ $roles->links() }}
                </div>

                <table class="table-auto w-full mt-2">
                    <thead class="text-xs font-semibold uppercase text-gray-400 bg-gray-50 dark:bg-gray-500">
                        <tr>
                            <th class="p-2 whitespace-nowrap">
                                <div class="font-semibold text-left dark:text-gray-200">Role</div>
                            </th>
                            <th class="p-2 whitespace-nowrap">
                                <div class="font-semibold text-left dark:text-gray-200">Permissions belongs to the role
                                </div>
                            </th>
                            <th class="p-2 whitespace-nowrap">
                            </th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        @foreach ($roles as $role)
                            <tr>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-md dark:text-gray-300">
                                        {{ $role->name }}
                                    </div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-left flex flex-wrap gap-y-1">
                                        @forelse ($role->getAllPermissions() as $p)
                                            <div
                                                class="{{ $this->getPermissionBadgeStyle($p->name) }} text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full cursor-default uppercase tracking-wider">
                                                {{ $p->name }}</div>
                                        @empty
                                            <div class="italic text-gray-500 text-sm">(no permissions yet)</div>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-md text-right">
                                        <button type="button" class="btn-sm btn-red"
                                            data-modal-toggle="confirmBox{{ $loop->index ?? ($loop ?? 'one') }}">
                                            <i class="bi bi-trash"></i> Delete</button>
                                    </div>
                                    <!-- Modal -->
                                    <div class="hidden overflow-y-auto overflow-x-hidden fixed right-0 left-0 top-4 z-50 justify-center items-center md:inset-0 h-modal sm:h-full"
                                        id="confirmBox{{ $loop->index ?? ($loop ?? 'one') }}">
                                        <div class="relative px-4 w-full max-w-md h-full md:h-auto">
                                            <!-- Modal content -->
                                            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                                                <!-- Modal header -->
                                                <div class="flex justify-end p-2">
                                                    <button type="button"
                                                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white"
                                                        data-modal-toggle="confirmBox{{ $loop->index ?? ($loop ?? 'one') }}">
                                                        <svg class="w-5 h-5" fill="currentColor"
                                                            viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd"
                                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                                clip-rule="evenodd"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <!-- Modal body -->
                                                <div class="p-6 pt-0 text-center">
                                                    <svg class="mx-auto mb-4 w-16 h-16 text-yellow-400 dark:text-gray-200"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                        </path>
                                                    </svg>
                                                    <p
                                                        class="my-5 text-md font-normal text-gray-500 dark:text-gray-400">
                                                        Are you sure you want to do this?
                                                    </p>
                                                    <button
                                                        data-modal-toggle="confirmBox{{ $loop->index ?? ($loop ?? 'one') }}"
                                                        type="button"
                                                        wire:click.prevent="DeleteRole({{ $role->id }})"
                                                        class="btn-sm btn-red mr-2">
                                                        Yes, I'm sure
                                                    </button>
                                                    <button
                                                        data-modal-toggle="confirmBox{{ $loop->index ?? ($loop ?? 'one') }}"
                                                        type="button" class="btn-sm btn-gray">No, cancel</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <br>

            </div>
        </div>
    </div>
</div>
