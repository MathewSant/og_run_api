<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryController extends Controller
{
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'public_id' => 'required|string',
        ]);

        $publicId = $validated['public_id'];

        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey    = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            return response()->json([
                'message' => 'Cloudinary não está configurado corretamente.',
            ], 500);
        }

        $timestamp = time();

        // stringToSign: params em ordem alfabética, sem api_key e sem signature
        $stringToSign = "public_id={$publicId}&timestamp={$timestamp}";

        // assinatura SHA1
        $signature = sha1($stringToSign . $apiSecret);

        $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy";

        $response = Http::asForm()->post($url, [
            'public_id' => $publicId,
            'timestamp' => $timestamp,
            'api_key'   => $apiKey,
            'signature' => $signature,
        ]);

        if ($response->failed()) {
            Log::error('Erro ao apagar imagem no Cloudinary', [
                'status'   => $response->status(),
                'body'     => $response->body(),
                'publicId' => $publicId,
            ]);

            return response()->json([
                'message' => 'Erro ao apagar imagem no Cloudinary.',
            ], 500);
        }

        return response()->json([
            'message'    => 'Foto removida com sucesso.',
            'cloudinary' => $response->json(), // opcional, pra debug
        ]);
    }
}
