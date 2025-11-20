<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $orders = Order::where('user_id', $user->id)->get();
        return response()->json($orders);
    }
    public function show(Request $request, $id)
    {
        $user = auth()->user();

        $order = Order::with('items.product')
            ->where('user_id', $user->id)
            ->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    public function store(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
            'phone' => 'required|string'
        ]);

        $user = auth()->user();
        $cart = Cart::where('user_id', $user->id)->get();
        if ($cart->isEmpty()) return response()->json(['error' => 'Cart empty'], 400);

        $total = 0;
        foreach ($cart as $item) {
            $product = Product::find($item->product_id);
            if ($product->stock < $item->quantity) {
                return response()->json(['error' => "$product->name out of stock"], 400);
            }
            $total += $product->price * $item->quantity;
        }

        $order = Order::create([
            'user_id' => $user->id,
            'address' => $request->address,
            'phone' => $request->phone,
            'total' => $total
        ]);

        foreach ($cart as $item) {
            $product = Product::find($item->product_id);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $item->quantity,
                'price' => $product->price
            ]);
            $product->decrement('stock', $item->quantity);
        }

        Cart::where('user_id', $user->id)->delete();

        return response()->json([
            'order_number' => $order->id,
            'total' => $order->total,
            'items' => OrderItem::where('order_id', $order->id)->get()
        ], 201);
    }
}
