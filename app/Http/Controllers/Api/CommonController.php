<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\Branches;
use App\Models\Cities;
use App\Models\Countries;
use App\Models\States;
use App\Models\User;

// db connection
use App\Helpers\DatabaseConnection;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\FuncCall;

class CommonController extends BaseController
{
    //
    // get sections 
    public function countryList()
    {
        try{
            $success = Countries::all();
            return $this->successResponse($success, 'Countries record fetch successfully');
        } catch (\Exception $e) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in countryList');
        }
    }
    // get states by country id
    public function getStateByIdList(Request $request)
    {
        try{
            $validator = \Validator::make($request->all(), [
                'country_id' => 'required',
            ]);
    
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $success = States::where('country_id', $request->country_id)->get();
                return $this->successResponse($success, 'States record fetch successfully');
            }
        } catch (\Exception $e) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getStateByIdList');
        }
        
    }
    // get citites by state id
    public function getCityByIdList(Request $request)
    {
        try{
            $validator = \Validator::make($request->all(), [
                'state_id' => 'required',
            ]);
    
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $success = Cities::where('state_id', $request->state_id)->get();
                return $this->successResponse($success, 'Citites record fetch successfully');
            }
        } catch (\Exception $e) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getCityByIdList');
        }
        
    }
    function databaseMigrate(Request $request)
    {
        try{
        $params =  Branches::select('id', 'db_name', 'db_username', 'db_password', 'db_port', 'db_host')->where('id', $request->branch_id)->first();
        $staffConn = DatabaseConnection::databaseMigrate($params);
        return $this->successResponse([], 'Migrated successfully');
        } catch (\Exception $e) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in databaseMigrate');
        }
    }
    function indexingMigrate(Request $request)
    {
        try{
        $params =  Branches::select('id', 'db_name', 'db_username', 'db_password', 'db_port', 'db_host')->where('id', $request->branch_id)->first();
        $staffConn = DatabaseConnection::indexingMigrate($params);
        return $this->successResponse([], 'Migrated successfully');
        } catch (\Exception $e) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in indexingMigrate');
        }
    }
    public function categoryList(Request $request)
    {
        try{
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection              
            $success = DB::table('forum_categorys')->where('branch_id', $request->branch_id)->get();
            $success = Category::all();
            return $this->successResponse($success, 'category record fetch successfully');
        }
        } catch (\Exception $e) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in categoryList');
        }
    }
    public function dbnameslist(Request $request)
    {
        try{
        $validator = \Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection              
            $success = DB::table('branches')->select('school_name', 'id')->get();

            return $this->successResponse($success, 'school db names fetch successfully');
        }
        } catch (\Exception $e) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in dbnameslist');
        }
    }
    public function fistLastScript(Request $request)
    {
        try{
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'table_name' => 'required',
            'role_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $details = $Connection->table($request->table_name)
                ->get();
            foreach ($details as $val) {
                $id = $val->id;
                $email = $val->email;
                $firstName = $val->first_name;
                $lastName = $val->last_name;
                $name = $lastName . " " . $firstName;
                $data = [
                    'first_name' => $lastName,
                    'last_name' => $firstName
                ];
                $Connection->table($request->table_name)->where('id', $id)->update($data);

                // $usersDetails = User::where([['user_id', '=', $id], ['role_id', '=', $request->role_id], ['branch_id', '=', $request->branch_id]])
                //             ->get();
                // ->orWhere('name', 'like', '%' . Input::get('name') . '%')
                // $query = User::where([['user_id', '=', $id], ['role_id', '=', $request->role_id], ['branch_id', '=', $request->branch_id]])
                // // $query = User::where([['user_id', '=', $id], ['branch_id', '=', $request->branch_id]])
                // // ->orWhere('role_id', 'like', '%' . Input::get('name') . '%')    
                // ->update([
                //         'name' => $name
                //     ]);
                $query = User::where([['email', '=', $email],  ['branch_id', '=', $request->branch_id]])
                ->update([
                        'name' => $name
                    ]);
                // print_r($data);
                // echo "count no of names". count($usersDetails);
                // User
            }
            return $this->successResponse([], 'reverse update record successfully');
        }
        } catch (\Exception $e) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in fistLastScript');
        }
    }
    public function basesixfour(Request $request)
    {
        try{
        $fontFile = public_path('common-asset\IPAexGothic\ipag_bold.ttf'); // Replace 'your-font-file.ttf' with the actual file name and path
        
        $fontData = file_get_contents($fontFile);
        $base64FontData = base64_encode($fontData);
        
        echo $base64FontData;
        } catch (\Exception $e) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in basesixfour');
        }
    }
}
