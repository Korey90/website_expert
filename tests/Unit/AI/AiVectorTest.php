<?php

namespace Tests\Unit\AI;

use App\Services\AI\AiUnavailable;
use App\Services\AI\AiVector;
use PHPUnit\Framework\TestCase;

class AiVectorTest extends TestCase
{
    public function test_valid_vectors_and_cosine(): void
    {
        $this->assertTrue(AiVector::valid([1, 0], 2));
        $this->assertEqualsWithDelta(1, AiVector::cosine([1, 0], [2, 0]), 0.000001);
        $this->assertEqualsWithDelta(0, AiVector::cosine([1, 0], [0, 1]), 0.000001);
        $this->assertEqualsWithDelta(-1, AiVector::cosine([1, 0], [-1, 0]), 0.000001);
    }

    public function test_invalid_vectors_are_rejected(): void
    {
        foreach ([[0, 0], [INF, 1], [NAN, 1], ['1', 0], [1], [1 => 1, 2 => 1]] as $vector) {
            $this->assertFalse(AiVector::valid($vector, 2));
        }

        $this->expectException(AiUnavailable::class);
        AiVector::cosine([0, 0], [1, 0]);
    }
}
