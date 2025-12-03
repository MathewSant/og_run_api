<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    /**
     * Deleta uma imagem no Cloudinary pelo public_id.
     *
     * Retorna true se deu tudo certo, false se deu erro.
     */
    public function deleteImage(?string $publicId): bool
    {
        if (! $publicId) {
            return false;
        }

        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey    = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            Log::error('Cloudinary não configurado corretamente ao tentar deletar imagem.', [
                'public_id' => $publicId,
            ]);

            return false;
        }

        $timestamp = time();

        // stringToSign: params em ordem alfabética, sem api_key e sem signature
        $stringToSign = "public_id={$publicId}&timestamp={$timestamp}";
        $signature    = sha1($stringToSign . $apiSecret);

        $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy";

        $response = Http::asForm()->post($url, [
            'public_id' => $publicId,
            'timestamp' => $timestamp,
            'api_key'   => $apiKey,
            'signature' => $signature,
        ]);

        if ($response->failed()) {
            Log::error('Erro ao apagar imagem no Cloudinary', [
                'status'    => $response->status(),
                'body'      => $response->body(),
                'public_id' => $publicId,
            ]);

            return false;
        }

        return true;
    }
}
