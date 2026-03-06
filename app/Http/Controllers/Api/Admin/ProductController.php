<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\CloudinaryHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use GuzzleHttp\Handler\Proxy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::query();

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

            $products = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'message' => 'Lấy sản phẩm thành công',
                'data' => $products->items(),
                'pagination' => [
                    'currentPage' => $products->currentPage(),
                    'perPage' => $products->perPage(),
                    'totalItems' => $products->total(),
                    'totalPages' => $products->lastPage(),
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $validated = $request->validate(
                [
                    'category_id' => [
                        'required',
                        Rule::exists('categories', 'id')->whereNull('deleted_at')
                    ],
                    'title' => 'required|string|max:255',

                    'description' => 'nullable|string',
                    'price' => 'required|numeric|min:0',
                    'discount_percentage' => 'nullable|numeric|min:0|max:100',
                    'stock' => 'required|integer|min:0',
                    'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                    'is_featured' => 'boolean',
                    'position' => 'nullable|integer'
                ],
                [
                    'category_id.required' => 'Danh mục là bắt buộc',
                    'category_id.exists' => 'Danh mục không tồn tại hoặc đã bị xóa',

                    'title.required' => 'Tiêu đề là bắt buộc',
                    'title.max' => 'Tiêu đề không được vượt quá 255 ký tự',

                    'price.required' => 'Giá là bắt buộc',
                    'price.numeric' => 'Giá phải là số',
                    'price.min' => "Giá phải lớn hơn 0",

                    'stock.required' => 'Số lượng là bắt buộc',
                    'stock.integer' => 'Số lượng phải là số nguyên',
                    'stock.min' => "Số lượng phải lớn hơn 0",
                ]
            );

            $thumbnailUrl = null;
            $thumbnailPublicId = null;

            if ($request->hasFile('thumbnail')) {
                $upload = CloudinaryHelper::upload(
                    $request->file('thumbnail'),
                    'products'
                );

                $thumbnailUrl = $upload['url'];
                $thumbnailPublicId = $upload['public_id'];
            }

            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $count = 1;

            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $nextPosition = Product::max('position') + 1;

            $product = Product::create([
                'category_id' => $validated['category_id'],
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'discount_percentage' => $validated['discount_percentage']  ?? 0,
                'stock' => $validated['stock'],
                'thumbnail' => $thumbnailUrl,
                'thumbnail_public_id' => $thumbnailPublicId,
                'position' => $validated['position'] ?? $nextPosition
            ]);

            return response()->json([
                'success' => true,
                'message' => "Tạo sản phẩm thành công",
                'data' => $product,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tạo sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ], 404);
            }

            $validated = $request->validate(
                [
                    'category_id' => [
                        'sometimes',
                        Rule::exists('categories', 'id')->whereNull('deleted_at')
                    ],

                    'title' => 'sometimes|string|max:255',

                    'slug' => [
                        'sometimes',
                        'string',
                        Rule::unique('products', 'slug')->ignore($id)
                    ],

                    'description' => 'sometimes|nullable|string',

                    'price' => 'sometimes|numeric|min:0',

                    'discount_percentage' => 'sometimes|numeric|min:0|max:100',

                    'stock' => 'sometimes|integer|min:0',

                    'thumbnail' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',

                    'status' => 'sometimes|boolean',

                    'is_featured' => 'sometimes|boolean',

                    'position' => 'sometimes|integer'
                ],
                [
                    'category_id.exists' => 'Danh mục không tồn tại hoặc đã bị xóa',

                    'title.max' => 'Tiêu đề không được vượt quá 255 ký tự',

                    'price.numeric' => 'Giá phải là số',
                    'price.min' => 'Giá phải lớn hơn hoặc bằng 0',

                    'stock.integer' => 'Số lượng phải là số nguyên',
                    'stock.min' => 'Số lượng phải lớn hơn hoặc bằng 0',

                    'slug.unique' => 'Slug đã tồn tại',

                    'status.boolean' => 'Trạng thái phải là true hoặc false',

                    'is_featured.boolean' => 'Sản phẩm nổi bật phải là true hoặc false',

                    'position.integer' => 'Vị trí phải là số nguyên',
                ]
            );

            $thumbnailUrl = $product->thumbnail;
            $thumbnailPublicId = $product->thumbnail_public_id;

            if ($request->hasFile('thumbnail')) {

                if ($product->thumbnail_public_id) {
                    CloudinaryHelper::destroy($product->thumbnail_public_id);
                }

                $upload = CloudinaryHelper::upload(
                    $request->file('thumbnail'),
                    'products'
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

                while (Product::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }

                $validated['slug'] = $slug;
            }

            $validated['thumbnail'] = $thumbnailUrl;
            $validated['thumbnail_public_id'] = $thumbnailPublicId;

            $product->update($validated);

            return response()->json([
                'success' => true,
                'message' => "Cập nhật sản phẩm thành công",
                'data' => $product,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ], 404);
            }


            //Đang softDelete nên không xoá ảnh khi xoá mềm danh mục
            // if ($product->thumbnail_public_id) {
            //     CloudinaryHelper::destroy($product->thumbnail_public_id);
            // }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm thành công'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xoá sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function changeStatus($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ], 404);
            }

            $product->update([
                'status' => !$product->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đổi trạng thái sản phẩm thành công'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đổi trạng thái sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
