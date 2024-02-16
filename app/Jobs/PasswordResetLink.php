<?php

namespace App\Jobs;

use App\Models\PasswordResetToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Notifications\PasswordResetLink as ResetPasswordNotification;

class PasswordResetLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $token = PasswordResetToken::updateOrCreate(
            [
                "email" => $this->user->email
            ],
            [
                "token" => Str::random(64),
            ]
        );

        $fullUrl = config('app.front_url') . "/reset-password/" . $this->user->username . "?signature=" . $token->token;

        $data = [
            "greeting" => "Hi " . $this->user->firstname . ",<br />",
            "subject" => "Reset Password Notification",
            "text" => "You are receiving this email because we received a password reset request for your account.",
            "button" => "Reset Password",
            "link" => $fullUrl,
        ];

        Notification::send($this->user, new ResetPasswordNotification($data));
    }
}
