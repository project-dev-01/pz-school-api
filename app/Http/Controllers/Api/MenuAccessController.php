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
use Illuminate\Support\Facades\Cache;
use App\Helpers\CommonHelper;

class MenuAccessController extends BaseController
{
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper)
    {
        $this->commonHelper = $commonHelper;
    }
    //
    public function getRoles(Request $request)
    {
        try {
            if ($request->status == 'All') {
                $data = Role::get();
            } else {
                $data = Role::where('status', $request->status)->get();
            }

            return $this->successResponse($data, 'Section record fetch successfully');
        } catch (Exception $error) {
            return  $this->commonHelper->generalReturn('403', 'error', $error, 'getRoles');
        }
    }
    //
    public function addMenu(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [

                'menu_name' => 'required',
                'menu_type' => 'required',
                'menu_url' => 'required'
            ]);
            //dd('success');
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
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
                    'menu_order' => $request->menu_order,
                    'menu_dropdown' => $request->menu_dropdown,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'New Menu Information has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'addMenu');
        }
    }
    public function updateMenuDetails(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [

                'menu_name' => 'required',
                'menu_type' => 'required',
                'menu_url' => 'required'
            ]);
            //dd('success');
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
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
                        'menu_order' => $request->menu_order,
                        'menu_dropdown' => $request->menu_dropdown,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);


                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Menu Information has been Updated successfully ');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'updateMenuDetails');
        }
    }

    public function getMenuList(Request $request)
    {
        try {
            if (isset($request->type)) {
                $data = Menus::where('menu_type', $request->type)->orderBy("role_id", "asc")->get();
            } else {
                $data = Menus::All();
            }


            //dd($data);
            return $this->successResponse($data, 'Menu Informations get successfully');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getMenuList');
        }
    }
    // public function getMenuAccessList(Request $request)
    // {
    //     $br_id = $request->br_id;
    //     /* $data = DB::table('menus AS t1')
    //     ->select('t1.*', 't2.menu_permission','t2.id as menuaccess_id')
    //     ->leftJoin('menuaccess AS t2', 't1.menu_id', '=', 't2.menu_id')
    //     ->where('t1.menu_type', $request->type)->where('t1.role_id', $request->role_id)->orderBy("t1.role_id", "asc")->get();
    //     */
    //     $br_id = $request->br_id;
    //     $data = Menus::select('menus.*')
    //         ->selectSub(function ($query) use ($br_id) {
    //             $query->select('menu_permission')
    //                 ->from('menuaccess')
    //                 ->where('branch_id', $br_id)
    //                 ->whereColumn('menus.menu_id', 'menuaccess.menu_id')
    //                 ->limit(1);
    //         }, 'menu_permission')
    //         ->selectSub(function ($query) use ($br_id) {
    //             $query->select('id')
    //                 ->from('menuaccess')
    //                 ->where('branch_id', $br_id)
    //                 ->whereColumn('menus.menu_id', 'menuaccess.menu_id')
    //                 ->limit(1);
    //         }, 'menuaccess_id')
    //         ->where('menu_type', $request->type)
    //         ->where('role_id', $request->role_id)
    //         ->where('flog', '0')
    //         ->orderBy("menu_order", "asc")
    //         ->get();
    //     // dd($data);
    //     return $this->successResponse($data, 'Menus fetch successfully');
    // }
    public function getMenuAccessList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'br_id' => 'required',
                'type' => 'required',
                'role_id' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $br_id = $request->br_id;
                $type = $request->type;
                $role_id = $request->role_id;
                $cacheKey = config('constants.cache_get_access_menu_list') . $br_id . $role_id . $type;
                $cache_time = config('constants.cache_time');
                // Check if the data is cached
                if (Cache::has($cacheKey)) {
                    // If cached, return cached data
                    $get_access_menu_list = Cache::get($cacheKey);
                } else {
                    $get_access_menu_list = Menus::select('menus.*')
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
                        ->where('flog', '0')
                        ->orderBy("menu_order", "asc")
                        ->get();
                    Cache::put($cacheKey, $get_access_menu_list, now()->addHours($cache_time)); // Cache for 24 hours 
                }

                return $this->successResponse($get_access_menu_list, 'Menus fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getMenuAccessList');
        }
    }
    public function getmenupermission(Request $request)
    {
        try {
            $br_id = $request->br_id;
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
        } catch (Exception $error) {
            return  $this->commonHelper->generalReturn('403', 'error', $error, 'getmenupermission');
        }
    }
    public function setmenupermission(Request $request)
    {
        try {
            //dd($request->branch_id);
            // insert data
            foreach ($request->menu_id as $menuid) {

                if ($request->act[$menuid] == 'Insert') {
                    $query = Menuaccess::insert([
                        'role_id' => $request->role_id,
                        'branch_id' => $request->br_id,
                        //'branch_id' => 4,
                        'menu_id' => $menuid,
                        'menu_permission' => $request->accessdenied[$menuid],
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                } else {
                    $query = Menuaccess::where('id', $request->menuaccess_id[$menuid])->update([
                        'menu_permission' => $request->accessdenied[$menuid],
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                }
            }

            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Menu Access Permission Assigned has been Updated successfully ');
            }
        } catch (Exception $error) {
            return  $this->commonHelper->generalReturn('403', 'error', $error, 'getmenupermission');
        }
    }

    public function getMenuDetails(Request $request)
    {
        try {
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
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getMenuDetails');
        }
    }
    public function addschool_role(Request $request)
    {
        try {
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
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'addschool_role');
        }
    }
    // getEventTypeList
    public function getschool_roleList(Request $request)
    {
        try {
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
                $schoolroleDetails = $conn->table('school_roles as t1')
                    ->select('t1.*', 't2.role_name', DB::raw('(SELECT DISTINCT role_id FROM school_menuaccess AS t3 WHERE t3.school_roleid = t1.id LIMIT 1) AS roles'))
                    ->leftJoin($main_db . '.roles AS t2', 't1.portal_roleid', '=', 't2.id')->get();

                return $this->successResponse($schoolroleDetails, 'School Role record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getschool_roleList');
        }
    }
    public function getschool_roleLists(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
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

                $schoolroleDetails = $conn->table('school_roles')->where('flag', '=', '0')->get();
                $school_array = [];
                foreach ($schoolroleDetails as $school_role) {

                    $roleIds1 = $conn->table('school_menuaccess as t1')
                        ->select('t1.role_id', 't2.role_name')
                        ->leftJoin($main_db . '.roles AS t2', 't1.role_id', '=', 't2.id')
                        ->distinct()
                        ->where('t1.school_roleid', '=', $school_role->id)
                        ->pluck('t2.role_name');
                    $roles = '';
                    foreach ($roleIds1 as $role) {
                        $roles .= $role . ',';
                    }

                    $datas = [
                        "id" => $school_role->id,
                        "fullname" => $school_role->fullname,
                        "shortname" => $school_role->shortname,
                        "portal_roleid" => $school_role->portal_roleid,
                        "created_at" => $school_role->created_at,
                        "updated_at" => $school_role->updated_at,
                        "flag" => $school_role->flag,
                        "roles" => substr($roles, 0, -1),

                    ];
                    array_push($school_array, $datas);
                }

                return $this->successResponse($school_array, 'School Role record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getschool_roleLists');
        }
    }
    public function portal_roles(Request $request)
    {
        try {
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
                $portal_roleDetails = $conn->table('portal_role')->get();

                return $this->successResponse($portal_roleDetails, 'Portal Role record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'portal_roles');
        }
    }
    // get EventType row details
    public function getschool_roleDetails(Request $request)
    {
        try {
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
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getschool_roleDetails');
        }
    }
    // update EventType
    public function updateschool_role(Request $request)
    {
        try {
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
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'updateschool_role');
        }
    }
    // delete EventType
    public function deleteschool_role(Request $request)
    {
        try {
            $id = $request->id;
            $user_id = $request->user_id;
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
                //$query = $conn->table('school_roles')->where('id', $id)->delete();
                $query = $conn->table('school_roles')->where('id', $id)->update([
                    'flag' => '2',
                    'deleted_at' => date("Y-m-d H:i:s"),
                    'deleted_by' => $user_id
                ]);
                // Set School Role deleted status as 2
                User::where([['school_roleid', '=', $id], ['branch_id', '=', $request->branch_id]])->update([

                    'status' => '2',
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'School Role have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'deleteschool_role');
        }
    }
    public function getschool_menuroleDetails(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $id = $request->id;

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $schoolroleDetails = $conn->table('school_roles')->where('id', $id)->first();
                $portal_roleid  = $schoolroleDetails->portal_roleid;
                $roleDetails = $conn->table('portal_role')->where('id', $portal_roleid)->first();
                $portal_roleid  = explode(',', $roleDetails->roles);
                $roles = Role::whereIn('id', $portal_roleid)->get();
                return $this->successResponse($roles, 'School Role row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getschool_menuroleDetails');
        }
    }
    public function getschoolmenuaccesslist(Request $request)
    {
        try {
            $br_id = $request->br_id;
            $scrole_id = $request->scrole_id;
            $main_db = config('constants.main_db');

            $conn = $this->createNewConnection($request->br_id);
            // get data
            //$query = $conn->table('school_roles')->where('id', $id)->delete();

            $data =  $conn->table($main_db . '.menus as ms')->select('ms.*')
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
                ->leftJoin($main_db . '.menuaccess as ma', 'ma.menu_id', '=', 'ms.menu_id')
                ->where('ma.menu_permission', 'Access')
                ->where('ma.branch_id', $br_id)
                ->where('ms.menu_type', $request->type)
                ->where('ms.role_id', $request->role_id)
                ->where('ms.flog', 0)
                ->orderBy("ms.menu_order", "asc")
                ->get();
            // dd($data);
            return $this->successResponse($data, 'Menus fetch successfully');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getschoolmenuaccesslist');
        }
    }
    public function setschoolpermission(Request $request)
    {
        try {
            // return $request;
            $conn = $this->createNewConnection($request->branch_id);
            $main_db = config('constants.main_db');
            foreach ($request->menu_id as $menuid) {

                if ($request->act[$menuid] == 'Insert') {
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
                } else {
                    $query = $conn->table('school_menuaccess')->where('id', $request->menuaccess_id[$menuid])->update([
                        'read' => @$request->read[$menuid],
                        'add' => @$request->add[$menuid],
                        'updates' => @$request->updates[$menuid],
                        'deletes' => @$request->deletes[$menuid],
                        'export' => @$request->export[$menuid],
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                }
            }
            $sid = $request->school_roleid;
            if ($sid != '' || $sid != null) {
                $roleIds1 = $conn->table('school_menuaccess as t1')
                    ->select('t1.role_id', 't2.role_name')
                    ->leftJoin($main_db . '.roles AS t2', 't1.role_id', '=', 't2.id')
                    ->distinct()
                    ->where('school_roleid', '=', $request->school_roleid)
                    ->pluck('role_id');

                $roles = '';
                foreach ($roleIds1 as $role) {
                    $roles .= $role . ',';
                }
                $roles = substr($roles, 0, -1);
                User::where([['school_roleid', '=', $request->school_roleid], ['branch_id', '=', $request->branch_id]])->update([

                    'role_id' => $roles,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            }
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Menu Access Permission Assigned has been successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'setschoolpermission');
        }
    }
    public function deleteschoolpermission(Request $request)
    {
        try {
            // return $request;
            $conn = $this->createNewConnection($request->branch_id);
            $main_db = config('constants.main_db');
            $query = $conn->table('school_menuaccess')->where([['school_roleid', '=', $request->school_roleid], ['role_id', '=', $request->role_id]])->delete();

            $sid = $request->school_roleid;
            if ($sid != '' || $sid != null) {
                $roleIds1 = $conn->table('school_menuaccess as t1')
                    ->select('t1.role_id', 't2.role_name')
                    ->leftJoin($main_db . '.roles AS t2', 't1.role_id', '=', 't2.id')
                    ->distinct()
                    ->where('school_roleid', '=', $request->school_roleid)
                    ->pluck('role_id');

                $roles = '';
                foreach ($roleIds1 as $role) {
                    $roles .= $role . ',';
                }
                $roles = substr($roles, 0, -1);
                User::where([['school_roleid', '=', $request->school_roleid], ['branch_id', '=', $request->branch_id]])->update([

                    'role_id' => $roles,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            }
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Menu Access Permission Deleted has been successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'deleteschoolpermission');
        }
    }
    public function getschoolroleaccess(Request $request)
    {
        try {
            $menu_id = $request->menu_id;
            $role_id = $request->role_id;
            $school_roleid = $request->school_roleid;
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $data = Menus::select('menu_id')->where('menu_routename', $menu_id)->where('role_id', $role_id)->first();

            if ($data !== null) {
                $menu_id1 = $data['menu_id'];
                //dd($menu_id1);
                $schoolroleDetails = $conn->table('school_menuaccess')->where('menu_id', $menu_id1)->where('role_id', $role_id)->where('school_roleid', $school_roleid)->first();
                return $this->successResponse($schoolroleDetails, 'School Role row fetch successfully');
            }
        } catch (\Exception $error) {

            return  $this->commonHelper->generalReturn('403', 'error', $error, 'getschoolroleaccess');
        }
    }
    public function getschoolroleaccessroute(Request $request)
    {
        try {
            // Generate cache key based on request parameters
            $cache_time = config('constants.cache_time');
            $school_role_access = config('constants.school_role_access');
            $cacheKey = $school_role_access . $request->role_id . '_' . $request->school_roleid;
            // Cache::forget($cacheKey);
            // Check if the data is already cached
            if (Cache::has($cacheKey)) {
                // If data exists in cache, return cached data
                return $this->successResponse(Cache::get($cacheKey), 'School Role row fetched successfully from cache');
            }

            // If data is not cached, proceed with fetching from the database

            $currentRouteName = $request->currentRouteName;
            $role_id = $request->role_id;
            $school_roleid = $request->school_roleid;

            // Assuming $request->branch_id is the branch ID for creating a new connection
            $conn = $this->createNewConnection($request->branch_id);

            // Fetching menu_id based on the current route name and role ID
            $menuData = Menus::select('menu_id')->where('menu_url', $currentRouteName)
                ->where('role_id', $role_id)->first();

            if (!$menuData) {
                return $this->errorResponse('Menu data not found for the current route', 404);
            }

            $menu_id1 = $menuData->menu_id;

            // Fetching access details from the school_menuaccess table
            $schoolroleDetails = $conn->table('school_menuaccess')
                ->select('read')
                ->where('menu_id', $menu_id1)
                ->where('role_id', $role_id)
                ->where('school_roleid', $school_roleid)
                ->first();

            if (!$schoolroleDetails) {
                return $this->errorResponse('Access details not found for the provided parameters', 404);
            }

            // Cache the fetched data for future requests
            Cache::put($cacheKey, $schoolroleDetails, now()->addMinutes($cache_time)); // Cache for 10 minutes

            return $this->successResponse($schoolroleDetails, 'School Role row fetched successfully');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getschoolroleaccessroute');
        }
    }
    public function get_login_menuroute(Request $request)
    {
        try {
            $branch_id = $request->branch_id;
            $role_id = $request->role_id;
            $school_roleid = $request->school_roleid;
            $main_db = config('constants.main_db');

            $conn = $this->createNewConnection($request->branch_id);            

            $data =  $conn->table($main_db . '.menus as ms')->select('ms.menu_id','ms.menu_routename','ms.menu_url')
                ->leftJoin($main_db . '.menuaccess as ma', 'ma.menu_id', '=', 'ms.menu_id')
                ->leftJoin('school_menuaccess as sm', 'sm.menu_id', '=', 'ms.menu_id')
                ->where('ma.role_id', $role_id)
                ->where('ma.branch_id', $branch_id)                
                ->where('ma.menu_permission', 'Access')
                ->where('sm.role_id', $role_id)
                ->where('sm.school_roleid', $school_roleid)
                ->where('sm.read', 'Access')
                ->where('ms.menu_type', 'Mainmenu')                
                ->where('ms.flog', 0)
                ->orderBy("ms.menu_order", "asc")
                ->limit(1)
                ->first();
            if($data)
            {
                $menu_url=$data->menu_url;
                $menu_id=$data->menu_id;
                if($menu_url[0]=='#')
                {
                    $data1 =  $conn->table($main_db . '.menus as ms')->select('ms.menu_id','ms.menu_routename','ms.menu_url')
                    ->leftJoin($main_db . '.menuaccess as ma', 'ma.menu_id', '=', 'ms.menu_id')
                    ->leftJoin('school_menuaccess as sm', 'sm.menu_id', '=', 'ms.menu_id')
                    ->where('ma.role_id', $role_id)
                    ->where('ma.branch_id', $branch_id)                
                    ->where('ma.menu_permission', 'Access')
                    ->where('sm.role_id', $role_id)
                    ->where('sm.school_roleid', $school_roleid)
                    ->where('sm.read', 'Access')
                    ->where('ms.menu_type', 'Submenu')                
                    ->where('ms.flog', 0)
                    ->where('ms.menu_refid', $menu_id)
                    ->orderBy("ms.menu_order", "asc")
                    ->limit(1)
                    ->first();
                    $login_route=$data1->menu_routename;
                }
                else
                {
                    $login_route=$data->menu_routename;
                }
            }
            
            // dd($data);
            
            return $this->successResponse($login_route, 'Menus fetch successfully');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getschoolmenuaccesslist');
        }
    }
}
