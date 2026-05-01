<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

const API_REGISTER = '/api/v1/register';
const API_LOGIN = '/api/v1/login';
const API_LOGOUT = '/api/v1/logout';

// ─── Register ────────────────────────────────────────────────────────────────

describe('register', function () {
    /** @var TestCase $this */
    it('registers a new user and returns a token', function () {
        $response = $this->postJson(API_REGISTER, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'status',
                'status_code',
                'data' => ['user', 'token'],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully.',
                'status' => 'success',
                'status_code' => 201,
            ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    });

    it('fails when required fields are missing', function () {
        $this->postJson(API_REGISTER, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('fails when email is already taken', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson(API_REGISTER, [
            'name' => 'Jane Doe',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when passwords do not match', function () {
        $this->postJson(API_REGISTER, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('fails when password is too short', function () {
        $this->postJson(API_REGISTER, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });
});

// ─── Login ────────────────────────────────────────────────────────────────────

describe('login', function () {
    /** @var TestCase $this */
    it('logs in a user with correct credentials and returns a token', function () {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson(API_LOGIN, [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'status',
                'status_code',
                'data' => ['user', 'token'],
            ])
            ->assertJson([
                'message' => 'Login successful.',
                'status' => 'success',
                'status_code' => 200,
            ]);
    });

    it('fails with invalid credentials', function () {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->postJson(API_LOGIN, [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJson([
                'success' => false,
                'status_code' => 401,
                'message' => 'Invalid login credentials.',
            ]);
    });

    it('fails when email does not exist', function () {
        $this->postJson(API_LOGIN, [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ])->assertStatus(401)
            ->assertJson([
                'success' => false,
                'status_code' => 401,
                'message' => 'Invalid login credentials.',
            ]);
    });

    it('fails when required fields are missing', function () {
        $this->postJson(API_LOGIN, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    });
});

// ─── Logout ───────────────────────────────────────────────────────────────────

describe('logout', function () {
    /** @var TestCase $this */
    it('logs out an authenticated user and deletes the token from the database', function () {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('api-token');
        $plainTextToken = $tokenResult->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$plainTextToken}")
            ->postJson(API_LOGOUT)
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful.',
                'status' => 'success',
                'status_code' => 200,
            ]);

        // Token record must be deleted from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenResult->accessToken->id,
        ]);
    });

    it('returns 401 when no token is provided', function () {
        $this->postJson(API_LOGOUT)->assertStatus(401);
    });
});
