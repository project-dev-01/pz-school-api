<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\Branches;
use App\Models\Cities;
use App\Models\Countries;
use App\Models\States;
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
        $success = Countries::all();
        return $this->successResponse($success, 'Countries record fetch successfully');
    }
    // get states by country id
    public function getStateByIdList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'country_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $success = States::where('country_id', $request->country_id)->get();
            return $this->successResponse($success, 'States record fetch successfully');
        }
    }
    // get citites by state id
    public function getCityByIdList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'state_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $success = Cities::where('state_id', $request->state_id)->get();
            return $this->successResponse($success, 'Citites record fetch successfully');
        }
    }
    function databaseMigrate(Request $request){
       
        $params =  Branches::select('id','db_name','db_username','db_password','db_port','db_host')->where('id',$request->branch_id)->first();
        $staffConn = DatabaseConnection::databaseMigrate($params);
        return $this->successResponse([], 'Migrated successfully');

    }
    public function categoryList(Request $request)
    {
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
    }
    public function dbnameslist(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
               // create new connection              
            $success = DB::table('branches')->select('school_name','id')->get();
            
            return $this->successResponse($success, 'school db names fetch successfully');
        }  
    }
}
