<?php
require_once 'CLASSES/database_class.php';

final class UserRegistrationRequest {
    public string $email;
    public ?string $username;
    public string $fullName;
    public string $password;
    public string $confirmPassword;
    public string $role;
    public ?int $courseId;
    public ?int $yearLevel;

    public function __construct(array $data) {
        $this->email = trim((string)($data['email'] ?? ''));
        $this->username = ($data['username'] ?? '') !== '' ? trim((string)$data['username']) : null;
        $this->fullName = trim((string)($data['full_name'] ?? ''));
        $this->password = (string)($data['password'] ?? '');
        $this->confirmPassword = (string)($data['confirm_password'] ?? '');
        $this->role = trim((string)($data['role'] ?? ''));
        $this->courseId = isset($data['course_id']) && $data['course_id'] !== '' ? (int)$data['course_id'] : null;
        $this->yearLevel = isset($data['year_level']) && $data['year_level'] !== '' ? (int)$data['year_level'] : null;
    }
}

final class RegistrationValidator {
    public function validate(UserRegistrationRequest $req): array {
        $errors = [];

        if ($req->email === '' || !filter_var($req->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required.';
        }
        if ($req->fullName === '') {
            $errors['full_name'] = 'Full name is required.';
        }
        if ($req->password === '' || strlen($req->password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }
        if ($req->password !== $req->confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
        if ($req->role !== 'admin' && $req->role !== 'student') {
            $errors['role'] = 'Role must be admin or student.';
        }

        if ($req->role === 'student') {
            if ($req->courseId === null) {
                $errors['course_id'] = 'Course selection is required for students.';
            }
            if ($req->yearLevel === null || $req->yearLevel < 1 || $req->yearLevel > 5) {
                $errors['year_level'] = 'Year level must be between 1 and 5.';
            }
        }

        return $errors;
    }
}

final class UserRepository extends Database {
    public function emailExists(string $email): bool {
        $stmt = $this->executeQuery('SELECT 1 FROM Users WHERE email = ? LIMIT 1', [$email]);
        return (bool)$stmt->fetchColumn();
    }

    public function usernameExists(?string $username): bool {
        if ($username === null || $username === '') {
            return false;
        }
        $stmt = $this->executeQuery('SELECT 1 FROM Users WHERE username = ? LIMIT 1', [$username]);
        return (bool)$stmt->fetchColumn();
    }

    public function createUser(UserRegistrationRequest $req): int {
        $passwordHash = password_hash($req->password, PASSWORD_DEFAULT);
        
        $this->executeQuery(
            'INSERT INTO Users (email, username, full_name, password_hash, role, course_id, year_level) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$req->email, $req->username, $req->fullName, $passwordHash, $req->role, $req->courseId, $req->yearLevel]
        );
        return (int)$this->pdo->lastInsertId();
    }

    public function courseExists(int $courseId): bool {
        $stmt = $this->executeQuery('SELECT 1 FROM Courses WHERE course_id = ? LIMIT 1', [$courseId]);
        return (bool)$stmt->fetchColumn();
    }
}

final class RegistrationService {
    private UserRepository $users;
    private RegistrationValidator $validator;

    public function __construct(UserRepository $users, RegistrationValidator $validator) {
        $this->users = $users;
        $this->validator = $validator;
    }

    public function register(UserRegistrationRequest $req): array {
        $errors = $this->validator->validate($req);
        if ($this->users->emailExists($req->email)) {
            $errors['email'] = 'Email already in use.';
        }
        if ($this->users->usernameExists($req->username)) {
            $errors['username'] = 'Username already in use.';
        }

        if ($req->role === 'student' && $req->courseId !== null) {
            if (!$this->users->courseExists($req->courseId)) {
                $errors['course_id'] = 'Selected course does not exist.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $userId = $this->users->createUser($req);
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['form' => 'Registration failed: ' . $e->getMessage()]];
        }
    }
}

function handle_registration_combined(array $post): array {
    $repo = new UserRepository();
    $service = new RegistrationService($repo, new RegistrationValidator());
    $request = new UserRegistrationRequest($post);
    return $service->register($request);
}
?>