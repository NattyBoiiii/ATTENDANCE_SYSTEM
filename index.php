<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once 'FUNCTIONS/attendance.php';

$user = $_SESSION['user'];
$role = $user['role'] ?? 'student';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_attendance') {
        $result = handle_attendance_add($_POST);
        $message = $result['success'] ? 'Attendance added successfully.' : ($result['errors']['general'] ?? 'Failed to add attendance.');
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'update_attendance' && $role === 'admin') {
        $result = handle_attendance_update($_POST);
        $message = $result['success'] ? 'Attendance updated successfully.' : 'Failed to update attendance.';
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'delete_attendance' && $role === 'admin') {
        $result = handle_attendance_delete($_POST);
        $message = $result['success'] ? 'Attendance deleted successfully.' : 'Failed to delete attendance.';
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

$attendanceData = get_attendance_data();
$studentsData = get_students_data();
$coursesData = get_courses_data();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance - Home</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand">Attendance System</div>
      <nav class="nav">
        <a href="index.php" class="nav-link active">Home</a>
        <a href="courses.php" class="nav-link">Courses Directory</a>
      </nav>
      <div class="mt-4 pt-4 border-t border-slate-700">
        <a href="FUNCTIONS/logout.php" class="nav-link" style="color: #fca5a5;">Logout</a>
      </div>
    </aside>
    <main class="content">
      <h1 class="card-title">Dashboard</h1>
      <p class="mb-4" style="color: #64748b;">Welcome to the attendance system.</p>
      <?php if ($message): ?>
        <div class="message <?php echo $messageType === 'success' ? 'message-success' : 'message-error'; ?>">
          <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>
      <div class="table-container">
        <div class="table-header">
          <h2>ATTENDANCE</h2>
          <div class="header-actions">
            <button onclick="toggleAddForm()" class="btn btn-success btn-sm">
              Add Attendance
            </button>
            <?php if ($role === 'admin'): ?>
              <input id="attendanceSearch" type="text" class="search-input" placeholder="Search records..." />
            <?php endif; ?>
          </div>
        </div>
        <div id="addAttendanceForm" class="hidden card">
          <h3 class="card-title">Add New Attendance</h3>
          <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add_attendance" />
            <div class="form-group">
              <label>Student</label>
              <?php if ($role === 'admin'): ?>
                <select name="student_id" required>
                  <option value="">Select Student</option>
                  <?php foreach ($studentsData as $student): ?>
                    <option value="<?php echo (int)$student['student_id']; ?>">
                      <?php echo htmlspecialchars($student['full_name'], ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($student['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <input type="hidden" name="student_id" value="<?php echo (int)$user['user_id']; ?>" />
                <input type="text" value="<?php echo htmlspecialchars($user['full_name'] ?? 'You', ENT_QUOTES, 'UTF-8'); ?>" disabled style="background:#f8fafc;" />
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label>Course</label>
              <?php if ($role === 'admin'): ?>
                <select name="course_id" required>
                  <option value="">Select Course</option>
                  <?php foreach ($coursesData as $course): ?>
                    <option value="<?php echo (int)$course['course_id']; ?>">
                      <?php echo htmlspecialchars($course['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <?php
                  $studentCourseId = null;
                  $studentCourseName = '';
                  foreach ($studentsData as $student) {
                    if ((int)$student['student_id'] === (int)$user['user_id']) {
                      $studentCourseId = (int)$student['course_id'];
                      $studentCourseName = $student['course_name'] ?? '';
                      break;
                    }
                  }
                ?>
                <input type="hidden" name="course_id" value="<?php echo (int)($studentCourseId ?? 0); ?>" />
                <input type="text" value="<?php echo htmlspecialchars($studentCourseName ?: 'Your course', ENT_QUOTES, 'UTF-8'); ?>" disabled style="background:#f8fafc;" />
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label>Date</label>
              <input type="date" name="attendance_date" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="attendance_status" required>
                <option value="">Select Status</option>
                <option value="Present">Present</option>
                <option value="Absent">Absent</option>
                <option value="Late">Late</option>
              </select>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-success btn-sm">
                Add Attendance
              </button>
              <button type="button" onclick="toggleAddForm()" class="btn btn-secondary btn-sm">
                Cancel
              </button>
            </div>
          </form>
        </div>
        <div style="overflow-x: auto;">
          <table class="table">
            <thead>
              <tr>
                <?php if ($role === 'admin'): ?>
                  <th>Actions</th>
                <?php endif; ?>
                <th>Student Name</th>
                <th>Course</th>
                <th>Year Level</th>
                <th>Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($attendanceData)): ?>
                <?php foreach ($attendanceData as $attendance): ?>
                  <tr>
                    <?php if ($role === 'admin'): ?>
                      <td>
                        <div style="display: flex; gap: 8px;">
                          <button onclick="toggleEditForm(<?php echo (int)$attendance['attendance_id']; ?>)" class="btn btn-primary btn-sm">
                            Edit
                          </button>
                          <button onclick="confirmDeleteAttendance(<?php echo (int)$attendance['attendance_id']; ?>)" class="btn btn-danger btn-sm">
                            Delete
                          </button>
                        </div>
                      </td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($attendance['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($attendance['course_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int)$attendance['year_level']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($attendance['attendance_date'])); ?></td>
                    <td>
                      <span class="status-badge status-<?php echo strtolower($attendance['attendance_status']); ?>">
                        <?php echo htmlspecialchars($attendance['attendance_status'], ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    </td>
                  </tr>
                  <?php if ($role === 'admin'): ?>
                    <tr id="editForm-<?php echo (int)$attendance['attendance_id']; ?>" class="hidden" style="background: #f0f9ff;">
                      <td colspan="6" style="padding: 20px;">
                        <form method="post" class="form-grid">
                          <input type="hidden" name="action" value="update_attendance" />
                          <input type="hidden" name="attendance_id" value="<?php echo (int)$attendance['attendance_id']; ?>" />
                          
                          <div class="form-group">
                            <label>Student</label>
                            <?php if ($role === 'admin'): ?>
                              <select name="student_id" required>
                                <?php foreach ($studentsData as $student): ?>
                                  <option value="<?php echo (int)$student['student_id']; ?>" <?php echo $student['student_id'] == $attendance['student_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['full_name'], ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($student['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            <?php else: ?>
                              <input type="hidden" name="student_id" value="<?php echo (int)$user['user_id']; ?>" />
                              <input type="text" value="<?php echo htmlspecialchars($user['full_name'] ?? 'You', ENT_QUOTES, 'UTF-8'); ?>" disabled style="background:#f8fafc;" />
                            <?php endif; ?>
                          </div>
                          <div class="form-group">
                            <label>Course</label>
                            <select name="course_id" required>
                              <?php foreach ($coursesData as $course): ?>
                                <option value="<?php echo (int)$course['course_id']; ?>" <?php echo $course['course_id'] == $attendance['course_id'] ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars($course['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="attendance_date" required value="<?php echo $attendance['attendance_date']; ?>">
                          </div>
                          <div class="form-group">
                            <label>Status</label>
                            <select name="attendance_status" required>
                              <option value="Present" <?php echo $attendance['attendance_status'] === 'Present' ? 'selected' : ''; ?>>Present</option>
                              <option value="Absent" <?php echo $attendance['attendance_status'] === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                              <option value="Late" <?php echo $attendance['attendance_status'] === 'Late' ? 'selected' : ''; ?>>Late</option>
                            </select>
                          </div>
                          <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                              Update Attendance
                            </button>
                            <button type="button" onclick="toggleEditForm(<?php echo (int)$attendance['attendance_id']; ?>)" class="btn btn-secondary">
                              Cancel
                            </button>
                          </div>
                        </form>
                      </td>
                    </tr>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="<?php echo $role === 'admin' ? '6' : '5'; ?>" style="padding: 40px; text-align: center; color: #64748b;">
                    No attendance records found.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
<script src="main.js"></script>