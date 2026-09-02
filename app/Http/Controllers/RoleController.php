<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Models\Role;
use App\Support\Audits;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return view('roles.index', [
            'roles' => $roles,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('roles.create', $this->formData());
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $key = $this->uniqueKey((string) $data['name']);

        $role = Role::query()->create([
            'key' => $key,
            'name' => $data['name'],
            'is_system' => false,
            'requires_regions' => (bool) ($data['requires_regions'] ?? false),
        ]);

        Permissions::syncRoleTasks($role->key, $data['tasks'] ?? []);
        Audits::log('created', $role, $role->only(['key', 'name', 'requires_regions']));

        return redirect()->route('roles.index')->with('status', __('app.role_admin.created'));
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('roles.edit', array_merge($this->formData(), [
            'managedRole' => $role,
            'selectedTasks' => $role->taskKeys(),
        ]));
    }

    public function update(StoreRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $data = $request->validated();
        $tasks = $data['tasks'] ?? [];

        if ($role->key === Role::KEY_ADMIN) {
            $tasks = array_values(array_unique([...$tasks, 'users.manage', 'roles.manage']));
        }

        if (! $role->isSystem()) {
            $role->update([
                'name' => $data['name'],
                'requires_regions' => (bool) ($data['requires_regions'] ?? false),
            ]);
        }

        Permissions::syncRoleTasks($role->key, $tasks);
        Audits::log('updated', $role, ['name' => $role->name, 'tasks' => count($tasks)]);

        return redirect()->route('roles.index')->with('status', __('app.role_admin.updated'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        Permissions::forgetRole($role->key);
        Audits::log('deleted', $role, ['key' => $role->key, 'name' => $role->name]);
        $role->delete();

        return redirect()->route('roles.index')->with('status', __('app.role_admin.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'groups' => Permissions::TASK_GROUPS,
            'taskLabels' => Permissions::TASKS,
        ];
    }

    private function uniqueKey(string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $key = $base;
        $i = 2;

        while (Role::query()->where('key', $key)->exists()) {
            $key = $base.'-'.$i;
            $i++;
        }

        return $key;
    }
}
