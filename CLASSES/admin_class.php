<?php
require_once 'courses.php';

final class AdminCoursesController {
    private CoursesService $service;

    public function __construct() {
        $this->service = new CoursesService(new CoursesRepository());
    }

    public function listCourses(): array {
        return $this->service->listCourses();
    }

    public function addCourse(string $name, ?string $description): array {
        return $this->service->addCourse($name, $description);
    }

    public function updateCourse(int $courseId, string $name, ?string $description): array {
        return $this->service->updateCourse($courseId, $name, $description);
    }

    public function deleteCourse(int $courseId): array {
        return $this->service->deleteCourse($courseId);
    }
}
?>