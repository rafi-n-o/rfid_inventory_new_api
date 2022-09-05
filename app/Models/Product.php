<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function prefixTableName($prefix)
    {
        $this->table = $prefix . "_products";
        return $this;
    }

    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'category_id',
        'attribute1_id',
        'attribute2_id',
        'attribute3_id'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
