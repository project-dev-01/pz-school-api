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
                    'as.name as reason',
                    'slt.name as leave_type_name',
                    'asdd.name as nursing_reason_name',
                    'sltdd.name as nursing_leave_type_name',
                    'ass.name as teacher_reason_name',
                    'slts.name as teacher_leave_type_name',
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
                    'lev.created_at'
                )
                ->join('students as std', 'lev.student_id', '=', 'std.id')
                ->join('classes as cl', 'lev.class_id', '=', 'cl.id')
                ->join('sections as sc', 'lev.section_id', '=', 'sc.id')
                ->leftJoin('student_leave_types as slt', 'lev.change_lev_type', '=', 'slt.id')
                ->leftJoin('absent_reasons as as', 'lev.reasonId', '=', 'as.id')
                ->leftJoin('student_leave_types as slts', 'lev.teacher_leave_type', '=', 'slt.id')
                ->leftJoin('absent_reasons as ass', 'lev.teacher_reason_id', '=', 'as.id')
                ->leftJoin('student_leave_types as sltdd', 'lev.nursing_leave_type', '=', 'slt.id')
                ->leftJoin('absent_reasons as asdd', 'lev.nursing_reason_id', '=', 'as.id')
                ->where('lev.id', $student_leave_id)
                ->first();
            return $this->successResponse($studentDetails, 'Student row details fetch successfully');
        }
    }
}
