<?php
header('Content-Type: application/json');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$projectRoot = dirname(__DIR__);
if (is_dir($projectRoot)) {
    @chdir($projectRoot);
}

require_once 'FUNCTIONS/attendance.php';
require_once 'FUNCTIONS/register.php';
require_once 'FUNCTIONS/login.php';
require_once 'CLASSES/admin_class.php';

$action = $_POST['action'] ?? '';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    switch ($action) {
        case 'add_attendance':
            $result = handle_attendance_add($_POST);
            $response['success'] = $result['success'];
            $response['message'] = $result['success'] ? 'Attendance added successfully.' : ($result['errors']['general'] ?? 'Failed to add attendance.');
            break;
            
        case 'update_attendance':
            $result = handle_attendance_update($_POST);
            $response['success'] = $result['success'];
            $response['message'] = $result['success'] ? 'Attendance updated successfully.' : 'Failed to update attendance.';
            break;
            
        case 'delete_attendance':
            $result = handle_attendance_delete($_POST);
            $response['success'] = $result['success'];
            $response['message'] = $result['success'] ? 'Attendance deleted successfully.' : 'Failed to delete attendance.';
            break;
            
        case 'create':
            if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
                throw new Exception('Unauthorized access');
            }
            $admin = new AdminCoursesController();
            $result = $admin->addCourse($_POST['course_name'] ?? '', $_POST['course_description'] ?? null);
            $response['success'] = $result['success'];
            $response['message'] = $result['success'] ? 'Course created successfully.' : ($result['message'] ?? 'Failed to create course.');
            break;
            
        case 'update':
            if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
                throw new Exception('Unauthorized access');
            }
            $admin = new AdminCoursesController();
            $result = $admin->updateCourse((int)($_POST['course_id'] ?? 0), $_POST['course_name'] ?? '', $_POST['course_description'] ?? null);
            $response['success'] = $result['success'];
            $response['message'] = $result['success'] ? 'Course updated successfully.' : 'Failed to update course.';
            break;
            
        case 'delete':
            if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
                throw new Exception('Unauthorized access');
            }
            $admin = new AdminCoursesController();
            $result = $admin->deleteCourse((int)($_POST['course_id'] ?? 0));
            $response['success'] = $result['success'];
            $response['message'] = $result['success'] ? 'Course deleted successfully.' : 'Failed to delete course.';
            break;
            
        case 'register':
            $result = handle_registration_combined($_POST);
            $response['success'] = $result['success'];
            $response['message'] = $result['success'] ? 'Registration successful. You can now log in.' : ($result['errors']['form'] ?? 'Registration failed.');
            break;
            
        case 'login':
            $result = handle_login($_POST);
            $response['success'] = $result['success'];
            $response['message'] = $result['success'] ? 'Login successful.' : ($result['errors']['form'] ?? 'Login failed.');
            if ($result['success']) {
                $response['redirect'] = 'index.php';
            }
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('AJAX Handler Error: ' . $e->getMessage());
}

echo json_encode($response);
exit;
?>