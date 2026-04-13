<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Page;

class PageController extends Controller
{
    public function getPage($slug)
    {
        try {
            $page = Page::where('slug', $slug)->first();

            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trang không tồn tại'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy trang thành công',
                'data' => $page
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy trang thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
