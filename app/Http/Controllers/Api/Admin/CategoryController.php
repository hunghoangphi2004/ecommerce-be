<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\CloudinaryHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Category::query();

            if ($request->has('status')) {
                $query->where("status", $request->status);
            }

            if ($request->search) {
                $query->where("title", 'like', '%' . $request->search . '%');
            }

            $sortKey = $request->sortKey ?? "created_at";
            $sortValue = $request->sortValue ?? 'desc';

            $query->orderBy($sortKey, $sortValue);

            $limit = $request->limit ?? 4;

            $categories = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh mục sản phẩm thành công',
                'data' => $categories->items(),
                'pagination' => [
                    'currentPage' => $categories->currentPage(),
                    'perPage' => $categories->perPage(),
                    'totalItems' => $categories->total(),
                    'totalPages' => $categories->lastPage(),
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh mục sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $validated = $request->validate(
                [
                    'title' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'parent_id' => [
                        'nullable',
                        Rule::exists('categories', 'id')->whereNull('deleted_at')
                    ],
                    'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                    'position' => 'nullable|integer'
                ],
                [
                    'title.required' => 'Tiêu đề là bắt buộc',
                    'title.string' => 'Tiêu đề phải là chuỗi',
                    'title.max' => 'Tiêu đề không được vượt quá 255 ký tự',

                    'description.string' => 'Mô tả phải là chuỗi',

                    'parent_id.exists' => 'Danh mục cha không tồn tại hoặc đã bị xoá',

                    'thumbnail.image' => 'Thumbnail phải là hình ảnh',
                    'thumbnail.mimes' => 'Thumbnail phải có định dạng jpg, jpeg, png',
                    'thumbnail.max' => 'Thumbnail không được vượt quá 2MB',

                    'position.integer' => 'Position phải là số nguyên',
                    'position.min' => 'Position phải lớn hơn hoặc bằng 0'
                ]
            );

            $thumbnailUrl = null;
            $thumbnailPublicId = null;

            if ($request->hasFile('thumbnail')) {
                $upload = CloudinaryHelper::upload(
                    $request->file('thumbnail'),
                    'categories'
                );

                $thumbnailUrl = $upload['url'];
                $thumbnailPublicId = $upload['public_id'];
            }

            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $count = 1;

            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $nextPosition = (Category::max('position') ?? 0) + 1;

            $category = Category::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'thumbnail' => $thumbnailUrl,
                'thumbnail_public_id' => $thumbnailPublicId,
                'position' => $validated['position'] ?? $nextPosition
            ]);

            return response()->json([
                'success' => true,
                'message' => "Tạo danh mục thành công",
                'data' => $category,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tạo danh mục thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ], 404);
            }

            $validated = $request->validate(
                [
                    'title' => 'sometimes|string|max:255',
                    'description' => 'sometimes|nullable|string',
                    'slug' => [
                        'sometimes',
                        'string',
                        Rule::unique('categories', 'slug')->ignore($id)
                    ],
                    'parent_id' => [
                        'sometimes',
                        'nullable',
                        Rule::exists('categories', 'id')->whereNull('deleted_at'),
                        'not_in:' . $id
                    ],
                    'thumbnail' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
                    'status' => 'sometimes|boolean',
                    'position' => 'sometimes|nullable|integer'
                ],
                [
                    'title.string' => 'Tiêu đề phải là chuỗi',
                    'title.max' => 'Tiêu đề không được vượt quá 255 ký tự',

                    'parent_id.exists' => 'Danh mục cha không tồn tại hoặc đã bị xoá',
                    'parent_id.not_in' => 'Danh mục cha không được là chính nó',

                    'slug.unique' => 'Slug đã tồn tại',

                    'thumbnail.image' => 'Thumbnail phải là hình ảnh',
                    'thumbnail.mimes' => 'Thumbnail phải có định dạng jpg, jpeg, png',
                    'thumbnail.max' => 'Thumbnail không được vượt quá 2MB',

                    'status.boolean' => 'Status phải là true hoặc false',

                    'position.integer' => 'Position phải là số nguyên'
                ]
            );

            $thumbnailUrl = $category->thumbnail;
            $thumbnailPublicId = $category->thumbnail_public_id;

            if ($request->hasFile('thumbnail')) {

                if ($category->thumbnail_public_id) {
                    CloudinaryHelper::destroy($category->thumbnail_public_id);
                }

                $upload = CloudinaryHelper::upload(
                    $request->file('thumbnail'),
                    'categories'
                );

                $thumbnailUrl = $upload['url'];
                $thumbnailPublicId = $upload['public_id'];
            }

            if ($request->filled('slug')) {

                $validated['slug'] = Str::slug($request->slug);
            } elseif ($request->filled('title')) {

                $slug = Str::slug($request->title);
                $originalSlug = $slug;
                $count = 1;

                while (Category::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }

                $validated['slug'] = $slug;
            }

            $validated['thumbnail'] = $thumbnailUrl;
            $validated['thumbnail_public_id'] = $thumbnailPublicId;


            $category->update($validated);

            return response()->json([
                'success' => true,
                'message' => "Cập nhật danh mục thành công",
                'data' => $category,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật danh mục thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ], 404);
            }

            if ($category->products()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa danh mục vì vẫn còn sản phẩm'
                ], 400);
            }

            if ($category->children()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa danh mục vì vẫn còn danh mục con'
                ], 400);
            }

            //Đang softDelete nên không xoá ảnh khi xoá mềm danh mục
            // if ($category->thumbnail_public_id) {
            //     CloudinaryHelper::destroy($category->thumbnail_public_id);
            // }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa danh mục thành công'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xoá danh mục thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function changeStatus($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ], 404);
            }

            $category->update([
                'status' => !$category->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đổi trạng thái danh mục thành công'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đổi trạng thái danh mục thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
