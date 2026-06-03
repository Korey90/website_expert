<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SandboxDomainTestCommand extends Command
{
    protected $signature   = 'domain:sandbox-test {--tld= : TLD do testowania, np. .nl, .com, .pl} {--log : Loguj pełny ruch HTTP do STDERR}';
    protected $description = 'Uruchamia testy OpenProvider sandbox z wybranym TLD';

    private const TLD_OPTIONS = [
        '.nl'     => '.nl  (najbardziej stabilny w sandbox)',
        '.com'    => '.com',
        '.pl'     => '.pl',
        '.info'   => '.info',
        '.net'    => '.net',
        '.org'    => '.org',
        '.io'     => '.io',
        '.de'     => '.de',
        '.eu'     => '.eu',
        '.co.uk'  => '.co.uk',
        'custom'  => 'Inny (wpisz ręcznie)...',
    ];

    public function handle(): int
    {
        $tld = $this->option('tld');

        if (! $tld) {
            $labels = array_values(self::TLD_OPTIONS);
            $keys   = array_keys(self::TLD_OPTIONS);

            $selected = $this->choice(
                'Wybierz TLD do testowania',
                $labels,
                0,
            );

            $tld = $keys[array_search($selected, $labels, true)];

            if ($tld === 'custom') {
                $tld = $this->ask('Wpisz TLD (np. .info)');
            }
        }

        if (! str_starts_with((string) $tld, '.')) {
            $tld = '.' . $tld;
        }

        $this->line('');
        $this->line("  Testowanie z TLD: <comment>{$tld}</comment>");
        if ($this->option('log')) {
            $this->line("  Logowanie HTTP:    <comment>włączone (OP_HTTP_LOG=true)</comment>");
        }
        $this->line('');

        $env = array_merge(
            array_filter(getenv(), fn ($v) => is_string($v)),
            ['DOMAIN_TEST_TLD' => $tld],
        );

        if ($this->option('log')) {
            $env['OP_HTTP_LOG'] = 'true';
        }

        $process = new Process(
            [PHP_BINARY, 'artisan', 'test', 'tests/Feature/Domain/OpenProviderSandboxTest.php'],
            base_path(),
            $env,
            null,
            0,
        );

        $tty = Process::isTtySupported();
        if ($tty) {
            $process->setTty(true);
        } else {
            $process->run(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });

            return $process->getExitCode() ?? 1;
        }

        $process->run();

        return $process->getExitCode() ?? 1;
    }
}
