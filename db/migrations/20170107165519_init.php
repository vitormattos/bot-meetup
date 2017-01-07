<?php

use Phinx\Migration\AbstractMigration;

class Init extends AbstractMigration
{
    public function change()
    {
        $this->table('userdata')
            ->addColumn('token', 'string', ['null' => true])
            ->addColumn('telegram_id', 'integer')
            ->addColumn('oauth2state', 'string', ['length' => 32, 'null' => true])
            ->addTimestamps()
            ->addIndex(['telegram_id'], ['unique' => true])
            ->save();
    }
}
