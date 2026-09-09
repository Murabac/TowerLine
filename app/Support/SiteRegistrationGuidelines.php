<?php

namespace App\Support;

class SiteRegistrationGuidelines
{
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
