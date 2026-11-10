<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\GenerateAccountantPackAction;
use App\Http\Controllers\Controller;
use App\Models\VatReturn;
use DomainException;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AccountantPackController extends Controller
{
    public function __invoke(VatReturn $vatReturn, GenerateAccountantPackAction $action): BinaryFileResponse
    {
        Gate::authorize('view', $vatReturn);

        try {
            $pack = $action->execute($vatReturn, auth()->user());
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }

        $response = response()->download($pack['path'], $pack['filename'], [
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Accountant-Pack-SHA256' => $pack['sha256'],
            'X-VAT-Return-Snapshot-SHA256' => $vatReturn->snapshot_hash,
        ]);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response->deleteFileAfterSend(true);
    }
}
