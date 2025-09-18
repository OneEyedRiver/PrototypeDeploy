<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth; // Optional if you prefer Auth::id()
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
class ProductController extends Controller
{
  
     public function addItems()
     {

        return view("product.addItems");
        
     }

     public function storeItems(Request $request){

   $request->validate([
     'product_name'=>'required|max:175|min:2',
     'product_price' => 'required|numeric|gt:0|max:9999999999',
     'product_qty' => 'required|numeric|gt:0|max:999999', // Accepts decimals > 0
     'product_freshness'=>'required|max:20|min:2',
     'product_description'=>'max:2005',
     'product_image'=> 'required|image|mimes:jpg,jpeg,png,gif|max:2048', // max 2MB

 ]);

  if ($request->hasFile('product_image')) {
        // Get file with extension
        $file = $request->file('product_image');
        
        // Create a unique file name: product-name_timestamp.extension
        $filename = str_replace(' ', '-', strtolower($request->product_name)) . '_' . time() . '.' . $file->getClientOriginalExtension();

        // Store file in storage/app/public/product_images
        $filePath = $file->storeAs('product_images', $filename, 'public');
    }

  
     $product= Product::create([
     'product_name'=>$request->product_name,
     'product_category'=>$request->product_category,
     'product_price'=>$request->product_price,
      
     'product_unit'=>$request->product_unit,
     'product_freshness'=>$request->product_freshness,
    'product_qty'=>$request->product_qty,
     'product_image' => $filePath ?? null,
 
     'harvest_date'=>$request->harvest_date,
      'product_description'=>$request->product_description,
     'deliver_availability'=>(int) $request->deliver_availability,
     'pick_up_availability'=>(int) $request->pick_up_availability,
     'seller_id' =>Auth::id(),


       

          
          
     
     ]);

if($product){
     return redirect()->route('user.sellView') ->with('success', "The product {$product->product_name}  was added successfully.");;}

else {
        return back()->withInput()->with('error', 'Failed to store the product.');
    }

     }
     
     public function destroyItems($id)
     {
           $productChosen=Product::find($id);
    if ($productChosen) {
          if (
            $productChosen->product_image &&
            Storage::disk('public')->exists($productChosen->product_image)
        ) {
            Storage::disk('public')->delete($productChosen->product_image);
        }

        $productChosen->delete();
       return redirect()->route('user.sellView')
            ->with('success', "The Product was deleted successfully.");
    }

    return redirect()->route('user.sellView')->with('error', 'Failed to Delete.');
     }

     public function editItems($id)
     {
             $productChosen=Product::find($id);
             


             
    if ($productChosen) {
  

     return view('product.editItems', [
    'products' => $productChosen,
    ]);


    }}
    public function UpdateItems(Request $request, $id)
    {
      $request->validate([
     'product_name'=>'required|max:175|min:2',
     'product_price' => 'required|numeric|gt:0|max:9999999999',
     'product_qty' => 'required|numeric|gt:0|max:999999', // Accepts decimals > 0
     'product_freshness'=>'required|max:20|min:2',
     'product_description'=>'max:2005',
   //  'product_image'=> 'required|image|mimes:jpg,jpeg,png,gif|max:2048', // max 2MB

 ]);
 $product=Product::find($id);

 if($product){

 

    $product->update([
    'product_name'=>$request->product_name,
     'product_category'=>$request->product_category,
     'product_price'=>$request->product_price,
      
     'product_unit'=>$request->product_unit,
     'product_freshness'=>$request->product_freshness,
      'product_qty'=>$request->product_qty,
     //'product_image' => $filePath ?? null,
 
     'harvest_date'=>$request->harvest_date,
      'product_description'=>$request->product_description,

     'deliver_availability'=>(int) $request->deliver_availability,
     'pick_up_availability'=>(int) $request->pick_up_availability,
     'is_available'=>(int) $request->product_availability,

     
    

    ]);
     return redirect()->route('user.sellView') ->with('success', "The product {$product->product_name}  was updated successfully.");


 }
    return back()->withInput()->with('error', 'Failed to update the product.');  
    }


    public function UpdateItemsImage(Request $request, $id)
    {

          $request->validate([
        'product_image' => 'required|image|max:2048', // max 2MB
    ]);


    $product = Product::findOrFail($id);


    if ($product->product_image && Storage::disk('public')->exists($product->product_image)) {
        Storage::disk('public')->delete($product->product_image);
    }

 
    $path = $request->file('product_image')->store('product_images', 'public');

    
    $product->product_image = $path;
    $product->save();

    return back()->with('success', 'Product image updated successfully!');
      
    }

   public function view($id)
     {
             $productChosen=Product::find($id);
             
             
    if ($productChosen) {

     return view('product.view', [
    'products' => $productChosen,
    ]);

  }}
  


public function storeItemsApi(Request $request)
{
    try {
        // Make product_qty optional
        $request->validate([
            'product_name'        => 'required|max:175|min:2',
            'product_category'    => 'required|max:100',
            'product_price'       => 'required|numeric|gt:0|max:9999999999',
            'product_qty'         => 'nullable|numeric|gt:0|max:999999', // changed from required
            'product_freshness'   => 'required|max:20|min:2',
            'product_unit'        => 'required|in:kg,pcs',
            'product_description' => 'nullable|max:2005',
            'harvest_date'        => 'nullable|date',
            'deliver_availability'=> 'nullable|boolean',
            'pick_up_availability'=> 'nullable|boolean',
            'product_image'       => 'required|image|mimes:jpg,jpeg,png,gif|max:15000',
        ]);

        $filePath = null;

        if ($request->hasFile('product_image')) {
            $file = $request->file('product_image');

            // Normalize filename (always .jpg after compression)
            $filename = str_replace(' ', '-', strtolower($request->product_name))
                        . '_' . time() . '.jpg';

            // Initialize Intervention v3 with GD driver
            $manager = new ImageManager(Driver::class);

            // Read, resize, and compress
            $image = $manager->read($file)
                ->scale(width: 1200)   // resize to max width 1200px
                ->toJpeg(75);          // compress to JPEG, 75% quality

            // Save to storage/app/public/product_images
            $filePath = 'product_images/' . $filename;
            Storage::disk('public')->put($filePath, (string) $image);
        }

        // Create product
        $product = Product::create([
            'product_name'         => $request->product_name,
            'product_category'     => $request->product_category,
            'product_price'        => $request->product_price,
            'product_unit'         => $request->product_unit,
            'product_freshness'    => $request->product_freshness,
           'product_qty' => $request->product_qty ?? 1, // default to 1

            'product_image'        => $filePath,
            'harvest_date'         => $request->harvest_date,
            'product_description'  => $request->product_description,
            'deliver_availability' => $request->deliver_availability ?? 1,
            'pick_up_availability' => $request->pick_up_availability ?? 1,
            'seller_id'            => Auth::id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "The product {$product->product_name} was added successfully.",
            'product' => $product
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}
public function editItemsApi($id)
{
    $product = Product::find($id);

    if ($product) {
        // make sure product_image is a full URL
        if ($product->product_image) {
            $product->product_image = url('storage/' . $product->product_image);
        }

        return response()->json([
            'Status'  => 'success',
            'productSingle' => $product
        ], 200);
    }

    return response()->json([
        'Status'  => 'error',
        'productSingle' => 'Product not found'
    ], 404);
}

public function updateItemsApi(Request $request, $id)
{
    $product = Product::find($id);

    if (!$product) {
        return response()->json([
            'Status' => 'error',
            'message' => 'Product not found'
        ], 404);
    }

    // Validation (image optional here)
    $request->validate([
        'product_name' => 'required|max:175|min:2',
        'product_price' => 'required|numeric|gt:0|max:9999999999',
        'product_freshness' => 'required|max:20|min:2',
        'product_description' => 'nullable|max:2005',
        'product_image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048'
    ]);

    // If new image uploaded → delete old one & save new
    if ($request->hasFile('product_image')) {
        // delete old image
        if ($product->product_image && file_exists(public_path('storage/' . $product->product_image))) {
            unlink(public_path('storage/' . $product->product_image));
        }

        // store new image
        $filePath = $request->file('product_image')->store('products', 'public');
        $product->product_image = $filePath;
    }

    // Update other fields
    $product->product_name = $request->product_name;
    $product->product_category = $request->product_category;
    $product->product_price = $request->product_price;
    $product->product_unit = $request->product_unit;
    $product->product_freshness = $request->product_freshness;
    $product->harvest_date = $request->harvest_date;
    $product->product_description = $request->product_description;
    $product->is_available = (int) $request->product_availability;

    $product->save();

    return response()->json([
        'Status' => 'success',
        'message' => "The product {$product->product_name} was updated successfully.",
        'productSingle' => $product,
        'image_url' => $product->product_image ? asset('storage/' . $product->product_image) : null
    ], 200);
}


public function destroyItemsApi($id)
{
    $product = Product::find($id);

    if (!$product) {
        return response()->json([
            'Status' => 'error',
            'message' => 'Product not found'
        ], 404);
    }

    // delete image if exists
    if ($product->product_image && Storage::disk('public')->exists($product->product_image)) {
        Storage::disk('public')->delete($product->product_image);
    }

    // delete product
    $product->delete();

    return response()->json([
        'Status' => 'success',
        'message' => "The product was deleted successfully."
    ], 200);
}

}
