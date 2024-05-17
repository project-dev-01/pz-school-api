<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Exception;
use File;

class HomeworkController extends BaseController
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
    // addHomework
    public function addHomework(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'title' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'subject_id' => 'required',
                'date_of_homework' => 'required',
                'date_of_submission' => 'required',
                'schedule_date' => '',
                'description' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
                'created_by' => 'required',
                'academic_session_id' => 'required',
                'semester_id' => '',
                'session_id' => '',
            ]);

            // return $request;

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $staffConn = $this->createNewConnection($request->branch_id);


                $now = now();
                $name = strtotime($now);
                $extension = $request->file_extension;
                $fileName = $name . "." . $extension;
                $path = '/public/' . $request->branch_id . '/teacher/homework/';
                File::ensureDirectoryExists(base_path() . $path);
                $base64 = base64_decode($request->file);
                $file = base_path() . $path . $fileName;
                $suc = file_put_contents($file, $base64);


                $query = $staffConn->table('homeworks')->insertGetId([
                    'title' => $request['title'],
                    'class_id' => $request['class_id'],
                    'section_id' => $request['section_id'],
                    'subject_id' => $request['subject_id'],
                    'semester_id' => $request['semester_id'],
                    'session_id' => $request['session_id'],
                    'date_of_homework' => $request['date_of_homework'],
                    'date_of_submission' => $request['date_of_submission'],
                    'schedule_date' => $request['schedule_date'],
                    'description' => $request['description'],
                    'document' => $fileName,
                    'status' => isset($request['status']) ? $request['status'] : "",
                    'created_by' => $request['created_by'],
                    'academic_session_id' => $request['academic_session_id'],
                    'created_at' => date("Y-m-d H:i:s")
                ]);

                $homework_id = $query;
                $getAssignStudent =  $staffConn->table('homeworks as h')->select('e.student_id', 'st.father_id', 'st.mother_id', 'st.guardian_id')
                    ->leftJoin('enrolls as e', function ($join) {
                        $join->on('e.class_id', '=', 'h.class_id')
                            ->on('e.section_id', '=', 'h.section_id')
                            ->on('e.academic_session_id', '=', 'h.academic_session_id');
                    })
                    ->leftJoin('students as st', 'e.student_id', '=', 'st.id')
                    ->where([
                        ['h.id', '=', $homework_id],
                    ])->get();

                // return $getAssignStudent;
                $assignerID = [];
                $fatherID = [];
                $motherID = [];
                $guardianID = [];
                if (isset($getAssignStudent)) {
                    foreach ($getAssignStudent as $key => $value) {
                        array_push($assignerID, $value->student_id);
                        if (isset($value->father_id) && $value->father_id != "") {

                            array_push($fatherID, $value->father_id);
                        }
                        if (isset($value->mother_id) && $value->mother_id != "") {

                            array_push($motherID, $value->mother_id);
                        }
                        if (isset($value->guardian_id) && $value->guardian_id != "") {
                            array_push($guardianID, $value->guardian_id);
                        }
                    }
                }
                $collection = collect($fatherID);
                $collection2 = $collection->merge($motherID);
                $collection3 = $collection2->merge($guardianID);
                // send Homework notifications
                $student = User::whereIn('user_id', $assignerID)->where([
                    ['branch_id', '=', $request->branch_id]
                ])->where(function ($q) {
                    $q->where('role_id', 6);
                })->get();
                $parent = User::whereIn('user_id', $collection3)->where([
                    ['branch_id', '=', $request->branch_id]
                ])->where(function ($q) {
                    $q->where('role_id', 5);
                })->get();

                $user = $student->merge($parent);
                // return $user;

                $homework = $staffConn->table('homeworks as h')->select('h.title as homework_name', 'c.name as class_name', 'sc.name as section_name', 'sbj.name as subject_name')
                    ->join('classes as c', 'h.class_id', '=', 'c.id')
                    ->join('sections as sc', 'h.section_id', '=', 'sc.id')
                    ->join('subjects as sbj', 'h.subject_id', '=', 'sbj.id')
                    ->where('h.id', $homework_id)->first();
                $homework->due_date = $request['date_of_submission'];

                $details = [
                    'branch_id' => $request->branch_id,
                    'teacher_id' => $request->created_by,
                    'homework_id' => $homework_id,
                    'homework' => $homework
                ];
                // return $details;
                // notifications sent
                Notification::send($user, new TeacherHomework($details));
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Homework has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addHomework');
        }
    }

    // get Homework List
    public function getHomeworkList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                // 'class_id' => 'required',
                // 'section_id' => 'required',
                // 'subject_id' => 'required',
                'academic_session_id' => 'required'
            ]);

            // return 1;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);
                $subject_id = $request->subject_id;
                $class_id = $request->class_id;
                $section_id = $request->section_id;
                $semester_id = $request->semester_id;
                $session_id = $request->session_id;
                $teacher_id = isset($request->teacher_id) ? $request->teacher_id : null;

                // get data
                // $homework['homework'] = $con->table('homeworks')->select('homeworks.*', 'sections.name as section_name', 'classes.name as class_name', 'subjects.name as subject_name', DB::raw('SUM(homework_evaluation.status = 1) as students_completed'))
                //     ->leftJoin('subjects', 'homeworks.subject_id', '=', 'subjects.id')
                //     ->leftJoin('sections', 'homeworks.section_id', '=', 'sections.id')
                //     ->leftJoin('classes', 'homeworks.class_id', '=', 'classes.id')
                //     ->leftJoin('homework_evaluation', 'homeworks.id', '=', 'homework_evaluation.homework_id')
                //     ->where('homeworks.class_id', $request->class_id)
                //     ->where('homeworks.section_id', $request->section_id)
                //     // ->where('homeworks.subject_id', $request->subject_id)
                //     ->when($subject != "All", function ($ins)  use ($subject) {
                //         $ins->where('homeworks.subject_id', $subject);
                //     })
                //     ->where('homeworks.semester_id', $request->semester_id)
                //     ->where('homeworks.session_id', $request->session_id)
                //     ->where('homeworks.academic_session_id', $request->academic_session_id)
                //     ->groupBy('homeworks.id')
                //     ->orderBy('homeworks.created_at', 'desc')
                //     ->get();
                // $homework['total_students'] =  $con->table('enrolls')
                //     ->where([
                //         ['class_id', '=', $request->class_id],
                //         ['section_id', '=', $request->section_id],
                //         ['semester_id', '=', $request->semester_id],
                //         ['session_id', '=', $request->session_id],
                //         ['academic_session_id', '=', $request->academic_session_id],
                //         ['active_status', '=', '0'],
                //     ])->count();
                $homework = $con->table('homeworks')
                    ->select(
                        'homeworks.*',
                        'sections.name as section_name',
                        'classes.name as class_name',
                        'subjects.name as subject_name',
                        // DB::raw('SUM(hwev.status = 1) as students_completed'),
                        DB::raw('SUM(CASE WHEN hwev.status = "1" then 1 ELSE 0 END) as "students_completed"'),
                        DB::raw('COUNT(en.id) as "studentCount"')
                    )
                    ->leftJoin('subjects', 'homeworks.subject_id', '=', 'subjects.id')
                    ->leftJoin('sections', 'homeworks.section_id', '=', 'sections.id')
                    ->leftJoin('classes', 'homeworks.class_id', '=', 'classes.id')
                    // ->leftJoin('homework_evaluation', 'homeworks.id', '=', 'homework_evaluation.homework_id')

                    // get student count informations
                    ->leftJoin('enrolls as en', function ($join) {
                        $join->on('homeworks.class_id', '=', 'en.class_id')
                            ->on('homeworks.section_id', '=', 'en.section_id')
                            ->on('homeworks.semester_id', '=', 'en.semester_id')
                            ->on('homeworks.session_id', '=', 'en.session_id')
                            ->on('homeworks.academic_session_id', '=', 'en.academic_session_id')
                            ->on('en.active_status', '=', DB::raw("'0'"));
                    })
                    ->leftJoin('homework_evaluation as hwev', function ($join) {
                        $join->on('hwev.homework_id', '=', 'homeworks.id')
                            ->on('hwev.student_id', '=', 'en.student_id');
                    })
                    ->when($class_id, function ($ins)  use ($class_id) {
                        $ins->where('homeworks.class_id', $class_id);
                    })
                    ->when($section_id, function ($ins)  use ($section_id) {
                        $ins->where('homeworks.section_id', $section_id);
                    })
                    ->when($subject_id != "All", function ($ins)  use ($subject_id) {
                        $ins->where('homeworks.subject_id', $subject_id);
                    })
                    ->when($semester_id, function ($ins)  use ($semester_id) {
                        $ins->where('homeworks.semester_id', $semester_id);
                    })
                    ->when($session_id, function ($ins)  use ($session_id) {
                        $ins->where('homeworks.session_id', $session_id);
                    })
                    ->when($teacher_id, function ($ins)  use ($teacher_id) {
                        $ins->where('homeworks.created_by', $teacher_id);
                    })
                    ->where('homeworks.academic_session_id', $request->academic_session_id)
                    ->groupBy('homeworks.id')
                    ->orderBy('homeworks.created_at', 'desc')
                    ->get();
                return $this->successResponse($homework, 'Homework record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHomeworkList');
        }
    }
    // view Homework
    public function viewHomework(Request $request)
    {
        // dd($request);
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'homework_id' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);
                // get data
                // $homework = $con->table('homework_evaluation as he')->select('he.*','s.first_name','s.last_name','s.register_no')->leftJoin('students as s', 'he.student_id', '=', 's.id')->where('he.homework_id',$request['homework_id'])->get();

                $homework_id = $request->homework_id;
                $status = $request->status;
                $evaluation = $request->evaluation;
                $query = $con->table('homeworks as h')
                    ->select(
                        's.first_name',
                        's.last_name',
                        's.register_no',
                        'h.id as homework_id',
                        'e.student_id',
                        'h.document',
                        'he.id as evaluation_id',
                        'he.file',
                        'he.remarks',
                        'he.status',
                        'he.rank',
                        'he.score_name',
                        'he.correction',
                        'he.homework_status',
                        'he.teacher_remarks',
                        'he.score_value'
                    )
                    ->join('enrolls as e', function ($q) use ($homework_id) {
                        $q->on('h.section_id', '=', 'e.section_id')
                            ->on('h.class_id', '=', 'e.class_id')
                            ->on('h.semester_id', '=', 'e.semester_id')
                            ->on('h.session_id', '=', 'e.session_id')
                            ->on('h.academic_session_id', '=', 'e.academic_session_id');
                    })
                    ->leftJoin('students as s', 'e.student_id', '=', 's.id')
                    ->leftJoin('homework_evaluation as he', function ($q) use ($evaluation) {
                        $q->on('h.id', '=', 'he.homework_id')
                            ->on('s.id', '=', 'he.student_id');
                    })
                    // ->where('e.semester_id', '=', $request->semester_id)
                    // ->where('e.session_id', '=', $request->session_id)
                    // ->where('e.active_status', '=', '0')
                    // ->where('e.academic_session_id', '=', $request->academic_session_id)
                    ->where('h.id', $request['homework_id']);
                $homework = $query->get();
                // dd($homework);

                return $this->successResponse($homework, 'Homework record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in viewHomework');
        }
    }

    // evaluate Homework
    public function evaluateHomework(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'homework' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);

                foreach ($request['homework'] as $home) {

                    // return $home;
                    $correction = 0;
                    if (isset($home['correction'])) {
                        $correction = 1;
                    }
                    $homework_status = "0";
                    if (isset($home['student_check'])) {
                        $homework_status = "1";
                    }
                    if ($home['homework_evaluation_id']) {
                        $query = $conn->table('homework_evaluation')->where('id', $home['homework_evaluation_id'])->update([
                            'score_name' => $home['score_name'],
                            'score_value' => $home['score_value'],
                            'teacher_remarks' => $home['teacher_remarks'],
                            'correction' => $correction,
                            'evaluated_by' => $request->evaluated_by,
                            'homework_status' => $homework_status,
                            'evaluation_date' => date("Y-m-d"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                    } else {
                        // if not submitted
                        if (isset($home['student_check'])) {
                            $checkExist = $conn->table('homework_evaluation')->where([
                                ['homework_id', '=', $home['homework_id']],
                                ['student_id', '=', $home['student_id']]
                            ])->first();

                            if (isset($checkExist->id)) {
                                $query = $conn->table('homework_evaluation')->where('id', $checkExist->id)->update([
                                    'score_name' => $home['score_name'],
                                    'score_value' => $home['score_value'],
                                    'teacher_remarks' => $home['teacher_remarks'],
                                    'correction' => $correction,
                                    'evaluated_by' => $request->evaluated_by,
                                    'homework_status' => $homework_status,
                                    'evaluation_date' => date("Y-m-d"),
                                    'updated_at' => date("Y-m-d H:i:s")
                                ]);
                            } else {
                                $query = $conn->table('homework_evaluation')->insert([
                                    'homework_id' => $home['homework_id'],
                                    'student_id' => $home['student_id'],
                                    'status' => 1,
                                    'file' => "",
                                    'date' => date("Y-m-d"),
                                    'score_name' => $home['score_name'],
                                    'score_value' => $home['score_value'],
                                    'teacher_remarks' => $home['teacher_remarks'],
                                    'correction' => $correction,
                                    'evaluated_by' => $request->evaluated_by,
                                    'homework_status' => $homework_status,
                                    'evaluation_date' => date("Y-m-d"),
                                    'created_at' => date("Y-m-d H:i:s")
                                ]);
                            }
                        }
                    }
                }

                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Homework has been Updated Successfully');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in evaluateHomework');
        }
    }


    // get Student Homework List
    public function studentHomework(Request $request)
    {
        try {
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
                $con = $this->createNewConnection($request->branch_id);

                $student = $con->table('enrolls')->where('student_id', $request->student_id)->where('active_status', '0')->first();
                // get data
                $student_id = $request->student_id;
                $homework = [];
                $homework['homeworks'] = Null;
                $homework['count'] = NULL;
                if ($student_id) {
                    $homework['homeworks'] = $con->table('homeworks')->select('homeworks.*', 'homework_evaluation.homework_status', 'sections.name as section_name', 'classes.name as class_name', 'subjects.name as subject_name', 'homeworks.document', 'homework_evaluation.file', 'homework_evaluation.evaluation_date', 'homework_evaluation.remarks', 'homework_evaluation.status', 'homework_evaluation.rank')
                        ->leftJoin('subjects', 'homeworks.subject_id', '=', 'subjects.id')
                        ->leftJoin('sections', 'homeworks.section_id', '=', 'sections.id')
                        ->leftJoin('classes', 'homeworks.class_id', '=', 'classes.id')
                        // ->leftJoin('homework_evaluation', 'homeworks.id', '=', 'homework_evaluation.homework_id')
                        // ,DB::raw('SUM(homework_evaluation.status = 1) as students_completed')
                        ->leftJoin('homework_evaluation', function ($join) use ($student_id) {
                            $join->on('homeworks.id', '=', 'homework_evaluation.homework_id')
                                ->on('homework_evaluation.student_id', '=', DB::raw("'$student_id'"));
                            // >on(DB::raw('COUNT(CASE WHEN homework_evaluation.date < homeworks.date_of_submission then 1 ELSE NULL END) as "presentCount"'));
                        })
                        ->where('homeworks.class_id', $student->class_id)
                        ->where('homeworks.section_id', $student->section_id)
                        ->where('homeworks.semester_id', $student->semester_id)
                        ->where('homeworks.session_id', $student->session_id)
                        ->where('homeworks.academic_session_id', $request->academic_session_id)
                        ->orderBy('homeworks.created_at', 'desc')
                        ->get();

                    $count = $con->table('homeworks')->select(DB::raw('SUM(homework_evaluation.date <= homeworks.date_of_submission) as ontime'), DB::raw('SUM(homework_evaluation.date > homeworks.date_of_submission) as late'))
                        ->leftJoin('homework_evaluation', 'homeworks.id', '=', 'homework_evaluation.homework_id')
                        ->where('homework_evaluation.student_id', $request->student_id)
                        ->where('homeworks.semester_id', $student->semester_id)
                        ->where('homeworks.session_id', $student->session_id)
                        ->where('homeworks.academic_session_id', $request->academic_session_id)
                        ->first();
                    $total = $count->ontime + $count->late;
                    $homework['count']['ontime'] = $count->ontime;
                    $homework['count']['late'] = $count->late;
                    if ($total == "0") {
                        $homework['count']['ontime_percentage'] = Null;
                        $homework['count']['late_percentage'] =  Null;
                    } else {
                        $homework['count']['ontime_percentage'] = round(($count->ontime / $total) * 100, 2);
                        $homework['count']['late_percentage'] =  round(($count->late / $total) * 100, 2);
                    }
                }


                // $homework['subjects'] = $con->table('subjects')->select('subjects.id', 'subjects.name')->join('subject_assigns', 'subject_assigns.subject_id', '=', 'subjects.id')->groupBy('subjects.id')->get();
                return $this->successResponse($homework, 'Homework record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in studentHomework');
        }
    }

    // get Student Homework List by filter
    public function studentHomeworkFilter(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'student_id' => 'required',
                'academic_session_id' => 'required',
            ]);

            //    return $request;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);

                $student = $con->table('enrolls')->where('student_id', $request->student_id)->where('active_status', '0')->first();
                // get data
                $student_id = $request->student_id;
                $status = $request->status;
                $subject = $request->subject;


                $query = $con->table('homeworks')->select('homeworks.*', 'homework_evaluation.evaluation_date', 'homework_evaluation.homework_status', 'sections.name as section_name', 'classes.name as class_name', 'subjects.name as subject_name', 'homeworks.document', 'homework_evaluation.file', 'homework_evaluation.remarks', 'homework_evaluation.status', 'homework_evaluation.rank')
                    ->leftJoin('subjects', 'homeworks.subject_id', '=', 'subjects.id')
                    ->leftJoin('sections', 'homeworks.section_id', '=', 'sections.id')
                    ->leftJoin('classes', 'homeworks.class_id', '=', 'classes.id')

                    ->leftJoin('homework_evaluation', function ($join) use ($student_id) {
                        $join->on('homeworks.id', '=', 'homework_evaluation.homework_id')
                            ->on('homework_evaluation.student_id', '=', DB::raw("'$student_id'"));
                        // >on(DB::raw('COUNT(CASE WHEN homework_evaluation.date < homeworks.date_of_submission then 1 ELSE NULL END) as "presentCount"'));
                    });
                if ($status == "1") {
                    $query->where(function ($query) use ($status) {
                        $query->where('homework_evaluation.status', $status);
                    })
                        ->where('homework_evaluation.student_id', $request->student_id);
                }
                if ($status == "0") {
                    $query->whereNotIn('homeworks.id', function ($q) use ($student_id) {
                        $q->select('homework_id')->from('homework_evaluation')->where('student_id', $student_id);
                    });
                }
                $query->when($subject != "All", function ($ins)  use ($subject) {
                    $ins->where('homeworks.subject_id', $subject);
                })
                    ->where('homeworks.class_id', $student->class_id)
                    ->where('homeworks.section_id', $student->section_id)
                    ->where('homeworks.semester_id', $student->semester_id)
                    ->where('homeworks.session_id', $student->session_id)
                    ->where('homeworks.academic_session_id', $request->academic_session_id)
                    ->orderBy('homeworks.created_at', 'desc');

                $homework['homeworks'] = $query->get();


                if ($subject == "All") {
                    $homework['subject'] = "All";
                } else {

                    $subname = $con->table('subjects')->select('name')->where('id', $subject)->first();
                    $homework['subject'] = $subname->name;
                }
                return $this->successResponse($homework, 'Homework record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in studentHomeworkFilter');
        }
    }


    //  Student submits Homework
    public function submitHomework(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'student_id' => 'required',
                'remarks' => 'required',
                'homework_id' => 'required',
                'file' => 'required',
                'file_extension' => '',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);
                $now = now();
                $name = strtotime($now);
                $extension = $request->file_extension;
                $fileName = $name . "." . $extension;
                $path = '/public/' . $request->branch_id . '/student/homework/';
                $base64 = base64_decode($request->file);
                File::ensureDirectoryExists(base_path() . $path);
                $file = base_path() . $path . $fileName;
                $suc = file_put_contents($file, $base64);

                // $query = $con->table('homework_evaluation')->insert([
                //     'homework_id' => $request['homework_id'],
                //     'student_id' => $request['student_id'],
                //     'remarks' => $request['remarks'],
                //     'status' => 1,
                //     'file' => $fileName,
                //     'date' => date("Y-m-d"),
                //     'created_at' => date("Y-m-d H:i:s")
                // ]);

                $checkExist = $con->table('homework_evaluation')->where([
                    ['homework_id', '=', $request['homework_id']],
                    ['student_id', '=', $request['student_id']]
                ])->first();

                if (isset($checkExist->id)) {
                    $query = $con->table('homework_evaluation')->where('id', $checkExist->id)->update([
                        'homework_id' => $request['homework_id'],
                        'student_id' => $request['student_id'],
                        'remarks' => $request['remarks'],
                        'status' => 1,
                        'file' => $fileName,
                        'homework_status' => "0",
                        'date' => date("Y-m-d"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                } else {
                    $query = $con->table('homework_evaluation')->insert([
                        'homework_id' => $request['homework_id'],
                        'student_id' => $request['student_id'],
                        'remarks' => $request['remarks'],
                        'status' => 1,
                        'file' => $fileName,
                        'homework_status' => "0",
                        'date' => date("Y-m-d"),
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                }


                $teacher =  $con->table('homeworks as h')->select('sa.teacher_id')
                    ->leftJoin('subject_assigns as sa', function ($join) {
                        $join->on('sa.class_id', '=', 'h.class_id')
                            ->on('sa.section_id', '=', 'h.section_id')
                            ->on('sa.subject_id', '=', 'h.subject_id')
                            ->on('sa.academic_session_id', '=', 'h.academic_session_id');
                    })->where([
                        ['h.id', '=', $request['homework_id']],
                    ])->first();

                $homework = $con->table('homeworks as h')->select('h.title as homework_name', 'c.name as class_name', 'sc.name as section_name', 'sbj.name as subject_name')
                    ->join('classes as c', 'h.class_id', '=', 'c.id')
                    ->join('sections as sc', 'h.section_id', '=', 'sc.id')
                    ->join('subjects as sbj', 'h.subject_id', '=', 'sbj.id')
                    ->where('h.id', $request->homework_id)->first();
                $homework->student_name = $request->student_name;
                $homework->date = date('Y-m-d');

                $user = User::where('user_id', $teacher->teacher_id)->where([
                    ['branch_id', '=', $request->branch_id]
                ])->where(function ($q) {
                    $q->where('role_id', 2)
                        ->orWhere('role_id', 3)
                        ->orWhere('role_id', 4);
                })->get();
                $details = [
                    'branch_id' => $request->branch_id,
                    'student_id' => $request->student_id,
                    'homework_id' => $request->homework_id,
                    'homework' => $homework
                ];
                // return $details;
                // notifications sent
                Notification::send($user, new StudentHomeworkSubmit($details));
                //chess
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Homework has been Submitted Successfully ');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in submitHomework');
        }
    }
    // get Homework All List
    public function getHomeworkAllList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                // 'class_id' => 'required',
                // 'section_id' => 'required',
                // 'subject_id' => 'required',
                // 'academic_session_id' => 'required'
            ]);

            // return 1;
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);
                $teacher_id = isset($request->teacher_id) ? $request->teacher_id : null;
                // get data
                $homework = $con->table('homeworks')
                    ->select(
                        'homeworks.*',
                        'sections.name as section_name',
                        'classes.name as class_name',
                        'subjects.name as subject_name',
                        // DB::raw('SUM(homework_evaluation.status = 1) as students_completed'),
                        DB::raw('SUM(CASE WHEN hwev.status = "1" then 1 ELSE 0 END) as "students_completed"'),
                        DB::raw('COUNT(en.id) as "studentCount"')
                    )
                    ->leftJoin('subjects', 'homeworks.subject_id', '=', 'subjects.id')
                    ->leftJoin('sections', 'homeworks.section_id', '=', 'sections.id')
                    ->leftJoin('classes', 'homeworks.class_id', '=', 'classes.id')
                    // ->leftJoin('homework_evaluation', 'homeworks.id', '=', 'homework_evaluation.homework_id')
                    // get student count informations
                    ->leftJoin('enrolls as en', function ($join) {
                        $join->on('homeworks.class_id', '=', 'en.class_id')
                            ->on('homeworks.section_id', '=', 'en.section_id')
                            ->on('homeworks.semester_id', '=', 'en.semester_id')
                            ->on('homeworks.session_id', '=', 'en.session_id')
                            ->on('homeworks.academic_session_id', '=', 'en.academic_session_id');
                        // ->on('en.active_status', '=', DB::raw("'0'"));
                    })
                    ->leftJoin('homework_evaluation as hwev', function ($join) {
                        $join->on('hwev.homework_id', '=', 'homeworks.id')
                            ->on('hwev.student_id', '=', 'en.student_id');
                    })
                    ->when($teacher_id, function ($ins)  use ($teacher_id) {
                        $ins->where('homeworks.created_by', $teacher_id);
                    })
                    // ->where('homeworks.class_id', $request->class_id)
                    // ->where('homeworks.section_id', $request->section_id)
                    // ->where('homeworks.subject_id', $request->subject_id)
                    // ->where('homeworks.semester_id', $request->semester_id)
                    // ->where('homeworks.session_id', $request->session_id)
                    // ->where('homeworks.academic_session_id', $request->academic_session_id)
                    ->groupBy('homeworks.id')
                    ->orderBy('homeworks.created_at', 'desc')
                    ->get();
                // $homework['total_students'] =  $con->table('enrolls')
                //     ->where([
                //         ['class_id', '=', $request->class_id],
                //         ['section_id', '=', $request->section_id],
                //         ['semester_id', '=', $request->semester_id],
                //         ['session_id', '=', $request->session_id],
                //         ['academic_session_id', '=', $request->academic_session_id],
                //         ['active_status', '=', '0'],
                //     ])->count();
                return $this->successResponse($homework, 'All homework record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHomeworkAllList');
        }
    }
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
