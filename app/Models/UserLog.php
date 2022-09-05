<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    public function prefixTableName($prefix)
    {
        $this->table = $prefix . "_user_logs";
        return $this;
    }

    use HasFactory;

    protected $fillable = [
        'at',
        'device',
        'version',
        'activity',
        'user_id',
        'user_data'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];
}
