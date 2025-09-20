<?php
require_once 'FUNCTIONS/login.php';

$errors = [];
$loginSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $result = handle_login($_POST);
        if ($result['success'] === true) {
            $loginSuccess = true;
            $role = $result['user']['role'];
            if ($role === 'admin') {
                header('Location: index.php');
                exit;
            }
            if ($role === 'student') {
                header('Location: index.php');
                exit;
            }
        } else {
            $errors = $result['errors'] ?? ['form' => 'Login failed.'];
        }
    } catch (Throwable $e) {
        $errors['form'] = 'Unexpected error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="auth-wrapper">
    <div class="form-container">
        <form method="post" action="">
            <h2>Log In</h2>
            <?php if (!empty($errors['form'])) : ?>
                <div class="message message-error"><?php echo htmlspecialchars($errors['form']); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="email_or_username">Email or Username</label>
                <input type="text" id="email_or_username" name="email_or_username" value="<?php echo htmlspecialchars($_POST['email_or_username'] ?? ''); ?>" required>
                <?php if (!empty($errors['email_or_username'])) : ?><div class="message message-error"><?php echo htmlspecialchars($errors['email_or_username']); ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <?php if (!empty($errors['password'])) : ?><div class="message message-error"><?php echo htmlspecialchars($errors['password']); ?></div><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Login</button>

            <div class="text-center mt-4" style="font-size: 14px;">
                No account yet? <a href="register.php" style="color: #3b82f6; text-decoration: none;">Create one</a>
            </div>
        </form>
    </div>
    </div>
</body>
</html>
<script src="main.js"></script>