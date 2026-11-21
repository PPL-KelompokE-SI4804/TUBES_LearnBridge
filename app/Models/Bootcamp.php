<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bootcamp extends Model
{
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
