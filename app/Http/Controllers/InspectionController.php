<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInspectionRequest;
use App\Models\Inspection;
use App\Models\Tower;
use App\Support\Audits;
use App\Support\InspectorApprovals;
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
        foreach (Inspection::photoSlots() as $slot) {
            $files = $request->file('photos.'.$slot);
            if (! $files) {
                continue;
            }

            $files = is_array($files) ? $files : [$files];
            $stored = [];
            foreach ($files as $file) {
                if ($file) {
                    $stored[] = $file;
                }
            }

            if ($stored !== []) {
                $paths[$slot] = $stored;
            }
        }

        if (! $request->user()->publishesDirectly()) {
            InspectorApprovals::submitInspection(
                $request->user(),
                $tower,
                $request->safe()->except('photos'),
                $paths,
            );

            return redirect()->route('towers.show', $tower)->with('status', __('app.approvals.submitted_inspection'));
        }

        $storedPaths = [];
        foreach ($paths as $slot => $files) {
            $stored = [];
            foreach ($files as $file) {
                $stored[] = $file->store('inspections/'.$tower->id, 'public');
            }
            $storedPaths[$slot] = $stored;
        }

        $inspection = $tower->inspections()->create([
            ...$request->safe()->except('photos'),
            'inspector_id' => $request->user()->id,
            'photos' => $storedPaths,
            'inspected_at' => now(),
        ]);

        Audits::log('created', $inspection, [
            'tower_id' => $tower->id,
            'health' => $inspection->derivedHealth(),
        ]);

        return redirect()->route('towers.show', $tower)->with('status', __('app.inspections.created'));
    }
}
