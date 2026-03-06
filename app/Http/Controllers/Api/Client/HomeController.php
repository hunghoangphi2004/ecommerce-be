<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Error;
use Illuminate\Http\Request;
use Throwable;

class HomeController extends Controller
{
    public function index()
    {
        try {
            $latestProduct =  Product::where('status', true)->latest()->take(4)->get();
            return response()->json([
                'success' => true,
                'message' => 'Lấy dữ liệu trang chủ thành công',
                'latestProduct' => $latestProduct,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy dữ liệu trang chủ thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
