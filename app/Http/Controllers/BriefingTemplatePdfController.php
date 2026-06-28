<?php

namespace App\Http\Controllers;

use App\Models\BriefingTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class BriefingTemplatePdfController extends Controller
{
    public function __invoke(BriefingTemplate $briefingTemplate): Response
    {
        $briefingTemplate->load('business');

        $pdf = Pdf::loadView('pdf.briefing-template', [
            'template' => $briefingTemplate,
        ])->setPaper('a4', 'portrait');

        $slug = str($briefingTemplate->title)->slug()->limit(40)->toString();

        return $pdf->download("briefing-template-{$slug}.pdf");
    }
}
