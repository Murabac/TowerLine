<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInspectionRequest;
use App\Models\Inspection;
use App\Models\Tower;
use App\Support\Audits;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InspectionController extends Controller
{
    public function create(Tower $tower): View
    {
        $this->authorize('create', [Inspection::class, $tower]);

        return view('inspections.create', compact('tower'));
    }

    public function store(StoreInspectionRequest $request, Tower $tower): RedirectResponse
    {
        $this->authorize('create', [Inspection::class, $tower]);

        $paths = [];
        foreach ($request->file('photos', []) as $photo) {
            $paths[] = $photo->store('inspections/'.$tower->id, 'public');
        }

        $inspection = $tower->inspections()->create([
            ...$request->safe()->except('photos'),
            'inspector_id' => $request->user()->id,
            'photos' => $paths,
            'inspected_at' => now(),
        ]);

        Audits::log('created', $inspection, [
            'tower_id' => $tower->id,
            'health' => $inspection->derivedHealth(),
        ]);

        return redirect()->route('towers.show', $tower)->with('status', __('app.inspections.created'));
    }
}
