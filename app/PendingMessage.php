<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PendingMessage extends Model
{
    protected $fillable = ['user_id', 'platform_id', 'content'];
}
