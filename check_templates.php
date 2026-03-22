<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (DB::table('project_templates')->get() as $t) {
    $phases = json_decode($t->phases, true) ?? [];
    $taskTotal = array_sum(array_map(fn($p) => count($p['tasks'] ?? []), $phases));
    echo $t->name . ' — ' . count($phases) . ' phases, ' . $taskTotal . ' tasks' . PHP_EOL;
    foreach ($phases as $p) {
        echo '  ' . $p['order'] . '. ' . $p['name'] . ' (' . count($p['tasks'] ?? []) . ' tasks)' . PHP_EOL;
    }
}
