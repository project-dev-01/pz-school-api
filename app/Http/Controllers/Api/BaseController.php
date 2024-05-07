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
        try{
            $response = [
                'code' => 200,
                'success' => true,
                'message' => $message,
                'data'    => $result,
            ];
            return response()->json($response, 200);
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in successResponse');
        }
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function send404Error($error, $errorMessages = [], $code = 404)
    {
        try{
            $response = [
                'code' => 404,
                'success' => false,
                'message' => $error,
            ];

            if (!empty($errorMessages)) {
                $response['data'] = $errorMessages;
            }

            return response()->json($response, $code);
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in send404Error');
        }
    }

    public function send500Error($error, $errorMessages = [], $code = 500)
    {
        try{
            $response = [
                'code' => 500,
                'success' => false,
                'message' => $error,
            ];

            if (!empty($errorMessages)) {
                $response['data'] = $errorMessages;
            }

            return response()->json($response, $code);
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in send500Error');
        }
    }
    public function send400Error($error, $errorMessages = [], $code = 400)
    {
        try{
            $response = [
                'code' => 400,
                'success' => false,
                'message' => $error,
            ];

            if (!empty($errorMessages)) {
                $response['data'] = $errorMessages;
            }

            return response()->json($response, $code);
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in send400Error');
        }
    }

    public function send422Error($error, $errorMessages = [], $code = 422)
    {
        try{
            $response = [
                'code' => 422,
                'success' => false,
                'message' => $error,
            ];

            if (!empty($errorMessages)) {
                $response['data'] = $errorMessages;
            }

            return response()->json($response, $code);
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in send422Error');
        }
    }
    // unauthorized
    public function send401Error($error, $errorMessages = [], $code = 401)
    {
        try{
            $response = [
                'code' => 401,
                'success' => false,
                'message' => $error,
            ];

            if (!empty($errorMessages)) {
                $response['data'] = $errorMessages;
            }

            return response()->json($response, $code);
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in send401Error');
        }
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
            Artisan::call(
                'db:seed',
                [
                    '--class' => 'DatabaseSeederDynamic',
                    '--database' => 'mysql_new_connection'
                ]
            );
            return true;
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in DBMigrationCall');
        }
    }
    // create users
    function createUser(Request $request, $lastInsertID, $Staffid)
    {
        try{
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in createUser');
        }
            
    }
    // check users email exit 
    function existUser($email)
    {
        try{
            if (User::where('email', '=', $email)->count() > 0) {
                return false;
            } else {
                return true;
            }
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in existUser');
        }
    }
    // check users email exit with branch
    function existUserWithBranch($email,$branch)
    {
        try{
            if (User::where([['email', '=', $email], ['branch_id', '=', $branch]])->count() > 0) {
                return false;
            } else {
                return true;
            }
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in existUserWithBranch');
        }
    }
    // check users email exit 
    function existBranch($email)
    {
        try{
            if (Branches::where('email', '=', $email)->count() > 0) {
                return false;
            } else {
                return true;
            }
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in existBranch');
        }
    }
    // create new connection
    function createNewConnection($branch_id)
    {
        try {
            $params = Branches::select('id', 'db_name', 'db_username', 'db_password', 'db_port', 'db_host')->where('id', $branch_id)->first();
            if ($params != null) {
                // dd($params);
                $staffConn = DatabaseConnection::setConnection($params);
                return $staffConn;
            } else {
                return $this->send404Error('Error While Connecting DB.', ['error' => 'Error While Connecting DB']);
            }
        } catch (Exception $e) {
            return $this->send404Error('No Data Found.', ['error' => $e->getMessage()]);
        }
    }
    // upload user profile
    function uploadUserProfile(Request $request)
    {
        try{
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in uploadUserProfile');
        }
    }
    public function sendCommonError($error, $errorMessages = [], $code = 503)
    {
        try{
            $response = [
                'code' => 503,
                'success' => false,
                'message' => $error,
            ];

            if (!empty($errorMessages)) {
                $response['data'] = $errorMessages;
            }

            return response()->json($response, $code);
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in sendCommonError');
        }
    }
}
