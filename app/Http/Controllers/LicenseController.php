<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLicenseRequest;
use App\Models\License;
use App\Models\Operator;
use App\Models\Tower;
use App\Support\Audits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', License::class);

        $query = License::query()
            ->visibleTo($request->user())
            ->with(['tower.region', 'operator'])
            ->orderBy('expires_at');

        if ($request->filled('license_type')) {
            $query->where('license_type', $request->string('license_type'));
        }

        if ($request->filled('state')) {
            match ($request->string('state')->toString()) {
                'expired' => $query->expired(),
                'expiring_soon' => $query->expiringSoon(),
                'active' => $query->whereDate('expires_at', '>', now()->addDays(30)->toDateString()),
                default => null,
            };
        }

        return view('licenses.index', [
            'licenses' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', License::class);

        $towers = Tower::query()->visibleTo($request->user())->with('operator')->orderBy('name')->get();

        return view('licenses.create', [
            'towers' => $towers,
            'operators' => Operator::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreLicenseRequest $request): RedirectResponse
    {
        $tower = Tower::query()->visibleTo($request->user())->findOrFail($request->integer('tower_id'));
        $this->authorize('update', $tower);

        $license = License::query()->create([
            ...$request->safe()->except(['documents', 'remove_documents']),
            'operator_id' => $tower->operator_id,
            'documents' => [],
        ]);

        $files = $request->file('documents');
        $license->syncDocuments(is_array($files) ? $files : array_filter([$files]));

        Audits::log('created', $license, $license->only(['tower_id', 'license_type', 'expires_at']));

        return redirect()->route('licenses.index')->with('status', __('app.licenses.created'));
    }

    public function edit(License $license): View
    {
        $this->authorize('update', $license);

        $license->load(['tower.region', 'tower.operator', 'operator']);

        return view('licenses.edit', compact('license'));
    }

    public function update(StoreLicenseRequest $request, License $license): RedirectResponse
    {
        $this->authorize('update', $license);

        $license->update($request->safe()->only(['license_type', 'issued_at', 'expires_at']));

        $files = $request->file('documents');
        $license->syncDocuments(
            is_array($files) ? $files : array_filter([$files]),
            $request->input('remove_documents', []),
        );

        Audits::log('updated', $license, $license->only(['license_type', 'expires_at']));

        return redirect()->route('licenses.index')->with('status', __('app.licenses.updated'));
    }

    public function destroy(License $license): RedirectResponse
    {
        $this->authorize('delete', $license);
        Audits::log('deleted', $license, ['tower_id' => $license->tower_id]);
        $license->delete();

        return redirect()->route('licenses.index')->with('status', __('app.licenses.deleted'));
    }
}
