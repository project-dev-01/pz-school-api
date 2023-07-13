<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Exception;

class DatabaseConnection
{
    public static function setConnection($params)
    {
        try {
            config(['database.connections.tenant' => [
                'driver'    => 'mysql',
                'host'      => $params->db_host,
                'port'      => $params->db_port,
                'database'  => $params->db_name,
                'username'  => $params->db_username,
                'password'  => $params->db_password,
                'charset'   => 'utf8',
            ]]);
            // Config::set('app.timezone', "Asia/Kolkata");
            // // // Optionally, you can also update the timezone for the current request
            // date_default_timezone_set('Asia/Kolkata');
            return DB::connection('tenant');
        } catch (Exception $e) {
            return $this->sendCommonError('No Data Found.', ['error' => $e->getMessage()]);
        }
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
        Artisan::call(
            'migrate',
            array(
                '--path' => 'database/migrations/dynamic_migrate',
                '--database' => 'mysql_new_connection',
                '--force' => true
            )
        );
        return true;
    }
    public static function indexingMigrate($params)
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
        Artisan::call(
            'migrate',
            array(
                '--path' => 'database/migrations/indexing_migrate',
                '--database' => 'mysql_new_connection',
                '--force' => true
            )
        );
        return true;
    }
    public function sendCommonError($error, $errorMessages = [], $code = 503)
    {
        $response = [
            'code' => 503,
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
}
