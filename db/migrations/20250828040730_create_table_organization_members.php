<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Util\Literal;

final class CreateTableOrganizationMembers extends AbstractMigration
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
        $organization_members = $this->table('organization_members', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $organization_members->addColumn('id', 'biginteger', [
            'identity' => true,
            'null' => false,
            'signed' => false,
        ]);
        $organization_members->addColumn('organization_id', 'biginteger', [
            'null' => false,
            'signed' => false,
        ]);
        $organization_members->addColumn('user_id', 'biginteger', [
            'null' => false,
            'signed' => false,
        ]);
        $organization_members->addColumn('created_at', 'datetime', [
            'null' => false,
            'default' => Literal::from('CURRENT_TIMESTAMP'),
        ]);
        $organization_members->addColumn('updated_at', 'datetime', [
            'null' => false,
            'default' => Literal::from('CURRENT_TIMESTAMP'),
        ]);
        $organization_members->addColumn('deleted_at', 'datetime', [
            'null' => true,
        ]);

        $organization_members->addForeignKey('organization_id', 'organizations', ['id']);
        $organization_members->addForeignKey('user_id', 'users', ['id']);
    
        $organization_members->addIndex('organization_id', [
            'unique' => false,
            'name' => 'idx_organization_members_organization_id',
        ]);
        $organization_members->addIndex('user_id', [
            'unique' => false,
            'name' => 'idx_organization_members_user_id',
        ]);
        $organization_members->addIndex(['organization_id', 'user_id'], [
            'unique' => true,
            'name' => 'unique_organization_member',
        ]);

        $organization_members->addIndex(['deleted_at'], [
            'unique' => false,
            'name' => 'idx_organization_members_deleted_at',
        ]);

        $organization_members->create();
    }
}
