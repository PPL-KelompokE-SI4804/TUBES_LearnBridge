<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    protected $fillable = ['user_id','name','type','description','website','verification_status','verification_note','profile_public','certificates_public'];
    public function user() { return $this->belongsTo(User::class); }
}
