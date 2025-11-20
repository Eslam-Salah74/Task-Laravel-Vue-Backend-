<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $carts = Cart::with('product')->where('user_id', $request->user()->id)->get();
        return response()->json($carts);
    }

    // public function store(Request $request)
    // {
    //     $product = Product::find($request->product_id);

    //     if (!$product) {
    //         return response()->json(['error' => 'Product not found'], 404);
    //     }

    //     if ($product->stock <= 0) {
    //         return response()->json(['error' => 'Product out of stock'], 400);
    //     }

    //     $cart = Cart::updateOrCreate(
    //         [
    //             'user_id' => $request->user()->id,
    //             'product_id' => $request->product_id
    //         ],
    //         [
    //             'quantity' => $request->quantity ?? 1
    //         ]
    //     );
    //     return response()->json($cart);
    // }

    public function store(Request $request)
    {
        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        if ($product->stock <= 0) {
            return response()->json(['error' => 'Product out of stock'], 400);
        }

        // تحقق إذا المنتج موجود مسبقًا في الكارت
        $existingCart = Cart::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingCart) {
            return response()->json(['message' => 'Product already added to cart'], 200);
        }

        // إضافة المنتج للكارت
        $cart = Cart::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity ?? 1
        ]);

        return response()->json($cart);
    }


    public function update(Request $request, $id)
    {
        $cart = Cart::findOrFail($id);
        $cart->update($request->only('quantity'));
        return response()->json($cart);
    }

    public function destroy($id)
    {
        $cart = Cart::findOrFail($id);
        $cart->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
