<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $fillable = ['user_id','name','type','address','contact','description','website','verification_status','verification_note','profile_public','certificates_public','is_active'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function courses(): HasMany { return $this->hasMany(Course::class); }
    public function bootcamps(): HasMany { return $this->hasMany(Bootcamp::class); }
}
