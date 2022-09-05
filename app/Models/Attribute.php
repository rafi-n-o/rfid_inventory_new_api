<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    public function prefixTableName($prefix)
    {
        $this->table = $prefix . "_attributes";
        return $this;
    }

    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'list'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
