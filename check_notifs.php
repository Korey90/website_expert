<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();
$rows = DB::table('notifications')->orderBy('created_at', 'desc')->get();
foreach ($rows as $r) {
    $d = json_decode($r->data, true);
    $url = $d['actions'][0]['url'] ?? '(no action)';
    $status = $r->read_at ? 'READ' : 'UNREAD';
    echo $r->created_at . ' ' . $status . ' url=' . $url . PHP_EOL;
}
