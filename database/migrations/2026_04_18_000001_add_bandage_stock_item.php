<?php

use App\Models\StockItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        StockItem::create([
            'category'               => 'misc',
            'slug'                   => 'misc_bandage',
            'name'                   => 'Bandage',
            'default_sell_price'     => 500,
            'default_purchase_price' => 500,
            'unit_weight_g'          => 164,
            'quantity'               => 82,
            'is_sellable'            => true,
            'is_active'              => true,
            'sort_order'             => 20,
        ]);
    }

    public function down(): void
    {
        StockItem::where('slug', 'misc_bandage')->delete();
    }
};
