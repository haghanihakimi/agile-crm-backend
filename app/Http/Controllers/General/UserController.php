<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserController extends Controller
{
    public function saveSettings(Request $request)
    {
        $user = Auth::guard('api')->user()->makeVisible(['email_verified_at', 'created_at']);

        $validator = Validator::make(
            $request->user,
            [
                "firstname" => ["required", "string", "max:24", "min:2"],
                "lastname" => ["required", "string", "max:24", "min:2"],
                "username" => ["required", "string", "max:24", "min:5", "unique:users,username," . $user->id . ",id"],
                "email" => ["required", "min:9", "unique:users,email," . $user->id . ",id"],
                "phone" => ["required", "unique:users,phone," . $user->id . ",id"],
                "bio" => ["nullable", "string", "max:1000"],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                "user" => [],
                "message" => $validator->errors(),
            ], 422);
        }

        $user->update([
            "firstname" => $request->user['firstname'],
            "lastname" => $request->user['lastname'],
            "username" => $request->user['username'],
            "email" => $request->user['email'],
            'email_verified_at' => ($request->user['email'] !== $user->email) ? NULL : $user->email_verified_at,
            "phone" => $request->user['phone'],
            "bio" => $request->user['bio'],
        ]);

        return response()->json([
            "user" => $user,
            "message" => "Profile successfully updated.",
        ], 200);
    }

    public function delete()
    {
        $user = Auth::guard('api')->user();


        if ($user->tokens()->delete()) {
            if ($user->delete()) {
                return response()->json([
                    "message" => "Your account deleted."
                ], 200);
            }

            return response()->json([
                "message" => "Revoking token failed."
            ], 500);
        }

        return response()->json([
            "message" => "Deleting your account failed."
        ], 500);
    }
}
