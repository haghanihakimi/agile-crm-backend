<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function getProfileUser($userId)
    {
        $user = User::find($userId)->makeVisible(['email']);

        $user->organizations = $user->members()->where('memberable_type', 'App\Models\Organization')->count();
        $user->projects = $user->members()->where('memberable_type', 'App\Models\Project')->count();
        $user->tasks = $user->members()->where('memberable_type', 'App\Models\Task')->count();

        return response()->json([
            "code" => 200,
            "message" => '',
            "user" => $user,
        ], 200);
    }

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

    public function changePicture(Request $request)
    {
        // validate
        $user = Auth::guard('api')->user()->makeVisible(['email_verified_at', 'created_at']);

        if (!$request->hasFile('newImage')) {
            return response()->json([
                "code" => 422,
                "message" => "Select image file to continue.",
                "user" => $user,
            ], 422);
        }

        $file = $request->file('newImage');
        $originalName = $file->getClientOriginalName();
        $originalMimeType = $file->getClientMimeType();
        $originalFormat = $file->getClientOriginalExtension();
        $originalFileSize = $file->getSize();
        $hashName = $file->hashName();

        $stored = $file->storeAs('uploads/users/profiles/' . $user->username, $hashName, 'public');

        $user->image = config('app.app_url') . "/storage/" . $stored;
        $user->save();

        $uploadedFile = File::create([
            'file_uuid' => Str::uuid(),
            'uploaded_by' => $user->id,
            'fileable_type' => 'App\Models\User',
            'fileable_id' => $user->id,
            'file_name' => $hashName,
            'original_name' => $originalName,
            'file_format' => $originalFormat,
            'file_type' => $originalMimeType,
            'file_path' => $stored,
            'file_size' => $originalFileSize,
            'used_for' => 'Profile',
        ]);


        return response()->json([
            "code" => 200,
            "message" => "Profile picture successfully updated.",
            "user" => $user,
        ], 200);
    }
}
