<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Error;
use GuzzleHttp\Handler\Proxy;
use Illuminate\Http\Request;
use Throwable;

class HomeController extends Controller
{
    public function index()
    {
        try {
            $latestProduct = Product::with('branch:id,name,slug')
                ->where('status', true)
                ->latest()
                ->take(4)
                ->get();

            $featuredProduct = Product::with('branch:id,name,slug')
                ->where([
                    ['status', true],
                    ['is_featured', 1]
                ])
                ->take(4)
                ->get();
            return response()->json([
                'success' => true,
                'message' => 'Lấy dữ liệu trang chủ thành công',
                'latestProduct' => $latestProduct,
                'featuredProduct' => $featuredProduct
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy dữ liệu trang chủ thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // public function getAllCategory()
    // {
    //     try {
    //         $categories = Category::where('status', true)->select('id', 'title', 'slug', 'description', 'thumbnail', 'parent_id')->get();
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Lấy dữ liệu danh mục thành công',
    //             'categories' => $categories,
    //         ], 200);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Lấy dữ liệu trang chủ thất bại',
    //             'error' => config('app.debug') ? $e->getMessage() : null
    //         ], 500);
    //     }
    // }

    public function getAllBranch(Request $request)
    {
        try {
            $branches = Branch::where('status', true)->select('id', 'name', 'description', 'slug')->get();
            return response()->json([
                'success' => true,
                'message' => 'Lấy dữ liệu thuơng hiệu thành công',
                'branches' => $branches,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getBranch($slug)
    {
        try {
            $branch = Branch::where('slug', $slug)
                ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thương hiệu không tồn tại'
                ], 404);
            }

            $products = Product::with('branch:id,name,slug')->where('branch_id', $branch->id)->where('status', true)->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy dữ liệu danh mục thành công',
                'branch' => $branch,
                'products' => $products
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy dữ liệu thương hiệu thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getAllCategory(Request $request)
    {
        try {
            if ($request->filled('slug')) {
                $category = Category::where('slug', $request->slug)
                    ->where('status', true)
                    ->with(['childrenRecursive' => function ($query) {
                        $query->where('status', true)
                            ->select('id', 'title', 'slug', 'description', 'thumbnail', 'parent_id');
                    }])
                    ->first();

                if (!$category) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy danh mục'
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'category' => $category
                ], 200);
            }

            $categories = Category::whereNull('parent_id')
                ->where('status', true)
                ->with(['childrenRecursive' => function ($query) {
                    $query->where('status', true)
                        ->select('id', 'title', 'slug', 'description', 'thumbnail', 'parent_id');
                }])
                ->select('id', 'title', 'slug', 'description', 'thumbnail', 'parent_id')
                ->get();

            return response()->json([
                'success' => true,
                'categories' => $categories
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function getAllCategoryIds($category)
    {
        $ids = [$category->id];

        foreach ($category->childrenRecursive as $child) {
            $ids = array_merge($ids, $this->getAllCategoryIds($child));
        }

        return $ids;
    }

    public function getCategory($slug)
    {
        try {
            $category = Category::where('slug', $slug)
                ->with('childrenRecursive')
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ], 404);
            }

            $categoryIds = $this->getAllCategoryIds($category);

            $products = Product::with('branch:id,name,slug')->whereIn('category_id', $categoryIds)->where('status', true)->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy dữ liệu danh mục thành công',
                'category' => $category,
                'products' => $products
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy dữ liệu danh mục thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getProduct($slug)
    {
        try {
            $product = Product::where('slug', $slug)->where('status', true)
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy dữ liệu sản phẩm thành công',
                'product' => $product
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy dữ liệu sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
