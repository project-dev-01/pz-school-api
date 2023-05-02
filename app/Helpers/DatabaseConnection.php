<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DatabaseConnection
{
    public static function setConnection($params)
    {
        config(['database.connections.tenant' => [
            'driver'    => 'mysql',
            'host'      => $params->db_host,
            'port'      => $params->db_port,
            'database'  => $params->db_name,
            'username'  => $params->db_username,
            'password'  => $params->db_password,
            'charset'   => 'utf8',
        ]]);
        return DB::connection('tenant');
    }
    public static function databaseMigrate($params)
    {
        config(['database.connections.mysql_new_connection' => [
            'driver'    => 'mysql',
            'host'      => $params->db_host,
            'port'      => $params->db_port,
            'database'  => $params->db_name,
            'username'  => $params->db_username,
            'password'  => $params->db_password,
            'charset'   => 'utf8',
        ]]);
        Artisan::call('migrate',
        array(
        '--path' => 'database/migrations/dynamic_migrate',
        '--database' => 'mysql_new_connection',
        '--force' => true));
        return true;

    }
    
}
