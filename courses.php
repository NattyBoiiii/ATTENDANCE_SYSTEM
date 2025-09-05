<?php
require_once 'CLASSES/admin_class.php';
require_once 'CLASSES/student_class.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$role = $user['role'] ?? 'student';

$message = '';
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin = new AdminCoursesController();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $res = $admin->addCourse($_POST['course_name'] ?? '', $_POST['course_description'] ?? null);
        $message = $res['success'] ? 'Course created successfully.' : ($res['message'] ?? 'Failed to create course.');
    } elseif ($action === 'update') {
        $res = $admin->updateCourse((int)($_POST['course_id'] ?? 0), $_POST['course_name'] ?? '', $_POST['course_description'] ?? null);
        $message = $res['success'] ? 'Course updated successfully.' : 'Failed to update course.';
    } elseif ($action === 'delete') {
        $res = $admin->deleteCourse((int)($_POST['course_id'] ?? 0));
        $message = $res['success'] ? 'Course deleted successfully.' : 'Failed to delete course.';
    }
}

if ($role === 'admin') {
    $controller = new AdminCoursesController();
} else {
    $controller = new StudentCoursesController();
}
$courses = $controller->listCourses();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Courses Directory - Attendance System</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand">Attendance System</div>
      <nav class="nav">
        <a href="index.php" class="nav-link">Home</a>
        <a href="courses.php" class="nav-link active">Courses Directory</a>
      </nav>
      <div class="mt-4 pt-4 border-t border-slate-700">
        <a href="FUNCTIONS/logout.php" class="nav-link" style="color: #fca5a5;">Logout</a>
      </div>
    </aside>
    <main class="content">
      <h1 class="card-title">Courses</h1>
      <?php if ($message): ?>
        <div class="message message-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <?php if ($role === 'admin'): ?>
      <div class="card">
        <h2 class="card-title">Add Course</h2>
        <form method="post" class="form-grid">
          <input type="hidden" name="action" value="create" />
          <div class="form-group">
            <label>Course Name</label>
            <input name="course_name" placeholder="Course name" required />
          </div>
          <div class="form-group">
            <label>Course Description</label>
            <textarea name="course_description" placeholder="Course description"></textarea>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Course</button>
          </div>
        </form>
      </div>
      <?php endif; ?>
      <div class="card">
        <h2 class="card-title">All Courses</h2>
        <div style="display: grid; gap: 16px;">
          <?php foreach ($courses as $c): ?>
            <div class="card">
              <div class="card-header">
                <h3 style="font-size: 18px; font-weight: 600; color: #1e40af; margin: 0;"><?php echo htmlspecialchars($c['course_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php if ($role === 'admin'): ?>
                  <div style="display: flex; gap: 8px;">
                    <button onclick="toggleCourseEdit(<?php echo (int)$c['course_id']; ?>)" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                    <button onclick="confirmDeleteCourse(<?php echo (int)$c['course_id']; ?>)" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Delete</button>
                  </div>
                <?php endif; ?>
              </div>
              <div style="color: #64748b; font-size: 14px; margin-bottom: 16px;"><?php echo htmlspecialchars((string)($c['course_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
              <?php if ($role === 'admin'): ?>
                <div id="editCourse-<?php echo (int)$c['course_id']; ?>" class="hidden" style="margin-top: 16px; padding: 16px; background: #f0f9ff; border-radius: 8px;">
                  <form method="post" class="form-grid">
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="course_id" value="<?php echo (int)$c['course_id']; ?>" />
                    <div class="form-group">
                      <label>Course Name</label>
                      <input name="course_name" value="<?php echo htmlspecialchars($c['course_name'], ENT_QUOTES, 'UTF-8'); ?>" required />
                    </div>
                    <div class="form-group">
                      <label>Course Description</label>
                      <textarea name="course_description"><?php echo htmlspecialchars((string)($c['course_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="form-actions">
                      <button type="submit" class="btn btn-primary">Update</button>
                      <button type="button" onclick="toggleCourseEdit(<?php echo (int)$c['course_id']; ?>)" class="btn btn-secondary btn-sm">Cancel</button>
                    </div>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if (empty($courses)): ?>
            <div style="text-align: center; color: #64748b; padding: 40px;">No courses yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
<script src="main.js"></script>