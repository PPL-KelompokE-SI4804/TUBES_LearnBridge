<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumThread extends Model
{
    use HasFactory;

    // Kolom yang diizinkan untuk diisi secara massal (mass assignment)
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'content',
        'status',
    ];

    /**
     * Relasi ke model User (Pembuat Diskusi)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke model Category (Kategori Pembelajaran/Diskusi)
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi ke model ForumComment (Komentar pada Diskusi untuk PBI 18)
     */
    public function comments()
    {
        return $this->hasMany(ForumComment::class, 'thread_id');
    }
}