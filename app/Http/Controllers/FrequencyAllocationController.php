<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFrequencyAllocationRequest;
use App\Models\FrequencyAllocation;
use App\Models\FrequencyRenewalReceipt;
use App\Models\Operator;
use App\Models\Region;
use App\Support\Audits;
use App\Support\FrequencyRenewalDocuments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FrequencyAllocationController extends Controller
{
    public function dashboard(Request $request): View
    {
        $this->authorize('viewAny', FrequencyAllocation::class);

        $base = FrequencyAllocation::query()->visibleTo($request->user());

        $expiringSoon = (clone $base)
            ->with(['operator', 'region'])
            ->expiringSoon()
            ->orderBy('expires_at')
            ->limit(8)
            ->get();

        $recentRenewals = (clone $base)
            ->with(['operator', 'region', 'renewedFrom', 'currentLetter', 'currentReceipt'])
            ->whereNotNull('renewed_from_id')
            ->latest()
            ->limit(8)
            ->get();

        $operatorCounts = (clone $base)
            ->selectRaw('operator_id, count(*) as total')
            ->groupBy('operator_id')
            ->orderByDesc('total')
            ->pluck('total', 'operator_id');

        $byOperator = Operator::query()
            ->whereIn('id', $operatorCounts->keys())
            ->orderBy('name')
            ->get()
            ->map(fn (Operator $operator) => [
                'operator' => $operator,
                'total' => (int) $operatorCounts[$operator->id],
            ]);

        $receipts = FrequencyRenewalReceipt::query()->visibleTo($request->user());
        $renewalFee = FrequencyRenewalReceipt::renewalFeeAmount();
        $totalReceipts = (clone $receipts)->count();
        $yearReceipts = (clone $receipts)->whereYear('issued_at', now()->year)->count();

        $monthlyRevenue = collect(range(5, 0))
            ->map(function (int $monthsAgo) use ($receipts, $renewalFee) {
                $month = now()->subMonths($monthsAgo)->startOfMonth();

                $count = (clone $receipts)
                    ->whereYear('issued_at', $month->year)
                    ->whereMonth('issued_at', $month->month)
                    ->count();

                return [
                    'label' => $month->format('M'),
                    'fullLabel' => $month->format('M Y'),
                    'month' => $month->format('Y-m'),
                    'count' => $count,
                    'revenue' => $count * $renewalFee,
                    'url' => route('frequencies.registry', [
                        'receipt_month' => $month->format('Y-m'),
                        'payment' => 'with_receipt',
                    ]),
                ];
            })
            ->values();

        $totalRevenue = $totalReceipts * $renewalFee;
        $yearRevenue = $yearReceipts * $renewalFee;
        $dueCount = (clone $base)->outstandingRenewal()->count();
        $dueRevenue = $dueCount * $renewalFee;

        return view('frequencies.dashboard', [
            'totalCount' => (clone $base)->count(),
            'activeCount' => (clone $base)->whereDate('expires_at', '>', now()->addDays(30)->toDateString())->count(),
            'expiringCount' => (clone $base)->expiringSoon()->count(),
            'expiredCount' => (clone $base)->expired()->count(),
            'expiringSoon' => $expiringSoon,
            'recentRenewals' => $recentRenewals,
            'byOperator' => $byOperator,
            'renewalFee' => $renewalFee,
            'totalReceipts' => $totalReceipts,
            'totalRevenue' => $totalRevenue,
            'yearReceipts' => $yearReceipts,
            'yearRevenue' => $yearRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'dueCount' => $dueCount,
            'dueRevenue' => $dueRevenue,
        ]);
    }

    public function registry(Request $request): View
    {
        $this->authorize('viewAny', FrequencyAllocation::class);

        $query = FrequencyAllocation::query()
            ->visibleTo($request->user())
            ->with(['operator', 'region', 'currentLetter', 'currentReceipt'])
            ->orderBy('expires_at');

        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->integer('operator_id'));
        }

        if ($request->filled('state')) {
            match ($request->string('state')->toString()) {
                'expired' => $query->expired(),
                'outstanding' => $query->outstandingRenewal(),
                'expiring_soon' => $query->expiringSoon(),
                'expiring_60' => $query->expiringWithin(60),
                'active' => $query->whereDate('expires_at', '>', now()->addDays(30)->toDateString()),
                default => null,
            };
        }

        if ($request->filled('payment')) {
            match ($request->string('payment')->toString()) {
                'with_receipt' => $query->whereHas('receipts'),
                'this_year' => $query->whereHas('receipts', fn ($receipts) => $receipts->whereYear('issued_at', now()->year)),
                default => null,
            };
        }

        $activeReceiptMonth = null;

        if ($request->filled('receipt_month') && preg_match('/^\d{4}-\d{2}$/', $request->string('receipt_month')->toString())) {
            $activeReceiptMonth = Carbon::createFromFormat('Y-m', $request->string('receipt_month')->toString())->startOfMonth();

            $query->whereHas('receipts', fn ($receipts) => $receipts
                ->whereYear('issued_at', $activeReceiptMonth->year)
                ->whereMonth('issued_at', $activeReceiptMonth->month));
        }

        return view('frequencies.registry', [
            'allocations' => $query->paginate(20)->withQueryString(),
            'operators' => Operator::query()->orderBy('name')->get(),
            'activeReceiptMonth' => $activeReceiptMonth,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', FrequencyAllocation::class);

        return view('frequencies.create', $this->formData());
    }

    public function store(StoreFrequencyAllocationRequest $request): RedirectResponse
    {
        $frequency = FrequencyAllocation::query()->create([
            ...$request->safe()->except(['documents', 'remove_documents']),
            'documents' => [],
        ]);

        $files = $request->file('documents');
        $frequency->syncDocuments(is_array($files) ? $files : array_filter([$files]));

        Audits::log('created', $frequency, $frequency->only([
            'operator_id',
            'band_label',
            'frequency_range',
            'expires_at',
        ]));

        return redirect()
            ->route('frequencies.show', $frequency)
            ->with('status', __('app.frequencies.created'));
    }

    public function show(FrequencyAllocation $frequency): View
    {
        $this->authorize('view', $frequency);

        $frequency->load(['operator', 'region', 'renewals.operator']);
        $frequency->loadRenewalChain();

        return view('frequencies.show', [
            'allocation' => $frequency,
            'documentTimeline' => $frequency->officialDocumentsTimeline(),
        ]);
    }

    public function edit(FrequencyAllocation $frequency): View
    {
        $this->authorize('update', $frequency);

        return view('frequencies.edit', array_merge($this->formData(), [
            'allocation' => $frequency->load(['operator', 'region']),
        ]));
    }

    public function update(StoreFrequencyAllocationRequest $request, FrequencyAllocation $frequency): RedirectResponse
    {
        $this->authorize('update', $frequency);

        $frequency->update($request->safe()->except(['documents', 'remove_documents']));

        $files = $request->file('documents');
        $frequency->syncDocuments(
            is_array($files) ? $files : array_filter([$files]),
            $request->input('remove_documents', []),
        );

        Audits::log('updated', $frequency, $frequency->only([
            'band_label',
            'frequency_range',
            'expires_at',
        ]));

        return redirect()
            ->route('frequencies.show', $frequency)
            ->with('status', __('app.frequencies.updated'));
    }

    public function destroy(FrequencyAllocation $frequency): RedirectResponse
    {
        $this->authorize('delete', $frequency);

        Audits::log('deleted', $frequency, [
            'operator_id' => $frequency->operator_id,
            'band_label' => $frequency->band_label,
        ]);

        $frequency->delete();

        return redirect()
            ->route('frequencies.registry')
            ->with('status', __('app.frequencies.deleted'));
    }

    public function renew(Request $request, FrequencyAllocation $frequency): RedirectResponse
    {
        $this->authorize('renew', $frequency);

        $issuedAt = now()->toDateString();
        $renewed = FrequencyAllocation::query()->create([
            'operator_id' => $frequency->operator_id,
            'region_id' => $frequency->region_id,
            'band_label' => $frequency->band_label,
            'frequency_range' => $frequency->frequency_range,
            'channel_details' => $frequency->channel_details,
            'issued_at' => $issuedAt,
            'expires_at' => Carbon::parse($issuedAt)->addYear()->toDateString(),
            'notes' => $frequency->notes,
            'documents' => [],
            'renewed_from_id' => $frequency->id,
        ]);

        $documents = FrequencyRenewalDocuments::issue($renewed, $request->user(), $issuedAt);

        Audits::log('created', $renewed, [
            'renewed_from_id' => $frequency->id,
            'expires_at' => $renewed->expires_at->toDateString(),
            'letter_ref' => $documents['letter']->reference_number,
            'receipt_ref' => $documents['receipt']->reference_number,
        ]);

        return redirect()
            ->route('frequencies.show', $renewed)
            ->with('status', __('app.frequencies.renewed_with_documents', [
                'letter' => $documents['letter']->reference_number,
                'receipt' => $documents['receipt']->reference_number,
            ]));
    }

    /**
     * @return array{operators: \Illuminate\Support\Collection, regions: \Illuminate\Support\Collection}
     */
    private function formData(): array
    {
        return [
            'operators' => Operator::query()->orderBy('name')->get(),
            'regions' => Region::query()->orderBy('name_en')->get(),
        ];
    }
}
