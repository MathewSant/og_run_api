<?php

namespace App\Http\Controllers;

use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class CloudinaryController extends Controller
{
    public function __construct(
        protected CloudinaryService $cloudinary
    ) {}

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'public_id' => 'required|string',
        ]);

        $publicId = $validated['public_id'];

        $ok = $this->cloudinary->deleteImage($publicId);

        if (! $ok) {
            return response()->json([
                'message' => 'Erro ao apagar imagem no Cloudinary.',
            ], 500);
        }

        return response()->json([
            'message' => 'Foto removida com sucesso.',
        ]);
    }
}
