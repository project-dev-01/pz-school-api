<?php

namespace App\Http\Controllers\Api\V1\Academic;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Exception;

class TimetableController extends BaseController
{
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper)
    {
        $this->commonHelper = $commonHelper;
    }
    /**
     * @Chandru @since May 15,2024
     * @desc List section
     */
    // Timetable Subject Bulk
    public function timetableSubjectBulk(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // return $request;
                // create new connection
                $classConn = $this->createNewConnection($request->branch_id);
                $class_id = $request->class_id;

                $Timetable = $classConn->table('timetable_bulk')->select(
                    'timetable_bulk.*',
                    DB::raw('CONCAT(staffs.first_name, " ", staffs.last_name) as teacher_name')
                )
                    ->leftJoin('staffs', 'timetable_bulk.teacher_id', '=', 'staffs.id')
                    ->where([
                        ['timetable_bulk.day', $request->day],
                        ['timetable_bulk.class_id', $request->class_id],
                        ['timetable_bulk.semester_id', $request->semester_id],
                        ['timetable_bulk.session_id', $request->session_id],
                        ['timetable_bulk.academic_session_id', $request->academic_session_id],
                    ])
                    ->orderBy('time_start', 'asc')
                    ->orderBy('time_end', 'asc')
                    ->get()->toArray();
                $output['timetable'] = $Timetable;
                $output['teacher'] = $classConn->table('subject_assigns as sa')->select(
                    's.id',
                    DB::raw('CONCAT(s.last_name, " ", s.first_name) as name')
                )
                    ->join('staffs as s', 'sa.teacher_id', '=', 's.id')
                    ->when($class_id != "All", function ($q)  use ($class_id) {
                        $q->where('sa.class_id', $class_id);
                    })
                    // type zero mean main
                    ->where('sa.type', '=', '0')
                    ->where('sa.academic_session_id', $request->academic_session_id)
                    ->groupBy('sa.teacher_id')
                    ->get();
                $output['exam_hall'] = $classConn->table('exam_hall')->get();

                return $this->successResponse($output, 'Teacher and Subject record fetch successfully');
            }
        } catch (\Exception $error) {
            $this->commonHelper->generalReturn('403', 'error', $error, 'Error in timetableSubjectBulk');
        }
    }
    // add Bulk Timetable
    public function addBulkTimetable(Request $request)
    {
        try {
            // dd($request);
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
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
                $oldest = $staffConn->table('timetable_bulk')->where([['class_id', $request->class_id], ['semester_id', $request->semester_id], ['session_id', $request->session_id], ['day', $request->day], ['academic_session_id', $request->academic_session_id]])->get()->toArray();

                $diff = array_diff(array_column($oldest, 'id'), array_column($timetable, 'id'));

                if (isset($diff)) {
                    foreach ($diff as $del) {

                        if ($staffConn->table('timetable_class')->where('bulk_id', '=', $del)->count() > 0) {
                            $delete =  $staffConn->table('timetable_class')->where('bulk_id', $del)->get();
                            // delete calendor data
                            foreach ($delete as $d) {
                                if ($staffConn->table('calendors')->where('time_table_id', '=', $d->id)->count() > 0) {
                                    $staffConn->table('calendors')->where('time_table_id', $d->id)->delete();
                                }
                            }

                            // delete timetable data
                            $staffConn->table('timetable_class')->where('bulk_id', $del)->delete();
                        }

                        if ($staffConn->table('timetable_bulk')->where('id', '=', $del)->count() > 0) {
                            // record found
                            $staffConn->table('timetable_bulk')->where('id', $del)->delete();
                        }
                    }
                }
                foreach ($timetable as $table) {

                    // return $table;
                    $session_id = 0;
                    $semester_id = 0;

                    $break_type = NULL;
                    $break = 0;
                    $teacher_id = 0;


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
                    //  dd($break_type);
                    $bulkID = 0;
                    if (isset($table['id'])) {
                        // echo "<pre>";
                        // echo $teacher_id;
                        $query = $staffConn->table('timetable_bulk')->where('id', $table['id'])->update([
                            'class_id' => $request['class_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'teacher_id' => $teacher_id,
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                        $timeTableUpdate = $staffConn->table('timetable_class')->where('bulk_id', $table['id'])->update([
                            'break' => $break,
                            'break_type' => $break_type,
                            'teacher_id' => $teacher_id,
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'type' => "All",
                            'academic_session_id' => $request['academic_session_id'],
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);

                        $bulkID = $table['id'];
                        $class = $staffConn->table('timetable_class')->where('bulk_id', $bulkID)->get();
                        if ($class) {
                            foreach ($class as $cla) {
                                $timeTableID = $cla->id;
                                $request['section_id'] = "$cla->section_id";
                                // update calendor
                                $this->addCalendorTimetable($request, $table, $getObjRow, $timeTableID, $bulkID);
                            }
                        }

                        // $calendorUpdate = $staffConn->table('calendors')->where('bulk_id', $table['id'])->update([

                        //     "title" =>  $break_type,
                        //     'teacher_id' => $teacher_id,
                        //     'updated_at' => date("Y-m-d H:i:s"),
                        // ]);
                    } else {
                        // echo "<pre>";
                        // echo $teacher_id;
                        $query = $staffConn->table('timetable_bulk')->insertGetId([
                            'class_id' => $request['class_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'teacher_id' => $teacher_id,
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'created_at' => date("Y-m-d H:i:s")
                        ]);
                        $bulkID = $query;

                        $class = [];
                        // fetch class and section
                        if ($request['class_id'] == "All") {
                            $class = $staffConn->table('section_allocations')->select('class_id', 'section_id')->get();
                        } else {
                            $class = $staffConn->table('section_allocations')->select('class_id', 'section_id')->where('class_id', $request['class_id'])->get();
                        }
                        if ($class) {
                            foreach ($class as $cla) {
                                $timeTableID = $staffConn->table('timetable_class')->insertGetId([
                                    'class_id' => $cla->class_id,
                                    'section_id' => $cla->section_id,
                                    'break' => $break,
                                    'break_type' => $break_type,
                                    'teacher_id' => $teacher_id,
                                    'class_room' => $table['class_room'],
                                    'time_start' => $table['time_start'],
                                    'time_end' => $table['time_end'],
                                    'semester_id' => $semester_id,
                                    'session_id' => $session_id,
                                    'day' => $request['day'],
                                    'bulk_id' => $bulkID,
                                    'type' => "All",
                                    'academic_session_id' => $request['academic_session_id'],
                                    'created_at' => date("Y-m-d H:i:s")
                                ]);
                                $request['class_id'] = "$cla->class_id";
                                $request['section_id'] = "$cla->section_id";
                                // update calendor
                                $this->addCalendorTimetable($request, $table, $getObjRow, $timeTableID, $bulkID);
                            }
                        }
                    }
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'TimeTable has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addBulkTimetable');
        }
    }

    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
