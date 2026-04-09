<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        db::beginTransaction();
        try {
            $user = Auth::guard('api_user')->user();

            $request->validate([
                'name' => 'required|string',
                'phone' => 'required|string',
                'address' => 'required|string',
                'items' => 'required|array|min:1'
            ]);

            if ($user) {
                $cart = Cart::with('items')->where('user_id', $user->id)->first();

                if (!$cart || $cart->items->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cart is empty'
                    ], 400);
                }

                $items = $cart->items;
            } else {
                $items = collect($request->items);

                if ($items->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cart is empty'
                    ], 400);
                }
            }

            $order = Order::create([
                'user_id' => $user?->id,
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'total_price' => 0
            ]);

            $total = 0;

            foreach ($items as $item) {

                $productId = is_array($item) ? $item['product_id'] : $item->product_id;
                $quantity = is_array($item) ? $item['quantity'] : $item->quantity;

                $product = Product::where('id', $productId)
                    ->where('status', true)
                    ->first();

                if (!$product) {
                    throw new \Exception("Product ID {$productId} không hợp lệ hoặc không hoạt động");
                }

                if ($product->discount_percentage > 0) {
                    $price = $product->price * (1 - $product->discount_percentage / 100);
                } else {
                    $price = $product->price;
                }

                $subtotal = $price * $quantity;
                $total += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'price_new' => $price
                ]);
            }

            $order->update([
                'total_price' => $total
            ]);

            if ($user) {
                $cart->items()->delete();
            }

            db::commit();

            return response()->json([
                'success' => true,
                'order' => $order
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $error) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
            ], 500);
        }
    }
}
