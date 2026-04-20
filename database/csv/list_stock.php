<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$items = App\Models\StockItem::active()->orderBy('category')->orderBy('sort_order')->get();
foreach ($items as $s) {
    echo $s->slug . '|' . $s->name . '|' . $s->category . '|' . $s->quantity . PHP_EOL;
}
