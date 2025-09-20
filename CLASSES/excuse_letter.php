<?php
require_once 'database_class.php';

final class ExcuseLetter extends Database
{
    public function getAllExcuseLetters(): array
    {
        $stmt = $this->executeQuery('SELECT * FROM excuse_letters ORDER BY date_created DESC');
        return $stmt->fetchAll();
    }

    public function getExcuseLetterById(int $letter_id): ?array
    {
        $row = $this->executeQuerySingle('SELECT * FROM excuse_letters WHERE letter_id = ?', [$letter_id]);
        return $row !== false ? $row : null;
    }

    public function addExcuseLetter($student_id, $attendance_id, $excuse, $imagePath): bool
    {
        $status = 'Pending';
        $this->executeQuery(
            'INSERT INTO excuse_letters (student_id, attendance_id, excuse, image_path, status, date_created) VALUES (?, ?, ?, ?, ?, NOW())',
            [$student_id, $attendance_id, $excuse, $imagePath, $status]
        );
        return ((int)$this->pdo->lastInsertId()) > 0;
    }

    public function updateStatus($letter_id, $status): bool
    {
        $normalized = ucfirst(strtolower((string)$status));
        $allowed = ['Pending', 'Approved', 'Rejected'];
        if (!in_array($normalized, $allowed, true)) {
            return false;
        }
        $affected = $this->executeNonQuery('UPDATE excuse_letters SET status = ? WHERE letter_id = ?', [$normalized, $letter_id]);
        return $affected > 0;
    }
}
?>