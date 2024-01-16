<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class File extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_uuid',
        'uploaded_by',
        'fileable_type',
        'fileable_id',
        'file_name',
        'original_name',
        'file_format',
        'file_type',
        'file_path',
        'file_size',
        'used_for',
    ];
    
    /**
    * The attributes that should be hidden for serialization.
    *
    * @var array<int, string>
    */
   protected $hidden = [
       'id',
       'uploaded_by',
       'fileable_type',
       'fileable_id',
       'file_name',
       'file_type',
       'used_for',
       'created_at',
       'updated_at',
   ];

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
