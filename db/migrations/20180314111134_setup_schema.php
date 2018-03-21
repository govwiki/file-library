<?php

use Phinx\Migration\AbstractMigration;

/**
 * Class SetupSchema
 */
class SetupSchema extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     *
     * @return void
     */
    public function change()
    {
        $this->table('users')
            ->addColumn('username', 'string')
            ->addColumn('password', 'string')
            ->addColumn('first_name', 'string')
            ->addColumn('last_name', 'string')
            ->create();

        $this->table('documents', [ 'id' => false, 'primary_key' => 'path' ])
            ->addColumn('slug', 'string')
            ->addColumn('name', 'string')
            ->addColumn('type', 'string')
            ->addColumn('state', 'string', [ 'limit' => 2 ])
            ->addColumn('year', 'integer')
            ->addColumn('path', 'string')
            ->addColumn('file_size', 'integer')
            ->addColumn('uploaded_at', 'datetime')
            ->addColumn('uploaded_by_id', 'integer', [ 'null' => true, 'limit' => 11 ])
            ->addForeignKey('uploaded_by_id', 'users', 'id', [
                'delete' => 'SET_NULL',
            ])
            ->create();
    }
}
