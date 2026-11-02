<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\FrequencyAllocation;
use App\Models\Inspection;
use App\Models\License;
use App\Models\Tower;
use App\Support\PrivateStorage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFileController extends Controller
{
    public function towerSiteMap(Tower $tower): StreamedResponse
    {
        $this->authorize('view', $tower);
        abort_unless($tower->site_map_path, 404);

        return PrivateStorage::response($tower->site_map_path, basename($tower->site_map_path));
    }

    public function inspectionPhoto(Inspection $inspection, int $index): StreamedResponse
    {
        $this->authorize('view', $inspection);

        $path = $this->flatPaths($inspection->photos ?? [])[$index] ?? null;
        abort_unless(is_string($path) && $path !== '', 404);

        return PrivateStorage::response($path, basename($path));
    }

    public function licenseDocument(License $license, int $index): StreamedResponse
    {
        $this->authorize('view', $license);

        $document = $license->documentRecords()[$index] ?? null;
        abort_unless(is_array($document) && ! empty($document['path']), 404);

        return PrivateStorage::download($document['path'], $document['name'] ?? basename($document['path']));
    }

    public function frequencyDocument(FrequencyAllocation $frequency, int $index): StreamedResponse
    {
        $this->authorize('view', $frequency);

        $document = $frequency->documentRecords()[$index] ?? null;
        abort_unless(is_array($document) && ! empty($document['path']), 404);

        return PrivateStorage::download($document['path'], $document['name'] ?? basename($document['path']));
    }

    public function approvalSiteMap(ApprovalRequest $approval): StreamedResponse
    {
        $this->authorize('view', $approval);

        $path = $approval->siteMapPath();
        abort_unless($path, 404);

        return PrivateStorage::response($path, basename($path));
    }

    public function approvalPhoto(ApprovalRequest $approval, int $index): StreamedResponse
    {
        $this->authorize('view', $approval);

        $path = $this->flatPaths($approval->photos())[$index] ?? null;
        abort_unless(is_string($path) && $path !== '', 404);

        return PrivateStorage::response($path, basename($path));
    }

    /**
     * @param  array<mixed>  $photos
     * @return list<string>
     */
    private function flatPaths(array $photos): array
    {
        return collect($photos)
            ->flatMap(fn ($path) => is_array($path) ? $path : [$path])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values()
            ->all();
    }
}
