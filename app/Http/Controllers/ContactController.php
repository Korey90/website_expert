<?php

namespace App\Http\Controllers;

use App\Actions\CreateLeadAction;
use App\Http\Requests\ContactRequest;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function store(ContactRequest $request, CreateLeadAction $action): JsonResponse
    {
        $data = $request->validated();

        $action->execute([
            'email'           => $data['email'],
            'name'            => $data['name'] ?? null,
            'company'         => $data['company'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'nip'             => $data['nip'] ?? null,
            'project_type'    => $data['project_type'] ?? null,
            'source'          => 'contact_form',
            'notes'           => $data['message'] ?? null,
            'calculator_data' => $data,
        ]);

        return response()->json(['message' => 'ok'], 201);
    }
}
