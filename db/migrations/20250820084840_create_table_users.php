<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTableUsers extends AbstractMigration
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
        $users = $this->table('users');
        $users->addColumn('email_hash', 'string', [
            'limit' => 64,
            'null' => false,
            'comment' => 'Deterministic hash of email for fast lookups'
        ]);
        $users->addColumn('email_encrypted', 'text', [
            'null' => false,
            'comment' => 'Encrypted email address for communication purposes'
        ]);
        $users->addColumn('encryption_version', 'integer', [
            'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
            'null' => false,
            'default' => 1,
            'comment' => 'Encryption version for future compatibility'
        ]);
        $users->addColumn('email_verified', 'boolean', [
            'null' => false,
            'default' => false,
        ]);
        $users->addColumn('username', 'string', [
            'limit' => 255,
            'null' => false,
        ]);
        $users->addColumn('created_at', 'datetime', [
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
        ]);
        $users->addColumn('updated_at', 'datetime', [
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
            'update' => 'CURRENT_TIMESTAMP',
        ]);
        $users->addColumn('deleted_at', 'datetime', [
            'null' => true,
            'default' => null,
        ]);
        
        // Email hash lookup index - primary method for finding users by email
        $users->addIndex(['email_hash'], [
            'unique' => true,
            'name' => 'idx_users_email_hash',
        ]);
        
        // Soft delete support - exclude deleted users from queries
        $users->addIndex(['deleted_at'], [
            'unique' => false,
            'name' => 'idx_users_deleted_at',
        ]);
        
        $users->create();
    }
}
