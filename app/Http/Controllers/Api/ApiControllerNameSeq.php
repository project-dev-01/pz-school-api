<?php

namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
// base controller add
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
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
use App\Notifications\SendEmail;
use App\Notifications\LeaveApply;
use App\Notifications\StudentLeaveApply;
use App\Notifications\LeaveApprove;
use App\Notifications\StudentHomeworkSubmit;
use App\Notifications\TeacherHomework;
use App\Notifications\ParentEmail;
use App\Notifications\StudentEmail;
use App\Notifications\TeacherEmail;
use App\Notifications\ParentTermination;
use App\Notifications\AdminTermination;
use App\Notifications\ParentInfoUpdate;
use App\Notifications\StudentInfoUpdate;
use App\Notifications\LeaveReasonNotification;
use App\Notifications\newApplication;

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
use App\Helpers\CommonHelper;

class ApiControllerNameSeq extends BaseController
{
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper) {
        $this->commonHelper = $commonHelper;
    }
    
    
    // get assign teacher subject
    public function getTeacherListSubject(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'academic_session_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            $name_status = $request->name_status;

            $success = $createConnection->table('subject_assigns as sa')
                ->select(
                    'sa.id',
                    'sa.class_id',
                    DB::raw("CONCAT(st." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', st." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as teacher_name"),
                    // DB::raw("CONCAT(st.first_name, ' ', st.last_name) as teacher_name"),
                    'sa.section_id',
                    'sa.subject_id',
                    'sa.teacher_id',
                    'sa.type',
                    's.name as section_name',
                    'sb.name as subject_name',
                    'c.name as class_name',
                    'stf_dp.name as department_name'
                )
                ->join('sections as s', 'sa.section_id', '=', 's.id')
                ->join('staffs as st', 'sa.teacher_id', '=', 'st.id')
                ->join('subjects as sb', 'sa.subject_id', '=', 'sb.id')
                ->join('classes as c', 'sa.class_id', '=', 'c.id')
                ->leftJoin('staff_departments as stf_dp', 'sa.department_id', '=', 'stf_dp.id')
                ->where('sa.academic_session_id', $request->academic_session_id)
                ->orderBy('sa.department_id', 'desc')
                ->get();
            return $this->successResponse($success, 'Teacher record fetch successfully');
        }
    }


    // getEmployeeListNEW
    public function getEmployeeList(Request $request)
    {
        try {
        // get data
        $cache_time = config('constants.cache_time');
        $cache_Staff = config('constants.cache_Staff');
        $cacheKey = $cache_Staff . $request->branch_id;
        
        // Check if the data is cached
        if (Cache::has($cacheKey)) {
            // If cached, return cached data
            $Staff = Cache::get($cacheKey);
        } else {
        // create new connection
        $main_db = config('constants.main_db');
       
        $Connection = $this->createNewConnection($request->branch_id);
        
        $name_status = $request->name_status;
       
        $Staff = $Connection->table('staffs as s')
        ->select(
            DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
            DB::raw('CONCAT(s.' . ($name_status == 0 ? 'last_name_english' : 'first_name_english') . ', " ", s.' . ($name_status == 0 ? 'first_name_english' : 'last_name_english') . ") as english_emp_name"),
            DB::raw('CONCAT(s.' . ($name_status == 0 ? 'last_name_furigana' : 'first_name_furigana') . ', " ", s.' . ($name_status == 0 ? 'first_name_furigana' : 'last_name_furigana') . ") as furigana_emp_name"),
            
            // DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"),
            // DB::raw('CONCAT(s.last_name_english, " ", s.first_name_english) as english_emp_name'),
            // DB::raw('CONCAT(s.last_name_furigana, " ", s.first_name_furigana) as furigana_emp_name'),
            's.id',
            's.short_name',
            's.salary_grade',
            's.email',
            's.gender',
            's.height',
            's.weight',
            's.allergy',
            's.blood_group',
            's.employment_status',
            'stps.name as staff_position_name',
            'stc.name as staff_category_name',
            's.birthday',
            's.nationality',
            're.name as religion_name',
            's.mobile_no',
            's.photo',
            's.is_active',
            'stp.name as stream_type',
            DB::raw("GROUP_CONCAT(DISTINCT  dp.name) as department_name"),
            DB::raw("GROUP_CONCAT(DISTINCT  ds.name) as designation_name"),
            's.joining_date',
            'em.name as employee_name'
        )
        ->leftJoin("staff_departments as dp", DB::raw("FIND_IN_SET(dp.id,s.department_id)"), ">", DB::raw("'0'"))
        ->leftJoin("staff_designations as ds", DB::raw("FIND_IN_SET(ds.id,s.designation_id)"), ">", DB::raw("'0'"))
        ->leftJoin("employee_types as em", DB::raw("FIND_IN_SET(em.id, s.employee_type_id)"), ">", DB::raw("'0'"))
        ->leftJoin('stream_types as stp', 's.stream_type_id', '=', 'stp.id')
        ->leftJoin('religions as re', 's.religion', '=', 're.id')
        ->leftJoin('staff_categories as stc', 's.staff_category', '=', 'stc.id')
        ->leftJoin('staff_positions as stps', 's.staff_position', '=', 'stps.id')
        ->where('s.is_active', '=', '0')
        ->whereNull('s.deleted_at')
        ->orderBy('stp.name', 'desc')
        ->orderBy('s.salary_grade', 'desc')
        ->groupBy("s.id")
        ->get();
      
        // Cache the fetched data for future requests
        Cache::put($cacheKey, $Staff, now()->addHours($cache_time)); // Cache for 24 hours
        }
        return $this->successResponse($Staff, 'Staff record fetch successfully');
         }
        catch(\Exception $error) {
            $this->commonHelper->generalReturn('403','error',$error,'getSectionDetails');
        }
    }


    // getEmployeeDetails row details
    public function getEmployeeDetails(Request $request)
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
            $staffConn = $this->createNewConnection($request->branch_id);
            $branch_id = $request->branch_id;
            $name_status = $request->name_status;

            // get data
            $getEmpDetails = $staffConn->table('staffs as s')
                ->select(
                    's.id',
                    's.teacher_type',
                    's.first_name',
                    's.last_name',
                    's.employment_status',
                    's.short_name',
                    's.department_id',
                    's.designation_id',
                    's.staff_qualification_id',
                    's.stream_type_id',
                    's.race',
                    's.joining_date',
                    's.releive_date',
                    's.birthday',
                    's.gender',
                    's.religion',
                    's.height',
                    's.weight',
                    's.allergy',
                    's.blood_group',
                    's.city',
                    's.state',
                    's.country',
                    's.post_code',
                    's.present_address',
                    's.permanent_address',
                    's.mobile_no',
                    's.email',
                    's.photo',
                    's.facebook_url',
                    's.linkedin_url',
                    's.twitter_url',
                    's.salary_grade',
                    's.staff_category',
                    's.staff_position',
                    's.nric_number',
                    's.passport',
                    's.status',
                    's.employee_type_id',
                    's.job_title_id',
                    's.designation_start_date',
                    's.designation_end_date',
                    's.department_start_date',
                    's.department_end_date',
                    's.employee_type_start_date',
                    's.employee_type_end_date',
                    DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ',s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),

                    // DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"),
                    DB::raw("GROUP_CONCAT(DISTINCT  dp.name) as department_name"),
                    's.first_name_english',
                    's.last_name_english',
                    's.first_name_furigana',
                    's.last_name_furigana',
                    's.passport_expiry_date',
                    's.passport_photo',
                    's.visa_number',
                    's.visa_expiry_date',
                    's.visa_photo',
                    's.nationality',
                )
                ->leftJoin("staff_departments as dp", DB::raw("FIND_IN_SET(dp.id,s.department_id)"), ">", DB::raw("'0'"))
                ->where('s.id', $id)
                ->get();
            $staffObj = new \stdClass();
            if (!empty($getEmpDetails)) {
                foreach ($getEmpDetails as $suc) {
                    $staffObj = $suc;
                    $staffObj->present_address = Helper::decryptStringData($suc->present_address);
                    $staffObj->permanent_address = Helper::decryptStringData($suc->permanent_address);
                    $staffObj->mobile_no = Helper::decryptStringData($suc->mobile_no);
                    $staffObj->nric_number = Helper::decryptStringData($suc->nric_number);
                    $staffObj->passport = Helper::decryptStringData($suc->passport);
                }
            }
            $empDetails['staff'] = $staffObj;
            $empDetails['bank'] = $staffConn->table('staff_bank_accounts')->where('staff_id', $id)->first();
            $staffRoles = array('4', '3', '2');
            $sql = "";
            for ($x = 0; $x < count($staffRoles); $x++) {
                $getRow = User::where('user_id', $id)
                    ->where('branch_id', $request->branch_id)
                    ->whereRaw("find_in_set('$staffRoles[$x]',role_id)")
                    ->first();
                if (isset($getRow->id)) {
                    $sql = $getRow;
                    break;
                }
            }
            $empDetails['user'] = $sql;
            return $this->successResponse($empDetails, 'Employee row fetch successfully');
        }
         }
        catch(\Exception $error) {
            $this->commonHelper->generalReturn('403','error',$error,'Error in getEmployeeDetails');
        }
    }
     
    // getAttendanceList
    function getAttendanceList(Request $request)
    {
        //  return $request;   
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'year_month' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $year_month = explode('-', $request->year_month);
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // $getAttendanceList = $Connection->table('students as stud')
            //     ->select(
            //         'stud.first_name',
            //         'stud.last_name',
            //         'sa.id',
            //         'sa.date',
            //         'sa.status',
            //     )
            //     ->join('student_attendances as sa', 'sa.student_id', '=', 'stud.id')
            //     ->join('enrolls as en', function ($join) {
            //         $join->on('stud.id', '=', 'en.student_id')
            //             ->on('sa.class_id', '=', 'en.class_id')
            //             ->on('sa.section_id', '=', 'en.section_id');
            //     })
            //     ->where([
            //         ['stud.id', '=', $request->student_id],
            //         ['sa.subject_id', '=', $request->subject_id]
            //     ])
            //     ->whereMonth('sa.date', $year_month[0])
            //     ->whereYear('sa.date', $year_month[1])
            //     ->groupBy('sa.date')
            //     ->orderBy('sa.date', 'asc')
            //     ->get();
                $student_id = $request->student_id;
                $getAttendanceList = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.academic_session_id',
                    'en.active_status',
                        'st.first_name',
                        'st.last_name',
                        'sad.student_id',
                        'c.name as class_name', 
                        's.name as section_name',
                        'sad.date',
                        'sad.status',
                        'sad.remarks',
                        'st.photo',
                        'sad.homeroom_teacher_remarks',
                        DB::raw("CONCAT(st.last_name_english, ' ', st.first_name_english) as name_english"),
                    DB::raw('COUNT(*) as "no_of_days_attendance"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                )
                ->join('students as st', 'en.student_id', '=', 'st.id')
                ->leftJoin('classes as c', 'en.class_id', '=', 'c.id')
                ->leftJoin('sections as s', 'en.section_id', '=', 's.id')
                ->leftJoin('student_attendances_day as sad', function ($q) {
                    $q->on('sad.student_id', '=', 'en.student_id')
                        ->on('sad.class_id', '=', 'en.class_id')
                        ->on('sad.section_id', '=', 'en.section_id');
                })
                ->when($student_id, function ($query, $student_id) {
                    return $query->where('sad.student_id', $student_id);
                })
                ->whereMonth('sad.date', $year_month[0])
                ->whereYear('sad.date', $year_month[1])
                ->where('en.academic_session_id', '=', $request->academic_session_id)
                // ->groupBy('en.student_id')
                ->get()->toArray();
                
                // dd($getAttendanceList);
                $studentDetails = array();
                if (!empty($getAttendanceList)) {
                    foreach ($getAttendanceList as $value) {
                        $object = new \stdClass();

                        $object->first_name = $value->first_name;
                        $object->last_name = $value->last_name;
                        $object->student_id = $value->student_id;
                        $object->presentCount = $value->presentCount;
                        $object->absentCount = $value->absentCount;
                        $object->lateCount = $value->lateCount;
                        $object->excusedCount = $value->excusedCount;
                        $student_id = $value->student_id;
                        $object->photo = $value->photo;
                        $date = $value->date;
                        $getStudentsAttData = $this->getAttendanceByDateStudentParent($request, $student_id, $date);
                        // dd($date);
                        $object->attendance_details = $getStudentsAttData;

                        array_push($studentDetails, $object);
                    }
                }
            $data = [
                'get_attendance_list' => $studentDetails,
            ];
            return $this->successResponse($data, 'attendance record fetch successfully');
        }
    }
   
    // get attendance list teacher
    function getAttendanceListTeacher(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'year_month' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $year_month = explode('-', $request->year_month);
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);            
            $name_status = $request->name_status;
                
            $student_name = isset($request->student_name) ? $request->student_name : null;
            $session_id = isset($request->session_id) ? $request->session_id : null;
            $student_id = isset($request->student_id) ? $request->student_id : null;
            $class_id = isset($request->class_id) ? $request->class_id : null;
            $section_id = isset($request->section_id) ? $request->section_id : null;
            $attendance_academic_year = isset($request->academic_session_id) ? $request->academic_session_id : null;
           
            // return $request;
            if($request->pattern=="Month"){
                
                if ($request->student_id) {
                        $getAttendanceList = $Connection->table('enrolls as en')
                        ->select(
                            'en.student_id',
                            'en.class_id',
                            'en.section_id',
                            'en.academic_session_id',
                            'en.active_status',
                            'st.first_name',
                            'st.last_name',
                            'sad.student_id',
                            'c.name as class_name', 
                            's.name as section_name',
                            'sad.date',
                            'sad.status',
                            'sad.remarks',
                            'st.photo',
                            'sad.homeroom_teacher_remarks',
                            DB::raw("CONCAT(st." . ($name_status == 0 ? 'last_name_english' : 'first_name_english') . ", ' ',st." . ($name_status == 0 ? 'first_name_english' : 'last_name_english') . ") as name_english"),

                            // DB::raw("CONCAT(st.last_name_english, ' ', st.first_name_english) as name_english"),
                            DB::raw('COUNT(*) as "no_of_days_attendance"'),
                            DB::raw('COUNT(CASE WHEN sad.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                            DB::raw('COUNT(CASE WHEN sad.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                            DB::raw('COUNT(CASE WHEN sad.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                            DB::raw('COUNT(CASE WHEN sad.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                        )
                        ->join('students as st', 'en.student_id', '=', 'st.id')
                        ->join('classes as c', 'en.class_id', '=', 'c.id')
                        ->join('sections as s', 'en.section_id', '=', 's.id')
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
                        ->when($student_id, function ($query, $student_id) {
                            return $query->where('sad.student_id', $student_id);
                        })
                        ->whereMonth('sad.date', $year_month[0])
                        ->whereYear('sad.date', $year_month[1])
                        ->where('en.academic_session_id', '=', $attendance_academic_year)
                        // ->where('en.active_status', '=', "0")
                        ->groupBy('en.student_id')
                        ->get()->toArray();
            
    
                    $studentDetails = array();
                    if (!empty($getAttendanceList)) {
                        foreach ($getAttendanceList as $value) {
                            $object = new \stdClass();
    
                            $object->first_name = $value->first_name;
                            $object->last_name = $value->last_name;
                            $object->student_id = $value->student_id;
                            $object->presentCount = $value->presentCount;
                            $object->absentCount = $value->absentCount;
                            $object->lateCount = $value->lateCount;
                            $object->excusedCount = $value->excusedCount;
                            $student_id = $value->student_id;
                            $object->photo = $value->photo;
                            $date = $value->date;
                            $getStudentsAttData = $this->getAttendanceByDateStudentParent($request, $student_id, $date);
                            $object->attendance_details = $getStudentsAttData;
    
                            array_push($studentDetails, $object);
                        }
                    }
                } else {
                $getAttendanceList = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.academic_session_id',
                    'en.active_status',
                        'st.first_name',
                        'st.last_name',
                        'sad.student_id',
                        'c.name as class_name', 
                        's.name as section_name',
                        'sad.date',
                        'sad.status',
                        'sad.remarks',
                        'st.photo',
                        DB::raw("CONCAT(st." . ($name_status == 0 ? 'last_name_english' : 'first_name_english') . ", ' ',st." . ($name_status == 0 ? 'first_name_english' : 'last_name_english') . ") as name_english"),
                    // DB::raw("CONCAT(st.last_name_english, ' ', st.first_name_english) as name_english"),
                        'sad.homeroom_teacher_remarks',
                    DB::raw('COUNT(*) as "no_of_days_attendance"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                )
                ->join('students as st', 'en.student_id', '=', 'st.id')
                ->join('classes as c', 'en.class_id', '=', 'c.id')
                ->join('sections as s', 'en.section_id', '=', 's.id')
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
                ->when($session_id, function ($query, $session_id) {
                    return $query->where('en.session_id', $session_id);
                })
                ->whereMonth('sad.date', $year_month[0])
                ->whereYear('sad.date', $year_month[1])
                ->where('en.academic_session_id', '=', $attendance_academic_year)
                ->groupBy('en.student_id')
                ->get()->toArray();
    
                    $studentDetails = array();
                    if (!empty($getAttendanceList)) {
                        foreach ($getAttendanceList as $value) {
                            $object = new \stdClass();
    
                            $object->first_name = $value->first_name;
                            $object->last_name = $value->last_name;
                            $object->student_id = $value->student_id;
                            $object->presentCount = $value->presentCount;
                            $object->absentCount = $value->absentCount;
                            $object->lateCount = $value->lateCount;
                            $object->excusedCount = $value->excusedCount;
                            $object->photo = $value->photo;
                            $student_id = $value->student_id;
                            $date = $value->date;
                            $getStudentsAttData = $this->getAttendanceByDateStudent($request, $student_id, $date);
                            // dd($getStudentsAttData);
                            $object->attendance_details = $getStudentsAttData;
    
                            $object->remarks = $value->remarks;
                            $object->homeroom_teacher_remarks = $value->homeroom_teacher_remarks;
                            array_push($studentDetails, $object);
                        }
                    }
                }
                // date wise late present analysis
                $getLatePresentData = $Connection->table('student_attendances_day as sa')
                    ->select(

                        // 'sa.date',
                        DB::raw('DATE_FORMAT(sa.date, "%b %d") as date'),
                        DB::raw('COUNT(CASE WHEN sa.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                    )
                    ->join('enrolls as en', 'sa.student_id', '=', 'en.student_id')
                    ->join('students as stud', 'sa.student_id', '=', 'stud.id')
                    ->where([
                        ['sa.class_id', '=', $request->class_id],
                        ['sa.section_id', '=', $request->section_id]
                    ])
                    ->whereMonth('sa.date', $year_month[0])
                    ->whereYear('sa.date', $year_month[1])
                    ->groupBy('sa.date')
                    ->get();

                    
                $data = [
                    'student_details' => $studentDetails,
                    'late_present_graph' => $getLatePresentData
                ];
            }else if($request->pattern=="Day"){  
                $getAttendanceList = $Connection->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'en.class_id',
                        'en.section_id',
                        'en.academic_session_id',
                        'en.active_status',
                            'st.first_name',
                            'st.last_name',
                            'sad.student_id',
                            'c.name as class_name', 
                            's.name as section_name',
                            'sad.date',
                            'sad.status',
                            'sad.remarks',
                            'st.photo',
                        DB::raw("CONCAT(st." . ($name_status == 0 ? 'last_name_english' : 'first_name_english') . ", ' ',st." . ($name_status == 0 ? 'first_name_english' : 'last_name_english') . ") as name_english"),
                            // DB::raw("CONCAT(st.last_name_english, ' ', st.first_name_english) as name_english"),
                        DB::raw('COUNT(*) as "no_of_days_attendance"'),
                        DB::raw('COUNT(CASE WHEN sad.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                        DB::raw('COUNT(CASE WHEN sad.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                        DB::raw('COUNT(CASE WHEN sad.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                        DB::raw('COUNT(CASE WHEN sad.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                    )
                    ->join('students as st', 'en.student_id', '=', 'st.id')
                    ->leftJoin('classes as c', 'en.class_id', '=', 'c.id')
                    ->leftJoin('sections as s', 'en.section_id', '=', 's.id')
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
                    ->where('sad.date', $request->year_month)
                    ->where('en.academic_session_id', '=', $attendance_academic_year)
                    ->groupBy('en.student_id')
                    ->get()->toArray();
                // dd($getAttendanceList);
                
                $presentCount = 0;
                $absentCount = 0;
                $totalCount = 0;
                $count = [];
                $studentDetails = array();
                if (!empty($getAttendanceList)) {
                    foreach ($getAttendanceList as $value) {
                        $object = new \stdClass();

                        $object->first_name = $value->first_name;
                        $object->last_name = $value->last_name;
                        $object->name_english = $value->name_english;
                        $object->class_name = $value->class_name;
                        $object->section_name = $value->section_name;
                        $object->student_id = $value->student_id;
                        $object->status = $value->status;
                        $object->remarks = $value->remarks;
                        
                        $object->photo = $value->photo;
                        $student_id = $value->student_id;
                        $date = $value->date;
                        
                            if($value->presentCount!=0){
                                $presentCount++;
                            }
                            if($value->absentCount!=0){
                                $absentCount++;
                            }
                            $totalCount++;
                        array_push($studentDetails, $object);
                        
                    }
                }
                $count['totalCount'] = $totalCount;
                $count['presentCount'] = $presentCount;
                $count['absentCount'] = $absentCount;
                $data = [
                    'student_details' => $studentDetails,
                    'count' => $count,
                ];
            }else if($request->pattern=="Term"){
                
                $semester = $Connection->table('semester as s')
                        ->select('start_date', 'end_date','name')
                        ->where('id', $request->year_month)
                        ->first();
                $start = $semester->start_date;
                $end = $semester->end_date;

                // dd($start.$end);
                $start_date = Carbon::parse($semester->start_date);
                $end_date = Carbon::parse($semester->end_date);
                $school_days = $end_date->diffInWeekDays($start_date);

                $holiday = $Connection->table('holidays as h')
                ->where('date','>=',$start)
                ->where('date','<=',$end)
                ->whereNull('deleted_at')
                ->get();

                $holiday_count = $holiday->count();
                $weekend_holiday = 0;
                foreach($holiday as $holi){

                    if(Carbon::parse($holi->date)->isWeekend()){
                        $weekend_holiday++;
                    }
                }
                $total_holidays = $holiday_count - $weekend_holiday;
                $total_school_days = $school_days - $total_holidays;
                $getAttendanceList = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.academic_session_id',
                    'en.active_status',
                        'st.first_name',
                        'st.last_name',
                        'sad.student_id',
                        'c.name as class_name', 
                        's.name as section_name',
                        'sad.date',
                        'sad.status',
                        'sad.remarks',
                        'st.photo',
                        DB::raw("CONCAT(st." . ($name_status == 0 ? 'last_name_english' : 'first_name_english') . ", ' ',st." . ($name_status == 0 ? 'first_name_english' : 'last_name_english') . ") as name_english"),
                        // DB::raw("CONCAT(st.last_name_english, ' ', st.first_name_english) as name_english"),
                    DB::raw('COUNT(*) as "no_of_days_attendance"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                )
                ->join('students as st', 'en.student_id', '=', 'st.id')
                ->leftJoin('classes as c', 'en.class_id', '=', 'c.id')
                ->leftJoin('sections as s', 'en.section_id', '=', 's.id')
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
                ->whereBetween(DB::raw('date(date)'), [$start, $end])
                ->groupBy('en.student_id')
                ->get()->toArray();
            
                // dd($getAttendanceList);
                
                $presentCount = 0;
                $absentCount = 0;
                $totalCount = 0;
                $count = [];
                $studentDetails = array();
                if (!empty($getAttendanceList)) {
                    foreach ($getAttendanceList as $value) {
                        $object = new \stdClass();

                        $object->first_name = $value->first_name;
                        $object->last_name = $value->last_name;
                        $object->name_english = $value->name_english;
                        $object->class_name = $value->class_name;
                        $object->section_name = $value->section_name;
                        $object->student_id = $value->student_id;
                        $object->remarks = $value->remarks;
                        $object->semester_name = $semester->name;
                        $object->photo = $value->photo;
                        $student_id = $value->student_id;
                        
                        $object->presentCount = $value->presentCount;
                        $object->absentCount = $value->absentCount;
                        $object->lateCount = $value->lateCount;
                        array_push($studentDetails, $object);
                        
                    }
                }
                // dd(count($getAttendanceList));
                $count['total_students'] = count($getAttendanceList);
                $count['total_school_days'] = $total_school_days;
                $count['total_holidays'] = $total_holidays;
                $data = [
                    'student_details' => $studentDetails,
                    'count' => $count,
                ];
            }else if($request->pattern=="Year"){
                
                // $academic_year = $Connection->table('academic_year as ay')
                //         ->select('id','name')
                //         ->where('id', $request->year_month)
                //         ->first();
                        
                $yearData = $Connection->table('semester as sm')
                ->select(DB::raw('MIN(sm.start_date) AS year_start_date, MAX(sm.end_date) AS year_end_date'))
                ->where([
                    ['sm.academic_session_id', '=', $request->academic_session_id],
                ])
                ->get();
                        
                // $start_end = explode('-', $academic_year->name);
                // dd($yearData);
                
                $start = $yearData[0]->year_start_date;
                $end = $yearData[0]->year_end_date;

                $start_date = Carbon::parse($start);
                $end_date = Carbon::parse($end);
                $school_days = $end_date->diffInWeekDays($start_date);

                $holiday = $Connection->table('holidays as h')
                ->where('date','>=',$start)
                ->where('date','<=',$end)
                ->whereNull('deleted_at')
                ->get();

                $holiday_count = $holiday->count();
                $weekend_holiday = 0;
                foreach($holiday as $holi){

                    if(Carbon::parse($holi->date)->isWeekend()){
                        $weekend_holiday++;
                    }
                }
                $total_holidays = $holiday_count - $weekend_holiday;
                $total_school_days = $school_days - $total_holidays;
                $getAttendanceList = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.academic_session_id',
                    'en.active_status',
                        'st.first_name',
                        'st.last_name',
                        'sad.student_id',
                        'c.name as class_name', 
                        's.name as section_name',
                        'sad.date',
                        'sad.status',
                        'sad.remarks',
                        'st.photo',
                        DB::raw("CONCAT(st." . ($name_status == 0 ? 'last_name_english' : 'first_name_english') . ", ' ',st." . ($name_status == 0 ? 'first_name_english' : 'last_name_english') . ") as name_english"),
                        // DB::raw("CONCAT(st.last_name_english, ' ', st.first_name_english) as name_english"),
                    DB::raw('COUNT(*) as "no_of_days_attendance"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                    DB::raw('COUNT(CASE WHEN sad.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                )
                ->join('students as st', 'en.student_id', '=', 'st.id')
                ->leftJoin('classes as c', 'en.class_id', '=', 'c.id')
                ->leftJoin('sections as s', 'en.section_id', '=', 's.id')
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
                ->whereBetween(DB::raw('date(date)'), [$start, $end])
                ->groupBy('en.student_id')
                ->get()->toArray();
                
                $presentCount = 0;
                $absentCount = 0;
                $totalCount = 0;
                $count = [];
                $studentDetails = array();
                if (!empty($getAttendanceList)) {
                    foreach ($getAttendanceList as $value) {
                        $object = new \stdClass();

                        $object->first_name = $value->first_name;
                        $object->last_name = $value->last_name;
                        $object->name_english = $value->name_english;
                        $object->class_name = $value->class_name;
                        $object->section_name = $value->section_name;
                        $object->student_id = $value->student_id;
                        $object->remarks = $value->remarks;
                        $object->photo = $value->photo;
                        $student_id = $value->student_id;
                        $object->presentCount = $value->presentCount;
                        $object->absentCount = $value->absentCount;
                        $object->lateCount = $value->lateCount;
                        array_push($studentDetails, $object);
                        
                    }
                }
                $count['total_students'] = count($getAttendanceList);
                $count['total_school_days'] = $total_school_days;
                $count['total_holidays'] = $total_holidays;
                $data = [
                    'student_details' => $studentDetails,
                    'count' => $count,
                ];
            }
            return $this->successResponse($data, 'attendance record fetch successfully');
        }
    }
    
    // getTimetableCalendor
    public function getTimetableCalendorStud(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'student_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            if($request->student_id!==null)
            {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            $studentID = $request->student_id;
            $name_status = $request->name_status;
            $start = date('Y-m-d h:i:s', strtotime($request->start));
            $end = date('Y-m-d h:i:s', strtotime($request->end));
            $student = $Connection->table('enrolls')->where('student_id', $request->student_id)->where('active_status', '0')->first();
            $success = $Connection->table('students as stud')
                ->select(
                    'cl.id',
                    'cl.class_id',
                    'cl.time_table_id',
                    'cl.section_id',
                    'cl.subject_id',
                    'cl.start',
                    'cl.end',
                    'en.semester_id',
                    'en.session_id',
                    's.name as section_name',
                    'c.name as class_name',
                    'sb.subject_color_calendor as color',
                    'sb.name as subject_name',
                    'sb.name as title',
                    DB::raw("CONCAT(st." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ',st." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as teacher_name"),
                    // DB::raw("CONCAT(st.first_name, ' ', st.last_name) as teacher_name"),
                    'drr.student_remarks',
                    'ev.id as event_holiday_id'
                )
                ->join('enrolls as en', 'en.student_id', '=', 'stud.id')
                ->join('classes as c', 'en.class_id', '=', 'c.id')
                ->join('sections as s', 'en.section_id', '=', 's.id')
                ->leftJoin('subject_assigns as sa', function ($join) {
                    $join->on('sa.class_id', '=', 'en.class_id')
                        ->on('sa.section_id', '=', 'en.section_id');
                })
                ->join('calendors as cl', function ($join) {
                    $join->on('cl.class_id', '=', 'sa.class_id')
                        ->on('cl.section_id', '=', 'sa.section_id')
                        ->on('cl.subject_id', '=', 'sa.subject_id');
                })
                ->join('subjects as sb', 'sa.subject_id', '=', 'sb.id')
                ->join('staffs as st', 'sa.teacher_id', '=', 'st.id')
                ->leftJoin('daily_report_remarks as drr', function ($join) use ($studentID) {
                    $join->on('cl.class_id', '=', 'drr.class_id')
                        ->on('cl.section_id', '=', 'drr.section_id')
                        ->on('cl.subject_id', '=', 'drr.subject_id')
                        ->on(DB::raw('DATE(cl.start)'), '=', 'drr.date')
                        ->on('drr.student_id', '=', DB::raw("'$studentID'"));
                })
                ->leftJoin('events as ev', function ($join) {
                    $join->where([
                        [DB::raw('date(ev.start_date)'), '<=', DB::raw('date(cl.end)')],
                        [DB::raw('date(ev.end_date)'), '>=', DB::raw('date(cl.end)')],
                        ['ev.holiday', '=', '0'],
                        ['ev.student_holiday', '=', '1']
                    ]);
                })
                ->where('stud.id', $request->student_id)
                ->whereRaw('cl.start between "' . $start . '" and "' . $end . '"')
                ->whereRaw('cl.end between "' . $start . '" and "' . $end . '"')

                // ->where('cl.sem_id', $student->semester_id)
                // ->where('cl.session_id', $student->session_id)
                ->where('cl.academic_session_id', $student->academic_session_id)
                ->whereNull('ev.id')
                // ->groupBy('cl.subject_id')
                ->get();
            return $this->successResponse($success, 'student calendor data get successfully');
            }
        }
    }
    
    // get Teacher list 
    public function getTeacherList(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'academic_session_id' => 'required',
            'branch_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            $name_status = $request->name_status;
            // insert data
            $success = $createConnection->table('subject_assigns as sa')->select('s.id', 
            DB::raw("CONCAT(" . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' '," . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
            // DB::raw("CONCAT(last_name, ' ', first_name) as name")
            )
                ->join('staffs as s', 'sa.teacher_id', '=', 's.id')
                // ->where('sa.class_id', $request->class_id)
                ->where('sa.academic_session_id', '=', $request->academic_session_id)
                ->groupBy('sa.teacher_id')
                ->get();
            return $this->successResponse($success, 'Teachers record fetch successfully');
        }
    }

//getStudentListNew
     public function getStudentList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
        ]);
 
        $department_id = isset($request->department_id) ? $request->department_id : null;
        $class_id = isset($request->class_id) ? $request->class_id : null;
        $session_id = isset($request->session_id) ? $request->session_id : 0;
        $section_id = isset($request->section_id) ? $request->section_id : null;
        $status = isset($request->status) ? $request->status : null;
        $name = isset($request->student_name) ? $request->student_name : null;
 
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        }
         else {
            // get data
            $cache_time = config('constants.cache_time');
            $cache_students = config('constants.cache_students');
 
            $cacheKey = $cache_students . $request->branch_id;
            // Check if the data is cached
            if (Cache::has($cacheKey) && !($department_id || $class_id || $session_id || $section_id || $status)) {
                // If cached and no filters are applied, return cached data
                \Log::info('cacheKey ' . json_encode($cacheKey));
                $students = Cache::get($cacheKey);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);
             
                $name_status = $request->name_status;
            
                $query = $con->table('enrolls as e')
                ->select(
                    's.id',
                    DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                    DB::raw('CONCAT(s.' . ($name_status == 0 ? 'last_name_common' : 'first_name_common') . ', " ", s.' . ($name_status == 0 ? 'first_name_common' : 'last_name_common') . ") as name_common"),
                   
                    // DB::raw('CONCAT(s.last_name, " ", s.first_name) as name'),
                    // DB::raw('CONCAT(s.last_name_common, " ", s.first_name_common) as name_common'),
                    's.register_no',
                    's.roll_no',
                    's.mobile_no',
                    's.email',
                    's.gender',
                    's.photo',
                    'e.attendance_no'
                )
                ->join('students as s', 'e.student_id', '=', 's.id');
           
                if (isset($request->department_id) && filled($request->department_id)) {
                    $query->where('e.department_id', $request->department_id);
                }
 
                if (isset($request->class_id) && filled($request->class_id)) {
                    $query->where('e.class_id', $request->class_id);
                }
 
                // if (isset($request->session_id) && filled($request->session_id)) {
                //     $query->where('e.session_id', $request->session_id);
                // }
 
                if (isset($request->section_id) && filled($request->section_id)) {
                    $query->where('e.section_id', $request->section_id);
                }
 
                if (isset($request->status) && filled($request->status)) {
                    $query->where('e.active_status', $request->status);
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
                if (!($department_id || $class_id || $session_id || $section_id || $status)) {
                    Cache::put($cacheKey, $students, now()->addHours($cache_time)); // Cache for 24 hours
                }
            }
            return $this->successResponse($students, 'Student record fetch successfully');
        }
    }
   
    // get StudentDetails details
    public function getStudentDetails(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'id' => 'required',
            'branch_id' => 'required'
        ]);
        // return $request;

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            if($request->id!==0)
            {
                $id = $request->id;
                // create new connection            
                $conn = $this->createNewConnection($request->branch_id);
                $name_status = $request->name_status;
                // get data
                // $studentDetail['student'] = $conn->table('students as s')
                //     ->select('s.*', DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"), 'c.name as class_name', 'sec.name as section_name', 's.relation', 'e.class_id', 'e.section_id', 'e.session_id', 'e.semester_id')
                //     ->leftJoin('enrolls as e', 's.id', '=', 'e.student_id')
                //     ->leftJoin('classes as c', 'e.class_id', '=', 'c.id')
                //     ->leftJoin('sections as sec', 'e.section_id', '=', 'sec.id')
                //     ->where('s.id', $id)->first();

                $getStudentDetail = $conn->table('students as s')
                    ->select(
                        's.*',
                        // DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"),
                        DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                        'e.academic_session_id as year',
                        'c.name as class_name',
                        'sec.name as section_name',
                        'e.department_id',
                        'e.class_id',
                        'e.section_id',
                        'e.session_id',
                        'e.semester_id',
                        'e.attendance_no',
                        'ter.date_of_termination' // Include termination date
                    )
                    ->leftJoin('enrolls as e', 's.id', '=', 'e.student_id')
                    ->leftJoin('classes as c', 'e.class_id', '=', 'c.id')
                    ->leftJoin('sections as sec', 'e.section_id', '=', 'sec.id')
                    ->leftJoin('termination as ter', function($join) {
                        $join->on('ter.student_id', '=', 's.id');
                            //  ->whereNull('ter.date_of_termination'); // Add condition for termination date being null
                    })
                    ->where('s.id', $id)
                    ->get();
                $studentObj = new \stdClass();
                if (!empty($getStudentDetail)) {
                    foreach ($getStudentDetail as $suc) {
                        $studentObj = $suc;
                        $studentObj->current_address = Helper::decryptStringData($suc->current_address);
                        $studentObj->permanent_address = Helper::decryptStringData($suc->permanent_address);
                        $studentObj->mobile_no = Helper::decryptStringData($suc->mobile_no);
                        $studentObj->nric = Helper::decryptStringData($suc->nric);
                        $studentObj->passport = Helper::decryptStringData($suc->passport);
                    }
                }
                $studentDetail['student'] = $studentObj;
                $class_id = $studentDetail['student']->class_id;
                $studentDetail['section'] = $conn->table('section_allocations as sa')->select('s.id as section_id', 's.name as section_name')
                    ->join('sections as s', 'sa.section_id', '=', 's.id')
                    ->where('sa.class_id', $class_id)
                    ->get();

                $route_id = $studentDetail['student']->route_id;
                $studentDetail['vehicle'] = $conn->table('transport_assign')->select('transport_vehicle.id as vehicle_id', 'transport_vehicle.vehicle_no')
                    ->join('transport_vehicle', 'transport_assign.vehicle_id', '=', 'transport_vehicle.id')
                    ->where('transport_assign.route_id', $route_id)
                    ->get();

                $hostel_id = $studentDetail['student']->hostel_id;
                $studentDetail['room'] = $conn->table('hostel_room')->select('hostel_room.id as room_id', 'hostel_room.name as room_name')
                    ->where('hostel_room.hostel_id', $hostel_id)
                    ->get();
                $staffRoles = array('6');
                $sql = "";
                for ($x = 0; $x < count($staffRoles); $x++) {
                    $getRow = User::select('google2fa_secret_enable', 'id')->where('user_id', $id)
                        ->where('branch_id', $request->branch_id)
                        ->whereRaw("find_in_set('$staffRoles[$x]',role_id)")
                        ->first();
                    if (isset($getRow->id)) {
                        $sql = $getRow;
                        break;
                    }
                }
                $studentDetail['user'] = $sql;
                return $this->successResponse($studentDetail, 'Student record fetch successfully');
            }
            else
            {
                return $this->commonHelper->generalReturn('403', 'error', 'Invalid Student Id ');
            }
        }
    }

   
    private function fetchParentDetails($branch_id, $status = null, $academic_session_id) {
        $conn = $this->createNewConnection($branch_id);
    
        if ($status == "1") {
            $inactive1 = $conn->table('parent as pt')
                        ->select("pt.id", 'pt.email', 'pt.occupation', DB::raw("CONCAT(pt.last_name, ' ', pt.first_name) as name"))
                        // ->join('students as st', 'pt.id', '=', 'st.guardian_id')
                        ->join('students as st', 'pt.id', '=', 'st.father_id')
                        // ->leftjoin('students as st', function ($join) {
                        //     $join->on('st.father_id', '=', 'pt.id');
                        //     $join->orOn('st.mother_id', '=', 'pt.id');
                        //     $join->orOn('st.guardian_id', '=', 'pt.id');
                        // })
                        ->leftJoin('enrolls as e', 'st.id', '=', 'e.student_id')
                        ->where('e.active_status', '!=' , "0")
                        ->where('pt.status', '=', '0')
                        ->groupBy('st.father_id')
                        ->get()->toArray();


            $inactive2 = $conn->table('parent as pt')
                        ->select("pt.id", 'pt.email', 'pt.occupation', DB::raw("CONCAT(pt.last_name, ' ', pt.first_name) as name"))
                        // ->leftJoin('students as st', 'pt.id', '=', 'st.guardian_id')
                        ->leftJoin('students as st', 'pt.id', '=', 'st.father_id')
                        // ->leftjoin('students as st', function ($join) {
                        //     $join->on('st.father_id', '=', 'pt.id');
                        //     $join->orOn('st.mother_id', '=', 'pt.id');
                        //     $join->orOn('st.guardian_id', '=', 'pt.id');
                        // })
                        ->leftJoin('enrolls as e', 'st.id', '=', 'e.student_id')
                        ->whereNull('st.guardian_id')
                        ->where('pt.status', '=', '0')
                        ->groupBy('st.father_id')
                        ->get()->toArray();

            
            $parentDetails = array_merge($inactive2, $inactive1);
        } else {
            $parentDetails = $conn->table('parent as pt')
                ->select("pt.id", 'pt.email', 'pt.occupation', DB::raw("CONCAT(pt.last_name, ' ', pt.first_name) as name"))
                // ->join('students as st', 'pt.id', '=', 'st.guardian_id')
                ->join('students as st', 'pt.id', '=', 'st.father_id')
                // ->leftjoin('students as st', function ($join) {
                //     $join->on('st.father_id', '=', 'pt.id');
                //     $join->orOn('st.mother_id', '=', 'pt.id');
                //     $join->orOn('st.guardian_id', '=', 'pt.id');
                // })
                ->leftJoin('enrolls as e', 'st.id', '=', 'e.student_id')
                ->where('e.academic_session_id', '=' , $academic_session_id)
                ->where('e.active_status', '=' , "0")
                ->where('pt.status', '=', '0')
                ->groupBy('st.father_id')
                ->get();
        }
    
        return $parentDetails;
    }
    // getParentStudentUpdateList
    public function getParentStudentUpdateInfoList(Request $request)
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
            $parent_id = $request->parent_id;
            $student_id = $request->student_id;
            // $parentDetails = $conn->table('parent_change_info as pi')->select("p.email","pi.status", "pi.status_parent", "p.id as parent_id", "pi.id", "p.occupation", DB::raw("CONCAT(p.last_name, ' ', p.first_name) as name"))
            //     ->leftJoin('parent as p', 'pi.parent_id', '=', 'p.id')->where('pi.parent_id', $parent_id)->get()->toArray();
            $name_status = $request->name_status;
            $parentDetails = $conn->table('parent_change_info as pi')
                            ->select("p.email", "pi.status", "pi.status_parent", "p.id as parent_id", "pi.id", "p.occupation",
                            DB::raw("CONCAT(p." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', p." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                            // DB::raw("CONCAT(p.last_name, ' ', p.first_name) as name")
                             )
                            ->leftJoin('students as s', function($join) {
                                $join->on('pi.parent_id', '=', 's.guardian_id')
                                    ->orOn('pi.parent_id', '=', 's.father_id')
                                    ->orOn('pi.parent_id', '=', 's.mother_id');
                            })
                            ->leftJoin('parent as p', 'pi.parent_id', '=', 'p.id')
                            ->where('s.guardian_id', $parent_id)
                            ->get()
                            ->toArray();

            $studentDetails = $conn->table('student_change_info as si')->select("s.email","si.status", "si.status_parent", "s.id as student_id", 'si.id', "s.roll_no", DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"))
                ->leftJoin('students as s', 'si.student_id', '=', 's.id')->where('si.parent_id', $parent_id)->get()->toArray();
            $details = array_merge($parentDetails, $studentDetails);
            return $this->successResponse($details, 'Parent record fetch successfully');
        }
    }
    // getParentUpdateList
    public function getParentUpdateInfoList(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $name_status = $request->name_status;
            // get data
            $parentDetails = $conn->table('parent_change_info as pi')
                ->select("p.email", "pi.status_parent", "p.id as parent_id", "pi.id", "p.occupation",
                
                DB::raw("CONCAT(p." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', p." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                // DB::raw("CONCAT(p.last_name, ' ', p.first_name) as name")                
                )
                ->leftJoin('parent as p', 'pi.parent_id', '=', 'p.id')->where('pi.status', $request->status)->get();
            return $this->successResponse($parentDetails, 'Parent record fetch successfully');
        }
    }

  

    // getStudentUpdateListNew
     public function getStudentUpdateInfoList(Request $request)
     {
       
         $validator = \Validator::make($request->all(), [
             'branch_id' => 'required',
             'token' => 'required',
         ]);
        
         if (!$validator->passes()) {
             return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
         } else {
             // create new connection
             $conn = $this->createNewConnection($request->branch_id);
        
             $name_status = $request->name_status; 
             // get data
             $studentDetails = $conn->table('student_change_info as si')
                                     ->select("s.email",
                                     "s.id as student_id", 'si.id', "s.roll_no", 
 
                                     DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                                    //  DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name")
                                     )
                                     ->leftJoin('students as s', 'si.student_id', '=', 's.id')->where('si.status', $request->status)->get();
             return $this->successResponse($studentDetails, 'Student record fetch successfully');
         }
     }
    // get Parent row details
    public function getParentDetails(Request $request)
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
            $academic_session_id = $request->academic_session_id;
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            // $parentDetails['parent'] = $conn->table('parent')->select('*', DB::raw("CONCAT(last_name, ' ', first_name) as name"))->where('id', $id)->first();

            $name_status = $request->name_status;
            $getparentDetails = $conn->table('parent as s')
                ->select(
                    's.*',
                    DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                    // DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name")
                )
                ->where('s.id', $id)
                ->get();
                // dd($getparentDetails);
            $parentObj = new \stdClass();
            if (!empty($getparentDetails)) {
                foreach ($getparentDetails as $suc) {
                    $parentObj = $suc;
                    $parentObj->company_phone_number = Helper::decryptStringData($suc->company_phone_number);
                    $parentObj->mobile_no = Helper::decryptStringData($suc->mobile_no);
                    $parentObj->japan_contact_no = Helper::decryptStringData($suc->japan_contact_no);
                    $parentObj->japan_emergency_sms = Helper::decryptStringData($suc->japan_emergency_sms);
                }
            }
            $parentDetails['parent'] = $parentObj;

            $parentDetails['childs'] = $conn->table('students as s')->select('s.id', 's.first_name', 's.last_name', 's.photo', 'c.name as class_name', 'sec.name as section_name')
                ->leftJoin('enrolls as e', 'e.student_id', '=', 's.id')
                ->leftJoin('classes as c', 'e.class_id', '=', 'c.id')
                ->leftJoin('sections as sec', 'e.section_id', '=', 'sec.id')
                ->where('e.active_status', '=', "0")
                ->where('e.academic_session_id',$academic_session_id)
                ->where('s.father_id', $id)
                ->orWhere('s.mother_id', $id)
                ->orWhere('s.guardian_id', $id)
                ->groupBy('e.student_id')->get();
           /* $staffRoles = array('5');
            $sql = "";
            for ($x = 0; $x < count($staffRoles); $x++) {
                $getRow = User::select('google2fa_secret_enable', 'id')->where('user_id', $id)
                    ->where('branch_id', $request->branch_id)
                    ->whereRaw("find_in_set('$staffRoles[$x]',role_id)")
                    ->first();
                if (isset($getRow->id)) {
                    $sql = $getRow;
                    break;
                }
            }*/
            $parent = User::where([['user_id', '=', $id], ['role_id', '=', "5"], ['branch_id', '=', $request->branch_id]])->first();
            $parentDetails['user'] = $parent;
            return $this->successResponse($parentDetails, 'Parent row fetch successfully');
        }
    }
    
    // get Parent Name
    public function getParentName(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $name_status = $request->name_status;
            // get data
            $data = $conn->table('parent')
                ->select("id",
                DB::raw("CONCAT(" . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', " . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                //  DB::raw("CONCAT(last_name, ' ', first_name) as name"), 
                 'email')
                ->where("first_name", "LIKE", "%{$request->name}%")
                ->orWhere("last_name", "LIKE", "%{$request->name}%")
                ->where("status",'=','0')
                ->get();

            $output = '';
            if ($request->name) {
                if (!$data->isEmpty()) {
                    $output = '<ul class="list-group" style="display: block; position: relative; z-index: 1">';
                    foreach ($data as $row) {

                        $output .= '<li class="list-group-item" value="' . $row->id . '">' . $row->name . ' ( ' . $row->email . ' ) </li>';
                    }
                    $output .= '</ul>';
                } else {
                    $output .= '<li class="list-group-item">' . 'No results Found' . '</li>';
                }
            } else {
                $output .= '<li class="list-group-item">' . 'No results Found' . '</li>';
            }
            return $output;
        }
    }
   
    // get all teacher list
    public function getAllTeacherList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $main_db = config('constants.main_db');
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $branchID = $request->branch_id;
            $name_status = $request->name_status;

            // get all teachers
            $allTeachers = $conn->table('staffs as stf')
                ->select(
                    'us.id as uuid',
                    'us.role_id',
                    'us.branch_id',
                    'stf.id',              
                    DB::raw("CONCAT(stf." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', stf." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                    // DB::raw("CONCAT(stf.last_name, ' ', stf.first_name) as name"),
                    'us.role_id',
                    'us.user_id',
                    'us.email',
                    'rol.role_name'
                )
                // ->join('' . $main_db . '.users as us', 'stf.id', '=', 'us.user_id')
                // ->join('' . $main_db . '.roles as rol', 'rol.id', '=', 'us.role_id')
                // // ->join('paxsuzen_pz-school.users as us', 'stf.id', '=', 'us.user_id')
                // // ->join('paxsuzen_pz-school.roles as rol', 'rol.id', '=', 'us.role_id')
                // ->where([
                //     ['us.branch_id', '=', $request->branch_id],
                //     ['stf.is_active', '=', '0']
                // ])
                // ->whereRaw('FIND_IN_SET(?,us.role_id)', ['4'])
                ->join('' . $main_db . '.users as us', function ($join) use ($branchID) {
                    $join->on('stf.id', '=', 'us.user_id')
                        // ->on('us.branch_id', '=', DB::raw("'$branchID'"));
                        ->where('us.branch_id', $branchID);
                })
                ->join('' . $main_db . '.roles as rol', 'rol.id', '=', 'us.role_id')
                ->where(function ($query) use ($branchID) {
                    // foreach ($search_terms as $item) {
                    $query->whereRaw('FIND_IN_SET(?,us.role_id)', ['4'])
                        ->orWhereRaw('FIND_IN_SET(?,us.role_id)', ['3']);
                    // }
                })
                ->where('stf.is_active', '=', '0')
                ->groupBy('stf.id')
                ->get();
            // $allTeachers = User::select('name', 'user_id')->where([['role_id', '=', "4"], ['branch_id', '=', $request->branch_id]])->get();
            return $this->successResponse($allTeachers, 'get all record fetch successfully');
        }
    }
    
    // studnet leave start 
    // parent dashboard : parent id wise get student
    public function get_studentsparentdashboard(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'parent_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $parent_id = $request->parent_id;
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $name_status = $request->name_status;

            // get data
            // $studentDetails = $conn->table('students as std')
            //     ->select('std.id', 'class_id', 'section_id', 'parent_id', 'first_name', 'last_name', 'gender')
            //     ->leftJoin('enrolls as en', 'std.id', '=', 'en.student_id')
            //     ->where('parent_id', $parent_id)
            //     ->get();
            $studentDetails = $conn->table('students as std')
                ->select(
                    'std.id',
                    'en.class_id',
                    'en.section_id',
                    // DB::raw("CONCAT(last_name, ' ', first_name) as name"),
                    DB::raw("CONCAT(" . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', " . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                    'std.gender'
                )
                ->join('enrolls as en', 'std.id', '=', 'en.student_id')
                ->where('std.father_id', '=', $parent_id)
                ->orWhere('std.mother_id', '=', $parent_id)
                ->orWhere('std.guardian_id', '=', $parent_id)
                ->groupBy("std.id")
                ->get();
            return $this->successResponse($studentDetails, 'Student details fetch successfully');
        }
    }
    // student leave apply insert 
    public function student_leaveapply(Request $request)
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
        ]);

        // return $request;

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $staffConn = $this->createNewConnection($request->branch_id);
            $from_leave = date('Y-m-d', strtotime($request['frm_leavedate']));
            $to_leave = date('Y-m-d', strtotime($request['to_leavedate']));
            $name_status = $request->name_status;

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
                    'parent_id' => $request['parent_id'],
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
                    'home_teacher_status' => $request['status'],
                    'nursing_teacher_status' => $request['status'],
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $query = $staffConn->table('student_leaves')->insert($data);
                // send notifications to assign staff
                // $getAssignStaff = $staffConn->table('subject_assigns')
                //     ->select('teacher_id')
                //     ->where([
                //         ['class_id', '=', $request->class_id],
                //         ['type', '=', '0'],
                //         ['teacher_id', '!=', '0'],
                //         ['section_id', '=', $request->section_id]
                //     ])->groupBy("teacher_id")->get();
                // homeroom teacher or teacher allocated
                $getAssignStaff = $staffConn->table('teacher_allocations')
                    ->select('teacher_id')
                    ->where([
                        ['class_id', '=', $request->class_id],
                        ['type', '=', '0'],
                        ['teacher_id', '!=', '0'],
                        ['section_id', '=', $request->section_id]
                    ])->groupBy("teacher_id")->get()->toArray();
                // nursing teacher list
                $nursingStaff = $staffConn->table('staffs')
                    ->select('id as teacher_id')
                    ->where([
                        ['teacher_type', '=', 'nursing_teacher']
                    ])->groupBy("id")->get()->toArray();
                // Merge the arrays
                $combinedArray = array_merge($getAssignStaff, $nursingStaff);
                // Remove duplicate values
                $uniqueValuesArray = array_unique($combinedArray, SORT_REGULAR);
                $assignerID = [];
                if (isset($uniqueValuesArray)) {
                    foreach ($uniqueValuesArray as $key => $value) {
                        array_push($assignerID, $value->teacher_id);
                    }
                }
                // dd($assignerID);
                // send leave notifications
                $user = User::whereIn('user_id', $assignerID)->where([
                    ['branch_id', '=', $request->branch_id]
                ])->where(function ($q) {
                    $q->where('role_id', 3)
                        ->orWhere('role_id', 2)
                        ->orWhere('role_id', 4);
                })->get();
                // dd($user);
                // get staff name
                $student_name = $staffConn->table('students')
                    ->select(
                    DB::raw("CONCAT(students." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', students." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                        // DB::raw('CONCAT(students.last_name, " ", students.first_name) as name')
                    )
                    ->where([
                        ['id', '=', $request->student_id]
                    ])->first();
                // notifications sent
                Notification::send($user, new StudentLeaveApply($data, $request->branch_id, $student_name->name));

                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Waiting for approval');
                }
            }
        }
    }
   

    // student leave apply update 
    public function student_leaveupdate(Request $request)
    {        
        
        try{
            $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'id' => 'required',
            'student_id' => 'required',
            'class_id' => 'required',
            'section_id' => 'required',
            'frm_leavedate' => 'required',
            'to_leavedate' => 'required',
            'reason_id' => 'required',
            'total_leave' => 'required',
            'change_lev_type' => 'required',
        ]);
       
        // return $validator->passes();

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            
            // create new connection
            $staffConn = $this->createNewConnection($request->branch_id);
            $from_leave = date('Y-m-d', strtotime($request['frm_leavedate']));
            $to_leave = date('Y-m-d', strtotime($request['to_leavedate']));
            $name_status = $request->name_status;

            // check leave exist
            $fromLeaveCnt = $staffConn->table('student_leaves as lev')
                ->where([
                    ['lev.student_id', '=', $request->student_id],
                    ['lev.class_id', '=', $request->class_id],
                    ['lev.section_id', '=', $request->section_id],
                    ['lev.from_leave', '<=', $from_leave],
                    ['lev.to_leave', '>=', $from_leave],
                ])->whereNotIn('lev.id', [$request->id]) // Exclude the leave record with the given ID
                ->count();
            $toLeaveCnt = $staffConn->table('student_leaves as lev')
                ->where([
                    ['lev.student_id', '=', $request->student_id],
                    ['lev.class_id', '=', $request->class_id],
                    ['lev.section_id', '=', $request->section_id],
                    ['lev.from_leave', '<=', $to_leave],
                    ['lev.to_leave', '>=', $to_leave]
                ])->whereNotIn('lev.id', [$request->id]) // Exclude the leave record with the given ID
                ->count();
            if ($fromLeaveCnt > 0 || $toLeaveCnt > 0) {
                return $this->send422Error('You have already applied for leave between these dates', ['error' => 'You have already applied for leave between these dates']);
            } else {
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
                    'parent_id' => $request['parent_id'],
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
                    'home_teacher_status' => $request['status'],
                    'nursing_teacher_status' => $request['status'],
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $query = $staffConn->table('student_leaves')->where('id', $request['id'])->update($data);
               
                // homeroom teacher or teacher allocated
                $getAssignStaff = $staffConn->table('teacher_allocations')
                    ->select('teacher_id')
                    ->where([
                        ['class_id', '=', $request->class_id],
                        ['type', '=', '0'],
                        ['teacher_id', '!=', '0'],
                        ['section_id', '=', $request->section_id]
                    ])->groupBy("teacher_id")->get()->toArray();
                // nursing teacher list
                $nursingStaff = $staffConn->table('staffs')
                    ->select('id as teacher_id')
                    ->where([
                        ['teacher_type', '=', 'nursing_teacher']
                    ])->groupBy("id")->get()->toArray();
                // Merge the arrays
                $combinedArray = array_merge($getAssignStaff, $nursingStaff);
                // Remove duplicate values
                $uniqueValuesArray = array_unique($combinedArray, SORT_REGULAR);
                $assignerID = [];
                if (isset($uniqueValuesArray)) {
                    foreach ($uniqueValuesArray as $key => $value) {
                        array_push($assignerID, $value->teacher_id);
                    }
                }
                // dd($assignerID);
                // send leave notifications
                $user = User::whereIn('user_id', $assignerID)->where([
                    ['branch_id', '=', $request->branch_id]
                ])->where(function ($q) {
                    $q->where('role_id', 3)
                        ->orWhere('role_id', 2)
                        ->orWhere('role_id', 4);
                })->get();
                // dd($user);
                // get staff name
                $student_name = $staffConn->table('students')
                    ->select(
                    DB::raw("CONCAT(students." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', students." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                        // DB::raw('CONCAT(students.last_name, " ", students.first_name) as name')
                    )
                    ->where([
                        ['id', '=', $request->student_id]
                    ])->first();
                // notifications sent
                // Notification::send($user, new StudentLeaveApply($data, $request->branch_id, $student_name->name));

                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Waiting for approval');
                }
            }
        }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in student_leaveapply');
        }
    }



    //Class room management : get student leaves
    function get_studentleaves(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'class_id' => 'required',
            'section_id' => 'required',
            'classDate' => 'required'

        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $name_status = $request->name_status;
            
            // get data
            $compare_date = $request->classDate;
            $studentDetails = $conn->table('student_leaves as lev')
                ->select(
                    'lev.id',
                    'lev.class_id',
                    'lev.section_id',
                    'lev.student_id',
                    DB::raw("CONCAT(std." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', std." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                    // DB::raw('CONCAT(std.last_name, " ", std.first_name) as name'),
                    DB::raw('DATE_FORMAT(lev.from_leave, "%d-%m-%Y") as from_leave'),
                    DB::raw('DATE_FORMAT(lev.to_leave, "%d-%m-%Y") as to_leave'),
                    DB::raw('DATE_FORMAT(lev.created_at, "%d-%m-%Y") as created_at'),
                    'lev.reason',
                    'lev.document',
                    'lev.status',
                    'lev.remarks',
                    'lev.teacher_remarks',
                    'std.photo'
                )
                ->leftJoin('students as std', 'lev.student_id', '=', 'std.id')
                ->where([
                    ['lev.class_id', '=', $request->class_id],
                    ['lev.section_id', '=', $request->section_id],
                    // ['lev.status', '!=', 'Approve'],
                    // ['lev.status', '!=', 'Reject'],
                    // ['lev.status', '!=', 'Reject'],
                    ['lev.from_leave', '<=', $compare_date],
                    ['lev.to_leave', '>=', $compare_date]
                ])
                ->orderBy('lev.from_leave', 'asc')
                ->get();
            return $this->successResponse($studentDetails, 'Student details fetch successfully');
        }
    }
    
    //get particular student leave 
    function get_particular_studentleave_list(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'parent_id' => 'required'

        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            $name_status = $request->name_status;

            $studentDetails = $conn->table('student_leaves as lev')
                ->select(
                    'lev.id',
                    'lev.class_id',
                    'lev.section_id',
                    'lev.student_id',
                    DB::raw("CONCAT(std." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', std." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                    // DB::raw("CONCAT(std.last_name, ' ', std.first_name) as name"),
                    DB::raw('DATE_FORMAT(lev.from_leave, "%d-%m-%Y") as from_leave'),
                    DB::raw('DATE_FORMAT(lev.to_leave, "%d-%m-%Y") as to_leave'),
                    DB::raw('DATE_FORMAT(lev.created_at, "%d-%m-%Y") as created_at'),
                    'as.name as reason',
                    'slt.name as leave_type_name',
                    'lev.document',
                    'lev.status',
                    'lev.remarks',
                    'lev.teacher_remarks',
                    'lev.nursing_teacher_remarks'
                )
                //->select('lev.class_id','lev.section_id','student_id','std.first_name','std.last_name','lev.from_leave','lev.to_leave','lev.reason','lev.status')
                ->leftJoin('students as std', 'lev.student_id', '=', 'std.id')
                ->leftJoin('student_leave_types as slt', 'lev.change_lev_type', '=', 'slt.id')
                ->leftJoin('absent_reasons as as', 'lev.reasonId', '=', 'as.id')
                ->where([
                    ['lev.parent_id', '=', $request->parent_id]
                ])
                ->orderby('lev.to_leave', 'desc')
                ->get();
            return $this->successResponse($studentDetails, 'Student details fetch successfully');
        }
    }
    
    public function absentReasonsendNotificationAndEmail($branch_id, $student_leave_tbl_id, $status)
    {
        // echo $branch_id;
        // echo $student_leave_tbl_id;
        // echo $status;
        // exit;
        if ($branch_id && $student_leave_tbl_id && $status) {
            $conn = $this->createNewConnection($branch_id);
            $studentDetails = $conn->table('student_leaves as lev')
                ->select(
                    'lev.id',
                    'lev.student_id',
                    'lev.parent_id',
                    DB::raw('DATE_FORMAT(lev.from_leave, "%d-%m-%Y") as from_leave'),
                    DB::raw('DATE_FORMAT(lev.to_leave, "%d-%m-%Y") as to_leave'),
                    'as.name as reason',
                    'as.status as absent_status',
                    'as.recommended_leave_days',
                    'lev.status',
                    DB::raw("CONCAT(std.last_name, ' ', std.first_name) as name"),
                    DB::raw("CONCAT(p.last_name, ' ', p.first_name) as parent_name"),
                    'p.email'
                )
                ->join('students as std', 'lev.student_id', '=', 'std.id')
                ->join('parent as p', 'lev.student_id', '=', 'p.id')
                ->leftJoin('absent_reasons as as', 'lev.reasonId', '=', 'as.id')
                ->where('lev.id', $student_leave_tbl_id)
                ->first();
            // dd($studentDetails);
            if (isset($studentDetails->absent_status)) {
                    $user = User::where([
                        ['branch_id', '=', $branch_id],
                        ['role_id', '=', 5],
                        ['user_id', '=', $studentDetails->parent_id]
                    ])->get();
                    $leaveapproveDetails = [];
                    $leaveapproveDetails['status'] = $studentDetails->status;
                    $leaveapproveDetails['student_name'] = $studentDetails->name;
                    $leaveapproveDetails['parent_name'] = $studentDetails->parent_name;
                    $leaveapproveDetails['from_leave'] = $studentDetails->from_leave;
                    $leaveapproveDetails['to_leave'] = $studentDetails->to_leave;
                    $leaveapproveDetails['reason'] = $studentDetails->reason;
                    $leaveapproveDetails['date'] = date("Y-m-d H:i:s");
                    $details = [
                        'branch_id' => $branch_id,
                        'parent_id' => $studentDetails->parent_id,
                        'student_id' => $studentDetails->student_id,
                        'student_leave_tbl_id' => $student_leave_tbl_id,
                        'leave_approve_details' => $leaveapproveDetails
                    ];
                    Notification::send($user, new LeaveReasonNotification($details));
                    if ($studentDetails->absent_status == "1") {
                        $email = $studentDetails->email;
                        // $email = "karthik@aibots.my";
                        $data = array(
                            'parent_name' => isset($studentDetails->parent_name) ? $studentDetails->parent_name : "",
                            'reason' => isset($studentDetails->reason) ? $studentDetails->reason : 0,
                            'status' => isset($studentDetails->status) ? $studentDetails->status : 0,
                            'recommended_leave_days' => isset($studentDetails->recommended_leave_days) ? $studentDetails->recommended_leave_days : 0,
                            'date' => date("Y-m-d H:i:s"),
                        );
                        // return $data;
                        
                        $mailFromAddress = env('MAIL_FROM_ADDRESS', config('constants.client_email'));
                        Mail::send('auth.absent_reason', $data, function ($message) use ($email,$mailFromAddress) {
                            $message->to($email, 'Parent')->subject('Absent Reason Suggestions');
                            $message->from($mailFromAddress, 'Absent Reason Suggestions');
                        });
                        return true;
                    } else {
                        return false;
                    }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    // studnet leave end 
    // get all student leave list
    function getAllStudentLeaves(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $name_status = $request->name_status;
            
            // return $request;
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $department_id = isset($request->department_id) ? $request->department_id : null;
            $class_id = isset($request->class_id) ? $request->class_id : null;
            $section_id = isset($request->section_id) ? $request->section_id : null;
            $student_name = isset($request->student_name) ? $request->student_name : null;
            $status = isset($request->status) ? $request->status : null;
            $date = null;
            // Simplifying date range check
            if ($request->date) {
                $date_range = explode(' to ', $request->date);
                $date['from'] = $date_range[0];
                $date['to'] = isset($date_range[1]) ? $date_range[1] : $date_range[0];
            }
            // return $status;
            $studentDetails = $conn->table('student_leaves as lev')
                ->select(
                    'lev.id',
                    'lev.class_id',
                    'lev.section_id',
                    'lev.student_id',
                    // 'std.first_name',
                    // 'std.last_name',
                    DB::raw("CONCAT(std." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', std." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                    // DB::raw("CONCAT(std.last_name, ' ', std.first_name) as name"),
                    DB::raw('DATE_FORMAT(lev.from_leave, "%d-%m-%Y") as from_leave'),
                    DB::raw('DATE_FORMAT(lev.to_leave, "%d-%m-%Y") as to_leave'),
                    'ar.name as reason',
                    'slt.name as leave_type_name',
                    'lev.document',
                    'lev.status',
                    'lev.remarks',
                    'lev.teacher_remarks',
                    'cl.name as class_name',
                    'sc.name as section_name',
                    'lev.teacher_reason_id',
                    'lev.teacher_leave_type',
                    'lev.nursing_reason_id',
                    'lev.nursing_leave_type',
                    'lev.home_teacher_status',
                    'lev.nursing_teacher_status',
                    'lev.nursing_teacher_remarks',
                    'en.attendance_no',
                    'sd.name as department_name'
                )
                ->join('students as std', 'lev.student_id', '=', 'std.id')
                ->join('enrolls as en', 'lev.student_id', '=', 'en.student_id')
                ->join('classes as cl', 'en.class_id', '=', 'cl.id')
                ->join('sections as sc', 'en.section_id', '=', 'sc.id')
                ->leftJoin('student_leave_types as slt', 'lev.change_lev_type', '=', 'slt.id')
                ->leftJoin('absent_reasons as ar', 'lev.reasonId', '=', 'ar.id')
                ->join('staff_departments as sd', 'cl.department_id', '=', 'sd.id')
                // ->leftJoin('students as std', 'lev.student_id', '=', 'std.id')
                ->when($department_id, function ($query, $department_id) {
                    return $query->where('cl.department_id', $department_id);
                })
                ->when($class_id, function ($query, $class_id) {
                    return $query->where('lev.class_id', $class_id);
                })
                ->when($section_id, function ($query, $section_id) {
                    return $query->where('lev.section_id', $section_id);
                })
                ->when($status, function ($query, $status) {
                    return $query->where('lev.status', $status);
                })
                ->when($student_name, function ($query, $student_name) {
                    return $query->where(function ($query) use ($student_name) {
                        $query->where("std.first_name", "LIKE", "%{$student_name}%")
                            ->orWhere("std.last_name", "LIKE", "%{$student_name}%");
                    });
                })
                // ->when($date, function ($query, $date) {
                //     return $query->where(function ($query1) use ($date) {
                //         $query1->whereBetween('lev.from_leave', [$date['from'], $date['to']])
                //                ->orWhereBetween('lev.to_leave', [$date['from'], $date['to']]);
                //     });
                // })
                ->when($date, function ($query, $date) {
                    return $query->where(function ($query1) use ($date) {
                        $query1->where(function ($query2) use ($date) {
                            $query2->where('lev.from_leave', '>=', $date['from'])
                                   ->where('lev.from_leave', '<=', $date['to']);
                        })->orWhere(function ($query3) use ($date) {
                            $query3->where('lev.to_leave', '>=', $date['from'])
                                   ->where('lev.to_leave', '<=', $date['to']);
                        });
                    });
                })
                ->where('en.active_status', '=', '0')
                ->orderBy('lev.from_leave', 'desc')
                ->get();
            return $this->successResponse($studentDetails, 'Student details fetch successfully');
        }
    }
    
    // getStaffLeaveAssignList
    public function getStaffLeaveAssignList(Request $request)
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
            $department = $request->department;
            $staff_id = $request->staff_id;
            $name_status = $request->name_status;

            $StaffLeaveAssignDetails = $conn->table('staff_leave_assign as sla')
                ->select('sla.id', 'sla.staff_id', 
                DB::raw("CONCAT(st." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', st." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as staff_name"),
                // DB::raw("CONCAT(st.first_name, ' ', st.last_name) as staff_name"),
                 DB::raw("GROUP_CONCAT(lt.short_name) as leave_type"))
                ->join('staffs as st', 'sla.staff_id', '=', 'st.id')
                ->join('leave_types as lt', 'sla.leave_type', '=', 'lt.id')

                ->when($department, function ($query, $department) {
                    return $query->where('st.department_id', $department);
                })
                ->when($staff_id, function ($query, $staff_id) {
                    return $query->where('sla.staff_id', $staff_id);
                })
                ->where('st.is_active', '=', '0')
                ->groupBy('sla.staff_id')
                ->get();
            return $this->successResponse($StaffLeaveAssignDetails, 'Staff Leave Assign record fetch successfully');
        }
    }
    // get StaffLeaveAssign row details
    public function getStaffLeaveAssignDetails(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'staff_id' => 'required',
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
            $name_status = $request->name_status;
            $StaffLeaveAssignDetails['staff'] = $conn->table('staffs as s')->select('s.id as staff_id', 
            DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as staff_name"),        
            // DB::raw("CONCAT(s.first_name, ' ', s.last_name) as staff_name")
            )->where('s.id', $request->staff_id)->first();
            $StaffLeaveAssignDetails['leave'] = $conn->table('staff_leave_assign as sla')
                ->select('sla.id', 'lt.name as leave_name', 'sla.leave_days', 'sla.leave_type as leave_type_id')
                ->join('leave_types as lt', 'sla.leave_type', '=', 'lt.id')
                ->where('sla.staff_id', $request->staff_id)
                ->get();
            return $this->successResponse($StaffLeaveAssignDetails, 'Staff Leave Assign row fetch successfully');
        }
    }
    
    // getAllStaffDetails
    public function getAllStaffDetails(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $main_db = config('constants.main_db');
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $branchID = $request->branch_id;
            $name_status = $request->name_status;

            // get data
            $staffDetails['staff_details'] = $conn->table('staffs as stf')
                ->select(
                    'us.id as uuid',
                    'us.role_id',
                    'us.branch_id',
                    'stf.id',
                    'stf.department_id',
                    'stf.photo',
                    'stf.is_active',
                    'ala.staff_id',
                    'ala.level_one_staff_id',
                    'ala.level_two_staff_id',
                    'ala.level_three_staff_id',
                    DB::raw("GROUP_CONCAT(sdp.name) as department_name"),
                    DB::raw("CONCAT(stf." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', stf." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                    // DB::raw("CONCAT(stf.last_name, ' ', stf.first_name) as name")
                )
                // ->join('' . $main_db . '.users as us', 'stf.id', '=', 'us.user_id')
                ->join('' . $main_db . '.users as us', function ($join) use ($branchID) {
                    $join->on('stf.id', '=', 'us.user_id')
                        // ->on('us.branch_id', '=', DB::raw("'$branchID'"));
                        ->where('us.branch_id', $branchID);
                })
                // ->join('paxsuzen_pz-school.users as us', 'stf.id', '=', 'us.user_id')
                ->leftJoin("staff_departments as sdp", DB::raw("FIND_IN_SET(sdp.id,stf.department_id)"), ">", DB::raw("'0'"))
                ->leftJoin("assign_leave_approval as ala", 'ala.staff_id', '=', 'stf.id')
                // ->where([
                //     // ['us.branch_id', '=', $request->branch_id],
                //     ['stf.is_active', '=', '0']
                // ])
                // ->whereIn('us.role_id', ['3', '4'])
                // ->whereRaw('FIND_IN_SET(?,us.role_id)', ['4'])
                // ->orWhereRaw('FIND_IN_SET(?,us.role_id)', ['3'])
                ->where(function ($query) use ($branchID) {
                    // foreach ($search_terms as $item) {
                    $query->whereRaw('FIND_IN_SET(?,us.role_id)', ['4'])
                        ->orWhereRaw('FIND_IN_SET(?,us.role_id)', ['3']);
                    // ->orWhereRaw('FIND_IN_SET(?,us.role_id)', ['2']);
                    // }
                })
                ->where('stf.is_active', '=', '0')
                ->groupBy("stf.id")
                ->get();
            //above same query but get count
            $staffDetails['widget_details'] = $conn->table('staffs as stf')
                ->select(
                    DB::raw('count(stf.id) as total_staff'),
                    DB::raw('COUNT(CASE WHEN ala.level_one_staff_id IS NOT NULL THEN 1 END) as level_one_count'),
                    DB::raw('COUNT(CASE WHEN ala.level_two_staff_id IS NOT NULL THEN 1 END) as level_two_count'),
                    DB::raw('COUNT(CASE WHEN ala.level_three_staff_id IS NOT NULL THEN 1 END) as level_three_count'),
                )
                ->join('' . $main_db . '.users as us', function ($join) use ($branchID) {
                    $join->on('stf.id', '=', 'us.user_id')
                        ->where('us.branch_id', $branchID);
                })
                ->leftJoin("assign_leave_approval as ala", 'ala.staff_id', '=', 'stf.id')
                ->where(function ($query) use ($branchID) {
                    $query->whereRaw('FIND_IN_SET(?,us.role_id)', ['4'])
                        ->orWhereRaw('FIND_IN_SET(?,us.role_id)', ['3']);
                    // ->orWhereRaw('FIND_IN_SET(?,us.role_id)', ['2']);
                })
                ->where('stf.is_active', '=', '0')
                ->get();
            return $this->successResponse($staffDetails, 'Staffs admin record fetch successfully');
        }
    }
    
    // get Group row details
    public function getGroupDetails(Request $request)
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
            $name_status = $request->name_status;
            
            $id = $request->id;
            $group = $conn->table('groups')->where('id', $id)->first();

            $staff = NULL;
            $parent = NULL;
            $student = NULL;

            if ($group->staff) {
                $group_staff = explode(",", $group->staff);
                $staff = [];
                foreach ($group_staff as $gs) {
                    $data = $conn->table('staffs as s')
                        ->select("s.id", 
                        DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),

                        // DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"), 
                        DB::raw("GROUP_CONCAT(DISTINCT  dp.name) as department_name")
                        )
                        ->leftJoin("staff_departments as dp", DB::raw("FIND_IN_SET(dp.id,s.department_id)"), ">", DB::raw("'0'"))
                        ->where('s.id', $gs)->first();
                    array_push($staff, $data);
                }
            }

            if ($group->student) {
                $group_student = explode(",", $group->student);
                $student = [];
                foreach ($group_student as $gs) {
                    $data = $conn->table('students as s')
                        ->select("s.id",
                        DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),

                        //  DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"), 
                         'c.name as class_name', 'sc.name as section_name')
                        ->leftJoin('enrolls as e', 'e.student_id', '=', 's.id')
                        ->leftJoin('classes as c', 'e.class_id', '=', 'c.id')
                        ->leftJoin('sections as sc', 'e.section_id', '=', 'sc.id')
                        ->where('s.id', $gs)->first();
                    array_push($student, $data);
                }
            }

            if ($group->parent) {
                $group_parent = explode(",", $group->parent);
                $parent = [];
                foreach ($group_parent as $gp) {
                    $data = $conn->table('parent')->select("id", 
                    DB::raw("CONCAT(" . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', " . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),

                    // DB::raw("CONCAT(last_name, ' ', first_name) as name"), 
                    'email')->where('id', $gp)->first();
                    array_push($parent, $data);
                }
            }

            $groupDetails['group'] = $group;
            $groupDetails['staff'] = $staff;
            $groupDetails['student'] = $student;
            $groupDetails['parent'] = $parent;
            return $this->successResponse($groupDetails, 'Group row fetch successfully');
        }
    }
   
  
    // get Student Name
    public function getStudentName(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            $name_status = $request->name_status;
            $data = $conn->table('students as s')
                ->select("s.id",
                DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),

                //  DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"),
                  'c.name as class_name', 'sc.name as section_name')
                ->leftJoin('enrolls as e', 'e.student_id', '=', 's.id')
                ->leftJoin('classes as c', 'e.class_id', '=', 'c.id')
                ->leftJoin('sections as sc', 'e.section_id', '=', 'sc.id')
                ->where("s.first_name", "LIKE", "%{$request->name}%")
                ->orWhere("s.last_name", "LIKE", "%{$request->name}%")
                ->get();
            $output = '';
            if ($request->name) {
                if (!$data->isEmpty()) {
                    $output = '<ul class="list-group" style="display: block; position: relative; z-index: 1">';
                    foreach ($data as $row) {

                        $output .= '<li class="list-group-item" value="' . $row->id . '">' . $row->name . ' ( ' . $row->class_name . ' - ' .  $row->section_name . ' )</li>';
                    }
                    $output .= '</ul>';
                } else {
                    $output .= '<li class="list-group-item">' . 'No results Found' . '</li>';
                }
            } else {
                $output .= '<li class="list-group-item">' . 'No results Found' . '</li>';
            }
            return $output;
        }
    }

    // get Staff Name
    public function getStaffName(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $name_status = $request->name_status;
            // get data
            $data = $conn->table('staffs as s')
                ->select("s.id", 
                DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),

                // DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"), 
                DB::raw("GROUP_CONCAT(DISTINCT  dp.name) as department_name"))
                ->where("s.first_name", "LIKE", "%{$request->name}%")
                ->orWhere("s.last_name", "LIKE", "%{$request->name}%")
                ->leftJoin("staff_departments as dp", DB::raw("FIND_IN_SET(dp.id,s.department_id)"), ">", DB::raw("'0'"))
                ->groupBy("s.id")
                ->get();
            $output = '';
            if ($request->name) {
                if (!$data->isEmpty()) {
                    $output = '<ul class="list-group" style="display: block; position: relative; z-index: 1">';
                    foreach ($data as $row) {
                        $output .= '<li class="list-group-item" value="' . $row->id . '">' . $row->name . '( ' . $row->department_name . ' ) </li>';
                    }
                    $output .= '</ul>';
                } else {
                    $output .= '<li class="list-group-item">' . 'No results Found' . '</li>';
                }
            } else {
                $output .= '<li class="list-group-item">' . 'No results Found' . '</li>';
            }
            return $output;
        }
    }

    
    // get HostelGroups 
    public function getHostelGroupList(Request $request)
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
            $name_status = $request->name_status;
            // get data
            $groupDetails = $conn->table('hostel_groups as hg')
                ->select(
                    'hg.id',
                    'hg.name',
                    'hg.color',
                    DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as incharge_staff"),
                    DB::raw("CONCAT(st." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', st." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as incharge_student"),
                    DB::raw("CONCAT(stu." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as student"),

                    // DB::raw("GROUP_CONCAT( DISTINCT s.first_name, ' ', s.last_name) as incharge_staff"),
                    // DB::raw("GROUP_CONCAT( DISTINCT st.first_name, ' ', st.last_name) as incharge_student"),
                    // DB::raw("GROUP_CONCAT( DISTINCT stu.first_name, ' ', stu.last_name) as student"),
                )
                ->leftJoin('staffs as s', 'hg.incharge_staff', '=', 's.id')
                ->leftJoin('students as st', 'hg.incharge_student', '=', 'st.id')
                ->leftJoin("students as stu", DB::raw("FIND_IN_SET(stu.id,hg.student)"), ">", DB::raw("'0'"))
                ->groupBy('hg.id')
                ->get();

            return $this->successResponse($groupDetails, 'Hostel Group record fetch successfully');
        }
    }
    
    // teacherCount
    public function teacherCount(Request $request)
    {
        try{
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // get data
            $main_db = config('constants.main_db');
            // // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $branchID = $request->branch_id;
            $name_status = $request->name_status;
            // get all teachers
            $query = $conn->table('staffs as stf')
                ->select(
                    'us.id as uuid',
                    'us.branch_id',
                    'stf.id',
                    DB::raw("CONCAT(stf." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', stf." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),

                    // DB::raw("CONCAT(stf.last_name, ' ', stf.first_name) as name"),
                    'us.role_id',
                    'us.user_id',
                    'us.email'
                )
                ->join('' . $main_db . '.users as us', function ($join) use ($branchID) {
                    $join->on('stf.id', '=', 'us.user_id')
                        // ->on('us.branch_id', '=', DB::raw("'$branchID'"));
                        ->where('us.branch_id', $branchID);
                })
                ->where(function ($query) use ($branchID) {
                    $query->whereRaw('FIND_IN_SET(?,us.role_id)', ['4']);
                })
                ->where('stf.is_active', '=', '0')
                ->groupBy('stf.id')
                ->get()->count();
            return $this->successResponse($query, 'Student Count has been Fetched Successfully');
        }
         }
        catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in teacherCount');
        }
    }

    

    // get application list
    public function getApplicationList(Request $request)
    {
        try{
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $name_status = isset($request->name_status) ? $request->name_status : '1';
            // get data

            // $application = $conn->table('student_applications as s')
            //     ->select(
            //         's.*',
            //         DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"),
            //         DB::raw("CONCAT(s.first_name_english, ' ', s.last_name_english) as name_english"),
            //         DB::raw("CONCAT(s.first_name_furigana, ' ', s.last_name_furigana) as name_furigana"),
            //         DB::raw("CONCAT(s.first_name_common, ' ', s.last_name_common) as name_common"),
            //         'academic_cl.name as academic_grade',
            //         'ay.name as academic_year',
            //     )

            //     ->leftJoin('academic_year as ay', 's.academic_year', '=', 'ay.id')
            //     ->leftJoin('classes as academic_cl', 's.academic_grade', '=', 'academic_cl.id')
            //     ->when($request->admission == 1, function ($query) {
            //         return $query->where('s.status', '=', 'Approved')->where('s.phase_2_status', '=', 'Approved');
            //     })
            //     ->when($request->academic_year, function ($query) use ($request) {
            //         return $query->where('s.academic_year', '=', $request->academic_year);
            //     })
            //     ->when($request->academic_grade, function ($query)  use ($request) {
            //         return $query->where('s.academic_grade', '=', $request->academic_grade);
            //     })
            //     ->when($request->created_by, function ($query)  use ($request) {
            //         return $query->where('s.created_by', '=', $request->created_by)->where('s.created_by_role', '=', $request->role);
            //     })
            //     // ->when("s.created_by_role" == "5", function ($query) {
            //     //     return $query->leftJoin('parent as p', 's.created_by', '=', 'p.id');
            //     // })
            //     ->get();

            // $data = new \stdClass();
            // foreach ($application as $key => $app) {
            //     $created_by = "Public";
            //     if ($app->created_by_role == "5") {
            //         $name = $conn->table('parent')->select(DB::raw("CONCAT(last_name, ' ', first_name) as name"))->where('id', $app->created_by)->first();
            //         $created_by = $name->name . " (Parent)";
            //     } else if ($app->created_by_role == "2") {
            //         $name = $conn->table('staffs')->select(DB::raw("CONCAT(last_name, ' ', first_name) as name"))->where('id', $app->created_by)->first();
            //         $created_by = $name->name . " (Admin)";
            //     }
            //     // else if($app->created_by_role == "7"){
            //     //     $name = $conn->table('guest')->select("name")->where('id',$app->created_by)->first();
            //     //     $created_by = $name->name. " (Guest)";
            //     // }
            //     // dd($created_by);
            //     $application[$key]->created_by = $created_by;
            //     // $data = $app;
            //     // $application = $app;
            // }
            // Fetch application data
            $applications = $conn->table('student_applications as s')
            ->select(
                's.*',
                DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name_english' : 'first_name_english') . ", ' ', s." . ($name_status == 0 ? 'first_name_english' : 'last_name_english') . ") as name_english"),
                DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name_furigana' : 'first_name_furigana') . ", ' ', s." . ($name_status == 0 ? 'first_name_furigana' : 'last_name_furigana') . ") as name_furigana"),
                DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name_common' : 'first_name_common') . ", ' ', s." . ($name_status == 0 ? 'first_name_common' : 'last_name_common') . ") as name_common"),
               
                // DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"),
                // DB::raw("CONCAT(s.last_name_english, ' ', s.first_name_english) as name_english"),
                // DB::raw("CONCAT(s.last_name_furigana, ' ', s.first_name_furigana) as name_furigana"),
                // DB::raw("CONCAT(s.last_name_common, ' ', s.first_name_common) as name_common"),
                'academic_cl.name as academic_grade',
                'ay.name as academic_year',
            )
            ->leftJoin('academic_year as ay', 's.expected_academic_year', '=', 'ay.id')
            ->leftJoin('classes as academic_cl', 's.expected_grade', '=', 'academic_cl.id')
            ->when($request->admission == 1, function ($query) {
                return $query->where('s.status', '=', 'Approved')->where('s.phase_2_status', '=', 'Approved')->where('s.phase_2_status', '=', 'Approved')->where('s.enrolled_status', '=', 'Not Enrolled');
            })
            ->when($request->academic_year, function ($query) use ($request) {
                return $query->where('s.expected_academic_year', '=', $request->academic_year);
            })
            ->when($request->academic_grade, function ($query) use ($request) {
                return $query->where('s.expected_grade', '=', $request->academic_grade);
            })
            ->when($request->created_by, function ($query) use ($request) {
                return $query->where('s.created_by', '=', $request->created_by)->where('s.created_by_role', '=', $request->role);
            })
            ->orderBy('id','desc')
            ->get();
            // dd($applications);
            foreach ($applications as $application) {
                $created_by = "Public";
                if ($application->created_by_role == "5") {
                    $parent = $conn->table('parent')->select(DB::raw("CONCAT(last_name, ' ', first_name) as name"))->where('id', $application->created_by)->first();
                    $created_by = isset($parent->name)?$parent->name:"-" . " (Parent)";
                } elseif ($application->created_by_role == "2") {
                    $staff = $conn->table('staffs')->select(DB::raw("CONCAT(last_name, ' ', first_name) as name"))->where('id', $application->created_by)->first();
                    $created_by = isset($staff->name)?$staff->name:"-" . " (Admin)";
                }
                $application->created_by = $created_by;
            }

            return $this->successResponse($applications, 'Application record fetch successfully');
        }
         }
        catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getApplicationList');
        }
    }

    

    // get bulletin 
    public function getBuletinBoardList(Request $request)
    {
        try{
        $validator = \Validator::make($request->all(), [
            // 'token' => 'required',
            'branch_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $name_status = $request->name_status;
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
                DB::raw("CONCAT(p." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', p." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as parent_name"),
                DB::raw("CONCAT(st." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', st." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as student_name"),

                    // DB::raw('CONCAT(p.last_name, " ", p.first_name) as parent_name'),
                    // DB::raw('CONCAT(st.last_name, " ", st.first_name) as student_name')
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
        catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getBuletinBoardList');
        }
    }
   
    // get Student row details
    public function getStudentUpdateInfoDetails(Request $request)
    {
        try{
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
            $name_status = $request->name_status;
            // get data
            $getstudentDetails = $conn->table('student_change_info as sci')
                ->select(
                    'sci.id',
                    'sci.student_id',
                    'sci.parent_id',
                    'sci.last_name',
                    'sci.middle_name',
                    'sci.first_name',
                    'sci.last_name_english',
                    'sci.middle_name_english',
                    'sci.first_name_english',
                    'sci.last_name_furigana',
                    'sci.middle_name_furigana',
                    'sci.first_name_furigana',
                    'sci.last_name_common',
                    'sci.first_name_common',
                    'sci.birthday',
                    'sci.gender',
                    'sci.religion',
                    'sci.post_code',
                    'sci.address_unit_no',
                    'sci.address_condominium',
                    'sci.address_street',
                    'sci.address_district',
                    'sci.city',
                    'sci.state',
                    'sci.country',
                    'sci.nationality',
                    'sci.dual_nationality',
                    'sci.passport',
                    'sci.passport_photo',
                    'sci.passport_expiry_date',
                    'sci.visa_photo',
                    'sci.visa_type',
                    'sci.visa_type_others',
                    'sci.visa_expiry_date',
                    'sci.japanese_association_membership_number_student',
                    'sci.nric',
                    'sci.nric_photo',
                    'sci.school_name',
                    'sci.school_country',
                    'sci.school_state',
                    'sci.school_city',
                    'sci.school_postal_code',
                    'sci.school_enrollment_status',
                    'sci.school_enrollment_status_tendency',
                    
                    're.name as religion',
                    // 'rc.name as race',
                    // DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name")
                )
                ->leftJoin('religions as re', 'sci.religion', '=', 're.id')
                // ->leftJoin('races as rc', 'sci.race', '=', 'rc.id')
                ->where('sci.id', $id)
                ->first();
            $student_id = $getstudentDetails->student_id;
            unset($getstudentDetails->id, $getstudentDetails->student_id, $getstudentDetails->parent_id);
            // dd($getstudentDetails);
            $studentObj = new \stdClass();
            if (!empty($getstudentDetails)) {
                foreach ($getstudentDetails as $key => $suc) {

                    $old = $conn->table('students as s')->select('s.*','re.name as religion', 
                    DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
                    
                    // DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"), 
                    'c.name as class_name', 'sc.name as section_name')
                        ->leftJoin('enrolls as e', 'e.student_id', '=', 's.id')
                        ->leftJoin('classes as c', 'e.class_id', '=', 'c.id')
                        ->leftJoin('sections as sc', 'e.section_id', '=', 'sc.id')
                        ->leftJoin('religions as re', 's.religion', '=', 're.id')
                        ->where('s.id', '=', $student_id)->first();
                    // dd($old);
                    // dd(${$key});
                    if ($suc) {
                        // dd($key);

                        if ($key == "passport" || $key == "nric" ) {
                            // $encrypt = Helper::decryptStringData($old->$key);
                            // dd(Crypt::encryptString($old->$key));

                            ${$key} = [];
                            ${$key}['old_value'] =  Helper::decryptStringData($old->$key);
                            ${$key}['new_value'] =  Helper::decryptStringData($suc);
                        } else {
                            // if($key == "religion")
                            // {
                            //     $religion_old_name = "";
                            //     if($old->$key){

                            //         $religionOldValue = $conn->table('religions')
                            //         ->select('id','name')
                            //         ->where('id', $old->$key)
                            //         ->first();
                            //         $religion_old_name = $religionOldValue->name;
                            //     }
                            //     $religionNewValue = $conn->table('religions')
                            //     ->select('id','name')
                            //     ->where('id', $suc)
                            //     ->first();
                            //     ${$key} = [];
                            //     ${$key}['old_value'] =  $religion_old_name;
                            //     ${$key}['new_value'] =  $religionNewValue->name;
                            //     // dd($key);
                            // }
                            // else
                            // {
                                ${$key} = [];
                                ${$key}['old_value'] =  $old->$key;
                                ${$key}['new_value'] =  $suc;
                            // }
                        }

                        $studentObj->$key = ${$key};
                    }
                }
            }
            $studentDetails['student'] = $studentObj;
            $profile = $old;
            $profile->passport = Helper::decryptStringData($old->passport);
            $profile->nric = Helper::decryptStringData($old->nric);
            $profile->mobile_no = Helper::decryptStringData($old->mobile_no);
            $studentDetails['profile'] = $profile;

            return $this->successResponse($studentDetails, 'Student row fetch successfully');
        }
        }
        catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getStudentUpdateInfoDetails');
        }
    }

    
    // get Termination row details
    public function getTerminationDetails(Request $request)
    {
        try{
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
            $name_status = $request->name_status;

            // get data
            $id = $request->id;
            $terminationDetails = $conn->table('termination as t')->select('t.*',
            DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
            DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name_english' : 'first_name_english') . ", ' ', s." . ($name_status == 0 ? 'first_name_english' : 'last_name_english') . ") as name_english"),

            //  DB::raw("CONCAT(s.last_name_english, ' ', s.first_name_eglish) as name_english"),
            //   DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"),
              'c.name as class_name', 'sc.name as section_name')
                ->leftJoin('students as s', 's.id', '=', 't.student_id')
                ->leftJoin('enrolls as e', 'e.student_id', '=', 's.id')
                ->leftJoin('classes as c', 'e.class_id', '=', 'c.id')
                ->leftJoin('sections as sc', 'e.section_id', '=', 'sc.id')
                ->where('t.id', $id)->first();
            $terminationDetails->today_date =  now()->format('Y-m-d');
            return $this->successResponse($terminationDetails, 'Termination row fetch successfully');
        }
        }
        catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getTerminationDetails');
        }
    }
   
    // get Terminations 
    public function getTerminationList(Request $request)
    {
        try{
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $name_status = $request->name_status;
            // get data
            $parent_id = $request->parent_id;
            $terminationDetails = $conn->table('termination as t')->select('t.*', 'ay.name as academic_year', 's.gender', 
            
            DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name' : 'first_name') . ", ' ', s." . ($name_status == 0 ? 'first_name' : 'last_name') . ") as name"),
            DB::raw("CONCAT(s." . ($name_status == 0 ? 'last_name_english' : 'first_name_english') . ", ' ', s." . ($name_status == 0 ? 'first_name_english' : 'last_name_english') . ") as name_english"),

            // DB::raw("CONCAT(s.last_name_english, ' ', s.first_name_english) as name_english"), 
            // DB::raw("CONCAT(s.last_name, ' ', s.first_name) as name"),
             'c.name as class_name', 'sc.name as section_name')
                ->leftJoin('students as s', 's.id', '=', 't.student_id')
                ->leftJoin('enrolls as e', 'e.student_id', '=', 's.id')
                ->leftJoin('classes as c', 'e.class_id', '=', 'c.id')
                ->leftJoin('sections as sc', 'e.section_id', '=', 'sc.id')
                ->leftJoin('academic_year as ay', 'e.academic_session_id', '=', 'ay.id')
                ->where('e.active_status', '=', '0')
                ->when($parent_id, function ($query, $parent_id) {
                    return $query->where('t.created_by', $parent_id);
                })->orderBy('t.created_by', 'desc')->get()->toArray();

            // $groupDetails = $conn->table('termination')->get()->toArray();
            return $this->successResponse($terminationDetails, 'Termination record fetch successfully');
        }
        }
        catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getTerminationList');
        }
    }


    
    protected function clearCache($cache_name,$branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
