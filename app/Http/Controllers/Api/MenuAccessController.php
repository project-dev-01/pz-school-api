<?php

namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
//
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
// base controller add
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Validation\Rule;
use DateTime;
use DateInterval;
use DatePeriod;
use App\Models\Branches;
use App\Models\Section;
use App\Helpers\Helper;
use App\Models\Classes;
use App\Models\Role;
use App\Models\EventType;
use App\Models\User;
use App\Models\Menus;
use App\Models\Menuaccess;
// db connection
use App\Models\Forum_posts;
use App\Models\Forum_count_details;
use App\Models\Forum_post_replies;
use Carbon\Carbon;
use App\Models\Forum_post_replie_counts;
use Illuminate\Support\Arr;
// notifications
use App\Notifications\LeaveApply;
use Illuminate\Support\Facades\Notification;
// encrypt and decrypt
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use File;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class MenuAccessController extends BaseController
{
    //
    public function getRoles(Request $request)
    {
        if($request->status=='All')			
		{$data = Role::get();}
		else
		{$data = Role::where('status', $request->status)->get();}
	
        return $this->successResponse($data, 'Section record fetch successfully');
    }
	 //
    public function addMenu(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            
            'menu_name' => 'required',
            'menu_type' => 'required',
            'menu_url' => 'required'
        ]);
        //dd('success');
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } 
        else {
                // insert data
                $query = Menus::insert([
                    'role_id' => $request->role_id,
                    'menu_name' => $request->menu_name,
                    'menu_type' => $request->menu_type,
                    'menu_icon' => $request->menu_icon,
                    'menu_refid' => $request->menu_refid,
                    'menu_url' => $request->menu_url,
                    'menu_routename' => $request->menu_routename,
                    'menu_status' => $request->menu_status,
                    'menu_dropdown' => $request->menu_dropdown,
                    'created_at' => date("Y-m-d H:i:s")
                ]);               
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'New Menu has been successfully saved');
                }
            
        }
    }
    public function updateMenuDetails(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            
            'menu_name' => 'required',
            'menu_type' => 'required',
            'menu_url' => 'required'
        ]);
        //dd('success');
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } 
        else {
                // Update data
                $query =  Menus::where('menu_id', $request->menu_id)
            ->update([
                    'role_id' => $request->role_id,
                    'menu_name' => $request->menu_name,
                    'menu_type' => $request->menu_type,
                    'menu_icon' => $request->menu_icon,
                    'menu_refid' => $request->menu_refid,
                    'menu_url' => $request->menu_url,
                    'menu_routename' => $request->menu_routename,
                    'menu_status' => $request->menu_status,
                    'menu_dropdown' => $request->menu_dropdown,
                    'updated_at' => date("Y-m-d H:i:s")
                ]); 
            
                 
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'New Menu has been successfully Updated');
                }
            
        }
    }

    public function getMenuList(Request $request)
    {
        if(isset($request->type))			
		{$data = Menus::where('menu_type', $request->type)->orderBy("role_id", "asc")->get();}
		else
		{$data = Menus::All();}
	
	
		//dd($data);
        return $this->successResponse($data, 'Menus fetch successfully');
    }
    public function getMenuAccessList(Request $request)
    {
        $br_id =$request->br_id;
        /* $data = DB::table('menus AS t1')
        ->select('t1.*', 't2.menu_permission','t2.id as menuaccess_id')
        ->leftJoin('menuaccess AS t2', 't1.menu_id', '=', 't2.menu_id')
        ->where('t1.menu_type', $request->type)->where('t1.role_id', $request->role_id)->orderBy("t1.role_id", "asc")->get();
        */
        $br_id = $request->br_id;
        $data = Menus::select('menus.*')
            ->selectSub(function ($query) use ($br_id) {
                $query->select('menu_permission')
                    ->from('menuaccess')
                    ->where('branch_id', $br_id)
                    ->whereColumn('menus.menu_id', 'menuaccess.menu_id')
                    ->limit(1);
            }, 'menu_permission')
            ->selectSub(function ($query) use ($br_id) {
                $query->select('id')
                    ->from('menuaccess')
                    ->where('branch_id', $br_id)
                    ->whereColumn('menus.menu_id', 'menuaccess.menu_id')
                    ->limit(1);
            }, 'menuaccess_id')
            ->where('menu_type', $request->type)
            ->where('role_id', $request->role_id)
            ->orderBy("role_id", "asc")
            ->get();
       // dd($data);
        return $this->successResponse($data, 'Menus fetch successfully');
    }
    public function getmenupermission(Request $request)
    {
        $br_id =$request->br_id;        
        $role_id = $request->role_id;
        $menu_id = $request->menu_id;        
       /* $data = Menuaccess::select('menu_permission')
                ->where('branch_id', $br_id)
                ->where('role_id', $role_id)
                ->where('menu_id', $menu_id)
                ->first();*/
       // dd($data);
       $data = DB::table('menuaccess AS t1')
    ->select('t1.menu_permission')
    ->leftJoin('menus AS t2', 't1.menu_id', '=', 't2.menu_id')
    ->where('t2.menu_routename', $menu_id)
    ->where('t1.role_id', $role_id)
    ->where('t1.branch_id', $br_id)
    ->first();
        return $this->successResponse($data, 'Get Menus Permission   successfully');
    }
    public function setmenupermission(Request $request)
    {
    
        //dd($request->branch_id);
         // insert data
        foreach($request->menu_id as $menuid)
        {
            
            if($request->act[$menuid]=='Insert')
            {
                $query = Menuaccess::insert([
                    'role_id' => $request->role_id,
                    'branch_id' => $request->br_id,
                    //'branch_id' => 4,
                    'menu_id' => $menuid,
                    'menu_permission' => $request->accessdenied[$menuid],
                    'created_at' => date("Y-m-d H:i:s")
                ]); 
            }
            else
            {
                $query = Menuaccess::where('id',$request->menuaccess_id[$menuid])->update([
                    'menu_permission' => $request->accessdenied[$menuid],
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            }
        } 

        $success = [];
        if (!$query) {
            return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
        } else {
            return $this->successResponse($success, 'Menu Access Permission Assigned has been successfully saved');
        }
            
        
    }

    public function getMenuDetails(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'id' => 'required',
            'token' => 'required',
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $id = $request->id;
            $MenuDetails = Menus::where('menu_id', $id)->first();
            return $this->successResponse($MenuDetails, 'Menu row fetch successfully');
        }
    }
    public function addschool_role(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'fullname' => 'required',
            'shortname' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($conn->table('school_roles')->where('fullname', '=', $request->fullname)->count() > 0) {
                return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            } else {
                // insert data
                $query = $conn->table('school_roles')->insert([
                    'portal_roleid' => $request->portal_roleid,
                    'fullname' => $request->fullname,
                    'shortname' => $request->shortname,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'School Role has been successfully saved');
                }
            }
        }
    }
    // getEventTypeList
    public function getschool_roleList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            //'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
           // $conn = $this->createNewConnection($request->branch_id);
            // get data
            //$schoolroleDetails = $conn->table('school_roles')->get();
            $main_db = config('constants.main_db');
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
        $schoolroleDetails =$conn->table('school_roles as t1')
        ->select('t1.*', 't2.role_name', DB::raw('(SELECT DISTINCT role_id FROM school_menuaccess AS t3 WHERE t3.school_roleid = t1.id LIMIT 1) AS roles'))
        ->leftJoin($main_db.'.roles AS t2', 't1.portal_roleid', '=', 't2.id')->get();
     
            return $this->successResponse($schoolroleDetails, 'School Role record fetch successfully');
        }
    }
    // get EventType row details
    public function getschool_roleDetails(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'id' => 'required',
            'branch_id' => 'required',
            'token' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $id = $request->id;
          
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
       
            $schoolroleDetails = $conn->table('school_roles')->where('id', $id)->first();
            return $this->successResponse($schoolroleDetails, 'School Role row fetch successfully');
        }
    }
    // update EventType
    public function updateschool_role(Request $request)
    {
        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'fullname' => 'required',
            'shortname' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($conn->table('school_roles')->where([['fullname', '=', $request->fullname], ['id', '!=', $id]])->count() > 0) {
                return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            } else {
                // update data
                $query = $conn->table('school_roles')->where('id', $id)->update([
                    'portal_roleid' => $request->portal_roleid,
                    'fullname' => $request->fullname,
                    'shortname' => $request->shortname,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'School Role Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        }
    }
    // delete EventType
    public function deleteschool_role(Request $request)
    {

        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            $query = $conn->table('school_roles')->where('id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'School Role have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    public function getschoolmenuaccesslist(Request $request)
    {
        
        /* $data = DB::table('menus AS t1')
        ->select('t1.*', 't2.menu_permission','t2.id as menuaccess_id')
        ->leftJoin('menuaccess AS t2', 't1.menu_id', '=', 't2.menu_id')
        ->where('t1.menu_type', $request->type)->where('t1.role_id', $request->role_id)->orderBy("t1.role_id", "asc")->get();
        */
        $br_id = $request->br_id;
        $scrole_id = $request->scrole_id;
        $main_db = config('constants.main_db');
        
        $conn = $this->createNewConnection($request->br_id);
        // get data
        //$query = $conn->table('school_roles')->where('id', $id)->delete();

        $data =  $conn->table($main_db.'.menus as ms')->select('ms.*')
            ->selectSub(function ($query) use ($scrole_id) {
                $query->select('read')
                    ->from('school_menuaccess')
                    ->where('school_roleid', $scrole_id)
                    ->whereColumn('ms.menu_id', 'school_menuaccess.menu_id')
                    ->limit(1);
            }, 'menu_read')           
            ->selectSub(function ($query) use ($scrole_id) {
                $query->select('add')
                    ->from('school_menuaccess')
                    ->where('school_roleid', $scrole_id)
                    ->whereColumn('ms.menu_id', 'school_menuaccess.menu_id')
                    ->limit(1);
            }, 'menu_add')
            ->selectSub(function ($query) use ($scrole_id) {
                $query->select('updates')
                    ->from('school_menuaccess')
                    ->where('school_roleid', $scrole_id)
                    ->whereColumn('ms.menu_id', 'school_menuaccess.menu_id')
                    ->limit(1);
            }, 'menu_update') 
            ->selectSub(function ($query) use ($scrole_id) {
                $query->select('deletes')
                    ->from('school_menuaccess')
                    ->where('school_roleid', $scrole_id)
                    ->whereColumn('ms.menu_id', 'school_menuaccess.menu_id')
                    ->limit(1);
            }, 'menu_delete')
            ->selectSub(function ($query) use ($scrole_id) {
                $query->select('export')
                    ->from('school_menuaccess')
                    ->where('school_roleid', $scrole_id)
                    ->whereColumn('ms.menu_id', 'school_menuaccess.menu_id')
                    ->limit(1);
            }, 'menu_export')
            ->selectSub(function ($query) use ($scrole_id) {
                $query->select('id')
                    ->from('school_menuaccess')
                    ->where('school_roleid', $scrole_id)
                    ->whereColumn('ms.menu_id', 'school_menuaccess.menu_id')
                    ->limit(1);
            }, 'menuaccess_id')
            ->leftJoin($main_db.'.menuaccess as ma', 'ma.menu_id', '=', 'ms.menu_id')
            ->where('ma.menu_permission', 'Access')
            ->where('ma.branch_id', $br_id)
            ->where('ms.menu_type', $request->type)
            ->where('ms.role_id', $request->role_id) 
            ->orderBy("ms.menu_id", "asc")
            ->get();
       // dd($data);
        return $this->successResponse($data, 'Menus fetch successfully');
    }
    public function setschoolpermission(Request $request)
    {
    
        $conn = $this->createNewConnection($request->br_id);
        
        foreach($request->menu_id as $menuid)
        {
            
            if($request->act[$menuid]=='Insert')
            {
                $query = $conn->table('school_menuaccess')->insert([
                    'role_id' => $request->role_id,
                    'school_roleid' => $request->school_roleid,
                    //'branch_id' => 4,
                    'menu_id' => $menuid,
                    'read' => @$request->read[$menuid],                      
                    'add' => @$request->add[$menuid],                  
                    'updates' => @$request->updates[$menuid],                   
                    'deletes' => @$request->deletes[$menuid],                    
                    'export' => @$request->export[$menuid],
                    'created_at' => date("Y-m-d H:i:s")
                ]); 
                
            }
            else
            {
                $query = $conn->table('school_menuaccess')->where('id',$request->menuaccess_id[$menuid])->update([
                    'read' => @$request->read[$menuid],                      
                    'add' => @$request->add[$menuid],                  
                    'updates' => @$request->updates[$menuid],                   
                    'deletes' => @$request->deletes[$menuid],                    
                    'export' => @$request->export[$menuid],                    
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                
            }

        }         
        $success = [];
        if (!$query) {
            return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
        } else {
            return $this->successResponse($success, 'Menu Access Permission Assigned has been successfully saved');
        }
            
        
    }
    public function getschoolroleaccess(Request $request)
    {
        $menu_id = $request->menu_id;
        $role_id = $request->role_id;
        $school_roleid = $request->school_roleid;
        // create new connection
        $conn = $this->createNewConnection($request->branch_id);       
        $data = Menus::select('menu_id')->where('menu_routename', $menu_id)->where('role_id', $role_id)->first();
        
        $menu_id1=$data['menu_id'];
        //dd($menu_id1);
        $schoolroleDetails = $conn->table('school_menuaccess')->where('menu_id', $menu_id1)->where('role_id', $role_id)->where('school_roleid', $school_roleid)->first();
        return $this->successResponse($schoolroleDetails, 'School Role row fetch successfully');
        
    }   
    
}
