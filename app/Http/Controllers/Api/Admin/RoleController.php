<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $limit = $request->limit ?? 4;
            $roles = Role::with('permissions:id,name')->select('id', 'name', 'description')->paginate($limit);

            $data = collect($roles->items())->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'currentPage' => $roles->currentPage(),
                    'perPage' => $roles->perPage(),
                    'totalItems' => $roles->total(),
                    'totalPages' => $roles->lastPage(),
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách role thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function detail($id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nhóm quyền không tồn tại'
                ], 404);
            }

            return response()->json($role, 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy nhóm quyền thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function create(Request $request)
    {
        $validated = $request->validate(
            [
                'name' => 'required|string|max:255|unique:roles,name',

                'description' => 'nullable|string|',
            ],
            [
                'name.required' => 'Tên là bắt buộc',

                'name.unique' => 'Nhóm quyền đã tồn tại',
            ]
        );

        try {
            $role = Role::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Tạo nhóm quyền thành công",
                'data' => $role
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tạo nhóm quyền thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nhóm quyền không tồn tại'
                ], 404);
            }

            $validated = $request->validate(
                [
                    'name' => 'sometimes|string|max:255',

                    'description' => 'sometimes|nullable|string',
                ],
                [
                    'name.unique' => 'Nhóm quyền đã tồn tại',

                    'name.max' => 'Tiêu đề không được vượt quá 255 ký tự',
                ]
            );

            $role->update($validated);

            return response()->json([
                'success' => true,
                'message' => "Cập nhật nhóm quyền thành công",
                'data' => $role,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật nhóm quyền thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nhóm quyền không tồn tại'
                ], 404);
            }

            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa nhóm quyền thành công'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xoá nhóm quyền thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function updatePermissions(Request $request)
    {
        try {
            $data = json_decode($request->permissions, true);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => "Dữ liệu không hợp lệ"
                ]);
            }

            $grouped = collect($data)->groupBy('role_id');

            foreach ($grouped as $roleId => $items) {
                $role = Role::find($roleId);

                if (!$role) continue;

                $permissionIds = $items->pluck('permission_id');
                $role->permissions()->sync($permissionIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật phân quyền thành công'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật phân quyền',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
