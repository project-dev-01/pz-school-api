<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Exception;

class ClassroomManagemenController extends BaseController
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
    // gradeListByDepartment
    public function gradeListByDepartment(Request $request)
    {
        try {
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
                    ->select('cl.id', 'cl.name', 'cl.short_name', 'cl.name_numeric')
                    ->where([
                        ['cl.department_id', '=', $request->department_id],
                    ])
                    ->get();
                return $this->successResponse($success, 'grade list by department fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in gradeListByDepartment');
        }
    }
    // subjectByClass
    public function subjectByClass(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'section_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $classConn = $this->createNewConnection($request->branch_id);
                // get data
                // $teacher_id = "All";
                // if (isset($request->teacher_id)) {
                //     $teacher_id = $request->teacher_id;
                // }

                $class_id = $request->class_id;
                $class = $classConn->table('subject_assigns as sa')->select('s.id as subject_id', 's.name as subject_name')
                    ->join('subjects as s', 'sa.subject_id', '=', 's.id')

                    // ->when($teacher_id != "All", function ($ins)  use ($teacher_id) {
                    //     $ins->where('sa.teacher_id', $teacher_id);
                    // })
                    // ->where('sa.class_id', $class_id)
                    ->where([
                        ['sa.class_id', $request->class_id],
                        ['sa.section_id', $request->section_id],
                        ['sa.type', '=', '0'],
                        ['s.exam_exclude', '=', '0'],
                        ['sa.academic_session_id', $request->academic_session_id],
                    ])
                    // ->groupBy('s.id')
                    ->get();
                return $this->successResponse($class, 'Subject Name fetch successfully');
            }
        } catch (\Exception $error) {
            $this->commonHelper->generalReturn('403', 'error', $error, 'Error in subjectByClass');
        }
    }
    // getStudentAttendence
    function getStudentAttendence(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'subject_id' => 'required',
                'date' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                // get attendance details query
                $date = $request->date;
                $leave_date = date('Y-m-d', strtotime($request->date));
                $subject_id = $request->subject_id;
                $semester_id = $request->semester_id;
                $session_id = $request->session_id;
                $Connection = $this->createNewConnection($request->branch_id);
                $getStudentAttendence = $Connection->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'en.roll',
                        DB::raw('CONCAT(st.last_name, " ", st.first_name) as name'),
                        'st.register_no',
                        'sa.id as att_id',
                        'sa.status as att_status',
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
                    ->leftJoin('student_attendances as sa', function ($q) use ($date, $subject_id, $semester_id, $session_id) {
                        $q->on('sa.student_id', '=', 'st.id')
                            ->on('sa.date', '=', DB::raw("'$date'"))
                            ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                            ->on('sa.semester_id', '=', DB::raw("'$semester_id'"))
                            ->on('sa.session_id', '=', DB::raw("'$session_id'"));
                    })
                    // if already take attendance for the date
                    ->leftJoin('student_attendances as sapre', function ($q) use ($date, $semester_id, $session_id) {
                        $q->on('sapre.student_id', '=', 'st.id')
                            ->on('sapre.date', '=', DB::raw("'$date'"))
                            ->on('sapre.semester_id', '=', DB::raw("'$semester_id'"))
                            ->on('sapre.session_id', '=', DB::raw("'$session_id'"))
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
                        ['en.active_status', '=', "0"],
                        // ['en.semester_id', '=', $request->semester_id],
                        // ['en.session_id', '=', $request->session_id]
                    ])
                    ->groupBy('en.student_id')
                    ->get();

                // $getOldAttendance = $Connection->table('enrolls as en')
                //     ->select(
                //         'sapre.id',
                //         'sapre.student_id',
                //         // DB::raw('MAX(sapre.created_at)'),
                //         'sapre.created_at',
                //         'sapre.status as current_old_att_status'
                //     )
                //     ->leftJoin('students as st', 'st.id', '=', 'en.student_id')
                //     // if already take attendance for the date
                //     ->leftJoin('student_attendances as sapre', function ($q) use ($date, $semester_id, $session_id) {
                //         $q->on('sapre.student_id', '=', 'st.id')
                //             ->on('sapre.date', '=', DB::raw("'$date'"))
                //             ->on('sapre.semester_id', '=', DB::raw("'$semester_id'"))
                //             ->on('sapre.session_id', '=', DB::raw("'$session_id'"));
                //         // ->groupBy('sapre.student_id');
                //         // ->where('sapre.id', DB::raw("(select max(`id`) from student_attendances WHERE sapre.student_id = st.id)"));
                //         // ->on('sapre.day_recent_flag', '=', DB::raw("'1'"));
                //     })
                //     ->where([
                //         ['en.class_id', '=', $request->class_id],
                //         ['en.section_id', '=', $request->section_id],
                //         ['en.semester_id', '=', $request->semester_id],
                //         ['en.session_id', '=', $request->session_id]
                //     ])
                //     ->get();
                // dd($getOldAttendance);
                // $data = users::leftJoin('orders', function ($join) {
                //     $join->on('orders.user_id', '=', 'users.id')
                //         ->on('orders.id', '=', DB::raw("(SELECT max(id) from orders WHERE orders.user_id = users.id)"));
                // })
                //     ->select('*');
                // $result = $Connection->table("enrolls as en")
                //     ->select(
                //         'sul.id',
                //         'sul.student_id',
                //         DB::raw('MAX(sul.created_at)'),
                //         'sul.status as current_old_att_status'
                //     )
                //     ->leftJoin('students as st', 'st.id', '=', 'en.student_id')
                //     ->leftJoin('student_attendances as sul', function ($join) use ($date) {
                //         $join->on('sul.student_id', '=', 'st.id')
                //             ->where('sul.date', $date);
                //     })
                //     // ->orderBy('sul.created_at')
                //     ->groupBy('en.student_id')
                //     ->get();
                // $data = $Connection->table('enrolls')
                //     ->leftJoin('student_attendances', function ($join) {
                //         $join->on('student_attendances.student_id', '=', 'enrolls.student_id')
                //             ->on('student_attendances.id', '=', DB::raw("(SELECT max(id) from student_attendances WHERE student_attendances.student_id = enrolls.student_id)"));
                //     })
                //     ->select(
                //         'student_attendances.id',
                //         'student_attendances.student_id',
                //         'student_attendances.status'
                //     );
                // dd($getOldAttendance);
                $taken_attentance_status = $Connection->table('enrolls as en')
                    ->select(
                        'sa.status'
                    )
                    ->join('students as st', 'st.id', '=', 'en.student_id')
                    // if already take attendance for the date and subjects
                    ->leftJoin('student_attendances as sa', function ($q) use ($date, $subject_id, $semester_id, $session_id) {
                        $q->on('sa.student_id', '=', 'st.id')
                            ->on('sa.date', '=', DB::raw("'$date'"))
                            ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                            ->on('sa.semester_id', '=', DB::raw("'$semester_id'"))
                            ->on('sa.session_id', '=', DB::raw("'$session_id'"));
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
                return $this->successResponse($data, 'Attendance record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getStudentAttendence');
        }
    }
    // SectionByClass
    public function sectionByClass(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $classConn = $this->createNewConnection($request->branch_id);
                // get data
                $class_id = $request->class_id;
                $class = $classConn->table('section_allocations as sa')->select('s.id as section_id', 's.name as section_name')
                    ->join('sections as s', 'sa.section_id', '=', 's.id')
                    ->where('sa.class_id', $class_id)
                    ->get();
                return $this->successResponse($class, 'Class record fetch successfully');
            }
        } catch (\Exception $error) {
            $this->commonHelper->generalReturn('403', 'error', $error, 'Error in sectionByClass');
        }
    }
    // get Daily Report Remarks
    function getDailyReportRemarks(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'subject_id' => 'required',
                'date' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);
                $getDailyReportRemarks = $Connection->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        DB::raw('CONCAT(st.last_name, " ", st.first_name) as name'),
                        'dr.student_remarks',
                        'dr.teacher_remarks',
                        'dr.id',
                        'st.photo'
                    )
                    ->leftJoin('students as st', 'st.id', '=', 'en.student_id')
                    ->join('daily_report_remarks as dr', 'dr.student_id', '=', 'en.student_id')
                    ->where([
                        ['dr.class_id', '=', $request->class_id],
                        ['dr.section_id', '=', $request->section_id],
                        ['dr.subject_id', '=', $request->subject_id],
                        ['dr.semester_id', '=', $request->semester_id],
                        ['dr.session_id', '=', $request->session_id]
                    ])
                    ->get();
                $getDailyReport = $Connection->table('daily_reports as dr')
                    ->select(
                        'dr.date',
                        'dr.class_id',
                        'dr.section_id',
                        'dr.report',
                        'dr.subject_id',
                        'dr.id'
                    )
                    ->where([
                        ['dr.class_id', '=', $request->class_id],
                        ['dr.section_id', '=', $request->section_id],
                        ['dr.subject_id', '=', $request->subject_id],
                        ['dr.semester_id', '=', $request->semester_id],
                        ['dr.session_id', '=', $request->session_id],
                        ['dr.date', '=', $request->date]
                    ])
                    ->first();
                $data = [
                    'get_daily_report_remarks' => $getDailyReportRemarks,
                    'get_daily_report' => $getDailyReport
                ];
                return $this->successResponse($data, 'Daily report remarks fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getDailyReportRemarks');
        }
    }
    // get widget details
    function getClassroomWidget(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'subject_id' => 'required',
                'date' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $query_date = $request->date;
                // First day of the month.
                $startDate = date('Y-m-01', strtotime($query_date));
                // Last day of the month.
                $endDate = date('Y-m-t', strtotime($query_date));
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);
                $getWidgetDetails = $Connection->table('student_attendances as sa')
                    ->select(
                        DB::raw('COUNT(CASE WHEN sa.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                    )
                    ->where([
                        ['sa.class_id', '=', $request->class_id],
                        ['sa.section_id', '=', $request->section_id],
                        ['sa.subject_id', '=', $request->subject_id],
                        ['sa.semester_id', '=', $request->semester_id],
                        ['sa.session_id', '=', $request->session_id],
                        ['sa.date', '=', $request->date]
                    ])
                    // ->whereBetween(DB::raw('date(date)'), [$startDate, $endDate])
                    ->get();

                $avgAttendance = $Connection->table('student_attendances as sa')
                    ->select(
                        DB::raw('COUNT(CASE WHEN sa.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                        DB::raw('COUNT(DISTINCT sa.date) as "totalDate"')
                    )
                    ->where([
                        ['sa.class_id', '=', $request->class_id],
                        ['sa.section_id', '=', $request->section_id],
                        ['sa.subject_id', '=', $request->subject_id],
                        ['sa.semester_id', '=', $request->semester_id],
                        ['sa.session_id', '=', $request->session_id]
                    ])
                    ->whereBetween(DB::raw('date(date)'), [$startDate, $endDate])
                    ->get();

                $getStudentData = $Connection->table('student_attendances as sa')
                    ->select(
                        DB::raw('COUNT(CASE WHEN sa.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                        DB::raw('COUNT(sa.date) as "totalDaysCount"')
                    )
                    ->where([
                        ['sa.class_id', '=', $request->class_id],
                        ['sa.section_id', '=', $request->section_id],
                        ['sa.subject_id', '=', $request->subject_id],
                        ['sa.semester_id', '=', $request->semester_id],
                        ['sa.session_id', '=', $request->session_id],
                        ['sa.date', '=', $request->date]
                    ])
                    ->whereBetween(DB::raw('date(date)'), [$startDate, $endDate])
                    ->groupBy('sa.student_id')
                    ->get();

                $totalStudent = $Connection->table('enrolls as en')
                    ->select(
                        DB::raw('COUNT(en.student_id) as "totalStudentCount"')
                    )
                    ->where([
                        ['en.class_id', '=', $request->class_id],
                        ['en.section_id', '=', $request->section_id]
                        // ['en.semester_id', '=', $request->semester_id],
                        // ['en.session_id', '=', $request->session_id]
                    ])
                    ->get();

                $day = date('D', strtotime($query_date));

                $timetable_class = $Connection->table('timetable_class as tc')
                    ->select(
                        'tc.time_start',
                        'tc.time_end',
                        'tc.id'
                    )
                    ->where([
                        ['tc.class_id', '=', $request->class_id],
                        ['tc.section_id', '=', $request->section_id],
                        ['tc.subject_id', '=', $request->subject_id],
                        ['tc.semester_id', '=', $request->semester_id],
                        ['tc.session_id', '=', $request->session_id]
                    ])
                    ->where('tc.day', 'like', '%' . $day . '%')
                    ->first();
                $data = [
                    'avg_attendance' => $avgAttendance,
                    'get_widget_details' => $getWidgetDetails,
                    'get_student_data' => $getStudentData,
                    'total_student' => $totalStudent,
                    'timetable_class' => $timetable_class

                ];
                return $this->successResponse($data, 'Wigget record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getClassroomWidget');
        }
    }
    // getShortTest
    function getShortTest(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'date' => 'required',
                'subject_id' => 'required',
                'academic_session_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                // get attendance details query
                $date = $request->date;
                $subject_id = $request->subject_id;
                $semester_id = $request->semester_id;
                $session_id = $request->session_id;
                $academic_session_id = $request->academic_session_id;
                $Connection = $this->createNewConnection($request->branch_id);
                // $getTeachersClassName = $Connection->table('enrolls as en')
                //     ->select(
                //         'en.student_id',
                //         'en.roll',
                //         'st.first_name',
                //         'st.last_name'
                //     )
                //     ->leftJoin('students as st', 'st.id', '=', 'en.student_id')
                //     ->where([
                //         ['en.class_id', '=', $request->class_id],
                //         ['en.section_id', '=', $request->section_id]
                //     ])
                //     ->get();
                $getShortTest = $Connection->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'en.roll',
                        DB::raw('CONCAT(st.last_name, " ", st.first_name) as name'),
                        'st.register_no',
                        'sht.id as short_test_id',
                        'sht.test_marks',
                        'sht.grade_status',
                        'sht.date',
                        'sht.test_name',
                        'st.photo'
                    )
                    ->join('students as st', 'st.id', '=', 'en.student_id')
                    ->leftJoin('short_tests as sht', function ($q) use ($date, $subject_id, $semester_id, $session_id, $academic_session_id) {
                        $q->on('sht.student_id', '=', 'st.id')
                            ->on('sht.date', '=', DB::raw("'$date'"))
                            ->on('sht.subject_id', '=', DB::raw("'$subject_id'"))
                            ->on('sht.semester_id', '=', DB::raw("'$semester_id'"))
                            ->on('sht.session_id', '=', DB::raw("'$session_id'"))
                            ->on('sht.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                    })
                    ->where([
                        ['en.class_id', '=', $request->class_id],
                        ['en.section_id', '=', $request->section_id],
                        ['en.active_status', '=', '0'],
                        ['en.academic_session_id', '=', $academic_session_id]
                        // ['en.semester_id', '=', $request->semester_id],
                        // ['en.session_id', '=', $request->session_id]
                    ])
                    // ->groupBy('en.student_id')
                    ->get();
                return $this->successResponse($getShortTest, 'Short test record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getShortTest');
        }
    }
    //Class room management : get student leaves
    function get_studentleaves(Request $request)
    {
        try {
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
                // get data
                $compare_date = $request->classDate;
                $studentDetails = $conn->table('student_leaves as lev')
                    ->select(
                        'lev.id',
                        'lev.class_id',
                        'lev.section_id',
                        'lev.student_id',
                        DB::raw('CONCAT(std.last_name, " ", std.first_name) as name'),
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
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in get_studentleaves');
        }
    }
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
