<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\JWT;
use App\Core\Database;
use App\Models\User;
use App\Middleware\RateLimitMiddleware;

class AuthController extends Controller {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register(Request $request): void {
        RateLimitMiddleware::handle($request, 'register');

        $data = $this->validate($request->all(), [
            'name' => 'required|min:2|max:100',
            'phone' => 'required|phone|unique:users,phone',
            'email' => 'email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        $email = !empty($data['email']) ? strtolower(trim($data['email'])) : null;

        $userId = $this->userModel->create([
            'name' => trim($data['name']),
            'phone' => trim($data['phone']),
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => 'customer',
            'status' => 'active',
            'address' => $data['address'] ?? null
        ]);

        $user = $this->userModel->find($userId);
        unset($user['password_hash']);

        // Generate JWT
        $token = JWT::encode([
            'sub' => $user['id'],
            'role' => $user['role'],
            'name' => $user['name']
        ]);

        $this->logAudit($userId, 'customer', 'REGISTER', 'users', (string)$userId, null, json_encode(['phone' => $user['phone']]));

        $this->success([
            'user' => $user,
            'token' => $token
        ], 'Registration successful! Welcome to Rome & Ride.', 201);
    }

    public function login(Request $request): void {
        RateLimitMiddleware::handle($request, 'login');

        $data = $this->validate($request->all(), [
            'login' => 'required',
            'password' => 'required',
        ]);

        $identifier = trim($data['login']);
        $user = $this->userModel->findByLoginIdentifier($identifier);

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            $this->error('Invalid credentials. Please check your phone/email and password.', null, 401);
        }

        if ($user['status'] !== 'active') {
            $this->error('Your account is currently inactive or suspended. Please contact support.', null, 403);
        }

        unset($user['password_hash']);

        $token = JWT::encode([
            'sub' => $user['id'],
            'role' => $user['role'],
            'name' => $user['name']
        ]);

        // Set cookie as well for seamless document views
        setcookie('rnr_token', $token, [
            'expires' => time() + 86400 * 7,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax'
        ]);

        $this->logAudit($user['id'], $user['role'], 'LOGIN', 'users', (string)$user['id']);

        $this->success([
            'user' => $user,
            'token' => $token
        ], 'Login successful! Welcome back.');
    }

    public function me(Request $request): void {
        $user = $request->getUser();
        if (!$user) {
            $this->error('Unauthenticated', null, 401);
        }
        $this->success($user);
    }

    public function updateProfile(Request $request): void {
        $currentUser = $request->getUser();
        $data = $this->validate($request->all(), [
            'name' => 'required|min:2|max:100',
            'phone' => "required|phone|unique:users,phone,{$currentUser['id']}",
            'email' => "email|unique:users,email,{$currentUser['id']}",
        ]);

        $updateData = [
            'name' => trim($data['name']),
            'phone' => trim($data['phone']),
            'email' => !empty($data['email']) ? strtolower(trim($data['email'])) : null,
            'address' => $data['address'] ?? null,
        ];

        if (!empty($request->get('password'))) {
            if (strlen($request->get('password')) < 6) {
                $this->error('Password must be at least 6 characters.');
            }
            $updateData['password_hash'] = password_hash($request->get('password'), PASSWORD_BCRYPT);
        }

        $this->userModel->update($currentUser['id'], $updateData);
        $user = $this->userModel->find($currentUser['id']);
        unset($user['password_hash']);

        $this->logAudit($user['id'], $user['role'], 'UPDATE_PROFILE', 'users', (string)$user['id']);

        $this->success($user, 'Profile updated successfully.');
    }

    public function logout(Request $request): void {
        setcookie('rnr_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax'
        ]);
        $this->success(null, 'Logged out successfully.');
    }
}
