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
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('users.index', [
            'users' => User::query()->with(['region', 'operator'])->orderBy('name')->paginate(20),
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
        $data['region_id'] = $data['role'] === 'inspector' ? ($data['region_id'] ?? null) : null;
        $data['operator_id'] = $data['role'] === 'operator_viewer' ? ($data['operator_id'] ?? null) : null;

        $user = User::query()->create($data);
        Audits::log('created', $user, $user->only(['name', 'email', 'role']));

        return redirect()->route('users.index')->with('status', __('app.users.created'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', array_merge($this->formData(), ['managedUser' => $user]));
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $data['region_id'] = $data['role'] === 'inspector' ? ($data['region_id'] ?? null) : null;
        $data['operator_id'] = $data['role'] === 'operator_viewer' ? ($data['operator_id'] ?? null) : null;

        $user->update($data);
        Audits::log('updated', $user, $user->only(['name', 'email', 'role', 'region_id', 'operator_id']));

        return redirect()->route('users.index')->with('status', __('app.users.updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
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
