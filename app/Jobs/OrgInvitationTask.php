<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Models\InvitationToken;
use App\Models\User;
use App\Notifications\OrgInviteNotification;

class OrgInvitationTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $authUser;
    protected $user;
    protected $organizaton;

    /**
     * Create a new job instance.
     */
    public function __construct($authUser, $user, $organizaton)
    {
        $this->authUser = $authUser;
        $this->user = $user;
        $this->organizaton = $organizaton;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::where("email", $this->user->email)->first();
        if ($user) {
            $token = InvitationToken::create([
                "invitee_email" => $user->email,
                "token" => Str::random(64),
                "tokenable_type" => "App\Models\Organization",
                "tokenable_id" => $this->organizaton->id,
                "invited_for" => "Organization",
            ]);
            $url = config('app.front_url')."/organization/new/user/invitation/".$user->email."/".$token->token;
            $data = [
                "subject" => $this->authUser->firstname . ' requested you to joing ' . $this->organizaton->name,
                "greeting" => !empty($user->firstname) ? "Hello ".$user->firstname."!" : "Hello!",
                "text" => $this->authUser->firstname . ' invited you to join their ' . $this->organizaton->name . ' organization.<br />To join this organization follow given link below, and create an account with us then join the organization.',
                "button" => "Join " . $this->organizaton->name,
                "link" => $url,
            ];
            Notification::send($this->user, new OrgInviteNotification($data));
        }
    }
}
