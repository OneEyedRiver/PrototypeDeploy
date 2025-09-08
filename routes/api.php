<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OpenAIController;


// Route::post('/ask', [OpenAIController::class, 'ask']);
// Route::post('/describe-image', [OpenAIController::class, 'describeImage']);
Route::post('/upload-image', [OpenAIController::class, 'describeUploadedImage']);
Route::post('/describe-dish', [OpenAIController::class, 'describeDish']);
Route::post('/describe-dishImage', [OpenAIController::class, 'describeDishImage']);
Route::post('/describe-audio', [OpenAIController::class, 'describeUploadedAudio']);
Route::post('/describe-audioIngredients', [OpenAIController::class, 'describeAudioIngredients']);