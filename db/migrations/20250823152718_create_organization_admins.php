<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOrganizationAdmins extends AbstractMigration
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
        $organizationAdmins = $this->table('organization_admins');

        $organizationAdmins->addColumn('organization_id', 'biginteger', [
            'null' => false,
            'signed' => false,
        ]);
        $organizationAdmins->addForeignKey('organization_id', 'organizations', ['id']);

        $organizationAdmins->addColumn('user_id', 'biginteger', [
            'null' => false,
            'signed' => false,
        ]);
        $organizationAdmins->addForeignKey('user_id', 'users', ['id']);

        $organizationAdmins->addColumn('created_at', 'datetime', [
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
        ]);
        $organizationAdmins->addColumn('updated_at', 'datetime', [
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
        ]);
        $organizationAdmins->addColumn('deleted_at', 'datetime', [
            'null' => true,
        ]);

        // to ensure that a user can only be an admin of an organization once
        $organizationAdmins->addIndex(['organization_id', 'user_id'], [
            'unique' => true,
            'name' => 'unique_organization_admin',
        ]);

        $organizationAdmins->addIndex(['deleted_at'], [
            'unique' => false,
            'name' => 'idx_organization_admins_deleted_at',
        ]);

        $organizationAdmins->create();
    }
}
