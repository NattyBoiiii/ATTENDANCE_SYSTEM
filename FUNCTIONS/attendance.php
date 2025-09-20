<?php
require_once 'CLASSES/attendance_controller.php';

function handle_attendance_add(array $post): array {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $currentUser = $_SESSION['user'] ?? null;
    $role = $currentUser['role'] ?? 'student';

    $studentId = (int)($post['student_id'] ?? 0);
    if ($role !== 'admin') {
        $studentId = (int)($currentUser['user_id'] ?? 0);
    }
    $courseId = (int)($post['course_id'] ?? 0);
    $attendanceDate = trim($post['attendance_date'] ?? '');
    $status = trim($post['attendance_status'] ?? '');

    $controller = new StudentAttendanceController();
    return $controller->addAttendance($studentId, $courseId, $attendanceDate, $status);
}

function handle_attendance_update(array $post): array {
    $attendanceId = (int)($post['attendance_id'] ?? 0);
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $currentUser = $_SESSION['user'] ?? null;
    $role = $currentUser['role'] ?? 'student';

    $studentId = (int)($post['student_id'] ?? 0);
    if ($role !== 'admin') {
        $studentId = (int)($currentUser['user_id'] ?? 0);
    }
    $courseId = (int)($post['course_id'] ?? 0);
    $attendanceDate = trim($post['attendance_date'] ?? '');
    $status = trim($post['attendance_status'] ?? '');

    $controller = new AdminAttendanceController();
    return $controller->updateAttendance($attendanceId, $studentId, $courseId, $attendanceDate, $status);
}

function handle_attendance_delete(array $post): array {
    $attendanceId = (int)($post['attendance_id'] ?? 0);

    $controller = new AdminAttendanceController();
    return $controller->deleteAttendance($attendanceId);
}

function get_attendance_data(): array {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $user = $_SESSION['user'] ?? null;
    $role = $user['role'] ?? 'student';

    $controller = new StudentAttendanceController();
    if ($role === 'admin') {
        return $controller->listAttendance();
    }
    $studentId = (int)($user['user_id'] ?? 0);
    if ($studentId > 0) {
        return $controller->listAttendanceForStudent($studentId);
    }
    return [];
}

function get_students_data(): array {
    $controller = new StudentAttendanceController();
    return $controller->getStudents();
}

function get_courses_data(): array {
    $controller = new StudentAttendanceController();
    return $controller->getCourses();
}
?>