<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Вторая миграция: индексы для частых выборок и комментарий к статусу.
 */
final class AddIndexesAndStatusCheck extends AbstractMigration
{
    public function change(): void
    {
        $this->table('races')
            ->addIndex(['race_date'])
            ->addIndex(['status'])
            ->update();

        $this->table('horses')
            ->addIndex(['name'])
            ->update();

        $this->table('results')
            ->addIndex(['place'])
            ->update();
    }
}
