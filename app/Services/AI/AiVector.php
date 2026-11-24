<?php

namespace App\Services\AI;

final class AiVector
{
    public static function valid(array $vector, int $dimensions): bool
    {
        if ($dimensions < 1 || ! array_is_list($vector) || count($vector) !== $dimensions) {
            return false;
        }

        $norm = 0.0;

        foreach ($vector as $value) {
            if ((! is_float($value) && ! is_int($value)) || ! is_finite((float) $value)) {
                return false;
            }

            $norm += $value * $value;
        }

        return $norm > 0 && is_finite($norm);
    }

    public static function cosine(array $left, array $right): float
    {
        $dimensions = count($left);

        if (! self::valid($left, $dimensions) || ! self::valid($right, $dimensions)) {
            throw new AiUnavailable('invalid_embedding');
        }

        $dot = $a = $b = 0.0;

        foreach ($left as $i => $value) {
            $dot += $value * $right[$i];
            $a += $value * $value;
            $b += $right[$i] * $right[$i];
        }

        return max(-1.0, min(1.0, ($dot / sqrt($a)) / sqrt($b)));
    }
}
