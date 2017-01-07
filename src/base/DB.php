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
            return $db;
        }
        $dbopts = parse_url(getenv('DATABASE_URL'));
        self::$db = new ExtendedPdo(
            "pgsql:host={$dbopts["host"]};port={$dbopts["port"]};dbname=".ltrim($dbopts["path"],'/'),
            $dbopts['user'],
            $dbopts['pass']
        );
        return self::$db;
    }
}
