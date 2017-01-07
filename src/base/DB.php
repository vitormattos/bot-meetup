<?php
namespace Base;
use Aura\Sql\ExtendedPdo;
class DB
{
    private static $db;
    /**
     * @return ExtendedPdo
     */
    public static function getInstance()
    {
        if(self::$db) {
            return self::$db;
        }
        $dbopts = parse_url(getenv('DATABASE_URL'));
        if($dbopts['scheme'] == 'postgres') {
            $dbopts['scheme'] = 'pgsql';
        }
        self::$db = new ExtendedPdo(
            "{$dbopts['scheme']}:host={$dbopts["host"]};port={$dbopts["port"]};dbname=".ltrim($dbopts["path"],'/'),
            $dbopts['user'],
            $dbopts['pass']
        );
        return self::$db;
    }
}
