<?php

namespace App\Console\Commands;

use App\Actions\Finance\GenerateUkAccountantDemoPackAction;
use Illuminate\Console\Command;

class GenerateUkAccountantDemoPack extends Command
{
    protected $signature = 'finance:generate-uk-accountant-demo-pack
        {--output=docs/demo/uk-accountant-pack : Relative or absolute output directory}';

    protected $description = 'Generate UK finance PDF examples using system company details for accountant review';

    public function handle(GenerateUkAccountantDemoPackAction $action): int
    {
        $output = (string) $this->option('output');
        $directory = $this->isAbsolutePath($output) ? $output : base_path($output);
        $result = $action->execute($directory);

        $this->info('Generated '.count($result['pdfs']).' demonstration PDF documents.');
        $this->line('Directory: '.$result['directory']);
        $this->line('Manifest: '.$result['manifest']);
        $this->line('ZIP: '.($result['zip'] ?? 'not created (ZipArchive unavailable)'));

        return self::SUCCESS;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
