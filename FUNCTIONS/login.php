<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'CLASSES/database_class.php';

final class LoginRequest {
    public string $emailOrUsername;
    public string $password;

    public function __construct(array $data) {
        $this->emailOrUsername = trim((string)($data['email_or_username'] ?? ''));
        $this->password = (string)($data['password'] ?? '');
    }
}

final class LoginValidator {
    public function validate(LoginRequest $req): array {
        $errors = [];
        if ($req->emailOrUsername === '') {
            $errors['email_or_username'] = 'Email or Username is required.';
        }
        if ($req->password === '') {
            $errors['password'] = 'Password is required.';
        }
        return $errors;
    }
}

final class AuthRepository extends Database {
    public function findUserByEmailOrUsername(string $identifier): ?array {
        $stmt = $this->executeQuery('SELECT * FROM Users WHERE email = ? OR username = ? LIMIT 1', [$identifier, $identifier]);
        $user = $stmt->fetch();
        return $user !== false ? $user : null;
    }
}

final class AuthService {
    private AuthRepository $repo;
    private LoginValidator $validator;

    public function __construct(AuthRepository $repo, LoginValidator $validator) {
        $this->repo = $repo;
        $this->validator = $validator;
    }

    public function login(LoginRequest $req): array {
        $errors = $this->validator->validate($req);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $user = $this->repo->findUserByEmailOrUsername($req->emailOrUsername);
        if ($user === null) {
            return ['success' => false, 'errors' => ['email_or_username' => 'User not found.']];
        }
        if (!password_verify($req->password, $user['password_hash'])) {
            return ['success' => false, 'errors' => ['password' => 'Incorrect password.']];
        }

        $_SESSION['user'] = [
            'user_id' => (int)$user['user_id'],
            'email' => $user['email'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
        ];

        return ['success' => true, 'user' => $_SESSION['user']];
    }
}

function handle_login(array $post): array {
    $repo = new AuthRepository();
    $service = new AuthService($repo, new LoginValidator());
    $request = new LoginRequest($post);
    return $service->login($request);
}