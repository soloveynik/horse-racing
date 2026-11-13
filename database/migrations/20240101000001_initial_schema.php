<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialSchema extends AbstractMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('username', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->create();

        $this->table('owners')
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('contact', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->create();

        $this->table('jockeys')
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('experience_years', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->create();

        $this->table('horses')
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('breed', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('birth_year', 'integer', ['null' => true])
            ->addColumn('owner_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('owner_id', 'owners', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();

        $this->table('races')
            ->addColumn('name', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('race_date', 'date', ['null' => false])
            ->addColumn('location', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'planned', 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->create();

        $this->table('results')
            ->addColumn('race_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('horse_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('jockey_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('place', 'integer', ['null' => false])
            ->addColumn('finish_time', 'decimal', ['precision' => 8, 'scale' => 2, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('race_id', 'races', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('horse_id', 'horses', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('jockey_id', 'jockeys', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addIndex(['race_id', 'horse_id'], ['unique' => true])
            ->addIndex(['race_id', 'place'], ['unique' => true])
            ->create();
    }
}
