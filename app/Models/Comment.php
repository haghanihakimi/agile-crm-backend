<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'comment_uuid',
        'user_id',
        'commentable_type',
        'commentable_id',
        'comment',
    ];
    
    /**
    * The attributes that should be hidden for serialization.
    *
    * @var array<int, string>
    */
   protected $hidden = [
       'id',
       'commentable_type',
       'commentable_id',
       'updated_at',
   ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function users() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
