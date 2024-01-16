<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\PasswordResetToken;

class RestoreAccountController extends Controller
{
    public function sendLink(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "email" => ["required", "email"],
            ]
        );

        if (!$validator->fails()) {
            $status = Password::sendResetLink($request->only('email'));

            return response()->json(
                $status === Password::RESET_LINK_SENT ? "Email sent" : __($status)
            );
        }
        return response()->json($validator->errors());
    }

    public function resetPassword ($token) {
        return response()->json();
    }
}
