<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fixed = 0;
foreach (DB::table('notifications')->get() as $n) {
    $d = json_decode($n->data, true);
    if (!isset($d['format']) || $d['format'] !== 'filament') {
        $d['format'] = 'filament';
        DB::table('notifications')->where('id', $n->id)->update(['data' => json_encode($d)]);
        echo "Fixed: {$n->id}\n";
        $fixed++;
    }
}
echo "Total fixed: {$fixed}\n";
