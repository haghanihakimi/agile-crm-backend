<?php

namespace App\Services;

use App\Http\Requests\ActiveSessionsRequest;
use App\Models\ActiveSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class SessionsService
{
    private $activeSessionsRequest;

    public function __construct(ActiveSessionsRequest $activeSessionsRequest)
    {
        $this->activeSessionsRequest = $activeSessionsRequest;
    }

    public function toggleSession($user, $type, $id)
    {
        return $this->activeSessionsRequest->store([
            "user_id" => $user,
            "type" => $type,
            "id" => $id
        ]);
    }

    public function activateSession($user, $type)
    {
        $memberable = $user->members()
            ->where('memberable_type', 'App\Models\Organization')->first();

        if ($user->activeSessions()->count() <= 0 && $memberable) {
            return $this->activeSessionsRequest->store([
                "user_id" => $user->id,
                "type" => $type,
                "id" => $memberable->memberable_id
            ]);
        }
    }
}