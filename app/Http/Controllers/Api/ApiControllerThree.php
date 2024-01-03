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

class ApiControllerThree extends BaseController
{
    // get bulletin 
    public function getBuletinBoardList(Request $request)
    {

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
                    DB::raw("c.name as grade_name"),
                    DB::raw("s.name as section_name"),
                    DB::raw("GROUP_CONCAT(DISTINCT  d.name) as department_name"),
                    DB::raw('CONCAT(p.first_name, " ", p.last_name) as parent_name'),
                    DB::raw('CONCAT(st.first_name, " ", st.last_name) as student_name')
                )
                ->leftJoin('' . $main_db . '.roles as rol', function ($join) {
                    $join->on(\DB::raw("FIND_IN_SET(rol.id,b.target_user)"), ">", \DB::raw("'0'"));
                })
                ->leftJoin('classes as c', 'c.id', '=', 'b.class_id')
                ->leftJoin('sections as s', 's.id', '=', 'b.section_id')
                ->leftJoin('staff_departments as d', function ($join) {
                    $join->on(\DB::raw("FIND_IN_SET(d.id,b.department_id)"), ">", \DB::raw("'0'"));
                })
                ->leftJoin('parent as p', 'p.id', '=', 'b.parent_id')
                ->leftJoin('students as st', 'st.id', '=', 'b.student_id')
                ->where("b.status", 1)
                ->where('b.publish_end_date', '>', $currentDateTime)
                ->groupBy("b.id")
                ->orderBy('b.id', 'desc')
                ->get()->toArray();

            return $this->successResponse($buletinDetails, 'Bulletin record fetch successfully');
        }
    }
    public function addBuletinBoard(Request $request)
    {

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
            if (isset($request->file)) {
                $now = now();
                $name = strtotime($now);
                $extension = $request->file_extension;
                $fileName = $name . "." . $extension;
                $path = '/public/' . $request->branch_id . '/admin-documents/buletin_files/';
                $base64 = base64_decode($request->file);
                File::ensureDirectoryExists(base_path() . $path);
                $file = base_path() . $path . $fileName;
                $picture = file_put_contents($file, $base64);
            } else {
                $fileName = null;
            }
            $query = $conn->table('bulletin_boards')->insertGetId([
                'title' => $request->title,
                'discription' => $request->description,
                'file' => $fileName,
                'target_user' => $request->target_user,
                'status' => 1,
                'class_id' => $request->class_id,
                'section_id' => $request->section_id,
                'student_id' => $request->student_id,
                'parent_id' => $request->parent_id,
                'department_id' => $request->department_id,
                'publish_date' => $request->publish_date,
                'publish_end_date' => $request->publish_end_date,
                'publish' => !empty($request->publish == "on") ? "1" : "0",
                'add_dashboard' =>  !empty($request->add_to_dash == "on") ? "1" : "0",
                'created_by' => $request->created_by,
                'created_at' => date("Y-m-d H:i:s")
            ]);
            $target_user = $request->target_user; // Assuming $target_user is "2,5"
            $target_user_array = explode(',', $target_user);
            $target_user_array = array_map('intval', $target_user_array);

            if ($target_user_array == [2, 6]) {
                $class_id =   $request->class_id;
                $section_id = $request->section_id;
                $student_id  = $request->student_id;
                $getStudent = $conn->table('enrolls as e')->select('s.id', DB::raw('CONCAT(s.first_name, " ", s.last_name) as name'), 's.register_no', 's.roll_no', 's.mobile_no', 's.email', 's.gender', 's.photo')
                    ->leftJoin('students as s', 'e.student_id', '=', 's.id')
                    ->when($class_id, function ($query, $class_id) {
                        return $query->where('e.class_id', $class_id);
                    })
                    ->when($section_id, function ($query, $section_id) {
                        return $query->where('e.section_id', $section_id);
                    })
                    ->when($student_id, function ($query, $student_id) {
                        return $query->where('e.section_id', $student_id);
                    })
                    ->where('e.active_status', '=', "0")
                    ->groupBy('e.student_id')
                    ->get()->toArray();
                $assignerID = [];
                if (isset($getStudent)) {
                    foreach ($getStudent as $key => $value) {
                        array_push($assignerID, $value->id);
                    }
                }
                //dd($assignerID);
                // send leave notifications
                $user = User::whereIn('user_id', $assignerID)->where([
                    ['branch_id', '=', $request->branch_id]
                ])->where(function ($q) {
                    $q->where('role_id', 6);
                })->get();
                // Before sending the notification
                //\Log::info('Sending notification to users: ' . json_encode($user));
                Notification::send($user, new StudentEmail($request->branch_id));
                // After sending the notification
                //\Log::info('Notification sent successfully to users: ' . json_encode($user));
            }
            if ($target_user_array == [2, 5]) {
                $class_id =   $request->class_id;
                $section_id = $request->section_id;
                $parent_id  = $request->parent_id;
                $getParent = $conn->table('enrolls as e')->select('p.id', DB::raw('CONCAT(p.first_name, " ", p.last_name) as parent_name'))
                    ->leftJoin('students as s', 'e.student_id', '=', 's.id')
                    ->leftjoin('parent as p', function ($join) {
                        $join->on('s.father_id', '=', 'p.id');
                        $join->orOn('s.mother_id', '=', 'p.id');
                        $join->orOn('s.guardian_id', '=', 'p.id');
                    })
                    ->when($class_id, function ($query, $class_id) {
                        return $query->where('e.class_id', $class_id);
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
                $assignerID = [];
                if (isset($getParent)) {
                    foreach ($getParent as $key => $value) {
                        array_push($assignerID, $value->id);
                    }
                }
                //dd($assignerID);
                // send leave notifications
                $user = User::whereIn('user_id', $assignerID)->where([
                    ['branch_id', '=', $request->branch_id]
                ])->where(function ($q) {
                    $q->where('role_id', 5);
                })->get();
                // Before sending the notification
                // \Log::info('Sending notification to users: ' . json_encode($user));
                Notification::send($user, new ParentEmail($request->branch_id));
                // After sending the notification
                //\Log::info('Notification sent successfully to users: ' . json_encode($user));
            }
            if ($target_user_array == [2, 4]) {
                $deptId = $request->department_id;
                $getStaff = $conn->table('staffs as stf')
                    ->select(
                        'stf.id'
                    )->when($deptId, function ($query, $deptId) {
                        return $query->where('stf.department_id', $deptId);
                    })
                    ->where('stf.is_active', '=', '0')
                    ->groupBy('stf.id')
                    ->get()->toArray();
                $assignerID = [];
                if (isset($getStaff)) {
                    foreach ($getStaff as $key => $value) {
                        array_push($assignerID, $value->id);
                    }
                }
                $user = User::whereIn('user_id', $assignerID)->where([
                    ['branch_id', '=', $request->branch_id]
                ])->where(function ($q) {
                    $q->where('role_id', 4);
                })->get();

                Notification::send($user, new TeacherEmail($request->branch_id));
            }
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Bulliten Board has been successfully saved');
            }
        }
    }
    public function usernameBuletin(Request $request)
    {
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
                ->where('id', '!=', $request->user_id)
                ->get();
            //   $success = Category::all();
            return $this->successResponse($success, 'user name record fetch successfully');
        }
    }
    public function deleteBuletinBoard(Request $request)
    {
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
    }
    // get Event row details
    public function getBuletinBoardDetails(Request $request)
    {

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
                    DB::raw('CONCAT(p.first_name, " ", p.last_name) as parent_name')
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
                ->groupBy("b.id")
                ->orderBy('b.id', 'desc')
                ->where('b.id', $buletin_id)->first();
            return $this->successResponse($buletinDetails, 'Bulletin board row fetch successfully');
        }
    }
    public function updateBuletinBoard(Request $request)
    {
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
                $fileName = $request->oldfile;
            } else {
                if (isset($request->file)) {
                    $now = now();
                    $name = strtotime($now);
                    $extension = $request->file_extension;
                    $fileName = $name . "." . $extension;
                    $path = '/public/' . $request->branch_id . '/admin-documents/buletin_files/';
                    $base64 = base64_decode($request->file);
                    File::ensureDirectoryExists(base_path() . $path);
                    $file = base_path() . $path . $fileName;
                    $picture = file_put_contents($file, $base64);
                } else {
                    $fileName = null;
                }
            }
            $query = $conn->table('bulletin_boards')->where('id', $id)->update([
                'title' => $request->title,
                'discription' => $request->description,
                'file' => $fileName,
                'target_user' => $request->target_user,
                'status' => 1,
                'publish_date' => $request->publish_date,
                'publish_end_date' => $request->publish_end_dates,
                'publish' => !empty($request->publish == "on") ? "1" : "0",
                'updated_at' => date("Y-m-d H:i:s"),
                'updated_by' => $request->updated_by,
            ]);
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Bulliten Board has been successfully updated');
            }
        }
    }
    // get Student List
    public function getStudentListForBulletinBoard(Request $request)
    {
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
            $student = $con->table('enrolls as e')->select('s.id', DB::raw('CONCAT(s.first_name, " ", s.last_name) as name'), 's.register_no', 's.roll_no', 's.mobile_no', 's.email', 's.gender', 's.photo')
                ->leftJoin('students as s', 'e.student_id', '=', 's.id')
                ->when($class_id, function ($query, $class_id) {
                    return $query->where('e.class_id', $class_id);
                })
                ->when($section_id, function ($query, $section_id) {
                    return $query->where('e.section_id', $section_id);
                })
                // ->where('e.active_status', '=', "0")
                ->groupBy('e.student_id')
                ->get()->toArray();

            return $this->successResponse($student, 'Student record fetch successfully');
        }
    }
    public function getParentListForBulletinBoard(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        $class_id = $request->class_id;
        $section_id = $request->section_id;
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $con = $this->createNewConnection($request->branch_id);
            // get data
            $parent = $con->table('enrolls as e')->select('p.id', DB::raw('CONCAT(p.first_name, " ", p.last_name) as parent_name'))
                ->leftJoin('students as s', 'e.student_id', '=', 's.id')
                ->leftjoin('parent as p', function ($join) {
                    $join->on('s.father_id', '=', 'p.id');
                    $join->orOn('s.mother_id', '=', 'p.id');
                    $join->orOn('s.guardian_id', '=', 'p.id');
                })
                ->when($class_id, function ($query, $class_id) {
                    return $query->where('e.class_id', $class_id);
                })
                ->when($section_id, function ($query, $section_id) {
                    return $query->where('e.section_id', $section_id);
                })
                ->where('e.active_status', '=', "0")
                ->groupBy('p.id')
                ->get()->toArray();
            return $this->successResponse($parent, 'Parent record fetch successfully');
        }
    }
    // get Student List
    public function getRetiredList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',

        ]);

        $working_status = $request->working_status;
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $con = $this->createNewConnection($request->branch_id);

            $student = $con->table('staffs as stf')
                ->select(
                    'stf.id',
                    DB::raw('CONCAT(stf.first_name, " ", stf.last_name) as emp_name'),
                    'stf.employment_status',
                    'stf.birthday',
                    DB::raw('GROUP_CONCAT(ds.name) as designation_name'),
                    'dp.name as department_name',
                    DB::raw("stf.designation_start_date as designation_start_date"),
                    DB::raw("stf.designation_end_date as designation_end_date"),
                    'stf.joining_date',
                    'stf.releive_date',
                    'stf.updated_at',
                    'em.name'
                )
                ->leftJoin("staff_departments as dp", DB::raw("FIND_IN_SET(dp.id, stf.department_id)"), ">", DB::raw("'0'"))
                ->leftJoin("staff_designations as ds", DB::raw("FIND_IN_SET(ds.id, stf.designation_id)"), ">", DB::raw("'0'"))
                ->leftJoin("employee_types as em", DB::raw("FIND_IN_SET(em.id, stf.employee_type_id)"), ">", DB::raw("'0'"))
                ->where('stf.working_status', '=', $working_status)
                ->where('stf.is_active', '=', '0')
                ->groupBy('stf.id')
                ->orderBy('stf.created_at', 'asc');
            // Group by the formatted date
            $studentData = $student->get()->toArray();
            // dd($studentData);
            return $this->successResponse($studentData, 'Employee record fetch successfully');
        }
    }
    public function getBulletinParent(Request $request)
    {
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
                ->where('b.class_id', $class_id)
                ->where(function ($query) use ($section_id) {
                    $query->where('b.section_id', $section_id)
                        ->orWhereNull('b.section_id');
                })
                ->where("b.status", 1)
                ->where(function ($query) use ($parent_id, $role_id) {
                    $query->where('b.parent_id', $parent_id)
                        ->orWhereNull('b.parent_id')
                        ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                })
                ->where(function ($query) use ($currentDateTime) {
                    $query->where('b.publish_end_date', '>', $currentDateTime)
                        ->orWhereNull('b.publish_end_date');
                })
                ->where('b.publish_date', '<=', now())
                ->groupBy("b.id")
                ->orderBy('b.id', 'desc')
                ->get()->toArray();

            // dd($eventDetails);
            return $this->successResponse($buletinDetails, 'Bulletin record fetch successfully');
        }
    }

    public function bulletinParentStar(Request $request)
    {
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
    }
    public function getBulletinImpParent(Request $request)
    {
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
                ->where('b.class_id', $class_id)
                ->where(function ($query) use ($section_id) {
                    $query->where('b.section_id', $section_id)
                        ->orWhereNull('b.section_id');
                })
                ->where("b.status", 1)
                ->where(function ($query) use ($parent_id, $role_id) {
                    $query->where('b.parent_id', $parent_id)
                        ->orWhereNull('b.parent_id')
                        ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                })
                ->where("bi.parent_imp", '1')
                ->where(function ($query) use ($currentDateTime) {
                    $query->where('b.publish_end_date', '>', $currentDateTime)
                        ->orWhereNull('b.publish_end_date');
                })
                ->where('b.publish_date', '<=', now())
                ->groupBy("b.id")
                ->get()->toArray();

            // dd($eventDetails);
            return $this->successResponse($buletinDetails, 'Bulletin Important record fetch successfully');
        }
    }
    public function getBulletinStudent(Request $request)
    {
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
                ->where('b.class_id', $class_id)
                ->where(function ($query) use ($section_id) {
                    $query->where('b.section_id', $section_id)
                        ->orWhereNull('b.section_id');
                })
                ->where("b.status", 1)
                ->where(function ($query) use ($student_id, $role_id) {
                    $query->where('b.student_id', $student_id)
                        ->orWhereNull('b.student_id')
                        ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                })
                ->where(function ($query) use ($currentDateTime) {
                    $query->where('b.publish_end_date', '>', $currentDateTime)
                        ->orWhereNull('b.publish_end_date');
                })
                ->where('b.publish_date', '<=', now())
                ->groupBy("b.id")
                ->orderBy('b.id', 'desc')
                ->get()->toArray();

            // dd($eventDetails);
            return $this->successResponse($buletinDetails, 'Bulletin record fetch successfully');
        }
    }

    public function bulletinStudentStar(Request $request)
    {
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
    }
    public function getBulletinImpStudent(Request $request)
    {
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
                ->where('b.class_id', $class_id)
                ->where(function ($query) use ($section_id) {
                    $query->where('b.section_id', $section_id)
                        ->orWhereNull('b.section_id');
                })
                ->where("b.status", 1)
                ->where(function ($query) use ($student_id, $role_id) {
                    $query->where('b.student_id', $student_id)
                        ->orWhereNull('b.student_id')
                        ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                })
                ->where("bi.parent_imp", '1')
                ->where(function ($query) use ($currentDateTime) {
                    $query->where('b.publish_end_date', '>', $currentDateTime)
                        ->orWhereNull('b.publish_end_date');
                })
                ->where('b.publish_date', '<=', now())
                ->groupBy("b.id")
                ->get()->toArray();

            // dd($eventDetails);
            return $this->successResponse($buletinDetails, 'Bulletin Important record fetch successfully');
        }
    }
    public function getBulletinTeacher(Request $request)
    {

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
            $department = $dep->department_id;

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
                ->where(function ($query) use ($department, $role_id) {
                    $query->where('b.department_id', $department)
                        ->orWhereNull('b.department_id')
                        ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                })
                ->where("b.status", 1)
                ->where(function ($query) use ($currentDateTime) {
                    $query->where('b.publish_end_date', '>', $currentDateTime)
                        ->orWhereNull('b.publish_end_date');
                })
                ->where('b.publish_date', '<=', now())
                ->groupBy("b.id")
                ->orderBy('b.id', 'desc')
                ->get()->toArray();

            // dd($eventDetails);
            return $this->successResponse($buletinDetails, 'Bulletin record fetch successfully');
        }
    }
    public function getBulletinImpTeacher(Request $request)
    {

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
            $department = $dep->department_id;

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
                ->where(function ($query) use ($department, $role_id) {
                    $query->where('b.department_id', $department)
                        ->orWhereNull('b.department_id')
                        ->whereRaw("FIND_IN_SET('$role_id', b.target_user)");
                })
                ->where("b.status", 1)
                ->where("bi.parent_imp", '1')
                ->where(function ($query) use ($currentDateTime) {
                    $query->where('b.publish_end_date', '>', $currentDateTime)
                        ->orWhereNull('b.publish_end_date');
                })
                ->where('b.publish_date', '<=', now())
                ->groupBy("b.id")
                ->orderBy('b.id', 'desc')
                ->get()->toArray();

            // dd($eventDetails);
            return $this->successResponse($buletinDetails, 'Bulletin record fetch successfully');
        }
    }
    public function bulletinTeacherStar(Request $request)
    {
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
    }
    // getStudentLeaveTypes
    public function getStudentLeaveTypes(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            $getAllTypes = $conn->table('student_leave_types')
                ->select('id', 'name', 'short_name')
                ->get();
            return $this->successResponse($getAllTypes, 'student leave types fetch successfully');
        }
    }
    // get Reasons By LeaveType
    public function getReasonsByLeaveType(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'student_leave_type_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            $getAllReason = $conn->table('absent_reasons as ar')
                ->where("ar.student_leave_type_id", $request->student_leave_type_id)
                ->get();
            return $this->successResponse($getAllReason, 'reasons by leave types fetch successfully');
        }
    }
    // viewStudentLeaveDetailsRow
    function viewStudentLeaveDetailsRow(Request $request)
    {
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
                    DB::raw("CONCAT(std.first_name, ' ', std.last_name) as name"),
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
    }
    // nursingOrHomeroom
    function nursingOrHomeroom(Request $request)
    {
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
    }
    public function leaveTypeWiseAllReason(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $results = $conn->select("
    SELECT 
        lt.name AS leave_type,
        CONCAT('[', GROUP_CONCAT(JSON_OBJECT('reason', r.name)), ']') AS reasons
    FROM 
        student_leave_types lt
    LEFT JOIN 
        absent_reasons r ON lt.id = r.student_leave_type_id
    GROUP BY 
        lt.id
");
            $jsonResult = json_encode($results);
            return $this->successResponse($jsonResult, 'student leave types fetch successfully');
        }
    }
    // callViaLeaveDirectApprove 
    public function callViaLeaveDirectApprove(Request $request)
    {
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
                return $this->send422Error('You have already applied for leave between these dates', ['error' => 'You have already applied for leave between these dates']);
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
    }
    public function getClassListByDept(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'teacher_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $classConn = $this->createNewConnection($request->branch_id);
            $class = $classConn->table('classes as cl')
                ->select('cl.id', 'cl.name', 'cl.short_name', 'cl.name_numeric', 'cl.department_id', 'stf_dp.name as department_name')
                ->join('staff_departments as stf_dp', 'cl.department_id', '=', 'stf_dp.id')
                ->join("staffs as sf", \DB::raw("FIND_IN_SET(sf.department_id,cl.department_id)"), ">", \DB::raw("'0'"))
                ->where('sf.id', '=', $request->teacher_id)
                ->orderBy('cl.department_id', 'desc')
                ->get();
            return $this->successResponse($class, 'class by department record fetch successfully');
        }
    }
    public function downloadStudentListInformation(Request $request)
    {
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
            $class_id = isset($request->class_id) ? $request->class_id : null;
            $section_id = isset($request->section_id) ? $request->section_id : null;
            $enableStudentInfo = isset($info->student_info) ? $info->student_info : null;
            $attendance_academic_year = 2;
            // $attendance_academic_year = isset($info->attendance_academic_year) ? $info->attendance_academic_year : 0;
            // student information
            if ($enableStudentInfo == "0") {
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
                        DB::raw("CONCAT(st.first_name, ' ', st.last_name) as name"),
                        DB::raw("CONCAT(st.first_name_english, ' ', st.last_name_english) as eng_name"),
                        DB::raw("CONCAT(st.first_name_furigana, ' ', st.last_name_furigana) as fur_name"),
                        'st.gender',
                        'st.birthday',
                        'st.email',
                        // 'cl.name as class_name',
                        // 'sc.name as section_name',
                        // 'emp.name as department_name'
                    )
                    // ->leftJoin('emp_department as emp', 'en.department_id', '=', 'emp.id')
                    // ->leftJoin('classes as cl', 'en.class_id', '=', 'cl.id')
                    // ->leftJoin('sections as sc', 'en.section_id', '=', 'sc.id')
                    ->join('students as st', 'en.student_id', '=', 'st.id')
                    ->when($class_id, function ($q)  use ($class_id) {
                        $q->where('en.class_id', $class_id);
                    })
                    ->when($section_id, function ($q)  use ($section_id) {
                        $q->where('en.section_id', $section_id);
                    })
                    ->where('en.active_status', '=', '0')
                    ->groupBy('en.student_id')
                    ->get()->toArray();
            }
            // parent information
            if ($enableStudentInfo == "0") {
                // get parent informations
                $getParentInfo = $Connection->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        // 'en.attendance_no',
                        // 'en.department_id',
                        // 'en.class_id',
                        // 'en.section_id',
                        // 'en.semester_id',
                        // 'en.session_id',
                        DB::raw("CONCAT(p.first_name, ' ', p.last_name) as parent_name"),
                        DB::raw("CONCAT(p.first_name_english, ' ', p.last_name_english) as parent_eng_name"),
                        DB::raw("CONCAT(p.first_name_furigana, ' ', p.last_name_furigana) as parent_fur_name"),
                        'p.nationality',
                        'p.gender as parent_gender',
                        'p.date_of_birth as parent_dob',
                        'p.education as parent_education',
                        'p.email as parent_email',
                        // 'st.birthday',
                        // 'st.email',
                    )
                    ->join('students as st', 'en.student_id', '=', 'st.id')
                    ->leftjoin('parent as p', function ($join) {
                        $join->on('st.father_id', '=', 'p.id');
                        $join->orOn('st.mother_id', '=', 'p.id');
                        $join->orOn('st.guardian_id', '=', 'p.id');
                    })
                    ->when($class_id, function ($q)  use ($class_id) {
                        $q->where('en.class_id', $class_id);
                    })
                    ->when($section_id, function ($q)  use ($section_id) {
                        $q->where('en.section_id', $section_id);
                    })
                    ->where('en.active_status', '=', '0')
                    ->groupBy('en.student_id')
                    ->get()->toArray();
            }
            // if ($enableStudentInfo == "0") {
            //     // get school informations
            //     $globalSettings = $Connection->table('global_settings as gls')
            //         ->select('gls.*')
            //         ->first();
            //     // echo "<pre>";
            //     // print_r($globalSettings);
            // }
            // grade and class information
            if ($enableStudentInfo == "0") {
                $gradeClassInfo = $Connection->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'cl.name as class_name',
                        'sc.name as section_name',
                        'emp.name as department_name'
                    )
                    ->leftJoin('emp_department as emp', 'en.department_id', '=', 'emp.id')
                    ->leftJoin('classes as cl', 'en.class_id', '=', 'cl.id')
                    ->leftJoin('sections as sc', 'en.section_id', '=', 'sc.id')
                    ->join('students as st', 'en.student_id', '=', 'st.id')
                    ->when($class_id, function ($q)  use ($class_id) {
                        $q->where('en.class_id', $class_id);
                    })
                    ->when($section_id, function ($q)  use ($section_id) {
                        $q->where('en.section_id', $section_id);
                    })
                    ->where('en.active_status', '=', '0')
                    ->groupBy('en.student_id')
                    ->get()->toArray();
            }
            // attendance information
            if ($enableStudentInfo == "0") {
                $attendanceInfo = $Connection->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'en.class_id',
                        'en.section_id',
                        'en.academic_session_id',
                        'en.active_status',
                        DB::raw('COUNT(*) as "no_of_days_attendance"'),
                        DB::raw('COUNT(CASE WHEN sad.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                        DB::raw('COUNT(CASE WHEN sad.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                        DB::raw('COUNT(CASE WHEN sad.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                        DB::raw('COUNT(CASE WHEN sad.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                    )
                    ->leftJoin('student_attendances_day as sad', function ($q) {
                        $q->on('sad.student_id', '=', 'en.student_id')
                            ->on('sad.class_id', '=', 'en.class_id')
                            ->on('sad.section_id', '=', 'en.section_id');
                    })
                    ->when($class_id, function ($q)  use ($class_id) {
                        $q->where('en.class_id', $class_id);
                    })
                    ->when($section_id, function ($q)  use ($section_id) {
                        $q->where('en.section_id', $section_id);
                    })
                    ->where('en.academic_session_id', '=', $attendance_academic_year)
                    ->groupBy('en.student_id')
                    ->get()->toArray();
            }
            if ($enableStudentInfo == "0") {
                $studentMarks = $Connection->table('enrolls as en')
                    ->select(
                        'sm.id',
                        'en.student_id',
                        'cl.name as class_name',
                        'sc.name as section_name',
                        'sb.name as subject_name',
                        'exp.paper_name',
                        'sm.score',
                        'sm.pass_fail',
                        'sm.status',
                        'sm.grade',
                        'sm.points',
                        'sm.freetext',
                        'sm.ranking',
                        'exp.score_type',
                        'sm.subject_id',
                        'sm.paper_id',
                        'sm.grade_category',
                        'sm.semester_id',
                        'sm.session_id',
                        'sm.exam_id',
                        'en.class_id',
                        'en.section_id',
                        'ay.name as academic_session_name'
                    )
                    ->leftJoin('classes as cl', 'en.class_id', '=', 'cl.id')
                    ->leftJoin('sections as sc', 'en.section_id', '=', 'sc.id')
                    ->leftJoin('student_marks as sm', function ($q) {
                        $q->on('sm.student_id', '=', 'en.student_id')
                            ->on('sm.class_id', '=', 'en.class_id')
                            ->on('sm.section_id', '=', 'en.section_id')
                            ->on('sm.academic_session_id', '=', 'en.academic_session_id');
                    })
                    ->leftJoin('exam_papers as exp', function ($qs) {
                        $qs->on('exp.class_id', '=', 'sm.class_id')
                            ->on('sm.subject_id', '=', 'sm.subject_id')
                            ->on('exp.id', '=', 'sm.paper_id')
                            ->on('sm.academic_session_id', '=', 'en.academic_session_id');
                    })
                    ->leftJoin('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                    ->leftJoin('academic_year as ay', 'en.academic_session_id', '=', 'ay.id')
                    ->when($class_id, function ($q)  use ($class_id) {
                        $q->where('en.class_id', $class_id);
                    })
                    ->when($section_id, function ($q)  use ($section_id) {
                        $q->where('en.section_id', $section_id);
                    })
                    ->where('en.academic_session_id', '=', $attendance_academic_year)
                    ->get()->groupBy('student_id');
                $studentMarkDetails = array();
                foreach ($studentMarks as $studentId => $marks) {
                    $object = new \stdClass();
                    $object->student_id = $studentId;
                    $object->all_marks = $marks;
                    array_push($studentMarkDetails, $object);
                }
            }
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
            // Merge collections based on 'student_id'
            $merged = $collection1->reduce(function ($carry, $item) use ($collection2, $collection3, $collection4, $collection5) {
                
                $matchingItem2 = $collection2->firstWhere('student_id', $item->student_id);
                $matchingItem3 = $collection3->firstWhere('student_id', $item->student_id);
                $matchingItem4 = $collection4->firstWhere('student_id', $item->student_id);
                $matchingItem5 = $collection5->firstWhere('student_id', $item->student_id);

                $itemArray = json_decode(json_encode($item), true);
                $matchingItem2Array = ($matchingItem2) ? json_decode(json_encode($matchingItem2), true) : [];
                $matchingItem3Array = ($matchingItem3) ? json_decode(json_encode($matchingItem3), true) : [];
                $matchingItem4Array = ($matchingItem4) ? json_decode(json_encode($matchingItem4), true) : [];
                $matchingItem5Array = ($matchingItem5) ? json_decode(json_encode($matchingItem5), true) : [];

                $mergedItem = array_merge($itemArray, $matchingItem2Array, $matchingItem3Array,$matchingItem4Array, $matchingItem5Array);
                $carry[] = $mergedItem;
                return $carry;
            }, []);
            return $this->successResponse($merged, 'get all subject record fetch successfully');
        }
    }
}
