<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
  public function store(Request $request)
{
    $cartItem = Cart::where('user_id', Auth::id())
        ->where('product_id', $request->product_id)
        ->where('store_id', $request->store_id)
        ->first();

    if ($cartItem) {
        // Product already exists → increase quantity
        $cartItem->quantity += $request->quantity ?? 1;
        $cartItem->save();
    } else {
        // Add new row
        $cartItem = Cart::create([
            'user_id' => Auth::id(),
            'product_id' => $request->product_id,
            'store_id' => $request->store_id,
            'product_name' => $request->product_name,
            'product_price' => $request->product_price,
            'product_unit' => $request->product_unit,
            'product_description' => $request->product_description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'quantity' => $request->quantity ?? 1,
        ]);
    }

    return response()->json(['success' => true, 'cart' => $cartItem]);
}

public function userCart(Request $request)
{
    $cats = $request->get('cat_name');
    $search = $request->get('search');

    $userID = Auth::id();

    $query = Cart::with('product')->where('user_id', $userID);

    // (Optional) filter by category
    // if ($cats) {
    //     $query->whereHas('product', function ($q) use ($cats) {
    //         $q->where('product_category', $cats);
    //     });
    // }

    // // (Optional) search inside product name
    // if ($search) {
    //     $query->whereHas('product', function ($q) use ($search) {
    //         $q->where('product_name', 'like', "%{$search}%");
    //     });
    // }

    $products = $query->paginate(6);

    return view('cart.userCart', [
        'products' => $products,
    ]);
}



public function update(Request $request, $id)
{
    $cartItem = Cart::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

    $cartItem->quantity = $request->quantity;
    $cartItem->save();

    return response()->json([
        'success' => true,
        'quantity' => $cartItem->quantity,
        'total' => $cartItem->quantity * $cartItem->product_price
    ]);
}

public function destroy($id)
{
    $cartItem = Cart::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
    $cartItem->delete();

    return response()->json(['success' => true]);
}


}