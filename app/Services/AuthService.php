<?php

namespace App\Services;

use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthService
{
    protected int $refreshTokenDays = 30; // validade do refresh em dias

    public function register(array $data, Request $request): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'], // será hasheado pelo cast
        ]);

        return $this->issueTokens($user, $request);
    }

    public function login(array $data, Request $request): array
    {
        /** @var User|null $user */
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            abort(401, 'Credenciais inválidas.');
        }

        return $this->issueTokens($user, $request);
    }

    public function refresh(string $refreshTokenPlain, Request $request): array
    {
        $hashedToken = hash('sha256', $refreshTokenPlain);

        /** @var RefreshToken|null $stored */
        $stored = RefreshToken::where('token', $hashedToken)
            ->where('revoked', false)
            ->first();

        if (! $stored) {
            abort(401, 'Refresh token inválido.');
        }

        if ($stored->expires_at->isPast()) {
            abort(401, 'Refresh token expirado.');
        }

        $user = $stored->user;

        if (! $user) {
            abort(401, 'Usuário não encontrado.');
        }

        // rotaciona o refresh token antigo
        $stored->update(['revoked' => true]);

        return $this->issueTokens($user, $request);
    }

    public function logout(User $user, ?string $refreshTokenPlain = null, ?Request $request = null): void
    {
        // revoga o access token atual
        if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        // revoga o refresh token (se enviado)
        if ($refreshTokenPlain) {
            $hashedToken = hash('sha256', $refreshTokenPlain);

            RefreshToken::where('user_id', $user->id)
                ->where('token', $hashedToken)
                ->update(['revoked' => true]);
        }
    }

    protected function issueTokens(User $user, Request $request): array
    {
        $deviceName = $request->input('device_name') ?? 'mobile-app';

        // 1) Access token Sanctum
        $accessToken = $user->createToken($deviceName, ['*']);
        $accessTokenPlain = $accessToken->plainTextToken;

        $accessTokenExpiresInMinutes = config('sanctum.expiration') ?? 30;

        // 2) Refresh token
        $refreshTokenPlain = Str::random(64);
        $hashedRefreshToken = hash('sha256', $refreshTokenPlain);
        $refreshExpiresAt = Carbon::now()->addDays($this->refreshTokenDays);

        RefreshToken::create([
            'user_id'     => $user->id,
            'token'       => $hashedRefreshToken,
            'device_name' => $deviceName,
            'ip_address'  => $request->ip(),
            'user_agent'  => substr((string) $request->userAgent(), 0, 255),
            'expires_at'  => $refreshExpiresAt,
        ]);

        return [
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'tokens' => [
                'access_token'             => $accessTokenPlain,
                'access_token_expires_in'  => $accessTokenExpiresInMinutes * 60, // segundos
                'refresh_token'            => $refreshTokenPlain,
                'refresh_token_expires_in' => 60 * 60 * 24 * $this->refreshTokenDays,
            ],
        ];
    }
}
