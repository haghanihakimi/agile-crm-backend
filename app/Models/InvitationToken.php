<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvitationToken extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invitee_email',
        'token',
        'tokenable_type',
        'tokenable_id',
        'invited_for',
    ];

    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }
}
