<?php

namespace App\Services;

use App\Contracts\Auth\AuthServiceInterface;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService implements AuthServiceInterface
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return [
            'user' => $user,
            'token' => $user->createToken('api-token')->plainTextToken,
        ];
    }

    /**
     * @param  array{email: string, password: string}  $data
     * @return array{user: User, token: string}
     *
     * @throws HttpException
     */
    public function login(array $data): array
    {
        $user = User::query()
            ->where('email', '=', $data['email'], 'and')
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw new HttpException(401, 'Invalid login credentials.');
        }

        return [
            'user' => $user,
            'token' => $user->createToken('api-token')->plainTextToken,
        ];
    }

    public function logout(Request $request): void
    {
        /** @var PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();
    }
}
