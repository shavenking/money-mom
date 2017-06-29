<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('platform_user_id')->withTimestamps();
    }

    public function usersByPlatformUserId($platformUserId)
    {
        return $this->users()->wherePivot('platform_user_id', $platformUserId);
    }

    public function hasNoUser($platformUserId)
    {
        return !$this->hasUser($platformUserId);
    }

    public function hasUser($platformUserId)
    {
        return $this->usersByPlatformUserId($platformUserId)->exists();
    }
}
