<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Favourite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavouriteController extends Controller
{
    /** GET /api/favourites */
    public function index(Request $request): JsonResponse
    {
        $favourites = $request->user()->favourites()->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $favourites]);
    }

    /** POST /api/favourites */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|string',
            'service_id' => 'nullable|string',
            'service_name' => 'nullable|string',
            'counterparty_name' => 'required|string',
            'description' => 'nullable|string',
            'amount_kobo' => 'nullable|integer|min:0',
            'transaction_reference' => 'nullable|string',
        ]);

        $favourite = $request->user()->favourites()->create($data);

        return response()->json(['message' => 'Favourite saved successfully.', 'data' => $favourite], 201);
    }

    /** DELETE /api/favourites/{id} */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $favourite = $request->user()->favourites()->findOrFail($id);
        $favourite->delete();

        return response()->json(['message' => 'Favourite removed.']);
    }
}
