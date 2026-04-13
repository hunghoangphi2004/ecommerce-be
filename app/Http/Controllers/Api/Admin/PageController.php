<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\CloudinaryHelper;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Page::query();

            if ($request->search) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            $sortKey = $request->sortKey ?? 'created_at';
            $sortValue = $request->sortValue ?? 'desc';

            $query->orderBy($sortKey, $sortValue);

            $limit = $request->limit ?? 10;

            $pages = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách trang thành công',
                'data' => $pages->items(),
                'pagination' => [
                    'currentPage' => $pages->currentPage(),
                    'perPage' => $pages->perPage(),
                    'totalItems' => $pages->total(),
                    'totalPages' => $pages->lastPage(),
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách trang thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function detail($id)
    {
        try {
            $page = Page::find($id);

            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trang không tồn tại'
                ], 404);
            }

            return response()->json($page, 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy trang thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function create(Request $request)
    {
        $validated = $request->validate(
            [
                'title' => 'required|string|max:255',

                'slug' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('pages', 'slug')
                ],

                'content' => 'nullable|string',

                'thumbnail' => 'nullable|image|max:2048',
            ],
            [
                'title.required' => 'Tiêu đề là bắt buộc',
                'title.max' => 'Tiêu đề không vượt quá 255 ký tự',

                'slug.unique' => 'Slug đã tồn tại',

                'thumbnail.image' => 'Thumbnail phải là ảnh',
                'thumbnail.max' => 'Thumbnail tối đa 2MB',
            ]
        );

        try {
            $thumbnailUrl = null;
            $thumbnailPublicId = null;

            if ($request->hasFile('thumbnail')) {
                $upload = CloudinaryHelper::upload(
                    $request->file('thumbnail'),
                    'pages'
                );

                $thumbnailUrl = $upload['url'];
                $thumbnailPublicId = $upload['public_id'];
            }

            $slug = $validated['slug'] ?? Str::slug($validated['title']);
            $originalSlug = $slug;
            $count = 1;

            while (Page::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $page = Page::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'content' => $validated['content'] ?? null,
                'thumbnail' => $thumbnailUrl,
                'thumbnail_public_id' => $thumbnailPublicId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tạo trang thành công',
                'data' => $page,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tạo trang thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $page = Page::find($id);

            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trang không tồn tại'
                ], 404);
            }

            $validated = $request->validate(
                [
                    'title' => 'sometimes|string|max:255',

                    'slug' => [
                        'sometimes',
                        'string',
                        Rule::unique('pages', 'slug')->ignore($id)
                    ],

                    'content' => 'sometimes|nullable|string',

                    'thumbnail' => 'sometimes|nullable|image|max:2048',
                ],
                [
                    'slug.unique' => 'Slug đã tồn tại',
                ]
            );

            $thumbnailUrl = $page->thumbnail;
            $thumbnailPublicId = $page->thumbnail_public_id;

            if ($request->hasFile('thumbnail')) {

                if ($page->thumbnail_public_id) {
                    CloudinaryHelper::destroy($page->thumbnail_public_id);
                }

                $upload = CloudinaryHelper::upload(
                    $request->file('thumbnail'),
                    'pages'
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

                while (Page::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }

                $validated['slug'] = $slug;
            }

            $validated['thumbnail'] = $thumbnailUrl;
            $validated['thumbnail_public_id'] = $thumbnailPublicId;

            $page->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trang thành công',
                'data' => $page,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật trang thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $page = Page::find($id);

            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trang không tồn tại'
                ], 404);
            }

            if ($page->thumbnail_public_id) {
                CloudinaryHelper::destroy($page->thumbnail_public_id);
            }

            $page->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa trang thành công'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xóa trang thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}