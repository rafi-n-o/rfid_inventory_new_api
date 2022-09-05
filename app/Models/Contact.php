<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    public function prefixTableName($prefix)
    {
        $this->table = $prefix . "_contacts";
        return $this;
    }

    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
