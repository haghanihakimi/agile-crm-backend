<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;
use App\Models\User;

class RegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "firstname" => ["required", "string", "min:3", "max:24"],
                "lastname" => ["required", "string", "min:3", "max:24"],
                "username" => ["nullable", "string", "min:4", "unique:users"],
                "email" => ["required", "email", "unique:users"],
                "password" => ["required", "string", "min:6", "confirmed"],
            ]
        );

        if (!$validator->fails()) {
            $user = User::create([
                "firstname" => $request->firstname,
                "lastname" => $request->lastname,
                "username" => !empty($request->username) ? $request->username : Str::upper(Str::random(16)),
                "email" => $request->email,
                "email_verified_at" => null,
                "password" => Hash::make($request->password_confirmation),
            ]);
            return response()->json($user);
        }
        return response()->json($validator->errors());
    }
}
