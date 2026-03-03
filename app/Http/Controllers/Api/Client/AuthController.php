<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\CloudinaryHelper;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    public function __construct()
    {
        Auth::shouldUse('api_user');
    }

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string',
                'password' => 'required|min:6|confirmed',
                'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
            ]);

            $avatarUrl = null;
            $avatarPublicId = null;

            if ($request->hasFile('avatar')) {
                $upload = CloudinaryHelper::upload(
                    $request->file('avatar'),
                    'avatars'
                );

                $avatarUrl = $upload['url'];
                $avatarPublicId = $upload['public_id'];
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'avatar' => $avatarUrl,
                'avatar_public_id' => $avatarPublicId,
            ]);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message' => "Dang ky tai khoan thanh cong",
                'user' => $user,
                'tokenUser' => $token
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if (!$token = Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Email hoặc mật khẩu không đúng'
                ], 422);
            }

            $user = Auth::user();

            return response()->json([
                'message' => 'Đăng nhập thành công',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone
                ],
                'tokenUser' => $token
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    public function logout()
    {
        try {
            Auth::logout();

            return response()->json([
                'message' => 'Đăng xuất thành công'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        try {

            $user = $request->user();

            return response()->json([
                'message' => 'Thành công',
                'user' => $user->only([
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'phone'
                ])
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $newToken = Auth::refresh();

            return response()->json([
                'message' => 'Refresh token thành công',
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 60
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Token không thể refresh'
            ], 401);
        }
    }
}
