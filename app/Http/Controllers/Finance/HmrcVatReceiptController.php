<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\GenerateHmrcVatReceiptAction;
use App\Http\Controllers\Controller;
use App\Models\VatReturn;
use DomainException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class HmrcVatReceiptController extends Controller
{
    public function __invoke(VatReturn $vatReturn, GenerateHmrcVatReceiptAction $action): Response
    {
        Gate::authorize('downloadReceipt', $vatReturn);

        try {
            $receipt = $action->execute($vatReturn, auth()->user());
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }

        $response = response($receipt['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$receipt['filename'].'"',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-HMRC-Receipt-SHA256' => $receipt['sha256'],
            'X-VAT-Return-Snapshot-SHA256' => $vatReturn->snapshot_hash,
        ]);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
