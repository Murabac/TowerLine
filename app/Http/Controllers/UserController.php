<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Operator;
use App\Models\Region;
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

        $query = User::query()->with(['regions', 'operator'])->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        return view('users.index', [
            'users' => $query->paginate(20)->withQueryString(),
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
        $regionIds = $data['role'] === 'inspector' ? ($data['region_ids'] ?? []) : [];
        unset($data['region_ids']);
        $data['region_id'] = $regionIds[0] ?? null;
        $data['operator_id'] = null;

        $user = User::query()->create([
            ...$data,
            'email_verified_at' => now(),
        ]);
        $user->syncInspectorRegions($regionIds);
        Audits::log('created', $user, $user->only(['name', 'email', 'role']));

        return redirect()->route('users.index')->with('status', __('app.users.created'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', array_merge($this->formData(), ['managedUser' => $user->load('regions')]));
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $regionIds = $data['role'] === 'inspector' ? ($data['region_ids'] ?? []) : [];
        unset($data['region_ids']);
        $data['region_id'] = $regionIds[0] ?? null;
        $data['operator_id'] = null;

        $user->update($data);
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

    private function formData(): array
    {
        return [
            'regions' => Region::query()->orderBy('name_en')->get(),
            'operators' => Operator::query()->orderBy('name')->get(),
        ];
    }
}
