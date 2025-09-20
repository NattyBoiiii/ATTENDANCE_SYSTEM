<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once 'FUNCTIONS/excuse_letter.php';

$user = $_SESSION['user'];
$role = $user['role'] ?? 'student';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($role === 'admin') {
            $action = $_POST['action'] ?? '';
            if ($action === 'approve_excuse') {
                $res = handle_approve_excuse($_POST);
                $message = $res['success'] ? 'Excuse letter approved successfully.' : ($res['errors']['general'] ?? 'Failed to approve excuse letter.');
                $messageType = $res['success'] ? 'success' : 'error';
            } elseif ($action === 'reject_excuse') {
                $res = handle_reject_excuse($_POST);
                $message = $res['success'] ? 'Excuse letter rejected successfully.' : ($res['errors']['general'] ?? 'Failed to reject excuse letter.');
                $messageType = $res['success'] ? 'success' : 'error';
            }
        } elseif ($role === 'student') {
            $action = $_POST['action'] ?? '';
            if ($action === 'add_excuse_letter') {
                $res = handle_add_excuse_letter($_POST, $_FILES);
                $message = $res['success'] ? 'Excuse letter added successfully.' : ($res['errors']['general'] ?? 'Failed to add excuse letter.');
                $messageType = $res['success'] ? 'success' : 'error';
            }
        }
    } catch (Throwable $e) {
        $message = 'Unexpected error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$attendanceOptions = [];
foreach ($attendanceList as $att) {
    $status = $att['attendance_status'] ?? $att['status'] ?? '';
    if (!in_array($status, ['Absent', 'Late'], true)) {
        continue;
    }

    if ($role === 'student') {
        if ((int)$att['student_id'] === (int)$user['user_id']) {
            $attendanceOptions[] = $att;
        }
    } else {
        $attendanceOptions[] = $att;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance - Excuse Letters</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand">Attendance System</div>
      <nav class="nav">
        <a href="index.php" class="nav-link">Home</a>
        <a href="courses.php" class="nav-link">Courses Directory</a>
        <a href="excuses.php" class="nav-link active">Excuse Letter Directory</a>
      </nav>
      <div class="mt-4 pt-4 border-t border-slate-700">
        <a href="FUNCTIONS/logout.php" class="nav-link" style="color: #fca5a5;">Logout</a>
      </div>
    </aside>
    <main class="content">
      <h1 class="card-title">Dashboard</h1>
      <p class="mb-4" style="color: #64748b;">Welcome to Excuse Letters Directory</p>
      <?php if ($message): ?>
        <div class="message <?php echo $messageType === 'success' ? 'message-success' : 'message-error'; ?>">
          <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>
        <div class="table-header">
          <h2 class="table-title">Excuse Letters</h2>
          <div class="header-actions" style="align-items:center; gap:12px;">
            <?php if ($role === 'admin'): ?>
              <form id="filterForm" method="get" style="margin:0;">
                <select name="course_filter" onchange="document.getElementById('filterForm').submit();" class="search-input" style="min-width:220px;">
                  <option value="0"<?php echo (isset($_GET['course_filter']) && (int)$_GET['course_filter'] === 0) ? ' selected' : ''; ?>>All Courses</option>
                  <?php foreach ($coursesList as $c): ?>
                    <option value="<?php echo (int)$c['course_id']; ?>" <?php echo ((int)($_GET['course_filter'] ?? 0) === (int)$c['course_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($c['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </form>
            <?php endif; ?>
            <?php if ($role === 'student'): ?>
              <button id="addExcuseLetterBtn" class="btn btn-primary">Add Excuse Letter</button>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($role === 'student'): ?>
        <div id="addExcuseForm" class="card hidden" style="margin:16px 0;">
          <h3 class="card-title">Add Excuse Letter</h3>
          <form method="post" enctype="multipart/form-data" class="form-grid">
            <input type="hidden" name="action" value="add_excuse_letter" />
            <input type="hidden" name="student_id" value="<?php echo (int)$user['user_id']; ?>" />
            <div class="form-group">
              <label for="attendance_id">Attendance Record</label>
              <select id="attendance_id" name="attendance_id" required>
                <option value="">Select Attendance</option>
                <?php foreach ($attendanceOptions as $opt): ?>
                  <option value="<?php echo (int)$opt['attendance_id']; ?>">
                    <?php echo htmlspecialchars($opt['attendance_date'] . ' - ' . ($opt['course_name'] ?? $opt['student_name']), ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="excuse">Excuse</label>
              <textarea id="excuse" name="excuse" rows="4" required></textarea>
            </div>
            <div class="form-group">
              <label for="excuse_image">Upload Image as proof</label>
              <input type="file" id="excuse_image" name="excuse_image" accept="image/*" required/>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-success">Submit</button>
              <button type="button" class="btn btn-secondary" id="cancelAddExcuse">Cancel</button>
            </div>
          </form>
        </div>
        <?php endif; ?>
        <div class="table-container">
            <table class="table">
                <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Attendance Date</th>
                    <th>Excuse</th>
                    <th>Excuse Letter Image</th>
                    <th>Status</th>
                    <th>Date Created</th>
                    <?php if ($role === 'admin'): ?>
                    <th>Actions</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($excuseLetters as $letter): ?>
                    <tr>
                    <td><?php echo htmlspecialchars($studentsData[$letter['student_id']]['name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($attendanceData[$letter['attendance_id']]['date'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($letter['excuse'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php if (!empty($letter['image_path'])): ?>
                            <a href="<?php echo htmlspecialchars($letter['image_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">View Image</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($letter['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($letter['date_created'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <?php if ($role === 'admin'): ?>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="approve_excuse" />
                                <input type="hidden" name="letter_id" value="<?php echo htmlspecialchars($letter['letter_id'], ENT_QUOTES, 'UTF-8'); ?>" />
                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                            </form>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="reject_excuse" />
                                <input type="hidden" name="letter_id" value="<?php echo htmlspecialchars($letter['letter_id'], ENT_QUOTES, 'UTF-8'); ?>" />
                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
  </div>
</body>
</html>
<script src="main.js"></script>