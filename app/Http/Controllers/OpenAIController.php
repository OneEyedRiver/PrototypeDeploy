<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OpenAIController extends Controller
{
    
public function describeUploadedImage(Request $request)
{
    if (!$request->hasFile('image')) {
        return response()->json(['error' => 'No image uploaded'], 400);
    }

    // Convert image to base64
    $imageData = base64_encode(file_get_contents($request->file('image')->getRealPath()));

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

    $data = $response->json();
    $answer = $data['choices'][0]['message']['content'] ?? 'Unknown';

    $oneWord = strtolower(trim(explode(' ', $answer)[0]));

    return response()->json([
        'object' => $oneWord
    ]);
}


public function describeDish(Request $request)
{
    $dish = $request->input('dish'); // user text input

    if (!$dish) {
        return response()->json(['error' => 'No dish provided'], 400);
    }

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        'Content-Type'  => 'application/json',
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4o-mini',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a food assistant. Always return JSON only.'
            ],
            [
                'role' => 'user',
                'content' => "Give me the ingredients for {$dish}, and also don't include other detail like how many or type just the name of the ingredient. Respond in JSON format with two keys only:\n{\n  \"dish\": \"Name of the dish\",\n  \"ingredients\": [\"Ingredient1\", \"Ingredient2\", \"Ingredient3\"]\n}"
            ],
        ],
    ]);

    $data = $response->json();
    $answer = $data['choices'][0]['message']['content'] ?? '{}';

    // Try decode JSON
    $parsed = json_decode($answer, true);

    if (!is_array($parsed)) {
        if (preg_match('/\{.*\}/s', $answer, $match)) {
            $parsed = json_decode($match[0], true);
        }
    }

    return response()->json($parsed ?: ['dish' => $dish, 'ingredients' => []]);
}









public function describeDishImage(Request $request)
{
    if (!$request->hasFile('image')) {
        return response()->json(['error' => 'No image uploaded'], 400);
    }

    // Convert uploaded image to base64
    $imageData = base64_encode(file_get_contents($request->file('image')->getRealPath()));

    // Send to GPT-4o-mini
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        'Content-Type' => 'application/json',
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4o-mini',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a food image recognizer. Always return JSON only.'
            ],
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Identify the food in this image. Respond in JSON only with keys:\n" .
                                 "{\"dish\":\"Name of the dish\", \"ingredients\":[\"ingredient1\",\"ingredient2\",...]}. " .
                                 "Return only ingredient names (no quantities, no units, no extra text)."
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => ['url' => 'data:image/png;base64,' . $imageData]
                    ],
                ],
            ],
        ],
    ]);

    $data = $response->json();
    $answer = $data['choices'][0]['message']['content'] ?? '{}';

    // Attempt to decode JSON
    $parsed = json_decode($answer, true);

    // Fallback: extract JSON from text if needed
    if (!is_array($parsed)) {
        if (preg_match('/\{.*\}/s', $answer, $match)) {
            $parsed = json_decode($match[0], true);
        }
    }

    // Final fallback
    if (!is_array($parsed)) {
        $parsed = ['dish' => 'Unknown', 'ingredients' => []];
    }

    // Clean ingredients: remove empty, trim, lowercase, remove duplicates
    if (isset($parsed['ingredients']) && is_array($parsed['ingredients'])) {
        $parsed['ingredients'] = array_values(array_unique(array_filter(array_map('trim', $parsed['ingredients']))));
    } else {
        $parsed['ingredients'] = [];
    }

    // Ensure dish key exists
    if (!isset($parsed['dish']) || empty($parsed['dish'])) {
        $parsed['dish'] = 'Unknown';
    }

    return response()->json($parsed);
}




public function describeUploadedAudio(Request $request)
{
    if (!$request->hasFile('audio')) {
        return response()->json(['error' => 'No audio uploaded'], 400);
    }

    $audioPath = $request->file('audio')->getRealPath();

    // Step 1: Transcribe audio using Whisper
    $transcription = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
    ])->attach(
        'file', file_get_contents($audioPath), $request->file('audio')->getClientOriginalName()
    )->post('https://api.openai.com/v1/audio/transcriptions', [
        'model' => 'whisper-1',
    ]);

    if (!$transcription->successful()) {
        return response()->json(['error' => 'Transcription failed', 'details' => $transcription->json()], 500);
    }

    $recognizedText = $transcription->json()['text'];

    // Step 2: Send transcription to GPT just to get the item name
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        'Content-Type'  => 'application/json',
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4o-mini',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a chef assistant. Respond ONLY in JSON with the key "dish". "dish" is the name of the dish mentioned in the text.',
            ],
            [
                'role' => 'user',
                'content' => "Recognized speech: \"$recognizedText\". Extract only the dish name.",
            ],
        ],
    ]);

    return response()->json($response->json());
}


public function describeAudioIngredients(Request $request)
{
    if (!$request->hasFile('audio')) {
        return response()->json(['error' => 'No audio uploaded'], 400);
    }

    $audioPath = $request->file('audio')->getRealPath();

    // Step 1: Transcribe audio using Whisper
    $transcription = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
    ])->attach(
        'file', file_get_contents($audioPath), $request->file('audio')->getClientOriginalName()
    )->post('https://api.openai.com/v1/audio/transcriptions', [
        'model' => 'whisper-1',
    ]);

    if (!$transcription->successful()) {
        return response()->json(['error' => 'Transcription failed', 'details' => $transcription->json()], 500);
    }

    $recognizedText = $transcription->json()['text'];

    // Step 2: Ask GPT for dish + ingredients
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        'Content-Type'  => 'application/json',
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4o-mini',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a chef assistant. Respond ONLY in JSON with keys "dish" and "ingredients". 
                              - "dish" is the name of the dish mentioned in the text. 
                              - "ingredients" is an array of the main ingredients usually used in this dish.',
            ],
            [
                'role' => 'user',
                'content' => "Recognized speech: \"$recognizedText\". Extract the dish name and its ingredients.",
            ],
        ],
    ]);

    return response()->json($response->json());
}




}
