<?php
require_once 'database_class.php';

final class AttendanceRepository extends Database {
    public function listAll(): array {
        $stmt = $this->executeQuery('
            SELECT 
                a.attendance_id,
                a.student_id,
                a.course_id,
                a.attendance_date,
                a.attendance_status,
                u.full_name as student_name,
                c.course_name,
                u.year_level,
                a.date_created,
                a.date_updated
            FROM Attendance a
            JOIN Users u ON a.student_id = u.user_id
            JOIN Courses c ON a.course_id = c.course_id
            WHERE u.role = "student"
            ORDER BY a.attendance_date DESC, u.full_name ASC
        ');
        return $stmt->fetchAll();
    }

    public function listByStudent(int $studentId): array {
        $stmt = $this->executeQuery('
            SELECT 
                a.attendance_id,
                a.student_id,
                a.course_id,
                a.attendance_date,
                a.attendance_status,
                u.full_name as student_name,
                c.course_name,
                u.year_level,
                a.date_created,
                a.date_updated
            FROM Attendance a
            JOIN Users u ON a.student_id = u.user_id
            JOIN Courses c ON a.course_id = c.course_id
            WHERE u.role = "student" AND a.student_id = ?
            ORDER BY a.attendance_date DESC
        ', [$studentId]);
        return $stmt->fetchAll();
    }

    public function getById(int $attendanceId): ?array {
        $row = $this->executeQuerySingle('
            SELECT 
                a.attendance_id,
                a.student_id,
                a.course_id,
                a.attendance_date,
                a.attendance_status,
                u.full_name as student_name,
                c.course_name,
                u.year_level,
                a.date_created,
                a.date_updated
            FROM Attendance a
            JOIN Users u ON a.student_id = u.user_id
            JOIN Courses c ON a.course_id = c.course_id
            WHERE a.attendance_id = ? AND u.role = "student"
        ', [$attendanceId]);
        return $row !== false ? $row : null;
    }

    public function create(int $studentId, int $courseId, string $attendanceDate, string $status): int {
        $this->executeQuery('
            INSERT INTO Attendance (student_id, course_id, attendance_date, attendance_status) 
            VALUES (?, ?, ?, ?)
        ', [$studentId, $courseId, $attendanceDate, $status]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $attendanceId, int $studentId, int $courseId, string $attendanceDate, string $status): int {
        return $this->executeNonQuery('
            UPDATE Attendance 
            SET student_id = ?, course_id = ?, attendance_date = ?, attendance_status = ? 
            WHERE attendance_id = ?
        ', [$studentId, $courseId, $attendanceDate, $status, $attendanceId]);
    }

    public function delete(int $attendanceId): int {
        return $this->executeNonQuery('DELETE FROM Attendance WHERE attendance_id = ?', [$attendanceId]);
    }

    public function getStudentCourseId(int $studentId): ?int {
        $row = $this->executeQuerySingle(
            'SELECT course_id FROM Users WHERE user_id = ? AND role = "student"',
            [$studentId]
        );
        if ($row === false || $row['course_id'] === null) {
            return null;
        }
        return (int)$row['course_id'];
    }

    public function getStudents(): array {
        $stmt = $this->executeQuery('
            SELECT u.user_id as student_id, u.full_name, u.year_level, u.course_id, c.course_name
            FROM Users u
            JOIN Courses c ON u.course_id = c.course_id
            WHERE u.role = "student"
            ORDER BY u.full_name ASC
        ');
        return $stmt->fetchAll();
    }

    public function getCourses(): array {
        $stmt = $this->executeQuery('
            SELECT course_id, course_name 
            FROM Courses 
            ORDER BY course_name ASC
        ');
        return $stmt->fetchAll();
    }
}

final class AttendanceService {
    private AttendanceRepository $repo;

    public function __construct(AttendanceRepository $repo) {
        $this->repo = $repo;
    }

    public function listAttendance(): array {
        return $this->repo->listAll();
    }

    public function listAttendanceForStudent(int $studentId): array {
        return $this->repo->listByStudent($studentId);
    }

    public function addAttendance(int $studentId, int $courseId, string $attendanceDate, string $status): array {
        $errors = $this->validateAttendance($studentId, $courseId, $attendanceDate, $status);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $id = $this->repo->create($studentId, $courseId, $attendanceDate, $status);
        return ['success' => true, 'attendance_id' => $id];
    }

    public function addAttendanceForStudentFixedCourse(int $studentId, string $attendanceDate, string $status): array {
        $courseId = $this->repo->getStudentCourseId($studentId);
        if ($courseId === null || $courseId <= 0) {
            return ['success' => false, 'errors' => ['course_id' => 'No registered course found for this student.']];
        }

        $errors = $this->validateAttendance($studentId, $courseId, $attendanceDate, $status);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $id = $this->repo->create($studentId, $courseId, $attendanceDate, $status);
        return ['success' => true, 'attendance_id' => $id];
    }

    public function updateAttendance(int $attendanceId, int $studentId, int $courseId, string $attendanceDate, string $status): array {
        $errors = $this->validateAttendance($studentId, $courseId, $attendanceDate, $status);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $affected = $this->repo->update($attendanceId, $studentId, $courseId, $attendanceDate, $status);
        return ['success' => $affected > 0, 'affected' => $affected];
    }

    public function deleteAttendance(int $attendanceId): array {
        $affected = $this->repo->delete($attendanceId);
        return ['success' => $affected > 0, 'affected' => $affected];
    }

    public function getStudents(): array {
        return $this->repo->getStudents();
    }

    public function getCourses(): array {
        return $this->repo->getCourses();
    }

    private function validateAttendance(int $studentId, int $courseId, string $attendanceDate, string $status): array {
        $errors = [];
        
        if ($studentId <= 0) {
            $errors['student_id'] = 'Please select a student.';
        }
        
        if ($courseId <= 0) {
            $errors['course_id'] = 'Please select a course.';
        }
        
        if (empty($attendanceDate)) {
            $errors['attendance_date'] = 'Attendance date is required.';
        } elseif (!strtotime($attendanceDate)) {
            $errors['attendance_date'] = 'Please enter a valid date.';
        }
        
        if (!in_array($status, ['Present', 'Absent', 'Late'])) {
            $errors['attendance_status'] = 'Please select a valid attendance status.';
        }
        
        return $errors;
    }
}
?>