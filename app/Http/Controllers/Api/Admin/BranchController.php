<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Helpers\CloudinaryHelper;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Branch::query();

            if ($request->has('status')) {
                $query->where("status", $request->status);
            }

            if ($request->search) {
                $query->where("name", 'like', '%' . $request->search . '%');
            }

            $sortKey = $request->sortKey ?? "created_at";
            $sortValue = $request->sortValue ?? 'desc';

            $query->orderBy($sortKey, $sortValue);

            $limit = $request->limit ?? 4;

            $branches = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'message' => 'Lấy thương hiệu thành công',
                'data' => $branches->items(),
                'pagination' => [
                    'currentPage' => $branches->currentPage(),
                    'perPage' => $branches->perPage(),
                    'totalItems' => $branches->total(),
                    'totalPages' => $branches->lastPage(),
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy thuơng hiệu thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function detail($id)
    {
        try {
            $branch = Branch::where('id', $id)
                ->where('status', 1)
                ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thương hiệu không tồn tại'
                ], 404);
            }

            return response()->json($branch, 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy thương hiệu thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function create(Request $request)
    {
        try {

            $validated = $request->validate(
                [
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                ],
                [
                    'name.required' => 'Tiêu đề là bắt buộc',
                    'name.string' => 'Tiêu đề phải là chuỗi',
                    'name.max' => 'Tiêu đề không được vượt quá 255 ký tự',

                    'description.string' => 'Mô tả phải là chuỗi',
                ]
            );

            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $count = 1;

            while (Branch::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $branch = Branch::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Tạo thương hiệu thành công",
                'data' => $branch,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tạo thương hiệu thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thương hiệu không tồn tại'
                ], 404);
            }

            $validated = $request->validate(
                [
                    'name' => 'sometimes|string|max:255',
                    'description' => 'sometimes|nullable|string',
                    'slug' => [
                        'sometimes',
                        'string',
                        Rule::unique('branches', 'slug')->ignore($id)
                    ],
                    'status' => 'sometimes|boolean',
                ],
                [
                    'name.string' => 'Tiêu đề phải là chuỗi',
                    'name.max' => 'Tiêu đề không được vượt quá 255 ký tự',

                    'slug.unique' => 'Slug đã tồn tại',

                    'status.boolean' => 'Status phải là true hoặc false',
                ]
            );

            if ($request->filled('slug')) {

                $validated['slug'] = Str::slug($request->slug);
            } elseif ($request->filled('title')) {

                $slug = Str::slug($request->title);
                $originalSlug = $slug;
                $count = 1;

                while (Branch::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }

                $validated['slug'] = $slug;
            }

            $branch->update($validated);

            return response()->json([
                'success' => true,
                'message' => "Cập nhật thương hiệu thành công",
                'data' => $branch,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thương hiệu thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thương hiệu không tồn tại'
                ], 404);
            }

            if ($branch->products()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa thương hiệu vì vẫn còn sản phẩm'
                ], 400);
            }

            $branch->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa thương hiệu thành công'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xoá thương hiệu thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function changeStatus($id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thương hiệu không tồn tại'
                ], 404);
            }

            $branch->update([
                'status' => !$branch->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đổi trạng thái thương hiệu thành công'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đổi trạng thái thương hiệu thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
