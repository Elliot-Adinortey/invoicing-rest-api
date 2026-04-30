<?php

namespace App\Contracts\Auth;

use App\Models\User;
use Illuminate\Http\Request;

interface AuthServiceInterface
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array;

    /**
     * @param  array{email: string, password: string}  $data
     * @return array{user: User, token: string}
     */
    public function login(array $data): array;

    public function logout(Request $request): void;
}
