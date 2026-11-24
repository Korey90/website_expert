<?php

namespace App\Http\Controllers\AI;

use App\Actions\AI\SendPublicAiMessageAction;
use App\Actions\AI\CaptureAiLeadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AI\PublicAiMessageRequest;
use App\Http\Requests\AI\PublicAiLeadRequest;
use App\Services\AI\AiUnavailable;
use App\Services\AI\PublicAiBotGuard;
use App\Services\AI\PublicAssistantResolver;
use App\Services\AI\PublicConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PublicAiChatController extends Controller
{
    public function bootstrap(PublicAssistantResolver $resolver): JsonResponse
    {
        $assistant = $resolver->resolve();

        return response()->json([
            'enabled' => $assistant !== null,
            'name' => $assistant?->name,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function history(
        Request $request,
        PublicAssistantResolver $resolver,
        PublicConversationService $conversations,
    ): JsonResponse {
        $assistant = $resolver->resolve();
        abort_unless($assistant, 404);

        $conversation = $conversations->current($request, $assistant);

        return response()->json([
            'messages' => $conversation ? $conversations->history($conversation) : [],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function send(
        PublicAiMessageRequest $request,
        PublicAssistantResolver $resolver,
        SendPublicAiMessageAction $action,
        PublicAiBotGuard $guard,
    ): JsonResponse {
        $assistant = $resolver->resolve();
        abort_unless($assistant, 404);

        try {
            $guard->verify($request, 'website_ai_chat');
            $result = $action->execute($request, $assistant, $request->validated());
        } catch (AiUnavailable $exception) {
            $status = $exception->reason === 'request_in_progress' ? 409 : 503;

            return response()->json([
                'status' => 'unavailable',
                'answer' => __('ai_assistant.unavailable'),
                'sources' => [],
            ], $status)->header('Cache-Control', 'no-store, private');
        }

        return response()->json($result)->header('Cache-Control', 'no-store, private');
    }

    public function captureLead(
        PublicAiLeadRequest $request,
        PublicAssistantResolver $resolver,
        CaptureAiLeadAction $action,
        PublicAiBotGuard $guard,
    ): JsonResponse {
        $assistant = $resolver->resolve();
        abort_unless($assistant, 404);

        try {
            $guard->verify($request, 'website_ai_chat');
            $result = $action->execute($request, $assistant, $request->validated());
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['success' => false, 'message' => __('ai_assistant.unavailable')], 503)
                ->header('Cache-Control', 'no-store, private');
        }

        return response()->json($result)->header('Cache-Control', 'no-store, private');
    }

    public function restart(
        Request $request,
        PublicAssistantResolver $resolver,
        PublicConversationService $conversations,
    ): JsonResponse {
        $assistant = $resolver->resolve();
        abort_unless($assistant, 404);
        $conversations->forget($request, $assistant);

        return response()->json(['success' => true])->header('Cache-Control', 'no-store, private');
    }
}
