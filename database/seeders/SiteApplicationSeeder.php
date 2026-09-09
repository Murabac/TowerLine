<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\License;
use App\Models\Operator;
use App\Models\Region;
use App\Models\SiteApplication;
use App\Models\SubDistrict;
use App\Models\User;
use App\Support\SimplePdf;
use App\Support\SiteApplicationNumberGenerator;
use App\Support\SiteApplicationPermit;
use App\Support\UserSignature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SiteApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $region = Region::query()->where('name_en', 'Maroodi Jeex')->first();
        $operator = Operator::query()->where('name', 'Telesom')->first();
        $district = $region
            ? District::query()->where('region_id', $region->id)->orderBy('name')->first()
            : null;
        $subDistrict = $district
            ? SubDistrict::query()->where('district_id', $district->id)->orderBy('name')->first()
            : null;

        if (! $region || ! $operator || ! $district || ! $subDistrict) {
            return;
        }

        $paths = $this->documentPaths();
        $head = User::query()->where('email', 'section.head@mocit.local')->first();
        $coordinator = User::query()->where('email', 'coordinator.maroodi@mocit.local')->first();
        $director = User::query()->where('email', 'director@mocit.local')->first();
        $dg = User::query()->where('email', 'dg@mocit.local')->first();

        $this->ensure('Demo Maroodi Jeex Site', function () use ($region, $district, $subDistrict, $operator, $paths) {
            return SiteApplication::query()->create([
                ...$this->baseAttributes($region, $district, $subDistrict, $operator, $paths, 9.562, 44.077),
                'site_name' => 'Demo Maroodi Jeex Site',
                'contact_name' => 'Demo Applicant',
                'email' => 'demo.applicant@example.com',
                'status' => SiteApplication::STATUS_RECEIVED,
            ]);
        });

        if ($head && $coordinator) {
            $this->ensure('Demo assigned Maroodi site', function () use ($region, $district, $subDistrict, $operator, $paths, $head, $coordinator) {
                $assigned = SiteApplication::query()->create([
                    ...$this->baseAttributes($region, $district, $subDistrict, $operator, $paths, 9.563, 44.078),
                    'site_name' => 'Demo assigned Maroodi site',
                    'contact_name' => 'Hodan Ali',
                    'email' => 'hodan.demo@example.com',
                    'telephone' => '+252 63 4000002',
                    'status' => SiteApplication::STATUS_RECEIVED,
                ]);
                $assigned->assignTo($coordinator, $head);

                return $assigned;
            });

            $this->ensure('Demo returned Maroodi site', function () use ($region, $district, $subDistrict, $operator, $paths, $head, $coordinator) {
                $returned = SiteApplication::query()->create([
                    ...$this->baseAttributes($region, $district, $subDistrict, $operator, $paths, 9.564, 44.079),
                    'site_name' => 'Demo returned Maroodi site',
                    'contact_name' => 'Yusuf Omar',
                    'email' => 'yusuf.demo@example.com',
                    'telephone' => '+252 63 4000004',
                    'status' => SiteApplication::STATUS_RECEIVED,
                ]);
                $returned->assignTo($coordinator, $head);
                $returned->recordOfficerReview(
                    $coordinator,
                    SiteApplication::DECISION_REJECT,
                    now()->subDay()->toDateString(),
                    'Pin does not match the described plot.',
                    'Return for a corrected GPS map.',
                    $this->sign($coordinator, $returned, 'officer'),
                );

                return $returned;
            });

            $this->ensure('Demo director review Maroodi site', function () use ($region, $district, $subDistrict, $operator, $paths, $head, $coordinator) {
                $directorFile = SiteApplication::query()->create([
                    ...$this->baseAttributes($region, $district, $subDistrict, $operator, $paths, 9.565, 44.080),
                    'site_name' => 'Demo director review Maroodi site',
                    'contact_name' => 'Khadra Mohamed',
                    'email' => 'khadra.demo@example.com',
                    'telephone' => '+252 63 4000003',
                    'status' => SiteApplication::STATUS_RECEIVED,
                ]);
                $directorFile->assignTo($coordinator, $head);
                $directorFile->recordOfficerReview(
                    $coordinator,
                    SiteApplication::DECISION_APPROVE,
                    now()->toDateString(),
                    'Demo visit: GPS and fence match the plan.',
                    'Recommend concurrence.',
                    $this->sign($coordinator, $directorFile, 'officer'),
                );

                return $directorFile;
            });
        }

        if ($head && $coordinator && $director) {
            $this->ensure('Demo DG review Maroodi site', function () use ($region, $district, $subDistrict, $operator, $paths, $head, $coordinator, $director) {
                $file = SiteApplication::query()->create([
                    ...$this->baseAttributes($region, $district, $subDistrict, $operator, $paths, 9.566, 44.081),
                    'site_name' => 'Demo DG review Maroodi site',
                    'contact_name' => 'Sahra Abdi',
                    'email' => 'sahra.demo@example.com',
                    'telephone' => '+252 63 4000005',
                    'status' => SiteApplication::STATUS_RECEIVED,
                ]);
                $file->assignTo($coordinator, $head);
                $file->recordOfficerReview(
                    $coordinator,
                    SiteApplication::DECISION_APPROVE,
                    now()->subDays(2)->toDateString(),
                    'Site visit complete. Spacing is acceptable.',
                    'Recommend Yes.',
                    $this->sign($coordinator, $file, 'officer'),
                );
                $file->recordDirectorDecision(
                    $director,
                    SiteApplication::DECISION_YES,
                    'Site meets ministry spacing.',
                    $director->name,
                    $this->sign($director, $file, 'director'),
                );

                return $file;
            });

            $this->ensure('Demo refused Maroodi site', function () use ($region, $district, $subDistrict, $operator, $paths, $head, $coordinator, $director) {
                $file = SiteApplication::query()->create([
                    ...$this->baseAttributes($region, $district, $subDistrict, $operator, $paths, 9.567, 44.082),
                    'site_name' => 'Demo refused Maroodi site',
                    'contact_name' => 'Liban Hassan',
                    'email' => 'liban.demo@example.com',
                    'telephone' => '+252 63 4000006',
                    'status' => SiteApplication::STATUS_RECEIVED,
                ]);
                $file->assignTo($coordinator, $head);
                $file->recordOfficerReview(
                    $coordinator,
                    SiteApplication::DECISION_APPROVE,
                    now()->subDays(3)->toDateString(),
                    'Visited. Close to a school compound.',
                    'Flag for Director review.',
                    $this->sign($coordinator, $file, 'officer'),
                );
                $file->recordDirectorDecision(
                    $director,
                    SiteApplication::DECISION_NO,
                    'Too close to the school. File closed.',
                    $director->name,
                    $this->sign($director, $file, 'director'),
                );

                return $file;
            });
        }

        if ($head && $coordinator && $director && $dg) {
            $this->ensure('Demo granted Maroodi site', function () use ($region, $district, $subDistrict, $operator, $paths, $head, $coordinator, $director, $dg) {
                $file = SiteApplication::query()->create([
                    ...$this->baseAttributes($region, $district, $subDistrict, $operator, $paths, 9.568, 44.083),
                    'site_name' => 'Demo granted Maroodi site',
                    'contact_name' => 'Amina Hassan',
                    'email' => 'amina.demo@example.com',
                    'telephone' => '+252 63 4000007',
                    'city' => 'Hargeisa',
                    'status' => SiteApplication::STATUS_RECEIVED,
                ]);
                $file->assignTo($coordinator, $head);
                $file->recordOfficerReview(
                    $coordinator,
                    SiteApplication::DECISION_APPROVE,
                    now()->subDays(5)->toDateString(),
                    'Fence and GPS match the plan.',
                    'Recommend concurrence.',
                    $this->sign($coordinator, $file, 'officer'),
                );
                $file->recordDirectorDecision(
                    $director,
                    SiteApplication::DECISION_YES,
                    'Concur. Send to the Director General.',
                    $director->name,
                    $this->sign($director, $file, 'director'),
                );
                $file->recordDgDecision(
                    $dg,
                    SiteApplication::DECISION_GRANT,
                    'Permit granted.',
                    $dg->name,
                    $this->sign($dg, $file, 'dg'),
                );
                $tower = SiteApplicationPermit::issue($file, $dg);
                License::query()->firstOrCreate(
                    ['tower_id' => $tower->id],
                    [
                        'operator_id' => $tower->operator_id,
                        'license_type' => 'A',
                        'issued_at' => now()->subYear(),
                        'expires_at' => now()->addMonths(8),
                    ],
                );

                return $file;
            });
        }

        $this->backfillMissingSignatures($coordinator, $director, $dg);
    }

    private function ensure(string $siteName, callable $factory): void
    {
        if (SiteApplication::query()->where('site_name', $siteName)->exists()) {
            return;
        }

        $factory();
    }

    private function backfillMissingSignatures(?User $coordinator, ?User $director, ?User $dg): void
    {
        SiteApplication::query()
            ->whereNotNull('officer_reviewed_at')
            ->whereNull('officer_signature_path')
            ->get()
            ->each(function (SiteApplication $application) use ($coordinator): void {
                $officer = $application->officerReviewer ?: $coordinator;
                if ($officer) {
                    $application->forceFill([
                        'officer_signature_path' => $this->sign($officer, $application, 'officer'),
                    ])->save();
                }
            });

        SiteApplication::query()
            ->whereNotNull('director_reviewed_at')
            ->whereNull('director_signature_path')
            ->get()
            ->each(function (SiteApplication $application) use ($director): void {
                $user = $application->directorReviewer ?: $director;
                if ($user) {
                    $application->forceFill([
                        'director_signature_path' => $this->sign($user, $application, 'director'),
                    ])->save();
                }
            });

        SiteApplication::query()
            ->whereNotNull('dg_reviewed_at')
            ->whereNull('dg_signature_path')
            ->get()
            ->each(function (SiteApplication $application) use ($dg): void {
                $user = $application->dgReviewer ?: $dg;
                if ($user) {
                    $application->forceFill([
                        'dg_signature_path' => $this->sign($user, $application, 'dg'),
                    ])->save();
                }
            });
    }

    private function sign(User $user, SiteApplication $application, string $party): string
    {
        $png = $this->demoPng();

        if (! $user->hasSavedSignature()) {
            UserSignature::storeForUser($user, $png);
        }

        return UserSignature::snapshot('applications/'.$application->id, $party, $png);
    }

    private function demoPng(): string
    {
        $png = UserSignature::pngFromDataUrl('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==');

        return (string) $png;
    }

    /**
     * @param  array<string, string>  $paths
     * @return array<string, mixed>
     */
    private function baseAttributes(
        Region $region,
        District $district,
        SubDistrict $subDistrict,
        Operator $operator,
        array $paths,
        float $latitude,
        float $longitude,
    ): array {
        return [
            'reference_number' => SiteApplicationNumberGenerator::generate(),
            'telephone' => '+252 63 4000001',
            'operator_id' => $operator->id,
            'license_class_no' => 'A-DEMO/2026',
            'address' => 'Hargeisa',
            'city' => 'Hargeisa',
            'region_id' => $region->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            ...$paths,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function documentPaths(): array
    {
        $folder = 'site-applications/demo-maroodi';
        $pdf = new SimplePdf;
        $pdf->title('Demo site application');
        $pdf->paragraph('Placeholder document for the TowerLine application inbox.');
        $bytes = $pdf->output();

        $paths = [];
        foreach (array_keys(SiteApplication::DOCUMENT_FIELDS) as $field) {
            $path = $folder.'/'.$field.'.pdf';
            if (! Storage::disk('local')->exists($path)) {
                Storage::disk('local')->put($path, $bytes);
            }
            $paths[SiteApplication::DOCUMENT_FIELDS[$field]] = $path;
        }

        return $paths;
    }
}
