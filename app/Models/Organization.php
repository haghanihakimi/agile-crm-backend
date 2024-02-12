<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Organization extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'org_uuid',
        'creator_id',
        'name',
        'description',
        'image',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function members(): MorphMany
    {
        return $this->morphMany(Member::class, 'memberable');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, Member::class, 'user_id');
    }

    public function activeSessions(): MorphMany
    {
        return $this->morphMany(ActiveSession::class, 'sessionable');
    }

    public function tokens(): MorphMany
    {
        return $this->morphMany(InvitationToken::class, 'tokenable');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_organization');
    }
}
