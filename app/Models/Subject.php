<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Subject extends Model
{
    protected string $table = 'subjects';
    protected array $fillable = ['name', 'slug', 'description', 'icon', 'color'];

    public function getAllWithStats(): array
    {
        return $this->query("
            SELECT s.*,
                   COUNT(DISTINCT q.id) as question_count,
                   COUNT(DISTINCT e.id) as exam_count
            FROM subjects s
            LEFT JOIN questions q ON q.subject_id = s.id
            LEFT JOIN exams e ON e.subject_id = s.id
            GROUP BY s.id
            ORDER BY s.name
        ");
    }

    public function findByName(string $name): ?array
    {
        return $this->queryOne("SELECT * FROM subjects WHERE name = ?", [$name]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->queryOne("SELECT * FROM subjects WHERE slug = ?", [$slug]);
    }

    public function createSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        // Ensure unique
        $counter = 1;
        $originalSlug = $slug;
        while ($this->findBySlug($slug)) {
            $slug = $originalSlug . '-' . $counter++;
        }
        return $slug;
    }
}