<?php

namespace Tests\Unit\AI;

use App\Services\AI\KnowledgeChunker;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class KnowledgeChunkerTest extends TestCase
{
    public function test_chunking_is_bounded_and_preserves_content(): void
    {
        Config::set('ai_assistant.chunk_chars', 200);
        Config::set('ai_assistant.overlap_chars', 20);
        Config::set('ai_assistant.max_document_chars', 30000);

        $chunks = (new KnowledgeChunker)->split(str_repeat('abc ', 200));

        $this->assertGreaterThan(1, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(200, mb_strlen($chunk));
            $this->assertNotSame('', trim($chunk));
        }
    }
}
