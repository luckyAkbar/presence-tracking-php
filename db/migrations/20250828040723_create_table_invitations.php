<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Util\Literal;

final class CreateTableInvitations extends AbstractMigration
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
        $invitations = $this->table('invitations', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $invitations->addColumn('id', 'biginteger', [
            'identity' => true,
            'null' => false,
            'signed' => false,
        ]);
        $invitations->addColumn('organization_id', 'biginteger', [
            'null' => false,
            'signed' => false,
        ]);
        $invitations->addColumn('created_by', 'biginteger', [
            'null' => false,
            'signed' => false,
        ]);
        $invitations->addColumn('intended_for', 'biginteger', [
            'null' => false,
            'signed' => false,
        ]);
        $invitations->addColumn('expires_at', 'datetime', [
            'null' => false,
        ]);
        $invitations->addColumn('status', 'enum', [
            'null' => false,
            'values' => ['pending', 'accepted', 'rejected', 'cancelled'],
        ]);
        $invitations->addColumn('created_at', 'datetime', [
            'null' => false,
            'default' => Literal::from('CURRENT_TIMESTAMP'),
        ]);
        $invitations->addColumn('updated_at', 'datetime', [
            'null' => false,
            'default' => Literal::from('CURRENT_TIMESTAMP'),
        ]);
        $invitations->addColumn('deleted_at', 'datetime', [
            'null' => true,
        ]);

        $invitations->addForeignKey('intended_for', 'users', ['id']);

        // to ensure only one admin of an organization can create an invitation to the same user
        $invitations->addForeignKey([
            'organization_id',
            'created_by',
        ], 'organization_admins', [
            'organization_id',
            'user_id',
        ]);

        $invitations->addIndex('organization_id', [
            'unique' => false,
            'name' => 'idx_invitations_organization_id',
        ]);

        // to ensure that a user can only be invited to an organization once
        $invitations->addIndex(['organization_id', 'intended_for'], [
            'unique' => true,
            'name' => 'unique_organization_invitation_intended_for',
        ]);

        $invitations->addIndex('intended_for', [
            'unique' => false,
            'name' => 'idx_invitations_intended_for',
        ]);

        $invitations->addIndex(['deleted_at'], [
            'unique' => false,
            'name' => 'idx_invitations_deleted_at',
        ]);

        $invitations->create();
    }
}
