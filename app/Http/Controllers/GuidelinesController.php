<?php

namespace App\Http\Controllers;

use App\Support\SiteRegistrationGuidelines;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuidelinesController extends Controller
{
    public function show(): View
    {
        app()->setLocale('en');

        return view('guidelines.show', [
            'documents' => SiteRegistrationGuidelines::documents(),
        ]);
    }

    public function pdf(): BinaryFileResponse|Response
    {
        $official = SiteRegistrationGuidelines::officialPdfPath();

        if ($official) {
            $filename = SiteRegistrationGuidelines::downloadFilename();

            return response()->file($official, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        return response(SiteRegistrationGuidelines::generatedPdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="site-registration-guidelines.pdf"',
        ]);
    }
}
