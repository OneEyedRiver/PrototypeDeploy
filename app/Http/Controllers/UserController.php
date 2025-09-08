<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
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
    $search = $request->input('search');

    if (!$search) {
        $stores = collect(); // empty collection
    } else {
     $words = explode(' ', $search); // ['whole', 'chicken']

    $products = Product::query();

    foreach ($words as $word) {
        $products->orWhere('product_name', 'LIKE', "%{$word}%");
    }

    $products = $products->get();

        // Group them by seller_id so we know which store sells which product
        $grouped = $products->groupBy('seller_id');

        // Get stores for those sellers
        $stores = Store::whereIn('seller_id', $grouped->keys())->get([
            'id', 'store_name', 'store_address', 'latitude', 'longitude', 'seller_id'
        ]);

        // Attach only the matched products to each store
        $stores->each(function ($store) use ($grouped) {
            $store->matched_products = $grouped->get($store->seller_id) ?? collect();
        });
    }

    return view('user.fastSearch', compact('stores', 'search'));

 
   // dd($stores);
}






public function fastSearchGroup(Request $request)
{
    $search = $request->input('search');
    
    if (!$search) {
        $stores = collect(); // empty collection
    } else {
        $words = explode(' ', $search); // ['whole', 'chicken']

    $products = Product::query();

    foreach ($words as $word) {
        $products->orWhere('product_name', 'LIKE', "%{$word}%");
    }

    $products = $products->get();

        // Group them by seller_id so we know which store sells which product
        $grouped = $products->groupBy('seller_id');

        // Get stores for those sellers
        $stores = Store::whereIn('seller_id', $grouped->keys())->get([
            'id', 'store_name', 'store_address', 'latitude', 'longitude', 'seller_id'
        ]);

        // Attach only the matched products to each store
        $stores->each(function ($store) use ($grouped) {
            $store->matched_products = $grouped->get($store->seller_id) ?? collect();
        });
    }

    return view('user.fastSearchGroup', compact('stores', 'search'));

 
   // dd($stores);
}

}

