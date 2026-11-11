<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumComment extends Model
{
    use HasFactory;
    
    protected $table = 'comments';
    protected $fillable = ['discussion_id', 'user_id', 'body'];

    public function thread()
    {
        return $table->belongsTo(ForumThread::class, 'discussion_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
