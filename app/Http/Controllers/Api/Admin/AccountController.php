<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\CloudinaryHelper;
use App\Models\Account;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function __construct()
    {
        Auth::shouldUse('api_account');
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

            $account = Auth::user();

            return response()->json([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'account' => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'email' => $account->email,
                    'avatar' => $account->avatar,
                    'phone' => $account->phone
                ],
                'token' => $token
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đăng nhập thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function logout()
    {
        try {
            Auth::logout();

            return response()->json([
                'success' => true,
                'message' => 'Đăng xuất thành công'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đăng xuất thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function create(Request $request)
    {
        $validated = $request->validate(
            [
                'name' => 'required|string|max:150',

                'email' => 'required|email|unique:accounts,email',

                'phone' => 'nullable|string|max:20',

                'password' => 'required|string|min:6',

                'avatar' => 'nullable|image|max:2048',

                'role_id' => [
                    'required',
                    Rule::exists('roles', 'id')
                ],

                'status' => 'boolean'
            ],
            [
                'name.required' => 'Tên là bắt buộc',

                'email.required' => 'Email là bắt buộc',
                'email.email' => 'Email không hợp lệ',
                'email.unique' => 'Email đã tồn tại',

                'password.required' => 'Mật khẩu là bắt buộc',
                'password.min' => 'Mật khẩu phải ít nhất 6 ký tự',

                'avatar.image' => 'Avatar phải là hình ảnh',
                'avatar.max' => 'Avatar tối đa 2MB',

                'role_id.required' => 'Vai trò là bắt buộc',
                'role_id.exists' => 'Vai trò không tồn tại',

                'status.boolean' => 'Trạng thái phải là true hoặc false'
            ]
        );

        try {
            $avatarUrl = null;
            $avatarPublicId = null;

            if ($request->hasFile('avatar')) {

                $upload = CloudinaryHelper::upload(
                    $request->file('avatar'),
                    'accounts'
                );

                $avatarUrl = $upload['url'];
                $avatarPublicId = $upload['public_id'];
            }

            $account = Account::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'avatar' => $avatarUrl,
                'avatar_public_id' => $avatarPublicId,
                'role_id' => $validated['role_id'],
                'status' => $validated['status'] ?? true
            ]);

            $account->load('role');

            return response()->json([
                'success' => true,
                'message' => "Tạo tài khoản thành công",
                'data' => $account
            ], 201);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Tạo tài khoản thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
