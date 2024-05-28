<?php

namespace App\Http\Controllers\Api\V1\Academic;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Exception;

class ScheduleController extends BaseController
{
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper)
    {
        $this->commonHelper = $commonHelper;
    }

    function addTimetableCalendor($request, $startDate, $endDate, $day, $row, $insertOrUpdateID, $bulkID)
    {
        // Create new connection
        try{
        $Connection = $this->createNewConnection($request->branch_id);
        // Loop through each date in the range
        while ($startDate <= $endDate) {
            // Check if the current date matches the desired day of the week
            if ($startDate->format('w') == $day) {
                $start = $startDate->format('Y-m-d') . " " . $row['time_start'];
                $end = $startDate->format('Y-m-d') . " " . $row['time_end'];

                // Construct the data to insert into the calendar table
                $arrayInsert = [
                    "title" => "timetable",
                    "class_id" => $request['class_id'],
                    "section_id" => $request['section_id'],
                    "sem_id" => $request['semester_id'],
                    "session_id" => $request['session_id'],
                    "subject_id" => $row['subject'],
                    "teacher_id" => implode(",", $row['teacher']),
                    "start" => $start,
                    "end" => $end,
                    "time_table_id" => $insertOrUpdateID,
                    "academic_session_id" => $request['academic_session_id'],
                    'created_at' => date("Y-m-d H:i:s")
                ];

                // Insert the data into the calendar table
                $Connection->table('calendors')->insert($arrayInsert);
            }
            // Move to the next date
            $startDate->modify('+1 day');
        }
    }
    catch(Exception $error) {
        return $this->commonHelper->generalReturn('403','error',$error,'Error in addTimetableCalendor');
    }
    }
    /**
     * @Chandru @since May 15,2024
     * @desc List section
     */
    // add Timetable
    public function addTimetable(Request $request)
    {
        try {
            // dd($request);
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'day' => 'required',
                'timetable' => 'required',
                'academic_session_id' => 'required'
            ]);


            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $staffConn = $this->createNewConnection($request->branch_id);

                // calendor data populate
                $getObjRow = $staffConn->table('semester as s')
                    ->select('start_date', 'end_date')
                    ->where('id', $request->semester_id)
                    ->get();
                if (count($getObjRow) == 0) {
                    // Use the $yearData as needed
                    $getObjRow = $staffConn->table('semester as sm')
                        ->select('sm.start_date', 'sm.end_date')
                        ->where('sm.academic_session_id', '=', $request->academic_session_id)
                        ->get();
                }
                $timetable = $request->timetable;
                $oldest = $staffConn->table('timetable_class')->where([['class_id', $request->class_id], ['section_id', $request->section_id], ['semester_id', $request->semester_id], ['session_id', $request->session_id], ['day', $request->day], ['academic_session_id', $request->academic_session_id]])->WhereNull('bulk_id')->get()->toArray();

                // return $oldest;
                $diff = array_diff(array_column($oldest, 'id'), array_column($timetable, 'id'));

                if (isset($diff)) {
                    foreach ($diff as $del) {
                        // $delete =  $staffConn->table('timetable_class')->where('id', $del)->delete();
                        // // delete calendor data
                        // $staffConn->table('calendors')->where('time_table_id', $del)->delete();
                        if ($staffConn->table('timetable_class')->where('id', '=', $del)->count() > 0) {
                            // record found
                            // echo "time table" . $del;
                            $delete =  $staffConn->table('timetable_class')->where('id', $del)->delete();
                        }
                        // delete calendor data
                        if ($staffConn->table('calendors')->where('time_table_id', '=', $del)->count() > 0) {
                            // record found
                            // dd($del);
                            // echo "calendor" . $del;
                            $staffConn->table('calendors')->where('time_table_id', $del)->delete();
                        }
                    }
                }

                // return $timetable;

                foreach ($timetable as $table) {

                    // return $table;
                    $session_id = 0;
                    $semester_id = 0;
                    $break_type = NULL;
                    $break = 0;
                    $subject_id = 0;
                    $teacher_id = NULL;


                    if (isset($request['session_id'])) {
                        $session_id = $request['session_id'];
                    }
                    if (isset($request['semester_id'])) {
                        $semester_id = $request['semester_id'];
                    }
                    if (isset($table['break_type'])) {
                        $break_type = $table['break_type'];
                    }
                    if (isset($table['break'])) {
                        $break = 1;
                    }
                    if (!empty($table['teacher'])) {
                        $teacher_id =  implode(",", $table['teacher']);
                    }
                    if (isset($table['subject'])) {
                        $subject_id = $table['subject'];
                    }

                    //  return $break_type;
                    $insertOrUpdateID = 0;
                    if (isset($table['id'])) {
                        // return $table['id'];
                        // echo "<pre>";
                        // echo $teacher_id;
                        // return $table['id']; 
                        $query = $staffConn->table('timetable_class')->where('id', $table['id'])->update([
                              'class_id' => $request['class_id'],
                            'section_id' => $request['section_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'subject_id' => $subject_id,
                            'teacher_id' => (isset($teacher_id) ? $teacher_id : 0),
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                        // return $query;
                        $insertOrUpdateID = $table['id'];
                    } else {
                        $query = $staffConn->table('timetable_class')->insertGetId([
                            'class_id' => $request['class_id'],
                            'section_id' => $request['section_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'subject_id' => $subject_id,
                            'teacher_id' => (isset($teacher_id) ? $teacher_id : 0),
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'created_at' => date("Y-m-d H:i:s")
                        ]);
                        $insertOrUpdateID = $query;
                    }
                    $bulkID = NuLL;
                    // return $break;
                    $this->addCalendorTimetable($request, $table, $getObjRow, $insertOrUpdateID, $bulkID);
                }
                // cache clear start
                $cache_timetable = config('constants.cache_timetable');
                $this->clearCache($cache_timetable, $request->branch_id);
                // cache clear end
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'TimeTable has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addTimetable');
        }
    }
    public function getTimetableList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                // 'token' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // get data
                //$cache_time = config('constants.cache_time');
                //$cache_timetable = config('constants.cache_timetable');
                //$cacheKey = $cache_timetable . $request->branch_id;

                // Check if the data is cached
                //if (Cache::has($cacheKey)) {
                // If cached, return cached data
                // $output = Cache::get($cacheKey);
                //} else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);
                // get data
                // $Timetable = $con->table('timetable_class')->where('class_id',$request->class_id)->where('section_id',$request->section_id)->orderBy('time_start', 'asc')->orderBy('time_end', 'asc')->get()->toArray();
                $Timetable = $con->table('timetable_class')->select(
                    'timetable_class.*',
                    DB::raw('GROUP_CONCAT(staffs.first_name, " ", staffs.last_name) as teacher_name'),
                    'subjects.name as subject_name',
                    'exam_hall.hall_no'
                )
                    // ->leftJoin('staffs', 'timetable_class.teacher_id', '=', 'staffs.id')
                    ->leftJoin("staffs", DB::raw("FIND_IN_SET(staffs.id,timetable_class.teacher_id)"), ">", DB::raw("'0'"))
                    ->leftJoin('subjects', 'timetable_class.subject_id', '=', 'subjects.id')
                    ->leftJoin('exam_hall', 'timetable_class.class_room', '=', 'exam_hall.id')
                    ->where([
                        ['timetable_class.class_id', $request->class_id],
                        ['timetable_class.semester_id', $request->semester_id],
                        // ['timetable_class.session_id', $request->session_id],
                        ['timetable_class.section_id', $request->section_id],
                        ['timetable_class.academic_session_id', $request->academic_session_id]
                    ])
                    ->orderBy('time_start', 'asc')
                    ->orderBy('time_end', 'asc')
                    ->groupBy("timetable_class.id")
                    ->get()->toArray();

                if ($Timetable) {
                    $mapfunction = function ($s) {
                        return $s->day;
                    };
                    $count = array_count_values(array_map($mapfunction, $Timetable));
                    $max = max($count);

                    $output['timetable'] = $Timetable;
                    $output['max'] = $max;
                    $output['week'] = $count;
                    // Cache the fetched data for future requests
                    //Cache::put($cacheKey, $output, now()->addHours($cache_time)); // Cache for 24 hours
                    return $this->successResponse($output, 'Timetable record fetch successfully');
                } else {
                    return $this->send404Error('No Data Found.', ['error' => 'No Data Found']);
                }
                //}
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getTimetableList');
        }
    }

    // copy Timetable
    public function copyTimetable(Request $request)
    {

        try {
            // dd($request);
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'day' => 'required',
                'timetable' => 'required',
                'academic_session_id' => 'required'
            ]);


            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $staffConn = $this->createNewConnection($request->branch_id);

                // calendor data populate
                $getObjRow = $staffConn->table('semester as s')
                    ->select('start_date', 'end_date')
                    ->where('id', $request->semester_id)
                    ->get();
                if (count($getObjRow) == 0) {
                    // Use the $yearData as needed
                    $getObjRow = $staffConn->table('semester as sm')
                        ->select('sm.start_date', 'sm.end_date')
                        ->where('sm.academic_session_id', '=', $request->academic_session_id)
                        ->get();
                }
                $timetable = $request->timetable;
                $oldest = $staffConn->table('timetable_class')->where([['class_id', $request->class_id], ['section_id', $request->section_id], ['semester_id', $request->semester_id], ['session_id', $request->session_id], ['day', $request->day], ['academic_session_id', $request->academic_session_id]])->WhereNull('bulk_id')->get()->toArray();

                // return $oldest;
                $diff = array_diff(array_column($oldest, 'id'), array_column($timetable, 'id'));
                // dd($diff);
                if (isset($diff)) {
                    foreach ($diff as $del) {

                        // $delete =  $staffConn->table('timetable_class')->where('id', $del)->delete();
                        // // delete calendor data
                        // $staffConn->table('calendors')->where('time_table_id', $del)->delete();
                        if ($staffConn->table('timetable_class')->where('id', '=', $del)->count() > 0) {
                            // record found
                            // echo "time table" . $del;
                            $delete =  $staffConn->table('timetable_class')->where('id', $del)->delete();
                        }
                        // delete calendor data
                        if ($staffConn->table('calendors')->where('time_table_id', '=', $del)->count() > 0) {
                            // record found
                            // dd($del);
                            // echo "calendor" . $del;
                            $staffConn->table('calendors')->where('time_table_id', $del)->delete();
                        }
                    }
                }

                // return $timetable;

                foreach ($timetable as $table) {

                    // return $table;
                    $session_id = 0;
                    $semester_id = 0;

                    $break_type = NULL;
                    $break = 0;
                    $subject_id = 0;
                    $teacher_id = NULL;


                    if (isset($request['session_id'])) {
                        $session_id = $request['session_id'];
                    }
                    if (isset($request['semester_id'])) {
                        $semester_id = $request['semester_id'];
                    }
                    if (isset($table['break_type'])) {
                        $break_type = $table['break_type'];
                    }
                    if (isset($table['break'])) {
                        $break = 1;
                    }
                    if (!empty($table['teacher'])) {
                        $teacher_id =  implode(",", $table['teacher']);
                    }
                    if (isset($table['subject'])) {
                        $subject_id = $table['subject'];
                    }
                    //  dd($break_type);
                    $insertOrUpdateID = 0;
                    if (isset($table['id'])) {
                        // echo "<pre>";
                        // echo $teacher_id;
                        $query = $staffConn->table('timetable_class')->where('id', $table['id'])->update([
                            'class_id' => $request['class_id'],
                            'section_id' => $request['section_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'subject_id' => $subject_id,
                            'teacher_id' => (isset($teacher_id) ? $teacher_id : 0),
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                        $insertOrUpdateID = $table['id'];
                    } else {
                        // echo "<pre>";
                        // echo $teacher_id;
                        // exit;
                        $query = $staffConn->table('timetable_class')->insertGetId([
                            'class_id' => $request['class_id'],
                            'section_id' => $request['section_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'subject_id' => $subject_id,
                            'teacher_id' => (isset($teacher_id) ? $teacher_id : 0),
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'created_at' => date("Y-m-d H:i:s")
                        ]);
                        $insertOrUpdateID = $query;
                    }
                    $bulkID = NuLL;
                    // return $break;
                    // if(isset($break_type)){

                    // }
                    $this->addCalendorTimetable($request, $table, $getObjRow, $insertOrUpdateID, $bulkID);
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'TimeTable has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in copyTimetable');
        }
    }
    function addCalendorTimetable($request, $row, $getObjRow, $insertOrUpdateID, $bulkID)
    {
        // Create new connection
        try {
            $Connection = $this->createNewConnection($request->branch_id);

            // Delete existing calendar data
            $calendarsCount = $Connection->table('calendors')->where('time_table_id', $insertOrUpdateID)->where('sem_id', $request->semester_id)->count();
            if ($calendarsCount > 0) {
                $Connection->table('calendors')->where('time_table_id', $insertOrUpdateID)->where('sem_id', $request->semester_id)->delete();
            }
            if (!empty($getObjRow) && isset($request->day)) {
                // Determine the day of the week
                $day = null;
                switch ($request->day) {
                    case "monday":
                        $day = 1;
                        break;
                    case "tuesday":
                        $day = 2;
                        break;
                    case "wednesday":
                        $day = 3;
                        break;
                    case "thursday":
                        $day = 4;
                        break;
                    case "friday":
                        $day = 5;
                        break;
                    case "saturday":
                        $day = 6;
                        break;
                }

                // If day is set
                if ($day !== null) {
                    // Loop through each combination of elements from $getObjRow
                    foreach ($getObjRow as $val) {
                        $start = new DateTime($val->start_date);
                        $end = new DateTime($val->end_date);

                        // Call addTimetableCalendor function for each combination of elements
                        $this->addTimetableCalendor($request, $start, $end, $day, $row, $insertOrUpdateID, $bulkID);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addCalendorTimetable');
        }
    }
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }

}
