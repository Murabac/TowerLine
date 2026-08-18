<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTowerRequest;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Support\Audits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TowerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Tower::class);

        $query = Tower::query()
            ->visibleTo($request->user())
            ->with(['region', 'operator', 'latestInspection'])
            ->orderBy('name');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->integer('region_id'));
        }

        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->integer('operator_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('towers.index', [
            'towers' => $query->paginate(15)->withQueryString(),
            'regions' => Region::query()->orderBy('name_en')->get(),
            'operators' => Operator::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Tower::class);

        return view('towers.create', $this->formData());
    }

    public function store(StoreTowerRequest $request): RedirectResponse
    {
        $tower = Tower::query()->create($request->validated());
        Audits::log('created', $tower, $tower->only(['name', 'region_id', 'operator_id', 'status']));

        return redirect()->route('towers.show', $tower)->with('status', __('app.towers.created'));
    }

    public function show(Tower $tower): View
    {
        $this->authorize('view', $tower);

        $tower->load([
            'region',
            'operator',
            'inspections.inspector',
            'licenses.operator',
        ]);

        return view('towers.show', compact('tower'));
    }

    public function edit(Tower $tower): View
    {
        $this->authorize('update', $tower);

        return view('towers.edit', array_merge($this->formData(), compact('tower')));
    }

    public function update(StoreTowerRequest $request, Tower $tower): RedirectResponse
    {
        $this->authorize('update', $tower);

        $before = $tower->only(['name', 'status', 'latitude', 'longitude']);
        $tower->update($request->validated());
        Audits::log('updated', $tower, ['before' => $before, 'after' => $tower->only(array_keys($before))]);

        return redirect()->route('towers.show', $tower)->with('status', __('app.towers.updated'));
    }

    public function destroy(Tower $tower): RedirectResponse
    {
        $this->authorize('delete', $tower);

        Audits::log('deleted', $tower, ['name' => $tower->name]);
        $tower->delete();

        return redirect()->route('towers.index')->with('status', __('app.towers.deleted'));
    }

    private function formData(): array
    {
        $user = request()->user();

        $regions = Region::query()->orderBy('name_en')->get();
        if ($user->isInspector()) {
            $regions = $regions->where('id', $user->region_id)->values();
        }

        return [
            'regions' => $regions,
            'operators' => Operator::query()->orderBy('name')->get(),
        ];
    }
}
