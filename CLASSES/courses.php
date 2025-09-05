<?php
require_once 'database_class.php';

final class CoursesRepository extends Database {
    public function listAll(): array {
        $stmt = $this->executeQuery('SELECT course_id, course_name, course_description, date_created, date_updated FROM Courses ORDER BY course_name ASC');
        return $stmt->fetchAll();
    }

    public function getById(int $courseId): ?array {
        $row = $this->executeQuerySingle('SELECT course_id, course_name, course_description, date_created, date_updated FROM Courses WHERE course_id = ?', [$courseId]);
        return $row !== false ? $row : null;
    }

    public function create(string $name, ?string $description): int {
        $this->executeQuery('INSERT INTO Courses (course_name, course_description) VALUES (?, ?)', [trim($name), $description]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $courseId, string $name, ?string $description): int {
        return $this->executeNonQuery('UPDATE Courses SET course_name = ?, course_description = ? WHERE course_id = ?', [trim($name), $description, $courseId]);
    }

    public function delete(int $courseId): int {
        return $this->executeNonQuery('DELETE FROM Courses WHERE course_id = ?', [$courseId]);
    }
}

final class CoursesService {
    private CoursesRepository $repo;

    public function __construct(CoursesRepository $repo) {
        $this->repo = $repo;
    }

    public function listCourses(): array {
        return $this->repo->listAll();
    }

    public function addCourse(string $name, ?string $description): array {
        $name = trim($name);
        if ($name === '') {
            return ['success' => false, 'message' => 'Course name is required.'];
        }
        $id = $this->repo->create($name, $description);
        return ['success' => true, 'course_id' => $id];
    }

    public function updateCourse(int $courseId, string $name, ?string $description): array {
        $name = trim($name);
        if ($name === '') {
            return ['success' => false, 'message' => 'Course name is required.'];
        }
        $affected = $this->repo->update($courseId, $name, $description);
        return ['success' => $affected > 0, 'affected' => $affected];
    }

    public function deleteCourse(int $courseId): array {
        $affected = $this->repo->delete($courseId);
        return ['success' => $affected > 0, 'affected' => $affected];
    }
}
?>