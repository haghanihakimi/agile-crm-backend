<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActiveSessionsRequest;
use App\Services\SessionsService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    private $sessionsService;

    public function __construct(SessionsService $sessionsService)
    {
        $this->sessionsService = $sessionsService;
    }

    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "email" => ["required", "email"],
                "password" => ["required", "string"],
            ]
        );
        if (!$validator->fails()) {
            $user = User::withTrashed()->where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                if ($user->trashed()) {
                    $user->restore();
                }

                $token = $user->createToken($user->id . ':login', ['general:full'], now()->addMonths(1))->plainTextToken;

                $this->sessionsService->activateSession($user, 'App\Models\Organization');

                return response()->json([
                    'token' => $token,
                    'expire' => env('SESSION_LIFETIME'),
                    'user' => $user,
                ], 200);
            }
            return response()->json([
                "message" => "Incorrect email or password.",
                "code" => 500,
            ], 500);
        }
        return response()->json([
            "message" => $validator->errors(),
            "code" => 422,
        ], 422);
    }

    public function signout()
    {
        $user = Auth::guard('api')->user();
        if ($user) {
            $token = $user->currentAccessToken()->delete();

            return response()->json(true);
        } else {
            return response()->json(false);
        }
    }
}
