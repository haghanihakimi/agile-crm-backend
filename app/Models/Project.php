<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_uuid',
        'creator_id',
        'title',
        'description',
        'private',
        'budget',
    ];
    
    /**
    * The attributes that should be hidden for serialization.
    *
    * @var array<int, string>
    */
   protected $hidden = [
       'id',
       'creator_id',
       'created_at',
       'updated_at',
   ];

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'project_organization');
    }

    public function members(): MorphMany
    {
        return $this->morphMany(Member::class, 'memberable');
    }

    public function projectUsers () {
        return $this->members()->with('users');
    }

    public function activeSessions(): MorphMany {
        return $this->morphMany(ActiveSession::class,'sessionable');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_project');
    }
}
