<?php

declare(strict_types=1);

namespace App\Service;

use App\Validation\Validator;
use PDO;
use RuntimeException;

final class RaceService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array{success: bool, data?: array, message?: string, id?: int|string}
     */
    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $errors = Validator::validateRace($data);
        if ($errors !== []) {
            return ['success' => false, 'message' => implode('; ', $errors)];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO races (name, race_date, location, status, distance_meters)
             VALUES (:name, :race_date, :location, :status, :distance_meters)'
        );

        $stmt->execute([
            'name' => trim((string) $data['name']),
            'race_date' => trim((string) $data['race_date']),
            'location' => trim((string) $data['location']),
            'status' => $data['status'] ?? 'planned',
            'distance_meters' => isset($data['distance_meters']) && $data['distance_meters'] !== ''
                ? (int) $data['distance_meters']
                : null,
        ]);

        return [
            'success' => true,
            'message' => 'Скачка успешно создана',
            'id' => $this->pdo->lastInsertId(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, race_date, location, status, distance_meters, created_at
             FROM races
             ORDER BY race_date DESC, id DESC'
        );

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, race_date, location, status, distance_meters, created_at
             FROM races WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array{success: bool, message?: string}
     */
    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        $data['id'] = $id;
        $errors = Validator::validateRace($data, true);
        if ($errors !== []) {
            return ['success' => false, 'message' => implode('; ', $errors)];
        }

        $existing = $this->findById($id);
        if ($existing === null) {
            return ['success' => false, 'message' => 'Скачка не найдена'];
        }

        $stmt = $this->pdo->prepare(
            'UPDATE races
             SET name = :name,
                 race_date = :race_date,
                 location = :location,
                 status = :status,
                 distance_meters = :distance_meters
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'name' => trim((string) $data['name']),
            'race_date' => trim((string) $data['race_date']),
            'location' => trim((string) $data['location']),
            'status' => $data['status'] ?? $existing['status'],
            'distance_meters' => isset($data['distance_meters']) && $data['distance_meters'] !== ''
                ? (int) $data['distance_meters']
                : null,
        ]);

        return ['success' => true, 'message' => 'Скачка обновлена'];
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Некорректный ID'];
        }

        $stmt = $this->pdo->prepare('DELETE FROM races WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Скачка не найдена'];
        }

        return ['success' => true, 'message' => 'Скачка удалена'];
    }
}
