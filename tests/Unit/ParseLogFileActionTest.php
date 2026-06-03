<?php

namespace Tests\Unit;

use App\Actions\Admin\ParseLogFileAction;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ParseLogFileActionTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'laravel_test_') . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        parent::tearDown();
    }

    private function writeLog(string $content): void
    {
        file_put_contents($this->tmpFile, $content);
    }

    private function action(): ParseLogFileAction
    {
        return new ParseLogFileAction();
    }

    public function test_returns_empty_collection_for_missing_file(): void
    {
        $result = $this->action()->execute('/non/existent/file.log');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_parses_single_error_entry(): void
    {
        $this->writeLog("[2026-06-03 10:00:00] local.ERROR: Something went wrong {\"exception\":\"[object] (RuntimeException)\"}\n");

        $result = $this->action()->execute($this->tmpFile);

        $this->assertCount(1, $result);
        $this->assertEquals('ERROR', $result->first()['level']);
        $this->assertStringContainsString('Something went wrong', $result->first()['message']);
        $this->assertEquals('2026-06-03 10:00:00', $result->first()['datetime']);
    }

    public function test_parses_multiple_entries_newest_first(): void
    {
        $this->writeLog(
            "[2026-06-03 08:00:00] local.INFO: First entry\n" .
            "[2026-06-03 09:00:00] local.ERROR: Second entry\n" .
            "[2026-06-03 10:00:00] local.WARNING: Third entry\n"
        );

        $result = $this->action()->execute($this->tmpFile);

        $this->assertCount(3, $result);
        $this->assertEquals('WARNING', $result->get(0)['level']);
        $this->assertEquals('ERROR', $result->get(1)['level']);
        $this->assertEquals('INFO', $result->get(2)['level']);
    }

    public function test_filters_by_level(): void
    {
        $this->writeLog(
            "[2026-06-03 08:00:00] local.INFO: Info message\n" .
            "[2026-06-03 09:00:00] local.ERROR: Error message\n"
        );

        $result = $this->action()->execute($this->tmpFile, 'ERROR');

        $this->assertCount(1, $result);
        $this->assertEquals('ERROR', $result->first()['level']);
    }

    public function test_filters_by_search(): void
    {
        $this->writeLog(
            "[2026-06-03 08:00:00] local.INFO: Connection timeout\n" .
            "[2026-06-03 09:00:00] local.ERROR: Database error occurred\n"
        );

        $result = $this->action()->execute($this->tmpFile, 'ALL', 'database');

        $this->assertCount(1, $result);
        $this->assertStringContainsString('Database error', $result->first()['message']);
    }

    public function test_returns_empty_for_empty_file(): void
    {
        $this->writeLog('');

        $result = $this->action()->execute($this->tmpFile);

        $this->assertCount(0, $result);
    }

    public function test_level_filter_all_returns_all_entries(): void
    {
        $this->writeLog(
            "[2026-06-03 08:00:00] local.DEBUG: Debug message\n" .
            "[2026-06-03 09:00:00] local.INFO: Info message\n" .
            "[2026-06-03 10:00:00] local.ERROR: Error message\n"
        );

        $result = $this->action()->execute($this->tmpFile, 'ALL');

        $this->assertCount(3, $result);
    }
}
