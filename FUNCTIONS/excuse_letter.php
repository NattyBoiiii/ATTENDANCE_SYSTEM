<?php
require_once 'CLASSES/excuse_letter.php';
require_once 'CLASSES/attendance.php';
require_once 'CLASSES/attendance_controller.php';

$excuseLetterObj = new ExcuseLetter();
$attendanceRepo = new AttendanceRepository();
$coursesList = $attendanceRepo->getCourses();
$selectedCourse = isset($_GET['course_filter']) ? (int)$_GET['course_filter'] : 0;
$currentUser = $_SESSION['user'] ?? null;
$currentUserId = (int)($currentUser['user_id'] ?? 0);
$role = $currentUser['role'] ?? 'student';

$studentsList = [];
if (method_exists($attendanceRepo, 'getStudents')) {
    $studentsList = $attendanceRepo->getStudents();
} elseif (method_exists($attendanceRepo, 'listAll')) {
    $tmp = $attendanceRepo->listAll();
    foreach ($tmp as $r) {
        $studentsList[] = [
            'student_id' => $r['student_id'] ?? 0,
            'full_name' => $r['student_name'] ?? ($r['full_name'] ?? ''),
            'year_level' => $r['year_level'] ?? null,
            'course_id' => $r['course_id'] ?? null,
            'course_name' => $r['course_name'] ?? ''
        ];
    }
} else {
    error_log('AttendanceRepository: no method found to retrieve students. Expected getStudents() or listAll().');
}

$attendanceList = [];
if (method_exists($attendanceRepo, 'listAll')) {
    $attendanceList = $attendanceRepo->listAll();
} elseif (method_exists($attendanceRepo, 'listByStudent')) {
    foreach ($studentsList as $s) {
        $sid = (int)($s['student_id'] ?? 0);
        if ($sid > 0) {
            $rows = $attendanceRepo->listByStudent($sid);
            if (is_array($rows) && !empty($rows)) {
                foreach ($rows as $row) {
                    $attendanceList[] = $row;
                }
            }
        }
    }
} else {
    error_log('AttendanceRepository: no method found to retrieve attendance. Expected listAll() or listByStudent().');
}

$excuseLetters = $excuseLetterObj->getAllExcuseLetters();

$studentsData = [];
foreach ($studentsList as $s) {
    $studentsData[(int)($s['student_id'] ?? $s['id'] ?? 0)] = [
        'name' => $s['full_name'] ?? ($s['name'] ?? 'Unknown'),
        'year_level' => $s['year_level'] ?? '',
        'course_id' => $s['course_id'] ?? null,
        'course_name' => $s['course_name'] ?? ''
    ];
}

$attendanceData = [];
foreach ($attendanceList as $a) {
    $attendanceId = (int)($a['attendance_id'] ?? $a['id'] ?? 0);
    $attendanceData[$attendanceId] = [
        'date' => $a['attendance_date'] ?? ($a['date'] ?? ''),
        'student_id' => (int)($a['student_id'] ?? $a['user_id'] ?? 0),
        'course_id' => (int)($a['course_id'] ?? 0),
        'status' => $a['attendance_status'] ?? ($a['status'] ?? ''),
        'student_name' => $a['student_name'] ?? ''
    ];
}

$filteredLetters = [];
foreach ($excuseLetters as $letter) {
    $letterAttendanceId = (int)($letter['attendance_id'] ?? 0);
    $att = $attendanceData[$letterAttendanceId] ?? null;

    if ($role === 'student') {
        if ((int)$letter['student_id'] !== $currentUserId) {
            continue;
        }
    }

    if ($selectedCourse > 0) {
        $letterCourseId = (int)($att['course_id'] ?? 0);
        if ($letterCourseId !== $selectedCourse) {
            continue;
        }
    }

    $filteredLetters[] = $letter;
}

$excuseLetters = $filteredLetters;

function handle_add_excuse_letter(array $post, array $files): array
{
    global $excuseLetterObj;

    $errors = [];

    $student_id = isset($post['student_id']) ? (int)$post['student_id'] : null;
    $attendance_id = isset($post['attendance_id']) ? (int)$post['attendance_id'] : null;
    $excuse = trim($post['excuse'] ?? '');

    if (!$student_id || !$attendance_id || $excuse === '') {
        $errors['general'] = "All fields are required.";
        return ['success' => false, 'errors' => $errors];
    }

    $imagePath = null;
    if (isset($files['excuse_image']) && $files['excuse_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/excuses/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = time() . "_" . basename($files['excuse_image']['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($files['excuse_image']['tmp_name'], $targetPath)) {
            $imagePath = $targetPath;
        } else {
            $errors['general'] = "Failed to upload image.";
            return ['success' => false, 'errors' => $errors];
        }
    }

    $success = $excuseLetterObj->addExcuseLetter($student_id, $attendance_id, $excuse, $imagePath);

    return [
        'success' => $success,
        'errors' => $success ? [] : ['general' => 'Database insert failed']
    ];
}

function handle_approve_excuse(array $post): array
{
    global $excuseLetterObj;
    $letter_id = isset($post['letter_id']) ? (int)$post['letter_id'] : null;
    if (!$letter_id) {
        return ['success' => false, 'errors' => ['general' => 'Invalid letter ID']];
    }

    $success = $excuseLetterObj->updateStatus($letter_id, 'Approved');
    return ['success' => $success, 'errors' => $success ? [] : ['general' => 'Failed to approve']];
}

function handle_reject_excuse(array $post): array
{
    global $excuseLetterObj;
    $letter_id = isset($post['letter_id']) ? (int)$post['letter_id'] : null;
    if (!$letter_id) {
        return ['success' => false, 'errors' => ['general' => 'Invalid letter ID']];
    }

    $success = $excuseLetterObj->updateStatus($letter_id, 'Rejected');
    return ['success' => $success, 'errors' => $success ? [] : ['general' => 'Failed to reject']];
}
?>