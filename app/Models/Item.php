<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    public function prefixTableName($prefix)
    {
        $this->table = $prefix . "_items";
        return $this;
    }

    use HasFactory;

    protected $fillable = [
        'epc',
        'in_stock',
        'on_transfer',
        'attribute1_value',
        'attribute2_value',
        'attribute3_value',
        'product_id',
        'location_id',
        'warehouse_id',
        'path'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
