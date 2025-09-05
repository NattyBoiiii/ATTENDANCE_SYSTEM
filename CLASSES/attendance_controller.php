<?php
require_once 'attendance.php';

final class StudentAttendanceController {
    private AttendanceService $service;

    public function __construct() {
        $this->service = new AttendanceService(new AttendanceRepository());
    }

    public function listAttendance(): array {
        return $this->service->listAttendance();
    }

    public function listAttendanceForStudent(int $studentId): array {
        return $this->service->listAttendanceForStudent($studentId);
    }

    public function addAttendance(int $studentId, int $courseId, string $attendanceDate, string $status): array {
        return $this->service->addAttendance($studentId, $courseId, $attendanceDate, $status);
    }

    public function getStudents(): array {
        return $this->service->getStudents();
    }

    public function getCourses(): array {
        return $this->service->getCourses();
    }
}

final class AdminAttendanceController {
    private AttendanceService $service;

    public function __construct() {
        $this->service = new AttendanceService(new AttendanceRepository());
    }

    public function listAttendance(): array {
        return $this->service->listAttendance();
    }

    public function addAttendance(int $studentId, int $courseId, string $attendanceDate, string $status): array {
        return $this->service->addAttendance($studentId, $courseId, $attendanceDate, $status);
    }

    public function updateAttendance(int $attendanceId, int $studentId, int $courseId, string $attendanceDate, string $status): array {
        return $this->service->updateAttendance($attendanceId, $studentId, $courseId, $attendanceDate, $status);
    }

    public function deleteAttendance(int $attendanceId): array {
        return $this->service->deleteAttendance($attendanceId);
    }

    public function getStudents(): array {
        return $this->service->getStudents();
    }

    public function getCourses(): array {
        return $this->service->getCourses();
    }
}
?>