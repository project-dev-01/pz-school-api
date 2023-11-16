<?php

namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// base controller add
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\BranchRolesPermission;
// encrypt and decrypt
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\User;
use DateTime;
// notifications
use App\Notifications\ReliefAssignment;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use File;
use Exception;

class ApiControllerOne extends BaseController
{
    // add Grade Category
    public function addGradeCategory(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($Connection->table('grade_category')->where('name', '=', $request->name)->count() > 0) {
                return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            } else {
                // insert data
                $query = $Connection->table('grade_category')->insert([
                    'name' => $request->name,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Grade Category has been successfully saved');
                }
            }
        }
    }
    // get GradeCategoryList
    public function getGradeCategoryList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $GradeCategory = $Connection->table('grade_category')->get();
            return $this->successResponse($GradeCategory, 'Grade Category record fetch successfully');
        }
    }
    // get Grade Category Details row details
    public function getGradeCategoryDetails(Request $request)
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
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $deptDetails = $Connection->table('grade_category')->where('id', $id)->first();
            return $this->successResponse($deptDetails, 'Grade Category row fetch successfully');
        }
    }
    // updateGrade Category
    public function updateGradeCategory(Request $request)
    {
        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($Connection->table('grade_category')->where([['name', '=', $request->name], ['id', '!=', $id]])->count() > 0) {
                return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            } else {
                // update data
                $query = $Connection->table('grade_category')->where('id', $id)->update([
                    'name' => $request->name,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Grade Category Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        }
    }
    // delete Gade Category
    public function deleteGadeCategory(Request $request)
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
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $query = $Connection->table('grade_category')->where('id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Grade Category have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    // by class by all subjects
    public function classByAllSubjects(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'class_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $classByAllSubjects = $Connection->table('subject_assigns as sa')
                ->select(
                    'sa.subject_id',
                    'sb.name as subject_name',
                    DB::raw('CONCAT(stf.first_name, " ", stf.last_name) as teacher_name'),
                )
                ->leftJoin('staffs as stf', 'sa.teacher_id', '=', 'stf.id')
                ->join('subjects as sb', 'sa.subject_id', '=', 'sb.id')
                ->where([
                    ['sa.type', '=', '0'],
                    ['sb.exam_exclude', '=', '0'],
                    ['sa.class_id', '=', $request->class_id]
                ])
                ->groupBy('sa.subject_id')
                ->get();
            return $this->successResponse($classByAllSubjects, 'class by all subjects record fetch successfully');
        }
    }


    // get all paper types
    public function getPaperTypeList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $GradeCategory = $Connection->table('paper_type')->get();
            return $this->successResponse($GradeCategory, 'Paper type record fetch successfully');
        }
    }
    // import csv timetable
    // import Csv Parents
    public function importCsvTimetable(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'file' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $branchID = $request->branch_id;
            $Connection = $this->createNewConnection($request->branch_id);

            $file = $request->file('file');
            // File Details 
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            header('Content-type: text/plain; charset=utf-8');
            // Valid File Extensions
            $valid_extension = array("csv");
            // 2MB in Bytes
            $maxFileSize = 2097152;
            // Check file extension
            if (in_array(strtolower($extension), $valid_extension)) {
                // Check file size
                if ($fileSize <= $maxFileSize) {
                    // File upload location
                    // $location = 'uploads';
                    $location = base_path() . '/public/' . $request->branch_id . '/uploads/';
                    // Upload file
                    $file->move($location, $filename);
                    // Import CSV to Database
                    // $filepath = public_path($location."/".$filename);
                    $filepath = $location . "/" . $filename;
                    // $file = fopen($filename, "r");
                    // Reading file
                    $file = fopen($filepath, "r");
                    $importData_arr = array();
                    $i = 0;
                    while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
                        $num = count($filedata);
                        // Skip first row (Remove below comment if you want to skip the first row)
                        if ($i == 0) {
                            $i++;
                            continue;
                        }
                        for ($c = 0; $c < $num; $c++) {
                            $importData_arr[$i][] = $filedata[$c];
                        }
                        $i++;
                    }
                    fclose($file);
                    // dd($importData_arr);
                    // Insert to MySQL database
                    foreach ($importData_arr as $importData) {
                        $class_id = 0;
                        $section_id = 0;
                        $session_id = 0;
                        $semester_id = 0;
                        $break = 0;
                        $subject_id = 0;
                        $teacher_id = NULL;

                        $class_id = $importData[0];
                        $section_id = $importData[1];
                        $semester_id = $importData[3];
                        $session_id = $importData[4];
                        $day = strtolower($importData[2]);
                        // calendor data populate
                        $getObjRow = $Connection->table('semester as s')
                            ->select('start_date', 'end_date')
                            ->where('id', $semester_id)
                            ->first();
                        // print_r($getObjRow);
                        if (isset($importData[0])) {
                            $class_id = $importData[0];
                        }
                        if (isset($importData[1])) {
                            $section_id = $importData[1];
                        }
                        if (isset($importData[4])) {
                            $session_id = $importData[4];
                        }
                        if (isset($importData[3])) {
                            $semester_id = $importData[3];
                        }
                        if (isset($importData[6])) {
                            $teacher_id =  $importData[6];
                        }
                        if (isset($importData[5])) {
                            // if ($importData[5] == "" || trim($importData[5]) == "Rehat") {
                            //     $break = 1;
                            // } else {
                            //     $subject_id =  $importData[5];
                            // }
                            if (is_numeric($importData[5])) {
                                $subject_id =  $importData[5];
                            } else {
                                $break = 1;
                            }
                        }
                        // $breakType = ($break == 1 ? "Break" : null);
                        $breakType = ($break == 1 ? $importData[5] : null);
                        $time_start = date("H:i:s", strtotime($importData[7]));
                        $time_end = date("H:i:s", strtotime($importData[8]));

                        $data = [
                            'class_id' => $class_id,
                            'section_id' => $section_id,
                            'break' => $break,
                            'break_type' => $breakType,
                            'subject_id' => $subject_id,
                            'teacher_id' => $teacher_id,
                            'class_room' => $importData[9],
                            'time_start' => $time_start,
                            'time_end' => $time_end,
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $day,
                            'created_at' => date("Y-m-d H:i:s")
                        ];
                        $insertOrUpdateID = 0;
                        $insertOrUpdateID = $Connection->table('timetable_class')->insertGetId($data);

                        $bulkID = NuLL;
                        // return $break;
                        $this->addCalendorTimetable($branchID, $data, $getObjRow, $insertOrUpdateID, $bulkID);
                    }
                    // exit;
                    return $this->successResponse([], 'Import TimeTable Successful');
                } else {
                    return $this->send422Error('Validation error.', ['error' => 'File too large. File must be less than 2MB.']);
                }
            } else {
                return $this->send422Error('Validation error.', ['error' => 'Invalid File Extension']);
            }
        }
    }
    // import Csv add exam schedule
    public function addExamTimetable(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'file' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $branchID = $request->branch_id;
            $Connection = $this->createNewConnection($request->branch_id);

            $file = $request->file('file');
            // File Details 
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            header('Content-type: text/plain; charset=utf-8');
            // Valid File Extensions
            $valid_extension = array("csv");
            // 2MB in Bytes
            $maxFileSize = 2097152;
            // Check file extension
            if (in_array(strtolower($extension), $valid_extension)) {
                // Check file size
                if ($fileSize <= $maxFileSize) {
                    // File upload location
                    $location = 'uploads';
                    // Upload file
                    $file->move($location, $filename);
                    // Import CSV to Database
                    // $filepath = public_path($location."/".$filename);
                    $filepath = $location . "/" . $filename;
                    // $file = fopen($filename, "r");
                    // Reading file
                    $file = fopen($filepath, "r");
                    $importData_arr = array();
                    $i = 0;
                    while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
                        $num = count($filedata);
                        // Skip first row (Remove below comment if you want to skip the first row)
                        if ($i == 0) {
                            $i++;
                            continue;
                        }
                        for ($c = 0; $c < $num; $c++) {
                            $importData_arr[$i][] = $filedata[$c];
                        }
                        $i++;
                    }
                    // exit();
                    fclose($file);
                    // Insert to MySQL database
                    foreach ($importData_arr as $importData) {
                        // get internal staff name
                        $distributor = (isset($importData[12]) ? $importData[12] : null);
                        if (isset($importData[12])) {
                            if ($importData[11] == "1") {
                                $data = $Connection->table('staffs as s')->select(
                                    's.id',
                                    DB::raw('CONCAT(s.first_name, " ", s.last_name) as name')
                                )
                                    ->where('id', $importData[12])
                                    ->first();
                                $distributor = isset($data->name) ? $data->name : '';
                            }
                        }
                        $exam_date = date("Y-m-d", strtotime($importData[7]));
                        $time_start = date("H:i:s", strtotime($importData[8]));
                        $time_end = date("H:i:s", strtotime($importData[9]));
                        $data = [
                            'exam_id' => $importData[2],
                            'class_id' => $importData[0],
                            'section_id' => $importData[1],
                            'semester_id' => '2',
                            'session_id' => '1',
                            'subject_id' => $importData[3],
                            'paper_id' => $importData[4],
                            'time_start' => $time_start,
                            'time_end' => $time_end,
                            'hall_id' => $importData[10],
                            "distributor_type" => $importData[11],
                            "distributor" => $distributor,
                            "distributor_id" => $importData[12],
                            'exam_date' => $exam_date,
                            'created_at' => date("Y-m-d H:i:s")
                        ];
                        $Connection->table('timetable_exam')->insert($data);
                    }
                    return $this->successResponse([], 'Import TimeTable Successful');
                } else {
                    return $this->send422Error('Validation error.', ['error' => 'File too large. File must be less than 2MB.']);
                }
            } else {
                return $this->send422Error('Validation error.', ['error' => 'Invalid File Extension']);
            }
        }
    }
    function addCalendorTimetable($branchID, $row, $getObjRow, $insertOrUpdateID, $bulkID)
    {
        if ($getObjRow) {
            $start = $getObjRow->start_date;
            $end = $getObjRow->end_date;
            //
            $startDate = new DateTime($start);
            $endDate = new DateTime($end);
            // sunday=0,monday=1,tuesday=2,wednesday=3,thursday=4
            //friday =5,saturday=6
            if (isset($row['day'])) {
                if ($row['day'] == "monday") {
                    $day = 1;
                }
                if ($row['day'] == "tuesday") {
                    $day = 2;
                }
                if ($row['day'] == "wednesday") {
                    $day = 3;
                }
                if ($row['day'] == "thursday") {
                    $day = 4;
                }
                if ($row['day'] == "friday") {
                    $day = 5;
                }
                if ($row['day'] == "saturday") {
                    $day = 6;
                }
                if (isset($day)) {
                    $this->addTimetableCalendor($branchID, $startDate, $endDate, $day, $row, $insertOrUpdateID, $bulkID);
                }
            }
        }
    }
    // addTimetableCalendor
    function addTimetableCalendor($branchID, $startDate, $endDate, $day, $row, $insertOrUpdateID, $bulkID)
    {
        // create new connection
        $Connection = $this->createNewConnection($branchID);
        // delete existing calendor data
        $calendors = $Connection->table('calendors')->where([
            ['time_table_id', '=', $insertOrUpdateID]
        ])->count();

        if ($calendors > 0) {
            $Connection->table('calendors')->where('time_table_id', $insertOrUpdateID)->delete();
        }

        if (isset($row['subject_id']) && isset($row['teacher_id'])) {
            while ($startDate <= $endDate) {
                if ($startDate->format('w') == $day) {
                    $start = $startDate->format('Y-m-d') . " " . $row['time_start'];
                    $end = $startDate->format('Y-m-d') . " " . $row['time_end'];
                    $arrayInsert = [
                        "title" => "timetable",
                        "class_id" => $row['class_id'],
                        "section_id" => $row['section_id'],
                        "sem_id" => $row['semester_id'],
                        "session_id" => $row['session_id'],
                        "subject_id" => $row['subject_id'],
                        // "teacher_id" => $row['teacher'],
                        "teacher_id" => $row['teacher_id'],
                        "start" => $start,
                        "end" => $end,
                        "time_table_id" => $insertOrUpdateID,
                        'created_at' => date("Y-m-d H:i:s")
                    ];

                    // return $arrayInsert;

                    $Connection->table('calendors')->insert($arrayInsert);
                }
                $startDate->modify('+1 day');
            }
        }
    }

    // exam Schedule List
    public function examScheduleList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'student_id' => 'required'
        ]);

        // dd($request);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $con = $this->createNewConnection($request->branch_id);
            // get data
            $getStudentDetails = $con->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.semester_id',
                    'en.session_id',
                    'en.academic_session_id'
                )
                ->where([
                    ['en.student_id', '=', $request->student_id],
                    ['en.active_status', '=', '0']
                ])
                ->first();
            // return $getStudentDetails;
            // dd($getStudentDetails);
            $details = [];
            if ($getStudentDetails) {

                $details = $con->table('timetable_exam')->select('exam.name', 'timetable_exam.exam_id')
                    ->join('exam', 'timetable_exam.exam_id', '=', 'exam.id')
                    ->where([
                        ['class_id', $getStudentDetails->class_id],
                        ['section_id', $getStudentDetails->section_id],
                        ['semester_id', $getStudentDetails->semester_id],
                        ['session_id', $getStudentDetails->session_id],
                        ['timetable_exam.academic_session_id', $getStudentDetails->academic_session_id]
                    ])
                    ->groupBy('timetable_exam.exam_id')
                    ->orderBy('timetable_exam.exam_date', 'desc')
                    ->get();
            }
            return $this->successResponse($details, 'Exam Timetable record fetch successfully');
        }
    }
    // get Exam Timetable 
    public function getExamTimetableList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'exam_id' => 'required',
            'student_id' => 'required'
        ]);

        // return $request;
        // dd($request);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $con = $this->createNewConnection($request->branch_id);
            // get data

            $getStudentDetails = $con->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.semester_id',
                    'en.session_id',
                    'en.academic_session_id'
                )
                ->where([
                    ['en.student_id', '=', $request->student_id],
                    ['en.active_status', '=', '0']
                ])
                ->first();
            // dd($getStudentDetails);
            $exam_id = $request->exam_id;
            $class_id = $getStudentDetails->class_id;
            $section_id = $getStudentDetails->section_id;
            $session_id = $getStudentDetails->session_id;
            $semester_id = $getStudentDetails->semester_id;
            $academic_session_id = $getStudentDetails->academic_session_id;
            // dd($session_id);
            $details['exam'] = $con->table('subject_assigns as sa')
                ->select(
                    'sbj.name as subject_name',
                    'eh.hall_no',
                    'cl.name as class_name',
                    'sec.name as section_name',
                    'ex.name as exam_name',
                    'ep.paper_name as paper_name',
                    'ep.id as paper_id',
                    'sa.class_id as class_id',
                    'sa.section_id as section_id',
                    'sa.subject_id as subject_id',
                    'ttex.exam_id',
                    'ttex.semester_id',
                    'ttex.session_id',
                    'ttex.paper_id as timetable_paper_id',
                    'ttex.time_start',
                    'ttex.time_end',
                    'ttex.exam_date',
                    'ttex.hall_id',
                    'ttex.distributor_type',
                    'ttex.distributor',
                    'ttex.distributor_id',
                    'ttex.id'
                )
                ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                ->join('classes as cl', 'sa.class_id', '=', 'cl.id')
                ->join('sections as sec', 'sa.section_id', '=', 'sec.id')
                ->join('exam_papers as ep', function ($join) {
                    $join->on('sa.class_id', '=', 'ep.class_id')
                        ->on('sa.subject_id', '=', 'ep.subject_id');
                })
                ->where([
                    ['sa.class_id', $class_id],
                    ['sa.section_id', $section_id],
                    ['sa.type', '=', '0'],
                    ['sbj.exam_exclude', '=', '0']
                ])
                ->leftJoin('timetable_exam as ttex', function ($join) use ($exam_id, $semester_id, $session_id, $academic_session_id) {
                    $join->on('sa.class_id', '=', 'ttex.class_id')
                        ->on('sa.section_id', '=', 'ttex.section_id')
                        ->on('sa.subject_id', '=', 'ttex.subject_id')
                        ->on('ttex.semester_id', '=', DB::raw("'$semester_id'"))
                        ->on('ttex.session_id', '=', DB::raw("'$session_id'"))
                        ->on('ttex.academic_session_id', '=', DB::raw("'$academic_session_id'"))
                        ->on('ttex.paper_id', '=', 'ep.id')
                        ->where('ttex.exam_id', $exam_id);
                })
                ->leftJoin('exam as ex', 'ttex.exam_id', '=', 'ex.id')
                ->leftJoin('exam_hall as eh', 'ttex.hall_id', '=', 'eh.id')
                ->orderBy('sbj.id', 'asc')
                ->orderBy('ttex.exam_date', 'desc')
                ->orderBy('sbj.name', 'asc')
                ->get();
            $exam_name = $con->table('exam')->where('id', $exam_id)->first();
            $details['details']['exam_name'] = $exam_name->name;
            return $this->successResponse($details, 'Exam Timetable record fetch successfully');
        }
    }
    // by class get subjects
    public function getSubjectByClass(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'class_id' => 'required',
            'academic_session_id' => 'required',
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            $class_id = $request->class_id;
            $teacher_id = $request->teacher_id;
            $success = $createConnection->table('subject_assigns as sa')
                ->select(
                    'sb.id as subject_id',
                    'sb.name as subject_name'
                )
                ->join('subjects as sb', 'sa.subject_id', '=', 'sb.id')
                ->where('sa.type', '=', '0')
                ->where('sa.teacher_id', '!=', '0')
                ->where('sb.exam_exclude', '=', '0')
                ->where('sa.academic_session_id', '=', $request->academic_session_id)
                ->when($class_id != "All", function ($q)  use ($class_id) {
                    $q->where('sa.class_id', $class_id);
                })
                ->when($teacher_id, function ($q)  use ($teacher_id) {
                    $q->where('sa.teacher_id', $teacher_id);
                })
                ->groupBy('sa.subject_id')
                ->get();
            return $this->successResponse($success, 'subjects record fetch successfully');
        }
    }
    public function examByClassSubject(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'class_id' => 'required',
            'subject_id' => 'required',
            'academic_session_id' => 'required',
            'today' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data       
            $today = date('Y-m-d', strtotime($request->today));
            $class_id = $request->class_id;
            $getExamsName = $Connection->table('timetable_exam as texm')
                ->select(
                    'texm.exam_id as id',
                    'ex.name as name',
                    'texm.exam_date'
                )
                ->leftJoin('exam as ex', 'texm.exam_id', '=', 'ex.id')
                ->where('texm.exam_date', '<', $today)
                ->where('texm.academic_session_id', '=', $request->academic_session_id)
                ->when($class_id != "All", function ($q)  use ($class_id) {
                    $q->where('texm.class_id', $class_id);
                })
                ->where('texm.subject_id', '=', $request->subject_id)
                ->groupBy('texm.exam_id')
                ->get();
            return $this->successResponse($getExamsName, 'Exams  list of Name record fetch successfully');
        }
    }

    // by class single
    public function totgradeCalcuByClass(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'academic_year' => 'required',
            'class_id' => 'required',
            'subject_id' => 'required',
            'exam_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            $allbysubject = array();
            $academic_session_id = $request->academic_year;
            $semester_id = isset($request->semester_id) ? $request->semester_id : 0;
            $session_id = isset($request->session_id) ? $request->session_id : 0;
            // get subject total weightage
            $getExamMarks = $Connection->table('exam_papers as expp')
                ->select(
                    DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                    'expp.grade_category'
                )
                ->where([
                    ['expp.class_id', '=', $request->class_id],
                    ['expp.subject_id', '=', $request->subject_id],
                    ['expp.academic_session_id', '=', $academic_session_id]
                ])
                ->get();
            $total_subject_weightage = isset($getExamMarks[0]->total_subject_weightage) ? (int)$getExamMarks[0]->total_subject_weightage : 0;
            $grade_category = isset($getExamMarks[0]->grade_category) ? $getExamMarks[0]->grade_category : 0;
            //here get total sections
            $getTotalSections = $Connection->table('subject_assigns as sa')
                ->select(
                    'sa.class_id',
                    'sa.section_id',
                    'sbj.id as subject_id',
                    'sbj.name as subject_name',
                    'sf.id as staff_id',
                    DB::raw('CONCAT(sf.first_name, " ", sf.last_name) as teacher_name'),
                )
                ->join('staffs as sf', 'sa.teacher_id', '=', 'sf.id')
                ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                ->where([
                    ['sa.class_id', $request->class_id],
                    ['sa.subject_id', $request->subject_id],
                    ['sa.academic_session_id', '=', $academic_session_id],
                    ['sa.type', '=', '0'],
                    ['sbj.exam_exclude', '=', '0']
                ])
                ->get();
            // print_r($getTotalSections);

            // get all grade details header
            $allGradeDetails = $Connection->table('grade_marks')
                ->select('grade')
                ->where([
                    ['grade_category', '=', $grade_category]
                ])
                ->get();

            if (!empty($getTotalSections)) {
                foreach ($getTotalSections as $key => $val) {
                    $newobject = new \stdClass();
                    $section_id = $val->section_id;
                    $subject_name = $val->subject_name;
                    $teacher_name = $val->teacher_name;

                    $newobject->teacher_name = $teacher_name;
                    $newobject->subject_name = $subject_name;
                    // class name and section name by total students
                    $getstudentcount = $Connection->table('enrolls as en')
                        ->select(
                            'cl.name',
                            'en.semester_id',
                            'en.session_id',
                            'sc.name as section_name',
                            DB::raw('COUNT(en.student_id) as "totalStudentCount"')
                        )
                        ->join('classes as cl', 'en.class_id', '=', 'cl.id')
                        ->join('sections as sc', 'en.section_id', '=', 'sc.id')
                        ->where([
                            ['en.class_id', $request->class_id],
                            ['en.section_id', $section_id],
                            ['en.academic_session_id', '=', $academic_session_id],
                            ['en.semester_id', '=', $semester_id],
                            ['en.session_id', '=', $session_id]
                        ])
                        ->get();
                    // dd($getstudentcount);
                    $semester_id = isset($getstudentcount[0]->semester_id) ? $getstudentcount[0]->semester_id : 0;
                    $session_id = isset($getstudentcount[0]->session_id) ? $getstudentcount[0]->session_id : 0;
                    $totalNoOfStudents = isset($getstudentcount[0]->totalStudentCount) ? $getstudentcount[0]->totalStudentCount : 0;
                    $newobject->totalstudentcount = $totalNoOfStudents;
                    $newobject->name = $getstudentcount[0]->name;
                    $newobject->section_name = $getstudentcount[0]->section_name;

                    $getStudMarks = $Connection->table('student_marks as sm')
                        ->select(
                            DB::raw("group_concat(sm.score ORDER BY sm.student_id ASC) as score"),
                            DB::raw("group_concat(sm.student_id ORDER BY sm.student_id ASC) as student_ids"),
                            'sb.name as subject_name',
                            'sm.paper_id',
                            'sm.grade_category',
                            DB::raw("group_concat(expp.subject_weightage ORDER BY sm.student_id ASC) as subject_weightage")
                        )
                        ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                        ->join('timetable_exam as te', function ($join) {
                            $join->on('te.class_id', '=', 'sm.class_id')
                                ->on('te.section_id', '=', 'sm.section_id')
                                ->on('te.subject_id', '=', 'sm.subject_id')
                                ->on('te.semester_id', '=', 'sm.semester_id')
                                ->on('te.session_id', '=', 'sm.session_id')
                                ->on('te.paper_id', '=', 'sm.paper_id')
                                ->on('te.academic_session_id', '=', 'sm.academic_session_id');
                        })
                        ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                        ->where([
                            ['sm.class_id', '=', $request->class_id],
                            ['sm.section_id', '=', $section_id],
                            ['sm.subject_id', '=', $request->subject_id],
                            ['sm.exam_id', '=', $request->exam_id],
                            ['sm.semester_id', '=', $semester_id],
                            ['sm.session_id', '=', $session_id],
                            ['sm.academic_session_id', '=', $academic_session_id]
                        ])
                        ->groupBy('sm.paper_id')
                        ->get();
                    // dd($request->academic_session_id);
                    // here we get present absent pass fail count
                    $noOfPresentAbsent = $Connection->table('student_marks as sm')
                        ->select(
                            DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) AS absent'),
                            DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) AS present'),
                            DB::raw('SUM(CASE WHEN pass_fail = "Pass" THEN 1 ELSE 0 END) AS pass'),
                            DB::raw('SUM(CASE WHEN pass_fail = "Fail" THEN 1 ELSE 0 END) AS fail'),
                            DB::raw('SUM(CASE WHEN pass_fail = "Absent" THEN 1 ELSE 0 END) AS exam_absent')
                        )
                        ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                        ->join('timetable_exam as te', function ($join) {
                            $join->on('te.class_id', '=', 'sm.class_id')
                                ->on('te.section_id', '=', 'sm.section_id')
                                ->on('te.subject_id', '=', 'sm.subject_id')
                                ->on('te.semester_id', '=', 'sm.semester_id')
                                ->on('te.session_id', '=', 'sm.session_id')
                                ->on('te.paper_id', '=', 'sm.paper_id')
                                ->on('te.academic_session_id', '=', 'sm.academic_session_id');
                        })
                        ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                        ->where([
                            ['sm.class_id', '=', $request->class_id],
                            ['sm.section_id', '=', $section_id],
                            ['sm.subject_id', '=', $request->subject_id],
                            ['sm.exam_id', '=', $request->exam_id],
                            ['sm.semester_id', '=', $semester_id],
                            ['sm.session_id', '=', $session_id],
                            ['sm.academic_session_id', '=', $academic_session_id]
                        ])
                        ->groupBy('sm.subject_id')
                        ->groupBy('sm.student_id')
                        ->get();
                    // here we calculate present absent pass fail count
                    $presentCnt = 0;
                    $absentCnt = 0;
                    $passCnt = 0;
                    $failCnt = 0;
                    if (!empty($noOfPresentAbsent)) {
                        foreach ($noOfPresentAbsent as $key => $preab) {
                            $present = (int) $preab->present;
                            $absent = (int) $preab->absent;
                            $pass = (int) $preab->pass;
                            $fail = (int) $preab->fail;
                            $fail = (int) $preab->fail;
                            $exam_absent = (int) $preab->exam_absent;
                            // count present and absent students
                            if ($present != 0 && $absent == 0) {
                                $presentCnt++;
                            } else if ($present == 0 && $absent != 0) {
                                $absentCnt++;
                            } else if ($present == 0 && $absent == 0) {
                                $absentCnt;
                            } else if ($present != 0 && $absent != 0) {
                                $absentCnt++;
                            } else {
                                $presentCnt;
                                $absentCnt;
                            }
                            // count pass and fail students
                            if ($pass != 0 && $fail == 0 && $exam_absent == 0) {
                                $passCnt++;
                            } else if ($pass == 0 && $fail != 0 && $exam_absent == 0) {
                                $failCnt++;
                            } else if ($pass == 0 && $fail == 0 && $exam_absent != 0) {
                                $failCnt++;
                            } else if ($pass != 0 && $fail != 0 && $exam_absent == 0) {
                                $failCnt++;
                            } else if ($pass != 0 && $fail != 0 && $exam_absent != 0) {
                                $failCnt++;
                            } else if ($pass == 0 && $fail != 0 && $exam_absent != 0) {
                                $failCnt++;
                            } else if ($pass != 0 && $fail == 0 && $exam_absent != 0) {
                                $failCnt++;
                            } else if ($pass == 0 && $fail == 0 && $exam_absent == 0) {
                                $failCnt++;
                            } else {
                                $passCnt;
                                $failCnt;
                            }
                        }
                    }
                    $total_marks = [];
                    // here you get calculation based on student marks and subject weightage
                    if (!empty($getStudMarks)) {
                        foreach ($getStudMarks as $key => $value) {
                            $object = new \stdClass();
                            $total_sub_weightage = explode(',', $value->subject_weightage);
                            $total_score = explode(',', $value->score);
                            $marks = [];
                            // foreach for total no of students
                            for ($i = 0; $i < $totalNoOfStudents; $i++) {
                                $sub_weightage = isset($total_sub_weightage[$i]) ? (int) $total_sub_weightage[$i] : 0;
                                $score = isset($total_sub_weightage[$i]) ? (int) $total_score[$i] : 0;
                                $weightage = ($sub_weightage / $total_subject_weightage);
                                $marks[$i] = ($weightage * $score);
                            }
                            $object->marks = $marks;
                            $object->paper_id = $value->paper_id;
                            $object->grade_category = $value->grade_category;
                            array_push($total_marks, $object);
                        }
                    }
                    // here calculated values to sum by index
                    $sumArray = array();
                    if (!empty($total_marks)) {
                        foreach ($total_marks as $row) {
                            foreach ($row->marks as $index => $value) {
                                $sumArray[$index] = (isset($sumArray[$index]) ? $sumArray[$index] + $value : $value);
                            }
                        }
                    }
                    $gradeDetails = [];
                    if (!empty($sumArray)) {
                        foreach ($sumArray as $rows) {
                            $mark = (int) $rows;
                            $grade = $Connection->table('grade_marks')
                                ->select('grade', 'status')
                                ->where([
                                    ['min_mark', '<=', $mark],
                                    ['max_mark', '>=', $mark],
                                    ['grade_category', '=', $grade_category]
                                ])
                                ->first();
                            array_push($gradeDetails, $grade);
                        }
                        // here get grade count details
                        $gradecnt = array_count_values(array_column($gradeDetails, 'grade'));
                        $passcnt = array_count_values(array_column($gradeDetails, 'status'));
                    } else {
                        $gradecnt = new \stdClass();
                        $passcnt = new \stdClass();
                    }
                    // dd($passCnt);
                    if ($totalNoOfStudents > 0) {
                        $pass_percentage = ($passCnt / $totalNoOfStudents) * 100;
                        $newobject->pass_percentage = number_format($pass_percentage, 2);
                        $fail_percentage = ($failCnt / $totalNoOfStudents) * 100;
                        $newobject->fail_percentage = number_format($fail_percentage, 2);
                    } else {
                        $newobject->pass_percentage = 0;
                        $newobject->fail_percentage = 0;
                    }
                    // calculate gpa start
                    $gpa = 0;
                    $noOfStudGrade = 0;
                    $pointGrade = null;
                    foreach ($gradecnt as $gd => $gdcnt) {
                        $gdPnt = $Connection->table('grade_marks')
                            ->select('grade_point')
                            ->where([
                                ['grade', '=', $gd],
                                ['grade_category', '=', $grade_category]
                            ])
                            ->first();
                        if (isset($gdPnt->grade_point)) {
                            $grdPoint = $gdPnt->grade_point;
                            $mulGradePnt = ($gdcnt * $grdPoint);
                            $gpa += $mulGradePnt;
                            $noOfStudGrade += $gdcnt;
                        }
                    }
                    if ($gpa > 0 && $noOfStudGrade > 0) {
                        $calcGpa = ($gpa / $noOfStudGrade);
                        $calcGpa = number_format($calcGpa);
                        $pointByGrade = $Connection->table('grade_marks')
                            ->select('grade', 'grade_point')
                            ->where([
                                ['grade_point', '<=', $calcGpa],
                                ['grade_point', '>=', $calcGpa],
                                ['grade_category', '=', $grade_category]
                            ])
                            ->first();
                        $pointGrade = isset($pointByGrade->grade) ? $pointByGrade->grade : null;
                    }
                    // calculate gpa end
                    // get count details
                    $newobject->present_count = $presentCnt;
                    $newobject->absent_count = $absentCnt;
                    $newobject->pass_count = $passCnt;
                    $newobject->fail_count = $failCnt;
                    $newobject->gradecnt = $gradecnt;
                    $newobject->passcnt = $passcnt;
                    $newobject->gpa = isset($pointGrade) ? $pointGrade : '-';
                    array_push($allbysubject, $newobject);
                }
            }
            $data = [
                'headers' => $allGradeDetails,
                'allbysubject' => $allbysubject
            ];
            return $this->successResponse($data, 'byclass all Post record fetch successfully');
        }
    }
    // by class get subjects
    public function getClassBySection(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'class_id' => 'required',
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            $class_id = $request->class_id;
            $teacher_id = $request->teacher_id;
            $success = $createConnection->table('subject_assigns as sa')
                ->select(
                    'sc.id as section_id',
                    'sc.name as section_name'
                )
                ->join('sections as sc', 'sa.section_id', '=', 'sc.id')
                ->where('sa.type', '=', '0')
                ->where('sa.teacher_id', '!=', '0')
                ->when($class_id != "All", function ($q)  use ($class_id) {
                    $q->where('sa.class_id', $class_id);
                })
                ->when($teacher_id, function ($q)  use ($teacher_id) {
                    $q->where('sa.teacher_id', $teacher_id);
                })
                ->groupBy('sa.section_id')
                ->get();
            return $this->successResponse($success, 'sections record fetch successfully');
        }
    }
    public function examByClassSec(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'class_id' => 'required',
            'section_id' => 'required',
            'academic_session_id' => 'required',
            'today' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            $today = date('Y-m-d', strtotime($request->today));
            $class_id = $request->class_id;
            $section_id = $request->section_id;
            $getExamsName = $Connection->table('timetable_exam as texm')
                ->select(
                    'texm.exam_id as id',
                    'ex.name as name',
                    'texm.exam_date'
                )
                ->leftJoin('exam as ex', 'texm.exam_id', '=', 'ex.id')
                ->where('texm.exam_date', '<', $today)
                ->when($class_id != "All", function ($q)  use ($class_id) {
                    $q->where('texm.class_id', $class_id);
                })
                ->where('texm.section_id', '=', $section_id)
                ->where('texm.academic_session_id', '=', $request->academic_session_id)
                ->groupBy('texm.exam_id')
                ->get();
            return $this->successResponse($getExamsName, 'Exams  list of Name record fetch successfully');
        }
    }
    // by subject  single 
    public function totgradeCalcuBySubject(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'academic_year' => 'required',
            'class_id' => 'required',
            'section_id' => 'required',
            'exam_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data             

            $grade_list_master = array();
            $newobject = new \stdClass();
            $section_id = $request->section_id;
            $class_id = $request->class_id;
            $academic_session_id = $request->academic_year;
            $sem_id = isset($request->semester_id) ? $request->semester_id : 0;
            $ses_id = isset($request->session_id) ? $request->session_id : 0;
            // get grade category
            $getGradeCategory = $Connection->table('exam_papers as expp')
                ->select(
                    'expp.grade_category'
                )
                ->where([
                    ['expp.class_id', '=', $request->class_id],
                    ['expp.academic_session_id', '=', $academic_session_id]
                ])
                ->groupBy('expp.grade_category')
                ->get();
            $grade_category = isset($getGradeCategory[0]->grade_category) ? $getGradeCategory[0]->grade_category : 0;
            // get all grade details header
            $allGradeDetails = $Connection->table('grade_marks')
                ->select('grade')
                ->where([
                    ['grade_category', '=', $grade_category]
                ])
                ->get();
            // get exam paper weightage with subject assign
            $getExamMarks = $Connection->table('exam_papers as expp')
                ->select(
                    DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                    'expp.grade_category',
                    'sbj.id as subject_id',
                    'sbj.name as subject_name',
                    'cl.name as class_name',
                    'sec.name as section_name',
                    'sa.teacher_id',
                    DB::raw("CONCAT(sf.first_name, ' ', sf.last_name) as staff_name")
                )
                ->join('subject_assigns as sa', function ($join) use ($section_id, $academic_session_id) {
                    $join->on('sa.class_id', '=', 'expp.class_id')
                        ->on('sa.subject_id', '=', 'expp.subject_id')
                        ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->join('subjects as sbj', 'expp.subject_id', '=', 'sbj.id')
                ->join('classes as cl', 'sa.class_id', '=', 'cl.id')
                ->join('sections as sec', 'sa.section_id', '=', 'sec.id')
                ->leftJoin('staffs as sf', 'sa.teacher_id', '=', 'sf.id')
                ->where([
                    ['expp.class_id', $request->class_id],
                    ['sa.section_id', $section_id],
                    ['expp.academic_session_id', '=', $academic_session_id],
                    ['sa.type', '=', '0'],
                    ['sbj.exam_exclude', '=', '0']
                ])
                ->groupBy('expp.subject_id')
                ->get();
            // dd($getExamMarks);
            if (!empty($getExamMarks)) {
                foreach ($getExamMarks as $marks) {
                    $total_subject_weightage = isset($marks->total_subject_weightage) ? (int)$marks->total_subject_weightage : 0;
                    $newobject = new \stdClass();
                    $subject_id = $marks->subject_id;
                    $class_name = $marks->class_name;
                    $section_name = $marks->section_name;
                    $subject_name = $marks->subject_name;
                    $teacher_name = $marks->staff_name;

                    $newobject->class_name = $class_name;
                    $newobject->section_name = $section_name;
                    $newobject->subject_name = $subject_name;
                    $newobject->teacher_name = $teacher_name;
                    // class name and section name by total students
                    $getstudentcount = $Connection->table('enrolls as en')
                        ->select(
                            'cl.name',
                            'en.semester_id',
                            'en.session_id',
                            'sc.name as section_name',
                            DB::raw('COUNT(en.student_id) as "totalStudentCount"')
                        )
                        ->join('classes as cl', 'en.class_id', '=', 'cl.id')
                        ->join('sections as sc', 'en.section_id', '=', 'sc.id')
                        ->where([
                            ['en.class_id', $class_id],
                            ['en.section_id', $section_id],
                            ['en.academic_session_id', '=', $academic_session_id],
                            ['en.semester_id', '=', $sem_id],
                            ['en.session_id', '=', $ses_id]
                        ])
                        ->get();
                    // dd($getstudentcount);
                    $semester_id = isset($getstudentcount[0]->semester_id) ? $getstudentcount[0]->semester_id : 0;
                    $session_id = isset($getstudentcount[0]->session_id) ? $getstudentcount[0]->session_id : 0;
                    $totalNoOfStudents = isset($getstudentcount[0]->totalStudentCount) ? $getstudentcount[0]->totalStudentCount : 0;

                    $newobject->totalstudentcount = $totalNoOfStudents;

                    $getStudMarks = $Connection->table('student_marks as sm')
                        ->select(
                            DB::raw("group_concat(sm.score ORDER BY sm.student_id ASC) as score"),
                            DB::raw("group_concat(sm.student_id ORDER BY sm.student_id ASC) as student_ids"),
                            'sb.name as subject_name',
                            'sm.paper_id',
                            'sm.grade_category',
                            DB::raw("group_concat(expp.subject_weightage ORDER BY sm.student_id ASC) as subject_weightage")
                        )
                        ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                        ->join('timetable_exam as te', function ($join) {
                            $join->on('te.class_id', '=', 'sm.class_id')
                                ->on('te.section_id', '=', 'sm.section_id')
                                ->on('te.subject_id', '=', 'sm.subject_id')
                                ->on('te.semester_id', '=', 'sm.semester_id')
                                ->on('te.session_id', '=', 'sm.session_id')
                                ->on('te.paper_id', '=', 'sm.paper_id')
                                ->on('te.academic_session_id', '=', 'sm.academic_session_id');
                        })
                        ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                        ->where([
                            ['sm.class_id', '=', $class_id],
                            ['sm.section_id', '=', $section_id],
                            ['sm.subject_id', '=', $subject_id],
                            ['sm.exam_id', '=', $request->exam_id],
                            ['sm.semester_id', '=', $semester_id],
                            ['sm.session_id', '=', $session_id],
                            ['sm.academic_session_id', '=', $academic_session_id]
                        ])
                        ->groupBy('sm.paper_id')
                        ->get();
                    $noOfPresentAbsent = $Connection->table('student_marks as sm')
                        ->select(
                            DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) AS absent'),
                            DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) AS present'),
                            DB::raw('SUM(CASE WHEN pass_fail = "Pass" THEN 1 ELSE 0 END) AS pass'),
                            DB::raw('SUM(CASE WHEN pass_fail = "Fail" THEN 1 ELSE 0 END) AS fail'),
                            DB::raw('SUM(CASE WHEN pass_fail = "Absent" THEN 1 ELSE 0 END) AS exam_absent')
                        )
                        ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                        ->join('timetable_exam as te', function ($join) {
                            $join->on('te.class_id', '=', 'sm.class_id')
                                ->on('te.section_id', '=', 'sm.section_id')
                                ->on('te.subject_id', '=', 'sm.subject_id')
                                ->on('te.semester_id', '=', 'sm.semester_id')
                                ->on('te.session_id', '=', 'sm.session_id')
                                ->on('te.paper_id', '=', 'sm.paper_id')
                                ->on('te.academic_session_id', '=', 'sm.academic_session_id');
                        })
                        ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                        ->where([
                            ['sm.class_id', '=', $class_id],
                            ['sm.section_id', '=', $section_id],
                            ['sm.subject_id', '=', $subject_id],
                            ['sm.exam_id', '=', $request->exam_id],
                            ['sm.semester_id', '=', $semester_id],
                            ['sm.session_id', '=', $session_id],
                            ['sm.academic_session_id', '=', $academic_session_id]
                        ])
                        ->groupBy('sm.subject_id')
                        ->groupBy('sm.student_id')
                        ->get();
                    // get present absent count
                    $presentCnt = 0;
                    $absentCnt = 0;
                    $passCnt = 0;
                    $failCnt = 0;
                    if (!empty($noOfPresentAbsent)) {
                        foreach ($noOfPresentAbsent as $key => $preab) {
                            $present = (int) $preab->present;
                            $absent = (int) $preab->absent;
                            $pass = (int) $preab->pass;
                            $fail = (int) $preab->fail;
                            $fail = (int) $preab->fail;
                            $exam_absent = (int) $preab->exam_absent;

                            // count present and absent students
                            if ($present != 0 && $absent == 0) {
                                $presentCnt++;
                            } else if ($present == 0 && $absent != 0) {
                                $absentCnt++;
                            } else if ($present == 0 && $absent == 0) {
                                $absentCnt;
                            } else if ($present != 0 && $absent != 0) {
                                $absentCnt++;
                            } else {
                                $presentCnt;
                                $absentCnt;
                            }
                            // count pass and fail students
                            if ($pass != 0 && $fail == 0 && $exam_absent == 0) {
                                $passCnt++;
                            } else if ($pass == 0 && $fail != 0 && $exam_absent == 0) {
                                $failCnt++;
                            } else if ($pass == 0 && $fail == 0 && $exam_absent != 0) {
                                $failCnt++;
                            } else if ($pass != 0 && $fail != 0 && $exam_absent == 0) {
                                $failCnt++;
                            } else if ($pass != 0 && $fail != 0 && $exam_absent != 0) {
                                $failCnt++;
                            } else if ($pass == 0 && $fail != 0 && $exam_absent != 0) {
                                $failCnt++;
                            } else if ($pass != 0 && $fail == 0 && $exam_absent != 0) {
                                $failCnt++;
                            } else if ($pass == 0 && $fail == 0 && $exam_absent == 0) {
                                $failCnt++;
                            } else {
                                $passCnt;
                                $failCnt;
                            }
                        }
                    }
                    $total_marks = [];
                    // here you get calculation based on student marks and subject weightage
                    if (!empty($getStudMarks)) {
                        foreach ($getStudMarks as $key => $value) {
                            $object = new \stdClass();
                            $total_sub_weightage = explode(',', $value->subject_weightage);
                            $total_score = explode(',', $value->score);
                            $marks = [];
                            // foreach for total no of students
                            for ($i = 0; $i < $totalNoOfStudents; $i++) {
                                $sub_weightage = isset($total_sub_weightage[$i]) ? (int) $total_sub_weightage[$i] : 0;
                                $score = isset($total_sub_weightage[$i]) ? (int) $total_score[$i] : 0;
                                $weightage = ($sub_weightage / $total_subject_weightage);
                                $marks[$i] = ($weightage * $score);
                            }
                            $object->marks = $marks;
                            $object->paper_id = $value->paper_id;
                            $object->grade_category = $value->grade_category;
                            array_push($total_marks, $object);
                        }
                    }
                    // here calculated values to sum by index
                    $sumArray = array();
                    if (!empty($total_marks)) {
                        foreach ($total_marks as $row) {
                            foreach ($row->marks as $index => $value) {
                                $sumArray[$index] = (isset($sumArray[$index]) ? $sumArray[$index] + $value : $value);
                            }
                        }
                    }
                    $gradeDetails = [];
                    if (!empty($sumArray)) {
                        foreach ($sumArray as $rows) {
                            $mark = (int) $rows;
                            $grade = $Connection->table('grade_marks')
                                ->select('grade', 'status')
                                ->where([
                                    ['min_mark', '<=', $mark],
                                    ['max_mark', '>=', $mark],
                                    ['grade_category', '=', $grade_category]
                                ])
                                ->first();
                            array_push($gradeDetails, $grade);
                        }
                        // here get grade count details
                        $gradecnt = array_count_values(array_column($gradeDetails, 'grade'));
                        $passcnt = array_count_values(array_column($gradeDetails, 'status'));
                    } else {
                        $gradecnt = new \stdClass();
                        $passcnt = new \stdClass();
                    }
                    if ($totalNoOfStudents > 0) {
                        $pass_percentage = ($passCnt / $totalNoOfStudents) * 100;
                        $newobject->pass_percentage = number_format($pass_percentage, 2);
                        $fail_percentage = ($failCnt / $totalNoOfStudents) * 100;
                        $newobject->fail_percentage = number_format($fail_percentage, 2);
                    } else {
                        $newobject->pass_percentage = 0;
                        $newobject->fail_percentage = 0;
                    }
                    // calculate gpa start
                    $gpa = 0;
                    $noOfStudGrade = 0;
                    $pointGrade = null;
                    foreach ($gradecnt as $gd => $gdcnt) {
                        $gdPnt = $Connection->table('grade_marks')
                            ->select('grade_point')
                            ->where([
                                ['grade', '=', $gd],
                                ['grade_category', '=', $grade_category]
                            ])
                            ->first();
                        if (isset($gdPnt->grade_point)) {
                            $grdPoint = $gdPnt->grade_point;
                            $mulGradePnt = ($gdcnt * $grdPoint);
                            $gpa += $mulGradePnt;
                            $noOfStudGrade += $gdcnt;
                        }
                    }
                    if ($gpa > 0 && $noOfStudGrade > 0) {
                        $calcGpa = ($gpa / $noOfStudGrade);
                        $calcGpa = number_format($calcGpa);
                        $pointByGrade = $Connection->table('grade_marks')
                            ->select('grade', 'grade_point')
                            ->where([
                                ['grade_point', '<=', $calcGpa],
                                ['grade_point', '>=', $calcGpa],
                                ['grade_category', '=', $grade_category]
                            ])
                            ->first();
                        $pointGrade = isset($pointByGrade->grade) ? $pointByGrade->grade : null;
                    }
                    // calculate gpa end
                    // get count details
                    $newobject->present_count = $presentCnt;
                    $newobject->absent_count = $absentCnt;
                    $newobject->pass_count = $passCnt;
                    $newobject->fail_count = $failCnt;
                    $newobject->gradecnt = $gradecnt;
                    $newobject->passcnt = $passcnt;
                    $newobject->gpa = isset($pointGrade) ? $pointGrade : '-';
                    array_push($grade_list_master, $newobject);
                }
            }
            $data = [
                'headers' => $allGradeDetails,
                'grade_list_master' => $grade_list_master
            ];
            return $this->successResponse($data, 'bysubject all Post record fetch successfully');
        }
    }
    //by student exam results
    public function totgradeCalcuByStudent(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'class_id' => 'required',
            'section_id' => 'required',
            'exam_id' => 'required',
            'academic_year' => 'required'

        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            $allbyStudent = array();
            $class_id = $request->class_id;
            $section_id = $request->section_id;
            $exam_id = $request->exam_id;
            $academic_session_id = $request->academic_year;
            $sem_id = isset($request->semester_id) ? $request->semester_id : 0;
            $ses_id = isset($request->session_id) ? $request->session_id : 0;
            $student_id = isset($request->student_id) ? $request->student_id : null;
            // class name and section name by total students
            $getstudentdetails = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.semester_id',
                    'en.session_id',
                    DB::raw("CONCAT(stud.first_name, ' ', stud.last_name) as student_name")
                )
                ->join('classes as cl', 'en.class_id', '=', 'cl.id')
                ->join('sections as sc', 'en.section_id', '=', 'sc.id')
                ->join('students as stud', 'en.student_id', '=', 'stud.id')
                ->where([
                    ['en.class_id', $class_id],
                    ['en.section_id', $section_id],
                    ['en.academic_session_id', '=', $academic_session_id],
                    ['en.semester_id', '=', $sem_id],
                    ['en.session_id', '=', $ses_id]
                ])
                ->when($student_id, function ($q)  use ($student_id) {
                    $q->where('en.student_id', $student_id);
                })
                ->get();
            $get_all_subjects = $Connection->table('subject_assigns as sa')
                ->select(
                    'sa.class_id',
                    'sa.section_id',
                    'sbj.id as subject_id',
                    'sbj.name as subject_name'
                )
                ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                ->where([
                    ['sa.class_id', $class_id],
                    ['sa.section_id', $section_id],
                    ['sa.type', '=', '0'],
                    ['sa.academic_session_id', '=', $academic_session_id],
                    ['sbj.exam_exclude', '=', '0']
                ])
                ->groupBy('sa.subject_id')
                ->get();
            if (!empty($getstudentdetails)) {
                foreach ($getstudentdetails as $val) {
                    $student_obj = new \stdClass();

                    $student_id = $val->student_id;
                    $semester_id = $val->semester_id;
                    $session_id = $val->session_id;
                    $student_name = $val->student_name;

                    // add obj
                    $student_obj->student_id = $student_id;
                    $student_obj->student_name = $student_name;
                    $studentArr = [];
                    if (!empty($get_all_subjects)) {
                        foreach ($get_all_subjects as $value) {
                            $sbj_obj = new \stdClass();
                            // get subject total weightage
                            $getExamPaperWeightage = $Connection->table('exam_papers as expp')
                                ->select(
                                    DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                                    'expp.grade_category'
                                )
                                ->where([
                                    ['expp.class_id', '=', $value->class_id],
                                    ['expp.subject_id', '=', $value->subject_id],
                                    ['expp.academic_session_id', '=', $academic_session_id]
                                ])
                                ->get();
                            $total_subject_weightage = isset($getExamPaperWeightage[0]->total_subject_weightage) ? (int)$getExamPaperWeightage[0]->total_subject_weightage : 0;
                            $grade_category = isset($getExamPaperWeightage[0]->grade_category) ? $getExamPaperWeightage[0]->grade_category : 0;
                            $getStudMarksDetails = $Connection->table('student_marks as sm')
                                ->select(
                                    'expp.subject_weightage',
                                    'sb.name as subject_name',
                                    'sb.id as subject_id',
                                    'sm.score',
                                    'sm.paper_id',
                                    'sm.grade_category'
                                )
                                ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                                ->join('timetable_exam as te', function ($join) {
                                    $join->on('te.class_id', '=', 'sm.class_id')
                                        ->on('te.section_id', '=', 'sm.section_id')
                                        ->on('te.subject_id', '=', 'sm.subject_id')
                                        ->on('te.semester_id', '=', 'sm.semester_id')
                                        ->on('te.session_id', '=', 'sm.session_id')
                                        ->on('te.paper_id', '=', 'sm.paper_id')
                                        ->on('te.academic_session_id', '=', 'sm.academic_session_id');
                                })
                                ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                                ->where([
                                    ['sm.class_id', '=', $class_id],
                                    ['sm.section_id', '=', $section_id],
                                    ['sm.subject_id', '=', $value->subject_id],
                                    ['sm.exam_id', '=', $exam_id],
                                    ['sm.semester_id', '=', $semester_id],
                                    ['sm.session_id', '=', $session_id],
                                    ['sm.student_id', '=', $student_id],
                                    ['sm.academic_session_id', '=', $academic_session_id]
                                ])
                                ->groupBy('sm.paper_id')
                                ->get();

                            $sbj_obj->subject_id = $value->subject_id;
                            $marks = 0;
                            // here you get calculation based on student marks and subject weightage
                            if (!empty($getStudMarksDetails)) {
                                // grade calculations
                                foreach ($getStudMarksDetails as $Studmarks) {
                                    $sub_weightage = (int) $Studmarks->subject_weightage;
                                    $score = (int) $Studmarks->score;
                                    $grade_category = $Studmarks->grade_category;
                                    $weightage = ($sub_weightage / $total_subject_weightage);
                                    $marks += ($weightage * $score);
                                }
                                $mark = (int) $marks;
                                // get range grade
                                $grade = $Connection->table('grade_marks')
                                    ->select('grade')
                                    ->where([
                                        ['min_mark', '<=', $mark],
                                        ['max_mark', '>=', $mark],
                                        ['grade_category', '=', $grade_category]
                                    ])
                                    ->first();
                                $sbj_obj->marks = $marks != 0 ? number_format($marks) : $marks;
                                $sbj_obj->grade = isset($grade->grade) ? $grade->grade : '-';
                            } else {
                                $sbj_obj->marks = "Nill";
                                $sbj_obj->grade = "Nill";
                            }
                            array_push($studentArr, $sbj_obj);
                        }
                    }
                    $student_obj->student_class = $studentArr;
                    $gradecnt = array_count_values(array_column($studentArr, 'grade'));
                    // calculate gpa start
                    $gpa = 0;
                    $noOfStudGrade = 0;
                    $pointGrade = null;
                    foreach ($gradecnt as $gd => $gdcnt) {
                        $gdPnt = $Connection->table('grade_marks')
                            ->select('grade_point')
                            ->where([
                                ['grade', '=', $gd],
                                ['grade_category', '=', $grade_category]
                            ])
                            ->first();
                        if (isset($gdPnt->grade_point)) {
                            $grdPoint = $gdPnt->grade_point;
                            $mulGradePnt = ($gdcnt * $grdPoint);
                            $gpa += $mulGradePnt;
                            $noOfStudGrade += $gdcnt;
                        }
                    }
                    if ($gpa > 0 && $noOfStudGrade > 0) {
                        $calcGpa = ($gpa / $noOfStudGrade);
                        $calcGpa = number_format($calcGpa);
                        $pointByGrade = $Connection->table('grade_marks')
                            ->select('grade', 'grade_point')
                            ->where([
                                ['grade_point', '<=', $calcGpa],
                                ['grade_point', '>=', $calcGpa],
                                ['grade_category', '=', $grade_category]
                            ])
                            ->first();
                        $pointGrade = isset($pointByGrade->grade) ? $pointByGrade->grade : null;
                    }
                    $student_obj->gpa = $pointGrade;
                    array_push($allbyStudent, $student_obj);
                }
            }
            $data = [
                'headers' => isset($get_all_subjects) ? $get_all_subjects : [],
                'allbyStudent' => $allbyStudent
            ];
            return $this->successResponse($data, 'bystudent all Post record fetch successfully');
        }
    }
    // Individual Result 
    public function getbyresult_student(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'exam_id' => 'required',
            'class_id' => 'required',
            'section_id' => 'required',
            'academic_year' => 'required',
            'registerno' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $allbyStudent = array();
            $class_id = $request->class_id;
            $section_id = $request->section_id;
            $exam_id = $request->exam_id;
            $registerno = $request->registerno;
            $academic_session_id = $request->academic_year;
            $sem_id = isset($request->semester_id) ? $request->semester_id : 0;
            $ses_id = isset($request->session_id) ? $request->session_id : 0;
            $studentDetails = $Connection->table('students as stud')->Select(
                'stud.id',
                'en.class_id',
                'en.section_id',
                'en.semester_id',
                'en.session_id',
                'cl.name as class_name',
                'sc.name as section_name',
                DB::raw("CONCAT(stud.first_name, ' ', stud.last_name) as student_name"),
                'stud.birthday',
                'stud.register_no'
            )
                ->join('enrolls as en', 'en.student_id', '=', 'stud.id')
                ->join('classes as cl', 'en.class_id', '=', 'cl.id')
                ->join('sections as sc', 'en.section_id', '=', 'sc.id')
                ->where([
                    ['stud.register_no', $registerno],
                    ['en.academic_session_id', $academic_session_id],
                    ['en.semester_id', '=', $sem_id],
                    ['en.session_id', '=', $ses_id]
                ])
                ->first();
            if (isset($studentDetails->id)) {
                $student_id = $studentDetails->id;
                // class name and section name by total students
                $getstudentdetails = $Connection->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'en.semester_id',
                        'en.session_id',
                        DB::raw("CONCAT(stud.first_name, ' ', stud.last_name) as student_name")
                    )
                    ->join('classes as cl', 'en.class_id', '=', 'cl.id')
                    ->join('sections as sc', 'en.section_id', '=', 'sc.id')
                    ->join('students as stud', 'en.student_id', '=', 'stud.id')
                    ->where([
                        ['en.class_id', $class_id],
                        ['en.section_id', $section_id],
                        ['en.semester_id', '=', $sem_id],
                        ['en.session_id', '=', $ses_id],
                        ['en.academic_session_id', '=', $academic_session_id],
                        ['en.student_id', $student_id]
                    ])
                    ->get();
                $get_all_subjects = $Connection->table('subject_assigns as sa')
                    ->select(
                        'sa.class_id',
                        'sa.section_id',
                        'sbj.id as subject_id',
                        'sbj.name as subject_name'
                    )
                    ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                    ->where([
                        ['sa.class_id', $class_id],
                        ['sa.section_id', $section_id],
                        ['sa.academic_session_id', $academic_session_id],
                        ['sa.type', '=', '0'],
                        ['sbj.exam_exclude', '=', '0']
                    ])
                    ->groupBy('sa.subject_id')
                    ->get();
                if (!empty($getstudentdetails)) {
                    foreach ($getstudentdetails as $val) {
                        $student_obj = new \stdClass();

                        $student_id = $val->student_id;
                        $semester_id = $val->semester_id;
                        $session_id = $val->session_id;
                        $student_name = $val->student_name;

                        // add obj
                        $student_obj->student_id = $student_id;
                        $student_obj->student_name = $student_name;
                        $studentArr = [];
                        // dd($get_all_subjects);
                        if (!empty($get_all_subjects)) {
                            foreach ($get_all_subjects as $value) {
                                $sbj_obj = new \stdClass();
                                // get subject total weightage
                                $getExamPaperWeightage = $Connection->table('exam_papers as expp')
                                    ->select(
                                        DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                                        'expp.grade_category'
                                    )
                                    ->where([
                                        ['expp.class_id', '=', $value->class_id],
                                        ['expp.subject_id', '=', $value->subject_id],
                                        ['expp.academic_session_id', '=', $academic_session_id]
                                    ])
                                    ->get();
                                $total_subject_weightage = isset($getExamPaperWeightage[0]->total_subject_weightage) ? (int)$getExamPaperWeightage[0]->total_subject_weightage : 0;
                                $grade_category = isset($getExamPaperWeightage[0]->grade_category) ? $getExamPaperWeightage[0]->grade_category : 0;
                                $getStudMarksDetails = $Connection->table('student_marks as sm')
                                    ->select(
                                        'expp.subject_weightage',
                                        'sb.name as subject_name',
                                        'sb.id as subject_id',
                                        'sm.score',
                                        'sm.paper_id',
                                        'sm.grade_category'
                                    )
                                    ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                                    ->join('timetable_exam as te', function ($join) {
                                        $join->on('te.class_id', '=', 'sm.class_id')
                                            ->on('te.section_id', '=', 'sm.section_id')
                                            ->on('te.subject_id', '=', 'sm.subject_id')
                                            ->on('te.semester_id', '=', 'sm.semester_id')
                                            ->on('te.session_id', '=', 'sm.session_id')
                                            ->on('te.paper_id', '=', 'sm.paper_id')
                                            ->on('te.academic_session_id', '=', 'sm.academic_session_id');
                                    })
                                    ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                                    ->where([
                                        ['sm.class_id', '=', $class_id],
                                        ['sm.section_id', '=', $section_id],
                                        ['sm.subject_id', '=', $value->subject_id],
                                        ['sm.exam_id', '=', $exam_id],
                                        ['sm.semester_id', '=', $semester_id],
                                        ['sm.session_id', '=', $session_id],
                                        ['sm.academic_session_id', '=', $academic_session_id],
                                        ['sm.student_id', '=', $student_id]
                                    ])
                                    ->groupBy('sm.paper_id')
                                    ->get();

                                $sbj_obj->subject_id = $value->subject_id;
                                $marks = 0;
                                // here you get calculation based on student marks and subject weightage
                                if (!empty($getStudMarksDetails)) {
                                    // grade calculations
                                    foreach ($getStudMarksDetails as $Studmarks) {
                                        $sub_weightage = (int) $Studmarks->subject_weightage;
                                        $score = (int) $Studmarks->score;
                                        $grade_category = $Studmarks->grade_category;
                                        $weightage = ($sub_weightage / $total_subject_weightage);
                                        $marks += ($weightage * $score);
                                    }
                                    $mark = (int) $marks;
                                    // get range grade
                                    $grade = $Connection->table('grade_marks')
                                        ->select('grade')
                                        ->where([
                                            ['min_mark', '<=', $mark],
                                            ['max_mark', '>=', $mark],
                                            ['grade_category', '=', $grade_category]
                                        ])
                                        ->first();
                                    $sbj_obj->marks = $marks != 0 ? number_format($marks) : $marks;
                                    $sbj_obj->grade = isset($grade->grade) ? $grade->grade : '-';
                                } else {
                                    $sbj_obj->marks = "Nill";
                                    $sbj_obj->grade = "Nill";
                                }
                                array_push($studentArr, $sbj_obj);
                            }
                        }
                        // student classs
                        $student_obj->student_class = $studentArr;
                        $gradecnt = array_count_values(array_column($studentArr, 'grade'));
                        // calculate gpa start
                        $gpa = 0;
                        $noOfStudGrade = 0;
                        $pointGrade = null;
                        foreach ($gradecnt as $gd => $gdcnt) {
                            $gdPnt = $Connection->table('grade_marks')
                                ->select('grade_point')
                                ->where([
                                    ['grade', '=', $gd],
                                    ['grade_category', '=', $grade_category]
                                ])
                                ->first();
                            if (isset($gdPnt->grade_point)) {
                                $grdPoint = $gdPnt->grade_point;
                                $mulGradePnt = ($gdcnt * $grdPoint);
                                $gpa += $mulGradePnt;
                                $noOfStudGrade += $gdcnt;
                            }
                        }
                        if ($gpa > 0 && $noOfStudGrade > 0) {
                            $calcGpa = ($gpa / $noOfStudGrade);
                            $calcGpa = number_format($calcGpa);
                            $pointByGrade = $Connection->table('grade_marks')
                                ->select('grade', 'grade_point')
                                ->where([
                                    ['grade_point', '<=', $calcGpa],
                                    ['grade_point', '>=', $calcGpa],
                                    ['grade_category', '=', $grade_category]
                                ])
                                ->first();
                            $pointGrade = isset($pointByGrade->grade) ? $pointByGrade->grade : null;
                        }
                        $student_obj->gpa = $pointGrade;
                        array_push($allbyStudent, $student_obj);
                    }
                }
            }
            $data = [
                'student_details' => isset($studentDetails) ? $studentDetails : null,
                'headers' => isset($get_all_subjects) ? $get_all_subjects : [],
                'allbyStudent' => $allbyStudent
            ];
            return $this->successResponse($data, 'bystudent all Post record fetch successfully');
        }
    }
    // over all
    public function tot_grade_calcu_overall(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'class_id' => 'required',
            'exam_id' => 'required',
            'academic_year' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data     
            $allbysubject = array();
            $exam_id = $request->exam_id;
            $academic_session_id = $request->academic_year;
            $sem_id = isset($request->semester_id) ? $request->semester_id : 0;
            $ses_id = isset($request->session_id) ? $request->session_id : 0;
            // get grade category
            $getGradeCategory = $Connection->table('exam_papers as expp')
                ->select(
                    'expp.grade_category'
                )
                ->where([
                    ['expp.class_id', '=', $request->class_id],
                    ['expp.academic_session_id', '=', $academic_session_id]
                ])
                ->groupBy('expp.grade_category')
                ->get();
            $grade_category = isset($getGradeCategory[0]->grade_category) ? $getGradeCategory[0]->grade_category : 0;
            // get all grade details header
            $allGradeDetails = $Connection->table('grade_marks')
                ->select('grade')
                ->where([
                    ['grade_category', '=', $grade_category]
                ])
                ->get();

            $total_sujects_teacher = $Connection->table('subject_assigns as sa')
                ->select(
                    DB::raw("group_concat(sa.section_id) as all_section_id"),
                    'sbj.id as subject_id',
                    'sbj.name as subject_name'
                )
                ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                ->where([
                    ['sa.class_id', $request->class_id],
                    ['sa.academic_session_id', $academic_session_id],
                    ['sa.type', '=', '0'],
                    ['sbj.exam_exclude', '=', '0']
                ])
                ->groupBy('sa.subject_id')
                ->get();
            if (!empty($total_sujects_teacher)) {
                foreach ($total_sujects_teacher as $val) {
                    $object = new \stdClass();
                    $all_section_id = explode(',', $val->all_section_id);
                    $class_id = $request->class_id;
                    $subject_id = $val->subject_id;
                    $subject_name = $val->subject_name;

                    $object->class_id = $class_id;
                    $object->subject_id = $subject_id;
                    $object->subject_name = $subject_name;
                    // all section list
                    $studentArr = [];
                    $addAllStudCnt = 0;
                    $presentCnt = 0;
                    $absentCnt = 0;
                    $passCnt = 0;
                    $failCnt = 0;
                    // get subject total weightage
                    $getExamPaperWeightage = $Connection->table('exam_papers as expp')
                        ->select(
                            DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                            'expp.grade_category'
                        )
                        ->where([
                            ['expp.class_id', '=', $class_id],
                            ['expp.subject_id', '=', $subject_id],
                            ['expp.academic_session_id', $academic_session_id]
                        ])
                        ->get();
                    $total_subject_weightage = isset($getExamPaperWeightage[0]->total_subject_weightage) ? (int)$getExamPaperWeightage[0]->total_subject_weightage : 0;

                    foreach ($all_section_id as $key => $section) {

                        $studentDetails = $Connection->table('enrolls as en')
                            ->select(
                                'en.student_id',
                                'en.semester_id',
                                'en.session_id'
                            )
                            // ->join('classes as cl', 'en.class_id', '=', 'cl.id')
                            // ->join('sections as sc', 'en.section_id', '=', 'sc.id')
                            // ->join('students as stud', 'en.student_id', '=', 'stud.id')
                            ->where([
                                ['en.class_id', $class_id],
                                ['en.section_id', $section],
                                ['en.academic_session_id', '=', $academic_session_id],
                                ['en.semester_id', '=', $sem_id],
                                ['en.session_id', '=', $ses_id]
                            ])
                            ->get();
                        $semester_id = isset($studentDetails[0]->semester_id) ? $studentDetails[0]->semester_id : 0;
                        $session_id = isset($studentDetails[0]->session_id) ? $studentDetails[0]->session_id : 0;
                        $totalStudent = count($studentDetails);
                        $addAllStudCnt += $totalStudent;

                        $noOfPresentAbsent = $Connection->table('student_marks as sm')
                            ->select(
                                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) AS absent'),
                                DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) AS present'),
                                DB::raw('SUM(CASE WHEN pass_fail = "Pass" THEN 1 ELSE 0 END) AS pass'),
                                DB::raw('SUM(CASE WHEN pass_fail = "Fail" THEN 1 ELSE 0 END) AS fail'),
                                DB::raw('SUM(CASE WHEN pass_fail = "Absent" THEN 1 ELSE 0 END) AS exam_absent'),

                            )
                            ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                            ->join('timetable_exam as te', function ($join) {
                                $join->on('te.class_id', '=', 'sm.class_id')
                                    ->on('te.section_id', '=', 'sm.section_id')
                                    ->on('te.subject_id', '=', 'sm.subject_id')
                                    ->on('te.semester_id', '=', 'sm.semester_id')
                                    ->on('te.session_id', '=', 'sm.session_id')
                                    ->on('te.paper_id', '=', 'sm.paper_id')
                                    ->on('te.academic_session_id', '=', 'sm.academic_session_id');
                            })
                            ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                            ->where([
                                ['sm.class_id', '=', $class_id],
                                ['sm.section_id', '=', $section],
                                ['sm.subject_id', '=', $subject_id],
                                ['sm.exam_id', '=', $exam_id],
                                ['sm.semester_id', '=', $semester_id],
                                ['sm.session_id', '=', $session_id],
                                ['sm.academic_session_id', '=', $academic_session_id]
                            ])
                            ->groupBy('sm.subject_id')
                            ->groupBy('sm.student_id')
                            ->get();
                        // get present absent count
                        if (!empty($noOfPresentAbsent)) {
                            foreach ($noOfPresentAbsent as $key => $preab) {
                                $present = (int) $preab->present;
                                $absent = (int) $preab->absent;
                                $pass = (int) $preab->pass;
                                $fail = (int) $preab->fail;
                                $fail = (int) $preab->fail;
                                $exam_absent = (int) $preab->exam_absent;

                                // count present and absent students
                                if ($present != 0 && $absent == 0) {
                                    $presentCnt++;
                                } else if ($present == 0 && $absent != 0) {
                                    $absentCnt++;
                                } else if ($present == 0 && $absent == 0) {
                                    $absentCnt;
                                } else if ($present != 0 && $absent != 0) {
                                    $absentCnt++;
                                } else {
                                    $presentCnt;
                                    $absentCnt;
                                }
                                // count pass and fail students
                                if ($pass != 0 && $fail == 0 && $exam_absent == 0) {
                                    $passCnt++;
                                } else if ($pass == 0 && $fail != 0 && $exam_absent == 0) {
                                    $failCnt++;
                                } else if ($pass == 0 && $fail == 0 && $exam_absent != 0) {
                                    $failCnt++;
                                } else if ($pass != 0 && $fail != 0 && $exam_absent == 0) {
                                    $failCnt++;
                                } else if ($pass != 0 && $fail != 0 && $exam_absent != 0) {
                                    $failCnt++;
                                } else if ($pass == 0 && $fail != 0 && $exam_absent != 0) {
                                    $failCnt++;
                                } else if ($pass != 0 && $fail == 0 && $exam_absent != 0) {
                                    $failCnt++;
                                } else if ($pass == 0 && $fail == 0 && $exam_absent == 0) {
                                    $failCnt++;
                                } else {
                                    $passCnt;
                                    $failCnt;
                                }
                            }
                        }
                        if (!empty($studentDetails)) {
                            foreach ($studentDetails as $student) {
                                $sbj_obj = new \stdClass();

                                $student_id = $student->student_id;
                                $getStudMarksDetails = $Connection->table('student_marks as sm')
                                    ->select(
                                        'expp.subject_weightage',
                                        'sb.name as subject_name',
                                        'sb.id as subject_id',
                                        'sm.score',
                                        'sm.paper_id',
                                        'sm.grade_category'
                                    )
                                    ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                                    ->join('timetable_exam as te', function ($join) {
                                        $join->on('te.class_id', '=', 'sm.class_id')
                                            ->on('te.section_id', '=', 'sm.section_id')
                                            ->on('te.subject_id', '=', 'sm.subject_id')
                                            ->on('te.semester_id', '=', 'sm.semester_id')
                                            ->on('te.session_id', '=', 'sm.session_id')
                                            ->on('te.paper_id', '=', 'sm.paper_id')
                                            ->on('te.academic_session_id', '=', 'sm.academic_session_id');
                                    })
                                    ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                                    ->where([
                                        ['sm.class_id', '=', $class_id],
                                        ['sm.section_id', '=', $section],
                                        ['sm.subject_id', '=', $subject_id],
                                        ['sm.exam_id', '=', $exam_id],
                                        ['sm.semester_id', '=', $semester_id],
                                        ['sm.session_id', '=', $session_id],
                                        ['sm.student_id', '=', $student_id],
                                        ['sm.academic_session_id', '=', $academic_session_id]
                                    ])
                                    ->groupBy('sm.paper_id')
                                    ->get();
                                $marks = 0;
                                $marks = 0;
                                // // here you get calculation based on student marks and subject weightage
                                if (!empty($getStudMarksDetails)) {
                                    // grade calculations
                                    foreach ($getStudMarksDetails as $Studmarks) {
                                        $sub_weightage = (int) $Studmarks->subject_weightage;
                                        $score = (int) $Studmarks->score;
                                        $grade_category = $Studmarks->grade_category;
                                        // foreach for total no of students
                                        $weightage = ($sub_weightage / $total_subject_weightage);
                                        // dd($weightage);
                                        $marks += ($weightage * $score);
                                        // print_r($marks);
                                        // print_r($marks);

                                    }
                                    $mark = (int) $marks;
                                    // echo $mark;
                                    // get range grade
                                    $grade = $Connection->table('grade_marks')
                                        ->select('grade')
                                        ->where([
                                            ['min_mark', '<=', $mark],
                                            ['max_mark', '>=', $mark],
                                            ['grade_category', '=', $grade_category]
                                        ])
                                        ->first();
                                    $sbj_obj->marks = $marks != 0 ? number_format($marks) : $marks;
                                    $sbj_obj->grade = isset($grade->grade) ? $grade->grade : '-';
                                } else {
                                    $sbj_obj->marks = "Nill";
                                    $sbj_obj->grade = "Nill";
                                }

                                array_push($studentArr, $sbj_obj);
                            }
                        }
                    }
                    $gradecnt = array_count_values(array_column($studentArr, 'grade'));
                    // print_r($gradecnt);
                    // calculate gpa start
                    $gpa = 0;
                    $noOfStudGrade = 0;
                    $pointGrade = null;
                    foreach ($gradecnt as $gd => $gdcnt) {
                        $gdPnt = $Connection->table('grade_marks')
                            ->select('grade_point')
                            ->where([
                                ['grade', '=', $gd],
                                ['grade_category', '=', $grade_category]
                            ])
                            ->first();
                        if (isset($gdPnt->grade_point)) {
                            $grdPoint = $gdPnt->grade_point;
                            $mulGradePnt = ($gdcnt * $grdPoint);
                            $gpa += $mulGradePnt;
                            $noOfStudGrade += $gdcnt;
                        }
                    }
                    // echo "$gpa => $noOfStudGrade\n";
                    // exit;
                    if ($gpa > 0 && $noOfStudGrade > 0) {
                        $calcGpa = ($gpa / $noOfStudGrade);
                        $calcGpa = number_format($calcGpa);
                        $pointByGrade = $Connection->table('grade_marks')
                            ->select('grade', 'grade_point')
                            ->where([
                                ['grade_point', '<=', $calcGpa],
                                ['grade_point', '>=', $calcGpa],
                                ['grade_category', '=', $grade_category]
                            ])
                            ->first();
                        $pointGrade = isset($pointByGrade->grade) ? $pointByGrade->grade : null;
                    }
                    // echo "$pointGrade\n";
                    // exit;
                    // calculate gpa end
                    $object->gradecnt = $gradecnt;
                    $object->presentCnt = $presentCnt;
                    $object->absentCnt = $absentCnt;
                    $object->passCnt = $passCnt;
                    $object->failCnt = $failCnt;
                    $object->addAllStudCnt = $addAllStudCnt;
                    $object->gpa = isset($pointGrade) ? $pointGrade : '-';
                    if ($addAllStudCnt > 0) {
                        $pass_percentage = ($passCnt / $addAllStudCnt) * 100;
                        $object->pass_percentage = number_format($pass_percentage, 2);
                        $fail_percentage = ($failCnt / $addAllStudCnt) * 100;
                        $object->fail_percentage = number_format($fail_percentage, 2);
                    } else {
                        $object->pass_percentage = 0;
                        $object->fail_percentage = 0;
                    }
                    array_push($allbysubject, $object);
                }
            }
            $data = [
                'headers' => isset($allGradeDetails) ? $allGradeDetails : [],
                'allbysubject' => $allbysubject
            ];
            return $this->successResponse($data, 'bysubject all Post record fetch successfully');
        }
    }
    // Report card 
    // public function getreportcard(Request $request)
    // {
    //     $validator = \Validator::make($request->all(), [
    //         'branch_id' => 'required',
    //         'token' => 'required',
    //         'exam_id' => 'required',
    //         'selected_year' => 'required',
    //         'student_id' => 'required'
    //     ]);
    //     if (!$validator->passes()) {
    //         return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
    //     } else {
    //         // create new connection
    //         $Connection = $this->createNewConnection($request->branch_id);
    //         // get all teachers
    //         $allsubjectreport = array();
    //         $object = new \stdClass();
    //         // $subjectreport_studentmarks = $Connection->table('student_marks')
    //         //     ->select(
    //         //         'subjects.id as subject_id',
    //         //         'subjects.name as subject_name',
    //         //         'student_marks.score',
    //         //         'student_marks.grade',
    //         //         'student_marks.ranking',
    //         //         'student_marks.pass_fail',
    //         //         'timetable_exam.exam_date',
    //         //         'exam_papers.paper_name'
    //         //     )
    //         //     ->Join('subjects', 'student_marks.subject_id', '=', 'subjects.id')
    //         //     ->Join('exam_papers', 'student_marks.paper_id', '=', 'exam_papers.id')
    //         //     ->join('timetable_exam', function ($join) {
    //         //         $join->on('student_marks.exam_id', '=', 'timetable_exam.exam_id')
    //         //             ->on('student_marks.class_id', '=', 'timetable_exam.class_id')
    //         //             ->on('student_marks.section_id', '=', 'timetable_exam.section_id')
    //         //             ->on('student_marks.subject_id', '=', 'timetable_exam.subject_id')
    //         //             ->on('student_marks.semester_id', '=', 'timetable_exam.semester_id')
    //         //             ->on('student_marks.session_id', '=', 'timetable_exam.session_id')
    //         //             ->on('student_marks.paper_id', '=', 'timetable_exam.paper_id');
    //         //     })
    //         //     ->where([
    //         //         ['student_marks.exam_id', '=', $request->exam_id],
    //         //         ['student_marks.student_id', '=', $request->student_id]
    //         //     ])
    //         //     ->whereYear('timetable_exam.exam_date', $request->selected_year)
    //         //     ->get();
    //         // $getExamMarks = $Connection->table('exam_papers as expp')
    //         //     ->select(
    //         //         DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
    //         //         'expp.grade_category',
    //         //         'sbj.id as subject_id',
    //         //         'sbj.name as subject_name',
    //         //         'cl.name as class_name',
    //         //         'sec.name as section_name',
    //         //         'sa.teacher_id',
    //         //         DB::raw("CONCAT(sf.first_name, ' ', sf.last_name) as staff_name")
    //         //     )
    //         //     ->join('subject_assigns as sa', function ($join) use ($section_id) {
    //         //         $join->on('sa.class_id', '=', 'expp.class_id')
    //         //             ->on('sa.subject_id', '=', 'expp.subject_id')
    //         //             ->on('sa.section_id', '=', DB::raw("'$section_id'"));
    //         //     })
    //         //     ->join('subjects as sbj', 'expp.subject_id', '=', 'sbj.id')
    //         //     ->join('classes as cl', 'sa.class_id', '=', 'cl.id')
    //         //     ->join('sections as sec', 'sa.section_id', '=', 'sec.id')
    //         //     ->leftJoin('staffs as sf', 'sa.teacher_id', '=', 'sf.id')
    //         //     ->where([
    //         //         ['expp.class_id', $request->class_id],
    //         //         ['sa.section_id', $section_id],
    //         //         ['sa.type', '=', '0'],
    //         //         ['sbj.exam_exclude', '=', '0']
    //         //     ])
    //         //     ->groupBy('expp.subject_id')
    //         //     ->get();
    //         $getTotalExamMarksSub = $Connection->table('student_marks as sm')
    //             ->select(
    //                 'sb.id as subject_id',
    //                 'sb.name as subject_name'
    //             )
    //             ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
    //             ->join('timetable_exam as te', function ($join) {
    //                 $join->on('te.class_id', '=', 'sm.class_id')
    //                     ->on('te.section_id', '=', 'sm.section_id')
    //                     ->on('te.subject_id', '=', 'sm.subject_id')
    //                     ->on('te.semester_id', '=', 'sm.semester_id')
    //                     ->on('te.session_id', '=', 'sm.session_id')
    //                     ->on('te.paper_id', '=', 'sm.paper_id');
    //             })
    //             ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
    //             ->where([
    //                 ['sm.exam_id', '=', $request->exam_id],
    //                 ['sm.student_id', '=', $request->student_id]
    //             ])
    //             ->whereYear('te.exam_date', $request->selected_year)
    //             ->groupBy('sm.subject_id')
    //             ->get();
    //         dd($getTotalExamMarksSub);
    //         $getStudMarks = $Connection->table('student_marks as sm')
    //             ->select(
    //                 'sm.score',
    //                 'sm.pass_fail',
    //                 'sb.id as subject_id',
    //                 'sb.name as subject_name',
    //                 'sm.paper_id',
    //                 'sm.grade_category',
    //                 'expp.subject_weightage',
    //                 'sm.ranking',
    //                 'te.exam_date',
    //                 'expp.paper_name'
    //             )
    //             ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
    //             ->join('timetable_exam as te', function ($join) {
    //                 $join->on('te.class_id', '=', 'sm.class_id')
    //                     ->on('te.section_id', '=', 'sm.section_id')
    //                     ->on('te.subject_id', '=', 'sm.subject_id')
    //                     ->on('te.semester_id', '=', 'sm.semester_id')
    //                     ->on('te.session_id', '=', 'sm.session_id')
    //                     ->on('te.paper_id', '=', 'sm.paper_id');
    //             })
    //             ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
    //             ->where([
    //                 ['sm.exam_id', '=', $request->exam_id],
    //                 ['sm.student_id', '=', $request->student_id]
    //             ])
    //             ->whereYear('te.exam_date', $request->selected_year)
    //             ->groupBy('sm.subject_id')
    //             ->groupBy('sm.paper_id')
    //             // ->groupBy('sm.student_id')
    //             ->get();
    //         dd($getStudMarks);
    //         $total_marks = [];
    //         // here you get calculation based on student marks and subject weightage
    //         if (!empty($getStudMarks)) {
    //             foreach ($getStudMarks as $key => $value) {
    //                 $object = new \stdClass();
    //                 $total_sub_weightage = explode(',', $value->subject_weightage);
    //                 $total_score = explode(',', $value->score);
    //                 $marks = [];
    //                 // foreach for total no of students
    //                 for ($i = 0; $i < $totalNoOfStudents; $i++) {
    //                     $sub_weightage = isset($total_sub_weightage[$i]) ? (int) $total_sub_weightage[$i] : 0;
    //                     $score = isset($total_sub_weightage[$i]) ? (int) $total_score[$i] : 0;
    //                     $weightage = ($sub_weightage / $total_subject_weightage);
    //                     $marks[$i] = ($weightage * $score);
    //                 }
    //                 $object->marks = $marks;
    //                 $object->paper_id = $value->paper_id;
    //                 $object->grade_category = $value->grade_category;
    //                 array_push($total_marks, $object);
    //             }
    //         }

    //         dd($getStudMarks);
    //         $object->subjectreport = $getStudMarks;
    //         array_push($allsubjectreport, $object);
    //         //dd($allsubjectreport);
    //         return $this->successResponse($allsubjectreport, 'get report for student fetch successfully');
    //     }
    // }
    public function getreportcard(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'exam_id' => 'required',
            'student_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            $allbyStudent = array();
            $exam_id = $request->exam_id;
            $student_id = $request->student_id;
            // class name and section name by total students
            $getstudentdetails = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.student_id',
                    'en.semester_id',
                    'en.session_id',
                    'en.academic_session_id',
                    DB::raw("CONCAT(stud.first_name, ' ', stud.last_name) as student_name")
                )
                ->join('classes as cl', 'en.class_id', '=', 'cl.id')
                ->join('sections as sc', 'en.section_id', '=', 'sc.id')
                ->join('students as stud', 'en.student_id', '=', 'stud.id')
                ->where('en.student_id', '=', $student_id)
                ->where('en.active_status', '=', '0')
                ->first();
            $class_id = isset($getstudentdetails->class_id) ? $getstudentdetails->class_id : 0;
            $section_id = isset($getstudentdetails->section_id) ? $getstudentdetails->section_id : 0;
            $semester_id = isset($getstudentdetails->semester_id) ? $getstudentdetails->semester_id : 0;
            $session_id = isset($getstudentdetails->session_id) ? $getstudentdetails->session_id : 0;
            $academic_session_id = isset($getstudentdetails->academic_session_id) ? $getstudentdetails->academic_session_id : 0;
            $student_name = isset($getstudentdetails->student_name) ? $getstudentdetails->student_name : '-';
            $get_all_subjects = $Connection->table('subject_assigns as sa')
                ->select(
                    'sa.class_id',
                    'sa.section_id',
                    'sbj.id as subject_id',
                    'sbj.name as subject_name'
                )
                ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                ->where([
                    ['sa.class_id', $class_id],
                    ['sa.section_id', $section_id],
                    ['sa.academic_session_id', $academic_session_id],
                    ['sa.type', '=', '0'],
                    ['sbj.exam_exclude', '=', '0']
                ])
                ->groupBy('sa.subject_id')
                ->get();
            $student_obj = new \stdClass();
            // add obj
            $student_obj->student_id = $student_id;
            $student_obj->student_name = $student_name;
            $studentArr = [];
            if (!empty($get_all_subjects)) {
                foreach ($get_all_subjects as $value) {
                    $sbj_obj = new \stdClass();
                    // get subject total weightage
                    $getExamPaperWeightage = $Connection->table('exam_papers as expp')
                        ->select(
                            DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                            'expp.grade_category'
                        )
                        ->where([
                            ['expp.class_id', '=', $value->class_id],
                            ['expp.subject_id', '=', $value->subject_id]
                        ])
                        ->get();
                    $total_subject_weightage = isset($getExamPaperWeightage[0]->total_subject_weightage) ? (int)$getExamPaperWeightage[0]->total_subject_weightage : 0;

                    $getStudMarksDetails = $Connection->table('student_marks as sm')
                        ->select(
                            'expp.subject_weightage',
                            'sb.name as subject_name',
                            'sb.id as subject_id',
                            'sm.score',
                            'sm.paper_id',
                            'sm.grade_category'
                        )
                        ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                        ->join('timetable_exam as te', function ($join) {
                            $join->on('te.class_id', '=', 'sm.class_id')
                                ->on('te.section_id', '=', 'sm.section_id')
                                ->on('te.subject_id', '=', 'sm.subject_id')
                                ->on('te.semester_id', '=', 'sm.semester_id')
                                ->on('te.session_id', '=', 'sm.session_id')
                                ->on('te.paper_id', '=', 'sm.paper_id');
                        })
                        ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                        ->where([
                            ['sm.class_id', '=', $class_id],
                            ['sm.section_id', '=', $section_id],
                            ['sm.subject_id', '=', $value->subject_id],
                            ['sm.exam_id', '=', $exam_id],
                            ['sm.semester_id', '=', $semester_id],
                            ['sm.session_id', '=', $session_id],
                            ['sm.student_id', '=', $student_id]
                        ])
                        ->groupBy('sm.paper_id')
                        ->get();

                    $sbj_obj->subject_id = $value->subject_id;
                    $marks = 0;
                    $grade_category = 0;
                    // here you get calculation based on student marks and subject weightage
                    if (!empty($getStudMarksDetails)) {
                        // grade calculations
                        foreach ($getStudMarksDetails as $Studmarks) {
                            $sub_weightage = (int) $Studmarks->subject_weightage;
                            $score = (int) $Studmarks->score;
                            $grade_category = $Studmarks->grade_category;
                            $weightage = ($sub_weightage / $total_subject_weightage);
                            $marks += ($weightage * $score);
                        }
                        $mark = (int) $marks;
                        // get range grade
                        $grade = $Connection->table('grade_marks')
                            ->select('grade')
                            ->where([
                                ['min_mark', '<=', $mark],
                                ['max_mark', '>=', $mark],
                                ['grade_category', '=', $grade_category]
                            ])
                            ->first();
                        $sbj_obj->marks = $marks != 0 ? number_format($marks) : $marks;
                        $sbj_obj->grade = isset($grade->grade) ? $grade->grade : '-';
                    } else {
                        $sbj_obj->marks = "Nill";
                        $sbj_obj->grade = "Nill";
                    }
                    array_push($studentArr, $sbj_obj);
                }
            }
            $student_obj->student_class = $studentArr;
            array_push($allbyStudent, $student_obj);

            $data = [
                'headers' => isset($get_all_subjects) ? $get_all_subjects : [],
                'allbyStudent' => $allbyStudent
            ];
            return $this->successResponse($data, 'bystudent all Post record fetch successfully');
        }
    }
    // academic Year Add
    public function academicYearAdd(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($Connection->table('academic_year')->where('name', '=', $request->name)->count() > 0) {
                return $this->send422Error('Year Already Exist', ['error' => 'Year Already Exist']);
            } else {
                // insert data
                $query = $Connection->table('academic_year')->insert([
                    'name' => $request->name,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Academic year has been successfully saved');
                }
            }
        }
    }
    // academic Year List
    public function academicYearList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $Department = $Connection->table('academic_year')->orderBy('id', 'desc')->get();
            return $this->successResponse($Department, 'Academic year record fetch successfully');
        }
    }
    // academic Year Details
    public function academicYearDetails(Request $request)
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
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $deptDetails = $Connection->table('academic_year')->where('id', $id)->first();
            return $this->successResponse($deptDetails, 'Academic year row fetch successfully');
        }
    }
    // update academic Year Details
    public function updateAcademicYear(Request $request)
    {
        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($Connection->table('academic_year')->where([['name', '=', $request->name], ['id', '!=', $id]])->count() > 0) {
                return $this->send422Error('Academic Year Exist', ['error' => 'Academic Year Exist']);
            } else {
                // update data
                $query = $Connection->table('academic_year')->where('id', $id)->update([
                    'name' => $request->name,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Academic year have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        }
    }
    // delete academic Year
    public function deleteAcademicYear(Request $request)
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
            $staffConn = $this->createNewConnection($request->branch_id);
            // get data
            $query = $staffConn->table('academic_year')->where('id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Academic year have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    // get student list by entrolls
    public function getStudListByClassSecSemSess(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'class_id' => 'required',
            'section_id' => 'required',
            'session_id' => 'required',
            'semester_id' => 'required',
            'academic_session_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $Connection = $this->createNewConnection($request->branch_id);
            $getSubjectMarks = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    DB::raw("CONCAT(st.first_name, ' ', st.last_name) as name"),
                    'st.id as id',
                    'st.register_no',
                    'st.roll_no',
                    'st.photo'
                )
                ->join('students as st', 'st.id', '=', 'en.student_id')
                ->where([
                    ['en.class_id', '=', $request->class_id],
                    ['en.section_id', '=', $request->section_id],
                    ['en.semester_id', '=', $request->semester_id],
                    ['en.session_id', '=', $request->session_id],
                    ['en.academic_session_id', '=', $request->academic_session_id],
                    ['en.active_status', '=', '0']
                ])
                ->orderBy('st.first_name', 'asc')
                ->get();
            return $this->successResponse($getSubjectMarks, 'Students record fetch successfully');
        }
    }
    //add attendance
    function addPromotion(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'promote_year' => 'required',
            'promote_class_id' => 'required',
            'promote_semester_id' => 'required',
            'promote_session_id' => 'required',
            'promotion' => 'required',
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);

            $promotion = $request->promotion;
            if (!empty($promotion)) {
                foreach ($promotion as $key => $value) {
                    // dd($value['attendance_id']);
                    // dd($value);
                    if (isset($value['promotion_status'])) {
                        $student_id = (isset($value['student_id']) ? $value['student_id'] : 0);
                        $register_no = (isset($value['register_no']) ? $value['register_no'] : 0);
                        $roll_no = (isset($value['roll_no']) ? $value['roll_no'] : 0);
                        $promote_section_id = (isset($value['promote_section_id']) ? $value['promote_section_id'] : 0);
                        // here update studentID as promote
                        $Connection->table('enrolls')
                            ->where('student_id', '=', $student_id)
                            ->update(['active_status' => 1]);
                        $dataPromote = array(
                            'student_id' => $student_id,
                            'academic_session_id' => $request->promote_year,
                            'class_id' => $request->promote_class_id,
                            'section_id' => $promote_section_id,
                            'semester_id' => $request->promote_semester_id,
                            'session_id' => $request->promote_session_id,
                            'active_status' => 0,
                            'roll' => $roll_no
                        );
                        $row = $Connection->table('enrolls')
                            ->select(
                                'id',
                                'class_id',
                                'section_id',
                                'roll',
                                'session_id',
                                'semester_id'
                            )->where([
                                ['student_id', '=', $student_id],
                                ['class_id', '=', $request->promote_class_id],
                                ['section_id', '=', $promote_section_id],
                                ['semester_id', '=', $request->promote_semester_id],
                                ['session_id', '=', $request->promote_session_id]
                            ])->first();
                        // if (isset($value['promotion_status'])) {
                        if (isset($row->id)) {
                            $dataPromote['updated_at'] = date("Y-m-d H:i:s");
                            $Connection->table('enrolls')->where('id', $row->id)->update($dataPromote);
                        } else {
                            $dataPromote['created_at'] = date("Y-m-d H:i:s");
                            $Connection->table('enrolls')->insert($dataPromote);
                        }
                        // } else {
                        //     $dePromote = array(
                        //         'student_id' => $student_id,
                        //         'class_id' => $row->class_id,
                        //         'section_id' => $row->section_id,
                        //         'semester_id' => $row->semester_id,
                        //         'session_id' => $row->session_id,
                        //         'roll' => $row->roll
                        //     );
                        //     if (isset($row->id)) {
                        //         $dePromote['updated_at'] = date("Y-m-d H:i:s");
                        //         $Connection->table('enrolls')->where('id', $row->id)->update($dePromote);
                        //     } else {
                        //         $dePromote['created_at'] = date("Y-m-d H:i:s");
                        //         $Connection->table('enrolls')->insert($dePromote);
                        //     }
                        // }
                    }
                }
            }
            return $this->successResponse([], 'Promotion successfuly.');
        }
    }
    // relief assignment
    public function getAllLeaveReliefAssignment(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'academic_session_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $leaveDetails = $conn->table('staff_leaves as lev')
                ->select(
                    'lev.id',
                    'lev.staff_id',
                    DB::raw('CONCAT(stf.first_name, " ", stf.last_name) as name'),
                    DB::raw('DATE_FORMAT(lev.from_leave, "%d-%m-%Y") as from_leave'),
                    DB::raw('DATE_FORMAT(lev.to_leave, "%d-%m-%Y") as to_leave'),
                    DB::raw('DATE_FORMAT(lev.created_at, "%d-%m-%Y") as created_at'),
                    'lev.total_leave',
                    'lt.name as leave_type_name',
                    'rs.name as reason_name',
                    'lev.reason_id',
                    'lev.document',
                    'lev.status',
                    'lev.remarks',
                    'lev.assiner_remarks'

                )
                ->join('leave_types as lt', 'lev.leave_type', '=', 'lt.id')
                ->join('staffs as stf', 'lev.staff_id', '=', 'stf.id')
                ->leftJoin('reasons as rs', 'lev.reason_id', '=', 'rs.id')
                ->where([
                    ['lev.academic_session_id', '=', $request->academic_session_id],
                    // ['lev.status', '=', 'Approve']
                ])
                ->orderBy('lev.from_leave', 'desc')
                ->get();
            return $this->successResponse($leaveDetails, 'Staff leave details fetch successfully');
        }
    }
    public function getSubjectsByStaffIdWithDate(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'academic_session_id' => 'required',
            'staff_id' => 'required',
            'from_date' => 'required',
            'to_date' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $from_leave = date('Y-m-d', strtotime($request['from_date']));
            $to_leave = date('Y-m-d', strtotime($request['to_date']));
            $leave_teacher = $conn->table('calendors as cl')
                ->select(
                    'cl.id',
                    'cl.start',
                    'cl.end',
                    'cl.relief_assignment_id',
                    'cl.time_table_id',
                    DB::raw('date(cl.end) as end_date'),
                    'c.name as class_name',
                    'sc.name as section_name',
                    'sbj.name as subject_name',
                    'acal.teacher_id as assigned_teacher_id',
                    DB::raw("CONCAT(stf.first_name, ' ', stf.last_name) as teacher_name")
                )
                ->join('classes as c', 'cl.class_id', '=', 'c.id')
                ->join('sections as sc', 'cl.section_id', '=', 'sc.id')
                ->join('subjects as sbj', 'cl.subject_id', '=', 'sbj.id')
                ->join('staffs as stf', 'cl.teacher_id', '=', 'stf.id')
                ->leftJoin('calendors as acal', 'cl.id', '=', 'acal.relief_assignment_id')
                ->where([
                    // ['sa.academic_session_id', '=', $request->academic_session_id],
                    ['cl.teacher_id', '=', $request->staff_id]
                ])
                ->whereBetween(DB::raw('date(cl.end)'), [$from_leave, $to_leave])
                // ->where([
                //     [DB::raw('date(cl.end)'), '>=', DB::raw('date(ev.start_date)')],
                //     [DB::raw('date(cl.end)'), '<=', DB::raw('date(ev.end_date)')],
                //     ['ev.holiday', '=', '0']
                // ])
                ->orderBy('cl.start', 'asc')
                ->get()
                ->groupBy('end_date');
            // dd($leave_teacher);
            $output = [];
            // dd($leave_teacher);

            if (!empty($leave_teacher)) {
                foreach ($leave_teacher as $key => $value) {
                    // print_r($key);
                    // $object = new \stdClass();
                    // echo "------";
                    // print_r($value);
                    $subjectArr = [];
                    foreach ($value as $val) {
                        // $reqData->start = $val->start;
                        // $reqData->end = $val->end;
                        // $reqData->academic_session_id = $request->academic_session_id;
                        $request->request->add(['start' => $val->start]); //add request
                        $request->request->add(['end' => $val->end]); //add request
                        $teacherList = $this->getStaffListByTimeslot($request);
                        $val->teacherList = $teacherList;
                        array_push($subjectArr, $val);
                    }
                    $output[$key] = $subjectArr;
                    // $object->$key = $subjectArr;
                    // array_push($output, $object);
                }
            }
            // dd($output);
            // get teacher details
            // $output['teacher'] = $conn->table('subject_assigns as sa')->select(
            //     's.id',
            //     DB::raw('CONCAT(s.first_name, " ", s.last_name) as name')
            // )
            //     ->join('staffs as s', 'sa.teacher_id', '=', 's.id')
            //     // ->where('sa.class_id', $request->class_id)
            //     // ->where('sa.section_id', $request->section_id)
            //     ->where('sa.academic_session_id', $request->academic_session_id)
            //     // type zero mean main
            //     ->where('sa.type', '=', '0')
            //     ->groupBy('sa.teacher_id')
            //     ->get();
            return $this->successResponse($output, 'Staff calendor details fetch successfully');
        }
    }
    public function reliefAssignmentOtherTeacher(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'calendar_id' => 'required',
            'relief_assignment_teacher_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $getData = $conn->table('calendors as cl')
                ->select(
                    'cl.id',
                    'cl.class_id',
                    'cl.section_id',
                    'cl.subject_id',
                    'cl.sem_id',
                    'cl.session_id',
                    'cl.start',
                    'cl.end',
                    'cl.time_table_id'
                )
                ->where([
                    ['cl.id', '=', $request->calendar_id]
                ])
                ->first();
            // dd($getData->start);
            // dd($getData->end);
            $start_date = date('Y-m-d H:i:s', strtotime($getData->start));
            // $start_date = date('Y-m-d H:i:s', strtotime('2022-09-02 09:00:00'));

            $end_date = date('Y-m-d H:i:s', strtotime($getData->end) - 1);
            // $end_date = date('Y-m-d H:i:s', strtotime('2022-09-02 09:00:00'));
            // dd($start_date);
            $checkStart = $conn->table('calendors as cl')
                ->select(
                    'cl.teacher_id'
                )
                ->where('cl.start', '<=', $start_date)
                ->where('cl.end', '>=', $start_date)
                ->where('cl.teacher_id', '=', $request->relief_assignment_teacher_id)
                ->where('cl.teacher_id', '!=', '')
                ->whereNotNull('cl.relief_assignment_id')
                ->get()->count();
            $checkEnd = $conn->table('calendors as cl')
                ->select(
                    'cl.teacher_id'
                )
                ->where('cl.start', '<=', $end_date)
                ->where('cl.end', '>=', $end_date)
                ->where('cl.teacher_id', '=', $request->relief_assignment_teacher_id)
                ->where('cl.teacher_id', '!=', '')
                ->whereNotNull('cl.relief_assignment_id')
                ->get()->count();
            if (($checkStart > 0) || ($checkEnd > 0)) {
                return $this->send422Error('There are already staff assigned to this time slot', ['error' => 'There are already staff assigned to this time slot']);
            } else {
                // check exist name
                $alreadyAssign = $conn->table('calendors')
                    ->select('id', 'start', 'end')
                    ->where([
                        ['class_id', '=', $getData->class_id],
                        ['section_id', '=', $getData->section_id],
                        ['subject_id', '=', $getData->subject_id],
                        ['sem_id', '=', $getData->sem_id],
                        ['session_id', '=', $getData->session_id],
                        ['start', '=', $getData->start],
                        ['time_table_id', '=', $getData->time_table_id],
                        ['end', '=', $getData->end],
                    ])
                    ->whereNotNull('relief_assignment_id')
                    ->first();
                if (isset($alreadyAssign->id)) {
                    $updata =  [
                        "teacher_id" => $request->relief_assignment_teacher_id,
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                    $query = $conn->table('calendors')->where('id', $alreadyAssign->id)->update($updata);
                    // send leave notifications
                    $user = User::where('user_id', $request->relief_assignment_teacher_id)->where([
                        ['branch_id', '=', $request->branch_id]
                    ])->where(function ($q) {
                        $q->where('role_id', 2)
                            ->orWhere('role_id', 3)
                            ->orWhere('role_id', 4);
                    })->get();
                    $details = [
                        'branch_id' => $request->branch_id,
                        'staff_id' => $request->relief_assignment_teacher_id,
                        'calendar_id' => $alreadyAssign->id
                    ];
                    // notifications sent
                    Notification::send($user, new ReliefAssignment($details));
                } else {
                    $data =  [
                        "title" => "timetable",
                        "class_id" => $getData->class_id,
                        "section_id" => $getData->section_id,
                        "subject_id" => $getData->subject_id,
                        "sem_id" => $getData->sem_id,
                        "session_id" => $getData->session_id,
                        "start" => $getData->start,
                        "end" => $getData->end,
                        "time_table_id" => $getData->time_table_id,
                        "teacher_id" => $request->relief_assignment_teacher_id,
                        "relief_assignment_id" => $request->calendar_id,
                        'created_at' => date("Y-m-d H:i:s")
                    ];
                    $query = $conn->table('calendors')->insertGetId($data);
                    // send leave notifications
                    $user = User::where('user_id', $request->relief_assignment_teacher_id)->where([
                        ['branch_id', '=', $request->branch_id]
                    ])->where(function ($q) {
                        $q->where('role_id', 2)
                            ->orWhere('role_id', 3)
                            ->orWhere('role_id', 4);
                    })->get();
                    $details = [
                        'branch_id' => $request->branch_id,
                        'staff_id' => $request->relief_assignment_teacher_id,
                        'calendar_id' => $query
                    ];
                    // notifications sent
                    Notification::send($user, new ReliefAssignment($details));
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Relief Assignment Staff has been successfully saved');
                }
            }
        }
    }
    // public function getStaffListByTimeslot(Request $request)
    public function getStaffListByTimeslot(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'academic_session_id' => 'required',
            'start' => 'required',
            'end' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $start = date('Y-m-d H:i:s', strtotime($request['start']));
            $end = date('Y-m-d H:i:s', strtotime($request['end']));

            $leave_start = date('Y-m-d', strtotime($request['start']));
            $leave_end = date('Y-m-d', strtotime($request['end']));
            // here we get who taken leaves
            $startLeave = $conn->table('staff_leaves as lev')
                ->select(
                    'lev.staff_id as teacher_id'
                )
                ->where('lev.from_leave', '<=', $leave_start)
                ->where('lev.to_leave', '>=', $leave_start)
                ->where('lev.status', '>=', 'Approve')
                ->groupBy('lev.staff_id')
                ->get()->toArray();
            $endLeave = $conn->table('staff_leaves as lev')
                ->select(
                    'lev.staff_id as teacher_id'
                )
                ->where('lev.from_leave', '<=', $leave_end)
                ->where('lev.to_leave', '>=', $leave_end)
                ->where('lev.status', '>=', 'Approve')
                ->groupBy('lev.staff_id')
                ->get()->toArray();
            $startArray = $conn->table('calendors as cl')
                ->select(
                    'cl.teacher_id'
                )
                ->where('cl.start', '<=', $start)
                ->where('cl.end', '>=', $start)
                ->where('cl.teacher_id', '!=', '')
                ->whereNull('cl.relief_assignment_id')
                ->groupBy('cl.teacher_id')
                ->get()->toArray();
            $endArray = $conn->table('calendors as cl')
                ->select(
                    'cl.teacher_id'
                )
                ->where('cl.start', '<=', $end)
                ->where('cl.end', '>=', $end)
                ->where('cl.teacher_id', '!=', '')
                ->whereNull('cl.relief_assignment_id')
                ->groupBy('cl.teacher_id')
                ->get()->toArray();

            $result = array_merge($startLeave, $endLeave, $startArray, $endArray);
            // get all teacherid by unique
            $result_unique = array_unique($result, SORT_REGULAR);
            // here we get all that time period available teacher
            $idTeachers = array_column($result_unique, 'teacher_id');
            // here we get who is free that time period of teacher
            $all_available_staff = $conn->table('subject_assigns as sa')
                ->select(
                    'stf.id',
                    // DB::raw('CONCAT(stf.first_name, " ", stf.last_name, "(",sdept.name, ")") as teacher_name'),
                    DB::raw('CONCAT(stf.first_name, " ", stf.last_name) as teacher_name'),
                    'sdept.name as department_name'
                )
                ->join('staffs as stf', 'sa.teacher_id', '=', 'stf.id')
                ->leftJoin('staff_departments as sdept', 'stf.department_id', '=', 'sdept.id')
                ->where([
                    ['sa.type', '=', '0'],
                    ['sa.academic_session_id', '=', $request->academic_session_id],
                ])
                ->whereNotIn('sa.teacher_id', $idTeachers)
                ->groupBy('sa.teacher_id')
                ->get();
            return $all_available_staff;
            // return $this->successResponse($all_available_staff, 'Available teacher list');
        }
    }
    public function getCalendarDetailsTimetable(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'calendar_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $calendors = $conn->table('calendors as cl')
                ->select(
                    'cl.start',
                    'cl.end',
                    'c.name as class_name',
                    'sc.name as section_name',
                    'sbj.name as subject_name'
                    // DB::raw("CONCAT(stf.first_name, ' ', stf.last_name) as teacher_name")
                )
                ->join('classes as c', 'cl.class_id', '=', 'c.id')
                ->join('sections as sc', 'cl.section_id', '=', 'sc.id')
                ->join('subjects as sbj', 'cl.subject_id', '=', 'sbj.id')
                // ->join('staffs as stf', 'cl.teacher_id', '=', 'stf.id')
                ->where('cl.id', '=', $request->calendar_id)
                ->first();
            return $this->successResponse($calendors, 'Available teacher list');
        }
    }
    // soap category list
    public function soapCategoryList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $soapCategory = $conn->table('soap_category')
                ->select(
                    'id',
                    'name',
                    'soap_type_id'
                )
                ->where('soap_type_id', $request->soap_type_id)
                ->get();
            return $this->successResponse($soapCategory, 'Soap category list');
        }
    }
    // soap sub category
    public function soapSubCategoryList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'soap_category_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $soapCategory = $conn->table('soap_sub_category')
                ->select(
                    'id',
                    'name',
                    'photo'
                )
                ->where('soap_category_id', $request->soap_category_id)
                ->get();
            return $this->successResponse($soapCategory, 'Soap sub category list');
        }
    }
    // soap filter by notes
    public function soapFilterByNotes(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'soap_sub_category_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $name = $request->name;
            $soapCategory = $conn->table('soap_notes')
                ->select(
                    'id',
                    'notes'
                )
                ->where('soap_sub_category_id', $request->soap_sub_category_id)
                ->when($name, function ($query, $name) {
                    return $query->where('notes', 'like', '%' . $name . '%');
                })
                ->get();
            return $this->successResponse($soapCategory, 'Soap notes list');
        }
    }
    // soap add
    public function soapAdd(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'notes' => 'required',
            'referred_by' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $notes = $request->notes;
            if ($notes) {

                foreach ($notes as $not) {
                    // return $note;
                    foreach ($not as $note) {
                        // dd($request);
                        if (!isset($note['soap_id'])) {
                            $data = [
                                'soap_notes_id' => $note['soap_notes_id'],
                                'soap_category_id' => $note['soap_category_id'],
                                'soap_sub_category_id' => $note['soap_sub_category_id'],
                                'referred_by' => $request->referred_by,
                                'student_id' => $request->student_id,
                                'date' => date('Y-m-d'),
                                'created_at' => date("Y-m-d H:i:s")
                            ];
                            // insert data
                            $query = $conn->table('soap')->insert($data);
                            $type = "Added";
                            $soap_text = $conn->table('soap_notes')->where('id', $note['soap_notes_id'])->first();
                            // dd($soap_text);
                            // return $soap_text;
                            $this->addSoapLog($request, $type, $soap_text);
                        } else {
                            $query = 1;
                        }
                    }
                }
            }
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Added successfully');
            }
        }
    }
    // soap list
    public function getSoapList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            $student_id = $request->student_id;
            $soapDetails = $conn->table('soap as sp')
                ->select(
                    'sp.id',
                    'sn.notes as soap_notes',
                    'sp.date',
                    'sp.soap_category_id',
                    'sp.soap_sub_category_id',
                    DB::raw('CONCAT(s.first_name, " ", s.last_name) as referred_by'),
                )
                ->join('soap_notes as sn', 'sp.soap_notes_id', '=', 'sn.id')
                ->join('staffs as s', 'sp.referred_by', '=', 's.id')
                ->get();
            return $this->successResponse($soapDetails, 'Soap record fetch successfully');
        }
    }
    // get soap row details
    public function getSoapDetails(Request $request)
    {

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
            $leaveTypeDetails = $conn->table('soap')->where('id', $id)->first();
            return $this->successResponse($leaveTypeDetails, 'Soap row fetch successfully');
        }
    }
    // update soap
    public function updateSoap(Request $request)
    {
        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'id' => 'required',
            'notes' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);

            // update data
            $updateData = [
                'notes' => $request->notes,
                'updated_at' => date("Y-m-d H:i:s")
            ];
            $query = $conn->table('soap')->where('id', $id)->update($updateData);
            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Soap have Been updated');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    // delete soap
    public function deleteSoap(Request $request)
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
            $note = $conn->table('soap')->where('id', $id)->first();
            $soap_text = $conn->table('soap_notes')->where('id', $note->soap_notes_id)->first();
            $type = "Deleted";

            // dd($soap_text);
            $this->addSoapLog($request, $type, $soap_text);
            $query = $conn->table('soap')->where('id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Soap have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    // copy teacher allocations
    public function acdemicCopyAssignTeacher(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'academic_session_id' => 'required',
            'copy_academic_session_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            // get acdemic data
            $getAcademicData = $createConnection->table('teacher_allocations')
                ->where(
                    [
                        ['academic_session_id', $request->academic_session_id]
                    ]
                )
                ->get();
            if (count($getAcademicData) > 0) {
                foreach ($getAcademicData as $value) {
                    $old = $createConnection->table('teacher_allocations')
                        ->where(
                            [
                                ['class_id', $value->class_id],
                                ['section_id', $value->section_id],
                                ['teacher_id', $value->teacher_id],
                                ['type', $value->type],
                                ['academic_session_id', $request->copy_academic_session_id]
                            ]
                        )
                        ->first();
                    if (isset($old->id)) {
                        $arrayData = [
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $query = $createConnection->table('teacher_allocations')->where('id', $old->id)->update($arrayData);
                    } else {
                        $arrayData = [
                            'class_id' => $value->class_id,
                            'section_id' => $value->section_id,
                            'teacher_id' => $value->teacher_id,
                            'type' => $value->type,
                            'academic_session_id' => $request->copy_academic_session_id,
                            'created_at' => date("Y-m-d H:i:s")
                        ];
                        $query = $createConnection->table('teacher_allocations')->insert($arrayData);
                    }
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Teacher Allocation has been successfully saved');
                }
            } else {
                return $this->send500Error('No data available', ['error' => 'No data available']);
            }
        }
    }
    // copy class assign 
    public function copyClassAssign(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'academic_session_id' => 'required',
            'copy_academic_session_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            // get acdemic data
            $getAcademicData = $createConnection->table('subject_assigns as sa')
                ->where([
                    ['sa.type', '=', '0'],
                    ['sa.academic_session_id', $request->academic_session_id]
                ])
                ->get();
            if (count($getAcademicData) > 0) {
                foreach ($getAcademicData as $value) {
                    $old = $createConnection->table('subject_assigns')
                        ->where(
                            [
                                ['class_id', $value->class_id],
                                ['section_id', $value->section_id],
                                ['subject_id', $value->subject_id],
                                ['type', $value->type],
                                ['academic_session_id', $request->copy_academic_session_id]
                            ]
                        )
                        ->first();
                    if (isset($old->id)) {
                        $arrayData = [
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $query = $createConnection->table('subject_assigns')->where('id', $old->id)->update($arrayData);
                    } else {
                        $arrayData = [
                            'class_id' => $value->class_id,
                            'section_id' => $value->section_id,
                            'subject_id' => $value->subject_id,
                            'teacher_id' => $value->teacher_id,
                            'type' => $value->type,
                            'academic_session_id' => $request->copy_academic_session_id,
                            'created_at' => date("Y-m-d H:i:s")
                        ];
                        $query = $createConnection->table('subject_assigns')->insert($arrayData);
                    }
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Class assign has been successfully saved');
                }
            } else {
                return $this->send500Error('No data available', ['error' => 'No data available']);
            }
        }
    }
    // copy subject teacher assign 
    public function copySubjectTeacherAssign(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'academic_session_id' => 'required',
            'copy_academic_session_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            // get acdemic data
            $getAcademicData = $createConnection->table('subject_assigns as sa')
                ->where([
                    ['sa.teacher_id', '!=', '0'],
                    ['sa.academic_session_id', $request->academic_session_id]
                ])
                ->get();
            if (count($getAcademicData) > 0) {
                foreach ($getAcademicData as $value) {
                    $old = $createConnection->table('subject_assigns')
                        ->where(
                            [
                                ['class_id', $value->class_id],
                                ['section_id', $value->section_id],
                                ['subject_id', $value->subject_id],
                                ['teacher_id', $value->teacher_id],
                                ['type', $value->type],
                                ['academic_session_id', $request->copy_academic_session_id]
                            ]
                        )
                        ->first();
                    if (isset($old->id)) {
                        $arrayData = [
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $query = $createConnection->table('subject_assigns')->where('id', $old->id)->update($arrayData);
                    } else {
                        $arrayData = [
                            'class_id' => $value->class_id,
                            'section_id' => $value->section_id,
                            'subject_id' => $value->subject_id,
                            'teacher_id' => $value->teacher_id,
                            'type' => $value->type,
                            'academic_session_id' => $request->copy_academic_session_id,
                            'created_at' => date("Y-m-d H:i:s")
                        ];
                        $query = $createConnection->table('subject_assigns')->insert($arrayData);
                    }
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Teacher assign has been successfully saved');
                }
            } else {
                return $this->send500Error('No data available', ['error' => 'No data available']);
            }
        }
    }
    // exam master exam setup copy
    public function copyExamSetup(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'academic_session_id' => 'required',
            'copy_academic_session_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            // get acdemic data
            $getAcademicData = $createConnection->table('exam')
                ->where([
                    ['academic_session_id', $request->academic_session_id]
                ])
                ->get();
            if (count($getAcademicData) > 0) {
                foreach ($getAcademicData as $value) {
                    $old = $createConnection->table('exam')
                        ->where(
                            [
                                ['name', $value->name],
                                ['term_id', $value->term_id],
                                ['academic_session_id', $request->copy_academic_session_id]
                            ]
                        )
                        ->first();
                    if (isset($old->id)) {
                        $arrayData = [
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $query = $createConnection->table('exam')->where('id', $old->id)->update($arrayData);
                    } else {
                        $arrayData = [
                            'name' => $value->name,
                            'term_id' => $value->term_id,
                            'remarks' => $value->remarks,
                            'academic_session_id' => $request->copy_academic_session_id,
                            'created_at' => date("Y-m-d H:i:s")
                        ];
                        $query = $createConnection->table('exam')->insert($arrayData);
                    }
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Exam has been successfully saved');
                }
            } else {
                return $this->send500Error('No data available', ['error' => 'No data available']);
            }
        }
    }
    // exam master exam paper copy
    public function copyExamPaper(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'academic_session_id' => 'required',
            'copy_academic_session_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            // get acdemic data
            $getAcademicData = $createConnection->table('exam_papers')
                ->where([
                    ['academic_session_id', $request->academic_session_id]
                ])
                ->get();
            if (count($getAcademicData) > 0) {
                foreach ($getAcademicData as $value) {
                    $old = $createConnection->table('exam_papers')
                        ->where(
                            [
                                ['class_id', $value->class_id],
                                ['subject_id', $value->subject_id],
                                ['paper_name', $value->paper_name],
                                ['paper_type', $value->paper_type],
                                ['grade_category', $value->grade_category],
                                ['subject_weightage', $value->subject_weightage],
                                ['notes', $value->notes],
                                ['academic_session_id', $request->copy_academic_session_id]
                            ]
                        )
                        ->first();
                    if (isset($old->id)) {
                        $arrayData = [
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $query = $createConnection->table('exam_papers')->where('id', $old->id)->update($arrayData);
                    } else {
                        $arrayData = [
                            'class_id' => $value->class_id,
                            'subject_id' => $value->subject_id,
                            'paper_name' => $value->paper_name,
                            'paper_type' => $value->paper_type,
                            'grade_category' => $value->grade_category,
                            'subject_weightage' => $value->subject_weightage,
                            'notes' => $value->notes,
                            'academic_session_id' => $request->copy_academic_session_id,
                            'created_at' => date("Y-m-d H:i:s")
                        ];
                        $query = $createConnection->table('exam_papers')->insert($arrayData);
                    }
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Exam paper has been successfully saved');
                }
            } else {
                return $this->send500Error('No data available', ['error' => 'No data available']);
            }
        }
    }
    // password Expired Link
    public function passwordExpiredLink(Request $request)
    {
        //Retrieve the user from the database
        $url = $request->url;
        $dateS = Carbon::now()->subDays(75)->format('Y-m-d');
        $users = DB::table('users')
            ->select('email')
            ->where(DB::raw('date(password_changed_at)'), '<=', $dateS)
            ->where('role_id', '2')
            ->where('branch_id', $request->branch_id)
            ->whereNotNull('password_changed_at')
            ->get()->toArray();
        if (!empty($users)) {
            foreach ($users as $details) {
                $email = $details->email;
                // random string
                $token = Str::random(64);
                DB::table('password_resets')->insert([
                    'email' => $email,
                    'token' => $token,
                    'password_reminder' => "1",
                    'created_at' => Carbon::now()
                ]);
                $this->sendResetEmail($email, $token, $url);
            }
        }
        return $this->successResponse([], 'A reset link has been sent to your email address.');
    }
    private function sendResetEmail($email, $token, $url)
    {
        //Retrieve the user from the database
        $user = DB::table('users')->where('email', $email)->select('name', 'email')->first();
        //Generate, the password reset link. The token generated is embedded in the link
        $link = $url . '/password/expired/reset' . '/' . $token;
        if ($email) {
            $data = array('link' => $link, 'name' => $user->name);
            Mail::send('auth.reset_expire_pass_mail', $data, function ($message) use ($email) {
                $message->to($email, 'members')->subject('Resetting Expired Password');
                $message->from(env('MAIL_FROM_ADDRESS'), 'Password Reset');
            });
            return $user;
        } else {
            return false;
        }
    }

    // addSoapCategory
    public function addSoapCategory(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'soap_type_id' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // insert data
            $query = $conn->table('soap_category')->insert([
                'name' => $request->name,
                'soap_type_id' => $request->soap_type_id,
                'created_at' => date("Y-m-d H:i:s")
            ]);
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Category has been successfully saved');
            }
        }
    }
    // getSoapCategoryList
    public function getSoapCategoryList(Request $request)
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
            // get data
            $SoapCategoryDetails = $conn->table('soap_category')->get();
            return $this->successResponse($SoapCategoryDetails, 'Category record fetch successfully');
        }
    }
    // get SoapCategory row details
    public function getSoapCategoryDetails(Request $request)
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
            $SoapCategoryDetails = $conn->table('soap_category')->where('id', $id)->first();
            return $this->successResponse($SoapCategoryDetails, 'Category row fetch successfully');
        }
    }
    // update SoapCategory
    public function updateSoapCategory(Request $request)
    {
        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'soap_type_id' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // update data
            $query = $conn->table('soap_category')->where('id', $id)->update([
                'name' => $request->name,
                'soap_type_id' => $request->soap_type_id,
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Category Details have Been updated');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    // delete SoapCategory
    public function deleteSoapCategory(Request $request)
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
            $query = $conn->table('soap_category')->where('id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Category have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }

    // addSoapSubCategory
    public function addSoapSubCategory(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'soap_category_id' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // insert data
            if ($request->photo) {
                $path = '/public/' . $request->branch_id . '/soap/images/';

                $fileName = 'SCIMG_' . date('Ymd') . uniqid() . '.' . $request->file_extension;
                $base64 = base64_decode($request->photo);
                File::ensureDirectoryExists(base_path() . $path);
                $file = base_path() . $path . $fileName;
                $suc = file_put_contents($file, $base64);
            } else {
                $fileName = "";
            }
            $query = $conn->table('soap_sub_category')->insert([
                'name' => $request->name,
                'soap_category_id' => $request->soap_category_id,
                'photo' => $fileName,
                'created_at' => date("Y-m-d H:i:s")
            ]);
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Sub Category has been successfully saved');
            }
        }
    }
    // getSoapSubCategoryList
    public function getSoapSubCategoryList(Request $request)
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
            // get data
            $SoapSubCategoryDetails = $conn->table('soap_sub_category as sc')->select('sc.id', 'sc.name', 'c.name as soap_category_id')
                ->leftJoin('soap_category as c', 'sc.soap_category_id', '=', 'c.id')->get();
            return $this->successResponse($SoapSubCategoryDetails, 'Sub Category record fetch successfully');
        }
    }
    // get SoapSubCategory row details
    public function getSoapSubCategoryDetails(Request $request)
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
            $SoapSubCategoryDetails = $conn->table('soap_sub_category as sc')->select('sc.*', 'c.soap_type_id')
                ->leftJoin('soap_category as c', 'sc.soap_category_id', '=', 'c.id')
                ->where('sc.id', $id)->first();
            return $this->successResponse($SoapSubCategoryDetails, 'Sub Category row fetch successfully');
        }
    }
    // update SoapSubCategory
    public function updateSoapSubCategory(Request $request)
    {
        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'soap_category_id' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            if ($request->photo) {
                $path = '/public/' . $request->branch_id . '/soap/images/';
                $oldPicture = $conn->table('soap_sub_category')->where('id', $id)->first();

                // return $oldPicture->photo;
                if ($oldPicture->photo != '') {
                    if (\File::exists(base_path($path . $oldPicture->photo))) {
                        \File::delete(base_path($path . $oldPicture->photo));
                    }
                }
                $fileName = 'SCIMG_' . date('Ymd') . uniqid() . '.' . $request->file_extension;
                $base64 = base64_decode($request->photo);
                $file = base_path() . $path . $fileName;
                $suc = file_put_contents($file, $base64);
            } else {
                if ($request->old_photo) {
                    $fileName = $request->old_photo;
                } else {
                    $fileName = "";
                }
            }
            // update data
            $query = $conn->table('soap_sub_category')->where('id', $id)->update([
                'name' => $request->name,
                'soap_category_id' => $request->soap_category_id,
                'photo' => $fileName,
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Sub Category Details have Been updated');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    // delete SoapSubCategory
    public function deleteSoapSubCategory(Request $request)
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
            $query = $conn->table('soap_sub_category')->where('id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Sub Category have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }


    // addSoapNotes
    public function addSoapNotes(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'notes' => 'required',
            'soap_category_id' => 'required',
            'soap_sub_category_id' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // insert data
            $query = $conn->table('soap_notes')->insert([
                'notes' => $request->notes,
                'soap_category_id' => $request->soap_category_id,
                'soap_sub_category_id' => $request->soap_sub_category_id,
                'created_at' => date("Y-m-d H:i:s")
            ]);
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Notes has been successfully saved');
            }
        }
    }
    // getSoapNotesList
    public function getSoapNotesList(Request $request)
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
            // get data
            $SoapNotesDetails = $conn->table('soap_notes as n')->select('n.id', 'n.notes', 'c.name as soap_category_id', 'sc.name as soap_sub_category_id')
                ->leftJoin('soap_category as c', 'n.soap_category_id', '=', 'c.id')
                ->leftJoin('soap_sub_category as sc', 'n.soap_sub_category_id', '=', 'sc.id')
                ->get();
            return $this->successResponse($SoapNotesDetails, 'Notes record fetch successfully');
        }
    }
    // get SoapNotes row details
    public function getSoapNotesDetails(Request $request)
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
            $SoapNotesDetails = $conn->table('soap_notes as n')->select('n.*', 'c.soap_type_id')
                ->leftJoin('soap_category as c', 'n.soap_category_id', '=', 'c.id')->where('n.id', $id)->first();
            return $this->successResponse($SoapNotesDetails, 'Notes row fetch successfully');
        }
    }
    // update SoapNotes
    public function updateSoapNotes(Request $request)
    {
        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'notes' => 'required',
            'soap_category_id' => 'required',
            'soap_sub_category_id' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // update data
            $query = $conn->table('soap_notes')->where('id', $id)->update([
                'notes' => $request->notes,
                'soap_category_id' => $request->soap_category_id,
                'soap_sub_category_id' => $request->soap_sub_category_id,
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Notes Details have Been updated');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    // delete SoapNotes
    public function deleteSoapNotes(Request $request)
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
            $query = $conn->table('soap_notes')->where('id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Notes have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }

    // addSoapSubject
    public function addSoapSubject(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'title' => 'required',
            'header' => 'required',
            'body' => 'required',
            'soap_type_id' => 'required',
            'student_id' => 'required',
            'referred_by' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // insert data
            $query = $conn->table('soap_subject')->insert([
                'title' => $request->title,
                'header' => $request->header,
                'body' => $request->body,
                'soap_type_id' => $request->soap_type_id,
                'student_id' => $request->student_id,
                'referred_by' => $request->referred_by,
                'date' => date('Y-m-d'),
                'created_at' => date("Y-m-d H:i:s")
            ]);
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Subject has been successfully saved');
            }
        }
    }
    // getSoapSubjectList
    public function getSoapSubjectList(Request $request)
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
            // get data
            $SoapSubjectDetails = $conn->table('soap_subject')->select('*', DB::raw('CONCAT(s.first_name, " ", s.last_name) as referred_by'))
                ->join('staffs as s', 'soap_subject.referred_by', '=', 's.id')->get();
            return $this->successResponse($SoapSubjectDetails, 'Subject record fetch successfully');
        }
    }
    // get SoapSubject row details
    public function getSoapSubjectDetails(Request $request)
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
            $SoapSubjectDetails = $conn->table('soap_subject')->where('id', $id)->first();
            return $this->successResponse($SoapSubjectDetails, 'Subject row fetch successfully');
        }
    }
    // update SoapSubject
    public function updateSoapSubject(Request $request)
    {
        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'title' => 'required',
            'header' => 'required',
            'body' => 'required',
            'soap_type_id' => 'required',
            'student_id' => 'required',
            'referred_by' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // update data
            $query = $conn->table('soap_subject')->where('id', $id)->update([
                'title' => $request->title,
                'header' => $request->header,
                'body' => $request->body,
                'soap_type_id' => $request->soap_type_id,
                'student_id' => $request->student_id,
                'referred_by' => $request->referred_by,
                'date' => date('Y-m-d'),
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Subject Details have Been updated');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    // delete SoapSubject
    public function deleteSoapSubject(Request $request)
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
            $query = $conn->table('soap_subject')->where('id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Subject have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    public function getExamPaperResults(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'class_id' => 'required',
            'section_id' => 'required',
            'subject_id' => 'required',
            'exam_id' => 'required',
            'semester_id' => 'required',
            'session_id' => 'required',
            'academic_session_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $exam_id = $request->exam_id;
            $class_id = $request->class_id;
            $section_id = $request->section_id;
            $subject_id = $request->subject_id;
            $semester_id = $request->semester_id;
            $session_id = $request->session_id;
            $academic_session_id = $request->academic_session_id;
            $Connection = $this->createNewConnection($request->branch_id);
            $getSubjectMarks = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    DB::raw('CONCAT(st.first_name, " ", st.last_name) as name'),
                    DB::raw("group_concat(sa.score ORDER BY sa.paper_id ASC) as score"),
                    DB::raw("group_concat(exp.subject_weightage ORDER BY sa.paper_id ASC) as subject_weightage"),
                    DB::raw("group_concat(exp.paper_name ORDER BY sa.paper_id ASC) as paper_name"),
                    DB::raw("group_concat(sa.ranking ORDER BY sa.paper_id ASC) as ranking"),
                    DB::raw('SUM(exp.subject_weightage) as total_subject_weightage'),
                    'sa.pass_fail',
                    'sa.status',
                    'sa.paper_id'
                )
                ->join('students as st', 'st.id', '=', 'en.student_id')
                ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester_id, $session_id, $academic_session_id) {
                    $q->on('sa.student_id', '=', 'st.id')
                        ->on('sa.exam_id', '=', DB::raw("'$exam_id'"))
                        ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                        ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                        ->on('sa.semester_id', '=', DB::raw("'$semester_id'"))
                        ->on('sa.session_id', '=', DB::raw("'$session_id'"))
                        ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->join('exam_papers as exp', 'exp.id', '=', 'sa.paper_id')
                ->where([
                    ['en.class_id', '=', $request->class_id],
                    ['en.section_id', '=', $request->section_id],
                    ['en.semester_id', '=', $request->semester_id],
                    ['en.session_id', '=', $request->session_id],
                    ['en.academic_session_id', '=', $academic_session_id]
                ])
                ->groupBy('sa.student_id')
                ->get();
            $getPaperNames = $Connection->table('enrolls as en')
                ->select(
                    'exp.grade_category',
                    'exp.paper_name'
                )
                // ->join('students as st', 'st.id', '=', 'en.student_id')
                ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester_id, $session_id, $academic_session_id) {
                    // $q->on('sa.student_id', '=', 'st.id')
                    $q->on('sa.exam_id', '=', DB::raw("'$exam_id'"))
                        ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                        ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                        ->on('sa.semester_id', '=', DB::raw("'$semester_id'"))
                        ->on('sa.session_id', '=', DB::raw("'$session_id'"))
                        ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->join('exam_papers as exp', 'exp.id', '=', 'sa.paper_id')
                ->where([
                    ['en.class_id', '=', $request->class_id],
                    ['en.section_id', '=', $request->section_id],
                    ['en.semester_id', '=', $request->semester_id],
                    ['en.session_id', '=', $request->session_id],
                    ['en.academic_session_id', '=', $academic_session_id]
                ])
                ->groupBy('exp.id')
                ->get();
            $grade_category = isset($getPaperNames[0]->grade_category) ? $getPaperNames[0]->grade_category : 0;
            $total_marks = [];
            if (count($getSubjectMarks) > 0) {
                foreach ($getSubjectMarks as $key => $value) {
                    $object = new \stdClass();
                    $total_sub_weightage = explode(',', $value->subject_weightage);
                    $total_score = explode(',', $value->score);
                    $total_subject_weightage = $value->total_subject_weightage;
                    $paperMark = [];
                    $marks = 0;
                    // foreach for total no of students
                    for ($i = 0; $i < count($total_score); $i++) {
                        $sub_weightage = isset($total_sub_weightage[$i]) ? (int) $total_sub_weightage[$i] : 0;
                        $score = isset($total_sub_weightage[$i]) ? (int) $total_score[$i] : 0;
                        $weightage = ($sub_weightage / $total_subject_weightage);
                        $paperMark[$i] = ($weightage * $score);
                        $marks += ($weightage * $score);
                    }
                    $object->papers = $paperMark;
                    // grade marks
                    $grade = $Connection->table('grade_marks')
                        ->select('grade', 'status')
                        ->where([
                            ['min_mark', '<=', $marks],
                            ['max_mark', '>=', $marks],
                            ['grade_category', '=', $grade_category]
                        ])
                        ->first();
                    $object->name = $value->name;
                    $object->toal_marks = $marks;
                    $object->paper_name = $value->paper_name;
                    $object->grade = isset($grade->grade) ? $grade->grade : "-";
                    $object->status = isset($grade->status) ? $grade->status : "-";
                    array_push($total_marks, $object);
                }
            }
            $data = [
                'all_paper' => $getPaperNames,
                'get_subject_paper_marks' => $total_marks
            ];
            return $this->successResponse($data, 'paper wise record fetch successfully');
        }
    }
    public function getExamTimetableDown(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'exam_id' => 'required',
            'session_id' => 'required',
            'semester_id' => 'required',
            'class_id' => 'required',
            'section_id' => 'required',
            'academic_session_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $con = $this->createNewConnection($request->branch_id);
            // get data
            $exam_id = $request->exam_id;
            $session_id = $request->session_id;
            $semester_id = $request->semester_id;
            $academic_session_id = $request->academic_session_id;
            $details['exam'] = $con->table('subject_assigns as sa')
                ->select(
                    'sbj.name as subject_name',
                    'ep.paper_name as paper_name',
                    'ttex.exam_date',
                    'ttex.time_start',
                    'ttex.time_end',
                    'eh.hall_no',
                    'ttex.distributor'
                )
                ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                ->join('classes as cl', 'sa.class_id', '=', 'cl.id')
                ->join('sections as sec', 'sa.section_id', '=', 'sec.id')
                ->join('exam_papers as ep', function ($join) use ($academic_session_id) {
                    $join->on('sa.class_id', '=', 'ep.class_id')
                        ->on('sa.subject_id', '=', 'ep.subject_id')
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->leftJoin('timetable_exam as ttex', function ($join) use ($exam_id, $semester_id, $session_id, $academic_session_id) {
                    $join->on('sa.class_id', '=', 'ttex.class_id')
                        ->on('sa.section_id', '=', 'ttex.section_id')
                        ->on('sa.subject_id', '=', 'ttex.subject_id')
                        ->on('ttex.semester_id', '=', DB::raw("'$semester_id'"))
                        ->on('ttex.session_id', '=', DB::raw("'$session_id'"))
                        ->on('ttex.paper_id', '=', 'ep.id')
                        ->where('ttex.exam_id', $exam_id)
                        ->where('ttex.academic_session_id', $academic_session_id);
                })
                ->leftJoin('exam as ex', 'ttex.exam_id', '=', 'ex.id')
                ->leftJoin('exam_hall as eh', 'ttex.hall_id', '=', 'eh.id')
                ->where([
                    ['sa.class_id', $request->class_id],
                    ['sa.section_id', $request->section_id],
                    ['sa.type', '=', '0'],
                    ['sa.academic_session_id', '=', $academic_session_id],
                    ['sbj.exam_exclude', '=', '0']
                ])
                ->orderBy('sbj.id', 'asc')
                ->orderBy('ttex.exam_date', 'desc')
                ->orderBy('sbj.name', 'asc')
                ->get();
            // return $details;
            $exam_name = $con->table('exam')->select('name')->where('id', $exam_id)->first();
            $class_name = $con->table('classes')->select('name')->where('id', $request->class_id)->first();
            $section_name = $con->table('sections')->select('name')->where('id', $request->class_id)->first();
            $details['details']['exam_name'] = $exam_name->name;
            $details['details']['class_name'] = $class_name->name;
            $details['details']['section_name'] = $section_name->name;
            return $this->successResponse($details, 'Exam Timetable record fetch successfully');
        }
    } // getoldSoapStudentList
    public function getOldSoapStudentList(Request $request)
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
            // get data
            $class_id = $request->class_id;
            $session_id = $request->session_id;
            $section_id = $request->section_id;

            $SoapSubjectDetails = $conn->table('soap as sa')
                ->select('s.id', 's.photo', DB::raw('CONCAT(s.first_name, " ", s.last_name) as name'), 'sections.name as section_name', 'classes.name as class_name', 's.email')
                ->leftJoin('students as s', 'sa.student_id', '=', 's.id')
                ->leftJoin('enrolls as e', 's.id', '=', 'e.student_id')
                ->leftJoin('sections', 'e.section_id', '=', 'sections.id')
                ->leftJoin('classes', 'e.class_id', '=', 'classes.id')
                ->where('sa.student_id', '!=', '0')
                ->when($class_id, function ($query, $class_id) {
                    return $query->where('e.class_id', $class_id);
                })
                ->when($session_id, function ($query, $session_id) {
                    return $query->where('e.session_id', $session_id);
                })
                ->when($section_id, function ($query, $section_id) {
                    return $query->where('e.section_id', $section_id);
                })
                ->where('e.academic_session_id', '=', $request->academic_session_id)
                ->where('e.active_status', '=', "0")
                ->groupBy('s.id')
                ->get();
            return $this->successResponse($SoapSubjectDetails, 'Student record fetch successfully');
        }
    }
    // student Soap List 
    public function studentSoapList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            $student_id = $request->student_id;
            $soapDetails['soap'] = $conn->table('soap as sp')
                ->select(
                    'sp.id',
                    'sn.notes as soap_notes',
                    'sp.date',
                    'sp.soap_category_id',
                    'sp.soap_sub_category_id',
                    DB::raw('CONCAT(s.first_name, " ", s.last_name) as referred_by')
                )
                ->join('soap_notes as sn', 'sp.soap_notes_id', '=', 'sn.id')
                ->join('staffs as s', 'sp.referred_by', '=', 's.id')
                ->where('sp.student_id', $student_id)
                ->get();
            $soapDetails['subject'] =  $conn->table('soap_subject')->select('soap_subject.*', DB::raw('CONCAT(s.first_name, " ", s.last_name) as referred_by'))->join('staffs as s', 'soap_subject.referred_by', '=', 's.id')->where('student_id', $student_id)->get();
            return $this->successResponse($soapDetails, 'Soap & Subject record fetch successfully');
        }
    }

    // getSoapStudentList
    public function getSoapStudentList(Request $request)
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
            // get data
            //         $unassign = Device::select('devices.id','devices.deviceuid','devices.devicecode')

            //    ->whereNotIn('id', function($q){
            //     $q->select('deviceid')->from('deviceassignments');
            //     })
            // ->where('devices.status',1)->get();
            $class_id = $request->class_id;
            $session_id = $request->session_id;
            $section_id = $request->section_id;

            $SoapSubjectDetails = $conn->table('students as s')->select('s.id', 's.photo', 'e.section_id', 'e.class_id', DB::raw('CONCAT(s.first_name, " ", s.last_name) as name'), 'sections.name as section_name', 'classes.name as class_name', 's.email')
                ->leftJoin('enrolls as e', 's.id', '=', 'e.student_id')
                ->leftJoin('sections', 'e.section_id', '=', 'sections.id')
                ->leftJoin('classes', 'e.class_id', '=', 'classes.id')
                ->whereNotIn('s.id', $conn->table('soap')->pluck('student_id'))
                ->when($class_id, function ($query, $class_id) {
                    return $query->where('e.class_id', $class_id);
                })
                ->when($session_id, function ($query, $session_id) {
                    return $query->where('e.session_id', $session_id);
                })
                ->when($section_id, function ($query, $section_id) {
                    return $query->where('e.section_id', $section_id);
                })
                ->where('e.academic_session_id', '=', $request->academic_session_id)
                ->where('e.active_status', '=', "0")
                ->get();
            return $this->successResponse($SoapSubjectDetails, 'Student record fetch successfully');
        }
    }


    // getSoapLogList
    public function getSoapLogList(Request $request)
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
            // get data
            $SoapLogDetails = $conn->table('soap_logs')->select('soap_logs.*', DB::raw('DATE_FORMAT(soap_logs.created_at,"%d-%m-%Y") as date'), DB::raw('CONCAT(s.first_name, " ", s.last_name) as referred_by'))
                ->join('staffs as s', 'soap_logs.staff_id', '=', 's.id')
                ->where('soap_logs.student_id', $request->student_id)->orderBy('created_at', 'DESC')->get();
            return $this->successResponse($SoapLogDetails, 'Log record fetch successfully');
        }
    }



    // add Soap Log
    public function addSoapLog($request, $type, $note)
    {
        // dd($note);
        $conn = $this->createNewConnection($request->branch_id);

        $conn->table('soap_logs')->insert([
            'student_id' => $request->student_id,
            'staff_id' => $request->referred_by,
            'soap_id' => $note->id,
            'soap_text' => $note->notes,
            'soap_type' => $request->soap_type_id,
            'type' => $type,
            'created_at' => date("Y-m-d H:i:s")
        ]);
    }
    public function feesYearlyAdd(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'fees_type' => 'required',
            'student_id' => 'required',
            'date' => 'required',
            'payment_status' => 'required',
            'collect_by' => 'required',
            'branch_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $staffConn = $this->createNewConnection($request->branch_id);
            // insert data
            $query = $staffConn->table('fees_yearly')->insert([
                'fees_type' => $request->fees_type,
                'student_id' => $request->student_id,
                'date' => $request->date,
                'payment_status' => $request->payment_status,
                'collect_by' => $request->collect_by,
                'created_at' => date("Y-m-d H:i:s")
            ]);
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Yearly fees has been successfully saved');
            }
        }
    }
    // get Student Details
    public function getStudentDetails(Request $request)
    {
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
            $createConnection = $this->createNewConnection($request->branch_id);
            $studentData = $createConnection->table('enrolls as en')
                ->select(
                    'st.id',
                    DB::raw('CONCAT(st.first_name, " ", st.last_name) as name')
                )
                ->join('students as st', 'st.id', '=', 'en.student_id')
                ->where([
                    ['en.class_id', '=', $request->class_id],
                    ['en.section_id', '=', $request->section_id],
                    ['en.active_status', '=', '0'],
                    ['en.academic_session_id', '=', $request->academic_session_id]
                ])
                ->get();
            return $this->successResponse($studentData, 'students data fetch successfully');
        }
    }

    // addPaymentMode
    public function addPaymentMode(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($conn->table('payment_mode')->where('name', '=', $request->name)->count() > 0) {
                return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            } else {
                // insert data
                $query = $conn->table('payment_mode')->insert([
                    'name' => $request->name,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Payment Mode has been successfully saved');
                }
            }
        }
    }
    // getPaymentModeList
    public function getPaymentModeList(Request $request)
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
            // get data
            $PaymentModeDetails = $conn->table('payment_mode')->get();
            return $this->successResponse($PaymentModeDetails, 'Payment Mode record fetch successfully');
        }
    }
    // get PaymentMode row details
    public function getPaymentModeDetails(Request $request)
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
            $PaymentModeDetails = $conn->table('payment_mode')->where('id', $id)->first();
            return $this->successResponse($PaymentModeDetails, 'Payment Mode row fetch successfully');
        }
    }
    // update PaymentMode
    public function updatePaymentMode(Request $request)
    {
        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($conn->table('payment_mode')->where([['name', '=', $request->name], ['id', '!=', $id]])->count() > 0) {
                return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            } else {
                // update data
                $query = $conn->table('payment_mode')->where('id', $id)->update([
                    'name' => $request->name,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Payment Mode Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        }
    }
    // delete PaymentMode
    public function deletePaymentMode(Request $request)
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
            $query = $conn->table('payment_mode')->where('id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Payment Mode have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }


    // addPaymentStatus
    public function addPaymentStatus(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($conn->table('payment_status')->where('name', '=', $request->name)->count() > 0) {
                return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            } else {
                // insert data
                $query = $conn->table('payment_status')->insert([
                    'name' => $request->name,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Payment Status has been successfully saved');
                }
            }
        }
    }
    // getPaymentStatusList
    public function getPaymentStatusList(Request $request)
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
            // get data
            $PaymentStatusDetails = $conn->table('payment_status')->get();
            return $this->successResponse($PaymentStatusDetails, 'Payment Status record fetch successfully');
        }
    }
    // get PaymentStatus row details
    public function getPaymentStatusDetails(Request $request)
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
            $PaymentStatusDetails = $conn->table('payment_status')->where('id', $id)->first();
            return $this->successResponse($PaymentStatusDetails, 'Payment Status row fetch successfully');
        }
    }
    // update PaymentStatus
    public function updatePaymentStatus(Request $request)
    {
        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($conn->table('payment_status')->where([['name', '=', $request->name], ['id', '!=', $id]])->count() > 0) {
                return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            } else {
                // update data
                $query = $conn->table('payment_status')->where('id', $id)->update([
                    'name' => $request->name,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Payment Status Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        }
    }
    // delete PaymentStatus
    public function deletePaymentStatus(Request $request)
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
            $query = $conn->table('payment_status')->where('id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Payment Status have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }

    // addFeesType
    public function addFeesType(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($conn->table('fees_type')->where('name', '=', $request->name)->count() > 0) {
                return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            } else {
                // insert data
                $query = $conn->table('fees_type')->insert([
                    'name' => $request->name,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Fees Type has been successfully saved');
                }
            }
        }
    }
    // getFeesTypeList
    public function getFeesTypeList(Request $request)
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
            // get data
            $FeesTypeDetails = $conn->table('fees_type')->get();
            return $this->successResponse($FeesTypeDetails, 'Fees Type record fetch successfully');
        }
    }
    // get FeesType row details
    public function getFeesTypeDetails(Request $request)
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
            $FeesTypeDetails = $conn->table('fees_type')->where('id', $id)->first();
            return $this->successResponse($FeesTypeDetails, 'Fees Type row fetch successfully');
        }
    }
    // update FeesType
    public function updateFeesType(Request $request)
    {
        $id = $request->id;
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // check exist name
            if ($conn->table('fees_type')->where([['name', '=', $request->name], ['id', '!=', $id]])->count() > 0) {
                return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            } else {
                // update data
                $query = $conn->table('fees_type')->where('id', $id)->update([
                    'name' => $request->name,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Fees Type Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        }
    }
    // delete FeesType
    public function deleteFeesType(Request $request)
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
            $query = $conn->table('fees_type')->where('id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Fees Type have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }

    // addFeesGroup
    public function addFeesGroup(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
            'academic_session_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // check exist name
            // if ($conn->table('fees_group')->where('name', '=', $request->name)->count() > 0) {
            //     return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            // } else {
            // insert data
            // return $request->fees;
            $fees = $request->fees;
            // if (count($request->fees) > 0) {
            $insertId = $conn->table('fees_group')->insertGetId([
                'name' => $request->name,
                'description' => $request->description,
                'academic_session_id' => $request->academic_session_id,
                'created_at' => date("Y-m-d H:i:s")
            ]);
            // }
            // return $query;
            if (isset($insertId)) {
                // $fees = isset($request->fees) ? count($request->fees) : 0;
                // if ($fees > 0) {
                foreach ($request->fees as $fee) {
                    if (isset($fee['fees_type_id'])) {
                        // $yearly_fees_details = isset($fee['yearly_fees_details']) ? count($fee['yearly_fees_details']) : 0;
                        // $semester_fees_details = isset($fee['semester_fees_details']) ? count($fee['semester_fees_details']) : 0;
                        // $monthly_fees_details = isset($fee['monthly_fees_details']) ? count($fee['monthly_fees_details']) : 0;
                        // return $fee['semester_fees_details'];
                        // insert yearly fees group
                        // if ($yearly_fees_details > 0) {

                        foreach ($fee['yearly_fees_details'] as $year) {
                            // dd($year);
                            // return $year['amount'];
                            if (isset($year['due_date']) && isset($year['amount'])) {
                                $conn->table('fees_group_details')->insert([
                                    // 'fees_group_id' => $insertId,
                                    'fees_group_id' => $insertId,
                                    'fees_type_id' => $fee['fees_type_id'],
                                    'amount' => isset($year['amount']) ? $year['amount'] : 0,
                                    'payment_mode_id' => $year['payment_mode_id'],
                                    'due_date' => $year['due_date'],
                                    'yearly' => $year['yearly'],
                                    'created_at' => date("Y-m-d H:i:s")
                                ]);
                            }
                        }
                        // }
                        // insert semester fees group
                        // if ($semester_fees_details > 0) {
                        foreach ($fee['semester_fees_details'] as $semester) {
                            // dd($year);
                            if (isset($semester['due_date']) && isset($semester['amount'])) {
                                $conn->table('fees_group_details')->insert([
                                    // 'fees_group_id' => $insertId,
                                    'fees_group_id' => $insertId,
                                    'fees_type_id' => $fee['fees_type_id'],
                                    'amount' => isset($semester['amount']) ? $semester['amount'] : 0,
                                    'payment_mode_id' => $semester['payment_mode_id'],
                                    'due_date' => $semester['due_date'],
                                    'semester' => $semester['semester'],
                                    'created_at' => date("Y-m-d H:i:s")
                                ]);
                            }
                        }
                        // }
                        // insert monthly fees group
                        // if ($monthly_fees_details > 0) {
                        foreach ($fee['monthly_fees_details'] as $month) {
                            // dd($year);
                            if (isset($month['due_date']) && isset($month['amount'])) {
                                $conn->table('fees_group_details')->insert([
                                    // 'fees_group_id' => $insertId,
                                    'fees_group_id' => $insertId,
                                    'fees_type_id' => $fee['fees_type_id'],
                                    'amount' => isset($month['amount']) ? $month['amount'] : 0,
                                    'payment_mode_id' => $month['payment_mode_id'],
                                    'due_date' => $month['due_date'],
                                    'monthly' => $month['monthly'],
                                    'created_at' => date("Y-m-d H:i:s")
                                ]);
                            }
                        }
                        // }
                    }
                }
                // }
            }


            // return $query;
            $success = [];
            if (!$insertId) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Fees Group has been successfully saved');
            }
            // }
        }
    }
    // getFeesGroupList
    public function getFeesGroupList(Request $request)
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
            // get data
            $FeesGroupDetails = $conn->table('fees_group')->where('academic_session_id', $request->academic_session_id)->get();
            return $this->successResponse($FeesGroupDetails, 'Fees Group record fetch successfully');
        }
    }
    // get FeesGroup row details
    public function getFeesGroupDetails(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'id' => 'required',
            'branch_id' => 'required',
            'token' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $fees_group__id = $request->id;
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $fees_type = $conn->table('fees_type as ft')
                ->select(
                    'ft.id',
                    'ft.name'
                )
                ->get();

            $FeesGroupDetails = [];
            if (count($fees_type) > 0) {
                foreach ($fees_type as $value) {
                    $object = new \stdClass();
                    $object->id = $value->id;
                    $object->name = $value->name;
                    // yearly fees details
                    $query = $conn->table('fees_group_details as fg')
                        ->select(
                            'fg.id as fees_group_details_id',
                            'fg.fees_group_id',
                            'fg.fees_type_id',
                            'fg.payment_mode_id',
                            'fg.amount',
                            'fg.due_date',
                            'fg.monthly',
                            'fg.semester',
                            'fg.yearly'
                        )
                        ->where('fg.fees_group_id', '=', $fees_group__id)
                        ->where('fg.fees_type_id', '=', $value->id);
                    // year
                    $yearlyquery = clone $query;
                    $yearlyquery->whereNotNull('fg.yearly');
                    $year = $yearlyquery->get();
                    // // semester
                    $semesteryquery = clone $query;
                    $semesteryquery->whereNotNull('fg.semester');
                    $semester = $semesteryquery->get();
                    // // monthly
                    $monthlyyquery = clone $query;
                    $monthlyyquery->whereNotNull('fg.monthly');
                    $monthly = $monthlyyquery->get();

                    $data = [
                        'year' => $year,
                        'semester' => $semester,
                        'monthly' => $monthly
                    ];
                    $object->fees_details = $data;
                    array_push($FeesGroupDetails, $object);
                }
            }
            $fees_group = $conn->table('fees_group')->where('id', $fees_group__id)->first();
            $feesdata = [
                'fees_group_details' => $FeesGroupDetails,
                'fees_group' => $fees_group
            ];
            return $this->successResponse($feesdata, 'Fees Group row fetch successfully');
        }
    }
    // update FeesGroup
    public function updateFeesGroup(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'token' => 'required',
            'academic_session_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // check exist name
            // if ($conn->table('fees_group')->where('name', '=', $request->name)->count() > 0) {
            //     return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
            // } else {
            // insert data
            // return $request->fees;
            $fees = $request->fees;
            $id = $request->id;
            // if (count($request->fees) > 0) {
            // $insertId = $conn->table('fees_group')->insertGetId([
            //     'name' => $request->name,
            //     'description' => $request->description,
            //     'academic_session_id' => $request->academic_session_id,
            //     'created_at' => date("Y-m-d H:i:s")
            // ]);
            $query = $conn->table('fees_group')->where('id', $id)->update([
                'name' => $request->name,
                'description' => $request->description,
                'academic_session_id' => $request->academic_session_id,
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            // }
            // return $query;
            // $fees = isset($request->fees) ? count($request->fees) : 0;
            // if ($fees > 0) {
            foreach ($fees as $fee) {
                if (isset($fee['fees_type_id'])) {
                    // $yearly_fees_details = isset($fee['yearly_fees_details']) ? count($fee['yearly_fees_details']) : 0;
                    // $semester_fees_details = isset($fee['semester_fees_details']) ? count($fee['semester_fees_details']) : 0;
                    // $monthly_fees_details = isset($fee['monthly_fees_details']) ? count($fee['monthly_fees_details']) : 0;
                    // return $fee['semester_fees_details'];
                    // insert yearly fees group
                    // if ($yearly_fees_details > 0) {

                    foreach ($fee['yearly_fees_details'] as $year) {
                        // dd($year);
                        // return $year['amount'];
                        if (isset($year['fees_group_details_id'])) {
                            if (isset($year['due_date']) && isset($year['amount'])) {
                                $conn->table('fees_group_details')->where('id', $year['fees_group_details_id'])->update([
                                    'fees_group_id' => $id,
                                    'fees_type_id' => $fee['fees_type_id'],
                                    'amount' => isset($year['amount']) ? $year['amount'] : 0,
                                    'payment_mode_id' => $year['payment_mode_id'],
                                    'due_date' => $year['due_date'],
                                    'yearly' => $year['yearly'],
                                    'updated_at' => date("Y-m-d H:i:s")
                                ]);
                            } else {
                                $conn->table('fees_group_details')->where('id', $year['fees_group_details_id'])->delete();
                            }
                        } else {
                            if (isset($year['due_date']) && isset($year['amount'])) {
                                $conn->table('fees_group_details')->insert([
                                    // 'fees_group_id' => $insertId,
                                    'fees_group_id' => $id,
                                    'fees_type_id' => $fee['fees_type_id'],
                                    'amount' => isset($year['amount']) ? $year['amount'] : 0,
                                    'payment_mode_id' => $year['payment_mode_id'],
                                    'due_date' => $year['due_date'],
                                    'yearly' => $year['yearly'],
                                    'created_at' => date("Y-m-d H:i:s")
                                ]);
                            }
                        }
                    }
                    // }
                    // insert semester fees group
                    // if ($semester_fees_details > 0) {
                    foreach ($fee['semester_fees_details'] as $semester) {
                        // dd($year);
                        // if (isset($semester['due_date']) && isset($semester['amount'])) {
                        //     $conn->table('fees_group_details')->insert([
                        //         // 'fees_group_id' => $insertId,
                        //         'fees_group_id' => $insertId,
                        //         'fees_type_id' => $fee['fees_type_id'],
                        //         'amount' => isset($semester['amount']) ? $semester['amount'] : 0,
                        //         'payment_mode_id' => $semester['payment_mode_id'],
                        //         'due_date' => $semester['due_date'],
                        //         'semester' => $semester['semester'],
                        //         'created_at' => date("Y-m-d H:i:s")
                        //     ]);
                        // }
                        if (isset($semester['fees_group_details_id'])) {

                            if (isset($semester['due_date']) && isset($semester['amount'])) {
                                $conn->table('fees_group_details')->where('id', $semester['fees_group_details_id'])->update([
                                    'fees_group_id' => $id,
                                    'fees_type_id' => $fee['fees_type_id'],
                                    'amount' => isset($semester['amount']) ? $semester['amount'] : 0,
                                    'payment_mode_id' => $semester['payment_mode_id'],
                                    'due_date' => $semester['due_date'],
                                    'semester' => $semester['semester'],
                                    'updated_at' => date("Y-m-d H:i:s")
                                ]);
                            } else {
                                $conn->table('fees_group_details')->where('id', $semester['fees_group_details_id'])->delete();
                            }
                        } else {
                            if (isset($semester['due_date']) && isset($semester['amount'])) {
                                $conn->table('fees_group_details')->insert([
                                    // 'fees_group_id' => $insertId,
                                    'fees_group_id' => $id,
                                    'fees_type_id' => $fee['fees_type_id'],
                                    'amount' => isset($semester['amount']) ? $semester['amount'] : 0,
                                    'payment_mode_id' => $semester['payment_mode_id'],
                                    'due_date' => $semester['due_date'],
                                    'semester' => $semester['semester'],
                                    'created_at' => date("Y-m-d H:i:s")
                                ]);
                            }
                        }
                    }
                    // }
                    // insert monthly fees group
                    // if ($monthly_fees_details > 0) {
                    foreach ($fee['monthly_fees_details'] as $month) {
                        // dd($year);
                        // if (isset($month['due_date']) && isset($month['amount'])) {
                        //     $conn->table('fees_group_details')->insert([
                        //         // 'fees_group_id' => $insertId,
                        //         'fees_group_id' => $insertId,
                        //         'fees_type_id' => $fee['fees_type_id'],
                        //         'amount' => isset($month['amount']) ? $month['amount'] : 0,
                        //         'payment_mode_id' => $month['payment_mode_id'],
                        //         'due_date' => $month['due_date'],
                        //         'monthly' => $month['monthly'],
                        //         'created_at' => date("Y-m-d H:i:s")
                        //     ]);
                        // }
                        if (isset($month['fees_group_details_id'])) {
                            if (isset($month['due_date']) && isset($month['amount'])) {
                                $conn->table('fees_group_details')->where('id', $month['fees_group_details_id'])->update([
                                    'fees_group_id' => $id,
                                    'fees_type_id' => $fee['fees_type_id'],
                                    'amount' => isset($month['amount']) ? $month['amount'] : 0,
                                    'payment_mode_id' => $month['payment_mode_id'],
                                    'due_date' => $month['due_date'],
                                    'monthly' => $month['monthly'],
                                    'updated_at' => date("Y-m-d H:i:s")
                                ]);
                            } else {
                                $conn->table('fees_group_details')->where('id', $month['fees_group_details_id'])->delete();
                            }
                        } else {
                            if (isset($month['due_date']) && isset($month['amount'])) {
                                $conn->table('fees_group_details')->insert([
                                    // 'fees_group_id' => $insertId,
                                    'fees_group_id' => $id,
                                    'fees_type_id' => $fee['fees_type_id'],
                                    'amount' => isset($month['amount']) ? $month['amount'] : 0,
                                    'payment_mode_id' => $month['payment_mode_id'],
                                    'due_date' => $month['due_date'],
                                    'monthly' => $month['monthly'],
                                    'created_at' => date("Y-m-d H:i:s")
                                ]);
                            }
                        }
                    }
                    // }
                }
            }
            // }
            // return $query;
            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Fees Group Details have Been updated');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
            // }
        }
    }
    // public function updateFeesGroup(Request $request)
    // {
    //     $id = $request->id;
    //     $validator = \Validator::make($request->all(), [
    //         'name' => 'required',
    //         'branch_id' => 'required',
    //         'token' => 'required',
    //         'academic_session_id' => 'required',
    //     ]);
    //     // return $id;
    //     if (!$validator->passes()) {
    //         return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
    //     } else {

    //         // create new connection
    //         $conn = $this->createNewConnection($request->branch_id);
    //         // check exist name
    //         if ($conn->table('fees_group')->where([['name', '=', $request->name], ['id', '!=', $id]])->count() > 0) {
    //             return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
    //         } else {
    //             // update data
    //             $query = $conn->table('fees_group')->where('id', $id)->update([
    //                 'name' => $request->name,
    //                 'description' => $request->description,
    //                 'academic_session_id' => $request->academic_session_id,
    //                 'updated_at' => date("Y-m-d H:i:s")
    //             ]);
    //             // return $query;
    //             $fees = $request->fees;
    //             // return $request;
    //             foreach ($fees as $fee) {
    //                 if (isset($fee['fees_type_id'])) {

    //                     foreach ($fee['fees_group_details_id'] as $key => $fees_group__id) {
    //                         // dd($fees_group__id);

    //                         // $conn->table('fees_group_details')->insert([
    //                         //     'fees_group_id' => $insertId,
    //                         //     'fees_type_id' => $fee['fees_type_id'],
    //                         //     'due_date' => $fee['due_date'],
    //                         //     'payment_mode_id' => $fee['mode_id'][$key],
    //                         //     'amount' => $fee['amount'][$key],
    //                         //     'created_at' => date("Y-m-d H:i:s")
    //                         // ]);
    //                         if ($fees_group__id) {
    //                             // if (isset($fee['amount'][$key])) {
    //                             $conn->table('fees_group_details')->where('id', $fees_group__id)->update([
    //                                 'due_date' => $fee['due_date'],
    //                                 'payment_mode_id' => $fee['mode_id'][$key],
    //                                 'amount' => isset($fee['amount'][$key]) ? $fee['amount'][$key] : 0,
    //                                 'created_at' => date("Y-m-d H:i:s")
    //                             ]);
    //                             // }
    //                         } else {
    //                             // if (isset($fee['amount'][$key])) {
    //                             $conn->table('fees_group_details')->insert([
    //                                 'fees_group_id' => $id,
    //                                 'fees_type_id' => $fee['fees_type_id'],
    //                                 'due_date' => $fee['due_date'],
    //                                 'payment_mode_id' => $fee['mode_id'][$key],
    //                                 'amount' => isset($fee['amount'][$key]) ? $fee['amount'][$key] : 0,
    //                                 'created_at' => date("Y-m-d H:i:s")
    //                             ]);
    //                             // }
    //                         }
    //                     }
    //                 }
    //             }

    //             $success = [];
    //             if ($query) {
    //                 return $this->successResponse($success, 'Fees Group Details have Been updated');
    //             } else {
    //                 return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
    //             }
    //         }
    //     }
    // }
    // delete FeesGroup
    public function deleteFeesGroup(Request $request)
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
            $query = $conn->table('fees_group')->where('id', $id)->delete();
            $query = $conn->table('fees_group_details')->where('fees_group_id', $id)->delete();

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Fees Group have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    public function feesAllocation(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'class_id' => 'required',
            'section_id' => 'required',
            'student_operations' => 'required',
            'group_id' => 'required',
            'academic_session_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $student_array = $request->student_operations;
            $delete_student_operations = $request->delete_student_operations;
            // dd($student_array);
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            if (!empty($student_array)) {
                foreach ($student_array as $value) {
                    // print_r($value['student_id']);
                    // // payment_mode_id
                    // exit;
                    if ((isset($value['student_id'])) && (isset($value['payment_mode_id']))) {
                        $arrayData = array(
                            'student_id' => $value['student_id'],
                            'group_id' => $request->group_id,
                            'class_id' => $request->class_id,
                            'section_id' => $request->section_id,
                            // 'payment_mode_id' => $value['payment_mode_id'],
                            'academic_session_id' => $request->academic_session_id
                        );
                        // if already exist fees_allocation
                        $checkAlreadyTakenAttendance = $Connection->table('fees_allocation')->select('id')->where($arrayData)->first();
                        // update flag
                        if (isset($checkAlreadyTakenAttendance->id)) {
                            $Connection->table('fees_allocation')->where('id', '=', $checkAlreadyTakenAttendance->id)->update([
                                'payment_mode_id' => $value['payment_mode_id'],
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                        }
                        // insert
                        $examResultexist = $Connection->table('fees_allocation')
                            ->where($arrayData)
                            ->count();
                        if ($examResultexist == 0) {
                            $arrayData['payment_mode_id'] = $value['payment_mode_id'];
                            $Connection->table('fees_allocation')->insert($arrayData);
                        }
                    }
                }
            }
            if (!empty($delete_student_operations)) {
                $Connection->table('fees_allocation')
                    ->where('group_id', $request->group_id)
                    ->where('class_id', $request->class_id)
                    ->where('section_id', $request->section_id)
                    ->where('academic_session_id', $request->academic_session_id)
                    ->whereNotIn('student_id', $delete_student_operations)
                    ->delete();
            }
            if (empty($delete_student_operations)) {
                $Connection->table('fees_allocation')
                    ->where('group_id', $request->group_id)
                    ->where('class_id', $request->class_id)
                    ->where('section_id', $request->section_id)
                    ->where('academic_session_id', $request->academic_session_id)
                    ->delete();
            }
            return $this->successResponse([], 'Fess allocated successfully');
        }
    }
    // get fees allocated students
    public function feesAllocatedStudents(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'class_id' => 'required',
            'section_id' => 'required',
            'fees_group_id' => 'required',
            'academic_session_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            $fees_group_id = $request->fees_group_id;
            $academic_session_id = $request->academic_session_id;
            $section_id = $request->section_id;

            $studentData = $conn->table('enrolls as en')
                ->select(
                    'st.id',
                    'st.photo',
                    'st.gender',
                    'st.register_no',
                    'st.email',
                    'fa.id as allocation_id',
                    'fa.payment_mode_id',
                    DB::raw('CONCAT(st.first_name, " ", st.last_name) as name')
                )
                ->leftJoin('students as st', 'en.student_id', '=', 'st.id')
                ->leftJoin('fees_allocation as fa', function ($join) use ($fees_group_id, $academic_session_id) {
                    $join->on('fa.student_id', '=', 'en.student_id')
                        ->on('fa.group_id', '=', DB::raw("'$fees_group_id'"))
                        ->on('fa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->where([
                    ['en.class_id', '=', $request->class_id],
                    ['en.section_id', '=', $request->section_id],
                    ['en.active_status', '=', '0'],
                    ['en.academic_session_id', '=', $request->academic_session_id]
                ])
                // ->when($section_id != "All", function ($q)  use ($section_id) {
                //     $q->where('en.section_id', $section_id);
                // })
                ->orderBy('st.id', 'ASC')
                ->get();
            return $this->successResponse($studentData, 'Fees Type row fetch successfully');
        }
    }
    // get fees
    public function getFeesAllocatedStudents(Request $request)
    {

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
            // $feesGroupId = null;
            // if ($request->fees_type) {
            //     $feesDet = explode('|', $request->fees_type);
            //     $feesGroupId = $feesDet[0];
            // }
            $feesGroupId = isset($request->group_id) ? $request->group_id : null;
            $conn = $this->createNewConnection($request->branch_id);
            $student_id = $request->student_id;
            // get data
            $studentData = $conn->table('fees_allocation as fa')
                ->select(
                    'fa.student_id',
                    'fa.class_id',
                    'fa.section_id',
                    // 'en.roll',
                    'st.gender',
                    'st.register_no',
                    'st.email',
                    'cl.name as class_name',
                    'sc.name as section_name',
                    'st.photo',
                    // 'fa.id as allocation_id',
                    DB::raw('CONCAT(st.first_name, " ", st.last_name) as name'),
                    DB::raw("group_concat(fa.group_id) as all_group_id")
                )
                // ->join('enrolls as en', 'en.student_id', '=', 'fa.student_id')
                ->leftJoin('students as st', 'fa.student_id', '=', 'st.id')
                ->leftJoin('classes as cl', 'fa.class_id', '=', 'cl.id')
                ->leftJoin('sections as sc', 'fa.section_id', '=', 'sc.id')
                // ->where([
                //     ['en.class_id', '=', $request->class_id],
                //     ['en.section_id', '=', $request->section_id],
                //     ['en.active_status', '=', '0'],
                //     ['en.academic_session_id', '=', $request->academic_session_id]
                // ])
                ->where([
                    ['fa.class_id', '=', $request->class_id],
                    ['fa.section_id', '=', $request->section_id],
                    ['fa.academic_session_id', '=', $request->academic_session_id]
                ])
                ->when($student_id, function ($q)  use ($student_id) {
                    $q->where('fa.student_id', $student_id);
                })
                ->when($feesGroupId, function ($q)  use ($feesGroupId) {
                    $q->where('fa.group_id', $feesGroupId);
                })
                ->groupBy('fa.student_id')
                ->orderBy('st.id', 'ASC')
                ->get()->toArray();
            $arrData = [];
            if (!empty($studentData)) {
                foreach ($studentData as $key => $value) {
                    $object = new \stdClass();
                    $object->student_id = $value->student_id;
                    $object->email = $value->email;
                    $object->class_name = $value->class_name;
                    $object->section_name = $value->section_name;
                    $object->name = $value->name;
                    $object->photo = $value->photo;
                    $object->all_group_id = $value->all_group_id;
                    $all_group_id = explode(",", $value->all_group_id);
                    $group_arr = [];
                    if (!empty($all_group_id)) {
                        foreach ($all_group_id as $grp_id) {
                            $newGrp = new \stdClass();
                            $return_group = $this->getfeeGroup($value, $branchID, $academic_session_id, $grp_id);
                            $newGrp->grp_id = $grp_id;
                            $newGrp->paid_id = $return_group['paid_id'];
                            $newGrp->group_name = $return_group['group_name'];
                            $newGrp->paidSts = $return_group['paidSts'];
                            $newGrp->labelmode = $return_group['labelmode'];
                            // group_arr
                            if (isset($request->payment_status)) {
                                // dd($return_group['paid_id']);
                                if ($request->payment_status == $return_group['paid_id']) {
                                    array_push($group_arr, $newGrp);
                                }
                            } else {
                                array_push($group_arr, $newGrp);
                            }
                        }
                    }
                    $object->feegroup = $group_arr;
                    // filter by payment status
                    if (isset($request->payment_status)) {
                        if (!empty($group_arr)) {
                            array_push($arrData, $object);
                        }
                    } else {
                        array_push($arrData, $object);
                    }
                }
            }
            return $this->successResponse($arrData, 'get student details fetch successfully');
        }
    }
    public function getfeeGroup($value, $branchID, $academic_session_id, $grp_id)
    {
        $studentID = $value->student_id;
        $classID = $value->class_id;
        $sectionID = $value->section_id;
        // create new connection
        $conn = $this->createNewConnection($branchID);
        // get data
        $studentData = $conn->table('fees_allocation as fa')
            ->select(
                'g.id as group_id',
                'g.name',
                'fa.payment_mode_id',
                'fa.academic_session_id',
                'fg.amount',
                'fg.id as fg_id',
                'fg.monthly',
                'fg.semester',
                'fg.yearly',
                'fg.due_date',
                'fph.id',
                'fph.student_id',
                'fph.allocation_id',
                'fph.fees_type_id',
                'fph.payment_status_id',
                'fph.collect_by',
                'fph.amount as paid_amount',
                'fph.discount',
                'fph.fine',
                'fph.pay_via',
                'fph.remarks',
                'fph.date as paid_date',
                'ft.name as fees_type_name'
                // 'fa.id as allocation_id',
                // 'fa.payment_mode_id',
                // 'fa.student_id',
                // 't.name',
                // 'f.name as fees_group_name',
                // 'fg.amount',
                // 'fg.due_date',
                // 'fg.fees_type_id',
                // "fph.fees_group_details_id",
                // 'fph.date as paid_date',
                // "fph.payment_status_id",
                // "fph.student_id",
                // "fph.amount as paid_amount"
            )
            ->join('fees_group as g', 'g.id', '=', 'fa.group_id')
            ->leftjoin('fees_group_details as fg', function ($join) {
                $join->on('fg.fees_group_id', '=', 'fa.group_id')
                    ->on('fg.payment_mode_id', '=', 'fa.payment_mode_id');
            })
            ->leftjoin('fees_payment_history as fph', function ($join) use ($studentID, $academic_session_id) {
                $join->on('fph.fees_group_details_id', '=', 'fg.id');
                $join->on('fph.student_id', '=', DB::raw("'$studentID'"));
                $join->on('fph.academic_session_id', '=', DB::raw("'$academic_session_id'"));
            })
            ->join('fees_type as ft', 'ft.id', '=', 'fg.fees_type_id')
            ->where([
                ['fa.student_id', '=', $studentID],
                ['fa.class_id', '=', $classID],
                ['fa.section_id', '=', $sectionID],
                ['fa.group_id', '=', $grp_id],
                ['fa.academic_session_id', '=', $academic_session_id]
            ])
            ->get()->toArray();

        // dd($studentData);
        $paymentArr = [];
        if (!empty($studentData)) {
            $now = date('Y-m-d');
            // current semester details
            $current_semester = $conn->table('semester as sm')
                ->select(
                    'sm.id',
                    'sm.name',
                    'sm.start_date',
                    'sm.end_date'
                )
                ->whereRaw('"' . $now . '" between `start_date` and `end_date`')
                ->first();
            // semester details
            $all_semester = $conn->table('semester as sm')
                ->select(
                    'sm.id',
                    'sm.name',
                    'sm.start_date',
                    'sm.end_date'
                )
                ->where([
                    ['sm.academic_session_id', '=', $academic_session_id],
                ])->get()->toArray();
            // year details
            $year_details = $conn->table('semester as sm')
                ->select(DB::raw('MIN(sm.start_date) AS year_start_date, MAX(sm.end_date) AS year_end_date'))
                ->where([
                    ['sm.academic_session_id', '=', $academic_session_id],
                ])
                ->get();
            // get semester,month,year end date details
            $active_year_end_date = isset($year_details[0]->year_end_date) ? $year_details[0]->year_end_date : null;
            $active_sem_end_date = isset($current_semester->end_date) ? $current_semester->end_date : null;
            $active_month_end_date = date("Y-m-t");
            // dd($studentData);
            foreach ($studentData as $val) {
                $object = new \stdClass();
                // $object->group_id = $val->id;
                $object->group_name = $val->name;
                $object->fees_type_name = $val->fees_type_name;
                $due_date = isset($val->due_date) ? $val->due_date : null;
                // dd($due_date);
                $paid_date = isset($val->paid_date) ? $val->paid_date : null;
                $payment_status_id = isset($val->payment_status_id) ? $val->payment_status_id : null;
                $paid_amount = isset($val->paid_amount) ? $val->paid_amount : null;
                $amount = $val->amount;
                $payment_mode_id = $val->payment_mode_id;
                // $paid_amount = isset($val->paid_amount) ? $val->paid_amount : null;
                $paidSts = "";
                $labelmode = "";
                // paid fees details
                // print_r($val);
                // amount paid
                // dd($val);
                if ((isset($due_date)) && (isset($paid_date))) {
                    // paid status id 1 mean paid
                    if ($payment_status_id == 1 && isset($paid_date)) {
                        $type_amount = round($paid_amount);
                    } else {
                        $type_amount = round(0);
                    }
                    $balance = ($amount - $type_amount);
                    $balance = number_format($balance, 2, '.', '');
                    if ($balance == 0) {
                        $paidSts = 'paid';
                        $labelmode = 'badge-success';
                    } else {
                        $paidSts = 'unpaid';
                        $labelmode = 'badge-danger';
                    }
                }
                // amount unpaid or delay
                if ((isset($due_date)) && ($paid_date === null || trim($paid_date) === '')) {
                    // yearly payment
                    if ($payment_mode_id == 1) {

                        $year_start_date = isset($year_details['0']->year_start_date) ? $year_details['0']->year_start_date : null;
                        $start_date = date('Y-m-d', strtotime($year_start_date));
                        $year_end_date = isset($year_details['0']->year_end_date) ? $year_details['0']->year_end_date : null;
                        $end_date = date('Y-m-d', strtotime($year_end_date));

                        if ($start_date <= $now && $now <= $end_date) {
                            // if ($start_date <= $now && $now <= $end_date) {
                            // match between semester date
                            if ($due_date <= $now) {
                                $paidSts = 'delay';
                                $labelmode = 'badge-secondary';
                            } else {
                                $paidSts = 'unpaid';
                                $labelmode = 'badge-danger';
                            }
                        } else {
                            // not match between semester date
                            $paidSts = 'unpaid';
                            $labelmode = 'badge-danger';
                        }

                        // dd($paidSts);
                    }
                    // semester payment
                    if ($payment_mode_id == 2) {
                        $id = isset($current_semester->id) ? $current_semester->id : 0;
                        $key = array_search($id, array_column($all_semester, 'id'));
                        if ((!empty($key)) || ($key === 0)) {
                            // get which semester running now
                            $get_semester = $all_semester[$key];
                            $sem_start_date = isset($get_semester->start_date) ? $get_semester->start_date : null;
                            $start_date = date('Y-m-d', strtotime($sem_start_date));
                            $sem_end_date = isset($get_semester->end_date) ? $get_semester->end_date : null;
                            $end_date = date('Y-m-d', strtotime($sem_end_date));
                            if ($start_date <= $now && $now <= $end_date) {
                                // if ($start_date <= $now && $now <= $end_date) {
                                // match between semester date
                                if ($due_date <= $now) {
                                    $paidSts = 'delay';
                                    $labelmode = 'badge-secondary';
                                } else {
                                    $paidSts = 'unpaid';
                                    $labelmode = 'badge-danger';
                                }
                            } else {
                                // not match between semester date
                                $paidSts = 'unpaid';
                                $labelmode = 'badge-danger';
                            }
                        } else {
                            // if semester finish
                            $paidSts = 'unpaid';
                            $labelmode = 'badge-danger';
                        }
                    }
                    // monthly payment
                    if ($payment_mode_id == 3) {
                        $query_date = date('Y-m-d', strtotime($due_date));
                        // First day of the month.
                        $start_date = date('Y-m-01', strtotime($query_date));
                        // Last day of the month.
                        $end_date = date('Y-m-t', strtotime($query_date));
                        if ($start_date <= $now && $now <= $end_date) {
                            // match between semester date
                            if ($due_date <= $now) {
                                $paidSts = 'delay';
                                $labelmode = 'badge-secondary';
                            } else {
                                $paidSts = 'unpaid';
                                $labelmode = 'badge-danger';
                            }
                        } else {
                            // not match between semester date
                            $paidSts = 'unpaid';
                            $labelmode = 'badge-danger';
                        }
                    }
                }
                $object->paid_status = $paidSts;
                $object->label_mode = $labelmode;
                $object->due_date = $due_date;
                $object->paid_date = $paid_date;
                $object->payment_mode_id = $payment_mode_id;
                array_push($paymentArr, $object);
            }
            // default payment status
            $defpaidID = 2;
            $defpaidSts = "unpaid";
            $deflabelmode = "badge-danger";

            if (!empty($paymentArr)) {
                $arr = array();
                $group_name = "";
                // here filter by semester below,year below,month below all paid,unpaid,delay come
                foreach ($paymentArr as $key => $item) {
                    $group_name = $item->group_name;
                    $new_object = new \stdClass();
                    $new_object->group_name = $item->group_name;
                    $new_object->fees_type_name = $item->fees_type_name;
                    $new_object->paid_status = $item->paid_status;
                    // $new_object->label_mode = $item->label_mode;
                    // $new_object->due_date = $item->due_date;
                    // $new_object->paid_date = $item->paid_date;
                    // $new_object->payment_mode_id = $item->payment_mode_id;
                    // yearly
                    if ($item->payment_mode_id == 1) {
                        // lesser than expiry date
                        if ($item->due_date <= $active_year_end_date) {
                            array_push($arr, $new_object);
                        }
                    }
                    // semester
                    if ($item->payment_mode_id == 2) {
                        // lesser than expiry date
                        if ($item->due_date <= $active_sem_end_date) {
                            array_push($arr, $new_object);
                        }
                    }
                    // monthly
                    if ($item->payment_mode_id == 3) {
                        // lesser than expiry date
                        if ($item->due_date <= $active_month_end_date) {
                            array_push($arr, $new_object);
                        }
                    }
                }

                $totalCount = count($arr);
                $delayCount = 0;
                $unpaidCount = 0;
                $paidCount = 0;
                foreach ($arr as $aritem) {
                    // unpaid
                    if ($aritem->paid_status == "unpaid") {
                        $unpaidCount++;
                    }
                    // delay
                    if ($aritem->paid_status == "delay") {
                        $delayCount++;
                    }
                    // paid
                    if ($aritem->paid_status == "paid") {
                        $paidCount++;
                    }
                }
                // here only one statys either paid,delay,unpaid
                if ($totalCount != 0) {
                    if ($totalCount == $paidCount) {
                        $defpaidID = 1;
                        $defpaidSts = "paid";
                        $deflabelmode = "badge-success";
                    } else if ($delayCount > 0) {
                        $defpaidID = 3;
                        $defpaidSts = "delay";
                        $deflabelmode = "badge-secondary";
                    } else {
                        // otherwise all conside unpaid
                        $defpaidID = 2;
                        $defpaidSts = "unpaid";
                        $deflabelmode = "badge-danger";
                    }
                }
            }
            $ret_res = [
                'paid_id' => $defpaidID,
                'group_name' => $group_name,
                'paidSts' => $defpaidSts,
                'labelmode' => $deflabelmode
            ];
            return $ret_res;
        }
        // default
        $ret_res = [
            'paid_id' => "",
            'group_name' => "",
            'paidSts' => "unpaid",
            'labelmode' => "badge-danger"
        ];
        return $ret_res;
    }
    function deleteFeesDetails(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'student_id' => 'required',
            'academic_session_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $studentID =  $request->student_id;
            $conn = $this->createNewConnection($request->branch_id);
            $result = $conn->table('fees_allocation as fa')
                ->select(
                    'fa.id',
                    'fa.student_id'
                )
                ->where([
                    ['fa.student_id', '=', $studentID],
                    ['fa.academic_session_id', '=', $request->academic_session_id]
                ])
                ->get()->toArray();
            if (!empty($result)) {
                foreach ($result as $key => $value) {
                    $conn->table('fees_payment_history')
                        ->where('allocation_id', $value->id)
                        ->delete();
                }
            }
            $conn->table('fees_allocation')
                ->where('student_id', $studentID)
                ->delete();
            return $this->successResponse([], 'Fess deleted successfully');
        }
    }
    // get Fees row details
    public function getFeesDetails(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'student_id' => 'required',
            'branch_id' => 'required',
            'token' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $academic_session_id = $request->academic_session_id;
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            $FeesDetails['student'] = $conn->table('students as st')
                ->select(
                    'en.student_id',
                    'en.roll',
                    'st.gender',
                    'st.register_no',
                    'st.email',
                    'st.mobile_no',
                    'cl.name as class_name',
                    'sc.name as section_name',
                    DB::raw('CONCAT(st.first_name, " ", st.last_name) as name'),
                    DB::raw('CONCAT(p.first_name, " ", p.last_name) as parent_name'),
                    'ay.name as academic_year',
                    'ay.id as academic_id'
                )
                ->join('enrolls as en', 'en.student_id', '=', 'st.id')
                ->join('academic_year as ay', 'en.academic_session_id', '=', 'ay.id')
                ->leftjoin('parent as p', function ($join) {
                    $join->on('st.father_id', '=', 'p.id');
                    $join->orOn('st.mother_id', '=', 'p.id');
                    $join->orOn('st.guardian_id', '=', 'p.id');
                })
                // ->leftJoin('parent as p', 'st.father_id', '=', 'p.id')
                ->leftJoin('classes as cl', 'en.class_id', '=', 'cl.id')
                ->leftJoin('sections as sc', 'en.section_id', '=', 'sc.id')
                ->where([
                    ['en.student_id', '=', $request->student_id],
                    ['en.active_status', '=', '0'],
                    ['en.academic_session_id', '=', $academic_session_id],
                ])
                // ->when($section_id != "All", function ($q)  use ($section_id) {
                //     $q->where('en.section_id', $section_id);
                // })
                ->orderBy('st.id', 'ASC')
                ->first();
            // $FeesDetails['fees'] = $conn->table('fees_allocation as fa')
            //     // ->select('fa.id as allocation_id','fg.name as group_name')
            //     ->select(
            //         'ft.name as fees_name',
            //         'fg.name as group_name',
            //         'ft.id as fees_type_id',
            //         'fa.id as allocation_id',
            //         'fgd.amount as paid_amount',
            //         'fph.id as invoice_id',
            //         'fph.collect_by',
            //         'fph.amount',
            //         'fph.date',
            //     )
            //     ->leftJoin('fees_group as fg', 'fa.group_id', '=', 'fg.id')
            //     ->leftJoin('fees_group_details as fgd', 'fa.group_id', '=', 'fgd.fees_group_id')
            //     ->leftJoin('fees_type as ft', 'fgd.fees_type_id', '=', 'ft.id')
            //     ->leftjoin('fees_payment_history as fph', function ($join) {
            //         $join->on('ft.id', '=', 'fph.fees_type_id');
            //         $join->on('fa.id', '=', 'fph.allocation_id');
            //     })
            //     // ->leftJoin('fees_payment_history as fph', 'ft.id', '=', 'fph.fees_type_id')
            //     ->groupBy('ft.id')
            //     ->where('fa.student_id', $request->student_id)
            //     ->get();
            $FeesDetails['fees'] = $conn->table('fees_allocation as fa')
                // ->select('fa.id as allocation_id','fg.name as group_name')
                ->select(
                    // 'fg.name as group_name',
                    // 'ft.name as fees_name',
                    DB::raw('CONCAT(fg.name, " - ", ft.name) as fees_name'),
                    'ft.id as fees_type_id',
                    'fg.id',
                    'fa.id as allocation_id',
                    'fa.payment_mode_id',
                    'fgd.amount as paid_amount',
                    'fph.id as invoice_id',
                    'fph.collect_by',
                    'fph.amount',
                    'fph.date',
                )
                ->leftJoin('fees_group_details as fgd', 'fa.group_id', '=', 'fgd.fees_group_id')
                ->leftJoin('fees_group as fg', 'fa.group_id', '=', 'fg.id')
                ->leftJoin('fees_type as ft', 'fgd.fees_type_id', '=', 'ft.id')
                ->leftjoin('fees_payment_history as fph', function ($join) {
                    $join->on('ft.id', '=', 'fph.fees_type_id');
                    $join->on('fa.id', '=', 'fph.allocation_id');
                })
                // ->leftJoin('fees_payment_history as fph', 'ft.id', '=', 'fph.fees_type_id')
                // ->where('fa.student_id', $request->student_id)
                ->where([
                    ['fa.student_id', '=', $request->student_id],
                    ['fa.academic_session_id', '=', $academic_session_id]
                ])
                ->groupBy(['ft.id', 'fg.id'])
                // ->orderBy('ft.id', 'asc')
                // ->orderBy('fg.id', 'asc')
                // ->groupBy('fgd.id')
                // ->groupBy('ft.id')
                ->get();
            // if (!empty($studentData)) {
            //     foreach ($studentData as $key => $value) {
            //         $studentData[$key]->feegroup = $this->getfeeGroup($value->student_id, $branchID, $academic_session_id);
            //     }
            // }
            return $this->successResponse($FeesDetails, 'Fees row fetch successfully');
        }
    }
    // fees edit page
    public function studentFeesHistory(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'student_id' => 'required',
            'academic_session_id' => 'required',
            'branch_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $studentID = $request->student_id;
            $branchID = $request->branch_id;
            $academic_session_id = $request->academic_session_id;
            $allocations = $conn->table('fees_allocation as fa')
                ->select(
                    'fa.id as allocation_id',
                    'fa.payment_mode_id',
                    'fa.student_id',
                    't.name',
                    'f.name as fees_group_name',
                    'fg.amount',
                    'fg.due_date',
                    'fg.fees_type_id',
                    "fph.fees_group_details_id",
                    'fph.date as paid_date',
                    "fph.payment_status_id",
                    "fph.student_id",
                    "fph.amount as paid_amount",
                    "pm.name as payment_mode_name"
                )
                ->leftJoin('fees_group as f', 'f.id', '=', 'fa.group_id')
                ->leftJoin('payment_mode as pm', 'fa.payment_mode_id', '=', 'pm.id')
                ->leftjoin('fees_group_details as fg', function ($join) {
                    $join->on('fg.fees_group_id', '=', 'fa.group_id');
                    $join->on('fg.payment_mode_id', '=', 'fa.payment_mode_id');
                })
                ->leftjoin('fees_payment_history as fph', function ($join) use ($studentID, $academic_session_id) {
                    $join->on('fph.fees_group_details_id', '=', 'fg.id');
                    $join->on('fph.student_id', '=', DB::raw("'$studentID'"));
                    $join->on('fph.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->leftJoin('fees_type as t', 't.id', '=', 'fg.fees_type_id')
                ->where([
                    ['fa.student_id', '=', $studentID],
                    ['fa.academic_session_id', '=', $academic_session_id]
                ])
                ->orderBy('f.id', 'asc')
                ->orderBy('fg.id', 'asc')
                ->get()->toArray();
            return $this->successResponse($allocations, 'Get fees row fetch successfully');
        }
    }
    public function getStudentFeeDeposit($allocationID, $typeID, $studentID, $branchID)
    {
        $conn = $this->createNewConnection($branchID);
        $fees_payment_history = $conn->table('fees_payment_history as h')
            ->select(
                DB::raw('IFNULL(SUM(amount), "0.00") as total_amount'),
                DB::raw('IFNULL(SUM(discount), "0.00") as total_discount'),
                DB::raw('IFNULL(SUM(fine), "0.00") as total_fine')
            )
            ->where([
                ['h.allocation_id', '=', $allocationID],
                ['h.fees_type_id', '=', $typeID],
                ['h.student_id', '=', $studentID]
            ])
            ->get()->toArray();
        return $fees_payment_history;
    }

    // update Fees
    public function updateFees(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'student_id' => 'required',
            'allocation_id' => 'required',
            'fees_type' => 'required',
            'payment_mode' => 'required',
            'collect_by' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $fees = $request->fees;
            // dd($request);
            // return $fees;
            // check exist payment_mode,allocation_id,student_id,fees_type_id
            if ($request->payment_mode == "1") {
                // return $fees;
                $row = $conn->table('fees_payment_history')->select('id')->where([
                    ['student_id', '=', $request->student_id],
                    ['allocation_id', '=', $request->allocation_id],
                    ['fees_type_id', '=', $request->fees_type],
                    ['fees_group_id', '=', $request->fees_group_id],
                    ['payment_mode_id', '=', $request->payment_mode]
                ])->first();
                if (isset($row->id)) {
                    $conn->table('fees_payment_history')->where('id', $row->id)->update([
                        'payment_status_id' => isset($fees['payment_status']) ? $fees['payment_status'] : 0,
                        'collect_by' => $request->collect_by,
                        'amount' => isset($fees['amount']) ? $fees['amount'] : 0,
                        'discount' => "0",
                        'fine' => "0",
                        'yearly' => "1",
                        'pay_via' => "Cash",
                        'remarks' => isset($fees['memo']) ? $fees['memo'] : "",
                        'date' => isset($fees['date']) ? $fees['date'] : null,
                        'fees_group_details_id' => isset($fees['fees_group_details_id']) ? $fees['fees_group_details_id'] : 0,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                } else {
                    $data = [
                        'student_id' => $request->student_id,
                        'allocation_id' => $request->allocation_id,
                        'fees_type_id' => $request->fees_type,
                        'payment_mode_id' => $request->payment_mode,
                        'fees_group_id' => $request->fees_group_id,
                        'payment_status_id' => isset($fees['payment_status']) ? $fees['payment_status'] : 0,
                        'collect_by' => $request->collect_by,
                        'amount' => isset($fees['amount']) ? $fees['amount'] : 0,
                        'yearly' => "1",
                        'discount' => "0",
                        'fine' => "0",
                        'pay_via' => "Cash",
                        'remarks' => isset($fees['memo']) ? $fees['memo'] : "",
                        'date' => isset($fees['date']) ? $fees['date'] : null,
                        'fees_group_details_id' => isset($fees['fees_group_details_id']) ? $fees['fees_group_details_id'] : 0,
                        'academic_session_id' => $request->academic_session_id,
                        'created_at' => date("Y-m-d H:i:s")
                    ];
                    $conn->table('fees_payment_history')->insert($data);
                }
            } else if ($request->payment_mode == "2") {
                foreach ($fees as $fee) {
                    // if (isset($fee['status'])) {
                    $row = $conn->table('fees_payment_history')->select('id')->where([
                        ['student_id', '=', $request->student_id],
                        ['allocation_id', '=', $request->allocation_id],
                        ['fees_type_id', '=', $request->fees_type],
                        ['fees_group_id', '=', $request->fees_group_id],
                        ['payment_mode_id', '=', $request->payment_mode],
                        ['semester', '=', $fee['semester']]
                    ])->first();
                    if (isset($row->id)) {
                        $conn->table('fees_payment_history')->where('id', $row->id)->update([
                            'payment_status_id' => isset($fee['payment_status']) ? $fee['payment_status'] : 0,
                            'collect_by' => $request->collect_by,
                            'amount' => isset($fee['amount']) ? $fee['amount'] : 0,
                            'discount' => "0",
                            'fine' => "0",
                            'pay_via' => "Cash",
                            'remarks' => isset($fee['memo']) ? $fee['memo'] : "",
                            'semester' => $fee['semester'],
                            'date' => isset($fee['date']) ? $fee['date'] : null,
                            'fees_group_details_id' => isset($fee['fees_group_details_id']) ? $fee['fees_group_details_id'] : 0,
                            'academic_session_id' => $request->academic_session_id,
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                    } else {
                        $data = [
                            'student_id' => $request->student_id,
                            'allocation_id' => $request->allocation_id,
                            'fees_type_id' => $request->fees_type,
                            'fees_group_id' => $request->fees_group_id,
                            'payment_mode_id' => $request->payment_mode,
                            'payment_status_id' => isset($fee['payment_status']) ? $fee['payment_status'] : 0,
                            'collect_by' => $request->collect_by,
                            'amount' => isset($fee['amount']) ? $fee['amount'] : 0,
                            'discount' => "0",
                            'fine' => "0",
                            'pay_via' => "Cash",
                            'remarks' => isset($fee['memo']) ? $fee['memo'] : "",
                            'semester' => $fee['semester'],
                            'date' => isset($fee['date']) ? $fee['date'] : null,
                            'fees_group_details_id' => isset($fee['fees_group_details_id']) ? $fee['fees_group_details_id'] : 0,
                            'academic_session_id' => $request->academic_session_id,
                            'created_at' => date("Y-m-d H:i:s")
                        ];
                        $conn->table('fees_payment_history')->insert($data);
                    }
                    // }
                }
            } else if ($request->payment_mode == "3") {
                foreach ($fees as $fee) {
                    // if (isset($fee['status'])) {
                    $row = $conn->table('fees_payment_history')->select('id')->where([
                        ['student_id', '=', $request->student_id],
                        ['allocation_id', '=', $request->allocation_id],
                        ['fees_type_id', '=', $request->fees_type],
                        ['fees_group_id', '=', $request->fees_group_id],
                        ['payment_mode_id', '=', $request->payment_mode],
                        ['monthly', '=', $fee['month']]
                    ])->first();
                    if (isset($row->id)) {
                        $conn->table('fees_payment_history')->where('id', $row->id)->update([
                            'payment_status_id' => isset($fee['payment_status']) ? $fee['payment_status'] : 0,
                            'collect_by' => $request->collect_by,
                            'amount' => isset($fee['amount']) ? $fee['amount'] : 0,
                            'discount' => "0",
                            'fine' => "0",
                            'pay_via' => "Cash",
                            'remarks' => isset($fee['memo']) ? $fee['memo'] : "",
                            'monthly' => $fee['month'],
                            'date' => isset($fee['date']) ? $fee['date'] : null,
                            'fees_group_details_id' => isset($fee['fees_group_details_id']) ? $fee['fees_group_details_id'] : 0,
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                    } else {
                        $data = [
                            'student_id' => $request->student_id,
                            'allocation_id' => $request->allocation_id,
                            'fees_type_id' => $request->fees_type,
                            'fees_group_id' => $request->fees_group_id,
                            'payment_mode_id' => $request->payment_mode,
                            'payment_status_id' => isset($fee['payment_status']) ? $fee['payment_status'] : 0,
                            'collect_by' => $request->collect_by,
                            'amount' => isset($fee['amount']) ? $fee['amount'] : 0,
                            'discount' => "0",
                            'fine' => "0",
                            'pay_via' => "Cash",
                            'remarks' => isset($fee['memo']) ? $fee['memo'] : "",
                            'monthly' => $fee['month'],
                            'date' => isset($fee['date']) ? $fee['date'] : null,
                            'fees_group_details_id' => isset($fee['fees_group_details_id']) ? $fee['fees_group_details_id'] : 0,
                            'academic_session_id' => $request->academic_session_id,
                            'created_at' => date("Y-m-d H:i:s")
                        ];
                        $conn->table('fees_payment_history')->insert($data);
                    }
                    // }
                }
            }
            $query = 1;
            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Fees Group Details have Been updated');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    // get fees change payment mode
    public function feesChangePaymentMode(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'student_id' => 'required',
            'payment_mode' => 'required',
            'fees_type' => 'required',
            'fees_group_id' => 'required',
            'allocation_id' => 'required',
            'academic_session_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            $studentData['fees_payment_details'] = $conn->table('fees_payment_history as fph')
                ->select(
                    'fph.id',
                    'fph.student_id',
                    'fph.allocation_id',
                    'fph.fees_type_id',
                    'fph.monthly',
                    'fph.semester',
                    'fph.yearly',
                    'fph.payment_mode_id',
                    'fph.payment_status_id',
                    'fph.collect_by',
                    'fph.amount',
                    'fph.discount',
                    'fph.fine',
                    'fph.pay_via',
                    'fph.remarks',
                    'fph.date'
                )
                ->where([
                    ['fph.student_id', '=', $request->student_id],
                    ['fph.allocation_id', '=', $request->allocation_id],
                    ['fph.fees_type_id', '=', $request->fees_type],
                    ['fph.fees_group_id', '=', $request->fees_group_id],
                    ['fph.payment_mode_id', '=', $request->payment_mode]
                ])
                ->get();
            // here we get assign amount
            $studentData['amount_details'] = $conn->table('fees_group_details as fgd')
                ->select(
                    // 'fgd.id',
                    'fgd.id as fg_id',
                    'fgd.payment_mode_id',
                    'fgd.monthly',
                    'fgd.semester',
                    'fgd.yearly',
                    'fgd.amount as paying_amount',
                    // 'fgd.payment_mode_id',
                    // 'fgd.due_date',
                )
                ->where([
                    ['fgd.fees_type_id', '=', $request->fees_type],
                    ['fgd.fees_group_id', '=', $request->fees_group_id],
                    ['fgd.payment_mode_id', '=', $request->payment_mode]
                ])
                ->get();
            $studentData['semester_count'] = $conn->table('semester')->get()->count();
            return $this->successResponse($studentData, 'Fees paid fetch successfully');
        }
    }
    // fees Type Group
    public function feesTypeGroup(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'academic_session_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $branchID = $request->branch_id;
            // get data
            // $typeID = (isset($request->type_id) ? $request->type_id : 0);
            $html = "";
            $result = $conn->table('fees_group')
                ->where([
                    ['academic_session_id', '=', $request->academic_session_id]
                ])
                ->get();
            if (count($result)) {
                $html .= "<option value=''>Select</option>";
                foreach ($result as $row) {
                    $html .= '<optgroup label="' . $row->name . '">';
                    $resultdetails = $conn->table('fees_group_details as fgd')
                        ->select(
                            'fgd.fees_group_id',
                            'fgd.fees_type_id',
                            'ft.name as fees_type_name'
                        )
                        ->join('fees_type as ft', 'fgd.fees_type_id', '=', 'ft.id')
                        ->where([
                            ['fgd.fees_group_id', '=', $row->id]
                        ])
                        ->groupBy('fgd.fees_type_id')
                        ->get();
                    foreach ($resultdetails as $t) {
                        // dd($t);
                        // $sel = ($t->fees_group_id . "|" . $t->fees_type_id == $typeID ? 'selected' : '');
                        $html .= '<option value="' . $t->fees_group_id . "|" . $t->fees_type_id . '">' . $t->fees_type_name . '</option>';
                    }
                    $html .= '</optgroup>';
                }
            } else {
                $html .= '<option value="">No Fees Available</option>';
            }
            return $this->successResponse($html, 'Fees type fetch successfully');
        }
    }
    // get fees status check
    public function feesStatusCheck(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'academic_session_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get data
            // get data
            $currentDate = date('Y-m-d');
            $studentData['current_semester'] = $conn->table('semester as sm')
                // ->whereRaw('(now() between start_date and end_date)')
                ->select(
                    'sm.id',
                    'sm.name',
                    'sm.start_date',
                    'sm.end_date'
                )
                ->whereRaw('"' . $currentDate . '" between `start_date` and `end_date`')
                ->first();

            $studentData['all_semester'] = $conn->table('semester as sm')
                ->select(
                    'sm.id',
                    'sm.name',
                    'sm.start_date',
                    'sm.end_date'
                )
                ->where([
                    ['sm.academic_session_id', '=', $request->academic_session_id],
                ])->get();
            $studentData['year_details'] = $conn->table('semester as sm')
                ->select(DB::raw('MIN(sm.start_date) AS year_start_date, MAX(sm.end_date) AS year_end_date'))
                ->where([
                    ['sm.academic_session_id', '=', $request->academic_session_id],
                ])
                ->get();
            return $this->successResponse($studentData, 'Semester yearly record fetch successfully');
        }
    }
    // get active tab fee details
    public function feesActiveTabDetails(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'student_id' => 'required',
            'fees_type' => 'required',
            'allocation_id' => 'required',
            'payment_mode' => 'required',
            'academic_session_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $fees_type = $request->fees_type;
            $studentID = $request->student_id;
            $academic_session_id = $request->academic_session_id;
            // get data
            $retData = $conn->table('fees_allocation as fa')
                ->select(
                    'fa.group_id',
                    'fa.payment_mode_id',
                    'fa.academic_session_id',
                    'fg.amount as assign_amount',
                    'fg.id as fg_id',
                    'fg.monthly',
                    'fg.semester',
                    'fg.yearly',
                    'fph.id',
                    'fph.student_id',
                    'fph.allocation_id',
                    'fph.fees_type_id',
                    'fph.payment_status_id',
                    'fph.collect_by',
                    'fph.amount',
                    'fph.discount',
                    'fph.fine',
                    'fph.pay_via',
                    'fph.remarks',
                    'fph.date',
                )
                ->leftjoin('fees_group_details as fg', function ($join) use ($fees_type) {
                    $join->on('fg.fees_group_id', '=', 'fa.group_id')
                        ->on('fg.payment_mode_id', '=', 'fa.payment_mode_id')
                        ->on('fg.fees_type_id', '=', DB::raw("'$fees_type'"));
                })
                // ->leftjoin('fees_payment_history as fph', function ($join) use ($fees_type) {
                //     $join->on('fph.fees_group_details_id', '=', 'fg.id');
                // })
                ->leftjoin('fees_payment_history as fph', function ($join) use ($studentID, $academic_session_id) {
                    // $join->on('fph.fees_group_id', '=', 'fg.fees_group_id');
                    // $join->on('fph.fees_type_id', '=', 'fg.fees_type_id');
                    // $join->on('fph.payment_mode_id', '=', 'fg.payment_mode_id');
                    $join->on('fph.fees_group_details_id', '=', 'fg.id');
                    $join->on('fph.student_id', '=', DB::raw("'$studentID'"));
                    $join->on('fph.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                // ->leftJoin('fees_allocation as fa', function ($join) use ($fees_group_id, $academic_session_id) {
                //     $join->on('fa.student_id', '=', 'en.student_id')
                //         ->on('fa.group_id', '=', DB::raw("'$fees_group_id'"))
                //         ->on('fa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                // })
                ->where([
                    ['fa.id', '=', $request->allocation_id],
                    ['fa.academic_session_id', '=', $request->academic_session_id],
                ])->get();
            // dd($fees_allocation);
            // $query = $conn->table('fees_payment_history as fph')
            //     ->select(
            //         'fph.id',
            //         'fph.student_id',
            //         'fph.allocation_id',
            //         'fph.fees_type_id',
            //         'fph.monthly',
            //         'fph.semester',
            //         'fph.yearly',
            //         'fph.payment_mode_id',
            //         'fph.payment_status_id',
            //         'fph.collect_by',
            //         'fph.amount',
            //         'fph.discount',
            //         'fph.fine',
            //         'fph.pay_via',
            //         'fph.remarks',
            //         'fph.date',
            //         'fg.amount as assign_amount',
            //         'fg.id as fg_id'
            //     );
            // if ($request->payment_mode == 1) {
            //     $recentquery = clone $query;
            //     $recentquery->leftjoin('fees_group_details as fg', function ($join) {
            //         $join->on('fph.fees_group_id', '=', 'fg.fees_group_id');
            //         $join->on('fph.fees_type_id', '=', 'fg.fees_type_id');
            //         $join->on('fph.payment_mode_id', '=', 'fg.payment_mode_id');
            //         $join->on('fph.yearly', '=', 'fg.yearly');
            //     })
            //         ->where([
            //             ['fph.student_id', '=', $request->student_id],
            //             ['fph.allocation_id', '=', $request->allocation_id],
            //             ['fph.fees_type_id', '=', $request->fees_type],
            //             ['fph.payment_mode_id', '=', $request->payment_mode],
            //         ]);
            //     $retData = $recentquery->get();
            // }
            // if ($request->payment_mode == 2) {
            //     $recentquery = clone $query;
            //     $recentquery->leftjoin('fees_group_details as fg', function ($join) {
            //         $join->on('fph.fees_group_id', '=', 'fg.fees_group_id');
            //         $join->on('fph.fees_type_id', '=', 'fg.fees_type_id');
            //         $join->on('fph.payment_mode_id', '=', 'fg.payment_mode_id');
            //         $join->on('fph.semester', '=', 'fg.semester');
            //     })
            //         ->where([
            //             ['fph.student_id', '=', $request->student_id],
            //             ['fph.allocation_id', '=', $request->allocation_id],
            //             ['fph.fees_type_id', '=', $request->fees_type],
            //             ['fph.payment_mode_id', '=', $request->payment_mode],
            //         ]);
            //     $retData = $recentquery->get();
            // }
            // if ($request->payment_mode == 3) {
            //     $recentquery = clone $query;
            //     $recentquery->leftjoin('fees_group_details as fg', function ($join) {
            //         $join->on('fph.fees_group_id', '=', 'fg.fees_group_id');
            //         $join->on('fph.fees_type_id', '=', 'fg.fees_type_id');
            //         $join->on('fph.payment_mode_id', '=', 'fg.payment_mode_id');
            //         $join->on('fph.monthly', '=', 'fg.monthly');
            //     })
            //         ->where([
            //             ['fph.student_id', '=', $request->student_id],
            //             ['fph.allocation_id', '=', $request->allocation_id],
            //             ['fph.fees_type_id', '=', $request->fees_type],
            //             ['fph.payment_mode_id', '=', $request->payment_mode],
            //         ]);
            //     $retData = $recentquery->get();
            // }

            $studentData['fees_payment_details'] = isset($retData) ? $retData : [];
            // dd($studentData);
            // // here we get assign amount
            // $studentData['amount_details'] = $conn->table('fees_group_details as fgd')
            //     ->select(
            //         // 'fgd.id',
            //         // 'fgd.fees_group_id',
            //         'fgd.amount as paying_amount',
            //         // 'fgd.payment_mode_id',
            //         // 'fgd.due_date',
            //     )
            //     ->where([
            //         ['fgd.fees_type_id', '=', $request->fees_type],
            //         ['fgd.fees_group_id', '=', $request->fees_group_id],
            //         ['fgd.payment_mode_id', '=', $request->payment_mode]
            //     ])
            //     ->first();
            return $this->successResponse($studentData, 'Fees paid fetch successfully');
        }
    }
    // public function feesActiveTabDetails(Request $request)
    // {

    //     $validator = \Validator::make($request->all(), [
    //         'branch_id' => 'required',
    //         'student_id' => 'required',
    //         'fees_type' => 'required',
    //         'allocation_id' => 'required',
    //         'payment_mode' => 'required',
    //         'academic_session_id' => 'required'
    //     ]);

    //     if (!$validator->passes()) {
    //         return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
    //     } else {
    //         // create new connection
    //         $conn = $this->createNewConnection($request->branch_id);
    //         // get data
    //         $query = $conn->table('fees_payment_history as fph')
    //             ->select(
    //                 'fph.id',
    //                 'fph.student_id',
    //                 'fph.allocation_id',
    //                 'fph.fees_type_id',
    //                 'fph.monthly',
    //                 'fph.semester',
    //                 'fph.yearly',
    //                 'fph.payment_mode_id',
    //                 'fph.payment_status_id',
    //                 'fph.collect_by',
    //                 'fph.amount',
    //                 'fph.discount',
    //                 'fph.fine',
    //                 'fph.pay_via',
    //                 'fph.remarks',
    //                 'fph.date',
    //                 'fg.amount as assign_amount',
    //                 'fg.id as fg_id'
    //             );
    //         if ($request->payment_mode == 1) {
    //             $recentquery = clone $query;
    //             $recentquery->leftjoin('fees_group_details as fg', function ($join) {
    //                 $join->on('fph.fees_group_id', '=', 'fg.fees_group_id');
    //                 $join->on('fph.fees_type_id', '=', 'fg.fees_type_id');
    //                 $join->on('fph.payment_mode_id', '=', 'fg.payment_mode_id');
    //                 $join->on('fph.yearly', '=', 'fg.yearly');
    //             })
    //                 ->where([
    //                     ['fph.student_id', '=', $request->student_id],
    //                     ['fph.allocation_id', '=', $request->allocation_id],
    //                     ['fph.fees_type_id', '=', $request->fees_type],
    //                     ['fph.payment_mode_id', '=', $request->payment_mode],
    //                 ]);
    //             $retData = $recentquery->get();
    //         }
    //         if ($request->payment_mode == 2) {
    //             $recentquery = clone $query;
    //             $recentquery->leftjoin('fees_group_details as fg', function ($join) {
    //                 $join->on('fph.fees_group_id', '=', 'fg.fees_group_id');
    //                 $join->on('fph.fees_type_id', '=', 'fg.fees_type_id');
    //                 $join->on('fph.payment_mode_id', '=', 'fg.payment_mode_id');
    //                 $join->on('fph.semester', '=', 'fg.semester');
    //             })
    //                 ->where([
    //                     ['fph.student_id', '=', $request->student_id],
    //                     ['fph.allocation_id', '=', $request->allocation_id],
    //                     ['fph.fees_type_id', '=', $request->fees_type],
    //                     ['fph.payment_mode_id', '=', $request->payment_mode],
    //                 ]);
    //             $retData = $recentquery->get();
    //         }
    //         if ($request->payment_mode == 3) {
    //             $recentquery = clone $query;
    //             $recentquery->leftjoin('fees_group_details as fg', function ($join) {
    //                 $join->on('fph.fees_group_id', '=', 'fg.fees_group_id');
    //                 $join->on('fph.fees_type_id', '=', 'fg.fees_type_id');
    //                 $join->on('fph.payment_mode_id', '=', 'fg.payment_mode_id');
    //                 $join->on('fph.monthly', '=', 'fg.monthly');
    //             })
    //                 ->where([
    //                     ['fph.student_id', '=', $request->student_id],
    //                     ['fph.allocation_id', '=', $request->allocation_id],
    //                     ['fph.fees_type_id', '=', $request->fees_type],
    //                     ['fph.payment_mode_id', '=', $request->payment_mode],
    //                 ]);
    //             $retData = $recentquery->get();
    //         }

    //         $studentData['fees_payment_details'] = isset($retData) ? $retData : [];
    //         // dd($studentData);
    //         // // here we get assign amount
    //         // $studentData['amount_details'] = $conn->table('fees_group_details as fgd')
    //         //     ->select(
    //         //         // 'fgd.id',
    //         //         // 'fgd.fees_group_id',
    //         //         'fgd.amount as paying_amount',
    //         //         // 'fgd.payment_mode_id',
    //         //         // 'fgd.due_date',
    //         //     )
    //         //     ->where([
    //         //         ['fgd.fees_type_id', '=', $request->fees_type],
    //         //         ['fgd.fees_group_id', '=', $request->fees_group_id],
    //         //         ['fgd.payment_mode_id', '=', $request->payment_mode]
    //         //     ])
    //         //     ->first();
    //         return $this->successResponse($studentData, 'Fees paid fetch successfully');
    //     }
    // }
    // already paid student mode id
    public function getPayModeID(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'fees_group_id' => 'required',
            'fees_type' => 'required',
            'allocation_id' => 'required',
            'student_id' => 'required',
            'academic_session_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // here we get assign amount
            $getFeesMode = $conn->table('fees_payment_history as fgd')
                ->select(
                    'fgd.payment_mode_id'
                )
                ->where([
                    ['fgd.fees_type_id', '=', $request->fees_type],
                    ['fgd.fees_group_id', '=', $request->fees_group_id],
                    ['fgd.allocation_id', '=', $request->allocation_id],
                    ['fgd.student_id', '=', $request->student_id],
                    ['fgd.academic_session_id', '=', $request->academic_session_id]
                ])
                ->first();
            return $this->successResponse($getFeesMode, 'get fees amount successfully');
        }
    }
    public function calResult($Array, $student_id)
    {
        array_multisort(
            array_column($Array, 'mark'),
            SORT_DESC,
            $Array
        );
        $Array = $this->calculate_rank($Array);
        $key = array_search($student_id, array_column($Array, 'student_id'));
        return ['student_list' => $Array[$key]];
    }
    //calculate rank for multi dimensional array
    function calculate_rank($rank_values): array
    {
        $newrank = array();
        $newrank = $rank_values;
        $rank = 0;
        $r_last = null;
        foreach ($newrank as $key => $arr) {
            $mark = (int) $arr->mark;
            if ($mark != $r_last) {
                if ($mark > 0) { //if you want to set zero rank for values zero
                    $rank++;
                }
                $r_last = $mark;
            }
            $newrank[$key]->rank = $mark > 0 ? $rank : 0; //if you want to set zero rank for values zero
        }
        return $newrank;
    }
    // all exam subject score
    public function allExamSubjectScores(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'academic_session_id' => 'required',
            'student_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data     
            $academic_session_id = $request->academic_session_id;
            $student_id = $request->student_id;
            $allbysubject = [];
            // current semester
            $getStudentDetails = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.semester_id',
                    'en.session_id',
                    'en.academic_session_id'
                )
                ->where([
                    ['en.student_id', '=', $student_id],
                    ['en.academic_session_id', '=', $academic_session_id],
                    ['en.active_status', '=', '0']
                ])
                ->first();

            if ($request->class_id) {

                $student_class_id = $request->class_id;
            } else {
                $student_class_id = isset($getStudentDetails->class_id) ? $getStudentDetails->class_id : 0;
            }
            if ($request->section_id) {
                $student_section_id = $request->section_id;
            } else {
                $student_section_id = isset($getStudentDetails->section_id) ? $getStudentDetails->section_id : 0;
            }
            // $student_class_id = isset($getStudentDetails->class_id) ? $getStudentDetails->class_id : 0;
            // $student_section_id = isset($getStudentDetails->section_id) ? $getStudentDetails->section_id : 0;
            $student_semester_id = isset($getStudentDetails->semester_id) ? $getStudentDetails->semester_id : 0;
            $student_session_id = isset($getStudentDetails->session_id) ? $getStudentDetails->session_id : 0;
            // here we get all academic exams
            // get all exam
            $getAllExam = $Connection->table('timetable_exam as texm')
                ->select(
                    'texm.exam_id',
                    'ex.name',
                    'texm.semester_id',
                    'texm.session_id',
                )
                ->join('exam as ex', 'texm.exam_id', '=', 'ex.id')
                ->where([
                    ['texm.class_id', '=', $student_class_id],
                    ['texm.section_id', '=', $student_section_id],
                    // ['texm.semester_id', '=', $student_semester_id],
                    // ['texm.session_id', '=', $student_session_id],
                    ['texm.academic_session_id', '=', $academic_session_id]
                ])
                ->groupBy('texm.exam_id')
                ->get();
            // dd($getAllExam);
            // get all exams
            if (!empty($getAllExam)) {
                foreach ($getAllExam as $exm) {
                    $object = new \stdClass();
                    $exam_id = isset($exm->exam_id) ? $exm->exam_id : 0;
                    $exam_name = isset($exm->name) ? $exm->name : 0;
                    $exam_semester_id = isset($exm->semester_id) ? $exm->semester_id : 0;
                    $exam_session_id = isset($exm->session_id) ? $exm->session_id : 0;
                    // $object->exam_id = $exam_id;
                    $object->exam_name = $exam_name;
                    // get total recent subject teacher
                    $total_sujects_teacher = $Connection->table('subject_assigns as sa')
                        ->select(
                            'sbj.id as subject_id',
                            'sbj.name as subject_name'
                        )
                        ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                        ->where([
                            ['sa.class_id', $student_class_id],
                            ['sa.academic_session_id', $academic_session_id],
                            ['sa.type', '=', '0'],
                            ['sbj.exam_exclude', '=', '0']
                        ])
                        ->groupBy('sa.subject_id')
                        ->orderBy('sa.id', 'asc')
                        ->get();
                    $studentArr = [];
                    if (!empty($total_sujects_teacher)) {
                        foreach ($total_sujects_teacher as $val) {
                            $sbj_obj = new \stdClass();
                            $subject_id = $val->subject_id;
                            $subject_name = $val->subject_name;
                            $sbj_obj->subject_id = $subject_id;
                            $sbj_obj->subject_name = $subject_name;
                            // all section list
                            // get subject total weightage
                            $getExamPaperWeightage = $Connection->table('exam_papers as expp')
                                ->select(
                                    DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                                    'expp.grade_category'
                                )
                                ->where([
                                    ['expp.class_id', '=', $student_class_id],
                                    ['expp.subject_id', '=', $subject_id],
                                    ['expp.academic_session_id', $academic_session_id]
                                ])
                                ->get();
                            $total_subject_weightage = isset($getExamPaperWeightage[0]->total_subject_weightage) ? (int)$getExamPaperWeightage[0]->total_subject_weightage : 0;
                            $getStudMarksDetails = $Connection->table('student_marks as sm')
                                ->select(
                                    'expp.subject_weightage',
                                    'sb.name as subject_name',
                                    'sb.id as subject_id',
                                    'sm.score',
                                    'sm.paper_id',
                                    'sm.grade_category'
                                )
                                ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                                ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                                ->where([
                                    ['sm.class_id', '=', $student_class_id],
                                    ['sm.section_id', '=', $student_section_id],
                                    ['sm.subject_id', '=', $subject_id],
                                    ['sm.exam_id', '=', $exam_id],
                                    // ['sm.semester_id', '=', $student_semester_id],
                                    // ['sm.session_id', '=', $student_session_id],
                                    ['sm.semester_id', '=', $exam_semester_id],
                                    ['sm.session_id', '=', $exam_session_id],
                                    ['sm.student_id', '=', $student_id],
                                    ['sm.academic_session_id', '=', $academic_session_id]
                                ])
                                ->groupBy('sm.paper_id')
                                ->get();
                            // echo "<pre>";
                            // print_r($getStudMarksDetails);
                            $marks = 0;
                            // // here you get calculation based on student marks and subject weightage
                            if (!empty($getStudMarksDetails)) {
                                // grade calculations
                                foreach ($getStudMarksDetails as $Studmarks) {
                                    $sub_weightage = (int) $Studmarks->subject_weightage;
                                    $score = (int) $Studmarks->score;
                                    // foreach for total no of students
                                    $weightage = ($sub_weightage / $total_subject_weightage);
                                    $marks += ($weightage * $score);
                                }
                                $mark = (int) $marks;
                                $sbj_obj->mark = $mark != 0 ? number_format($mark) : $mark;
                            } else {
                                $sbj_obj->mark = "Nill";
                            }
                            array_push($studentArr, $sbj_obj);
                        }
                    }
                    // calculate ranking
                    $object->exam_marks = $studentArr;
                    array_push($allbysubject, $object);
                }
            }
            return $this->successResponse($allbysubject, 'get exam subject marks successfully');
        }
    }
    // all exam subject ranks
    public function allExamSubjectRanks(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'academic_session_id' => 'required',
            'student_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data     
            $academic_session_id = $request->academic_session_id;
            $student_id = $request->student_id;
            $allbysubject = [];
            $getStudentDetails = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.semester_id',
                    'en.session_id',
                    'en.academic_session_id'
                )
                ->where([
                    ['en.student_id', '=', $student_id],
                    ['en.academic_session_id', '=', $academic_session_id],
                    ['en.active_status', '=', '0']
                ])
                ->first();
            $student_class_id = isset($getStudentDetails->class_id) ? $getStudentDetails->class_id : 0;
            $student_section_id = isset($getStudentDetails->section_id) ? $getStudentDetails->section_id : 0;
            $student_semester_id = isset($getStudentDetails->semester_id) ? $getStudentDetails->semester_id : 0;
            $student_session_id = isset($getStudentDetails->session_id) ? $getStudentDetails->session_id : 0;
            // here we get all academic exams
            // get all exam
            $getAllExam = $Connection->table('timetable_exam as texm')
                ->select(
                    'texm.exam_id',
                    'ex.name',
                    'texm.semester_id',
                    'texm.session_id'
                )
                ->join('exam as ex', 'texm.exam_id', '=', 'ex.id')
                ->where([
                    ['texm.class_id', '=', $student_class_id],
                    ['texm.section_id', '=', $student_section_id],
                    // ['texm.semester_id', '=', $student_semester_id],
                    // ['texm.session_id', '=', $student_session_id],
                    ['texm.academic_session_id', '=', $academic_session_id]
                ])
                ->groupBy('texm.exam_id')
                ->get();
            if (!empty($getAllExam)) {
                foreach ($getAllExam as $exm) {
                    $object = new \stdClass();
                    $exam_id = isset($exm->exam_id) ? $exm->exam_id : 0;
                    $exam_name = isset($exm->name) ? $exm->name : 0;
                    $exam_semester_id = isset($exm->semester_id) ? $exm->semester_id : 0;
                    $exam_session_id = isset($exm->session_id) ? $exm->session_id : 0;
                    // $object->exam_id = $exam_id;
                    $object->exam_name = $exam_name;
                    // get total recent subject teacher
                    $total_sujects_teacher = $Connection->table('subject_assigns as sa')
                        ->select(
                            'sbj.id as subject_id',
                            'sbj.name as subject_name'
                        )
                        ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                        ->where([
                            ['sa.class_id', $student_class_id],
                            ['sa.academic_session_id', $academic_session_id],
                            ['sa.type', '=', '0'],
                            ['sbj.exam_exclude', '=', '0']
                        ])
                        ->groupBy('sa.subject_id')
                        ->orderBy('sa.id', 'asc')
                        ->get();
                    $studentArr = [];
                    if (!empty($total_sujects_teacher)) {
                        foreach ($total_sujects_teacher as $val) {
                            $subjectArr = [];
                            $sbj_obj = new \stdClass();
                            $subject_id = $val->subject_id;
                            $subject_name = $val->subject_name;
                            $sbj_obj->subject_id = $subject_id;
                            $sbj_obj->subject_name = $subject_name;
                            // all section list
                            // get subject total weightage
                            $getExamPaperWeightage = $Connection->table('exam_papers as expp')
                                ->select(
                                    DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                                    'expp.grade_category'
                                )
                                ->where([
                                    ['expp.class_id', '=', $student_class_id],
                                    ['expp.subject_id', '=', $subject_id],
                                    ['expp.academic_session_id', $academic_session_id]
                                ])
                                ->get();
                            $total_subject_weightage = isset($getExamPaperWeightage[0]->total_subject_weightage) ? (int)$getExamPaperWeightage[0]->total_subject_weightage : 0;
                            // here we get all students mark with same class
                            $studentDetails = $Connection->table('enrolls as en')
                                ->select(
                                    'en.student_id',
                                    'en.semester_id',
                                    'en.session_id'
                                )
                                ->where([
                                    ['en.class_id', $student_class_id],
                                    ['en.section_id', $student_section_id],
                                    ['en.academic_session_id', '=', $academic_session_id],
                                    ['en.semester_id', '=', $student_semester_id],
                                    ['en.session_id', '=', $student_session_id]
                                ])
                                ->get();
                            if (!empty($studentDetails)) {
                                foreach ($studentDetails as $student) {
                                    $all_stud_obj = new \stdClass();

                                    $studentID = $student->student_id;
                                    $all_stud_obj->student_id = $studentID;
                                    $all_stud_obj->class_id = $student_class_id;
                                    $all_stud_obj->section_id = $student_section_id;
                                    $getStudMarksDetails = $Connection->table('student_marks as sm')
                                        ->select(
                                            'expp.subject_weightage',
                                            'sb.name as subject_name',
                                            'sb.id as subject_id',
                                            'sm.score',
                                            'sm.paper_id',
                                            'sm.grade_category'
                                        )
                                        ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                                        ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                                        ->where([
                                            ['sm.class_id', '=', $student_class_id],
                                            ['sm.section_id', '=', $student_section_id],
                                            ['sm.subject_id', '=', $subject_id],
                                            ['sm.exam_id', '=', $exam_id],
                                            // ['sm.semester_id', '=', $student_semester_id],
                                            // ['sm.session_id', '=', $student_session_id],
                                            ['sm.semester_id', '=', $exam_semester_id],
                                            ['sm.session_id', '=', $exam_session_id],
                                            ['sm.student_id', '=', $studentID],
                                            ['sm.academic_session_id', '=', $academic_session_id]
                                        ])
                                        ->groupBy('sm.paper_id')
                                        ->get();
                                    $marks = 0;
                                    // // here you get calculation based on student marks and subject weightage
                                    if (!empty($getStudMarksDetails)) {
                                        // grade calculations
                                        foreach ($getStudMarksDetails as $Studmarks) {
                                            $sub_weightage = (int) $Studmarks->subject_weightage;
                                            $score = (int) $Studmarks->score;
                                            // foreach for total no of students
                                            $weightage = ($sub_weightage / $total_subject_weightage);
                                            $marks += ($weightage * $score);
                                        }
                                        $mark = (int) $marks;
                                        $all_stud_obj->mark = $mark != 0 ? number_format($mark) : $mark;
                                    } else {
                                        $all_stud_obj->mark = "Nill";
                                    }
                                    array_push($subjectArr, $all_stud_obj);
                                }
                            }
                            // calculate ranking
                            $rank = $this->calResult($subjectArr, $student_id);
                            $sbj_obj->rank = $rank['student_list'];
                            array_push($studentArr, $sbj_obj);
                        }
                    }
                    // calculate ranking
                    $object->exam_rank = $studentArr;
                    array_push($allbysubject, $object);
                }
            }
            return $this->successResponse($allbysubject, 'get exam subject rank successfully');
        }
    }
    // exam marks by high avg low
    public function examMarksByHighAvgLow(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'academic_session_id' => 'required',
            'student_id' => 'required',
            'exam_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data     
            $academic_session_id = $request->academic_session_id;
            $student_id = $request->student_id;
            $exam_id = $request->exam_id;
            $studentArr = [];
            $getStudentDetails = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.semester_id',
                    'en.session_id',
                    'en.academic_session_id'
                )
                ->where([
                    ['en.student_id', '=', $student_id],
                    ['en.academic_session_id', '=', $academic_session_id],
                    ['en.active_status', '=', '0']
                ])
                ->first();
            $student_class_id = isset($getStudentDetails->class_id) ? $getStudentDetails->class_id : 0;
            $student_section_id = isset($getStudentDetails->section_id) ? $getStudentDetails->section_id : 0;
            $student_semester_id = isset($getStudentDetails->semester_id) ? $getStudentDetails->semester_id : 0;
            $student_session_id = isset($getStudentDetails->session_id) ? $getStudentDetails->session_id : 0;
            // get total recent subject teacher
            $total_sujects_teacher = $Connection->table('subject_assigns as sa')
                ->select(
                    'sbj.id as subject_id',
                    'sbj.name as subject_name'
                )
                ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                ->where([
                    ['sa.class_id', $student_class_id],
                    ['sa.academic_session_id', $academic_session_id],
                    ['sa.type', '=', '0'],
                    ['sbj.exam_exclude', '=', '0']
                ])
                ->groupBy('sa.subject_id')
                ->orderBy('sa.id', 'asc')
                ->get();
            if (!empty($total_sujects_teacher)) {
                foreach ($total_sujects_teacher as $val) {
                    $subjectArr = [];
                    $object = new \stdClass();
                    $subject_id = $val->subject_id;
                    $subject_name = $val->subject_name;
                    $object->subject_id = $subject_id;
                    $object->subject_name = $subject_name;
                    // all section list
                    // get subject total weightage
                    $getExamPaperWeightage = $Connection->table('exam_papers as expp')
                        ->select(
                            DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                            'expp.grade_category'
                        )
                        ->where([
                            ['expp.class_id', '=', $student_class_id],
                            ['expp.subject_id', '=', $subject_id],
                            ['expp.academic_session_id', $academic_session_id]
                        ])
                        ->get();
                    $total_subject_weightage = isset($getExamPaperWeightage[0]->total_subject_weightage) ? (int)$getExamPaperWeightage[0]->total_subject_weightage : 0;
                    // here we get all students mark with same class
                    $studentDetails = $Connection->table('enrolls as en')
                        ->select(
                            'en.student_id',
                            'en.semester_id',
                            'en.session_id'
                        )
                        ->where([
                            ['en.class_id', $student_class_id],
                            ['en.section_id', $student_section_id],
                            ['en.academic_session_id', '=', $academic_session_id],
                            ['en.semester_id', '=', $student_semester_id],
                            ['en.session_id', '=', $student_session_id]
                        ])
                        ->get();
                    $totalStudent = count($studentDetails);
                    // dd($studentDetails);
                    if (!empty($studentDetails)) {
                        foreach ($studentDetails as $student) {
                            $all_stud_obj = new \stdClass();

                            $studentID = $student->student_id;
                            $all_stud_obj->student_id = $studentID;
                            $getStudMarksDetails = $Connection->table('student_marks as sm')
                                ->select(
                                    'expp.subject_weightage',
                                    'sb.name as subject_name',
                                    'sb.id as subject_id',
                                    'sm.score',
                                    'sm.paper_id',
                                    'sm.grade_category'
                                )
                                ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                                ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                                ->where([
                                    ['sm.class_id', '=', $student_class_id],
                                    ['sm.section_id', '=', $student_section_id],
                                    ['sm.subject_id', '=', $subject_id],
                                    ['sm.exam_id', '=', $exam_id],
                                    ['sm.semester_id', '=', $student_semester_id],
                                    ['sm.session_id', '=', $student_session_id],
                                    ['sm.student_id', '=', $studentID],
                                    ['sm.academic_session_id', '=', $academic_session_id]
                                ])
                                ->groupBy('sm.paper_id')
                                ->get();
                            $marks = 0;
                            // // here you get calculation based on student marks and subject weightage
                            if (!empty($getStudMarksDetails)) {
                                // grade calculations
                                foreach ($getStudMarksDetails as $Studmarks) {
                                    $sub_weightage = (int) $Studmarks->subject_weightage;
                                    $score = (int) $Studmarks->score;
                                    // foreach for total no of students
                                    $weightage = ($sub_weightage / $total_subject_weightage);
                                    $marks += ($weightage * $score);
                                }
                                $mark = (int) $marks;
                                $all_stud_obj->mark = $mark != 0 ? number_format($mark) : $mark;
                            } else {
                                $all_stud_obj->mark = "Nill";
                            }
                            array_push($subjectArr, $all_stud_obj);
                        }
                    }
                    // here we calculate maximum,minimum,avg marks by student start
                    $max = max(array_column($subjectArr, 'mark'));
                    $min = min(array_column($subjectArr, 'mark'));
                    $sum = array_sum(array_column($subjectArr, 'mark'));
                    $avg = ($sum / $totalStudent);
                    $key = array_search($student_id, array_column($subjectArr, 'student_id'));
                    $student_mark = isset($subjectArr[$key]->mark) ? $subjectArr[$key]->mark : 0;
                    // here we calculate maximum,minimum,avg marks by student end
                    $object->mark = $student_mark;
                    $object->max = $max;
                    $object->min = $min;
                    $object->avg = number_format($avg);
                    array_push($studentArr, $object);
                }
            }

            return $this->successResponse($studentArr, 'get subject mark average successfully');
        }
    }
    public function examByStudent(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'student_id' => 'required',
            'academic_session_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);

            $today = date('Y-m-d');
            // $today = date('Y-m-d', strtotime($request->today));
            $student = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.session_id',
                    'en.semester_id'
                )
                ->where([
                    ['en.student_id', '=', $request->student_id],
                    ['en.academic_session_id', '=', $request->academic_session_id],
                    // get active session
                    ['en.active_status', '=', '0']
                ])
                ->groupBy('en.student_id')
                ->first();
            $getExamsName = [];
            if ($student) {
                $class_id = $student->class_id;
                $section_id = $student->section_id;
                $getExamsName = $Connection->table('timetable_exam as texm')
                    ->select(
                        'texm.exam_id as id',
                        'ex.name as name',
                        'texm.exam_date'
                    )
                    ->leftJoin('exam as ex', 'texm.exam_id', '=', 'ex.id')
                    ->where('texm.exam_date', '<', $today)
                    ->when($class_id != "All", function ($q)  use ($class_id) {
                        $q->where('texm.class_id', $class_id);
                    })
                    ->where('texm.section_id', '=', $section_id)
                    ->where('texm.academic_session_id', '=', $request->academic_session_id)
                    ->groupBy('texm.exam_id')
                    ->get();
            }

            return $this->successResponse($getExamsName, 'Exams  list of Name record fetch successfully');
        }
    }

    // student marks 
    public function getMarksByStudent(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'academic_session_id' => 'required',
            'student_id' => 'required',
            'exam_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data     
            $academic_session_id = $request->academic_session_id;
            $exam_id = $request->exam_id;
            $student_id = $request->student_id;
            $allbysubject = [];
            // current semester
            $getStudentDetails = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.class_id',
                    'en.section_id',
                    'en.semester_id',
                    'en.session_id',
                    'en.academic_session_id'
                )
                ->where([
                    ['en.student_id', '=', $student_id],
                    ['en.academic_session_id', '=', $academic_session_id],
                    ['en.active_status', '=', '0']
                ])
                ->first();
            // dd($getStudentDetails);
            if ($request->class_id && $request->section_id) {

                $class_id = $request->class_id;
                $student_section_id = $request->section_id;
            } else {
                $class_id = isset($getStudentDetails->class_id) ? $getStudentDetails->class_id : 0;
                $student_section_id = isset($getStudentDetails->section_id) ? $getStudentDetails->section_id : 0;
            }
            $student_semester_id = isset($request->semester_id) ? $request->semester_id : 0;
            $student_session_id = isset($request->session_id) ? $request->session_id : 0;
            // $student_semester_id = isset($request->semester_id) ? $request->semester_id : 0;
            // $student_session_id = isset($request->session_id) ? $request->session_id : 0;

            // get total recent subject teacher
            $total_sujects_teacher = $Connection->table('subject_assigns as sa')
                ->select(
                    DB::raw("group_concat(sa.section_id) as all_section_id"),
                    'sbj.id as subject_id',
                    'sbj.name as subject_name'
                )
                ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                ->where([
                    ['sa.class_id', $class_id],
                    ['sa.section_id', $student_section_id],
                    ['sa.academic_session_id', $academic_session_id],
                    ['sa.type', '=', '0'],
                    ['sbj.exam_exclude', '=', '0']
                ])
                ->groupBy('sa.subject_id')
                ->get();
            // dd($total_sujects_teacher);
            $allSections = isset($total_sujects_teacher[0]->all_section_id) ? explode(',', $total_sujects_teacher[0]->all_section_id) : [];

            $total_marks = [];
            $rank = '';
            if (!empty($total_sujects_teacher)) {
                foreach ($total_sujects_teacher as $skey => $val) {
                    $studentArr = [];
                    $object = new \stdClass();
                    // $all_section_id = explode(',', $val->all_section_id);
                    $subject_id = $val->subject_id;
                    $subject_name = $val->subject_name;

                    // $object->class_id = $class_id;
                    $object->subject_id = $subject_id;
                    $object->subject_name = $subject_name;
                    // all section list
                    // get subject total weightage
                    $getExamPaperWeightage = $Connection->table('exam_papers as expp')
                        ->select(
                            DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                            'expp.grade_category'
                        )
                        ->where([
                            ['expp.class_id', '=', $class_id],
                            ['expp.subject_id', '=', $subject_id],
                            ['expp.academic_session_id', $academic_session_id]
                        ])
                        ->get();
                    // dd($class_id);
                    $total_subject_weightage = isset($getExamPaperWeightage[0]->total_subject_weightage) ? (int)$getExamPaperWeightage[0]->total_subject_weightage : 0;
                    // get last exam
                    // $getLastExam = $Connection->table('timetable_exam as texm')
                    //     ->select(
                    //         'texm.exam_id'
                    //     )
                    //     ->where([
                    //         ['texm.exam_id', '=', $exam_id],
                    //         ['texm.class_id', '=', $class_id],
                    //         ['texm.section_id', '=', $student_section_id],
                    //         ['texm.subject_id', '=', $subject_id],
                    //         ['texm.semester_id', '=', $student_semester_id],
                    //         ['texm.session_id', '=', $student_session_id],
                    //         ['texm.academic_session_id', '=', $academic_session_id]
                    //     ])
                    //     ->orderBy('texm.exam_date', 'desc')
                    //     ->first();
                    $getLastExam = new \stdClass();
                    $getLastExam->exam_id = $exam_id;
                    // dd($getLastExam);


                    // $exam_id = isset($getLastExam->exam_id) ? $getLastExam->exam_id : 0;
                    foreach ($allSections as $key => $section) {
                        $studentDetails = $Connection->table('enrolls as en')
                            ->select(
                                'en.student_id',
                                'en.semester_id',
                                'en.session_id'
                            )
                            ->where([
                                ['en.class_id', $class_id],
                                ['en.section_id', $section],
                                ['en.academic_session_id', '=', $academic_session_id],
                                ['en.semester_id', '=', $student_semester_id],
                                ['en.session_id', '=', $student_session_id]
                            ])
                            ->get();
                        // dd($studentDetails);
                        $semester_id = isset($studentDetails[0]->semester_id) ? $studentDetails[0]->semester_id : 0;
                        $session_id = isset($studentDetails[0]->session_id) ? $studentDetails[0]->session_id : 0;
                        if (!empty($studentDetails)) {
                            foreach ($studentDetails as $student) {
                                $sbj_obj = new \stdClass();

                                $studentID = $student->student_id;
                                // $total_marks['student_id'] = $studentID;
                                $sbj_obj->student_id = $studentID;
                                $sbj_obj->class_id = $class_id;
                                $sbj_obj->section_id = $section;
                                $getStudMarksDetails = $Connection->table('student_marks as sm')
                                    ->select(
                                        'expp.subject_weightage',
                                        'sb.name as subject_name',
                                        'sb.id as subject_id',
                                        'sm.score',
                                        'sm.paper_id',
                                        'sm.grade_category'
                                    )
                                    ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                                    ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                                    ->where([
                                        ['sm.class_id', '=', $class_id],
                                        ['sm.section_id', '=', $section],
                                        ['sm.subject_id', '=', $subject_id],
                                        ['sm.exam_id', '=', $exam_id],
                                        ['sm.semester_id', '=', $semester_id],
                                        ['sm.session_id', '=', $session_id],
                                        ['sm.student_id', '=', $studentID],
                                        ['sm.academic_session_id', '=', $academic_session_id]
                                    ])
                                    ->groupBy('sm.paper_id')
                                    ->get();
                                $marks = 0;
                                $fail = 0;
                                // // here you get calculation based on student marks and subject weightage
                                if (!empty($getStudMarksDetails)) {
                                    // grade calculations
                                    foreach ($getStudMarksDetails as $Studmarks) {
                                        $sub_weightage = (int) $Studmarks->subject_weightage;
                                        $score = (int) $Studmarks->score;
                                        $grade_category = $Studmarks->grade_category;
                                        // foreach for total no of students
                                        $weightage = ($sub_weightage / $total_subject_weightage);

                                        $marks += ($weightage * $score);
                                    }
                                    $mark = (int) $marks;
                                    if ($skey == 0) {

                                        $total_marks[$studentID]['mark'] = $mark;
                                        if ($mark == 0) {
                                            $fail++;
                                        }
                                        $total_marks[$studentID]['fail'] = $fail;
                                    } else {
                                        $total_marks[$studentID]['mark'] += $mark;
                                        if ($mark == 0) {
                                            $fail++;
                                        }
                                        $total_marks[$studentID]['fail'] += $fail;
                                    }
                                    $sbj_obj->mark = $mark != 0 ? number_format($mark) : $mark;
                                } else {
                                    $sbj_obj->mark = "Nill";
                                }
                                array_push($studentArr, $sbj_obj);
                            }
                        }

                        // sort by mark score
                        array_multisort(
                            array_column($studentArr, 'mark'),
                            SORT_DESC,
                            $studentArr
                        );
                        $studentAr = $this->calculate_rank($studentArr);
                        $key = array_search($student_id, array_column($studentAr, 'student_id'));
                    }
                    if ($studentArr) {
                        $object->mark = $studentArr[$key]->mark;
                        $object->rank = $studentArr[$key]->rank;
                    } else {
                        $object->mark = 0;
                        $object->rank = 0;
                    }
                    array_push($allbysubject, $object);
                }
                $class_rank = collect($total_marks)->sortByDesc('mark')->all();
                // dd($class_rank);
                if ($class_rank) {

                    $student_rank = $this->calculate_overall_rank($class_rank);
                }
                if (isset($student_rank[$student_id])) {

                    $rank = $student_rank[$student_id];
                }
            }

            $data = [
                'details' => $allbysubject,
                'rank' => $rank
            ];

            return $this->successResponse($data, 'All student grade and classes row fetch successfully');
        }
    }
    public function calculate_overall_rank($marks): array
    {
        $last_mark = 0;
        $rank = 0;
        $same_rank = 1;
        foreach ($marks as $key => $mark) {
            if ($mark['fail'] > 0) {
                $marks[$key]['rank'] = "N/A";
            } else {
                if ($mark['mark'] != $last_mark) {
                    $rank = $same_rank;
                }
                $last_mark = $mark['mark'];
                $marks[$key]['rank'] = $rank;
                $same_rank++;
            }
            // dd($last_mark);

        }
        return $marks;
    }

    // top 10 student marks 
    public function getTenStudent(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'academic_session_id' => 'required',
            'exam_id' => 'required',
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data     
            $academic_session_id = $request->academic_session_id;
            $exam_id = $request->exam_id;
            $class_id = $request->class_id;
            $student_section_id = $request->section_id;
            $student_semester_id = isset($request->semester_id) ? $request->semester_id : 0;
            $student_session_id = isset($request->session_id) ? $request->session_id : 0;

            // get total recent subject teacher
            $total_sujects_teacher = $Connection->table('subject_assigns as sa')
                ->select(
                    DB::raw("group_concat(sa.section_id) as all_section_id"),
                    'sbj.id as subject_id',
                    'sbj.name as subject_name'
                )
                ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                ->where([
                    ['sa.class_id', $class_id],
                    ['sa.section_id', $student_section_id],
                    ['sa.academic_session_id', $academic_session_id],
                    ['sa.type', '=', '0'],
                    ['sbj.exam_exclude', '=', '0']
                ])
                ->groupBy('sa.subject_id')
                ->get();
            // dd($total_sujects_teacher);
            $allSections = isset($total_sujects_teacher[0]->all_section_id) ? explode(',', $total_sujects_teacher[0]->all_section_id) : [];

            $total_marks = [];
            $rank = '';
            if (!empty($total_sujects_teacher)) {
                foreach ($total_sujects_teacher as $skey => $val) {
                    $studentArr = [];
                    $object = new \stdClass();
                    // $all_section_id = explode(',', $val->all_section_id);
                    $subject_id = $val->subject_id;
                    $subject_name = $val->subject_name;

                    // $object->class_id = $class_id;
                    $object->subject_id = $subject_id;
                    $object->subject_name = $subject_name;
                    // all section list
                    // get subject total weightage
                    $getExamPaperWeightage = $Connection->table('exam_papers as expp')
                        ->select(
                            DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                            'expp.grade_category'
                        )
                        ->where([
                            ['expp.class_id', '=', $class_id],
                            ['expp.subject_id', '=', $subject_id],
                            ['expp.academic_session_id', $academic_session_id]
                        ])
                        ->get();
                    // dd($class_id);
                    $total_subject_weightage = isset($getExamPaperWeightage[0]->total_subject_weightage) ? (int)$getExamPaperWeightage[0]->total_subject_weightage : 0;
                    // get last exam
                    // $getLastExam = $Connection->table('timetable_exam as texm')
                    //     ->select(
                    //         'texm.exam_id'
                    //     )
                    //     ->where([
                    //         ['texm.exam_id', '=', $exam_id],
                    //         ['texm.class_id', '=', $class_id],
                    //         ['texm.section_id', '=', $student_section_id],
                    //         ['texm.subject_id', '=', $subject_id],
                    //         ['texm.semester_id', '=', $student_semester_id],
                    //         ['texm.session_id', '=', $student_session_id],
                    //         ['texm.academic_session_id', '=', $academic_session_id]
                    //     ])
                    //     ->orderBy('texm.exam_date', 'desc')
                    //     ->first();
                    $getLastExam = new \stdClass();
                    $getLastExam->exam_id = $exam_id;
                    // dd($getLastExam);


                    // $exam_id = isset($getLastExam->exam_id) ? $getLastExam->exam_id : 0;
                    foreach ($allSections as $key => $section) {
                        $studentDetails = $Connection->table('enrolls as en')
                            ->select(
                                'en.student_id',
                                'en.semester_id',
                                'en.session_id',
                                DB::raw('CONCAT(stu.first_name, " ", stu.last_name) as student_name'),
                            )
                            ->join('students as stu', 'en.student_id', '=', 'stu.id')
                            ->where([
                                ['en.class_id', $class_id],
                                ['en.section_id', $section],
                                ['en.academic_session_id', '=', $academic_session_id],
                                ['en.semester_id', '=', $student_semester_id],
                                ['en.session_id', '=', $student_session_id]
                            ])
                            ->get();
                        $semester_id = isset($studentDetails[0]->semester_id) ? $studentDetails[0]->semester_id : 0;
                        $session_id = isset($studentDetails[0]->session_id) ? $studentDetails[0]->session_id : 0;
                        if (!empty($studentDetails)) {
                            foreach ($studentDetails as $student) {
                                $sbj_obj = new \stdClass();

                                $studentID = $student->student_id;
                                $studentName = $student->student_name;
                                // $total_marks['student_id'] = $studentID;
                                $sbj_obj->student_id = $studentID;
                                $sbj_obj->class_id = $class_id;
                                $sbj_obj->class_id = $class_id;
                                $sbj_obj->section_id = $section;
                                $sbj_obj->student_name = $studentName;
                                $getStudMarksDetails = $Connection->table('student_marks as sm')
                                    ->select(
                                        'expp.subject_weightage',
                                        'sb.name as subject_name',
                                        'sb.id as subject_id',
                                        'sm.score',
                                        'sm.paper_id',
                                        'sm.grade_category',
                                    )
                                    ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                                    ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                                    ->where([
                                        ['sm.class_id', '=', $class_id],
                                        ['sm.section_id', '=', $section],
                                        ['sm.subject_id', '=', $subject_id],
                                        ['sm.exam_id', '=', $exam_id],
                                        ['sm.semester_id', '=', $semester_id],
                                        ['sm.session_id', '=', $session_id],
                                        ['sm.student_id', '=', $studentID],
                                        ['sm.academic_session_id', '=', $academic_session_id]
                                    ])
                                    ->groupBy('sm.paper_id')
                                    ->get();
                                $marks = 0;
                                $$total_subject_weightage = 0;
                                $fail = 0;
                                // // here you get calculation based on student marks and subject weightage
                                if (!empty($getStudMarksDetails)) {
                                    // grade calculations
                                    foreach ($getStudMarksDetails as $Studmarks) {
                                        $sub_weightage = (int) $Studmarks->subject_weightage;
                                        $score = (int) $Studmarks->score;
                                        $grade_category = $Studmarks->grade_category;
                                        // foreach for total no of students
                                        $weightage = ($sub_weightage / $total_subject_weightage);
                                        $marks += ($weightage * $score);
                                    }
                                    $mark = (int) $marks;
                                    if ($skey == 0) {

                                        $total_marks[$studentID]['mark'] = $mark;
                                        $total_marks[$studentID]['total_mark'] = $total_subject_weightage;
                                        if ($mark == 0) {
                                            $fail++;
                                        }
                                        $total_marks[$studentID]['fail'] = $fail;
                                    } else {
                                        $total_marks[$studentID]['mark'] += $mark;
                                        $total_marks[$studentID]['total_mark'] += $total_subject_weightage;
                                        if ($mark == 0) {
                                            $fail++;
                                        }
                                        $total_marks[$studentID]['fail'] += $fail;
                                    }
                                    $sbj_obj->mark = $mark != 0 ? number_format($mark) : $mark;
                                } else {
                                    $sbj_obj->mark = "Nill";
                                    $sbj_obj->total_subject_weightage = "Nill";
                                }
                                array_push($studentArr, $sbj_obj);
                            }
                        }

                        // sort by mark score
                        array_multisort(
                            array_column($studentArr, 'mark'),
                            SORT_DESC,
                            $studentArr
                        );
                        $studentAr = $this->calculate_rank($studentArr);
                        // dd($studentArr);
                        // $key = array_search($student_id, array_column($studentAr, 'student_id'));
                    }
                }
                $class_rank = collect($total_marks)->sortByDesc('mark')->all();
                // dd($class_rank);
                if ($class_rank) {

                    $student_rank = $this->calculate_overall_rank($class_rank);
                }

                // foreach($student_rank as $stu=>$student){
                //     dd($studentArr[$stu]);
                //     $studentArr[$stu]->mark = $student['mark'];
                //     $studentArr[$stu]->rank = $student['rank'];
                // }

                // dd($stu);
                foreach ($studentArr as $stud) {
                    $stud->mark = $student_rank[$stud->student_id]['mark'];
                    $stud->rank = $student_rank[$stud->student_id]['rank'];
                    $stud->total_mark = $student_rank[$stud->student_id]['total_mark'];
                }

                if ($request->type == "top") {


                    array_multisort(
                        array_column($studentArr, 'rank'),
                        SORT_ASC,
                        $studentArr
                    );
                } else if ($request->type == "bottom") {


                    array_multisort(
                        array_column($studentArr, 'rank'),
                        SORT_DESC,
                        $studentArr
                    );
                }
            }

            $data = [
                'details' => $studentArr,
            ];

            return $this->successResponse($data, 'All student grade and classes row fetch successfully');
        }
    }
    // get class teacher grades 
    public function classTeacherClass(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'academic_session_id' => 'required',
            'teacher_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            $success = $createConnection->table('teacher_allocations as ta')
                ->select(
                    'c.id',
                    'c.name'
                )
                ->join('classes as c', 'ta.class_id', '=', 'c.id')
                ->where('ta.academic_session_id', $request->academic_session_id)
                ->where('ta.teacher_id', $request->teacher_id)
                ->get();
            return $this->successResponse($success, 'Class teacher record fetch successfully');
        }
    }
    public function classTeacherSections(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'branch_id' => 'required',
            'academic_session_id' => 'required',
            'teacher_id' => 'required',
            'class_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            $success = $createConnection->table('teacher_allocations as ta')
                ->select(
                    's.id as section_id',
                    's.name as section_name'
                )
                ->join('sections as s', 'ta.section_id', '=', 's.id')
                ->where('ta.academic_session_id', $request->academic_session_id)
                ->where('ta.class_id', $request->class_id)
                ->where('ta.teacher_id', $request->teacher_id)
                ->get();
            return $this->successResponse($success, 'Class teacher section record fetch successfully');
        }
    }

    public function faqEmail(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'email' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {

            // // return $request;
            $email = $request->email;
            //Retrieve the user from the database
            $user = DB::table('users')->where('email', $email)->select('name', 'email')->first();
            //Generate, the password reset link. The token generated is embedded in the link
            // $link = $url . '/password/expired/reset' . '/' . $token;
            if ($user) {
                try {
                    $data = array(
                        'subject' => isset($request->subject) ? $request->subject : "",
                        'remarks' => isset($request->remarks) ? $request->remarks : "",
                        'email' => $email,
                        'name' => isset($request->name) ? $request->name : "",
                        'school_name' => isset($request->school_name) ? $request->school_name : "",
                        'role_name' => isset($request->role_name) ? $request->role_name : ""
                    );
                    Mail::send('auth.faq_mail', $data, function ($message) use ($email) {
                        $message->to(env('MAIL_FROM_ADDRESS'), 'staffs')->subject('FAQ');
                        $message->from($email, 'FAQ');
                        // $message->to('karthik@aibots.my', 'staffs')->subject('FAQ');
                        // $message->from(env('MAIL_FROM_ADDRESS'), 'FAQ');
                    });
                    // return $user;
                    return $this->successResponse([], 'Mail Sended Successfully');
                } catch (Exception $e) {
                    return $this->sendCommonError('An error occurred while sending the email.', ['error' => $e->getMessage()]);
                }
            } else {
                // return false;
                return $this->sendCommonError('Email user not match.', ['error' => 'Email user not match']);
            }
        }
    }

    public function firstName(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'table_name' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $createConnection = $this->createNewConnection($request->branch_id);
            $table_name = $request->table_name;
            $students = $createConnection->table($table_name)->select('id', 'first_name')->get();
            foreach ($students as $stud) {
                $trim = explode(' ', $stud->first_name);
                $name1 = isset($trim[0]) ? $trim[0] : "";
                $name2 = isset($trim[1]) ? $trim[1] : "";
                $name = $name1 . ' ' . $name2;
                $update = $createConnection->table($table_name)->where('id', $stud->id)->update(['first_name' => $name]);
            }
            return "Success";
        }
    }

    public function staffAttendanceReport(Request $request)
    {
        // return 1;
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'staff_id' => 'required',
            'session_id' => 'required',
            'date' => 'required',
            'department_id' => '',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $con = $this->createNewConnection($request->branch_id);
            // get data
            $branch = $request->branch_id;
            $staff = $request->staff_id;
            $session = $request->session_id;
            $department = $request->department_id;
            $academic_session_id = $request->academic_session_id;
            $date = $request->date;


            $month_year = explode("-", $date);
            $m = $month_year[0];
            $y = $month_year[1];



            $start = $y . '-' . $m . '-01';
            $end = date('Y-m-t', strtotime($start));
            //
            $startDate = new DateTime($start);
            $endDate = new DateTime($end);

            $date = '';
            $tot = [];
            while ($startDate <= $endDate) {

                $dat = $startDate->format('Y-m-d');
                array_push($tot, $dat);
                $date .= $dat . ',';
                $startDate->modify('+1 day');
            }
            // dd($date);
            $trimdate = rtrim($date, ",");
            $attend = \DB::raw($trimdate);
            $Connection = $this->createNewConnection($branch);

            $excel = $Connection->table('staff_attendances as sa')
                ->select(
                    'st.id',
                    'sa.session_id',
                    \DB::raw("CONCAT(st.first_name, ' ', st.last_name) as name"),
                    $attend,
                    DB::raw('COUNT(CASE WHEN sa.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                    DB::raw('COUNT(CASE WHEN sa.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                    DB::raw(
                        'COUNT(CASE WHEN sa.status = "late" then 1 ELSE NULL END) as "lateCount"'
                    )
                )

                ->join('staffs as st', 'sa.staff_id', '=', 'st.id')
                ->when($staff != "All", function ($q)  use ($staff) {
                    $q->where('sa.staff_id', $staff);
                })

                ->when($staff == "All", function ($q)  use ($department) {
                    $q->where('st.department_id', $department);
                })
                ->join('staffs', 'staffs.id', '=', 'sa.staff_id')
                ->whereMonth('sa.date', $m)
                ->whereYear('sa.date', $y)
                ->when($session == "All", function ($q) {
                    $q->groupBy('sa.session_id');
                })
                ->when($session != "All", function ($q)  use ($session) {
                    $q->where('sa.session_id', $session);
                })
                ->groupBy('sa.staff_id')
                ->orderBy('sa.staff_id')
                ->orderBy('sa.session_id')
                ->get();

            if (!empty($excel)) {

                foreach ($excel as $key => $li) {
                    $staff_id = $li->id;
                    $session_id = $li->session_id;
                    $session_name = $Connection->table('session')->select('name')->where('id', $li->session_id)->first();

                    $li->session_id = $session_name->name;
                    foreach ($tot as $t) {
                        $in_date = $Connection->table('staff_attendances as sa')
                            ->where('sa.staff_id', $staff_id)
                            ->where('sa.date', $t)
                            ->where('sa.session_id', $session_id)
                            ->first();
                        if ($in_date) {
                            if ($in_date->status == "present") {
                                $li->$t = "P";
                            } else if ($in_date->status == "absent") {
                                $li->$t = "X";
                            } else if ($in_date->status == "late") {
                                $li->$t = "L";
                            } else if ($in_date->status == "excused") {
                                $li->$t = "E";
                            } else {
                                $li->$t = 0;
                            }
                        } else {
                            $li->$t = 0;
                        }
                    }
                    $excel[$key] = $li;
                }
            }
            $details['attendance'] = $excel;
            return $this->successResponse($details, 'Staff Attendance record fetch successfully');
        }
    }

    public function studentAttendanceReport(Request $request)
    {
        // return 1;
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'subject_id' => 'required',
            'date' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $con = $this->createNewConnection($request->branch_id);
            // get data
            $branch = $request->branch_id;
            $class = $request->class_id;
            $student = $request->student_id;
            $section = $request->section_id;
            $subject = $request->subject_id;
            $semester = $request->semester_id;
            $session = $request->session_id;
            $academic_session_id = $request->academic_session_id;
            $date = $request->date;


            $month_year = explode("-", $date);
            $m = $month_year[0];
            $y = $month_year[1];



            $start = $y . '-' . $m . '-01';
            $end = date('Y-m-t', strtotime($start));
            //
            $startDate = new DateTime($start);
            $endDate = new DateTime($end);

            $date = '';
            $tot = [];
            while ($startDate <= $endDate) {

                $dat = $startDate->format('Y-m-d');
                array_push($tot, $dat);
                $date .= $dat . ',';
                $startDate->modify('+1 day');
            }
            // dd($date);
            $trimdate = rtrim($date, ",");
            $attend = \DB::raw($trimdate);
            $Connection = $this->createNewConnection($branch);

            if ($student) {
                $excel = $Connection->table('student_attendances as sa')
                    ->select(
                        'sa.student_id',
                        \DB::raw("CONCAT(stud.first_name, ' ', stud.last_name) as name"),
                        $attend,
                        DB::raw('COUNT(CASE WHEN sa.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "late" then 1 ELSE NULL END) as "lateCount"'),

                    )
                    ->join('enrolls as en', 'sa.student_id', '=', 'en.student_id')
                    ->join('students as stud', 'sa.student_id', '=', 'stud.id')
                    ->where([
                        ['sa.student_id', '=', $student],
                        ['sa.subject_id', '=', $subject],
                    ])
                    ->whereMonth('sa.date', $m)
                    ->whereYear('sa.date', $y)
                    ->groupBy('sa.student_id')
                    ->get();
            } else {
                $excel = $Connection->table('student_attendances as sa')
                    ->select(
                        'sa.student_id',
                        \DB::raw("CONCAT(stud.first_name, ' ', stud.last_name) as name"),
                        $attend,
                        DB::raw('COUNT(CASE WHEN sa.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "late" then 1 ELSE NULL END) as "lateCount"'),

                    )
                    ->join('enrolls as en', 'sa.student_id', '=', 'en.student_id')
                    ->join('students as stud', 'sa.student_id', '=', 'stud.id')
                    ->where([
                        ['sa.class_id', '=', $class],
                        ['sa.section_id', '=', $section],
                        ['sa.subject_id', '=', $subject],
                        ['sa.semester_id', '=', $semester],
                        ['sa.session_id', '=', $session]
                    ])
                    ->whereMonth('sa.date', $m)
                    ->whereYear('sa.date', $y)
                    ->groupBy('sa.student_id')
                    ->get();
            }


            if (!empty($excel)) {

                foreach ($excel as $key => $li) {
                    $student_id = $li->student_id;
                    foreach ($tot as $t) {
                        if ($student) {
                            $in_date = $Connection->table('student_attendances as sa')
                                ->where('sa.student_id', $student_id)
                                ->where('sa.subject_id', $subject)
                                ->where('sa.date', $t)
                                ->first();
                        } else {
                            $in_date = $Connection->table('student_attendances as sa')
                                ->where('sa.student_id', $student_id)
                                ->where('sa.subject_id', $subject)
                                ->where('sa.semester_id', $semester)
                                ->where('sa.session_id', $session)
                                ->where('sa.date', $t)
                                ->first();
                        }


                        if ($in_date) {
                            if ($in_date->status == "present") {
                                $li->$t = "P";
                            } else if ($in_date->status == "absent") {
                                $li->$t = "X";
                            } else if ($in_date->status == "late") {
                                $li->$t = "L";
                            } else {
                                $li->$t = 0;
                            }
                        } else {
                            $li->$t = 0;
                        }
                    }
                    $excel[$key] = $li;
                }
            }
            $details['attendance'] = $excel;
            return $this->successResponse($details, 'Student Attendance record fetch successfully');
        }
    }
    // get Languages 
    public function getLanguages(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $section = $Connection->table('language')->orderBy('name', 'asc')->get();
            return $this->successResponse($section, 'Language record fetch successfully');
        }
    }
    // add holidays
    public function addHolidays(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'name' => 'required',
            'date' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            // insert data
            $query = $createConnection->table('holidays')->insert([
                'name' => $request->name,
                'date' => date('Y-m-d', strtotime($request->date)),
                'created_at' => date("Y-m-d H:i:s"),
                'created_by' => isset($request->created_by) ? $request->created_by : null
            ]);
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'New holidays has been successfully saved');
            }
        }
    }
    // get holidays 
    public function getHolidaysList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $secConn = $this->createNewConnection($request->branch_id);
            // get data
            $section = $secConn->table('holidays')->select('id', 'name', 'date')->orderBy('date', 'DESC')->whereNull('deleted_at')->get();
            return $this->successResponse($section, 'Holidays record fetch successfully');
        }
    }
    // get holidays row details
    public function getHolidaysDetails(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'id' => 'required',
            'branch_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            // insert data
            $sectionDetails = $createConnection->table('holidays')->where('id', $request->id)->first();
            return $this->successResponse($sectionDetails, 'Holidays row fetch successfully');
        }
    }
    // update holidays
    public function updateHolidaysDetails(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            'date' => 'required',
            'branch_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $staffConn = $this->createNewConnection($request->branch_id);
            // update data
            $query = $staffConn->table('holidays')->where('id', $request->id)->update([
                'name' => $request->name,
                'date' => date('Y-m-d', strtotime($request->date)),
                'updated_at' => date("Y-m-d H:i:s"),
                'updated_by' => isset($request->updated_by) ? $request->updated_by : null
            ]);
            $success = [];
            if ($query) {
                return $this->successResponse($success, 'holidays details have been updated');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    // delete holidays
    public function deleteHolidays(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'id' => 'required',
            'branch_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            // get data
            $query = $createConnection->table('holidays')->where('id', $request->id)->update([
                'deleted_at' => date("Y-m-d H:i:s"),
                'deleted_by' => isset($request->deleted_by) ? $request->deleted_by : null
            ]);

            $success = [];
            if ($query) {
                return $this->successResponse($success, 'Holidays have been deleted successfully');
            } else {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            }
        }
    }
    // all student marks
    public function allStudentRanking(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'academic_session_id' => 'required',
            'exam_id' => 'required',
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data     
            $academic_session_id = $request->academic_session_id;
            $exam_id = $request->exam_id;
            $class_id = $request->class_id;
            $student_section_id = isset($request->section_id) ? $request->section_id : null;
            $subject_id = isset($request->subject_id) ? $request->subject_id : null;
            $student_semester_id = isset($request->semester_id) ? $request->semester_id : 0;
            $student_session_id = isset($request->session_id) ? $request->session_id : 0;

            // get total recent subject teacher
            $total_sujects_teacher = $Connection->table('subject_assigns as sa')
                ->select(
                    DB::raw("group_concat(sa.section_id) as all_section_id"),
                    'sbj.id as subject_id',
                    'sbj.name as subject_name'
                )
                ->join('subjects as sbj', 'sa.subject_id', '=', 'sbj.id')
                ->where([
                    ['sa.class_id', $class_id],
                    ['sa.academic_session_id', $academic_session_id],
                    ['sa.type', '=', '0'],
                    ['sbj.exam_exclude', '=', '0']
                ])
                ->when($student_section_id != "All", function ($q)  use ($student_section_id) {
                    $q->where('sa.section_id', $student_section_id);
                })
                ->when($subject_id != "All", function ($q)  use ($subject_id) {
                    $q->where('sa.subject_id', $subject_id);
                })
                ->groupBy('sa.subject_id')
                ->get();
            // dd(count($total_sujects_teacher));
            // dd($total_sujects_teacher);
            $allSections = isset($total_sujects_teacher[0]->all_section_id) ? explode(',', $total_sujects_teacher[0]->all_section_id) : [];
            // dd($total_sujects_teacher);
            $total_marks = [];
            if (!empty($total_sujects_teacher)) {
                foreach ($total_sujects_teacher as $skey => $val) {
                    $subject_id = $val->subject_id;
                    // all section list
                    // get subject total weightage
                    $getExamPaperWeightage = $Connection->table('exam_papers as expp')
                        ->select(
                            DB::raw('SUM(expp.subject_weightage) as total_subject_weightage'),
                            'expp.grade_category'
                        )
                        ->where([
                            ['expp.class_id', '=', $class_id],
                            ['expp.subject_id', '=', $subject_id],
                            ['expp.academic_session_id', $academic_session_id]
                        ])
                        ->get();
                    // dd($class_id);
                    $total_subject_weightage = isset($getExamPaperWeightage[0]->total_subject_weightage) ? (int)$getExamPaperWeightage[0]->total_subject_weightage : 0;
                    // dd($total_subject_weightage);
                    $getLastExam = new \stdClass();
                    $getLastExam->exam_id = $exam_id;
                    foreach ($allSections as $key => $section) {
                        $studentDetails = $Connection->table('enrolls as en')
                            ->select(
                                'en.student_id',
                                'en.semester_id',
                                'en.session_id',
                                'cl.name as class_name',
                                'sec.name as section_name',
                                DB::raw('CONCAT(stu.first_name, " ", stu.last_name) as student_name'),
                            )
                            ->join('students as stu', 'en.student_id', '=', 'stu.id')
                            ->join('classes as cl', 'en.class_id', '=', 'cl.id')
                            ->join('sections as sec', 'en.section_id', '=', 'sec.id')
                            ->where([
                                ['en.class_id', $class_id],
                                ['en.section_id', $section],
                                ['en.academic_session_id', '=', $academic_session_id],
                                ['en.semester_id', '=', $student_semester_id],
                                ['en.session_id', '=', $student_session_id]
                            ])
                            ->get();
                        $semester_id = isset($studentDetails[0]->semester_id) ? $studentDetails[0]->semester_id : 0;
                        $session_id = isset($studentDetails[0]->session_id) ? $studentDetails[0]->session_id : 0;
                        if (!empty($studentDetails)) {
                            foreach ($studentDetails as $student) {
                                $studentID = $student->student_id;
                                $studentName = $student->student_name;
                                $getStudMarksDetails = $Connection->table('student_marks as sm')
                                    ->select(
                                        'expp.subject_weightage',
                                        'sb.name as subject_name',
                                        'sb.id as subject_id',
                                        'sm.score',
                                        'sm.paper_id',
                                        'sm.grade_category',
                                    )
                                    ->join('subjects as sb', 'sm.subject_id', '=', 'sb.id')
                                    ->join('exam_papers as expp', 'sm.paper_id', '=', 'expp.id')
                                    ->where([
                                        ['sm.class_id', '=', $class_id],
                                        ['sm.section_id', '=', $section],
                                        ['sm.subject_id', '=', $subject_id],
                                        ['sm.exam_id', '=', $exam_id],
                                        ['sm.semester_id', '=', $semester_id],
                                        ['sm.session_id', '=', $session_id],
                                        ['sm.student_id', '=', $studentID],
                                        ['sm.academic_session_id', '=', $academic_session_id]
                                    ])
                                    ->groupBy('sm.paper_id')
                                    ->get();
                                $marks = 0;
                                $fail = 0;
                                $pass = 0;
                                $grade_category = 0;
                                $total_marks[$studentID]['student_id'] = $studentID;
                                $total_marks[$studentID]['student_name'] = $studentName;
                                $total_marks[$studentID]['class_name'] = $student->class_name;
                                $total_marks[$studentID]['section_name'] = $student->section_name;
                                $total_marks[$studentID]['class_id'] = $class_id;
                                $total_marks[$studentID]['section_id'] = $section;
                                // // here you get calculation based on student marks and subject weightage
                                if (!empty($getStudMarksDetails)) {
                                    // grade calculations
                                    foreach ($getStudMarksDetails as $Studmarks) {
                                        $sub_weightage = (int) $Studmarks->subject_weightage;
                                        $score = (int) $Studmarks->score;
                                        $grade_category = $Studmarks->grade_category;
                                        // foreach for total no of students
                                        $weightage = ($sub_weightage / $total_subject_weightage);
                                        $marks += ($weightage * $score);
                                    }
                                    $mark = (int) $marks;
                                    // echo gettype($mark);
                                    if ($skey == 0) {
                                        $total_marks[$studentID]['subjects'] = $subject_id;
                                        $total_marks[$studentID]['sub_marks'] = $mark;
                                        $total_marks[$studentID]['mark'] = $mark;
                                        // grade marks fail count
                                        $grdMarks = $Connection->table('grade_marks')
                                            ->select('status')
                                            ->where([
                                                ['min_mark', '<=', $mark],
                                                ['max_mark', '>=', $mark],
                                                ['grade_category', '=', $grade_category],
                                                ['status', '=', 'Pass']
                                            ])
                                            ->first();

                                        // get maximum marks
                                        $maxMarks = $Connection->table('grade_marks')
                                            ->select('max_mark')
                                            ->select(DB::raw('MAX(max_mark) as total_marks'))
                                            ->where([
                                                ['grade_category', '=', $grade_category]
                                            ])
                                            ->get();
                                        $total_marks[$studentID]['total_mark'] = isset($maxMarks[0]->total_marks) ? $maxMarks[0]->total_marks : 0;

                                        if (isset($grdMarks->status)) {
                                            $pass++;
                                        } else {
                                            $fail++;
                                        }
                                        $total_marks[$studentID]['fail'] = $fail;
                                        $total_marks[$studentID]['pass'] = $pass;
                                    } else {
                                        $total_marks[$studentID]['subjects'] .= "-" . $subject_id;
                                        $total_marks[$studentID]['sub_marks'] .= "-" . $mark;
                                        $total_marks[$studentID]['mark'] += $mark;
                                        // grade marks fail count
                                        $grdMarks = $Connection->table('grade_marks')
                                            ->select('status')
                                            ->where([
                                                ['min_mark', '<=', $mark],
                                                ['max_mark', '>=', $mark],
                                                ['grade_category', '=', $grade_category],
                                                ['status', '=', 'Pass']
                                            ])
                                            ->first();
                                        // get maximum marks
                                        $maxMarks = $Connection->table('grade_marks')
                                            ->select('max_mark')
                                            ->select(DB::raw('MAX(max_mark) as total_marks'))
                                            ->where([
                                                ['grade_category', '=', $grade_category]
                                            ])
                                            ->get();
                                        $total_marks[$studentID]['total_mark'] += isset($maxMarks[0]->total_marks) ? $maxMarks[0]->total_marks : 0;

                                        if (isset($grdMarks->status)) {
                                            $pass++;
                                        } else {
                                            $fail++;
                                        }
                                        $total_marks[$studentID]['fail'] += $fail;
                                        $total_marks[$studentID]['pass'] += $pass;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $student_rank = [];
            $class_rank = collect($total_marks)->sortByDesc('mark')->all();
            if ($class_rank) {
                $student_rank = $this->calculate_overall_rank($class_rank);
            }

            if ($request->type == "top") {
                $student_rank = collect($student_rank)->sortByDesc('mark')->sortBy('rank')->all();

                // array_multisort(
                //     array_column($student_rank, 'rank'),
                //     SORT_ASC,
                //     $student_rank
                // );
            }
            if ($request->type == "bottom") {
                $student_rank = collect($student_rank)->sortBy('mark')->sortByDesc('rank')->all();
                // array_multisort(
                //     array_column($student_rank, 'rank'),
                //     SORT_DESC,
                //     $student_rank
                // );
            }
            $rank = [];
            $no = 1;
            foreach ($student_rank as $sr) {
                $rank[$no] = $sr;
                $no++;
            }
            return $this->successResponse($rank, 'All student top and bottom ranking fetch successfully');
        }
    }
    // get Branch Id Permission
    public function getBranchIdPermission(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $data = BranchRolesPermission::select('branch_id', 'role_id', 'permission_status')->where('branch_id', $request->branch_id)->get();
            return $this->successResponse($data, 'Branch permission fetch successfully');
        }
    }
    // work week
    public function workWeek(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $hiddenWeekends = $conn->table('work_weeks')
                ->where('status', '=', '1')
                ->select('day_value')
                ->pluck('day_value')
                ->toArray();
            return $this->successResponse($hiddenWeekends, 'work weeks fetch successfully');
        }
    }
    public function workWeekGet(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $hiddenWeekends = $conn->table('work_weeks')
                ->get();
            return $this->successResponse($hiddenWeekends, 'work weeks fetch successfully');
        }
    }
    public function workWeekUpdate(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'work_weeks' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $work_weeks = $request->work_weeks;

            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            foreach ($work_weeks as $row) {

                if (isset($row['id'])) {
                    if (isset($row['status'])) {
                        $status = '0';
                    } else {
                        $status = '1';
                    }
                    $query = $conn->table('work_weeks')->where('id', $row['id'])->update([
                        'status' => $status,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                }
            }
            return $this->successResponse([], 'work weeks updated successfully');
        }
    }
    // get fees
    public function getParentFeesList(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'academic_session_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $branchID = $request->branch_id;
            $academic_session_id = $request->academic_session_id;
            // $feesGroupId = null;
            // if ($request->fees_type) {
            //     $feesDet = explode('|', $request->fees_type);
            //     $feesGroupId = $feesDet[0];
            // }
            $feesGroupId = isset($request->group_id) ? $request->group_id : null;
            $conn = $this->createNewConnection($request->branch_id);
            $student_id = $request->student_id;
            // get data
            $studentData = $conn->table('fees_allocation as fa')
                ->select(
                    'fa.student_id',
                    'fa.class_id',
                    'fa.section_id',
                    // 'en.roll',
                    'st.gender',
                    'st.register_no',
                    'st.email',
                    'cl.name as class_name',
                    'sc.name as section_name',
                    'st.photo',
                    'fa.group_id as all_group_id',
                    // 'fa.id as allocation_id',
                    DB::raw('CONCAT(st.first_name, " ", st.last_name) as name'),
                )
                // ->join('enrolls as en', 'en.student_id', '=', 'fa.student_id')
                ->leftJoin('students as st', 'fa.student_id', '=', 'st.id')
                ->leftJoin('classes as cl', 'fa.class_id', '=', 'cl.id')
                ->leftJoin('sections as sc', 'fa.section_id', '=', 'sc.id')
                ->where([
                    // ['fa.class_id', '=', $request->class_id],
                    // ['fa.section_id', '=', $request->section_id],
                    ['fa.academic_session_id', '=', $request->academic_session_id]
                ])
                ->when($student_id, function ($q)  use ($student_id) {
                    $q->where('fa.student_id', $student_id);
                })
                ->when($feesGroupId, function ($q)  use ($feesGroupId) {
                    $q->where('fa.group_id', $feesGroupId);
                })
                ->groupBy('fa.student_id')
                ->groupBy('fa.group_id')
                ->orderBy('st.id', 'ASC')
                ->get()->toArray();
            // dd($studentData);
            $arrData = [];
            if (!empty($studentData)) {
                foreach ($studentData as $key => $value) {
                    $object = new \stdClass();
                    $object->student_id = $value->student_id;
                    $object->email = $value->email;
                    $object->class_name = $value->class_name;
                    $object->section_name = $value->section_name;
                    $object->name = $value->name;
                    $object->photo = $value->photo;
                    $object->all_group_id = $value->all_group_id;
                    $all_group_id = explode(",", $value->all_group_id);
                    $group_arr = [];
                    if (!empty($all_group_id)) {
                        foreach ($all_group_id as $grp_id) {
                            $newGrp = new \stdClass();
                            $return_group = $this->getfeeGroup($value, $branchID, $academic_session_id, $grp_id);
                            $newGrp->grp_id = $grp_id;
                            $newGrp->paid_id = $return_group['paid_id'];
                            $newGrp->group_name = $return_group['group_name'];
                            $newGrp->paidSts = $return_group['paidSts'];
                            $newGrp->labelmode = $return_group['labelmode'];
                            // group_arr
                            if (isset($request->payment_status)) {
                                // dd($return_group['paid_id']);
                                if ($request->payment_status == $return_group['paid_id']) {
                                    array_push($group_arr, $newGrp);
                                }
                            } else {
                                array_push($group_arr, $newGrp);
                            }
                        }
                    }
                    $object->feegroup = $group_arr;
                    // filter by payment status
                    if (isset($request->payment_status)) {
                        if (!empty($group_arr)) {
                            array_push($arrData, $object);
                        }
                    } else {
                        array_push($arrData, $object);
                    }
                }
            }
            return $this->successResponse($arrData, 'get Parent Fees details fetched successfully');
        }
    }


    // fees view page
    public function parentFeesHistory(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'student_id' => 'required',
            'group_id' => 'required',
            'academic_session_id' => 'required',
            'branch_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $studentID = $request->student_id;
            $groupId = $request->group_id;
            $branchID = $request->branch_id;
            $academic_session_id = $request->academic_session_id;
            $allocations = $conn->table('fees_allocation as fa')
                ->select(
                    'fa.id as allocation_id',
                    'fa.payment_mode_id',
                    'fa.student_id',
                    't.name',
                    'f.name as fees_group_name',
                    'fg.amount',
                    'fg.due_date',
                    'fg.fees_type_id',
                    "fph.fees_group_details_id",
                    'fph.date as paid_date',
                    "fph.payment_status_id",
                    "fph.student_id",
                    "fph.amount as paid_amount",
                    "pm.name as payment_mode_name"
                )
                ->leftJoin('fees_group as f', 'f.id', '=', 'fa.group_id')
                ->leftJoin('payment_mode as pm', 'fa.payment_mode_id', '=', 'pm.id')
                ->leftjoin('fees_group_details as fg', function ($join) {
                    $join->on('fg.fees_group_id', '=', 'fa.group_id');
                    $join->on('fg.payment_mode_id', '=', 'fa.payment_mode_id');
                })
                ->leftjoin('fees_payment_history as fph', function ($join) use ($studentID, $groupId, $academic_session_id) {
                    $join->on('fph.fees_group_details_id', '=', 'fg.id');
                    $join->on('fph.student_id', '=', DB::raw("'$studentID'"));
                    $join->on('fph.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->leftJoin('fees_type as t', 't.id', '=', 'fg.fees_type_id')
                ->where([
                    ['fa.student_id', '=', $studentID],
                    ['fa.group_id', '=', $groupId],
                    ['fa.academic_session_id', '=', $academic_session_id]
                ])
                ->orderBy('f.id', 'asc')
                ->orderBy('fg.id', 'asc')
                ->get()->toArray();
            return $this->successResponse($allocations, 'Get fees row fetch successfully');
        }
    }
    public function getTodaySchedulesAdmin(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'login_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            $today = now()->format('Y-m-d'); // Get today's date in 'YYYY-MM-DD' format
            $todayWithHours = now()->format('Y-m-d H:i:s'); // Get today's date in 'YYYY-MM-DD' format
            // all tasks in calendar
            $tasks = $Connection->table('calendors as cl')
                ->select('cl.id', 'cl.start', 'cl.end', 'cl.title', DB::raw('if(cl.all_day=1,false,true) as allDay'))
                ->whereDate('cl.start', '<=', $today)
                ->whereDate('cl.end', '>=', $today)
                ->where('cl.login_id', '=', $request->login_id)
                ->get()->toArray();
            // dd($tasks);
            //get all events
            $event = $Connection->table('calendors as c')
                ->select('c.id', 'c.start', 'c.end', 'e.title', 'e.audience', DB::raw('if(c.all_day=1,false,true) as allDay'))
                ->leftJoin('events as e', 'c.event_id', '=', 'e.id')
                ->leftJoin('event_types as et', 'e.type', '=', 'et.id')
                ->leftjoin("classes as cl", \DB::raw("FIND_IN_SET(cl.id,e.selected_list)"), ">", \DB::raw("'0'"))
                ->whereNotNull('c.event_id')
                ->whereNull('c.group_id')
                ->where('e.status', 1)
                ->whereDate('c.start', '<=', $today)
                ->whereDate('c.end', '>=', $today)
                ->groupBy('c.event_id')
                // ->groupBy('c.start')
                ->get();

            $all_events = [];
            foreach ($event as $eve) {
                $data = $eve;
                if ($eve->audience == "1") {
                    $data->class_name = "EveryOne";
                    array_push($all_events, $data);
                }
                if ($eve->audience == "2") {
                    array_push($all_events, $data);
                }
            }
            $all_event_group = $Connection->table('calendors as c')
                ->select('c.id', 'c.start', 'c.end', 'e.title', 'e.audience', DB::raw('if(c.all_day=1,false,true) as allDay'))
                ->leftJoin('events as e', 'c.event_id', '=', 'e.id')
                ->leftJoin('event_types as et', 'e.type', '=', 'et.id')
                ->leftjoin("groups as g", \DB::raw("FIND_IN_SET(g.id,e.selected_list)"), ">", \DB::raw("'0'"))
                ->whereNotNull('c.group_id')
                ->where('e.status', 1)
                ->whereDate('c.start', '<=', $today)
                ->whereDate('c.end', '>=', $today)
                ->groupBy('c.event_id')
                // ->groupBy('c.start')
                ->get();
            $events_group = [];
            foreach ($all_event_group as $events) {
                if ($events->audience == "3") {
                    array_push($events_group, $events);
                }
            }
            //  bulk details
            $bluk_calendar_admin = $Connection->table('calendors as cl')
                ->select('cl.id', 'cl.start', 'cl.end', 'cl.title', DB::raw('if(cl.all_day=1,false,true) as allDay'))
                ->where("cl.teacher_id", "0")
                ->whereDate('cl.start', '<=', $today)
                ->whereDate('cl.end', '>=', $today)
                ->whereNotNull('cl.bulk_id')
                ->groupBy('cl.bulk_id')
                ->get()->toArray();
            // get exam timetable
            $getTimeTableCalendor = $Connection->table('timetable_exam as tex')
                ->select(
                    'tex.id',
                    'tex.time_start',
                    'tex.time_end',
                    'ex.name as exam_name',
                    DB::raw("CONCAT('Exam: ',ex.name, ' - ', sbj.name) as title"),
                    'tex.exam_date as start',
                    'tex.exam_date as end'
                )
                ->join('subjects as sbj', 'tex.subject_id', '=', 'sbj.id')
                ->join('exam as ex', 'tex.exam_id', '=', 'ex.id')
                ->where('tex.exam_date', '<=', $today)
                ->where('tex.exam_date', '>=', $today)
                ->get();

            $timetableCalendar = [];
            if (!empty($getTimeTableCalendor)) {
                foreach ($getTimeTableCalendor as $key => $value) {
                    $object = new \stdClass();
                    $start = $value->start . " " . $value->time_start;
                    $end = $value->end . " " . $value->time_end;
                    $start = date('Y-m-d H:i:s', strtotime($start));
                    $end = date('Y-m-d H:i:s', strtotime($end));
                    $object->id = $value->id;
                    $object->title = $value->title;
                    $object->start = $start;
                    $object->end = $end;
                    array_push($timetableCalendar, $object);
                }
            }
            $combinedArray = array_merge($tasks, $all_events, $events_group, $bluk_calendar_admin, $timetableCalendar);
            // Sort the combined array by datetime values
            usort($combinedArray, function ($a, $b) {
                return strtotime($a->start) - strtotime($b->start);
            });
            return $this->successResponse($combinedArray, 'Today Schedule Events Fetched successfully');
        }
    }
    public function getTodaySchedulesTeacher(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'login_id' => 'required',
            'teacher_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // $meetingDatetimeString = '2023-07-12'; // Replace w
            // $particularDate = '2023-07-12'; // Replace with the date you want to format

            // $dateTime = new DateTime($particularDate);
            $teacherId = $request->teacher_id;
            // $formattedDate = $dateTime->format('Y-m-d'); // Format as year-month-dayith your datetime string
            // dd($formattedDate);
            $today = now()->format('Y-m-d'); // Get today's date in 'YYYY-MM-DD' format
            $todayWithHours = now()->format('Y-m-d H:i:s'); // Get today's date in 'YYYY-MM-DD' format
            // all tasks in calendar
            $tasks = $Connection->table('calendors as cl')
                ->select('cl.id', 'cl.start', 'cl.end', 'cl.title', DB::raw('if(cl.all_day=1,false,true) as allDay'))
                ->whereDate('cl.start', '<=', $today)
                ->whereDate('cl.end', '>=', $today)
                ->where('cl.login_id', '=', $request->login_id)
                ->get()->toArray();
            $timetable = $Connection->table('calendors as cl')
                ->select('cl.id', 'cl.start', 'cl.end', DB::raw('CONCAT(c.short_name," (",s.name,") " " - ", sb.short_name) as title'), DB::raw('if(cl.all_day=1,false,true) as allDay'))
                ->join('classes as c', 'cl.class_id', '=', 'c.id')
                ->join('sections as s', 'cl.section_id', '=', 's.id')
                ->join('staffs as st', 'cl.teacher_id', '=', 'st.id')
                ->leftJoin('daily_reports as dr', function ($join) {
                    $join->on('cl.class_id', '=', 'dr.class_id')
                        ->on('cl.section_id', '=', 'dr.section_id')
                        ->on('cl.subject_id', '=', 'dr.subject_id')
                        ->on(DB::raw('date(cl.end)'), '=', 'dr.date');
                })
                ->leftJoin('events as ev', function ($join) {
                    $join->where([
                        [DB::raw('date(ev.start_date)'), '<=', DB::raw('date(cl.end)')],
                        [DB::raw('date(ev.end_date)'), '>=', DB::raw('date(cl.end)')],
                        ['ev.holiday', '=', '0']
                    ]);
                })
                ->join('subjects as sb', 'cl.subject_id', '=', 'sb.id')
                ->whereRaw("find_in_set($request->teacher_id,cl.teacher_id)")
                ->whereDate('cl.start', '<=', $today)
                ->whereDate('cl.end', '>=', $today)
                // where null mean holidays can not show
                ->whereNull('ev.id')
                ->get()->toArray();
            // all events
            $all_event = $Connection->table('calendors as c')
                ->select('c.id', 'c.start', 'c.end', 'e.title', 'e.audience', 'c.event_id', DB::raw('if(c.all_day=1,false,true) as allDay'))
                ->leftJoin('events as e', 'c.event_id', '=', 'e.id')
                ->leftJoin('event_types as et', 'e.type', '=', 'et.id')
                ->leftjoin("classes as cl", \DB::raw("FIND_IN_SET(cl.id,e.selected_list)"), ">", \DB::raw("'0'"))
                ->whereNotNull('c.event_id')
                ->whereNull('c.group_id')
                ->where('e.status', 1)
                ->whereDate('c.start', '<=', $today)
                ->whereDate('c.end', '>=', $today)
                ->groupBy('c.event_id')
                // ->groupBy('c.start')
                ->get();

            //     // return $all;
            $event = [];
            foreach ($all_event as $events) {

                if ($events->audience == "1") {
                    $events->class_name = "EveryOne";
                    array_push($event, $events);
                }
                if ($events->audience == "2") {
                    $class_check = $Connection->table('events as e')
                        ->select('e.id', 's.teacher_id')
                        ->leftjoin("classes as c", \DB::raw("FIND_IN_SET(c.id,e.selected_list)"), ">", \DB::raw("'0'"))
                        ->leftJoin('subject_assigns as s', 'c.id', '=', 's.class_id')
                        ->leftJoin('event_types as et', 'e.type', '=', 'et.id')
                        ->where('e.id', $events->event_id)
                        ->where('s.teacher_id', $teacherId)
                        ->get();
                    // return $class_check;
                    if (!$class_check->isEmpty()) {
                        if ($class_check[0]->teacher_id == $teacherId) {
                            array_push($event, $events);
                        }
                    }
                }
            }

            // group event
            $all_event_group = $Connection->table('calendors as c')
                ->select('c.id', 'c.start', 'c.end', 'e.title', 'e.audience', 'c.event_id', DB::raw('if(c.all_day=1,false,true) as allDay'))
                ->leftJoin('events as e', 'c.event_id', '=', 'e.id')
                ->leftJoin('event_types as et', 'e.type', '=', 'et.id')
                ->leftjoin("groups as g", \DB::raw("FIND_IN_SET(g.id,e.selected_list)"), ">", \DB::raw("'0'"))
                ->whereNotNull('c.group_id')
                ->where('e.status', 1)
                ->whereDate('c.start', '<=', $today)
                ->whereDate('c.end', '>=', $today)
                ->groupBy('c.event_id')
                ->groupBy('c.start')
                ->get();
            $events_group = [];
            foreach ($all_event_group as $events) {
                if ($events->audience == "3") {
                    // return $events;
                    $group_check = $Connection->table('events as e')
                        ->select('e.id', 's.id as teacher_id')
                        ->leftjoin("groups as g", \DB::raw("FIND_IN_SET(g.id,e.selected_list)"), ">", \DB::raw("'0'"))
                        ->leftjoin("staffs as s", 's.id', '=', 'g.staff')
                        ->leftJoin('event_types as et', 'e.type', '=', 'et.id')
                        ->where('e.id', $events->event_id)
                        ->where('s.id', $teacherId)
                        ->get();
                    if (!$group_check->isEmpty()) {
                        if ($group_check[0]->teacher_id == $teacherId) {
                            array_push($events_group, $events);
                        }
                    }
                }
            }

            // get_bulk_calendor_teacher
            $get_bulk_calendor_teacher = $Connection->table('calendors as cl')
                ->select('cl.id', 'cl.start', 'cl.end', 'cl.title', DB::raw('if(cl.all_day=1,false,true) as allDay'))
                ->join('subject_assigns as sa', function ($q) {
                    $q->on('cl.class_id', '=', 'sa.class_id')
                        ->on('cl.section_id', '=', 'sa.section_id');
                })
                ->where('sa.teacher_id', $request->teacher_id)
                ->where("cl.teacher_id", "0")
                ->whereDate('cl.start', '<=', $today)
                ->whereDate('cl.end', '>=', $today)
                ->orWhere("cl.teacher_id", $request->teacher_id)
                ->whereNotNull('cl.bulk_id')
                // ->groupBy('cl.start')
                ->groupBy('cl.bulk_id')
                ->get()->toArray();
            // get exam timetable
            $getTimeTableCalendor = $Connection->table('subject_assigns as sa')
                ->select(
                    'tex.id',
                    'tex.time_start',
                    'tex.time_end',
                    'ex.name as exam_name',
                    DB::raw("CONCAT('Exam: ',ex.name, ' - ', sbj.name) as title"),
                    'tex.exam_date as start',
                    'tex.exam_date as end'
                )
                ->join('timetable_exam as tex', function ($q) {
                    $q->on('tex.class_id', '=', 'sa.class_id')
                        ->on('tex.section_id', '=', 'sa.section_id') //second join condition                           
                        ->on('tex.subject_id', '=', 'sa.subject_id'); //need to add subject id also later                           
                })
                ->join('classes as cl', 'tex.class_id', '=', 'cl.id')
                ->join('sections as sc', 'tex.section_id', '=', 'sc.id')
                ->join('subjects as sbj', 'tex.subject_id', '=', 'sbj.id')
                ->join('exam as ex', 'tex.exam_id', '=', 'ex.id')
                ->where([
                    ['sa.teacher_id', '=', $request->teacher_id]
                ])
                ->where('tex.exam_date', '<=', $today)
                ->where('tex.exam_date', '>=', $today)
                ->get();
            $timetableCalendar = [];
            if (!empty($getTimeTableCalendor)) {
                foreach ($getTimeTableCalendor as $key => $value) {
                    $object = new \stdClass();
                    $start = $value->start . " " . $value->time_start;
                    $end = $value->end . " " . $value->time_end;
                    $start = date('Y-m-d H:i:s', strtotime($start));
                    $end = date('Y-m-d H:i:s', strtotime($end));
                    $object->id = $value->id;
                    $object->title = $value->title;
                    $object->start = $start;
                    $object->end = $end;
                    array_push($timetableCalendar, $object);
                }
            }
            $combinedArray = array_merge($tasks, $timetable, $event, $events_group, $get_bulk_calendor_teacher, $timetableCalendar);
            // Sort the combined array by datetime values
            usort($combinedArray, function ($a, $b) {
                return strtotime($a->start) - strtotime($b->start);
            });
            return $this->successResponse($combinedArray, 'Today Schedule Events Fetched successfully');
        }
    }
    // job title list
    public function jobTitleList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $data = $Connection->table('job_title')->select('id', 'name', 'description')->whereNull('deleted_at')->get();

            return $this->successResponse($data, 'job title record fetch successfully');
        }
    }
    // employee type list
    public function employeeTypeList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            // get data
            $data = $Connection->table('employee_types')->select('id', 'name')->whereNull('deleted_at')->get();

            return $this->successResponse($data, 'employee types record fetch successfully');
        }
    }
    // gradeListByDepartment
    public function gradeListByDepartment(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'department_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $createConnection = $this->createNewConnection($request->branch_id);
            $success = $createConnection->table('classes as cl')
                ->select('cl.id', 'cl.name', 'cl.short_name')
                ->where([
                    ['cl.department_id', '=', $request->department_id],
                ])
                ->get();
            return $this->successResponse($success, 'grade list by department fetch successfully');
        }
    }
}
