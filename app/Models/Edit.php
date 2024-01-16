<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Edit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'edit_uuid',
        'edited_by',
        'editable_type',
        'editable_id',
    ];
    
    /**
    * The attributes that should be hidden for serialization.
    *
    * @var array<int, string>
    */
   protected $hidden = [
       'id',
       'editable_type',
       'editable_id',
   ];

    public function editable(): MorphTo
    {
        return $this->morphTo();
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
