<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$req = new Illuminate\Http\Request(['query' => 'rice']);
$r = app(App\Http\Controllers\Api\SearchController::class)->search($req);
$j = json_decode($r->getContent(), true);
var_dump($j['total'] ?? $j['mode'] ?? $j);
