<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PasswordResetToken;
use App\Jobs\PasswordResetLink;
use Carbon\Carbon;

class RestoreAccountController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     * this function sends unique link to users who cannot remember their current password.
     */
    public function sendLink(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "email" => ["required", "email"],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "message" => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                "code" => 404,
                "message" => "We couldn't find your account! Please double check your email address and try again.",
            ], 404);
        }

        PasswordResetLink::dispatch($user);

        return response()->json([
            "code" => 200,
            "message" => 'A link has been sent to your email to reset your password.',
        ], 200);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * This function is used ONLY for authenticated users since they don't use any "Reset Password URL" or "Tokens".
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "current_password" => ["required", "string", "min:6"],
                "new_password" => ["required", "string", "min:6", "confirmed"],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "message" => $validator->errors(),
            ], 422);
        }

        $user = Auth::guard('api')->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                "code" => 400,
                "message" => 'Your current password is incorrect!',
            ], 400);
        }

        if (Hash::check($request->new_password_confirmation, $user->password)) {
            return response()->json([
                "code" => 400,
                "message" => 'The new password cannot be same as the old one!',
            ], 400);
        }

        $user->password = $request->new_password_confirmation;
        $user->save();


        return response()->json([
            "code" => 200,
            "message" => 'Your password successfully changed.',
        ], 200);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * This function also changes password but it can be use ONLY by unauthenticated users via a "Reset Password Link"
     * which contains unique Token.
     */
    public function resetPassword(Request $request, $username)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "current_password" => ["required", "string", "min:6"],
                "new_password" => ["required", "string", "min:6", "confirmed"],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "message" => $validator->errors(),
            ], 422);
        }

        $user = User::where('username', $username)->first();

        if (!$this->verifySignature($request, $username)->getData()) {
            return response()->json([
                "code" => 403,
                "message" => 'Invalid link!',
            ], 403);
        }

        $token = PasswordResetToken::where('email', $user->email)->delete();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                "code" => 400,
                "message" => 'Your current password is incorrect!',
            ], 400);
        }

        if (Hash::check($request->new_password_confirmation, $user->password)) {
            return response()->json([
                "code" => 400,
                "message" => 'The new password cannot be same as the old one!',
            ], 400);
        }

        $user->password = $request->new_password_confirmation;
        $user->save();


        return response()->json([
            "code" => 200,
            "message" => 'Your password successfully changed.',
        ], 200);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * This function can be used ONLY for unauthenticated users bot it can be used for both API requests and
     * also can be called in other functions like "resetPassword()".
     */
    public function verifySignature(Request $request, $user)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "signature" => ["required", "string", "min:64", "max:64"],
            ]
        );

        if ($validator->fails()) {
            return response()->json(false);
        }

        $user = User::where('username', $user)->first();

        if (!$user) {
            return response()->json(false);
        }

        $token = PasswordResetToken::where('email', $user->email)
            ->whereRaw('BINARY token = ?', [$request->signature])
            ->where('updated_at', '>=', Carbon::now()->subDay())->first();

        if (!$token) {
            return response()->json(false);
        }

        return response()->json(true);
    }
}
