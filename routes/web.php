<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
/*
Route::get('/', function () {
    return view('welcome');
});
*/
Route::get('/', [UserController::class, 'showMenu'])->name('home');
Route::get('/menu',[UserController::class,'showMenu'])->name('show.menu');
Route::get('/view/{id}',[ProductController::class,'view'])->name('product.view');



Route::post('/logout',[AuthController::class,'logout'])->name('logout');

  Route::middleware('guest')->controller(AuthController::class)->group(function(){
  Route::get('/register','showRegister')->name('show.register');
  Route::get('/login','showLogin')->name('show.login');
  Route::post('/register','register')->name('register');
  Route::post('/login','login')->name('login');

  });

  

Route::middleware(['auth', 'is_user'])
    ->controller(UserController::class)
    ->group(function () {
        Route::get('/userOnly', 'userOnly')->name('user.only');
        Route::get('/sellView', 'sellView')->name('user.sellView');
        Route::get('/fastSearch', 'fastSearch')->name('user.fastSearch');
        Route::get('/fastSearchGroup', 'fastSearchGroup')->name('user.fastSearchGroup');
    })
    
      ->controller(ProductController::class)
    ->group(function () {
        Route::get('/addItems', 'addItems')->name('product.addItems');
        Route::get('/editItems/{id}', 'editItems')->name('product.editItems');

        Route::post('/storeItems', 'storeItems')->name('product.storeItems');
        Route::post('/EditItems', 'EditItems')->name('product.EditItems');

        Route::put('/UpdateItems/{id}', 'UpdateItems')->name('product.UpdateItems');
        Route::put('/UpdateItemsImage/{id}', 'UpdateItemsImage')->name('product.UpdateItemsImage');

        Route::delete('/destroyItems/{id}', 'destroyItems')->name('product.destroyItems');
        
    })

     ->controller(StoreController::class)
    ->group(function () {
       
         Route::get('/setUp', 'setUp')->name('store.setUp');
         Route::post('/saveStore', 'saveStore')->name('store.saveStore');
        
    })
      ->controller(CartController::class)
    ->group(function () {
       
         Route::post('/cart/add', 'store')->name('cart.add');
         Route::get('/userCart', 'userCart')->name('cart.userCart');
         Route::get('/userCart', 'userCart')->name('cart.userCart');
         Route::post('/cart/update/{id}', 'update')->name('cart.update');
         Route::delete('/cart/delete/{id}', 'destroy')->name('cart.delete');

        
    });


Route::middleware(['auth', 'is_admin'])
    ->controller(AdminController::class)
    ->group(function () {
        Route::get('/adminMenu', 'adminMenu')->name('admin.menu');



    });
