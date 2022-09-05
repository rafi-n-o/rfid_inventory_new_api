<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    public function prefixTableName($prefix)
    {
        $this->table = $prefix . "_locations";
        return $this;
    }

    use HasFactory;

    protected $fillable = [
        'name',
        'warehouse_id',
        'path'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
