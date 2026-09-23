<?php

namespace App\Support;

class SiteRegistrationGuidelines
{
    /** Qodobka 10.6, 14.8, 16.7 — inhabited buildings / public places (metres). */
    public const MIN_PUBLIC_M = 6;

    /** Qodobka 10.4–10.5 — schools, hospitals, nurseries, playgrounds (metres). */
    public const MIN_SENSITIVE_M = 13;

    /** Annex B — show these amenities on the location map if within this radius (metres). */
    public const MAP_AMENITY_RADIUS_M = 15;

    /** Qodobka 10.6 applied to fence-from-tower on the form (metres). */
    public const MIN_FENCE_M = 6;

    /** Qodobka 10.2 — plot short side (metres). */
    public const MIN_PLOT_SHORT_M = 18;

    /** Qodobka 10.2 — plot long side (metres). */
    public const MIN_PLOT_LONG_M = 24;

    /** Qodobka 10.3 — new towers in inhabited areas; rooftop sites are exempt (metres). */
    public const MIN_HEIGHT_INHABITED_M = 30;

    /** Qodobka 16.4 — same operator’s existing site (metres). */
    public const MIN_SAME_OPERATOR_M = 500;

    /**
     * @return array<string, int>
     */
    public static function distanceMins(): array
    {
        return [
            'nearest_house_m' => self::MIN_PUBLIC_M,
            'nearest_school_m' => self::MIN_SENSITIVE_M,
            'nearest_hospital_m' => self::MIN_SENSITIVE_M,
        ];
    }

    public static function requiresInhabitedHeight(?string $type): bool
    {
        return in_array($type, ['guyed', 'monopole'], true);
    }

    /**
     * @return array{0: float, 1: float}|null  short side, long side
     */
    public static function plotSides(?string $preset, ?string $custom): ?array
    {
        $text = $preset === 'custom'
            ? (string) $custom
            : (TowerLandArea::PRESETS[$preset] ?? (string) $preset);

        if (! preg_match('/(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)/u', $text, $match)) {
            return null;
        }

        $a = (float) $match[1];
        $b = (float) $match[2];

        return [min($a, $b), max($a, $b)];
    }

    public static function plotMeetsGuideline(?string $preset, ?string $custom): bool
    {
        $sides = self::plotSides($preset, $custom);

        if (! $sides) {
            return false;
        }

        return $sides[0] + 0.001 >= self::MIN_PLOT_SHORT_M
            && $sides[1] + 0.001 >= self::MIN_PLOT_LONG_M;
    }

    /**
     * Official MoCIT regulation PDF (Xeer-Nidaamiye Lr.02/2019).
     */
    public static function officialPdfPath(): ?string
    {
        foreach ([
            resource_path('docs/Xeer-Nidaamiyaha Goobaha Isgaadhsiinta.pdf'),
            resource_path('docs/site-registration-guidelines.pdf'),
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function downloadFilename(): string
    {
        $path = self::officialPdfPath();

        return $path ? basename($path) : 'site-registration-guidelines.pdf';
    }

    /**
     * @return list<array{title: string, body: string, bullets?: list<string>}>
     */
    public static function documents(): array
    {
        return [
            [
                'title' => __('app.guidelines.items.letter.title', [], 'en'),
                'body' => __('app.guidelines.items.letter.body', [], 'en'),
            ],
            [
                'title' => __('app.guidelines.items.map.title', [], 'en'),
                'body' => __('app.guidelines.items.map.body', [], 'en'),
            ],
            [
                'title' => __('app.guidelines.items.layout.title', [], 'en'),
                'body' => __('app.guidelines.items.layout.body', [], 'en'),
            ],
            [
                'title' => __('app.guidelines.items.radio.title', [], 'en'),
                'body' => __('app.guidelines.items.radio.body', [], 'en'),
                'bullets' => [
                    __('app.guidelines.items.radio.structure', [], 'en'),
                    __('app.guidelines.items.radio.antenna', [], 'en'),
                    __('app.guidelines.items.radio.height', [], 'en'),
                    __('app.guidelines.items.radio.bands', [], 'en'),
                    __('app.guidelines.items.radio.eirp', [], 'en'),
                    __('app.guidelines.items.radio.emf', [], 'en'),
                ],
            ],
            [
                'title' => __('app.guidelines.items.icnirp.title', [], 'en'),
                'body' => __('app.guidelines.items.icnirp.body', [], 'en'),
            ],
        ];
    }

    public static function generatedPdf(): string
    {
        $pdf = new SimplePdf();
        $pdf->title(__('app.guidelines.pdf_heading', [], 'en'));
        $pdf->paragraph(__('app.ministry_en'));
        $pdf->paragraph(__('app.guidelines.intro', [], 'en'));
        $pdf->paragraph(__('app.guidelines.pdf_note', [], 'en'));
        $pdf->heading(__('app.guidelines.required_title', [], 'en'));

        foreach (self::documents() as $i => $document) {
            $pdf->heading(($i + 1).'. '.$document['title']);
            $pdf->paragraph($document['body']);

            foreach ($document['bullets'] ?? [] as $bullet) {
                $pdf->bullet($bullet);
            }
        }

        $pdf->heading(__('app.guidelines.next_title', [], 'en'));
        $pdf->paragraph(__('app.guidelines.next_body', [], 'en'));
        $pdf->paragraph(__('app.guidelines.compulsory', [], 'en'));

        return $pdf->output();
    }
}
