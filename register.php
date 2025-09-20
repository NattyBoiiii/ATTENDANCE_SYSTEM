<?php
require_once 'FUNCTIONS/register.php';
require_once 'CLASSES/courses.php';

$errors = [];
$successMessage = null;
$courses = [];

try {
    $coursesRepo = new CoursesRepository();
    $courses = $coursesRepo->listAll();
} catch (Exception $e) {
    $errors['form'] = 'Failed to load courses: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $result = handle_registration_combined($_POST);
        if ($result['success'] === true) {
            $successMessage = 'Registration successful. You can now log in.';
        } else {
            $errors = $result['errors'] ?? ['form' => 'Registration failed.'];
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
    <title>Register - Attendance System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="auth-wrapper">
    <div class="form-container auth-large">
        <form method="post" action="">
            <h2>Create Account</h2>
            <?php if (!empty($successMessage)) : ?>
                <div class="message message-success"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['form'])) : ?>
                <div class="message message-error"><?php echo htmlspecialchars($errors['form']); ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                <?php if (!empty($errors['email'])) : ?><div class="message message-error"><?php echo htmlspecialchars($errors['email']); ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="username">Username (optional)</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                <?php if (!empty($errors['username'])) : ?><div class="message message-error"><?php echo htmlspecialchars($errors['username']); ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                <?php if (!empty($errors['full_name'])) : ?><div class="message message-error"><?php echo htmlspecialchars($errors['full_name']); ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required onchange="toggleStudentFields()">
                    <option value="" disabled <?php echo empty($_POST['role']) ? 'selected' : ''; ?>>Select a role</option>
                    <option value="student" <?php echo (($_POST['role'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
                    <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
                <?php if (!empty($errors['role'])) : ?><div class="message message-error"><?php echo htmlspecialchars($errors['role']); ?></div><?php endif; ?>
            </div>
            <div id="studentFields" class="student-fields" style="display: <?php echo (($_POST['role'] ?? '') === 'student') ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select id="course_id" name="course_id">
                        <option value="" disabled selected>Select a course</option>
                        <?php foreach ($courses as $course) : ?>
                            <option value="<?php echo $course['course_id']; ?>" 
                                    <?php echo (($_POST['course_id'] ?? '') == $course['course_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['course_id'])) : ?><div class="message message-error"><?php echo htmlspecialchars($errors['course_id']); ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select id="year_level" name="year_level">
                        <option value="" disabled selected>Select year level</option>
                        <option value="1" <?php echo (($_POST['year_level'] ?? '') == '1') ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2" <?php echo (($_POST['year_level'] ?? '') == '2') ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3" <?php echo (($_POST['year_level'] ?? '') == '3') ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4" <?php echo (($_POST['year_level'] ?? '') == '4') ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                    <?php if (!empty($errors['year_level'])) : ?><div class="message message-error"><?php echo htmlspecialchars($errors['year_level']); ?></div><?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <?php if (!empty($errors['password'])) : ?><div class="message message-error"><?php echo htmlspecialchars($errors['password']); ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <?php if (!empty($errors['confirm_password'])) : ?><div class="message message-error"><?php echo htmlspecialchars($errors['confirm_password']); ?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Register</button>
            <div class="text-center mt-4" style="font-size: 14px;">
                Already have an account? <a href="login.php" style="color: #3b82f6; text-decoration: none;">Log in</a>
            </div>
        </form>
    </div>
    </div>
</body>
</html>
<script src="main.js"></script>