<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    public function fastSearchApi(Request $request)
{
    $search  = $request->input('search');
    $userLat = $request->input('latitude');
    $userLng = $request->input('longitude');
    $radius  = 20; // km

    if (!$search) {
        return response()->json(['stores' => []]);
    }

    // Split search into words
    $words = explode(' ', $search);
    $products = Product::query();
    foreach ($words as $word) {
        $products->orWhere('product_name', 'LIKE', "%{$word}%");
    }
    $products = $products->get();
    $grouped = $products->groupBy('seller_id');

    // Get stores of matched sellers
    $stores = Store::whereIn('seller_id', $grouped->keys())->get();

    // Filter by radius
    if ($userLat && $userLng) {
        $stores = $stores->filter(function ($store) use ($userLat, $userLng, $radius) {
            $distance = $this->getDistance($userLat, $userLng, $store->latitude, $store->longitude);
            $store->distance = round($distance, 2);
            return $distance <= $radius;
        })->sortBy('distance')->values();
    }

    
    $stores->each(function ($store) use ($grouped) {
    $store->matched_products = $grouped->get($store->seller_id)->map(function ($product) {
        return [
            'id' => $product->id,
            'product_name' => $product->product_name,
            'price' => $product->product_price,          // price
            'description' => $product->product_description, // description
            'image_url' => $product->product_image 
                ? asset('storage/' . $product->product_image) 
                : null, // full URL for image
        ];
    }) ?? collect();
    $store->store_address = $store->store_address ?? "-";
});




    return response()->json([
        'stores' => $stores,
        'search' => $search
    ]);
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



public function describeUploadedImage_droid(Request $request)
{
    if (!$request->hasFile('image')) {
        return response()->json(['error' => 'No image uploaded'], 400);
    }

    try {
        $image = $request->file('image');
        $imageData = base64_encode(file_get_contents($image->getRealPath()));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an image classifier. Always respond with exactly ONE word: the main object in the image.'
                ],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'What is the main object in this image? Respond with one word only.'],
                        ['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,' . $imageData]],
                    ],
                ],
            ],
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to contact OpenAI'], 500);
        }

        $data = $response->json();
        $answer = $data['choices'][0]['message']['content'] ?? 'Unknown';
        $oneWord = strtolower(trim(explode(' ', $answer)[0]));

        return response()->json([
            'object' => $oneWord
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function describeUploadedAudio_droid(Request $request)
{
    // 1️⃣ Check if audio exists in request
    if (!$request->hasFile('audio')) {
        return response()->json(['error' => 'No audio uploaded'], 400);
    }

    $audio = $request->file('audio');

    if (!$audio->isValid()) {
        return response()->json(['error' => 'Invalid audio file'], 400);
    }

    $audioPath = $audio->getRealPath();

    try {
        // 2️⃣ Transcribe audio with Whisper
        $transcription = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])
        ->attach(
            'file',
            file_get_contents($audioPath),
            $audio->getClientOriginalName()
        )
        ->post('https://api.openai.com/v1/audio/transcriptions', [
            'model' => 'whisper-1',
        ]);

        if (!$transcription->successful()) {
            return response()->json([
                'error' => 'Transcription failed',
                'details' => $transcription->json()
            ], 500);
        }

        $recognizedText = $transcription->json()['text'] ?? '';

        if (empty($recognizedText)) {
            return response()->json(['error' => 'No text recognized from audio'], 400);
        }

        // 3️⃣ Send text to GPT to extract dish name (or description)
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a chef assistant. Respond ONLY in JSON format like {"dish": "Adobo"} — where "dish" is the name of the dish mentioned in the audio.',
                ],
                [
                    'role' => 'user',
                    'content' => "Recognized speech: \"$recognizedText\". Extract only the dish name.",
                ],
            ],
        ]);

        if (!$response->successful()) {
            return response()->json([
                'error' => 'GPT analysis failed',
                'details' => $response->json()
            ], 500);
        }

        // 4️⃣ Return JSON result
        return response()->json([
            'transcription' => $recognizedText,
            'result' => $response->json(),
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Server error', 'details' => $e->getMessage()], 500);
    }
}

    
}
