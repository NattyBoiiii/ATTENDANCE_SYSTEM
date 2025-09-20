<?php
require_once 'courses.php';

final class StudentCoursesController {
    private CoursesService $service;

    public function __construct() {
        $this->service = new CoursesService(new CoursesRepository());
    }

    public function listCourses(): array {
        return $this->service->listCourses();
    }
}
?>