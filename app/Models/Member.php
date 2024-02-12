<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Member extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'memberable_id',
        'memberable_type',
        'role',
    ];
    
    /**
    * The attributes that should be hidden for serialization.
    *
    * @var array<int, string>
    */
   protected $hidden = [
       'id',
       'memberable_type',
       'memberable_id',
       'updated_at',
   ];

    public function memberable(): MorphTo
    {
        return $this->morphTo();
    }

    public function users() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
