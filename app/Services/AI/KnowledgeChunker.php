<?php

namespace App\Services\AI;

final class KnowledgeChunker
{
    /** @return list<string> */
    public function split(string $content): array
    {
        $content = trim(str_replace(["\r\n", "\r"], "\n", $content));
        $size = max(200, min(4000, (int) config('ai_assistant.chunk_chars')));
        $overlap = max(0, min($size - 1, (int) config('ai_assistant.overlap_chars')));
        $length = mb_strlen($content);

        if ($length === 0 || $length > config('ai_assistant.max_document_chars')) {
            throw new AiUnavailable('invalid_document');
        }

        $chunks = [];

        for ($start = 0; $start < $length;) {
            $part = mb_substr($content, $start, $size);
            $end = mb_strlen($part);

            if ($start + $end < $length) {
                $paragraph = mb_strrpos($part, "\n\n");
                if ($paragraph !== false && $paragraph > max($overlap, intdiv($size, 2))) {
                    $end = $paragraph + 2;
                }
            }

            $chunk = trim(mb_substr($part, 0, $end));
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }

            if ($start + $end >= $length) {
                break;
            }

            $start += max(1, $end - $overlap);
        }

        return $chunks;
    }
}
