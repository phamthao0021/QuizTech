<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Room extends Model
{
    protected string $table = 'rooms';
    protected array $fillable = [
        'room_code', 'exam_id', 'host_id', 'status', 
        'max_players', 'current_question', 'started_at', 'finished_at'
    ];

    public function generateCode(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }

    public function findByCode(string $code): ?array
    {
        return $this->queryOne("SELECT * FROM rooms WHERE room_code = ?", [$code]);
    }

    public function getWithDetails(int $roomId): ?array
    {
        $room = $this->findById($roomId);
        if (!$room) {
            return null;
        }

        $room['members'] = $this->query("
            SELECT u.id, u.fullname, u.student_code
            FROM room_members rm
            JOIN users u ON u.id = rm.user_id
            WHERE rm.room_id = ?
        ", [$roomId]);

        $room['exam'] = (new Exam())->findById($room['exam_id']);
        $room['host'] = (new User())->findById($room['host_id']);

        return $room;
    }

    public function join(int $roomId, int $userId): bool
    {
        $stmt = self::getDb()->prepare(
            "INSERT IGNORE INTO room_members (room_id, user_id) VALUES (?, ?)"
        );
        return $stmt->execute([$roomId, $userId]);
    }

    public function leave(int $roomId, int $userId): bool
    {
        $stmt = self::getDb()->prepare(
            "DELETE FROM room_members WHERE room_id = ? AND user_id = ?"
        );
        return $stmt->execute([$roomId, $userId]);
    }

    public function getMembers(int $roomId): array
    {
        return $this->query("
            SELECT u.id, u.fullname, u.student_code
            FROM room_members rm
            JOIN users u ON u.id = rm.user_id
            WHERE rm.room_id = ?
        ", [$roomId]);
    }

    public function getMemberCount(int $roomId): int
    {
        return (int)$this->queryOne(
            "SELECT COUNT(*) FROM room_members WHERE room_id = ?",
            [$roomId]
        )['COUNT(*)'] ?? 0;
    }

    public function isMember(int $roomId, int $userId): bool
    {
        return (bool)$this->queryOne(
            "SELECT 1 FROM room_members WHERE room_id = ? AND user_id = ?",
            [$roomId, $userId]
        );
    }

    public function start(int $roomId): bool
    {
        return $this->update($roomId, [
            'status' => 'playing',
            'started_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function finish(int $roomId): bool
    {
        return $this->update($roomId, [
            'status' => 'finished',
            'finished_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getActiveRooms(): array
    {
        return $this->query("
            SELECT r.*, e.title as exam_title, 
                   (SELECT COUNT(*) FROM room_members WHERE room_id = r.id) as member_count
            FROM rooms r
            JOIN exams e ON e.id = r.exam_id
            WHERE r.status IN ('waiting', 'playing')
            ORDER BY r.created_at DESC
        ");
    }
}