<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class FirebaseAccessTokenService
{
    /**
     * Retrieve OAuth2 Access Token using Firebase Service Account Credentials.
     */
    public static function getAccessToken(): string
    {
        $filePath = storage_path('app/firebase-service-account.json');
        if (!file_exists($filePath)) {
            throw new Exception("Firebase service account JSON file not found at storage/app/firebase-service-account.json");
        }

        $serviceAccount = json_decode(file_get_contents($filePath), true);
        if (!$serviceAccount) {
            throw new Exception("Invalid service account JSON file.");
        }

        $privateKey = $serviceAccount['private_key'];
        $clientEmail = $serviceAccount['client_email'];

        $now = time();
        $payload = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $base64UrlHeader = self::base64UrlEncode(json_encode($header));
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;
        $signature = '';

        if (!openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception("Failed to sign JWT with private key.");
        }

        $base64UrlSignature = self::base64UrlEncode($signature);
        $jwt = $signatureInput . "." . $base64UrlSignature;

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed()) {
            throw new Exception("Google OAuth2 request failed: " . $response->body());
        }

        return $response->json()['access_token'];
    }

    /**
     * Base64Url Encode Helper
     */
    private static function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
