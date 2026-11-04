<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class CreditNotePdfController extends Controller
{
    public function __invoke(CreditNote $creditNote): Response
    {
        $creditNote->load([
            'business.accountingProfile',
            'client',
            'invoice',
            'items.taxCode',
            'createdBy',
            'issuedBy',
        ]);

        $pdf = Pdf::loadView('pdf.credit-note', compact('creditNote'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("credit-note-{$creditNote->number}.pdf");
    }
}
