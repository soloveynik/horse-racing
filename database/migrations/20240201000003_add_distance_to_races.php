<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Третья миграция: новая функциональность — дистанция скачки (метры).
 * Требует изменения схемы и поддержки в API/сервисе.
 */
final class AddDistanceToRaces extends AbstractMigration
{
    public function change(): void
    {
        $this->table('races')
            ->addColumn('distance_meters', 'integer', [
                'null' => true,
                'after' => 'status',
                'comment' => 'Дистанция скачки в метрах',
            ])
            ->update();
    }
}
