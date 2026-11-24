<?php

namespace App\Actions\AI;

use App\Models\AiAssistant;
use App\Models\AiMessage;
use App\Models\AiUnansweredQuestion;
use App\Scopes\BusinessScope;
use App\Services\AI\AiUnavailable;
use App\Services\AI\GroundedAnswerService;
use App\Services\AI\PublicConversationService;
use Illuminate\Http\Request;

final class SendPublicAiMessageAction
{
    public function __construct(
        private readonly PublicConversationService $conversations,
        private readonly GroundedAnswerService $answerer,
    ) {}

    /** @return array{status:string,answer:string,sources:array} */
    public function execute(Request $request, AiAssistant $assistant, array $data): array
    {
        $locale = $data['locale'];
        app()->setLocale($locale);
        $conversation = $this->conversations->getOrStart($request, $assistant, $locale);

        $messages = AiMessage::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $assistant->business_id)
            ->where('conversation_id', $conversation->id);

        $duplicate = (clone $messages)->where('client_message_id', $data['client_message_id'])->first();
        if ($duplicate) {
            $reply = (clone $messages)->where('in_reply_to_id', $duplicate->id)->first();

            if (! $reply) {
                throw new AiUnavailable('request_in_progress');
            }

            return ['status' => $reply->status ?? 'unknown', 'answer' => $reply->content, 'sources' => $reply->sources ?? []];
        }

        if ((clone $messages)->count() >= (int) config('ai_assistant.max_messages_per_conversation')) {
            throw new AiUnavailable('conversation_limit');
        }

        // Do not treat previous assistant responses as trusted knowledge.
        $previous = (clone $messages)->where('role', 'user')->latest()->first();
        $question = trim($data['message']);
        if ($previous && mb_strlen($question) < 120) {
            $question = mb_substr($previous->content, 0, 300)."\n".$question;
        }

        $userMessage = new AiMessage;
        $userMessage->forceFill([
            'business_id' => $assistant->business_id,
            'conversation_id' => $conversation->id,
            'client_message_id' => $data['client_message_id'],
            'role' => 'user',
            'content' => trim($data['message']),
            'status' => 'received',
        ])->save();

        try {
            $answer = $this->answerer->answer(
                $assistant->business_id, $assistant->id, mb_substr($question, 0, (int) config('ai_assistant.max_question_chars')), $locale
            );
        } catch (\Throwable $exception) {
            report($exception);
            $answer = ['status' => 'unavailable', 'answer' => __('ai_assistant.unavailable'), 'sources' => []];
        }

        $reply = new AiMessage;
        $reply->forceFill([
            'business_id' => $assistant->business_id,
            'conversation_id' => $conversation->id,
            'in_reply_to_id' => $userMessage->id,
            'role' => 'assistant',
            'content' => $answer['answer'],
            'status' => $answer['status'],
            'sources' => $answer['sources'],
        ])->save();

        if ($answer['status'] === 'unknown') {
            $unanswered = new AiUnansweredQuestion;
            $unanswered->forceFill([
                'business_id' => $assistant->business_id,
                'conversation_id' => $conversation->id,
                'message_id' => $userMessage->id,
                'question' => trim($data['message']),
                'status' => 'open',
            ])->save();
        }

        $conversation->forceFill(['locale' => $locale, 'last_message_at' => now()])->save();

        return $answer;
    }
}
