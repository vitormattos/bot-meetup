<?php

use Phinx\Migration\AbstractMigration;

class Init extends AbstractMigration
{
    public function change()
    {
        $this->table('user')
            ->addColumn('token', 'string')
            ->addColumn('telegram_id', 'integer')
            ->addColumn('md5token', 'string', ['length' => 32])
            ->addTimestamps()
            ->addIndex(['telegram_id'], ['unique' => true])
            ->save();
    }
}
