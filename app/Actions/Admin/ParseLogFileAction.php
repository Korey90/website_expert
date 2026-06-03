<?php

namespace App\Actions\Admin;

use Illuminate\Support\Collection;

class ParseLogFileAction
{
    private const LEVEL_PATTERN = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2}|Z)?)\] (\w+)\.(\w+): (.*)/s';

    /**
     * Parse a log file and return structured entries, newest first.
     *
     * @return Collection<int, array{datetime: string, env: string, level: string, message: string, context: string}>
     */
    public function execute(string $filePath, string $levelFilter = 'ALL', string $search = ''): Collection
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return collect();
        }

        $raw     = file_get_contents($filePath);
        $entries = $this->split($raw);

        return collect($entries)
            ->filter(function (array $entry) use ($levelFilter, $search): bool {
                if ($levelFilter !== 'ALL' && strtoupper($entry['level']) !== strtoupper($levelFilter)) {
                    return false;
                }
                if ($search !== '' && ! str_contains(strtolower($entry['message']), strtolower($search))) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    /** @return array<int, array{datetime: string, env: string, level: string, message: string, context: string}> */
    private function split(string $raw): array
    {
        // Split on lines that start a new log entry
        $chunks  = preg_split('/(?=^\[\d{4}-\d{2}-\d{2})/m', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $entries = [];

        foreach (array_reverse($chunks ?? []) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            if (preg_match(self::LEVEL_PATTERN, $chunk, $m)) {
                $entries[] = [
                    'datetime' => $m[1],
                    'env'      => $m[2],
                    'level'    => strtoupper($m[3]),
                    'message'  => rtrim($m[4]),
                    'context'  => '',
                ];
            } else {
                // Unrecognised line — treat as raw
                $entries[] = [
                    'datetime' => '',
                    'env'      => '',
                    'level'    => 'RAW',
                    'message'  => $chunk,
                    'context'  => '',
                ];
            }
        }

        return $entries;
    }
}
