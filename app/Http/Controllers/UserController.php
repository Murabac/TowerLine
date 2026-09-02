<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Support\Audits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->with(['regions', 'operator', 'roleRecord'])->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        return view('users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'roles' => Role::query()->assignable()->orderByDesc('is_system')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', $this->formData());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = Role::query()->where('key', $data['role'])->firstOrFail();
        $regionIds = $role->requires_regions ? ($data['region_ids'] ?? []) : [];
        unset($data['region_ids']);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $role->key,
            'role_id' => $role->id,
            'region_id' => $regionIds[0] ?? null,
            'operator_id' => null,
            'email_verified_at' => now(),
        ]);
        $user->setRelation('roleRecord', $role);
        $user->syncInspectorRegions($regionIds);
        Audits::log('created', $user, $user->only(['name', 'email', 'role']));

        return redirect()->route('users.index')->with('status', __('app.users.created'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', array_merge($this->formData(), ['managedUser' => $user->load(['regions', 'roleRecord'])]));
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        $role = Role::query()->where('key', $data['role'])->firstOrFail();
        $regionIds = $role->requires_regions ? ($data['region_ids'] ?? []) : [];
        unset($data['region_ids']);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $role->key,
            'role_id' => $role->id,
            'region_id' => $regionIds[0] ?? null,
            'operator_id' => null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user->setRelation('roleRecord', $role);
        $user->syncInspectorRegions($regionIds);
        Audits::log('updated', $user, $user->only(['name', 'email', 'role']));

        return redirect()->route('users.index')->with('status', __('app.users.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        Audits::log('deleted', $user, ['email' => $user->email]);
        $user->delete();

        return redirect()->route('users.index')->with('status', __('app.users.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'regions' => Region::query()->orderBy('name_en')->get(),
            'operators' => Operator::query()->orderBy('name')->get(),
            'roles' => Role::query()->assignable()->orderByDesc('is_system')->orderBy('name')->get(),
        ];
    }
}
