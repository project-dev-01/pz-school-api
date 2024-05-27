<?php

namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
//
use Illuminate\Support\Facades\Cache;
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
use App\Mail\TestQueueMail;
use App\Models\Classes;
use App\Models\Role;
use App\Models\EventType;
use App\Models\User;
// db connection
use App\Models\Forum_posts;
use App\Models\Forum_count_details;
use App\Models\Forum_post_replies;
use Carbon\Carbon;
use App\Models\Forum_post_replie_counts;
use Illuminate\Support\Arr;
// notifications
use App\Notifications\LeaveApply;
use App\Notifications\LeaveApprove;
use App\Notifications\StudentHomeworkSubmit;
use App\Notifications\TeacherHomework;
use App\Notifications\ParentEmail;
use App\Notifications\StudentEmail;
use App\Notifications\TeacherEmail;
use Illuminate\Support\Facades\Notification;
// encrypt and decrypt
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use File;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use App\Models\Menus;
use App\Models\Menuaccess;
use Illuminate\Support\Facades\Mail;
use App\Helpers\CommonHelper;

class ApiControllerThree extends BaseController
{
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper)
    {
        $this->commonHelper = $commonHelper;
    }
    // get bulletin 
    public function getBuletinBoardList(Request $request)
    {

        try {
            $validator = \Validator::make($request->all(), [
                // 'token' => 'required',
                'branch_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $main_db = config('constants.main_db');
                $currentDateTime = Carbon::now();
                // dd($currentDateTime);
                $buletinDetails = $conn->table('bulletin_boards as b')
                    ->select(
                        "b.*",
                        DB::raw("GROUP_CONCAT(DISTINCT  rol.role_name) as name"),
                        DB::raw("GROUP_CONCAT(DISTINCT c.name) as grade_name"),
                        DB::raw("s.name as section_name"),
                        DB::raw("GROUP_CONCAT(DISTINCT  d.name) as department_name"),
                        DB::raw('CONCAT(p.last_name, " ", p.first_name) as parent_name'),
                        DB::raw('CONCAT(st.last_name, " ", st.first_name) as student_name')
                    )
                    ->leftJoin('' . $main_db . '.roles as rol', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(rol.id,b.target_user)"), ">", \DB::raw("'0'"));
                    })
                    ->leftJoin('classes as c', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(c.id,b.class_id)"), ">", \DB::raw("'0'"));
                    })
                    // ->leftJoin('classes as c', 'c.id', '=', 'b.class_id')
                    ->leftJoin('sections as s', 's.id', '=', 'b.section_id')
                    ->leftJoin('staff_departments as d', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(d.id,b.department_id)"), ">", \DB::raw("'0'"));
                    })
                    ->leftJoin('parent as p', 'p.id', '=', 'b.parent_id')
                    ->leftJoin('students as st', 'st.id', '=', 'b.student_id')
                    ->where("b.status", 1)
                    // ->where("b.publish", 1)
                    // ->where('b.publish_end_date', '>', $currentDateTime)
                    ->groupBy("b.id")
                    ->orderBy('b.id', 'desc')
                    ->get()->toArray();

                return $this->successResponse($buletinDetails, 'Bulletin record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getBuletinBoardList');
        }
    }
    public function addBuletinBoard(Request $request)
    {

        try {
            $validator = \Validator::make($request->all(), [
                // 'token' => 'required',
                'branch_id' => 'required',
                'title' => 'required',
                'target_user' => 'required',
            ]);

            //    return $request;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                $fileDetails = $request->file;

                $fileNames = [];
                if ($fileDetails) {
                    foreach ($fileDetails as $key => $value) {
                         // Set timezone to Malaysia
                         $now = now()->setTimezone('Asia/Kuala_Lumpur');

                         // Generate formatted timestamp
                         $timestamp = $now->format('ymdHis'); // Example format: 20240522153000 (YYYYMMDDHHMMSS)
                         $extension = $value['extension'];
                         $originalfilename = $value['filename'];
                         // Construct the file name with the timestamp
                        $fileName = $originalfilename . '_' . $timestamp . '.' . $extension;
                        $path = '/public/' . $request->branch_id . '/admin-documents/buletin_files/';
                        $base64 = base64_decode($value['base64']);
                        File::ensureDirectoryExists(base_path() . $path);
                        $file = base_path() . $path . $fileName;
                        $upload = file_put_contents($file, $base64);
                        array_push($fileNames, $fileName);
                    }
                }

                // if (isset($request->file)) {
                //     $now = now();
                //     $name = strtotime($now);
                //     $originalfilename = $request->fileName;
                //     $extension = $request->file_extension;
                //     $fileName = $originalfilename . "." . $extension;
                //     $path = '/public/' . $request->branch_id . '/admin-documents/buletin_files/';
                //     $base64 = base64_decode($request->file);
                //     File::ensureDirectoryExists(base_path() . $path);
                //     $file = base_path() . $path . $fileName;
                //     $picture = file_put_contents($file, $base64);
                // } else {
                //     $fileName = null;
                // }
                $query = $conn->table('bulletin_boards')->insertGetId([
                    'title' => $request->title,
                    'discription' => $request->description,
                    'file' => implode(",", $fileNames),
                    'target_user' => $request->target_user,
                    'status' => 1,
                    'class_id' => $request->class_id,
                    'section_id' => $request->section_id,
                    'student_id' => $request->student_id,
                    'parent_id' => $request->parent_id,
                    'department_id' => $request->department_id,
                    'publish_date' => $request->publish_date,
                    'publish_end_date' => $request->publish_end_date,
                    // 'publish' => !empty($request->publish == "on") ? "1" : "0",
                    'add_dashboard' =>  !empty($request->add_to_dash == "on") ? "1" : "0",
                    'created_by' => $request->created_by,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Bulletin Board has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in  addBuletinBoard');
        }
    }
    public function bulletinCronJob(Request $request)
    {
        try {
            if ($request->secret_key !== 'S6rSMVixPeupH51AO5mVFjkQJ88bnjOO') {
                return response()->json(['error' => 'Unauthorized.'], 401);
            }
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $currentDateTime = now();
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);

                $itemsToPublish = $conn->table('bulletin_boards')->where('publish_date', '<=', $currentDateTime)
                    ->whereNull('notification_sent')
                    ->where('status', "1")
                    ->get();
                // dd($itemsToPublish);
                foreach ($itemsToPublish as $item) {

                    $target_user = $item->target_user; // Assuming $target_user is "2,5"
                    $target_user_array = explode(',', $target_user);
                    $target_user_array = array_map('intval', $target_user_array);
                    if ($target_user_array == [2, 6]) {
                        $class_id =   $item->class_id;
                        $section_id = $item->section_id;
                        $student_id  = $item->student_id;
                        $getStudent = $conn->table('enrolls as e')->select('s.id', DB::raw('CONCAT(s.last_name, " ", s.first_name) as name'), 's.register_no', 's.roll_no', 's.mobile_no', 's.email', 's.gender', 's.photo')
                            ->leftJoin('students as s', 'e.student_id', '=', 's.id')
                            // ->when($class_id, function ($query, $class_id) {
                            //     return $query->where('e.class_id', $class_id);
                            // })
                            ->when($class_id, function ($query, $class_id) {
                                $classIDsArray = explode(",", $class_id);
                                // Iterate over departmentIDs array to add conditions
                                foreach ($classIDsArray as $classID) {
                                    // Add condition for each department ID using FIND_IN_SET
                                    $query->orWhereRaw("FIND_IN_SET('$classID', e.class_id) > 0");
                                }
                            })
                            ->when($section_id, function ($query, $section_id) {
                                return $query->where('e.section_id', $section_id);
                            })
                            ->when($student_id, function ($query, $student_id) {
                                return $query->where('e.student_id', $student_id);
                            })
                            ->where('e.active_status', '=', "0")
                            ->groupBy('e.student_id')
                            ->get()->toArray();
                        if (!empty($getStudent)) {
                            $assignerID = [];
                            if (isset($getStudent)) {
                                foreach ($getStudent as $key => $value) {
                                    array_push($assignerID, $value->id);
                                }
                            }
                            //dd($assignerID);
                            $user = User::whereIn('user_id', $assignerID)
                                ->where('branch_id', $request->branch_id)
                                ->where(function ($q) {
                                    // Use whereRaw with FIND_IN_SET to check if the role_id contains the desired role
                                    $q->whereRaw('FIND_IN_SET(?, role_id)', [6]);
                                })
                                ->get();

                            Notification::send($user, new StudentEmail($request->branch_id));
                            // Update item to mark notification as sent
                            $conn->table('bulletin_boards')->where('id', $item->id)->update([
                                'notification_sent' => true,
                            ]);
                        }
                    }
                    if ($target_user_array == [2, 5]) {
                        //  dd("testing");
                        $class_id =   $item->class_id;
                        $section_id = $item->section_id;
                        $parent_id  = $item->parent_id;
                        $getParent = $conn->table('enrolls as e')->select('p.id', DB::raw('CONCAT(p.last_name, " ", p.first_name) as parent_name'))
                            ->leftJoin('students as s', 'e.student_id', '=', 's.id')
                            ->leftjoin('parent as p', function ($join) {
                                $join->on('s.father_id', '=', 'p.id');
                                $join->orOn('s.mother_id', '=', 'p.id');
                                $join->orOn('s.guardian_id', '=', 'p.id');
                            })
                            // ->when($class_id, function ($query, $class_id) {
                            //     return $query->where('e.class_id', $class_id);
                            // })
                            ->when($class_id, function ($query, $class_id) {
                                $classIDsArray = explode(",", $class_id);
                                // Iterate over departmentIDs array to add conditions
                                foreach ($classIDsArray as $classID) {
                                    // Add condition for each department ID using FIND_IN_SET
                                    $query->orWhereRaw("FIND_IN_SET('$classID', e.class_id) > 0");
                                }
                            })
                            ->when($section_id, function ($query, $section_id) {
                                return $query->where('e.section_id', $section_id);
                            })
                            ->when($parent_id, function ($query, $parent_id) {
                                return $query->where('p.id', $parent_id);
                            })
                            ->where('e.active_status', '=', "0")
                            ->groupBy('p.id')
                            ->get()->toArray();
                        if (!empty($getParent)) {
                            $assignerID = [];
                            if (isset($getParent)) {
                                foreach ($getParent as $key => $value) {
                                    array_push($assignerID, $value->id);
                                }
                            }
                            //dd($assignerID);
                            // send leave notifications
                            $user = User::whereIn('user_id', $assignerID)
                                ->where('branch_id', $request->branch_id)
                                ->where(function ($q) {
                                    // Use whereRaw with FIND_IN_SET to check if the role_id contains the desired role
                                    $q->whereRaw('FIND_IN_SET(?, role_id)', [5]);
                                })
                                ->get();
                            Notification::send($user, new ParentEmail($request->branch_id));
                            // Update item to mark notification as sent
                            $conn->table('bulletin_boards')->where('id', $item->id)->update([
                                'notification_sent' => true,
                            ]);
                        }
                    }
                    if ($target_user_array == [2, 4]) {
                        $deptId = isset($item->department_id) ? $item->department_id : null;

                        $getStaff = $conn->table('staffs as stf')
                            ->select(
                                'stf.id'
                            )->when($deptId, function ($query, $deptId) {
                                $departmentIDsArray = explode(",", $deptId);
                                // Iterate over departmentIDs array to add conditions
                                foreach ($departmentIDsArray as $departmentID) {
                                    // Add condition for each department ID using FIND_IN_SET
                                    $query->orWhereRaw("FIND_IN_SET('$departmentID', stf.department_id) > 0");
                                }
                            })
                            ->where('stf.is_active', '=', '0')
                            ->groupBy('stf.id')
                            ->get()->toArray();
                        if (!empty($getStaff)) {
                            $assignerID = [];
                            if (isset($getStaff)) {
                                foreach ($getStaff as $key => $value) {
                                    array_push($assignerID, $value->id);
                                }
                            }
                            $user = User::whereIn('user_id', $assignerID)
                                ->where('branch_id', $request->branch_id)
                                ->where(function ($q) {
                                    // Use whereRaw with FIND_IN_SET to check if the role_id contains the desired role
                                    $q->whereRaw('FIND_IN_SET(?, role_id)', [4]);
                                })
                                ->get();
                            Notification::send($user, new TeacherEmail($request->branch_id));
                            // Update item to mark notification as sent
                            $conn->table('bulletin_boards')->where('id', $item->id)->update([
                                'notification_sent' => true,
                            ]);
                        }
                    }
                    sort($target_user_array);
                    if ($target_user_array == [2, 4, 5] || $target_user_array == [2, 5, 4]) {
                        $deptId = isset($item->department_id) ? $item->department_id : null;
                        $class_id = isset($item->class_id) ? $item->class_id : null;
                        $section_id = isset($item->section_id) ? $item->section_id : null;
                        $student_id = isset($item->student_id) ? $item->student_id : null;
                        $parent_id = isset($item->parent_id) ? $item->parent_id : null;

                        $getStaff = $conn->table('staffs as stf')
                            ->select(
                                'stf.id',
                                'stf.email'
                            )->when($deptId, function ($query, $deptId) {
                                $departmentIDsArray = explode(",", $deptId);
                                // Iterate over departmentIDs array to add conditions
                                foreach ($departmentIDsArray as $departmentID) {
                                    // Add condition for each department ID using FIND_IN_SET
                                    $query->orWhereRaw("FIND_IN_SET('$departmentID', stf.department_id) > 0");
                                }
                            })
                            ->where('stf.is_active', '=', '0')
                            ->groupBy('stf.id')
                            ->get();

                        $getParent = $conn->table('enrolls as e')->select('p.id', 'p.email')
                            ->join('students as s', 'e.student_id', '=', 's.id')
                            ->leftjoin('parent as p', function ($join) {
                                $join->on('s.father_id', '=', 'p.id');
                                $join->orOn('s.mother_id', '=', 'p.id');
                                $join->orOn('s.guardian_id', '=', 'p.id');
                            })
                            ->when($deptId, function ($query, $deptId) {
                                return $query->where('e.department_id', $deptId);
                            })
                            ->when($class_id, function ($query, $class_id) {
                                $classIDsArray = explode(",", $class_id);
                                // Iterate over departmentIDs array to add conditions
                                foreach ($classIDsArray as $classID) {
                                    // Add condition for each department ID using FIND_IN_SET
                                    $query->orWhereRaw("FIND_IN_SET('$classID', e.class_id) > 0");
                                }
                            })
                            ->when($section_id, function ($query, $section_id) {
                                return $query->where('e.section_id', $section_id);
                            })
                            ->when($student_id, function ($query, $student_id) {
                                return $query->where('e.student_id', $student_id);
                            })
                            // ->where('e.active_status', '=', "0")
                            ->groupBy('e.student_id')
                            ->get();
                        if (!empty($getStaff)) {
                            $assignerID = [];
                            if (isset($getStaff)) {
                                foreach ($getStaff as $key => $value) {
                                    array_push($assignerID, $value->id);
                                }
                            }
                            $getStaffUser = User::whereIn('user_id', $assignerID)
                                ->where('branch_id', $request->branch_id)
                                ->where(function ($q) {
                                    // Use whereRaw with FIND_IN_SET to check if the role_id contains the desired role
                                    $q->whereRaw('FIND_IN_SET(?, role_id)', [4]);
                                })
                                ->get();
                            // dd($getStaffUser);
                            if (!empty($getStaffUser)) {
                                // dd($objects);
                                Notification::send($getStaffUser, new TeacherEmail($request->branch_id));
                            }
                        }
                        if (!empty($getParent)) {
                            $passignerID = [];
                            if (isset($getParent)) {
                                foreach ($getParent as $keys => $values) {
                                    array_push($passignerID, $values->id);
                                }
                            }
                            $getParentUsers = User::whereIn('user_id', $passignerID)
                                ->where('branch_id', $request->branch_id)
                                ->where(function ($q) {
                                    // Use whereRaw with FIND_IN_SET to check if the role_id contains the desired role
                                    $q->whereRaw('FIND_IN_SET(?, role_id)', [5]);
                                })
                                ->get();
                            // dd($getParentUsers);
                            if (!empty($getParentUsers)) {
                                // dd($objects);
                                Notification::send($getParentUsers, new TeacherEmail($request->branch_id));
                            }
                        }
                        // Update item to mark notification as sent
                        $conn->table('bulletin_boards')->where('id', $item->id)->update([
                            'notification_sent' => true,
                        ]);
                    }
                }

                return $this->successResponse([], 'Notifications sent successfully.');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in  bulletinCronJob');
        }
    }
    public function bulletinCronJobTestMail(Request $request)
    {
        try {
            if ($request->secret_key !== 'S6rSMVixPeupH51AO5mVFjkQJ88bnjOO') {
                return response()->json(['error' => 'Unauthorized.'], 401);
            }
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $currentDateTime = now();
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);

                //dd($assignerID);
                $user = User::where('email', "karthik@aibots.my")
                    ->get();

                Notification::send($user, new StudentEmail($request->branch_id));

                return $this->successResponse([], 'Notifications sent successfully.');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in  bulletinCronJob');
        }
    }

    public function usernameBuletin(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [

                'token' => 'required'

            ]);
            //dd($validator);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection              
                $success = DB::table('roles')->select('id', 'role_name as name')
                    ->where('id', '!=', 1)
                    ->where('id', '!=', 3)
                    ->where('id', '!=', 7)
                    ->where('id', '!=', $request->user_id)
                    ->get();
                //   $success = Category::all();
                return $this->successResponse($success, 'user name record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in usernameBuletin');
        }
    }
    public function deleteBuletinBoard(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'id' => 'required',
                'branch_id' => 'required',
            ]);
            $buletin_id = $request->id;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);

                // get data
                // $query = $conn->table('bulletin_boards')->where('id',$buletin_id)->delete();
                $query = $conn->table('bulletin_boards')->where('id', $buletin_id)->update([
                    'status'    => 0,
                    'deleted_at' => date("Y-m-d H:i:s"),
                    'deleted_by' => isset($request->deleted_by) ? $request->deleted_by : null
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Bulletin Board have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in  deleteBuletinBoard');
        }
    }
    // get Event row details
    public function getBuletinBoardDetails(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $main_db = config('constants.main_db');

                $buletin_id = $request->id;
                $buletinDetails = $conn->table('bulletin_boards as b')
                    ->select(
                        "b.*",
                        DB::raw("GROUP_CONCAT(DISTINCT  rol.role_name) as name"),
                        DB::raw("GROUP_CONCAT(DISTINCT  c.name) as grade_name"),
                        DB::raw("GROUP_CONCAT(DISTINCT  s.name) as section_name"),
                        DB::raw("GROUP_CONCAT(DISTINCT  d.name) as department_name"),
                        DB::raw('CONCAT(p.last_name, " ", p.first_name) as parent_name'),
                        DB::raw('CONCAT(st.last_name, " ", st.first_name) as student_name')
                    )
                    ->leftJoin('' . $main_db . '.roles as rol', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(rol.id,b.target_user)"), ">", \DB::raw("'0'"));
                    })
                    ->leftJoin('classes as c', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(c.id,b.class_id)"), ">", \DB::raw("'0'"));
                    })
                    ->leftJoin('sections as s', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(s.id,b.section_id)"), ">", \DB::raw("'0'"));
                    })
                    ->leftJoin('staff_departments as d', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(d.id,b.department_id)"), ">", \DB::raw("'0'"));
                    })
                    ->leftjoin('parent as p', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(p.id,b.parent_id)"), ">", \DB::raw("'0'"));
                    })
                    ->leftjoin('students as st', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(st.id,b.student_id)"), ">", \DB::raw("'0'"));
                    })
                    ->groupBy("b.id")
                    ->orderBy('b.id', 'desc')
                    ->where('b.id', $buletin_id)->first();
                return $this->successResponse($buletinDetails, 'Bulletin board row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getBuletinBoardDetails');
        }
    }
    public function updateBuletinBoard(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'title' => 'required',
                'target_user' => 'required',
            ]);

            //    return $request;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                if (isset($request->oldfile) && empty($request->file)) {
                    $fileNames = $request->oldfile;
                } else {
                    if (isset($request->file)) {
                        $fileDetails = $request->file;
                        $fileNames = [];
                        foreach ($fileDetails as $key => $value) {
                             // Set timezone to Malaysia
                             $now = now()->setTimezone('Asia/Kuala_Lumpur');

                             // Generate formatted timestamp
                             $timestamp = $now->format('YmdHis'); // Example format: 20240522153000 (YYYYMMDDHHMMSS)
                            $extension = $value['extension'];
                            $originalfilename = $value['filename'];
                            $fileName = $originalfilename . '_' . $timestamp . '.' . $extension;
                            $path = '/public/' . $request->branch_id . '/admin-documents/buletin_files/';
                            $base64 = base64_decode($value['base64']);
                            File::ensureDirectoryExists(base_path() . $path);
                            $file = base_path() . $path . $fileName;
                            $upload = file_put_contents($file, $base64);
                            array_push($fileNames, $fileName);
                        }
                    } else {
                        $fileNames = null;
                    }
                }
                $query = $conn->table('bulletin_boards')->where('id', $id)->update([
                    'title' => $request->title,
                    'discription' => $request->description,
                    'file' => implode(",", $fileNames),
                    'target_user' => $request->target_user,
                    'status' => 1,
                    'publish_date' => $request->publish_date,
                    'publish_end_date' => $request->publish_end_dates,
                    'class_id' => $request->class_id,
                    'section_id' => $request->section_id,
                    'student_id' => $request->student_id,
                    'parent_id' => $request->parent_id,
                    'department_id' => $request->department_id,
                    //  'publish' => !empty($request->publish == "on") ? "1" : "0",
                    'add_dashboard' =>  !empty($request->add_to_dash == "on") ? "1" : "0",
                    'updated_at' => date("Y-m-d H:i:s"),
                    'updated_by' => $request->updated_by,
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Bulletin Board has been successfully updated');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateBuletinBoard');
        }
    }
    // get Student List
    public function getStudentListForBulletinBoard(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            $class_id = $request->class_id;
            $section_id = $request->section_id;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);
                // get data
                $student = $con->table('enrolls as e')->select('s.id', DB::raw('CONCAT(s.last_name, " ", s.first_name) as name'), 's.register_no', 's.roll_no', 's.mobile_no', 's.email', 's.gender', 's.photo')
                    ->leftJoin('students as s', 'e.student_id', '=', 's.id')
                    // ->when($class_id, function ($query, $class_id) {
                    //     return $query->where('e.class_id', $class_id);
                    // })
                    // ->when($section_id, function ($query, $section_id) {
                    //     return $query->where('e.section_id', $section_id);
                    // })
                    ->whereIn('e.class_id', $class_id)
                    ->where('e.section_id', '=', $section_id)
                    ->where('e.active_status', '=', "0")
                    ->groupBy('e.student_id')
                    ->get()->toArray();

                return $this->successResponse($student, 'Student record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getStudentListForBulletinBoard');
        }
    }
    public function getParentListForBulletinBoard(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                // 'token' => 'required',
            ]);

            $class_id = $request->class_id;
            $section_id = $request->section_id;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $con = $this->createNewConnection($request->branch_id);
                // get data
                $parent = $con->table('enrolls as e')->select('p.id', DB::raw('CONCAT(p.last_name, " ", p.first_name) as parent_name'))
                    ->join('students as s', 'e.student_id', '=', 's.id')
                    ->join('parent as p', function ($join) {
                        $join->on('s.father_id', '=', 'p.id');
                        $join->orOn('s.mother_id', '=', 'p.id');
                        $join->orOn('s.guardian_id', '=', 'p.id');
                    })
                    // ->when($class_id, function ($query, $class_id) {
                    //     return $query->where('e.class_id', $class_id);
                    // })
                    // ->when($section_id, function ($query, $section_id) {
                    //     return $query->where('e.section_id', $section_id);
                    // })
                    ->whereIn('e.class_id', $class_id)
                    ->where('e.section_id', '=', $section_id)
                    ->where('e.active_status', '=', "0")
                    ->groupBy('e.student_id')
                    ->get()->toArray();
                return $this->successResponse($parent, 'Parent record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getParentListForBulletinBoard');
        }
    }
    // get Student List
    public function getRetiredList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',

            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);

                $student = $con->table('staffs as stf')
                    ->select(
                        'stf.id',
                        DB::raw('CONCAT(stf.first_name, " ", stf.last_name) as emp_name'),
                        DB::raw('CONCAT(stf.first_name_english, " ", stf.last_name_english) as english_emp_name'),
                        DB::raw('CONCAT(stf.first_name_furigana, " ", stf.last_name_furigana) as furigana_emp_name'),
                        'stf.email',
                        'stf.gender',
                        'stf.height',
                        'stf.weight',
                        'stf.allergy',
                        'stf.blood_group',
                        'stf.employment_status',
                        'stp.name as staff_position_name',
                        'stc.name as staff_category_name',
                        'stf.birthday',
                        'stf.nationality',
                        're.name as religion_name',
                        'stf.mobile_no',
                        'stf.city',
                        'stf.state',
                        'stf.country',
                        'stf.post_code',
                        'stf.visa_number',
                        DB::raw('GROUP_CONCAT(ds.name) as designation_name'),
                        'dp.name as department_name',
                        DB::raw("stf.designation_start_date as designation_start_date"),
                        DB::raw("stf.designation_end_date as designation_end_date"),
                        'stf.joining_date',
                        'stf.releive_date',
                        'stf.updated_at',
                        'em.name',
                        'stf.present_address',
                        'stf.permanent_address',
                        'stf.nric_number',
                        'stf.passport',
                    )
                    ->leftJoin("staff_departments as dp", DB::raw("FIND_IN_SET(dp.id, stf.department_id)"), ">", DB::raw("'0'"))
                    ->leftJoin("staff_designations as ds", DB::raw("FIND_IN_SET(ds.id, stf.designation_id)"), ">", DB::raw("'0'"))
                    ->leftJoin("employee_types as em", DB::raw("FIND_IN_SET(em.id, stf.employee_type_id)"), ">", DB::raw("'0'"))
                    ->leftJoin('religions as re', 'stf.religion', '=', 're.id')
                    ->leftJoin('staff_categories as stc', 'stf.staff_category', '=', 'stc.id')
                    ->leftJoin('staff_positions as stp', 'stf.staff_position', '=', 'stp.id')
                    ->where('stf.working_status', '=', '1')
                    ->where('stf.is_active', '=', '0')
                    ->groupBy('stf.id')
                    ->orderBy('stf.created_at', 'asc');
                // Group by the formatted date
                $staffArray = [];
                $getEmpDetails = $student->get();
                $staffObj = new \stdClass();
                if (!empty($getEmpDetails)) {
                    foreach ($getEmpDetails as $suc) {
                        $staffObj = $suc;
                        $staffObj->present_address = Helper::decryptStringData($suc->present_address);
                        $staffObj->permanent_address = Helper::decryptStringData($suc->permanent_address);
                        $staffObj->mobile_no = Helper::decryptStringData($suc->mobile_no);
                        $staffObj->nric_number = Helper::decryptStringData($suc->nric_number);
                        $staffObj->passport = Helper::decryptStringData($suc->passport);
                        $staffArray[] = $staffObj;
                    }
                }
                $studentData['staff'] = $staffArray;
                // dd($studentData);
                return $this->successResponse($studentData, 'Employee record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getRetiredList');
        }
    }
    public function getBulletinParent(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $main_db = config('constants.main_db');
                $parent_id = $request->parent_id;
                $role_id = $request->role_id;
                $student_id = $request->student_id;
                $currentDateTime = Carbon::now();
                $student_data = $conn->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'en.class_id',
                        'en.section_id'
                    )
                    ->join('students as st', 'st.id', '=', 'en.student_id')
                    ->leftjoin('parent as p', function ($join) {
                        $join->on('st.father_id', '=', 'p.id');
                        $join->orOn('st.mother_id', '=', 'p.id');
                        $join->orOn('st.guardian_id', '=', 'p.id');
                    })
                    ->where('en.active_status', '=', '0')
                    ->where('en.student_id', '=', $student_id)
                    ->first();

                $class_id = $student_data->class_id;
                $section_id = $student_data->section_id;

                $buletinDetails = $conn->table('bulletin_boards as b')
                    ->select("b.id", "b.title", "b.file", "b.discription", "bi.parent_imp", "b.publish_date")
                    ->leftJoin('' . $main_db . '.roles as rol', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(rol.id,b.target_user)"), ">", \DB::raw("'0'"));
                    })
                    ->leftJoin('classes as c', 'b.class_id', '=', 'c.id')
                    ->leftJoin('sections as sc', 'b.section_id', '=', 'sc.id')
                    ->leftJoin('bulletin_imp_document as bi', function ($join) use ($parent_id) {
                        $join->on('b.id', '=', 'bi.bulletin_id');
                        $join->where('bi.user_id', '=', $parent_id);
                    })
                    // ->where(function ($query) use ($class_id) {
                    //     $query->where('b.class_id', $class_id)
                    //         ->orWhereNull('b.class_id');
                    // })
                    ->where(function ($query) use ($class_id) {
                        // Check if $class_id exists in the comma-separated list of class IDs
                        $query->whereRaw("FIND_IN_SET('$class_id', b.class_id)")
                            ->orWhereNull('b.class_id');
                    })
                    //->where('b.class_id', $class_id)
                    ->where(function ($query) use ($section_id) {
                        $query->where('b.section_id', $section_id)
                            ->orWhereNull('b.section_id');
                    })
                    // ->where("b.publish", 1)
                    ->where("b.status", 1)
                    ->where(function ($query) use ($parent_id, $role_id) {
                        $query->where('b.parent_id', $parent_id)
                            ->orWhereNull('b.parent_id')
                            ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                    })
                    // ->where(function ($query) use ($currentDateTime) {
                    //     $query->where('b.publish_end_date', '>', $currentDateTime)
                    //         ->orWhereNull('b.publish_end_date');
                    // })
                    ->where('b.publish_date', '<=', now())
                    ->groupBy("b.id")
                    ->orderBy('b.id', 'desc')
                    ->get()->toArray();

                // dd($eventDetails);
                return $this->successResponse($buletinDetails, 'Bulletin record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getBulletinParent');
        }
    }

    public function bulletinParentStar(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
            ]);
            $id = $request->id;
            $parent_imp =  $request->parentImp;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);

                $existingRecord = $con->table('bulletin_imp_document')
                    ->where('bulletin_id', $id)
                    ->where('user_id', $request->user_id)
                    ->first();

                if ($existingRecord) {
                    // Update the existing record
                    $query = $con->table('bulletin_imp_document')
                        ->where('bulletin_id', $id)
                        ->where('user_id', $request->user_id)
                        ->update([
                            'parent_imp' => $parent_imp,
                            'updated_at' => date("Y-m-d H:i:s"),
                            'updated_by' => $request->updated_by,
                        ]);
                } else {
                    // Insert a new record
                    $query = $con->table('bulletin_imp_document')->insertGetId([
                        'target_user' => $request->role_id,
                        'user_id' => $request->user_id,
                        'bulletin_id' => $id,
                        'parent_imp' => $parent_imp,
                        'created_at' => date("Y-m-d H:i:s"),
                        'created_by' => $request->created_by,
                    ]);
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Important Bulliten Board has been successfully updated');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in bulletinParentStar');
        }
    }
    public function getBulletinImpParent(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $main_db = config('constants.main_db');
                $parent_id = $request->parent_id;
                $role_id = $request->role_id;
                $student_id = $request->student_id;
                $currentDateTime = Carbon::now();
                $student_data = $conn->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'en.class_id',
                        'en.section_id'
                    )
                    ->join('students as st', 'st.id', '=', 'en.student_id')
                    ->leftjoin('parent as p', function ($join) {
                        $join->on('st.father_id', '=', 'p.id');
                        $join->orOn('st.mother_id', '=', 'p.id');
                        $join->orOn('st.guardian_id', '=', 'p.id');
                    })
                    ->where('en.active_status', '=', '0')
                    ->where('en.student_id', '=', $student_id)
                    ->first();
                $class_id = $student_data->class_id;
                $section_id = $student_data->section_id;

                $buletinDetails = $conn->table('bulletin_boards as b')
                    ->select("b.id", "b.title", "b.file", "b.discription", "bi.parent_imp", "b.publish_date")
                    ->leftJoin('classes as c', 'b.class_id', '=', 'c.id')
                    ->leftJoin('sections as sc', 'b.section_id', '=', 'sc.id')
                    ->leftJoin('bulletin_imp_document as bi', function ($join) use ($parent_id) {
                        $join->on('b.id', '=', 'bi.bulletin_id');
                        $join->where('bi.user_id', '=', $parent_id);
                    })
                    ->where(function ($query) use ($class_id) {
                        // Check if $class_id exists in the comma-separated list of class IDs
                        $query->whereRaw("FIND_IN_SET('$class_id', b.class_id)")
                            ->orWhereNull('b.class_id');
                    })
                    //->where('b.class_id', $class_id)
                    ->where(function ($query) use ($section_id) {
                        $query->where('b.section_id', $section_id)
                            ->orWhereNull('b.section_id');
                    })
                    ->where("b.status", 1)
                    //  ->where("b.publish", 1)
                    ->where(function ($query) use ($parent_id, $role_id) {
                        $query->where('b.parent_id', $parent_id)
                            ->orWhereNull('b.parent_id')
                            ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                    })
                    ->where("bi.parent_imp", '1')
                    // ->where(function ($query) use ($currentDateTime) {
                    //     $query->where('b.publish_end_date', '>', $currentDateTime)
                    //         ->orWhereNull('b.publish_end_date');
                    // })
                    ->where('b.publish_date', '<=', now())
                    ->groupBy("b.id")
                    ->get()->toArray();

                // dd($eventDetails);
                return $this->successResponse($buletinDetails, 'Bulletin Important record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getBulletinImpParent');
        }
    }
    public function getBulletinStudent(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $main_db = config('constants.main_db');
                $role_id = $request->role_id;
                $student_id = $request->student_id;
                $currentDateTime = Carbon::now();
                $student_data = $conn->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'en.class_id',
                        'en.section_id'
                    )
                    ->join('students as st', 'st.id', '=', 'en.student_id')
                    ->where('en.student_id', '=', $student_id)
                    ->first();

                $class_id = $student_data->class_id;
                $section_id = $student_data->section_id;

                $buletinDetails = $conn->table('bulletin_boards as b')
                    ->select("b.id", "b.title", "b.file", "b.discription", "bi.parent_imp", "b.publish_date")
                    ->leftJoin('' . $main_db . '.roles as rol', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(rol.id,b.target_user)"), ">", \DB::raw("'0'"));
                    })
                    ->leftJoin('classes as c', 'b.class_id', '=', 'c.id')
                    ->leftJoin('sections as sc', 'b.section_id', '=', 'sc.id')
                    ->leftJoin('bulletin_imp_document as bi', function ($join) use ($student_id) {
                        $join->on('b.id', '=', 'bi.bulletin_id');
                        $join->where('bi.user_id', '=', $student_id);
                    })
                    ->where(function ($query) use ($class_id) {
                        // Check if $class_id exists in the comma-separated list of class IDs
                        $query->whereRaw("FIND_IN_SET('$class_id', b.class_id)")
                            ->orWhereNull('b.class_id');
                    })
                    //->where('b.class_id', $class_id)
                    ->where(function ($query) use ($section_id) {
                        $query->where('b.section_id', $section_id)
                            ->orWhereNull('b.section_id');
                    })
                    ->where("b.status", 1)
                    //->where("b.publish", 1)
                    ->where(function ($query) use ($student_id, $role_id) {
                        $query->where('b.student_id', $student_id)
                            ->orWhereNull('b.student_id')
                            ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                    })
                    // ->where(function ($query) use ($currentDateTime) {
                    //     $query->where('b.publish_end_date', '>', $currentDateTime)
                    //         ->orWhereNull('b.publish_end_date');
                    // })
                    ->where('b.publish_date', '<=', now())
                    ->groupBy("b.id")
                    ->orderBy('b.id', 'desc')
                    ->get()->toArray();

                // dd($eventDetails);
                return $this->successResponse($buletinDetails, 'Bulletin record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getBulletinStudent');
        }
    }

    public function bulletinStudentStar(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
            ]);
            $id = $request->id;
            $parent_imp =  $request->parentImp;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);

                $existingRecord = $con->table('bulletin_imp_document')
                    ->where('bulletin_id', $id)
                    ->where('user_id', $request->user_id)
                    ->first();

                if ($existingRecord) {
                    // Update the existing record
                    $query = $con->table('bulletin_imp_document')
                        ->where('bulletin_id', $id)
                        ->where('user_id', $request->user_id)
                        ->update([
                            'parent_imp' => $parent_imp,
                            'updated_at' => date("Y-m-d H:i:s"),
                            'updated_by' => $request->updated_by,
                        ]);
                } else {
                    // Insert a new record
                    $query = $con->table('bulletin_imp_document')->insertGetId([
                        'target_user' => $request->role_id,
                        'user_id' => $request->user_id,
                        'bulletin_id' => $id,
                        'parent_imp' => $parent_imp,
                        'created_at' => date("Y-m-d H:i:s"),
                        'created_by' => $request->created_by,
                    ]);
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Important Bulliten Board has been successfully updated');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in bulletinStudentStar');
        }
    }
    public function getBulletinImpStudent(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $main_db = config('constants.main_db');
                $role_id = $request->role_id;
                $student_id = $request->student_id;

                $currentDateTime = Carbon::now();
                $student_data = $conn->table('enrolls as en')
                    ->select(
                        'en.class_id',
                        'en.section_id'
                    )
                    ->leftJoin('students as st', 'st.id', '=', 'en.student_id')
                    ->where('en.student_id', '=', $student_id)
                    ->first();
                $class_id = $student_data->class_id;
                $section_id = $student_data->section_id;

                $buletinDetails = $conn->table('bulletin_boards as b')
                    ->select("b.id", "b.title", "b.file", "b.discription", "bi.parent_imp", "b.publish_date")
                    ->leftJoin('classes as c', 'b.class_id', '=', 'c.id')
                    ->leftJoin('sections as sc', 'b.section_id', '=', 'sc.id')
                    ->leftJoin('bulletin_imp_document as bi', function ($join) use ($student_id) {
                        $join->on('b.id', '=', 'bi.bulletin_id');
                        $join->where('bi.user_id', '=', $student_id);
                    })
                    ->where(function ($query) use ($class_id) {
                        // Check if $class_id exists in the comma-separated list of class IDs
                        $query->whereRaw("FIND_IN_SET('$class_id', b.class_id)")
                            ->orWhereNull('b.class_id');
                    })
                    // ->where('b.class_id', $class_id)
                    ->where(function ($query) use ($section_id) {
                        $query->where('b.section_id', $section_id)
                            ->orWhereNull('b.section_id');
                    })
                    ->where("b.status", 1)
                    //->where("b.publish", 1)
                    ->where(function ($query) use ($student_id, $role_id) {
                        $query->where('b.student_id', $student_id)
                            ->orWhereNull('b.student_id')
                            ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                    })
                    ->where("bi.parent_imp", '1')
                    // ->where(function ($query) use ($currentDateTime) {
                    //     $query->where('b.publish_end_date', '>', $currentDateTime)
                    //         ->orWhereNull('b.publish_end_date');
                    // })
                    ->where('b.publish_date', '<=', now())
                    ->groupBy("b.id")
                    ->get()->toArray();

                // dd($eventDetails);
                return $this->successResponse($buletinDetails, 'Bulletin Important record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getBulletinImpStudent');
        }
    }
    public function getBulletinTeacher(Request $request)
    {

        try {
            $validator = \Validator::make($request->all(), [
                // 'token' => 'required',
                'branch_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $main_db = config('constants.main_db');
                $currentDateTime = Carbon::now();
                $staff_id = $request->staff_id;
                $role_id = $request->role_id;
                $dep = $conn->table('staffs')->select('department_id')->where('id', $staff_id)->first();
                $departmentArray = $dep->department_id;
                //$departmentArray = explode(",", $department);
                $buletinDetails = $conn->table('bulletin_boards as b')
                    ->select("b.id", "b.title", "b.file", "b.discription", "bi.parent_imp", "b.publish_date")
                    ->leftJoin('' . $main_db . '.roles as rol', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(rol.id,b.target_user)"), ">", \DB::raw("'0'"));
                    })
                    ->leftJoin('bulletin_imp_document as bi', function ($join) use ($staff_id) {
                        $join->on('b.id', '=', 'bi.bulletin_id');
                        $join->where('bi.user_id', '=', $staff_id);
                    })
                    ->leftJoin('staffs as s', 'b.department_id', '=', 's.department_id')

                    ->where(function ($query) use ($departmentArray, $role_id) {
                        $query->whereRaw("FIND_IN_SET('$departmentArray', b.department_id)")
                            ->orWhereNull('b.department_id')
                            ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                    })
                    ->where("b.status", 1)
                    //->where("b.publish", 1)
                    // ->where(function ($query) use ($currentDateTime) {
                    //     $query->where('b.publish_end_date', '>', $currentDateTime)
                    //         ->orWhereNull('b.publish_end_date');
                    // })
                    ->where('b.publish_date', '<=', now())
                    ->groupBy("b.id")
                    ->orderBy('b.id', 'desc')
                    ->get()->toArray();

                // dd($eventDetails);
                return $this->successResponse($buletinDetails, 'Bulletin record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getBulletinTeacher');
        }
    }
    public function getBulletinImpTeacher(Request $request)
    {

        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $main_db = config('constants.main_db');
                $currentDateTime = Carbon::now();
                $staff_id = $request->staff_id;
                $role_id = $request->role_id;
                $dep = $conn->table('staffs')->select('department_id')->where('id', $staff_id)->first();
                $departmentArray = $dep->department_id;

                $buletinDetails = $conn->table('bulletin_boards as b')
                    ->select("b.id", "b.title", "b.file", "b.discription", "bi.parent_imp", "b.publish_date")
                    ->leftJoin('' . $main_db . '.roles as rol', function ($join) {
                        $join->on(\DB::raw("FIND_IN_SET(rol.id,b.target_user)"), ">", \DB::raw("'0'"));
                    })
                    ->leftJoin('bulletin_imp_document as bi', function ($join) use ($staff_id) {
                        $join->on('b.id', '=', 'bi.bulletin_id');
                        $join->where('bi.user_id', '=', $staff_id);
                    })
                    ->leftJoin('staffs as s', 'b.department_id', '=', 's.department_id')
                    ->where(function ($query) use ($departmentArray, $role_id) {
                        $query->whereRaw("FIND_IN_SET('$departmentArray', b.department_id)")
                            ->orWhereNull('b.department_id')
                            ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                    })
                    ->where("b.status", 1)
                    // ->where("b.publish", 1)
                    ->where("bi.parent_imp", '1')
                    // ->where(function ($query) use ($currentDateTime) {
                    //     $query->where('b.publish_end_date', '>', $currentDateTime)
                    //         ->orWhereNull('b.publish_end_date');
                    // })
                    ->where('b.publish_date', '<=', now())
                    ->groupBy("b.id")
                    ->orderBy('b.id', 'desc')
                    ->get()->toArray();

                // dd($eventDetails);
                return $this->successResponse($buletinDetails, 'Bulletin record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getBulletinImpTeacher');
        }
    }
    public function bulletinTeacherStar(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
            ]);
            $id = $request->id;
            $parent_imp =  $request->parentImp;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);

                $existingRecord = $con->table('bulletin_imp_document')
                    ->where('bulletin_id', $id)
                    ->where('user_id', $request->user_id)
                    ->first();

                if ($existingRecord) {
                    // Update the existing record
                    $query = $con->table('bulletin_imp_document')
                        ->where('bulletin_id', $id)
                        ->where('user_id', $request->user_id)
                        ->update([
                            'parent_imp' => $parent_imp,
                            'updated_at' => date("Y-m-d H:i:s"),
                            'updated_by' => $request->updated_by,
                        ]);
                } else {
                    // Insert a new record
                    $query = $con->table('bulletin_imp_document')->insertGetId([
                        'target_user' => $request->role_id,
                        'user_id' => $request->user_id,
                        'bulletin_id' => $id,
                        'parent_imp' => $parent_imp,
                        'created_at' => date("Y-m-d H:i:s"),
                        'created_by' => $request->created_by,
                    ]);
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Important Bulliten Board has been successfully updated');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in bulletinTeacherStar');
        }
    }
    // getStudentLeaveTypes
    public function getStudentLeaveTypes(Request $request)
    {

        // Data not found in cache, fetch from database
        try {
            $validator = Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            }


            // Generate cache key based on request data
            // get data
            $cache_time = config('constants.cache_time');
            $cache_student_leave_types = config('constants.cache_student_leave_types');

            $cacheKey = $cache_student_leave_types . $request->branch_id;

            // Check if the data is cached
            if (Cache::has($cacheKey)) {
                // Data found in cache, return cached data
                $getAllTypes = Cache::get($cacheKey);
            } else {
                // Create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // Get data from the database
                $getAllTypes = $conn->table('student_leave_types')
                    ->select('id', 'name', 'short_name')
                    ->get();

                // Store data in cache
                Cache::put($cacheKey, $getAllTypes, now()->addDay());
            }
            return $this->successResponse($getAllTypes, 'Student leave types fetched successfully');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getStudentLeaveTypes');
        }
    }
    // get Reasons By LeaveType
    public function getReasonsByLeaveType(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'student_leave_type_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // get data
                $cache_time = config('constants.cache_time');
                $cache_ReasonsByLeaveType = config('constants.cache_ReasonsByLeaveType');
                $cacheKey = $cache_ReasonsByLeaveType . $request->branch_id . $request->student_leave_type_id;
                // Check if the data is cached
                if (Cache::has($cacheKey)) {
                    // If cached, return cached data
                    $getAllReason = Cache::get($cacheKey);
                } else {
                    // create new connection
                    $conn = $this->createNewConnection($request->branch_id);
                    // get data
                    $getAllReason = $conn->table('absent_reasons as ar')
                        ->where("ar.student_leave_type_id", $request->student_leave_type_id)
                        ->get();
                    // Cache the fetched data for future requests
                    Cache::put($cacheKey, $getAllReason, now()->addHours($cache_time)); // Cache for 24 hours
                }
                return $this->successResponse($getAllReason, 'reasons by leave types fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getReasonsByLeaveType');
        }
    }
    // viewStudentLeaveDetailsRow
    function viewStudentLeaveDetailsRow(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'student_leave_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                $student_leave_id = isset($request->student_leave_id) ? $request->student_leave_id : null;
                // return $status;
                $studentDetails = $conn->table('student_leaves as lev')
                    ->select(
                        'lev.id',
                        'lev.class_id',
                        'lev.section_id',
                        'lev.student_id',
                        'lev.parent_id',
                        DB::raw("CONCAT(std.last_name, ' ', std.first_name) as name"),
                        DB::raw('DATE_FORMAT(lev.from_leave, "%d-%m-%Y") as from_leave'),
                        DB::raw('DATE_FORMAT(lev.to_leave, "%d-%m-%Y") as to_leave'),
                        'lev.total_leave',
                        'lev.document',
                        'lev.status',
                        'lev.remarks',
                        'lev.teacher_remarks',
                        'cl.name as class_name',
                        'sc.name as section_name',
                        'lev.nursing_teacher_remarks',
                        'lev.home_teacher_status',
                        'lev.nursing_teacher_status',
                        'lev.teacher_reason_id',
                        'lev.nursing_reason_id',
                        'lev.teacher_leave_type',
                        'lev.nursing_leave_type',
                        'lev.created_at',
                        'slt.name as leave_type_name',
                        'as.name as reason',
                        'sltdd.name as nursing_leave_type_name',
                        'asdd.name as nursing_reason_name',
                        'ass.name as teacher_reason_name',
                        'slts.name as teacher_leave_type_name',
                    )
                    ->join('students as std', 'lev.student_id', '=', 'std.id')
                    ->join('classes as cl', 'lev.class_id', '=', 'cl.id')
                    ->join('sections as sc', 'lev.section_id', '=', 'sc.id')
                    ->leftJoin('student_leave_types as slt', 'lev.change_lev_type', '=', 'slt.id')
                    ->leftJoin('absent_reasons as as', 'lev.reasonId', '=', 'as.id')
                    ->leftJoin('student_leave_types as slts', 'lev.teacher_leave_type', '=', 'slts.id')
                    ->leftJoin('absent_reasons as ass', 'lev.teacher_reason_id', '=', 'ass.id')
                    ->leftJoin('student_leave_types as sltdd', 'lev.nursing_leave_type', '=', 'sltdd.id')
                    ->leftJoin('absent_reasons as asdd', 'lev.nursing_reason_id', '=', 'asdd.id')
                    ->where('lev.id', $student_leave_id)
                    ->first();
                return $this->successResponse($studentDetails, 'Student row details fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in viewStudentLeaveDetailsRow');
        }
    }
    // nursingOrHomeroom
    function nursingOrHomeroom(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'teacher_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // return $status;
                $studentDetails = $conn->table('staffs')
                    ->select(
                        'id',
                        'teacher_type'
                    )
                    ->where('id', $request->teacher_id)
                    ->first();
                return $this->successResponse($studentDetails, 'home or nusing fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in nursingOrHomeroom');
        }
    }
    public function leaveTypeWiseAllReason(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // get data
                $cache_time = config('constants.cache_time');
                $cache_leaveTypeWiseAllReason = config('constants.cache_leaveTypeWiseAllReason');
                $cacheKey = $cache_leaveTypeWiseAllReason . $request->branch_id;

                // Check if the data is cached
                if (Cache::has($cacheKey)) {
                    // If cached, return cached data
                    $jsonResult = Cache::get($cacheKey);
                } else {
                    // create new connection
                    $conn = $this->createNewConnection($request->branch_id);
                    $results = $conn->select("
            SELECT 
            lt.id AS leave_type_id,
        lt.name AS leave_type,
        CONCAT('[', GROUP_CONCAT(JSON_OBJECT('id', r.id)), ']') as id,
        CONCAT('[', GROUP_CONCAT(JSON_OBJECT('reason', r.name)), ']') AS reasons
    FROM 
        student_leave_types lt
    LEFT JOIN 
        absent_reasons r ON lt.id = r.student_leave_type_id
    GROUP BY 
        lt.id
");
                    $jsonResult = json_encode($results);
                    // Cache the fetched data for future requests
                    Cache::put($cacheKey, $jsonResult, now()->addHours($cache_time)); // Cache for 24 hours
                }
                return $this->successResponse($jsonResult, 'student leave types fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in leaveTypeWiseAllReason');
        }
    }
    // callViaLeaveDirectApprove 
    public function callViaLeaveDirectApprove(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'student_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'frm_leavedate' => 'required',
                'to_leavedate' => 'required',
                'reason_id' => 'required',
                'total_leave' => 'required',
                'change_lev_type' => 'required',
                'status' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $staffConn = $this->createNewConnection($request->branch_id);
                $from_leave = date('Y-m-d', strtotime($request['frm_leavedate']));
                $to_leave = date('Y-m-d', strtotime($request['to_leavedate']));
                // check leave exist
                $fromLeaveCnt = $staffConn->table('student_leaves as lev')
                    ->where([
                        ['lev.student_id', '=', $request->student_id],
                        ['lev.class_id', '=', $request->class_id],
                        ['lev.section_id', '=', $request->section_id],
                        ['lev.from_leave', '<=', $from_leave],
                        ['lev.to_leave', '>=', $from_leave],
                    ])->count();
                $toLeaveCnt = $staffConn->table('student_leaves as lev')
                    ->where([
                        ['lev.student_id', '=', $request->student_id],
                        ['lev.class_id', '=', $request->class_id],
                        ['lev.section_id', '=', $request->section_id],
                        ['lev.from_leave', '<=', $to_leave],
                        ['lev.to_leave', '>=', $to_leave]
                    ])->count();
                if ($fromLeaveCnt > 0 || $toLeaveCnt > 0) {
                    // return 
                    return $this->validationFailureResponse([], 'You have already applied for leave between these dates');
                    // return $this->sendCommonError('You have already applied for leave between these dates', ['error' => 'You have already applied for leave between these dates']);
                } else {
                    $student_data = $staffConn->table('enrolls as en')
                        ->select(
                            'p.id'
                        )
                        ->join('students as st', 'st.id', '=', 'en.student_id')
                        ->leftjoin('parent as p', function ($join) {
                            $join->on('st.father_id', '=', 'p.id');
                            $join->orOn('st.mother_id', '=', 'p.id');
                            $join->orOn('st.guardian_id', '=', 'p.id');
                        })
                        ->where('en.active_status', '=', '0')
                        ->where('en.student_id', '=', $request->student_id)
                        ->first();
                    // dd($student_data->id);
                    // insert data
                    if (isset($request->file)) {
                        $now = now();
                        $name = strtotime($now);
                        $extension = $request->file_extension;
                        $fileName = $name . "." . $extension;
                        $path = '/public/' . $request->branch_id . '/teacher/student-leaves/';
                        $base64 = base64_decode($request->file);
                        File::ensureDirectoryExists(base_path() . $path);
                        $file = base_path() . $path . $fileName;
                        $suc = file_put_contents($file, $base64);
                    } else {
                        $fileName = null;
                    }
                    $data = [
                        'student_id' => $request['student_id'],
                        'parent_id' => isset($student_data->id) ? $student_data->id : 0,
                        'class_id' => $request['class_id'],
                        'section_id' => $request['section_id'],
                        'from_leave' => $from_leave,
                        'to_leave' => $to_leave,
                        'total_leave' => $request['total_leave'],
                        'change_lev_type' => $request['change_lev_type'],
                        'reasonid' => $request['reason_id'],
                        'remarks' => $request['remarks'],
                        'document' => $fileName,
                        'status' => $request['status'],
                        // 'home_teacher_status' => $request['status'],
                        'nursing_teacher_status' => $request['status'],
                        'direct_approval_status' => $request['direct_approval_status'],
                        'direct_approval_by' => $request['direct_approval_by'],
                        'created_at' => date("Y-m-d H:i:s")
                    ];
                    $query = $staffConn->table('student_leaves')->insert($data);
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'Direct approval successfully');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in callViaLeaveDirectApprove');
        }
    }
    public function getClassListByDept(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'teacher_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $classConn = $this->createNewConnection($request->branch_id);

                $getDeptList = $classConn->table('staffs as sf')
                    ->select('sf.department_id')
                    ->where('sf.id', $request->teacher_id)
                    ->first();
                $departmentIDs = isset($getDeptList->department_id) ? $getDeptList->department_id : null;
                // dd($departmentIDs);
                $class = $classConn->table('classes as cl')
                    ->select('cl.id', 'cl.name', 'cl.short_name', 'cl.name_numeric', 'cl.department_id', 'stf_dp.name as department_name')
                    ->join('staff_departments as stf_dp', 'cl.department_id', '=', 'stf_dp.id')
                    // ->join("staffs as sf", \DB::raw("FIND_IN_SET(sf.department_id,cl.department_id)"), ">", \DB::raw("'0'"))
                    // ->where('sf.id', '=', $request->teacher_id)
                    ->where(function ($query) use ($departmentIDs) {
                        // Explode departmentIDs string to an array
                        $departmentIDsArray = explode(",", $departmentIDs);
                        // Iterate over departmentIDs array to add conditions
                        foreach ($departmentIDsArray as $departmentID) {
                            // Add condition for each department ID using FIND_IN_SET
                            $query->orWhereRaw("FIND_IN_SET('$departmentID', cl.department_id) > 0");
                        }
                    })
                    ->orderBy('cl.department_id', 'desc')
                    ->get();
                return $this->successResponse($class, 'class by department record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getClassListByDept');
        }
    }
    public function saveStudentSetting(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'staff_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // $studentDetailsValue = $request->studentDetails ? 1 : 0;
                // $parent_details = $request->parentDetails ? 1 : 0;
                // $school_details = $request->schoolDetails ? 1 : 0;
                // $academic_details = $request->academicDetails ? 1 : 0;
                // $gradeAndClasses = $request->gradeAndClasses ? 1 : 0;
                // $attendance = $request->attendance ? 1 : 0;
                // $testResult = $request->testResult ? 1 : 0;
                // $gardeClassAcademic = $request->gardeClassAcademic;
                // $attendanceAcademic = $request->attendanceAcademic;
                // $testResultAcademic = $request->testResultAcademic;
                $staff_id = $request->staff_id;

                $old = $conn->table('student_info_download_settings')
                    ->where('staff_id', $request->staff_id)
                    ->first();
                $insertUpdateDate = array(
                    'student_info' => !empty($request->studentDetails == "true") ? "1" : "0",
                    'parent_info' =>  !empty($request->parentDetails == "true") ? "1" : "0",
                    'school_info' =>  !empty($request->schoolDetails == "true") ? "1" : "0",
                    // 'academic_info' =>  !empty($request->academicDetails == "true") ? "1" : "0",
                    // 'grade_class_info' =>  !empty($request->gradeAndClasses == "true") ? "1" : "0",
                    // 'grade_class_academic_year' => $gardeClassAcademic,
                    // 'attendance_info' =>  !empty($request->attendance == "true") ? "1" : "0",
                    // 'attendance_academic_year' => $attendanceAcademic,
                    // 'test_result_info' =>  !empty($request->testResult == "true") ? "1" : "0",
                    // 'test_result_academic_year' => $testResultAcademic,
                    'staff_id' => $staff_id,
                    'created_by' => $staff_id
                );
                if (isset($old->id)) {
                    $insertUpdateDate['updated_at'] = date("Y-m-d H:i:s");
                    $query = $conn->table('student_info_download_settings')->where('id', $old->id)->update($insertUpdateDate);
                } else {
                    $insertUpdateDate['created_at'] = date("Y-m-d H:i:s");
                    $query = $conn->table('student_info_download_settings')->insert($insertUpdateDate);
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Student Settings has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in saveStudentSetting');
        }
    }
    public function getStudentSownloadSettings(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'staff_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // insert data
                $classAssign = $createConnection->table('student_info_download_settings')
                    ->where('staff_id', $request->staff_id)
                    ->first();
                return $this->successResponse($classAssign, 'Student download row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getStudentSownloadSettings');
        }
    }
    public function downloadStudentListInformation(Request $request)
    {
        // return $request;
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'staff_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);
                $info = $Connection->table('student_info_download_settings as si')
                    ->where('si.staff_id', '=', $request->staff_id)
                    ->first();
                // dd($info);
                $student_name = isset($request->student_name) ? $request->student_name : null;
                $department_id = isset($request->department_id) ? $request->department_id : null;
                $session_id = isset($request->session_id) ? $request->session_id : null;
                $class_id = isset($request->class_id) ? $request->class_id : null;
                $section_id = isset($request->section_id) ? $request->section_id : null;
                $status = isset($request->status) ? $request->status : null;
                $academic_year = isset($request->academic_year) ? $request->academic_year : null;

                // student parent school info
                $enableStudentInfo = isset($info->student_info) ? $info->student_info : null;
                $enableParentInfo = isset($info->parent_info) ? $info->parent_info : null;
                $enableSchoolInfo = isset($info->school_info) ? $info->school_info : null;

                $enableAcademicInfo = isset($info->academic_info) ? $info->academic_info : null;
                // academic years
                $grade_class_info = isset($info->grade_class_info) ? $info->grade_class_info : null;
                $grade_class_academic_year = isset($info->grade_class_academic_year) ? $info->grade_class_academic_year : null;
                $attendance_info = isset($info->attendance_info) ? $info->attendance_info : null;
                $attendance_academic_year = isset($info->attendance_academic_year) ? $info->attendance_academic_year : null;
                $test_result_info = isset($info->test_result_info) ? $info->test_result_info : null;
                $test_result_academic_year = isset($info->test_result_academic_year) ? $info->test_result_academic_year : null;
                // $attendance_academic_year = 2;
                // $attendance_academic_year = isset($info->attendance_academic_year) ? $info->attendance_academic_year : 0;
                // student information
                $getStudentInfo = [];
                $getParentInfo = [];
                $gradeClassInfo = [];
                $attendanceInfo = [];
                $SchoolInfo = [];
                $studentMarkDetails = [];
                if ($enableStudentInfo == "1") {
                    // get student informations
                    $getStudentInfo = $Connection->table('enrolls as en')
                        ->select(
                            'en.student_id',
                            'en.attendance_no',
                            // 'en.department_id',
                            // 'en.class_id',
                            // 'en.section_id',
                            // 'en.semester_id',
                            // 'en.session_id',
                            //   DB::raw("CONCAT(st.first_name, ' ', st.last_name) as name"),
                            'st.first_name',
                            'st.last_name',
                            'st.first_name_english',
                            'st.last_name_english',
                            'st.first_name_furigana',
                            'st.last_name_furigana',
                            DB::raw("CONCAT(st.last_name_common, ' ', st.first_name_common) as common_name"),
                            'st.gender',
                            'st.birthday',
                            'st.email',
                            'st.register_no',
                            'st.passport',
                            'st.nric',
                            'st.admission_date',
                            'st.nationality',
                            'st.current_address',
                            'st.permanent_address',
                            'st.mobile_no',
                            'st.address_unit_no',
                            'st.address_condominium',
                            'st.address_street',
                            'st.address_district',
                            'st.dual_nationality',
                            'st.visa_type',
                            'st.japanese_association_membership_number_student',
                            'st.city',
                            'st.state',
                            'st.country',
                            'st.post_code',
                            'st.previous_details',
                            'st.school_country',
                            'st.school_city',
                            'st.school_state',
                            'st.school_postal_code',
                            'st.school_enrollment_status',
                            // 'st.address_condominium'
                            'cl.name as class_name',
                            'sc.name as section_name',
                            'emp.name as department_name'
                        )
                        ->leftJoin('emp_department as emp', 'en.department_id', '=', 'emp.id')
                        ->leftJoin('classes as cl', 'en.class_id', '=', 'cl.id')
                        ->leftJoin('sections as sc', 'en.section_id', '=', 'sc.id')
                        ->join('students as st', 'en.student_id', '=', 'st.id')
                        ->when($department_id, function ($q)  use ($department_id) {
                            $q->where('en.department_id', $department_id);
                        })
                        ->when($class_id, function ($q)  use ($class_id) {
                            $q->where('en.class_id', $class_id);
                        })
                        ->when($section_id, function ($q)  use ($section_id) {
                            $q->where('en.section_id', $section_id);
                        })
                        ->when($session_id, function ($query, $session_id) {
                            return $query->where('en.session_id', $session_id);
                        })
                        ->when($student_name, function ($query, $student_name) {
                            return $query->where('st.first_name', 'like', '%' . $student_name . '%')->orWhere('st.last_name', 'like', '%' . $student_name . '%');
                        })
                        ->when($status !== null, function ($query) use ($status) {
                            $query->where('en.active_status', $status);
                        })
                        // ->where('en.academic_session_id', '=', $academic_year)
                        ->groupBy('en.student_id')
                        ->get();
                    // Decrypt sensitive data if exists
                    $getStudentInfo->transform(function ($student) {
                        $student->passport = Helper::decryptStringData($student->passport);
                        $student->nric = Helper::decryptStringData($student->nric);
                        $student->mobile_no = Helper::decryptStringData($student->mobile_no);
                        $student->current_address = Helper::decryptStringData($student->current_address);
                        $student->permanent_address = Helper::decryptStringData($student->permanent_address);
                        $student->previous_school = json_decode($student->previous_details);
                        return $student;
                    });
                }
                // parent information
                if ($enableParentInfo == "1") {
                    // get parent informations
                    $getParentInfo = $Connection->table('enrolls as en')
                        ->select(
                            'en.student_id',
                            DB::raw("CASE WHEN st.father_id IS NOT NULL THEN pf.first_name  END as father_first_name"),
                            DB::raw("CASE WHEN st.father_id IS NOT NULL THEN pf.last_name END as father_last_name"),
                            DB::raw("CASE WHEN st.father_id IS NOT NULL THEN pf.first_name_furigana END as father_fur_first_name"),
                            DB::raw("CASE WHEN st.father_id IS NOT NULL THEN pf.last_name_furigana END as father_fur_last_name"),
                            DB::raw("CASE WHEN st.father_id IS NOT NULL THEN pf.first_name_english END as father_eng_first_name"),
                            DB::raw("CASE WHEN st.father_id IS NOT NULL THEN pf.last_name_english END as father_eng_last_name"),
                            DB::raw("CASE WHEN st.father_id IS NOT NULL THEN pf.nationality END as father_nationality"),
                            DB::raw("CASE WHEN st.father_id IS NOT NULL THEN pf.email END as father_email"),
                            DB::raw("CASE WHEN st.father_id IS NOT NULL THEN pf.occupation END as father_occupation"),
                            DB::raw("CASE WHEN st.father_id IS NOT NULL THEN pf.mobile_no END as father_mobile_no"),

                            // Mother's details
                            DB::raw("CASE WHEN st.mother_id IS NOT NULL THEN pm.first_name  END as mother_first_name"),
                            DB::raw("CASE WHEN st.mother_id IS NOT NULL THEN pm.last_name END as mother_last_name"),
                            DB::raw("CASE WHEN st.mother_id IS NOT NULL THEN pm.first_name_furigana END as mother_fur_first_name"),
                            DB::raw("CASE WHEN st.mother_id IS NOT NULL THEN pm.last_name_furigana END as mother_fur_last_name"),
                            DB::raw("CASE WHEN st.mother_id IS NOT NULL THEN pm.first_name_english END as mother_eng_first_name"),
                            DB::raw("CASE WHEN st.mother_id IS NOT NULL THEN pm.last_name_english END as mother_eng_last_name"),
                            DB::raw("CASE WHEN st.mother_id IS NOT NULL THEN pm.nationality END as mother_nationality"),
                            DB::raw("CASE WHEN st.mother_id IS NOT NULL THEN pm.email END as mother_email"),
                            DB::raw("CASE WHEN st.mother_id IS NOT NULL THEN pm.occupation END as mother_occupation"),
                            DB::raw("CASE WHEN st.mother_id IS NOT NULL THEN pm.mobile_no END as mother_mobile_no"),
                            // Guardian's details
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.first_name  END as guardian_first_name"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.last_name END as guardian_last_name"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.first_name_furigana END as guardian_fur_first_name"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.last_name_furigana END as guardian_fur_last_name"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.first_name_english END as guardian_eng_first_name"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.last_name_english END as guardian_eng_last_name"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.email END as guardian_email"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.occupation END as guardian_occupation"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.mobile_no END as guardian_mobile_no"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.company_name_japan END as guardian_company_name_japan"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.company_name_local END as guardian_company_name_local"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.company_phone_number END as guardian_company_phone_number"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.employment_status END as guardian_employment_status"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.japan_postalcode END as guardian_japan_postalcode"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.japan_address END as guardian_japan_address"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.japan_contact_no END as guardian_japan_contact_no"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.japan_emergency_sms END as guardian_japan_emergency_sms"),
                            DB::raw("CASE WHEN st.guardian_id IS NOT NULL THEN pg.stay_category END as guardian_japan_staycategory"),

                            // 'st.birthday',
                            // 'st.email',
                        )
                        ->join('students as st', 'en.student_id', '=', 'st.id')
                        // Join for father
                        ->leftJoin('parent as pf', 'st.father_id', '=', 'pf.id')
                        // Join for mother
                        ->leftJoin('parent as pm', 'st.mother_id', '=', 'pm.id')
                        // Join for guardian
                        ->leftJoin('parent as pg', 'st.guardian_id', '=', 'pg.id')
                        ->when($department_id, function ($q)  use ($department_id) {
                            $q->where('en.department_id', $department_id);
                        })
                        ->when($class_id, function ($q)  use ($class_id) {
                            $q->where('en.class_id', $class_id);
                        })
                        ->when($section_id, function ($q)  use ($section_id) {
                            $q->where('en.section_id', $section_id);
                        })
                        ->when($session_id, function ($query, $session_id) {
                            return $query->where('en.session_id', $session_id);
                        })
                        ->when($student_name, function ($query, $student_name) {
                            return $query->where('st.first_name', 'like', '%' . $student_name . '%')->orWhere('st.last_name', 'like', '%' . $student_name . '%');
                        })
                        ->when($status !== null, function ($query) use ($status) {
                            $query->where('en.active_status', $status);
                        })
                        //->where('en.academic_session_id', '=', $academic_year)
                        ->groupBy('en.student_id')
                        ->get();
                    // Decrypt sensitive data if exists
                    // $getParentInfo->transform(function ($student) {
                    //     $student->parent_mobile_no = Helper::decryptStringData($student->parent_mobile_no);
                    //     $student->parent_passport = Helper::decryptStringData($student->parent_passport);
                    //     $student->parent_nric = Helper::decryptStringData($student->parent_nric);
                    //     return $student;
                    // });
                    // Decrypt sensitive data if exists
                    foreach ($getParentInfo as $parent) {
                        $parent->father_mobile_no = Helper::decryptStringData($parent->father_mobile_no);
                        $parent->mother_mobile_no = Helper::decryptStringData($parent->mother_mobile_no);
                        $parent->guardian_mobile_no = Helper::decryptStringData($parent->guardian_mobile_no);
                        // $parent->parent_passport = Helper::decryptStringData($parent->parent_passport);
                        // $parent->parent_nric = Helper::decryptStringData($parent->parent_nric);
                    }
                }
                // enableSchoolInfo
                // if ($enableSchoolInfo == "1") {
                //     $SchoolInfo = $Connection->table('global_settings')
                //         ->select(
                //             'address as school_address',
                //             'mobile_no as school_mobile_no',
                //             'email as school_email'
                //         )
                //         ->first();
                // }
                // attendance information
                // if ($attendance_info == "1") {
                //     $attendanceInfo = $Connection->table('enrolls as en')
                //         ->select(
                //             'en.student_id',
                //             'en.class_id',
                //             'en.section_id',
                //             'en.academic_session_id',
                //             'en.active_status',
                //             DB::raw('COUNT(*) as "no_of_days_attendance"'),
                //             DB::raw('COUNT(CASE WHEN sad.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                //             DB::raw('COUNT(CASE WHEN sad.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                //             DB::raw('COUNT(CASE WHEN sad.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                //             DB::raw('COUNT(CASE WHEN sad.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                //         )
                //         ->join('students as st', 'en.student_id', '=', 'st.id')
                //         ->leftJoin('student_attendances_day as sad', function ($q) {
                //             $q->on('sad.student_id', '=', 'en.student_id')
                //                 ->on('sad.class_id', '=', 'en.class_id')
                //                 ->on('sad.section_id', '=', 'en.section_id');
                //         })
                //         ->when($class_id, function ($q)  use ($class_id) {
                //             $q->where('en.class_id', $class_id);
                //         })
                //         ->when($section_id, function ($q)  use ($section_id) {
                //             $q->where('en.section_id', $section_id);
                //         })
                //         ->when($session_id, function ($query, $session_id) {
                //             return $query->where('en.session_id', $session_id);
                //         })
                //         ->when($student_name, function ($query, $student_name) {
                //             return $query->where('st.first_name', 'like', '%' . $student_name . '%')->orWhere('st.last_name', 'like', '%' . $student_name . '%');
                //         })
                //         ->where('en.academic_session_id', '=', $attendance_academic_year)
                //         ->groupBy('en.student_id')
                //         ->get()->toArray();
                // }
                // if ($test_result_info == "1") {
                //     $studentMarks = $Connection->table('enrolls as en')
                //         ->select(
                //             'sm.id',
                //             'en.student_id',
                //             'cl.name as class_name',
                //             'sc.name as section_name',
                //             'sb.name as subject_name',
                //             'exp.paper_name',
                //             'sm.score',
                //             'sm.pass_fail',
                //             'sm.status',
                //             'sm.grade',
                //             'sm.points',
                //             'sm.freetext',
                //             'sm.ranking',
                //             'exp.score_type',
                //             'sm.subject_id',
                //             'sm.paper_id',
                //             'sm.grade_category',
                //             'sm.semester_id',
                //             'sm.session_id',
                //             'sm.exam_id',
                //             'en.class_id',
                //             'en.section_id',
                //             'ay.name as academic_session_name'
                //         )
                //         ->join('students as st', 'en.student_id', '=', 'st.id')
                //         ->leftJoin('classes as cl', 'en.class_id', '=', 'cl.id')
                //         ->leftJoin('sections as sc', 'en.section_id', '=', 'sc.id')
                //         ->leftJoin('student_marks as sm', function ($q) {
                //             $q->on('sm.student_id', '=', 'en.student_id')
                //                 ->on('sm.class_id', '=', 'en.class_id')
                //                 ->on('sm.section_id', '=', 'en.section_id')
                //                 ->on('sm.academic_session_id', '=', 'en.academic_session_id');
                //         })
                //         ->leftJoin('exam_papers as exp', function ($qs) {
                //             $qs->on('exp.class_id', '=', 'sm.class_id')
                //                 ->on('sm.subject_id', '=', 'sm.subject_id')
                //                 ->on('exp.id', '=', 'sm.paper_id')
                //                 ->on('sm.academic_session_id', '=', 'en.academic_session_id');
                //         })
                //         ->leftJoin('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                //         ->leftJoin('academic_year as ay', 'en.academic_session_id', '=', 'ay.id')
                //         ->when($class_id, function ($q)  use ($class_id) {
                //             $q->where('en.class_id', $class_id);
                //         })
                //         ->when($section_id, function ($q)  use ($section_id) {
                //             $q->where('en.section_id', $section_id);
                //         })
                //         ->when($session_id, function ($query, $session_id) {
                //             return $query->where('en.session_id', $session_id);
                //         })
                //         ->when($student_name, function ($query, $student_name) {
                //             return $query->where('st.first_name', 'like', '%' . $student_name . '%')->orWhere('st.last_name', 'like', '%' . $student_name . '%');
                //         })
                //         ->where('en.academic_session_id', '=', $test_result_academic_year)
                //         ->get()->groupBy('student_id');
                //     // $studentMarkDetails = array();
                //     foreach ($studentMarks as $studentId => $marks) {
                //         $object = new \stdClass();
                //         $object->student_id = $studentId;
                //         $object->all_marks = $marks;
                //         array_push($studentMarkDetails, $object);
                //     }
                // }
                // dd($getStudentInfo);
                // dd($getParentInfo);
                // dd($gradeClassInfo);
                // dd($attendanceInfo);
                // dd($studentMarkDetails);
                $collection1 = collect($getStudentInfo);
                $collection2 = collect($getParentInfo);
                $collection3 = collect($gradeClassInfo);
                $collection4 = collect($attendanceInfo);
                $collection5 = collect($studentMarkDetails);
                $collection6 = collect($SchoolInfo);
                // Merge collections based on 'student_id'
                $merged = $collection1->reduce(function ($carry, $item) use ($collection2, $collection3, $collection4, $collection5, $collection6) {

                    $matchingItem2 = $collection2->firstWhere('student_id', $item->student_id);
                    $matchingItem3 = $collection3->firstWhere('student_id', $item->student_id);
                    $matchingItem4 = $collection4->firstWhere('student_id', $item->student_id);
                    $matchingItem5 = $collection5->firstWhere('student_id', $item->student_id);
                    $matchingItem6 = $collection6;

                    $itemArray = json_decode(json_encode($item), true);
                    $matchingItem2Array = ($matchingItem2) ? json_decode(json_encode($matchingItem2), true) : [];
                    $matchingItem3Array = ($matchingItem3) ? json_decode(json_encode($matchingItem3), true) : [];
                    $matchingItem4Array = ($matchingItem4) ? json_decode(json_encode($matchingItem4), true) : [];
                    $matchingItem5Array = ($matchingItem5) ? json_decode(json_encode($matchingItem5), true) : [];
                    $matchingItem6Array = ($matchingItem6) ? json_decode(json_encode($matchingItem6), true) : [];

                    $mergedItem = array_merge($itemArray, $matchingItem2Array, $matchingItem3Array, $matchingItem4Array, $matchingItem5Array, $matchingItem6Array);
                    $carry[] = $mergedItem;
                    return $carry;
                }, []);
                return $this->successResponse($merged, 'get all subject record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in downloadStudentListInformation');
        }
    }
    // getStudentAttendenceByDay
    function getStudentAttendenceByDay(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'date' => 'required',
                'academic_session_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                // get attendance details query
                $date = date('Y-m-d', strtotime($request->date));
                $leave_date = date('Y-m-d', strtotime($request->date));
                $semester_id = $request->semester_id;
                $session_id = $request->session_id;
                $Connection = $this->createNewConnection($request->branch_id);
                $getStudentAttendence = $Connection->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        // 'en.roll_no',
                        DB::raw('CONCAT(st.last_name, " ", st.first_name) as name'),
                        'st.register_no',
                        'sa.id as att_id',
                        DB::raw('CASE 
                    WHEN stu_lev.status = "Approve" THEN "excused"
                    ELSE sa.status
                    END as att_status'),
                        'sa.remarks as att_remark',
                        'sa.date',
                        'sa.student_behaviour',
                        'sa.classroom_behaviour',
                        'sa.reasons',
                        'sapre.status as current_old_att_status',
                        'stu_lev.id as taken_leave_status',
                        'st.birthday',
                        'st.photo'
                    )
                    ->join('students as st', 'st.id', '=', 'en.student_id')
                    ->leftJoin('student_attendances_day as sa', function ($q) use ($date, $semester_id, $session_id) {
                        $q->on('sa.student_id', '=', 'st.id')
                            ->on('sa.date', '=', DB::raw("'$date'"));
                    })
                    // if already take attendance for the date
                    ->leftJoin('student_attendances_day as sapre', function ($q) use ($date, $semester_id, $session_id) {
                        $q->on('sapre.student_id', '=', 'st.id')
                            ->on('sapre.date', '=', DB::raw("'$date'"))
                            ->on('sapre.day_recent_flag', '=', DB::raw("'1'"));
                    })
                    ->leftJoin('student_leaves as stu_lev', function ($q) use ($date) {
                        $q->on('stu_lev.student_id', '=', 'st.id')
                            // ->on('stu_lev.date', '=', DB::raw("'$date'"))
                            ->on('stu_lev.status', '=', DB::raw("'Approve'"))
                            ->where('stu_lev.from_leave', '<=', $date)
                            ->where('stu_lev.to_leave', '>=', $date);
                    })
                    ->where([
                        ['en.class_id', '=', $request->class_id],
                        ['en.section_id', '=', $request->section_id],
                        ['en.academic_session_id', '=', $request->academic_session_id],
                        ['en.active_status', '=', "0"]
                    ])
                    ->groupBy('en.student_id')
                    ->get();
                $taken_attentance_status = $Connection->table('enrolls as en')
                    ->select(
                        'sa.status'
                    )
                    ->join('students as st', 'st.id', '=', 'en.student_id')
                    // if already take attendance for the date and subjects
                    ->leftJoin('student_attendances_day as sa', function ($q) use ($date, $semester_id, $session_id) {
                        $q->on('sa.student_id', '=', 'st.id')
                            ->on('sa.date', '=', DB::raw("'$date'"));
                    })
                    ->where([
                        ['en.class_id', '=', $request->class_id],
                        ['en.section_id', '=', $request->section_id]
                        // ['en.semester_id', '=', $request->semester_id],
                        // ['en.session_id', '=', $request->session_id]
                    ])
                    ->first();
                $data = [
                    "get_student_attendence" => $getStudentAttendence,
                    "taken_attentance_status" => $taken_attentance_status
                ];
                // dd($getTeachersClassName);
                return $this->successResponse($data, 'Attendance by day record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getStudentAttendenceByDay');
        }
    }
    //add attendance by day
    function addStudentAttendenceByDay(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'date' => 'required',
                'attendance' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);

                $attendance = $request->attendance;
                $class_id = $request->class_id;
                $section_id = $request->section_id;
                $semester_id = $request->semester_id;
                $session_id = $request->session_id;
                $date = $request->date;
                // if already take attendance for the date
                $checkAlreadyTakenAttendance = $Connection->table('student_attendances_day')->select('id')->where([
                    ['date', '=', $date],
                    ['class_id', '=', $class_id],
                    ['section_id', '=', $section_id],
                    ['semester_id', '=', $semester_id],
                    ['session_id', '=', $session_id],
                    ['day_recent_flag', '=', "1"]
                ])->first();
                // update flag
                if (isset($checkAlreadyTakenAttendance->id)) {
                    $Connection->table('student_attendances_day')->where([
                        ['date', '=', $date],
                        ['class_id', '=', $class_id],
                        ['section_id', '=', $section_id],
                        ['semester_id', '=', $semester_id],
                        ['session_id', '=', $session_id],
                        ['day_recent_flag', '=', "1"]
                    ])->update([
                        'day_recent_flag' => "0",
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                }
                foreach ($attendance as $key => $value) {

                    $attStatus = (isset($value['att_status']) ? $value['att_status'] : "");
                    $att_remark = (isset($value['att_remark']) ? $value['att_remark'] : "");
                    $reasons = (isset($value['reasons']) ? $value['reasons'] : "");
                    $student_behaviour = "";
                    if (isset($value['student_behaviour'])) {
                        $student_behaviour = implode(',', $value['student_behaviour']);
                    }
                    $classroom_behaviour = "";
                    if (isset($value['classroom_behaviour'])) {
                        $classroom_behaviour = implode(',', $value['classroom_behaviour']);
                    }
                    $arrayAttendance = array(
                        'student_id' => $value['student_id'],
                        'status' => $attStatus,
                        'remarks' => $att_remark,
                        'reasons' => $reasons,
                        'student_behaviour' => $student_behaviour,
                        'classroom_behaviour' => $classroom_behaviour,
                        'date' => $date,
                        'class_id' => $class_id,
                        'section_id' => $section_id,
                        'semester_id' => $semester_id,
                        'session_id' => $session_id,
                        'day_recent_flag' => "1",
                        'created_at' => date("Y-m-d H:i:s")

                    );
                    if ((empty($value['attendance_id']) || $value['attendance_id'] == "null")) {
                        $row = $Connection->table('student_attendances_day')->select('id')->where([
                            ['date', '=', $date],
                            ['class_id', '=', $class_id],
                            ['section_id', '=', $section_id],
                            ['semester_id', '=', $semester_id],
                            ['session_id', '=', $session_id],
                            ['student_id', '=', $value['student_id']]
                        ])->first();
                        if (isset($row->id)) {
                            /*$Connection->table('student_attendances_day')->where('id', $row->id)->update([
                            'status' => $attStatus,
                            'remarks' => $att_remark,
                            'reasons' => $reasons,
                            'student_behaviour' => $student_behaviour,
                            'classroom_behaviour' => $classroom_behaviour,
                            'day_recent_flag' => "1",
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);*/
                            $data = [
                                'status' => $attStatus,
                                'remarks' => $att_remark,
                                'reasons' => $reasons,
                                'student_behaviour' => $student_behaviour,
                                'classroom_behaviour' => $classroom_behaviour,
                                'day_recent_flag' => "1",
                                'updated_at' => date("Y-m-d H:i:s")
                            ];

                            $student_data = $Connection->table('students')->where('id', $value['student_id'])->first();
                            $oldData = $Connection->table('student_attendances_day')->where('id', $row->id)->first();
                            $query =  $Connection->table('student_attendances_day')->where('id', $row->id)->update($data);
                            $changes = $this->getChanges($oldData, $data);
                            $table_modify = [];
                            $table_modify['type'] = 'Student Attentance';
                            $table_modify['id'] = $value['student_id'];
                            $table_modify['name'] = $student_data->first_name . ' ' . $student_data->last_name;
                            $table_modify['email'] = $student_data->email;
                            $Connection->table('modify_datas')->insert([

                                'table_name' => 'Student Attentance',
                                'table_dbname' => 'student_attendances_day',
                                'table_dbid' => $row->id,
                                'table_id_name' => 'id',
                                'table_modify' => json_encode($table_modify),
                                'modifydata' => json_encode($changes),
                                'createdby_id' => $request->login_userid,
                                'createdby_role' => $request->login_roleid
                            ]);
                        } else {
                            $Connection->table('student_attendances_day')->insert($arrayAttendance);
                        }
                    } else {
                        /*$Connection->table('student_attendances_day')->where('id', $value['attendance_id'])->update([
                        'status' => $attStatus,
                        'remarks' => $att_remark,
                        'reasons' => $reasons,
                        'student_behaviour' => $student_behaviour,
                        'classroom_behaviour' => $classroom_behaviour,
                        'day_recent_flag' => "1",
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);*/
                        $data = [
                            'status' => $attStatus,
                            'remarks' => $att_remark,
                            'reasons' => $reasons,
                            'student_behaviour' => $student_behaviour,
                            'classroom_behaviour' => $classroom_behaviour,
                            'day_recent_flag' => "1",
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $student_data = $Connection->table('students')->where('id', $value['student_id'])->first();
                        $oldData = $Connection->table('student_attendances_day')->where('id', $value['attendance_id'])->first();
                        $query =  $Connection->table('student_attendances_day')->where('id', $value['attendance_id'])->update($data);
                        $changes = $this->getChanges($oldData, $data);
                        $table_modify = [];
                        $table_modify['type'] = 'Student Attentance';
                        $table_modify['id'] = $value['student_id'];
                        $table_modify['name'] = $student_data->first_name . ' ' . $student_data->last_name;
                        $table_modify['email'] = $student_data->email;

                        $Connection->table('modify_datas')->insert([
                            'table_name' => 'Student Attentance',
                            'table_dbname' => 'student_attendances_day',
                            'table_dbid' => $value['attendance_id'],
                            'table_id_name' => 'id',
                            'table_modify' => json_encode($table_modify),
                            'modifydata' => json_encode($changes),
                            'createdby_id' => $request->login_userid,
                            'createdby_role' => $request->login_roleid
                        ]);
                    }
                }
                return $this->successResponse([], 'Attendance added successfuly.');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addStudentAttendenceByDay');
        }
    }
    // studentNewJoiningList 
    public function studentNewJoiningList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);
                $class_id = isset($request->class_id) ? $request->class_id : null;
                $section_id = isset($request->section_id) ? $request->section_id : null;
                $academic_session_id = $request->academic_session_id;

                // Get the current year
                $currentYear = date('Y');
                // If yes, increment the year for the end date
                if (date('n') > 3) {
                    $endYear = $currentYear + 1;
                } else {
                    $endYear = $currentYear;
                }
                // Set the start and end dates
                $startDate = date('Y-m-d', strtotime('April 1st, ' . $currentYear));
                $endDate = date('Y-m-d', strtotime('March 31st, ' . $endYear));

                $data = $Connection->table('enrolls as en')
                    ->select(
                        'en.id',
                        'en.student_id',
                        'stud.register_no',
                        // 'en.class_id',
                        // 'en.section_id',
                        // 'en.academic_session_id',
                        // 'en.semester_id',
                        // 'en.session_id',
                        // 'en.active_status',
                        DB::raw("CONCAT(stud.last_name, ' ', stud.first_name) as student_name"),
                        'stud.admission_date',
                        'stud.official_date',
                        'cl.name as class_name',
                        'sc.name as section_name',
                        'emd.name as dept_name',
                        'stud.gender',
                        'stud.email',
                        'stap.status_after_approval'
                    )
                    ->join('classes as cl', 'en.class_id', '=', 'cl.id')
                    ->join('sections as sc', 'en.section_id', '=', 'sc.id')
                    ->join('students as stud', 'en.student_id', '=', 'stud.id')
                    ->join('emp_department as emd', 'en.department_id', '=', 'emd.id')
                    ->leftJoin('student_applications as stap', 'en.student_id', '=', 'stap.student_id')
                    ->when($class_id, function ($query, $class_id) {
                        return $query->where('en.class_id', $class_id);
                    })
                    ->when($section_id, function ($query, $section_id) {
                        return $query->where('en.section_id', $section_id);
                    })
                    ->where('en.active_status', '=', '0')
                    ->whereBetween('stud.official_date', [$startDate, $endDate])
                    // ->groupBy("stud.id")
                    ->get();
                return $this->successResponse($data, 'Student new joining list fetched successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in studentNewJoiningList');
        }
    }
    public function saveAttendanceReportSetting(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'staff_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                $staff_id = isset($request->staff_id) ? $request->staff_id : null;
                $department_id = isset($request->department_id) ? $request->department_id : null;
                $class_id = isset($request->class_id) ? $request->class_id : null;
                $section_id = isset($request->section_id) ? $request->section_id : null;
                $pattern = isset($request->pattern) ? $request->pattern : null;

                $old = $conn->table('attendance_report_settings')
                    ->where('staff_id', $request->staff_id)
                    ->first();
                $insertUpdateDate = array(
                    'department_id' => $department_id,
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'pattern' => $pattern,
                    'staff_id' => $staff_id,
                    'created_by' => $staff_id
                );
                if (isset($old->id)) {
                    $insertUpdateDate['updated_at'] = date("Y-m-d H:i:s");
                    $query = $conn->table('attendance_report_settings')->where('id', $old->id)->update($insertUpdateDate);
                } else {
                    $insertUpdateDate['created_at'] = date("Y-m-d H:i:s");
                    $query = $conn->table('attendance_report_settings')->insert($insertUpdateDate);
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Attendance Report Settings has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in saveAttendanceReportSetting');
        }
    }
    public function getAttendanceReportSetting(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'staff_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // insert data
                $attRep = $createConnection->table('attendance_report_settings')
                    ->where('staff_id', $request->staff_id)
                    ->first();
                return $this->successResponse($attRep, 'Attendance report row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getAttendanceReportSetting');
        }
    }
    public function absentAttendanceReport(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'staff_id' => 'required',
                'academic_session_id' => 'required',
                'pattern' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                if ($request->department_id || $request->class_id || $request->section_id) {
                    $department_id = isset($request->department_id) ? $request->department_id : null;
                    $class_id = isset($request->class_id) ? $request->class_id : null;
                    $section_id = isset($request->section_id) ? $request->section_id : null;
                } else {
                    $attReport = $createConnection->table('widget_hide_unhide')
                        ->where('staff_id', $request->staff_id)
                        ->first();
                    // dd($attReport);
                    $department_id = isset($attReport->department_id) ? $attReport->department_id : null;
                    $class_id = isset($attReport->class_id) ? $attReport->class_id : null;
                    $section_id = isset($attReport->section_id) ? $attReport->section_id : null;
                }
                $pattern = $request->pattern;
                $Day = $Month = $Term = $Year = null;
                $startDate = $endDate = $termData = $yearData =  "";
                $currentDate = date('Y-m-d');
                $type = "";
                if ($pattern == "Day") {
                    // Day // current day
                    $Day = $pattern;
                }
                if ($pattern == "Month") {
                    // Month // current month
                    $Month = $pattern;
                    // First day of the month.
                    $startDate = date('Y-m-01', strtotime($currentDate));
                    // Last day of the month.
                    $endDate = date('Y-m-t', strtotime($currentDate));
                }
                if ($pattern == "Term") {
                    // Term // term mean semester
                    $Term = $pattern;
                    $termData = $createConnection->table('semester as sm')
                        ->select(
                            'sm.id',
                            'sm.name',
                            'sm.start_date',
                            'sm.end_date'
                        )
                        ->whereRaw('"' . $currentDate . '" between `start_date` and `end_date`')
                        ->first();
                }
                if ($pattern == "Year") {
                    // Year // year mean academic id
                    $Year = $pattern;
                    $yearData = $createConnection->table('semester as sm')
                        ->select(DB::raw('MIN(sm.start_date) AS year_start_date, MAX(sm.end_date) AS year_end_date'))
                        ->where([
                            ['sm.academic_session_id', '=', $request->academic_session_id],
                        ])
                        ->get();
                }
                // dd($yearData);
                if ($department_id && $class_id === null && $section_id === null) {
                    $type = "Faculty";
                    // Department exists, Class is null, Section is null
                    $allClasses = $createConnection->table('classes')
                        ->select('id')
                        ->where('department_id', $department_id)
                        ->get()->toArray();
                    $classID = [];
                    if (isset($allClasses)) {
                        foreach ($allClasses as $key => $value) {
                            array_push($classID, $value->id);
                        }
                    }
                    $absentCountDetails = $createConnection->table('student_attendances_day')
                        ->select(
                            DB::raw('COUNT(*) as no_of_days_attendance'),
                            DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as presentCount'),
                            DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absentCount'),
                            DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as lateCount'),
                            DB::raw('SUM(CASE WHEN status = "excused" THEN 1 ELSE 0 END) as excusedCount')
                        )
                        ->whereIn('class_id', $classID)
                        // when not null comes here
                        ->when($Day, function ($q)  use ($currentDate) {
                            $q->where('date', $currentDate);
                        })
                        ->when($Month, function ($qs) use ($startDate, $endDate) {
                            $qs->where('date', '>=', $startDate)
                                ->where('date', '<=', $endDate);
                        })
                        ->when($Term, function ($qd)  use ($termData) {
                            $qd->where('date', '>=', $termData->start_date ?? now())
                                ->where('date', '<=', $termData->end_date ?? now());
                        })
                        ->when($Year, function ($qds)  use ($yearData) {
                            $qds->where('date', '>=', $yearData[0]->year_start_date ?? now())
                                ->where('date', '<=', $yearData[0]->year_end_date ?? now());
                        })
                        ->get();
                    // dd($absentCountDetails);
                } else if ($department_id && $class_id && $section_id === null) {
                    $type = "Grade";
                    // Department exists, Class exists, Section is null
                    $absentCountDetails = $createConnection->table('student_attendances_day')
                        ->select(
                            DB::raw('COUNT(*) as no_of_days_attendance'),
                            DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as presentCount'),
                            DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absentCount'),
                            DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as lateCount'),
                            DB::raw('SUM(CASE WHEN status = "excused" THEN 1 ELSE 0 END) as excusedCount')
                        )
                        // when not null comes here
                        ->when($Day, function ($q)  use ($currentDate) {
                            $q->where('date', $currentDate);
                        })
                        ->when($Month, function ($qs) use ($startDate, $endDate) {
                            $qs->where('date', '>=', $startDate)
                                ->where('date', '<=', $endDate);
                        })
                        ->when($Term, function ($qd)  use ($termData) {
                            $qd->where('date', '>=', $termData->start_date ?? now())
                                ->where('date', '<=', $termData->end_date ?? now());
                        })
                        ->when($Year, function ($qds)  use ($yearData) {
                            $qds->where('date', '>=', $yearData[0]->year_start_date ?? now())
                                ->where('date', '<=', $yearData[0]->year_end_date ?? now());
                        })
                        ->where('class_id', $class_id)
                        ->get();
                } else if ($department_id && $class_id && $section_id) {
                    $type = "Class";
                    // Department exists, Class exists, Section exists
                    $absentCountDetails = $createConnection->table('student_attendances_day')
                        ->select(
                            DB::raw('COUNT(*) as no_of_days_attendance'),
                            DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as presentCount'),
                            DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absentCount'),
                            DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as lateCount'),
                            DB::raw('SUM(CASE WHEN status = "excused" THEN 1 ELSE 0 END) as excusedCount')
                        )
                        // when not null comes here
                        ->when($Day, function ($q)  use ($currentDate) {
                            $q->where('date', $currentDate);
                        })
                        ->when($Month, function ($qs) use ($startDate, $endDate) {
                            $qs->where('date', '>=', $startDate)
                                ->where('date', '<=', $endDate);
                        })
                        ->when($Term, function ($qd)  use ($termData) {
                            $qd->where('date', '>=', $termData->start_date ?? now())
                                ->where('date', '<=', $termData->end_date ?? now());
                        })
                        ->when($Year, function ($qds)  use ($yearData) {
                            $qds->where('date', '>=', $yearData[0]->year_start_date ?? now())
                                ->where('date', '<=', $yearData[0]->year_end_date ?? now());
                        })
                        ->where([
                            ['class_id', $class_id],
                            ['section_id', $section_id]
                        ])
                        ->get();
                } else if ($department_id === null && $class_id === null && $section_id === null) {

                    $type = "Overall Faculty";
                    // Department exists, Class is null, Section is null
                    $allClasses = $createConnection->table('classes')
                        ->select('id')
                        ->get()->toArray();
                    // dd($allClasses);
                    $classID = [];
                    if (isset($allClasses)) {
                        foreach ($allClasses as $key => $value) {
                            array_push($classID, $value->id);
                        }
                    }
                    $absentCountDetails = $createConnection->table('student_attendances_day')
                        ->select(
                            DB::raw('COUNT(*) as no_of_days_attendance'),
                            DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as presentCount'),
                            DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absentCount'),
                            DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as lateCount'),
                            DB::raw('SUM(CASE WHEN status = "excused" THEN 1 ELSE 0 END) as excusedCount')
                        )
                        ->whereIn('class_id', $classID)
                        // when not null comes here
                        ->when($Day, function ($q)  use ($currentDate) {
                            $q->where('date', $currentDate);
                        })
                        ->when($Month, function ($qs) use ($startDate, $endDate) {
                            $qs->where('date', '>=', $startDate)
                                ->where('date', '<=', $endDate);
                        })
                        ->when($Term, function ($qd)  use ($termData) {
                            $qd->where('date', '>=', $termData->start_date ?? now())
                                ->where('date', '<=', $termData->end_date ?? now());
                        })
                        ->when($Year, function ($qds)  use ($yearData) {
                            $qds->where('date', '>=', $yearData[0]->year_start_date ?? now())
                                ->where('date', '<=', $yearData[0]->year_end_date ?? now());
                        })
                        ->get();
                } else {
                    // Default scenario
                    $absentCountDetails = [];
                }
                $data = [
                    'type' => $type,
                    'absent_details' => $absentCountDetails
                ];
                return $this->successResponse($data, 'Attendance report row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in absentAttendanceReport');
        }
    }
    public function studentPlanToLeave(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                $currentDate = date('Y-m-d');
                $attRep = $createConnection->table('termination as t')
                    ->select(
                        'e.id as en_id',
                        'e.class_id',
                        'e.section_id',
                        't.*',
                        'c.name as class_name',
                        'sc.name as section_name',
                        'ay.name as academic_year',
                        's.gender',
                        DB::raw("CONCAT(s.last_name_english, ' ', s.first_name_english) as name_english"),
                        DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name")
                    )
                    ->leftJoin('students as s', 's.id', '=', 't.student_id')
                    ->leftJoin('enrolls as e', function ($join) {
                        $join->on('e.student_id', '=', 's.id')
                            ->where('e.id', '=', function ($query) {
                                $query->select(DB::raw('MAX(id)'))
                                    ->from('enrolls')
                                    ->whereColumn('enrolls.student_id', '=', 'e.student_id');
                            });
                    })
                    ->leftJoin('classes as c', 'e.class_id', '=', 'c.id')
                    ->leftJoin('sections as sc', 'e.section_id', '=', 'sc.id')
                    ->leftJoin('academic_year as ay', 'e.academic_session_id', '=', 'ay.id')
                    ->where('t.termination_status', '!=', 'Approved')
                    ->where('t.date_of_termination', '<', $currentDate)
                    ->orderByDesc('e.id') // Now this will only order the results for each group
                    ->get();
                return $this->successResponse($attRep, 'student plan to leave list fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in studentPlanToLeave');
        }
    }
    public function studentTransferList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                $termination_status_flag = isset($request->termination_status_flag) ? $request->termination_status_flag : null;
                // get data
                $parent_id = $request->parent_id;
                $terminationDetails = $conn->table('termination as t')->select('t.*', 'ay.name as academic_year', 's.gender', DB::raw("CONCAT(s.last_name_english, ' ', s.first_name_english) as name_english"), DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"), 'c.name as class_name', 'sc.name as section_name')
                    ->leftJoin('students as s', 's.id', '=', 't.student_id')
                    ->leftJoin('enrolls as e', 'e.student_id', '=', 's.id')
                    ->leftJoin('classes as c', 'e.class_id', '=', 'c.id')
                    ->leftJoin('sections as sc', 'e.section_id', '=', 'sc.id')
                    ->leftJoin('academic_year as ay', 'e.academic_session_id', '=', 'ay.id')
                    ->where('e.active_status', '=', '0')
                    ->when($parent_id, function ($query, $parent_id) {
                        return $query->where('t.created_by', $parent_id);
                    })
                    ->when($termination_status_flag, function ($querys, $termination_status_flag) {
                        return $querys->where('t.termination_status', $termination_status_flag);
                    })
                    ->orderBy('t.created_by', 'desc')->get()->toArray();

                // $groupDetails = $conn->table('termination')->get()->toArray();
                return $this->successResponse($terminationDetails, 'Termination record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in studentTransferList');
        }
    }
    function hideUnhideSave(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'staff_id' => 'required',
                'unhide_data' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);

                $unhide_data = $request->unhide_data;
                $staff_id = $request->staff_id;
                foreach ($unhide_data as $key => $value) {
                    $order_no = (isset($value['order_no']) ? $value['order_no'] : 0);
                    $widget_name = (isset($value['widget_name']) ? $value['widget_name'] : null);
                    $widget_value = (isset($value['widget_value']) ? $value['widget_value'] : null);
                    $visibility = (isset($value['visibility']) ? $value['visibility'] : null);
                    $department_id = (isset($value['department_id']) ? $value['department_id'] : null);
                    $class_id = (isset($value['class_id']) ? $value['class_id'] : null);
                    $section_id = (isset($value['section_id']) ? $value['section_id'] : null);
                    $pattern = (isset($value['pattern']) ? $value['pattern'] : null);
                    $hideUnhideData = array(
                        'staff_id' => $staff_id,
                        'order_no' => $order_no,
                        'widget_name' => $widget_name,
                        'widget_value' => $widget_value,
                        'visibility' => $visibility,
                        'department_id' => $department_id,
                        'class_id' => $class_id,
                        'section_id' => $section_id,
                        'pattern' => $pattern,
                    );
                    if (isset($widget_value) && isset($widget_value) && isset($staff_id) && isset($order_no)) {
                        if ((empty($value['old_id']) || $value['old_id'] == "null")) {
                            $row = $Connection->table('widget_hide_unhide')->select('id')->where([
                                ['order_no', '=', $order_no],
                                ['staff_id', '=', $staff_id]
                            ])->first();
                            if (isset($row->id)) {

                                $hideUnhideData['updated_by'] = $staff_id;
                                $hideUnhideData['updated_at'] = date("Y-m-d H:i:s");
                                $query =  $Connection->table('widget_hide_unhide')->where('id', $row->id)->update($hideUnhideData);
                            } else {
                                $hideUnhideData['created_by'] = $staff_id;
                                $hideUnhideData['created_at'] = date("Y-m-d H:i:s");
                                $query = $Connection->table('widget_hide_unhide')->insert($hideUnhideData);
                            }
                        } else {
                            $hideUnhideData['updated_by'] = $staff_id;
                            $hideUnhideData['updated_at'] = date("Y-m-d H:i:s");
                            $query = $Connection->table('widget_hide_unhide')->where('id', $value['old_id'])->update($hideUnhideData);
                        }
                        // Delete records not present in the current data
                        $Connection->table('widget_hide_unhide')
                            ->where('staff_id', $staff_id)
                            ->whereNotIn('order_no', array_column($unhide_data, 'order_no'))
                            ->delete();
                    }
                }
                return $this->successResponse([], 'hide unhide data added successfuly.');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in hideUnhideSave');
        }
    }
    function getDataHideUnhideDashboard(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'staff_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);
                $whuData = $Connection->table('widget_hide_unhide as whu')
                    ->select(
                        'whu.*'
                    )
                    ->where('whu.staff_id', $request->staff_id)
                    ->orderBy('whu.order_no', 'asc')
                    ->get();
                return $this->successResponse($whuData, 'widget list fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getDataHideUnhideDashboard');
        }
    }
    public function staffLeaveHistoryDashboard(Request $request)
    {
        try {
            // dd($request);
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'academic_session_id' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $currentDate = date('Y-m-d');
                // dd($staff_id);
                $leaveDetails = $conn->table('staff_leaves as lev')
                    ->select(
                        'lev.id',
                        'lev.staff_id',
                        DB::raw('CONCAT(stf.last_name, " ", stf.first_name) as name'),
                        DB::raw('DATE_FORMAT(lev.from_leave, "%d-%m-%Y") as from_leave'),
                        DB::raw('DATE_FORMAT(lev.to_leave, "%d-%m-%Y") as to_leave'),
                        DB::raw('DATE_FORMAT(lev.created_at, "%d-%m-%Y") as created_at'),
                        'lev.total_leave',
                        'lt.name as leave_type_name',
                        'rs.name as reason_name',
                        'lev.reason_id',
                        'lev.document',
                        'lev.status',
                        'lev.level_one_status',
                        'lev.level_two_status',
                        'lev.level_three_status',
                        'lev.leave_reject',
                        'lev.level_one_staff_remarks',
                        'lev.level_two_staff_remarks',
                        'lev.level_three_staff_remarks',
                        'lev.remarks',
                        'lev.assiner_remarks'
                    )
                    ->join('leave_types as lt', 'lev.leave_type', '=', 'lt.id')
                    ->leftJoin('staffs as stf', 'lev.staff_id', '=', 'stf.id')
                    ->leftJoin('teacher_absent_reasons as rs', 'lev.reason_id', '=', 'rs.id')
                    ->where('lev.academic_session_id', '=', $request->academic_session_id)
                    ->where('stf.is_active', '=', '0')
                    ->where('lev.from_leave', '<=', $currentDate)
                    ->where('lev.to_leave', '>=', $currentDate)
                    ->orderBy('lev.from_leave', 'desc')
                    ->get();
                return $this->successResponse($leaveDetails, 'Staff leave details fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in staffLeaveHistoryDashboard');
        }
    }
    private function getChanges($oldData, $newData)
    {
        try {
            $changes = [];

            foreach ($newData as $key => $value) {
                if ($key != 'updated_at') {
                    if ($oldData->$key != $value) {

                        $changes[$key] = [
                            'field' => $key,
                            'old' => $oldData->$key,
                            'new' => $value,
                        ];
                    }
                }
            }

            return $changes;
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getChanges');
        }
    }
    public function encryptVariable(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $encrypt_name = Crypt::encryptString($request->name);
                $decrypt_name = Helper::decryptStringData($encrypt_name);
                $data = [
                    "encrypt_name" => $encrypt_name,
                    "decrypt_name" => $decrypt_name
                ];
                return $this->successResponse($data, 'encrypt and decrypt values');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in encryptVariable');
        }
    }
    public function decryptVariable(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $decrypt_name = Helper::decryptStringData($request->name);
                $data = [
                    "decrypt_name" => $decrypt_name
                ];
                return $this->successResponse($data, 'decrypt values');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in decryptVariable');
        }
    }
    public function passwordUpdate(Request $request)
    {
        try {
            // Set branch ID and role ID
            $branchID = "6";
            $roleID = "5";

            // Retrieve users matching criteria
            $users = User::select('id', 'password', 'email')
                ->where('role_id', $roleID)
                ->where('branch_id', $branchID)
                ->get();

            // Initialize success flag
            $success = false;

            // Update passwords
            foreach ($users as $user) {
                if ($user->email != "N/A") {
                    // $removeFourChar = substr($user->email, 4);
                    $update = User::find($user->id)->update(['password' => \Hash::make($user->email)]);
                    if ($update) {
                        $success = true;
                    }
                }
            }

            // Return response
            if ($success) {
                return $this->successResponse([], 'Passwords updated successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in passwordUpdate');
        }
    }
    public function testQueueEmail(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'email' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                try {
                    // echo "test";
                    // $content = [
                    //     'subject' => '【Suzen】アカウント情報のご案内'
                    // ];
                    $evenMoreUsers = [
                        "chlee@kddi.com.my",
                        "syakirin@kddi.com.my",
                        "chinhui1.lee@gmail.com",
                        // "kalaivani@aibots.my",
                        "karthik@aibots.my"
                    ];
                    // Define dynamic data
                    $loginId = 'abc@gmail.com';
                    $password = 'abc@gmail.comJ24';
                    // $evenMoreUsers = [
                    //     "karthik@aibots.my"
                    // ];
                    // $toUsers = [
                    //     "kalaivani@aibots.my",
                    //     "karthiksure31@gmail.com"
                    // ];
                    Mail::bcc($evenMoreUsers)
                        ->send(new TestQueueMail($loginId, $password));

                    return "Email has been sent.";
                } catch (\Exception $e) {
                    return "Failed to send email. Error: " . $e->getMessage();
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in testQueueEmail');
        }
    }
    public function testQueueEmailAllUsers(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'email' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                try {
                    // echo "test";
                    $content = [
                        'subject' => '【Suzen】アカウント情報のご案内'
                    ];


                    $allUsers = [
                        "karthik@aibots.my",
                        "karthiksure31@gmail.com"
                    ];
                    // $bccUsers = [
                    //     "chlee@kddi.com.my",
                    //     "syakirin@kddi.com.my",
                    //     "chinhui1.lee@gmail.com"
                    // ];
                    $bccUsers = [
                        "karthiksure1995@gmail.com",
                    ];
                    $loginId = 'abc@gmail.com';
                    $password = 'abc@gmail.comJ24';
                    foreach ($allUsers as $user) {
                        // dd($user);
                        Mail::to($user)
                            ->bcc($bccUsers) // Adding BCC recipient same as the email
                            ->send(new TestQueueMail($loginId, $password));
                    }

                    return "Emails have been sent.";
                } catch (\Exception $e) {
                    return "Failed to send emails. Error: " . $e->getMessage();
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in testQueueEmailAllUsers');
        }
    }

    public function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        Cache::forget($cacheKey);
    }
    public function getParentDetailsAccStudentId(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                //'token' => 'required',
                'branch_id' => 'required',
                'student_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $student_id = $request->student_id;
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $parentIds = $conn->table('students as std')
                    ->select('std.father_id', 'std.mother_id')
                    ->join('enrolls as en', 'std.id', '=', 'en.student_id')
                    ->where('std.id', '=', $student_id)
                    ->first();
                // dd($parentIds );
                if ($parentIds) {
                    // Fetch parent details from the 'parent' table using the retrieved IDs
                    $fatherDetails =  $conn->table('parent')
                        ->where('id', $parentIds->father_id)
                        ->first();
                    if ($fatherDetails) {
                        $fatherDetails->mobile_no = Helper::decryptStringData($fatherDetails->mobile_no);
                        $parentDetails['father'] = $fatherDetails;
                    }

                    $motherDetails =  $conn->table('parent')
                        ->where('id', $parentIds->mother_id)
                        ->first();
                    if ($motherDetails) {
                        $motherDetails->mobile_no = Helper::decryptStringData($motherDetails->mobile_no);
                        $parentDetails['mother'] = $motherDetails;
                    }

                    // $guardianDetails =  $conn->table('parent')
                    //     ->where('id', $parentIds->guardian_id)
                    //     ->first();

                    // Prepare parent details array
                    //     $parentDetails = [
                    //         'father' => $fatherDetails,
                    //         'mother' => $motherDetails,
                    //         //'guardian' => $guardianDetails,
                    //     ];
                }
                return $this->successResponse($parentDetails, 'Student details fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getParentDetailsAccStudentId');
        }
    }
    public function decryptEmailPassword(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $decrypt_email = Helper::decryptStringData($request->email);
                $decrypt_password = Helper::decryptStringData($request->password);
                $data = [
                    "decrypt_email" => $decrypt_email,
                    "decrypt_password" => $decrypt_password
                ];
                return $this->successResponse($data, 'Decrypt Email and password');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in decryptEmailPassword');
        }
    }
    public function clearApiCacheBranch(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);
            if ($validator->fails()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            }

            $dataClear = [
                'cache_academic_years' => "academic_years_",
                'cache_departments' => "departments_",
                'cache_classes' => "classes_",
                'cache_sections' => "sections_",
                'cache_students' => "students_",
                'cache_parentDetails' => "parentDetails_",
                'cache_Staff' => "Staff_",
                'cache_timetable' => "cache_timetable_",
                'cache_leaveTypeWiseAllReason' => "cache_leaveTypeWiseAllReason_",
                'cache_ReasonsByLeaveType' => "cache_ReasonsByLeaveType_",
                'cache_semester' => "cache_semester_",
                'cache_eventDetails' => "cache_eventDetails_",
                'cache_student_leave_types' => "student_leave_types_",
                'cache_subjects' => "subjects_",
                'cache_exam_papers' => "exam_papers_",
                'cache_exam_term' => "exam_term_",
                'cache_exam' => "exam_",
                'cache_educations' => "educations_",
                'cache_religions' => "religions_",
                'cache_race' => "race_",
                'cache_absent_reasons' => "absent_reasons_",
                'cache_late_reasons' => "late_reasons_",
                'cache_excused_reasons' => "excused_reasons_",
                'cache_holidays' => "holidays_",
                'cache_leave_types' => "leave_types_",
                'cache_get_access_menu_list' => "cache_get_access_menu_list_",
                'school_role_access' => "school_role_access_",
                'school_role_access' => "school_role_access_",
            ];

            foreach ($dataClear as $key => $prefix) {
                $cacheKey = $prefix . $request->branch_id;
                Cache::forget($cacheKey);
            }

            return $this->successResponse([], 'Clear Api Cache Branch');
        } catch (\Exception $e) {
            // Log the error or handle it accordingly
            // \Log::error('Error in clearApiCacheBranch: ' . $e->getMessage());

            // You can return a response with the error message
            return $this->sendErrorResponse('An error occurred while clearing cache.', 500);
        }
    }

    public function emailPasswordEncrypt(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'email' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                // Retrieve users matching criteria
                $users = User::select('id', 'branch_id', 'password', 'email')
                    ->where('email',  $request->email)
                    ->where('branch_id', $request->branch_id)
                    ->first();
                $decrypt_password = bcrypt($users->password);
                $data = [
                    "decrypt_password" => $decrypt_password,
                    "id" => $users->id,
                    "branch_id" => $users->branch_id,
                    "email" => $users->email,
                ];
                return $this->successResponse($data, 'Your decrypt password is above');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in emailPasswordEncrypt');
        }
    }
    // get Student List
    public function getTeacherStudentList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'class_id' => 'required'
            ]);

            $name = isset($request->student_name) ? $request->student_name : null;

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // get data
                // $cache_time = config('constants.cache_time');
                // $cache_teacher_students = config('constants.cache_teacher_students');

                // $cacheKey = $cache_teacher_students . $request->branch_id;
                // // Check if the data is cached
                // if (Cache::has($cacheKey) && !($class_id || $section_id || $status)) {
                //     // If cached and no filters are applied, return cached data
                //     \Log::info('cacheKey ' . json_encode($cacheKey));
                //     $students = Cache::get($cacheKey);
                // } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);
                $query = $con->table('enrolls as e')
                    ->select(
                        's.id',
                        'e.class_id',
                        'e.section_id',
                        DB::raw('CONCAT(s.last_name, " ", s.first_name) as name'),
                        DB::raw('CONCAT(s.last_name_common, " ", s.first_name_common) as name_common'),
                        's.register_no',
                        's.roll_no',
                        's.mobile_no',
                        's.email',
                        's.gender',
                        's.photo',
                        'e.attendance_no'
                    )
                    ->join('students as s', 'e.student_id', '=', 's.id');
                $query->where('e.class_id', $request->class_id);
                $query->where('e.active_status', '0');
                if (isset($request->section_id) && filled($request->section_id)) {
                    $query->where('e.section_id', $request->section_id);
                }

                if (isset($request->student_name) && filled($request->student_name)) {
                    $name = $request->student_name;
                    $query->where(function ($q) use ($name) {
                        $q->where('s.first_name', 'like', '%' . $name . '%')
                            ->orWhere('s.last_name', 'like', '%' . $name . '%');
                    });
                }

                $students = $query->groupBy('e.student_id')->get()->toArray();

                // Cache the fetched data for future requests, only if no filters are applied
                // if (!($class_id || $section_id || $status)) {
                //     Cache::put($cacheKey, $students, now()->addHours($cache_time)); // Cache for 24 hours
                // }
                // }
                return $this->successResponse($students, 'Student record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getTeacherStudentList');
        }
    }


    // change User Status
    public function changeUserStatus(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'status' => 'required',
            ]);
            $user_id = $request->id;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $query = User::where('id', $user_id)->update([
                    'status' => $request->status,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                if ($request->status == "1") {
                    $status = "Locked";
                } else {
                    $status = "Unlocked";
                }
                $success = [];
                if ($query) {
                    return $this->successResponse($success, $request->type . ' have been ' . $status . ' successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (\Exception $error) {
            $this->commonHelper->generalReturn('403', 'error', $error, 'Error in publishEvent');
        }
    }
    public function addStudentMedicalRecord(Request $request){
        //return $request;
        $validator = \Validator::make($request->all(), [
            // 'token' => 'required',
            'branch_id' => 'required',
        ]);

        //    return $request;
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $studentId = $request->student_id;

            // Update or Insert student medical histories
            $histories_id = $conn->table('student_medical_histories')->updateOrInsert(
                ['student_id' => $studentId],
                [
                    'heart_problem' => $request->heart_problem,
                    'epilepsy' => $request->epilepsy,
                    'measles' => $request->measles,
                    'kawasaki_disease' => $request->kawasaki_disease,
                    'febrile_convulsions' => $request->febrile_convulsion,
                    'chicken_pox' => $request->chicken_pox,
                    'scoliosis' => $request->scoliosis,
                    'tuberculosis' => $request->tuberculosis,
                    'mumps' => $request->mumps,
                    'kidny_problems' => $request->kidney_problems,
                    'others' => $request->others,
                    'rubella' => $request->rubella,
                    'diabetes' => $request->diabetes,
                    'dengue_fever' => $request->dengue_fever,
                    'operated_disease' => $request->operated_disease,
                    'injury' => $request->injury,
                    'illness' => $request->illness,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            // Update or Insert student immunization histories
            $immunization_history_id = $conn->table('student_immunization_histories')->updateOrInsert(
                ['student_id' => $studentId],
                [
                    'japanese_encephalitis' => $request->japanese_encephalitis,
                    'streptococcus_pneumoniae' => $request->streptococcus_pneumoniae,
                    'triple_antigen' => $request->triple_antigen,
                    'hib' => $request->hib,
                    'quadruple_antigen' => $request->quadruple_antigen,
                    'covid_19' => $request->covid,
                    'bcg' => $request->bcg,
                    'rabies_vaccine' => $request->rabies_vaccine,
                    'measles_rubella' => $request->measles,
                    'tetanus' => $request->tetanus,
                    'chicken_pox_imm' => $request->chicken_pox_imm,
                    'mumps_imm' => $request->mumps_imm,
                    'doctors_advised' => $request->doctors_advised,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            // Update or Insert current health conditions
            $current_health_condition_id = $conn->table('current_health_condition')->updateOrInsert(
                ['student_id' => $studentId],
                [
                    'develops_fever_easily' => $request->develops_fever,
                    'frequent_headaches' => $request->frequent_headaches,
                    'dyspepsia_stomachache_cliarrhea' => $request->dyspepsia,
                    'constipates' => $request->constipates,
                    'vomits' => $request->vomits,
                    'faints' => $request->faints,
                    'dizziness' => $request->dizziness,
                    'nettle_rash' => $request->nettle_rash,
                    'prone_to_car_sickness' => $request->prone_car_sickness,
                    'has_poor_hearing' => $request->poor_hearing,
                    'has_had_otitis_media_before' => $request->otitis_media,
                    'bleeds_from_the_nose' => $request->bleeds_nose,
                    'nasal_congestion' => $request->nasal_congestion_nose,
                    'throat_is_swollen' => $request->throat_swollen,
                    'squinted_eyes' => $request->squinted_eyes,
                    'eye_irritation_redness' => $request->eye_irritation,
                    'glasses_lenses' => $request->glasses_lenses,
                    'wrong_colour' => $request->wrong_colour,
                    'tooth_toothache' => $request->sensistive_tooth,
                    'bleed_gum' => $request->bleed_from_gum,
                    'pain_sound_jaw_joint' => $request->pain_sound_jaw_joint,
                    'orthodontics' => $request->orthodontics,
                    'any_medicine_take_daily' => $request->medicine_to_take_daily,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
           
            
            // Update or Insert student medical records
            if ($histories_id) {
               $student_medical_record_id =  $conn->table('student_medical_records')->updateOrInsert(
                    ['student_id' => $studentId, 'academic_session_id' => $request->academic_session_id],
                    [
                        'parent_id' => $request->parent_id,
                        'normal_temperature' => $request->normal_temp,
                        'hospital_name' => $request->hospital_name,
                        'doctor_name' => $request->doctor_name,
                        'insurance_yes_no' => $request->insurance,
                        'company_name' => $request->company_name,
                        'allergen_if_any' => $request->remark_allergen,
                        'anaphylactic_shock' => $request->anaphylactic,
                        'epinephrine_autoinjector' => $request->epinephrine,
                        'other_medicines' => $request->other_medicines,
                        'medical_history_id' => $histories_id,
                        'immunization_history_id' => $immunization_history_id,
                        'current_health_condition_id' => $current_health_condition_id,
                        'date' => $request->date,
                        'remarks' => $request->remarks,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                foreach ($request->allergies as $allergyType => $allergyDetails) {
                    // Retrieve the allergy ID from the student_allergies table
                    $allergyId = $conn->table('student_allergies')
                        ->where('name', $allergyDetails['name'])
                        ->value('id');
            
                    // Update or Insert into allergies_details table
                    $conn->table('allergies_details')->updateOrInsert(
                        [
                            'allergies_id' => $allergyId,
                            'student_id' => $studentId,
                            'student_medical_record_id' => $student_medical_record_id,
                        ],
                        [
                            'age_onset' => $allergyDetails['age_onset'] ?? null,
                            'under_treatment' => $allergyDetails['treatment'] ?? null,
                            'follow_up' => $allergyDetails['follow_up'] ?? null,
                            'age_treat' => $allergyDetails['treated'] ?? null,
                            'created_at' => now(),
                            'update_at' => now(),
                        ]
                    );
                }
            }
            $success = [];
            if (!$histories_id) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Student Medical Form  has been successfully saved');
            }
        }
    }
    public function getStudentMedicalRecord(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            // 'token' => 'required',
            'branch_id' => 'required',
        ]);

        //    return $request;
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);

             // Fetch medical records for the specified student_id
             $medicalRecords = $conn->table('student_medical_records as stm')
             ->select(
                 'stm.*', 
                 'sth.*', 
                 'sti.*', 
                 'stc.*'
             )
             ->leftJoin('student_medical_histories as sth', 'stm.medical_history_id', '=', 'sth.id')
             ->leftJoin('student_immunization_histories as sti', 'stm.immunization_history_id', '=', 'sti.id')
             ->leftJoin('current_health_condition as stc', 'stm.current_health_condition_id', '=', 'stc.id')
             ->where('stm.student_id', $request->student_id)
             ->where('stm.academic_session_id', $request->academic_session_id)
             ->first();
         
         // Query to fetch details from allergies_details table based on the allergies associated with the student
         $allergiesDetails = $conn->table('student_allergies as sa')     
         ->leftJoin('allergies_details as ad', function ($join) use ($request) {        
             $join->on('ad.allergies_id', '=', 'sa.id')              
             ->where('ad.student_id', '=', $request->student_id);     }) 
        ->leftJoin('student_medical_records as stm', 'ad.student_medical_record_id', '=', 'stm.id')    
         ->select('ad.*', 'sa.id as sa_id')     ->get();
       
         $indexedAllergies = [];
 
         foreach ($allergiesDetails as $key => $allergies) {
             $indexedAllergies[$allergies->sa_id] = $allergies;
                
         }
         $results = [];
         $results['student'] = $medicalRecords;
         $results['allergies'] = $indexedAllergies;
         
             return $this->successResponse($results, 'Studennt Medical list record fetch successfully');
        }
    }
    public function getStudentMedicalReportAdmin(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $branchID = $request->branch_id;
                $academic_session_id = $request->academic_session_id;
                $conn = $this->createNewConnection($request->branch_id);
                $student_id = $request->student_id;
                // get data
                $studentData = $conn->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        "sd.name as deptName",
                        'cl.name as class_name',
                        'sc.name as section_name',
                        'st.photo',
                        DB::raw('CONCAT(st.last_name, " ", st.first_name) as name'),
                        DB::raw('CASE WHEN smr.student_id IS NOT NULL THEN "Download" ELSE "N/A" END as medical_record_status')
                    )
                    // ->join('enrolls as en', 'en.student_id', '=', 'fa.student_id')
                    ->leftJoin('students as st', 'en.student_id', '=', 'st.id')
                    ->leftJoin('classes as cl', 'en.class_id', '=', 'cl.id')
                    ->leftJoin('sections as sc', 'en.section_id', '=', 'sc.id')
                    ->leftJoin('staff_departments as sd', 'en.department_id', '=', 'sd.id')
                    ->leftJoin('student_medical_records as smr', 'en.student_id', '=', 'smr.student_id')
                    ->where([
                        ['en.class_id', '=', $request->class_id],
                        ['en.section_id', '=', $request->section_id],
                        ['en.academic_session_id', '=', $request->academic_session_id],
                        ['en.department_id', '=', $request->department_id]
                    ])
                    ->when($student_id, function ($q)  use ($student_id) {
                        $q->where('en.student_id', $student_id);
                    })
                    ->groupBy('en.student_id')
                    ->orderBy('st.id', 'ASC')
                    ->get()->toArray();
                return $this->successResponse($studentData, 'get student details fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getStudentMedicalReportAdmin');
        }
    }
    public function getAllergiesNameList(Request $request){
        $validator = \Validator::make($request->all(), [
            // 'token' => 'required',
            'branch_id' => 'required',
        ]);

        //    return $request;
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
             // Fetch medical records for the specified student_id
             $allergiesNameList = $conn->table('student_allergies')
             ->select(
                 'id',
                 'name'
             )->get();

             return $this->successResponse($allergiesNameList, 'Allergies Name list record fetch successfully');
        }
    }
    public function getStudentMedicalRecordpdf(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            // 'token' => 'required',
            'branch_id' => 'required',
        ]);

        //    return $request;
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
              // Fetch medical records for the specified student_id
              $medicalRecords = $conn->table('student_medical_records as stm')
              ->select(
                  'stm.*', 
                  'sth.*', 
                  'sti.*', 
                  'stc.*',
                  DB::raw("CONCAT(st1.last_name, ' ', st1.first_name) as name"),
              )
              ->leftJoin('student_medical_histories as sth', 'stm.medical_history_id', '=', 'sth.id')
              ->leftJoin('student_immunization_histories as sti', 'stm.immunization_history_id', '=', 'sti.id')
              ->leftJoin('current_health_condition as stc', 'stm.current_health_condition_id', '=', 'stc.id')
              ->leftJoin('students as st1', 'st1.id', '=', 'stm.student_id')
              ->where('stm.student_id', $request->student_id)
              ->where('stm.academic_session_id', $request->academic_session_id)
              ->first();
              // Query to fetch details from allergies_details table based on the allergies associated with the student
         $allergiesDetails = $conn->table('student_allergies as sa')     
         ->leftJoin('allergies_details as ad', function ($join) use ($request) {        
             $join->on('ad.allergies_id', '=', 'sa.id')              
             ->where('ad.student_id', '=', $request->student_id);     }) 
        ->leftJoin('student_medical_records as stm', 'ad.student_medical_record_id', '=', 'stm.id')    
         ->select('ad.*', 'sa.id as sa_id')     ->get();
       
         $indexedAllergies = [];
 
         foreach ($allergiesDetails as $key => $allergies) {
             $indexedAllergies[$allergies->sa_id] = $allergies;
                
         }
         $getpdfData = [];
         $getpdfData['student'] = $medicalRecords;
         $getpdfData['allergies'] = $indexedAllergies;

             return $this->successResponse($getpdfData, 'Student Medical record pdf fetch successfully');
        }
    }
}
