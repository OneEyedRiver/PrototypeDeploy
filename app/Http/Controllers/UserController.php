<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
      public function showMenu(Request $request)
{

    $categories = Product::select('product_category')->distinct()->get();
    $cats = $request->get('cat_name');
    $search = $request->get('search');
    $user=Auth::user();

     if($user){
         if(!$cats && !$search){
    $products = Product::where('seller_id', '!=', Auth::id())->orderByDesc('updated_at')->get()->groupBy('product_category');
     return view('user.menu', [
        'products' => $products,
         'categories'=> $categories
    ]);
    }else{

          if($cats){
     $products = Product::where('product_category', $cats)->where('seller_id', '!=', Auth::id())->orderByDesc('updated_at')->paginate(3);
       return view('user.menu', [
        'products' => $products,
         'categories'=> $categories 
    ]);
    }elseif($search){

       $products = Product::where('product_name', 'like', '%'. $search. '%')->where('seller_id', '!=', Auth::id())->orderByDesc('updated_at')->paginate(3);
       return view('user.menu', [
        'products' => $products,
         'categories'=> $categories
    ]);

    }


    }
     }
     else if(!$user){

          if(!$cats  && !$search){
    $products = Product::all()->groupBy('product_category');

    return view('user.menu', [
        'products' => $products,
         'categories'=> $categories
    ]);
    }else{

    if($cats){
     $products = Product::where('product_category', $cats)->where('seller_id', '!=', Auth::id())->orderByDesc('updated_at')->paginate(3);
       return view('user.menu', [
        'products' => $products,
         'categories'=> $categories 
    ]);
    }elseif($search){

       $products = Product::where('product_name', 'like', '%'. $search. '%')->where('seller_id', '!=', Auth::id())->orderByDesc('updated_at')->paginate(3);
       return view('user.menu', [
        'products' => $products,
         'categories'=> $categories
    ]);

    }

    }

    }
}



    

             public function userOnly()
    {
        return view('user.only');
    }


  public function sellView(Request $request)
    {
$user = Auth::user();

if ($user && $user->is_seller) {
     $cats = $request->get('cat_name');
        $search = $request->get('search');

        if(!$cats && !$search){
        $userID=Auth::id();
    
        
        $products = Product::where('seller_id', $userID)->paginate(6);
        $categories = Product::select('product_category')->distinct()->get();

return view('user.sellView', [
    'products' => $products,
    'categories'=> $categories
]);
}

else{
$userID=Auth::id();
   if($cats){       
     
     
        
        $products = Product::where('seller_id', $userID)->
        where('product_category', $cats)->paginate(6);
        $categories = Product::select('product_category')->distinct()->get();

return view('user.sellView', [
    'products' => $products,
    'categories'=> $categories
]);


}if($search){

     $products= Product::where('product_name', 'like', '%'. $search. '%')->
     where('seller_id', $userID)->paginate(10);
     $categories = Product::select('product_category')->distinct()->get();
    return view('user.sellView',[
        'products'=>$products,
        'categories'=> $categories
    ]);

}

    
}

}else{

return view("store.setUp");

}


    }
public function fastSearch(Request $request)
{
    $search  = $request->input('search');
    $userLat = $request->input('latitude');
    $userLng = $request->input('longitude');
    $radius  = 20; // km

    if (!$search) {
        return view('user.fastSearch', ['stores' => collect(), 'search' => $search]);
    }

    // Get products by search
    $words = explode(' ', $search);
    $products = Product::query();
    foreach ($words as $word) {
        $products->orWhere('product_name', 'LIKE', "%{$word}%");
    }
    $products = $products->get();
    $grouped = $products->groupBy('seller_id');

    // Get all stores of matched sellers
    $stores = Store::whereIn('seller_id', $grouped->keys())->get();

    // ✅ If location available, filter by radius (simple PHP)
 if ($userLat && $userLng) {
    $stores = $stores->filter(function ($store) use ($userLat, $userLng, $radius) {
        $distance = $this->getDistance($userLat, $userLng, $store->latitude, $store->longitude);
        $store->distance = round($distance, 2);
        return $distance <= $radius; // only stores inside radius
    })->sortBy('distance')->values();
}
    // Attach products
    $stores->each(function ($store) use ($grouped) {
        $store->matched_products = $grouped->get($store->seller_id) ?? collect();
    });

    return view('user.fastSearch', compact('stores', 'search'));
}



public function fastSearchGroup(Request $request)
{
    $search  = $request->input('search');
    $userLat = $request->input('latitude');
    $userLng = $request->input('longitude');
    $radius  = 20; // km

    if (!$search) {
        return view('user.fastSearchGroup', ['stores' => collect(), 'search' => $search]);
    }

    // Get products by search
    $words = explode(' ', $search);
    $products = Product::query();
    foreach ($words as $word) {
        $products->orWhere('product_name', 'LIKE', "%{$word}%");
    }
    $products = $products->get();
    $grouped = $products->groupBy('seller_id');

    // Get all stores of matched sellers
    $stores = Store::whereIn('seller_id', $grouped->keys())->get([
        'id', 'store_name', 'store_address', 'latitude', 'longitude', 'seller_id'
    ]);

    // ✅ Filter by radius if location available
    if ($userLat && $userLng) {
        $stores = $stores->filter(function ($store) use ($userLat, $userLng, $radius) {
            $distance = $this->getDistance($userLat, $userLng, $store->latitude, $store->longitude);
            $store->distance = round($distance, 2);
            return $distance <= $radius;
        })->sortBy('distance')->values();
    }

    // Attach products
    $stores->each(function ($store) use ($grouped) {
        $store->matched_products = $grouped->get($store->seller_id) ?? collect();
    });

    return view('user.fastSearchGroup', compact('stores', 'search'));
}







// ✅ Helper
private function getDistance($lat1, $lon1, $lat2, $lon2)
{
    $earth = 6371; // km
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);

    $a = sin($latDelta/2) ** 2 +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDelta/2) ** 2;

    return $earth * 2 * atan2(sqrt($a), sqrt(1-$a));
}




//    public function showMenuApi(Request $request)
// {

//     $categories = Product::select('product_category')->distinct()->get();
//     $cats = $request->get('cat_name');
//     $search = $request->get('search');
//     // $user=Auth::user();

//     //  if($user){
//     //      if(!$cats && !$search){
//     // $products = Product::where('seller_id', '!=', Auth::id())->orderByDesc('updated_at')->get()->groupBy('product_category');
//     //  return view('user.menu', [
//     //     'products' => $products,
//     //      'categories'=> $categories
//     // ]);
//     // }else{

//     //       if($cats){
//     //  $products = Product::where('product_category', $cats)->where('seller_id', '!=', Auth::id())->orderByDesc('updated_at')->paginate(3);
//     //    return view('user.menu', [
//     //     'products' => $products,
//     //      'categories'=> $categories 
//     // ]);
//     // }elseif($search){

//     //    $products = Product::where('product_name', 'like', '%'. $search. '%')->where('seller_id', '!=', Auth::id())->orderByDesc('updated_at')->paginate(3);
//     //    return view('user.menu', [
//     //     'products' => $products,
//     //      'categories'=> $categories
//     // ]);

//     // }


//     // }
//     //  }
//     //  else if(!$user){

// if(!$cats && !$search){
//     $products = Product::all()->map(function ($product) {
//         // prepend full URL for product_image
//         $product->product_image = url('storage/' . $product->product_image);
//         return $product;
//     });

//     return response()->json([
//         'Status' => 'success',
//         'products' => $products,
//         'categories'=> $categories,
//         'message' => 'Find Successfully'
//     ], 200);
// }
// else{
// if ($cats) {
//     $products = Product::where('product_category', $cats)
//         ->where('seller_id', '!=', Auth::id())
//         ->orderByDesc('updated_at')
//         ->get()
//         ->map(function ($product) {
//             $product->product_image = url('storage/' . $product->product_image);
//             return $product;
//         });

//     return response()->json([
//         'Status' => 'success',
//         'products' => $products,
//         'categories' => $categories,
//         'message' => 'Find Successfully'
//     ], 200);

// } elseif ($search) {
//     $products = Product::where('product_name', 'like', '%' . $search . '%')
//         ->where('seller_id', '!=', Auth::id())
//         ->orderByDesc('updated_at')
//         ->get()
//         ->map(function ($product) {
//             $product->product_image = url('storage/' . $product->product_image);
//             return $product;
//         });

//     return response()->json([
//         'Status' => 'success',
//         'products' => $products,
//         'categories' => $categories,
//         'message' => 'Find Successfully'
//     ], 200);
// }


//     }

//     }
// // }








   public function showMenuApi(Request $request)
{

    $categories = Product::select('product_category')->distinct()->get();
    $cats = $request->get('cat_name');
    $search = $request->get('search');
   

if(!$cats && !$search){
    $products = Product::all()->map(function ($product) {
        // prepend full URL for product_image
        $product->product_image = url('storage/' . $product->product_image);
        return $product;
    });

    return response()->json([
        'Status' => 'success',
        'products' => $products,
        'categories'=> $categories,
        'message' => 'Find Successfully'
    ], 200);
}
else{
if ($cats) {
    $products = Product::where('product_category', $cats)
        ->where('seller_id', '!=', Auth::id())
        ->orderByDesc('updated_at')
        ->get()
        ->map(function ($product) {
            $product->product_image = url('storage/' . $product->product_image);
            return $product;
        });

    return response()->json([
        'Status' => 'success',
        'products' => $products,
        'categories' => $categories,
        'message' => 'Find Successfully'
    ], 200);

} elseif ($search) {
    $products = Product::where('product_name', 'like', '%' . $search . '%')
        ->where('seller_id', '!=', Auth::id())
        ->orderByDesc('updated_at')
        ->get()
        ->map(function ($product) {
            $product->product_image = url('storage/' . $product->product_image);
            return $product;
        });

    return response()->json([
        'Status' => 'success',
        'products' => $products,
        'categories' => $categories,
        'message' => 'Find Successfully'
    ], 200);
}


    }

    }


















public function sellViewApi(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'Status' => 'error',
            'message' => 'Unauthorized'
        ], 401);
    }

    // If user is NOT a seller → tell Android to redirect
    if (!$user->is_seller) {
        return response()->json([
            'Status' => 'not_seller',
            'message' => 'User is not a seller. Redirect required.'
        ], 200);
    }

    $cats = $request->get('cat_name');
    $search = $request->get('search');

    $query = Product::where('seller_id', $user->id);

    if ($cats) {
        $query->where('product_category', $cats);
    }

    if ($search) {
        $query->where('product_name', 'like', '%' . $search . '%');
    }

    $products = $query->orderByDesc('updated_at')->get()->map(function ($product) {
        // Ensure product_image is always absolute URL
        if ($product->product_image && !str_starts_with($product->product_image, 'http')) {
            $product->product_image = asset('storage/' . $product->product_image);
        }
        return $product;
    });

    $categories = Product::where('seller_id', $user->id)
        ->select('product_category')
        ->distinct()
        ->pluck('product_category');

    return response()->json([
        'Status' => 'success',
        'user_id' => $user->id,
        'products' => $products,
        'categories' => $categories,
        'message' => 'Find Successfully'
    ], 200);
}


}