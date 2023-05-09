<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branches;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
// db connection
use App\Helpers\DatabaseConnection;

class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function successResponse($result, $message)
    {
        $response = [
            'code' => 200,
            'success' => true,
            'message' => $message,
            'data'    => $result,
        ];
        return response()->json($response, 200);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function send404Error($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'code' => 404,
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    public function send500Error($error, $errorMessages = [], $code = 500)
    {
        $response = [
            'code' => 500,
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
    public function send400Error($error, $errorMessages = [], $code = 400)
    {
        $response = [
            'code' => 400,
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    public function send422Error($error, $errorMessages = [], $code = 422)
    {
        $response = [
            'code' => 422,
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
    // unauthorized
    public function send401Error($error, $errorMessages = [], $code = 401)
    {
        $response = [
            'code' => 401,
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
    // create migration file
    function DBMigrationCall($dbName, $dbUsername, $dbPass, $dbPort, $dbHost)
    {

        try {
            config(['database.connections.mysql_new_connection' => [
                'driver'    => 'mysql',
                'host'      => $dbHost,
                'port'      => $dbPort,
                'database'  => trim($dbName),
                'username'  => $dbUsername,
                'password'  => $dbPass,
                'charset'   => 'utf8',
                // 'collation' => 'utf8_unicode_ci'
            ]]);
    
            Artisan::call(
                'migrate',
                array(
                    '--path' => 'database/migrations/dynamic_migrate',
                    '--database' => 'mysql_new_connection',
                    '--force' => true
                )
            );
            Artisan::call('db:seed',
                [
                    '--class' => 'DatabaseSeederDynamic',
                    '--database' => 'mysql_new_connection'
                ]
            );
            return true;
        } catch (\Exception $e) {
             return false;
        }
    }
    // create users
    function createUser(Request $request, $lastInsertID, $Staffid)
    {
        $user = new User();
        $user->name = $request->first_name . " " . $request->last_name;
        $user->email = $request->email;
        $user->role_id = 2;
        $user->user_id = $Staffid;
        $user->password = \Hash::make($request->password);
        $user->branch_id = $lastInsertID;
        $query = $user->save();
        if (!$query) {
            return false;
        } else {
            return true;
        }
    }
    // check users email exit 
    function existUser($email)
    {
        if (User::where('email', '=', $email)->count() > 0) {
            return false;
        } else {
            return true;
        }
    }
    // check users email exit 
    function existBranch($email)
    {
        if (Branches::where('email', '=', $email)->count() > 0) {
            return false;
        } else {
            return true;
        }
    }
    // create new connection
    function createNewConnection($branch_id)
    {
        $params = Branches::select('id','db_name','db_username','db_password','db_port','db_host')->where('id',$branch_id)->first();
        $staffConn = DatabaseConnection::setConnection($params);
        return $staffConn;
    }
    // upload user profile
    function uploadUserProfile(Request $request)
    {
        $path = 'users/images/';
        $file = $request->file('photo');
        $new_name = 'UIMG_' . date('Ymd') . uniqid() . '.jpg';

        //Upload new image
        $upload = $file->move(public_path($path), $new_name);

        if (!$upload) {
            return null;
        } else {
            if (!$upload) {
                return null;
            } else {
                return $new_name;
            }
        }
    }
}
