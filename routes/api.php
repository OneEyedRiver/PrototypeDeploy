<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;

// Route::post('/ask', [OpenAIController::class, 'ask']);
// Route::post('/describe-image', [OpenAIController::class, 'describeImage']);
Route::post('/upload-image', [OpenAIController::class, 'describeUploadedImage']);
Route::post('/describe-dish', [OpenAIController::class, 'describeDish']);
Route::post('/describe-dishImage', [OpenAIController::class, 'describeDishImage']);
Route::post('/describe-audio', [OpenAIController::class, 'describeUploadedAudio']);
Route::post('/describe-audioIngredients', [OpenAIController::class, 'describeAudioIngredients']);



Route::post('/registerApi', [AuthController::class, 'registerApi']);
Route::post('/loginApi', [AuthController::class, 'loginApi']);

Route::get('/showMenu', [UserController::class, 'showMenuApi']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/list', function () {
        return \App\Models\User::all();
    });

    Route::get('/sellViewApi', [UserController::class, 'sellViewApi']);
    Route::post('/products', [ProductController::class, 'storeItemsApi']);
    Route::get('/getProduct/{id}', [ProductController::class, 'editItemsApi']);
    Route::post('/updateProduct/{id}', [ProductController::class, 'updateItemsApi']);
    Route::delete('/deleteProduct/{id}', [ProductController::class, 'destroyItemsApi']);
    Route::post('/saveStore', [StoreController::class, 'saveStoreApi']);
    Route::post('/fastSearchApi', [ApiController::class, 'fastSearchApi']);
    Route::post('/describeUploadedImage_droid', [ApiController::class, 'describeUploadedImage_droid']);

});
