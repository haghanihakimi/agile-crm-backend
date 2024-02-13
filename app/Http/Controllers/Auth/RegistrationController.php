<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\InvitationSerivce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class RegistrationController extends Controller
{
    private $invitationSerivce;

    public function __construct(InvitationSerivce $invitationSerivce)
    {
        $this->invitationSerivce = $invitationSerivce;
    }

    public function store(Request $request)
    {
        if ($request->token || $this->invitationSerivce->validateInvitation($request->email, $request->token)) {
            $validator = Validator::make($request->all(), [
                "firstname" => ["required", "string", "min:3", "max:24"],
                "lastname" => ["required", "string", "min:3", "max:24"],
                "email" => ["required", "email"],
                "password" => ["required", "string", "min:6", "confirmed"],
                "token" => ["required", "string", "max:64", "min:64"],
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                "firstname" => ["required", "string", "min:3", "max:24"],
                "lastname" => ["required", "string", "min:3", "max:24"],
                "email" => ["required", "email", "unique:users"],
                "password" => ["required", "string", "min:6", "confirmed"],
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "message" => $validator->errors(),
                "token" => null,
                "expire" => null,
                "user" => null,
            ], 422);
        }

        $user = User::updateOrCreate(
            [
                "email" => $request->email,
            ],
            [
                "firstname" => $request->firstname,
                "lastname" => $request->lastname,
                "username" => Str::upper(Str::random(16)),
                "email_verified_at" => null,
                "password" => Hash::make($request->password_confirmation),
            ]
        );

        if (!$user) {
            return response()->json([
                "code" => 500,
                "message" => "Unable to create new account at this moment. Please try again later.",
                "token" => null,
                "expire" => null,
                "user" => null,
            ], 500);
        }

        $token = $user->createToken($user->id . ':login', ['general:full'], now()->addMonth())->plainTextToken;

        return response()->json([
            'code' => 200,
            'message' => 'Your account successfully created.',
            'token' => $token,
            'expire' => Carbon::parse(now()->addMonth())->timestamp,
            'user' => $user,
        ], 200);
    }
}
