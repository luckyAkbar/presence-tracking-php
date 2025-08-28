<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOrganizations extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $organizations = $this->table('organizations', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $organizations->addColumn('id', 'biginteger', [
            'identity' => true,
            'null' => false,
            'signed' => false,
        ]);

        $organizations->addColumn('name', 'string', [
            'null' => false,
            'limit' => 255,
        ]);

        $organizations->addColumn('description', 'text', [
            'null' => false,
        ]);

        $organizations->addColumn('is_active', 'boolean', [
            'null' => false,
            'default' => true,
        ]);

        $organizations->addColumn('created_by', 'biginteger', [
            'null' => false,
            'comment' => 'The user who originally created the organization',
            'signed' => false,
        ]);
        $organizations->addForeignKey('created_by', 'users', ['id']);

        $organizations->addColumn('created_at', 'datetime', [
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
        ]);

        $organizations->addColumn('updated_at', 'datetime', [
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
            'update' => 'CURRENT_TIMESTAMP',
        ]);

        $organizations->addColumn('deleted_at', 'datetime', [
            'null' => true,
        ]);

        // add index for created_by
        $organizations->addIndex('created_by', [
            'unique' => false,
            'name' => 'idx_organizations_created_by',
        ]);

        $organizations->addIndex(['deleted_at'], [
            'unique' => false,
            'name' => 'idx_organizations_deleted_at',
        ]);

        $organizations->create();
    }
}
