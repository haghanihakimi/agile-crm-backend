<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_uuid',
        'creator_id',
        'title',
        'description',
        'is_completed',
        'due_date',
        'private',
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

    public function members(): MorphMany
    {
        return $this->morphMany(Member::class, 'memberable');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function edits(): MorphMany
    {
        return $this->morphMany(Edit::class, 'editable');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'task_project');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function statuses()
    {
        return $this->belongsToMany(Status::class, 'task_status');
    }

    public function priorities()
    {
        return $this->belongsToMany(Priority::class, 'task_priority');
    }

    public function taskPriorities()
    {
        return $this->priorities()->with('priorities');
    }

    public function taskStatuses()
    {
        return $this->statuses()->with('statuses');
    }

    public function taskUsers()
    {
        return $this->members()->with('users');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
