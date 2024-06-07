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

class ExamreportController extends BaseController
{
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper)
    {
        $this->commonHelper = $commonHelper;
    }
    public function getSubjectByPaper(Request $request)
    {


        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'exam_id' => 'required',
                'academic_session_id' => 'required',
                'subject_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);
                /*$examPapers = $Connection->table('timetable_exam as tex')
                ->select(
                    'tex.id as id',
                    'exp.id as paper_id',
                    'exp.paper_name',
                    'exp.grade_category'
                )
                ->join('exam_papers as exp', 'tex.paper_id', '=', 'exp.id')
                
                ->where([
                    ['tex.class_id', $request->class_id],
                    ['tex.section_id', $request->section_id],
                    ['tex.subject_id', $request->subject_id],
                    ['tex.academic_session_id', $request->academic_session_id],
                    ['tex.exam_id', $request->exam_id]
                ])
                ->groupBy('exp.id')
                ->get();*/
                $examPapers = $Connection->table('exam_papers as exp')
                    ->select(
                        'exp.id',
                        'exp.paper_name',
                        'exp.grade_category'
                    )
                    ->where([
                        ['exp.class_id', $request->class_id],
                        ['exp.subject_id', $request->subject_id],
                        ['exp.academic_session_id', $request->academic_session_id]
                    ])
                    ->get();
                $enrollCount = $Connection->table('enrolls as en')
                    ->select(1)
                    ->where([
                        ['en.class_id', '=', $request->class_id],
                        ['en.section_id', '=', $request->section_id],
                        ['en.academic_session_id', '=', $request->academic_session_id]
                    ])->count();

                $paper_list = [];
                foreach ($examPapers as $paper) {
                    $markcount = $Connection->table('student_marks')->select('1')->where([
                        ['class_id', '=', $request->class_id],
                        ['section_id', '=', $request->section_id],
                        ['subject_id', '=', $request->subject_id],
                        ['exam_id', '=', $request->exam_id],
                        ['semester_id', '=', $request->semester_id],
                        ['paper_id', '=', $paper->id],
                        ['academic_session_id', '=', $request->academic_session_id]
                    ])->count();

                    $data = [
                        'id' => $paper->id,
                        'paper_id' => $paper->id,
                        'paper_name' => $paper->paper_name,
                        'grade_category' => $paper->grade_category,
                        'totstu' => $enrollCount,
                        'examstu' =>  $markcount
                    ];

                    array_push($paper_list, $data);
                }

                return $this->successResponse($paper_list, 'get papers fetch successfully');
            }
        } catch (\Exception $error) {
            $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getSubjectByPaper');
        }
    }
    public function getExamByPaper(Request $request)
    {


        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'exam_id' => 'required',
                'academic_session_id' => 'required',
                'subject_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);
                
                $getpapersQuery = $Connection->table('exam_papers as exp')
                    ->select(
                        'exp.id',
                        'exp.paper_name',
                        'exp.score_type'
                    )
                    ->where([
                        ['exp.class_id', $request->class_id],
                        ['exp.subject_id', $request->subject_id],
                        ['exp.academic_session_id', $request->academic_session_id]
                    ]);
                if ($request->paper_id != 'All') {
                    $getpapersQuery->where('exp.id', '=', $request->paper_id);
                }
                $examPapers = $getpapersQuery->get();

                $enrollCount = $Connection->table('enrolls as en')
                    ->select(1)
                    ->where([
                        ['en.class_id', '=', $request->class_id],
                        ['en.section_id', '=', $request->section_id],
                        ['en.academic_session_id', '=', $request->academic_session_id]
                    ])->count();

                $paper_list = [];
                foreach ($examPapers as $paper) {
                    $markcount = $Connection->table('student_marks')->select('1')->where([
                        ['class_id', '=', $request->class_id],
                        ['section_id', '=', $request->section_id],
                        ['subject_id', '=', $request->subject_id],
                        ['exam_id', '=', $request->exam_id],
                        ['semester_id', '=', $request->semester_id],
                        ['paper_id', '=', $paper->id],
                        ['academic_session_id', '=', $request->academic_session_id]
                    ])->count();

                    $data = [
                        'id' => $paper->id,
                        'paper_id' => $paper->id,
                        'paper_name' => $paper->paper_name,
                        'score_type' => $paper->score_type,
                        'totstu' => $enrollCount ?? 0,
                        'examstu' =>  $markcount ?? 0
                    ];

                    array_push($paper_list, $data);
                }

                return $this->successResponse($paper_list, 'get papers fetch successfully');
            }
        } catch (\Exception $error) {
            $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getSubjectByPaper');
        }
    }
    public function get_jsklsubjectlist(Request $request)
    {
        $Connection = $this->createNewConnection($request->branch_id);

        $subjectdetails = $Connection->table('subjects_pdf as sa')
            ->select(
                'sa.name_jp',
                'sa.name_en'
            )
            ->where('sa.status', '=', '1')
            ->orderBy('sa.id', 'asc')
            ->get();

        return $this->successResponse($subjectdetails, 'Get Subject Lists');
    }
    public function getjsklexampaper_list(Request $request)
    {
        $Connection = $this->createNewConnection($request->branch_id);

        $exampaperdetails = $Connection->table('exam_papers_pdf as ep')
            ->select(
                'ep.name_jp',
                'ep.name_en',
                'ep.score_type',
            )
            ->where('ep.status', '=', '1')
            ->orderBy('ep.id', 'asc')
            ->get();

        return $this->successResponse($exampaperdetails, 'Get Exam Papers Lists');
    }
    public function get_subject_wise_paper_list(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'department_id' => 'required',
                'codes' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);
                $exampaperdetails = $Connection->table('exam_papers_pdf as ep')
                    ->select(
                        'ep.name_jp',
                        'ep.name_en',
                        'ep.score_type',
                    )
                    ->where('ep.codes', '=', $request->codes)
                    ->whereRaw("FIND_IN_SET($request->department_id,ep.department)")
                    ->orderBy('ep.id', 'asc')
                    ->get();
                return $this->successResponse($exampaperdetails, 'Get Exam Papers Lists');
            }
        } catch (\Exception $error) {
            $this->commonHelper->generalReturn('403', 'error', $error, 'Error in get Subject Wise Paper List');
        }
    }
    public function exam_file_name(Request $request)
    {
        $department_id = $request->department_id;
        $exam_id = $request->exam_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $subject_id = $request->subject_id;
        $paper_id = $request->paper_id;
        $semester_id = $request->semester_id;
        $session_id = $request->session_id;
        $academic_session_id = $request->academic_session_id;
        $Connection = $this->createNewConnection($request->branch_id);

        $getclass = $Connection->table('classes')
            ->select(
                'classes.name as class_name',
                'dp.name as department_name'
            )
            ->leftJoin('staff_departments as dp', 'dp.id', '=', 'classes.department_id')
            ->where('classes.id', '=', $class_id)
            ->first();
        $getsection = $Connection->table('sections')->select('name as section_name')->where('id', '=', $section_id)->first();
        $getexam = $Connection->table('exam')->select('name as exam_name')->where('id', '=', $exam_id)->first();
        $getsem = $Connection->table('semester')->select('name as semester_name')->where('id', '=', $semester_id)->first();
        $getSubjectteacher = $Connection->table('subject_assigns as sa')
            ->select(
                'st.id',
                'st.first_name',
                'st.last_name',
                'sb.name as subject_name'
            )
            ->leftJoin('staffs as st', 'st.id', '=', 'sa.teacher_id')
            ->leftJoin('subjects as sb', 'sb.id', '=', 'sa.subject_id')
            ->where([
                ['sa.class_id', '=', $class_id],
                ['sa.subject_id', '=', $subject_id],
                ['sa.section_id', '=', $section_id],
                ['sa.academic_session_id', '=', $academic_session_id],
                ['sa.department_id', '=', $department_id]
            ])
            ->first();
        $enrollCount = $Connection->table('enrolls as en')
            ->select(1)
            ->where([
                ['en.class_id', '=', $request->class_id],
                ['en.section_id', '=', $request->section_id],
                ['en.academic_session_id', '=', $request->academic_session_id]
            ])->count();
        $data = [
            "department_name" => $getclass->department_name,
            "class_name" => $getclass->class_name,
            "section_name" => $getsection->section_name,
            "subject_name" => $getSubjectteacher->subject_name,
            "exam_name" => $getexam->exam_name,
            "semester_name" => $getsem->semester_name,
            "teachername" => isset($getSubjectteacher) ? ($getSubjectteacher->last_name . ' ' . $getSubjectteacher->first_name) : '',
            "totalstudent" => $enrollCount ?? 0
        ];
        return $this->successResponse($data, 'Get student Detatils');
    }
    public function adhocexam_file_name(Request $request)
    {
        $department_id = $request->department_id;
        $exam_id = $request->exam_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $subject_id = $request->subject_id;
        $academic_session_id = $request->academic_session_id;
        $Connection = $this->createNewConnection($request->branch_id);
        $getpapers = $Connection->table('subjects as sb')
            ->select('sb.name as subject_name')
            ->where('sb.id', '=', $subject_id)
            ->first();
        $getclass = $Connection->table('classes')
            ->select(
                'classes.name as class_name',
                'dp.name as department_name'
            )
            ->leftJoin('staff_departments as dp', 'dp.id', '=', 'classes.department_id')
            ->where('classes.id', '=', $class_id)
            ->first();
        $getsection = $Connection->table('sections')->select('name as section_name')->where('id', '=', $section_id)->first();
        $getexam = $Connection->table('exam')->select('name as exam_name')->where('id', '=', $exam_id)->first();
        $data = [
            "department_name" => $getclass->department_name,
            "class_name" => $getclass->class_name,
            "section_name" => $getsection->section_name,
            "subject_name" => $getpapers->subject_name,
            "exam_name" => $getexam->exam_name,

        ];
        return $this->successResponse($data, 'Get File Detatils');
    }

    public function exam_student_list(Request $request)
    {

        $department_id = $request->department_id;
        $exam_id = $request->exam_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $subject_id = $request->subject_id;
        $paper_id = $request->paper_id;
        $semester_id = $request->semester_id;
        $session_id = $request->session_id;
        $academic_session_id = $request->academic_session_id;
        $Connection = $this->createNewConnection($request->branch_id);

        $getstudentDetails = $Connection->table('enrolls')
            ->select(
                DB::raw('CONCAT(students.last_name, " ", students.first_name) as student_name'),
                'students.register_no',
                'students.id'
            )
            ->leftJoin('students', 'enrolls.student_id', '=', 'students.id')
            ->where('enrolls.class_id', '=', $class_id)
            ->where('enrolls.section_id', '=', $section_id)
            ->where('enrolls.department_id', '=', $department_id)
            ->where('enrolls.academic_session_id', '=', $academic_session_id)
            ->get();
        $getpapersQuery = $Connection->table('exam_papers as ep')
            ->select(
                'ep.id',
                'ep.paper_name',
                'ep.score_type',
                'sb.name as subject_name'
            )
            ->leftJoin('subjects as sb', 'sb.id', '=', 'ep.subject_id')
            ->where([
                ['ep.department_id', '=', $request->department_id],
                ['ep.class_id', '=', $request->class_id],
                ['ep.subject_id', '=', $subject_id],
                ['ep.academic_session_id', '=', $request->academic_session_id]
            ]);

        if ($paper_id != 'All') {
            $getpapersQuery->where('ep.id', '=', $paper_id);
        }

        $getpapers = $getpapersQuery->get();
        $student_list = [];
        $k = 0;
        foreach ($getpapers as $paper) {
            foreach ($getstudentDetails as $stu) {
                $k++;
                $student_id = $stu->id;
                $row = $Connection->table('student_marks')->select('points', 'freetext', 'score', 'grade', 'status', 'memo')->where([
                    ['class_id', '=', $class_id],
                    ['section_id', '=', $section_id],
                    ['subject_id', '=', $subject_id],
                    ['student_id', '=', $student_id],
                    ['exam_id', '=', $exam_id],
                    ['semester_id', '=', $semester_id],
                    ['session_id', '=', $session_id],
                    ['paper_id', '=', $paper->id],
                    ['academic_session_id', '=', $academic_session_id]
                ])->first();
                if ($row !== null) {
                    if ($paper->score_type == 'Points') {
                        $id = isset($row->points) ? $row->points : '.';
                        $grade_marks = $Connection->table('grade_marks')->select('id', 'grade', 'status')->where([
                            ['id', '=', $id]
                        ])->first();
                        $mark = isset($grade_marks->grade) ? $grade_marks->grade : '';
                    } elseif ($paper->score_type == 'Freetext') {
                        $mark = isset($row->freetext) ? $row->freetext : '';
                    } else {
                        $mark = isset($row->score) ? $row->score : '';
                    }
                    $status = isset($row->status) ? $row->status : '';
                    $memo = isset($row->memo) ? $row->memo : '';
                    $data = [
                        "sno" => $k,
                        "register_no" => $stu->register_no,
                        "student_name" => $stu->student_name,
                        "paper_name" => $paper->paper_name,
                        "score_type" =>  $paper->score_type,
                        "mark" => $mark,
                        "attandance" => $status[0],
                        "memo" => $memo
                    ];
                } else {
                    $data = [
                        "sno" => $k,
                        "register_no" => $stu->register_no,
                        "student_name" => $stu->student_name,
                        "paper_name" => $paper->paper_name,
                        "score_type" =>  $paper->score_type,
                        "mark" => "",
                        "attandance" =>  "p",
                        "memo" => ""
                    ];
                }

                array_push($student_list, $data);
            }
        }
        return $this->successResponse($student_list, 'Get student Detatils');
    }

    public function adhocexam_student_list(Request $request)
    {

        $department_id = $request->department_id;
        $exam_id = $request->exam_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $subject_id = $request->subject_id;
        $academic_session_id = $request->academic_session_id;
        $Connection = $this->createNewConnection($request->branch_id);

        $getstudentDetails = $Connection->table('enrolls')
            ->select(
                DB::raw('CONCAT(students.last_name, " ", students.first_name) as student_name'),
                'students.register_no',
                'students.id'
            )
            ->leftJoin('students', 'enrolls.student_id', '=', 'students.id')
            ->where('enrolls.class_id', '=', $class_id)
            ->where('enrolls.section_id', '=', $section_id)
            ->where('enrolls.department_id', '=', $department_id)
            ->where('enrolls.academic_session_id', '=', $academic_session_id)
            ->get();
        $getpapers = $Connection->table('subjects as sb')
            ->select('sb.name as subject_name')
            ->where('sb.id', '=', $subject_id)
            ->first();
        $student_list = [];
        $k = 0;
        foreach ($getstudentDetails as $stu) {
            $k++;
            $student_id = $stu->id;
            /*$row = $Connection->table('student_marks')->select('*')->where([
                ['class_id', '=', $class_id],
                ['section_id', '=', $section_id],
                ['subject_id', '=', $subject_id],
                ['student_id', '=', $student_id],
                ['exam_id', '=', $exam_id],
                ['semester_id', '=', $semester_id],
                ['session_id', '=', $session_id],
                ['paper_id', '=', $paper_id],
                ['academic_session_id', '=', $academic_session_id]
                ])->first();
            if($row!==null)
            {
                if($getpapers->score_type=='Points')
                {
                    $mark=isset($row->points)?$row->points:'';
                }
                elseif($getpapers->score_type=='Freetext')
                {
                    $mark=isset($row->freetext)?$row->freetext:'';
                }
                else
                {
                    $mark=isset($row->score)?$row->score:'';
                }
                $status=isset($row->status)?$row->status:'';
                $memo=isset($row->memo)?$row->memo:'';
            $data=[
                "sno"=> $k, 
                "register_no"=> $stu->register_no,               
                "student_name"=> $stu->student_name,            
                "mark"=> $mark,  
                "attandance"=>  $status[0],             
                "memo"=> $memo    
            ];
        }
        else
        {
            $data=[
                "sno"=> $k, 
                "register_no"=> $stu->register_no,               
                "student_name"=> $stu->student_name,            
                "mark"=> "",  
                "attandance"=>  "",             
                "memo"=> ""   
            ];
        }*/
            $data = [
                "sno" => $k,
                "register_no" => $stu->register_no,
                "student_name" => $stu->student_name,
                "mark" => "",

            ];
            array_push($student_list, $data);
        }
        return $this->successResponse($student_list, 'Get student Detatils');
    }
    public function mark_comparison(Request $request)
    {
        $Connection = $this->createNewConnection($request->branch_id);
        $department_id = $request->department_id;

        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $exam_id = $request->exam_id;
        $subject_id = $request->subject_id;
        $paper_id = $request->paper_id;
        $semester_id = $request->semester_id;
        $session_id = $request->session_id;
        $papername = $request->papername;
        $score_type = $request->score_type;
        $mark = $request->mark;
        $academic_session_id = $request->academic_session_id;
        $student_regno = $request->student_regno;

        $row = 0;
        $getpapersQuery = $Connection->table('exam_papers as ep')
            ->select(
                'ep.id',
                'ep.paper_name',
                'ep.score_type',
                'sb.name as subject_name'
            )
            ->leftJoin('subjects as sb', 'sb.id', '=', 'ep.subject_id')
            ->where([
                ['ep.department_id', '=', $request->department_id],
                ['ep.class_id', '=', $request->class_id],
                ['ep.subject_id', '=', $subject_id],
                ['ep.academic_session_id', '=', $request->academic_session_id]
            ]);

        if ($paper_id != 'All') {
            $getpapersQuery->where('ep.id', '=', $paper_id);
        } else {
            $getpapersQuery->where('ep.paper_name', '=', $papername);
            $getpapersQuery->where('ep.score_type', '=', $score_type);
        }

        $getpapers = $getpapersQuery->first();
        $paperID = ($getpapers !== null) ? $getpapers->id : '0';
        $students = $Connection->table('students')->select('id', 'first_name', 'last_name')->where('register_no', '=', $student_regno)->first();
        $points = '';
        if ($score_type == 'Points' && $mark != '') {
            $grade_marks = $Connection->table('grade_marks')->select('id', 'grade', 'status')->where([

                ['grade', '=', $mark]
            ])->first();
            $points = ($grade_marks != null) ? $grade_marks->id : '';
        }
        if ($students !== null) {
            $student_id = $students->id;
            $student_name = $students->last_name . ' ' . $students->first_name;

            $row = $Connection->table('student_marks')->select('*')->where([
                ['class_id', '=', $class_id],
                ['section_id', '=', $section_id],
                ['subject_id', '=', $subject_id],
                ['student_id', '=', $student_id],
                ['exam_id', '=', $exam_id],
                ['semester_id', '=', $semester_id],
                ['paper_id', '=', $paperID],
                ['academic_session_id', '=', $academic_session_id]
            ])->first();

            $data = [

                "register_no" =>  $student_regno,
                "student_id" =>  $student_id,
                "student_name" =>  $student_name,
                "mark_id" => ($row !== null) ? $row->id : '',
                "score" => ($row !== null) ? $row->score : '',
                "points" => ($row !== null) ? $row->points : '',
                "freetext" => ($row !== null) ? $row->freetext : '',
                "status" => ($row !== null) ? $row->status : '',
                "memo" => ($row !== null) ? $row->memo : '',
                "point_grade" => ($points !== null) ? $points : ''
            ];
        } else {
            $data = [
                "register_no" =>  $student_regno,
                "student_id" => "",
                "student_name" => "",
                "mark_id" => "",
                "score" => "",
                "points" => "",
                "freetext" => "",
                "status" => "",
                "memo" => "",
                "point_grade" => ($points !== null) ? $points : ''
            ];
        }

        return $this->successResponse($data, 'Get student Detatils');
    }
    public function adhocmark_comparison(Request $request)
    {
        $Connection = $this->createNewConnection($request->branch_id);
        $department_id = $request->department_id;

        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $exam_id = $request->exam_id;
        $subject_id = $request->subject_id;
        $exam_date = $request->exam_date;
        $academic_session_id = $request->academic_session_id;
        $student_regno = $request->student_regno;
        $row = 0;

        $students = $Connection->table('students')->select('id', 'first_name', 'last_name')->where('register_no', '=', $student_regno)->first();

        if ($students !== null) {
            $student_id = $students->id;
            $student_name = $students->last_name . ' ' . $students->first_name;
            $row = $Connection->table('adhocexam_marks')->select('*')->where([
                ['department_id', '=', $department_id],
                ['class_id', '=', $class_id],
                ['section_id', '=', $section_id],
                ['subject_id', '=', $subject_id],
                ['student_id', '=', $student_id],
                ['exam_id', '=', $exam_id],
                ['exam_date', '=', $exam_date],
                ['academic_session_id', '=', $academic_session_id]
            ])->first();

            $data = [

                "register_no" =>  $student_regno,
                "student_id" =>  $student_id,
                "student_name" =>  $student_name,
                "mark_id" => ($row !== null) ? $row->id : '',
                "mark" => ($row !== null) ? $row->mark : '',
                "memo" => ($row !== null) ? $row->memo : ''

            ];
        } else {
            $data = [
                "register_no" =>  $student_regno,
                "student_id" => "",
                "student_name" => "",
                "mark_id" => "",
                "mark" => "",
                "memo" => ""
            ];
        }

        return $this->successResponse($data, 'Get student Detatils');
    }
    /*public function examuploadmark(Request $request)
    {
        $Connection = $this->createNewConnection($request->branch_id);
        $department_id = $request->department_id;
     
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $exam_id = $request->exam_id;
        $subject_id = $request->subject_id;
        $paper_id = $request->paper_id;
        $semester_id = $request->semester_id;
        $session_id = $request->session_id;
        $academic_session_id = $request->academic_session_id;
        $fdata = $request->fdata;
        $row=0;
        foreach ($fdata as $importData) {
                     
            $row++;
            if($importData[1]!='')
            {
                 $student_roll = $importData[1];
            
            
                $score = "";
                $points = "";
                $freetext =  "";
                $grade = "";
                $ranking = "";
                $memo = $importData[5];
                $pass_fail = "";
                $att=strtolower($importData[4]);
                if($att=="p" || $att=="present")
                {
                $status = "present";
                }
                elseif($att=="a" || $att=="absent")
                {
                $status = "absent";
                }
                $mark =($att!='a')? $importData[3]:0;
                
                $students = $Connection->table('students')->select('id', 'first_name', 'last_name')->where('register_no', '=', $student_roll)->first();
                  $student_id = $students->id;
                

                
                  $paper1 = $Connection->table('exam_papers')->select('id', 'grade_category', 'score_type')->where([
                    ['id', '=', $paper_id]
                ])->first();
                if ($paper1 != null) { 
                $grade_category1 = $paper1->grade_category;
                if ($paper1->score_type == 'Grade' || $paper1->score_type == 'Mark') {
                     $grade_marks = $Connection->table('grade_marks')->select('id', 'grade', 'status')->where([
                            ['grade_category', '=', $grade_category1],
                            ['min_mark', '<=', $mark],
                            ['max_mark', '>=', $mark]
                        ])->first();
                       
                        
                        $grade =($grade_marks!=null)?$grade_marks->grade:'1';
                        $pass_fail =($grade_marks!=null)?$grade_marks->status:'';
                   
                        $score = $mark;
                   
                } elseif ($paper1->score_type == 'Points') {
                    $grade_marks = $Connection->table('grade_marks')->select('id', 'grade', 'status')->where([
                        ['grade_category', '=', $grade_category1],
                        ['grade', '=', $mark]
                    ])->first();
                    $points = ($grade_marks!=null)?$grade_marks->id:'';
                    $grade = ($grade_marks!=null)?$grade_marks->grade:'';
                    $pass_fail = ($grade_marks!=null)?$grade_marks->status:'';
                } elseif ($paper1->score_type == 'Freetext') {
                    $freetext = $mark;
                    $pass_fail = 'Pass';
                }
               
                $arrayStudentMarks1 = array(
                    'student_id' => $student_id,
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'subject_id' => $subject_id,
                    'exam_id' => $exam_id,
                    'paper_id' => $paper_id,
                    'semester_id' => $semester_id,
                    'session_id' => $session_id,
                    'grade_category' => $grade_category1,
                    'score' => $score,
                    'points' => $points,
                    'freetext' => $freetext,
                    'grade' => $grade,
                    'pass_fail' => $pass_fail,
                    'ranking' => $ranking,
                    'status' => $status,
                    'memo' => $memo,
                    'academic_session_id' => $academic_session_id,
                    'created_at' => date("Y-m-d H:i:s")
                );

                $row = $Connection->table('student_marks')->select('id')->where([
                    ['class_id', '=', $class_id],
                    ['section_id', '=', $section_id],
                    ['subject_id', '=', $subject_id],
                    ['student_id', '=', $student_id],
                    ['exam_id', '=', $exam_id],
                    ['semester_id', '=', $semester_id],
                    ['session_id', '=', $session_id],
                    ['paper_id', '=', $paper_id],
                    ['academic_session_id', '=', $academic_session_id]
                ])->first();
                if(isset($row->id)) {
                    $Connection->table('student_marks')->where('id', $row->id)->update([
                        'score' => $score,
                        'points' => $points,
                        'freetext' => $freetext,
                        'grade' => $grade,
                        'ranking' => $ranking,
                        'pass_fail' => $pass_fail,
                        'status' => $status,
                        'memo' => $memo,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                } else {
                    $Connection->table('student_marks')->insert($arrayStudentMarks1);
                }
                // Insert Mark End
            }
        }
    }
        
        $success[]='';   
        return $this->successResponse($success, 'Exam Mark Upload Successfully');
    }*/
    public function examuploadmark(Request $request)
    {
        // Extracting request parameters
        $Connection = $this->createNewConnection($request->branch_id);
        $department_id = $request->department_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $exam_id = $request->exam_id;
        $subject_id = $request->subject_id;
        $paper_id = $request->paper_id;
        $semester_id = $request->semester_id;
        $session_id = $request->session_id;
        $academic_session_id = $request->academic_session_id;
        $fdata = $request->fdata;

        $row = 0;

        // Processing each row of data
        foreach ($fdata as $importData) {
            // Incrementing row count
            $row++;

            // Checking if student roll number is provided
            if ($importData[1] != '') {
                $student_roll = $importData[1];
                $papername = $importData[3];
                $score_type = $importData[4];
                // Determining student status
                $att = strtolower($importData[6]);
                $status = ($att == "p" || $att == "present") ? "present" : "absent";
                // $mark = ($att != 'a' && !empty($importData[5])) ? $importData[5] : 0;
                $mark = ($att != 'a' && !empty($importData[5])) ? $importData[5] : null;
                $memo = $importData[7];

                // Retrieving student details
                $students = $Connection->table('students')->select('id')->where('register_no', '=', $student_roll)->first();
                if (!$students) {
                    continue; // If student not found, skip to next iteration
                }
                $student_id = $students->id;

                // Retrieving paper details
                $getpapersQuery = $Connection->table('exam_papers as ep')
                    ->select(
                        'ep.id',
                        'ep.paper_name',
                        'ep.score_type',
                        'ep.grade_category'
                    )
                    ->where([
                        ['ep.department_id', '=', $department_id],
                        ['ep.class_id', '=', $class_id],
                        ['ep.subject_id', '=', $subject_id],
                        ['ep.academic_session_id', '=', $academic_session_id]
                    ]);

                if ($paper_id != 'All') {
                    $getpapersQuery->where('ep.id', '=', $paper_id);
                } else {
                    $getpapersQuery->where('ep.paper_name', '=', $papername);
                    $getpapersQuery->where('ep.score_type', '=', $score_type);
                }

                $paper = $getpapersQuery->first();
                if (!$paper) {
                    continue; // If paper not found, skip to next iteration
                }
                $paperID = $paper->id;
                $grade_category = $paper->grade_category;
                $score_type = $paper->score_type;
                // return $score_type;
                // Initialize variables
                $score = null;
                $points = null;
                $freetext = null;
                $grade = null;
                $pass_fail = null;
                $ranking = null; // Initialize ranking variable

                // Processing based on score type
                if ($score_type == 'Grade' || $score_type == 'Mark') {
                    if (!empty($mark)) {
                        $grade_marks = $Connection->table('grade_marks')->select('grade', 'status')->where([
                            ['grade_category', '=', $grade_category],
                            ['min_mark', '<=', $mark],
                            ['max_mark', '>=', $mark]
                        ])->first();
                        $score = $mark;
                        $grade = ($grade_marks != null) ? $grade_marks->grade : '';
                        $pass_fail = ($grade_marks != null) ? $grade_marks->status : '';
                    }
                } elseif ($score_type == 'Points') {
                    if (!empty($mark)) {
                        $grade_marks = $Connection->table('grade_marks')->select('id', 'grade', 'status')->where([
                            ['grade_category', '=', $grade_category],
                            ['grade', '=', $mark]
                        ])->first();
                        $points = ($grade_marks != null) ? $grade_marks->id : '';
                        $grade = ($grade_marks != null) ? $grade_marks->grade : '';
                        $pass_fail = ($grade_marks != null) ? $grade_marks->status : '';
                    }
                } elseif ($score_type == 'Freetext') {
                    $freetext = $mark;
                    $pass_fail = 'Pass';
                }

                // Constructing student marks array
                $arrayStudentMarks = [
                    'student_id' => $student_id,
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'subject_id' => $subject_id,
                    'exam_id' => $exam_id,
                    'paper_id' => $paperID ?? 0,
                    'semester_id' => $semester_id,
                    'session_id' => $session_id ?? 0,
                    'grade_category' => $grade_category ?? null,
                    'score' => $score ?? null,
                    'points' => $points ?? null,
                    'freetext' => $freetext ?? null,
                    'grade' => $grade ?? null,
                    'pass_fail' => $pass_fail ?? null,
                    'ranking' => $ranking ?? null,
                    'status' => $status ?? null,
                    'memo' => $memo ?? null,
                    'academic_session_id' => $academic_session_id,
                    'created_at' => date("Y-m-d H:i:s")
                ];

                // Checking if student marks exist
                $existingRow = $Connection->table('student_marks')->select('id')->where([
                    ['class_id', '=', $class_id],
                    ['section_id', '=', $section_id],
                    ['subject_id', '=', $subject_id],
                    ['student_id', '=', $student_id],
                    ['exam_id', '=', $exam_id],
                    ['semester_id', '=', $semester_id],
                    ['paper_id', '=', $paperID],
                    ['academic_session_id', '=', $academic_session_id]
                ])->first();

                // Inserting or updating student marks
                if (isset($existingRow->id)) {
                    $Connection->table('student_marks')->where('id', $existingRow->id)->update([
                        'score' => $score ?? null,
                        'points' => $points ?? null,
                        'freetext' => $freetext ?? null,
                        'grade' => $grade ?? null,
                        'pass_fail' => $pass_fail,
                        'ranking' => $ranking ?? null,
                        'status' => $status ?? null,
                        'memo' => $memo ?? null,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                } else {
                    $Connection->table('student_marks')->insert($arrayStudentMarks);
                }
            }
        }
        return $this->successResponse([], 'Exam Mark Upload Successfully');
    }

    public function adhocexamuploadmark(Request $request)
    {
        // Extracting request parameters
        $Connection = $this->createNewConnection($request->branch_id);
        $department_id = $request->department_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $exam_id = $request->exam_id;
        $subject_id = $request->subject_id;
        $exam_date = $request->exam_date;
        $score_type = $request->score_type;
        $academic_session_id = $request->academic_session_id;
        $fdata = $request->fdata;

        $row = 0;

        // Processing each row of data
        foreach ($fdata as $importData) {
            // Incrementing row count
            $row++;

            // Checking if student roll number is provided
            if ($importData[1] != '') {
                $student_roll = $importData[1];

                // Determining student status

                $mark = $importData[3];
                $memo = '';

                // Retrieving student details
                $students = $Connection->table('students')->select('id')->where('register_no', '=', $student_roll)->first();
                $student_id = $students->id;

                // Retrieving paper details


                // Constructing student marks array
                $arrayStudentMarks = [
                    'student_id' => $student_id,
                    'department_id' => $department_id,
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'subject_id' => $subject_id,
                    'exam_id' => $exam_id,
                    'exam_date' => $exam_date,
                    'score_type' => $score_type,

                    'mark' => $mark ?? null,
                    'memo' => $memo ?? null,
                    'academic_session_id' => $academic_session_id,
                    'created_at' => date("Y-m-d H:i:s")
                ];

                // Checking if student marks exist
                $existingRow = $Connection->table('adhocexam_marks')->select('id')->where([
                    ['class_id', '=', $class_id],
                    ['section_id', '=', $section_id],
                    ['subject_id', '=', $subject_id],
                    ['student_id', '=', $student_id],
                    ['exam_id', '=', $exam_id],
                    ['exam_date', '=', $exam_date],
                    ['academic_session_id', '=', $academic_session_id]
                ])->first();

                // Inserting or updating student marks
                if (isset($existingRow->id)) {
                    $Connection->table('adhocexam_marks')->where('id', $existingRow->id)->update([
                        'mark' => $mark ?? null,
                        'memo' => $memo ?? null,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                } else {
                    $Connection->table('adhocexam_marks')->insert($arrayStudentMarks);
                }
            }
        }
        // Returning success response
        $success[] = '';
        return $this->successResponse($success, 'Exam Mark Upload Successfully');
    }

    public function getec_marks(Request $request)
    {
        try {

            $exam_id = $request->exam_id;
            $class_id = $request->class_id;
            $department_id = $request->department_id;
            $section_id = $request->section_id;
            //$subject_id = $request->subject_id;
            $semester_id = $request->semester_id;
            $session_id = $request->session_id;
            $academic_session_id = $request->academic_session_id;
            $student_id = $request->student_id;
            $paper_name = $request->paper_name;
            $Connection = $this->createNewConnection($request->branch_id);
            $getsubject = $Connection->table('subjects as sb')
                ->select(
                    'sb.id as subject_id',
                    'sb.name'
                )
                // ->where('sb.name', '=', 'EC')
                ->where('sb.name', '=', '英語コミュニケーション')
                ->first();
            $subject_id = $getsubject->subject_id;
            // return $getsubject;
            $getSubjectMarks = $Connection->table('exam_papers as ep')
                ->select(
                    'ep.id',
                    'ep.paper_name',
                    'ep.score_type',
                    'sa.score',
                    'sa.grade',
                    'sa.points',
                    'sa.freetext',
                    'sa.memo',
                    'gm.grade as grade_name',
                )
                ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester_id, $session_id, $academic_session_id, $student_id, $department_id) {
                    $q->on('sa.paper_id', '=', 'ep.id')
                        ->on('sa.exam_id', '=', DB::raw("'$exam_id'"))
                        ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                        ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                        ->on('sa.semester_id', '=', DB::raw("'$semester_id'"))
                        ->on('sa.session_id', '=', DB::raw("'$session_id'"))
                        ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                        ->on('sa.student_id', '=', DB::raw("'$student_id'"))
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->leftJoin('grade_marks as gm', 'gm.id', '=', 'sa.points')
                ->where([
                    ['ep.class_id', '=', $request->class_id],
                    ['ep.subject_id', '=', $subject_id],
                    ['ep.academic_session_id', '=', $academic_session_id],
                    ['ep.department_id', '=', $department_id],
                    ['ep.paper_name', 'like', $paper_name]
                ])
                ->first();


            return $this->successResponse($getSubjectMarks, 'Get EC Mark Detatils');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getec_marks');
        }
    }
    public function getec_teacher(Request $request)
    {
        try {
            $class_id = $request->class_id;
            $department_id = $request->department_id;
            $section_id = $request->section_id;
            //$subject_id = $request->subject_id;
            $semester_id = $request->semester_id;
            $session_id = $request->session_id;
            $academic_session_id = $request->academic_session_id;
            $student_id = $request->student_id;
            $paper_name = $request->paper_name;
            $Connection = $this->createNewConnection($request->branch_id);
            $getsubject = $Connection->table('subjects as sb')
                ->select(
                    'sb.id as subject_id',
                    'sb.name'
                )
                ->where('sb.name', '=', '英語コミュニケーション')
                ->orWhere('sb.name', '=', 'English Comminication')
                ->first();
            $subject_id = $getsubject->subject_id;
            $getSubjectMarks = $Connection->table('subject_assigns as sa')
                ->select(
                    'st.id',
                    'st.first_name',
                    'st.last_name',

                )
                ->leftJoin('staffs as st', 'st.id', '=', 'sa.teacher_id')
                ->where([
                    ['sa.class_id', '=', $request->class_id],
                    ['sa.subject_id', '=', $subject_id],
                    ['sa.section_id', '=', $section_id],
                    ['sa.academic_session_id', '=', $academic_session_id],
                    ['sa.department_id', '=', $department_id]
                ])
                ->first();


            return $this->successResponse($getSubjectMarks, 'Get EC Teacher Name Detatils');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getec_teacher');
        }
    }
    public function getsubjectpapermark(Request $request)
    {
        try {
            $Connection = $this->createNewConnection($request->branch_id);
            $getsubject = $Connection->table('subjects as sb')
                ->select(
                    'sb.id as subject_id',
                    'sb.name'
                )
                ->where('sb.name', 'like', $request->subject)
                ->first();
            $subject_id = $getsubject->subject_id;

            $paper_list = [];
            foreach ($request->papers as $paper) {
                $getpapers = $Connection->table('exam_papers as ep')
                    ->select(
                        'ep.id',
                        'ep.paper_name',
                        'ep.score_type',
                        'sb.name'
                    )
                    ->leftJoin('subjects as sb', 'sb.id', '=', 'ep.subject_id')
                    ->where([
                        ['ep.department_id', '=', $request->department_id],
                        ['ep.class_id', '=', $request->class_id],
                        ['ep.subject_id', '=', $subject_id],
                        ['ep.academic_session_id', '=', $request->academic_session_id],
                        ['ep.paper_name', 'like', $paper]
                    ])
                    ->first();
                $getsemester = $Connection->table('semester')->where('academic_session_id', $request->academic_session_id)->orderBy('start_date', 'asc')->get();
                $mark = ['', '', ''];
                if (!empty($getpapers)) {
                    $mark = [];
                    foreach ($getsemester as $sem) {
                        $semester = $sem->id;
                        $paper_id = $getpapers->id;

                        $getmark = $Connection->table('student_marks as sa')
                            ->select(
                                'sa.score',
                                'sa.grade',
                                'sa.points',
                                'sa.freetext',
                                'gm.grade as grade_name',
                            )
                            ->leftJoin('grade_marks as gm', 'gm.id', '=', 'sa.points')
                            ->where([
                                ['sa.class_id', '=', $request->class_id],
                                ['sa.section_id', '=', $request->section_id],
                                ['sa.student_id', '=', $request->student_id],
                                ['sa.subject_id', '=', $subject_id],
                                ['sa.paper_id', '=', $paper_id],
                                ['sa.semester_id', '=', $semester]
                            ])
                            ->first();
                        array_push($mark, $getmark);
                    }
                }
                $data = [
                    "papers" => $paper,
                    "marks" => $mark
                ];

                array_push($paper_list, $data);
            }

            return $this->successResponse($paper_list, 'Get Subject Paper Lists');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getsubjectpapermark');
        }
    }
    public function classteacher_principal(Request $request)
    {
        try {

            $class_id = $request->class_id;
            $section_id = $request->section_id;
            $academic_session_id = $request->academic_session_id;
            $Connection = $this->createNewConnection($request->branch_id);
            $principaldata = $Connection->table('staffs')
                ->select(DB::raw("CONCAT(last_name, ' ', first_name) as full_name"))
                ->where('designation_id', 1)
                ->first();

            $teacherdata = $Connection->table('teacher_allocations as t1')
                ->select(DB::raw("CONCAT(t2.last_name, ' ', t2.first_name) as full_name"))
                ->leftJoin('staffs as t2', 't1.teacher_id', '=', 't2.id')
                ->where('t1.class_id', $class_id)
                ->where('t1.section_id', $section_id)
                ->where('t1.academic_session_id', $academic_session_id)
                ->first();

            $principal = $principaldata->full_name ?? '';
            $teacher = $teacherdata->full_name ?? '';

            $data = [
                "class_id" => $class_id,
                "section_id" => $section_id,
                "academic_session_id" => $academic_session_id,
                "principal" => $principal,
                "teacher" => $teacher
            ];
            return $this->successResponse($data, 'Get Class Teacher & Principal Details Successfully.');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in classteacher_principal');
        }
    }
    public function stuexam_ppmarklist(Request $request)
    {
        try {
            $exam_id = $request->exam_id;
            $class_id = $request->class_id;
            $section_id = $request->section_id;
            $subject_id = $request->subject_id;
            $session_id = $request->session_id;
            $academic_session_id = $request->academic_session_id;
            $semester_id = $request->semester_id;
            $student_id = $request->student_id;
            $paper = $request->paper;

            $Connection = $this->createNewConnection($request->branch_id);

            $getsubject = $Connection->table('subjects as sb')
                ->select(
                    'sb.id as subject_id',
                    'sb.name'
                )
                ->where('sb.name', 'like', $request->subject)
                ->first();
            $subject_id = $getsubject->subject_id;
            $getSemesterMark = $Connection->table('exam_papers as ep')
                ->select(
                    'ep.id',
                    'sa.score',
                    'sa.grade',

                )
                ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester_id, $session_id, $academic_session_id, $student_id) {
                    $q->on('sa.paper_id', '=', 'ep.id')
                        ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                        ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                        ->on('sa.semester_id', '=', DB::raw("'$semester_id'"))
                        ->on('sa.session_id', '=', DB::raw("'$session_id'"))
                        ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                        ->on('sa.student_id', '=', DB::raw("'$student_id'"))
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->where([
                    ['ep.class_id', '=', $request->class_id],
                    ['ep.subject_id', '=', $subject_id],
                    ['ep.academic_session_id', '=', $academic_session_id],
                    ['ep.paper_name', 'like', $paper]
                ])
                ->first();

            return $this->successResponse($getSemesterMark, 'Get Personal Point Mark Details Successfully.');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in stuexam_ppmarklist');
        }
    }
    public function stuexam_ppavgmarklist(Request $request)
    {
        try {
            $exam_id = $request->exam_id;
            $class_id = $request->class_id;
            $section_id = $request->section_id;
            $subject_id = $request->subject_id;
            $session_id = $request->session_id;
            $academic_session_id = $request->academic_session_id;
            $semester_id = $request->semester_id;
            $student_id = $request->student_id;
            $paper = $request->paper;


            $Connection = $this->createNewConnection($request->branch_id);
            $getsubject = $Connection->table('subjects as sb')
                ->select(
                    'sb.id as subject_id',
                    'sb.name'
                )
                ->where('sb.name', 'like', $request->subject)
                ->first();
            $subject_id = $getsubject->subject_id;
            $getSemesterMark = $Connection->table('exam_papers as ep')
                ->select(
                    'ep.id',
                    'sa.score',
                    'sa.grade',
                    DB::raw('AVG(score) as avg'),
                )
                ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester_id, $session_id, $academic_session_id, $student_id) {
                    $q->on('sa.paper_id', '=', 'ep.id')
                        ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                        ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                        ->on('sa.semester_id', '=', DB::raw("'$semester_id'"))
                        ->on('sa.session_id', '=', DB::raw("'$session_id'"))
                        ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                        //->on('sa.student_id', '=', DB::raw("'$student_id'"))
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->leftJoin('grade_marks as gm', 'gm.id', '=', 'sa.points')
                ->where([
                    ['ep.class_id', '=', $request->class_id],
                    ['ep.subject_id', '=', $subject_id],
                    ['ep.academic_session_id', '=', $academic_session_id],
                    ['ep.paper_name', 'like', $paper]
                ])
                ->first();

            return $this->successResponse($getSemesterMark, 'Get Personal Point Avg Mark Details Successfully');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in stuexam_ppavgmarklist');
        }
    }
    public function getpaperoverallmarklist1(Request $request)
    {
        try {
            $exam_id = $request->exam_id;
            $class_id = $request->class_id;
            $section_id = $request->section_id;
            $subject_id = $request->subject_id;
            $session_id = $request->session_id;
            $academic_session_id = $request->academic_session_id;
            $semester_id = $request->semester_id;
            $student_id = $request->student_id;
            $paper = $request->paper;
            $Connection = $this->createNewConnection($request->branch_id);
            $getsubject = $Connection->table('subjects as sb')
                ->select(
                    'sb.id as subject_id',
                    'sb.name'
                )
                ->where('sb.name', 'like', $request->subject)
                ->first();
            $subject_id = $getsubject->subject_id;
            $getsemester = $Connection->table('semester')->where('academic_session_id', $request->academic_session_id)->orderBy('start_date', 'asc')->get();
            $mark = [];
            $nsem = count($getsemester);
            $s = 0;
            $getmark = [];
            foreach ($getsemester as $sem) {
                $s++;

                if ($nsem == $s) {
                    $semester = $sem->id;


                    $getmark = $Connection->table('exam_papers as ep')
                        ->select(
                            'sa.score',
                            'sa.grade',
                            'sa.points',
                            'sa.freetext',
                            'gm.grade as grade_name',
                            'ep.score_type',
                        )
                        ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester, $session_id, $academic_session_id, $student_id) {
                            $q->on('sa.paper_id', '=', 'ep.id')
                                ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                                ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                                ->on('sa.semester_id', '=', DB::raw("'$semester'"))
                                ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                                ->on('sa.student_id', '=', DB::raw("'$student_id'"))
                                ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                        })
                        ->leftJoin('grade_marks as gm', 'gm.id', '=', 'sa.points')
                        ->where([
                            ['ep.class_id', '=', $class_id],
                            ['ep.subject_id', '=', $subject_id],
                            ['ep.academic_session_id', '=', $academic_session_id],
                            ['ep.paper_name', 'like', $paper]
                        ])
                        ->first();
                }
            }


            return $this->successResponse($getmark, 'Get  Paper Marks Lists');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getpaperoverallmarklist1');
        }
    }
    public function getacyeardates(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $branchID = $request->branch_id;
                $Connection = $this->createNewConnection($request->branch_id);
                $getsemester = $Connection->table('semester')->where('academic_session_id', $request->academic_session_id)->orderBy('start_date', 'asc')->get();

                $yearData = $Connection->table('semester as sm')
                    ->select(DB::raw('MIN(sm.start_date) AS year_start_date, MAX(sm.end_date) AS year_end_date'))
                    ->where([
                        ['sm.academic_session_id', '=', $request->academic_session_id],
                    ])
                    ->get();
                $data = [
                    "semesters" => $getsemester,
                    "acydates" => $yearData
                ];

                return $this->successResponse($data, 'Get Pdf Academic year Report successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getacyeardates');
        }
    }
    public function getsem_studentattendance(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $branchID = $request->branch_id;
                $Connection = $this->createNewConnection($request->branch_id);
                $getsemester = $Connection->table('semester')->where('academic_session_id', $request->academic_session_id)->orderBy('start_date', 'asc')->get();
                
                $attendance_list = [];
                foreach ($getsemester as $sem) {
                    
                    $semester_id = $sem->id;
                    
                    $fromdate = $sem->start_date;
                    
                    $enddate = $sem->end_date;
                    $froms = date('Y-m-01', strtotime($fromdate));
                    $start = new DateTime($froms);
                    $end = new DateTime($enddate);
                    $startmonth = date('m', strtotime($fromdate));
                  
                    $endmonth = date('m', strtotime($enddate));

                    $interval = new DateInterval('P1M'); // 1 month interval
                    $period = new DatePeriod($start, $interval, $end);


                    foreach ($period as $date) {


                        $month = trim($date->format('F') . PHP_EOL);
                        $montotaldays = trim($date->format('t') . PHP_EOL);
                        $mon = trim($date->format('m') . PHP_EOL);

                        $year = trim($date->format('Y') . PHP_EOL);
                        /*if($year==2024)
                    {
                        dd($mon,$year);
                    }*/
                        if (intval($mon) == intval($startmonth)) {
                            $fromdate1 = $fromdate;
                            $todate = trim($date->format('Y-m-t') . PHP_EOL);
                        } elseif (intval($mon) == intval($endmonth)) {
                            $todate = $enddate;
                            $fromdate1 = trim($date->format('Y-m-01') . PHP_EOL);
                        } else {
                            $fromdate1 = trim($date->format('Y-m-01') . PHP_EOL);
                            $todate = trim($date->format('Y-m-t') . PHP_EOL);
                        }



                        $suspension = 0;
                        $holidaydatas = $Connection->table('events as hl')
                        ->select('start_date','end_date')
                        ->where('hl.holiday', '=', '1')                        
                        ->where(function ($query) use ($fromdate1,$todate) {
                            $query->whereBetween('hl.start_date', [$fromdate1,  $todate])
                                ->orWhereBetween('hl.end_date', [$fromdate1,  $todate])
                                ->orWhere(function ($query)  use ($fromdate1,$todate) {
                                    $query->where('hl.start_date', '<', $fromdate1)
                                        ->where('hl.end_date', '>',  $todate);
                                });
                        })->get();
                        $holidaydatas;
                        $holidays=0;$holidays_array=[];
                        if(!empty($holidaydatas))
                        {
                            foreach($holidaydatas as $holy)
                            {
                                
                                $start_date = strtotime($holy->start_date);
                                $end_date = strtotime($holy->end_date);
                                $current_date = $start_date;
                                // Loop through each day
                                while ($current_date <= $end_date) {
                                    $hdate=date('Y-m-d',$current_date);
                                    
                                    // Check if the current date is in May
                                    if (date("m", $current_date) == $mon) {
                                        $holidays++;
                                        array_push($holidays_array, $hdate);
                                    }
                                    // Move to the next day
                                    $current_date = strtotime("+1 day", $current_date);
                                }
                            }
                        }
                        $start = strtotime($fromdate1);
                        $end = strtotime($todate);
                        $datediff = $end - $start;
                        $montotaldays = round($datediff / (60 * 60 * 24))+1;
                        $iter = 24 * 60 * 60; // whole day in seconds
                        $count = 0; // keep a count of Sats & Suns

                        for ($i = $start; $i <= $end; $i = $i + $iter) {
                            if (Date('D', $i) == 'Sat' || Date('D', $i) == 'Sun') {
                                $count++;
                            }
                        }

                        $totalweekends = $count;

                        $totaldays = $montotaldays - $holidays - $totalweekends;
                        $getleaves = $Connection->table('student_leaves')
                            ->where('student_id', $request->student_id)
                            ->where('class_id', $request->class_id)
                            ->where('section_id', $request->section_id)
                            ->where('status','=',"Approve")
                            ->where(function ($query) use ($fromdate1,$todate) {
                                $query->whereBetween('from_leave', [$fromdate1,  $todate])
                                    ->orWhereBetween('to_leave', [$fromdate1,  $todate])
                                    ->orWhere(function ($query)  use ($fromdate1,$todate) {
                                        $query->where('from_leave', '<', $fromdate1)
                                            ->where('to_leave', '>',  $todate);
                                    });
                            })->get();
                            $absent=0;$sus=0;$late1=0;$early=0;
                        foreach($getleaves as $leave)
                        {
                            if($leave->change_lev_type==1 || $leave->change_lev_type==1)
                            {
                                $weekday=array('Saturday','Sunday');
                                $start_date = strtotime($leave->from_leave);
                                $end_date = strtotime($leave->to_leave);
                                $current_date = $start_date;
                                // Loop through each day
                                while ($current_date <= $end_date) {
                                    $hdate=date('Y-m-d',$current_date);
                                    $curday=date('l',strtotime($hdate));
                                    // Check if the current date is in May
                                    if (date("m", $current_date) == $mon) {
                                        if(in_array($curday, $weekday))
                                        {
    
                                        }
                                        elseif(in_array($curday, $holidays_array))
                                        {
    
                                        }
                                        else
                                        {
                                            $absent++;
                                        }
                                    }
                                    // Move to the next day
                                    $current_date = strtotime("+1 day", $current_date);
                                }
                            }
                            if($leave->change_lev_type==3 || $leave->change_lev_type==4)
                            {
                                $weekday=array('Saturday','Sunday');
                                $start_date = strtotime($leave->from_leave);
                                $end_date = strtotime($leave->to_leave);
                                $current_date = $start_date;
                                // Loop through each day
                                while ($current_date <= $end_date) {
                                    $hdate=date('Y-m-d',$current_date);
                                    $curday=date('l',strtotime($hdate));
                                    // Check if the current date is in May
                                    if (date("m", $current_date) == $mon) {
                                        if(in_array($curday, $weekday))
                                        {
    
                                        }
                                        elseif(in_array($curday, $holidays_array))
                                        {
    
                                        }
                                        else
                                        {
                                            $sus++;
                                        }
                                    }
                                    // Move to the next day
                                    $current_date = strtotime("+1 day", $current_date);
                                }
                            }
                            if($leave->change_lev_type==5)
                            {
                                $weekday=array('Saturday','Sunday');
                                $start_date = strtotime($leave->from_leave);
                                $end_date = strtotime($leave->to_leave);
                                $current_date = $start_date;
                                // Loop through each day
                                while ($current_date <= $end_date) {
                                    $hdate=date('Y-m-d',$current_date);
                                    $curday=date('l',strtotime($hdate));
                                    // Check if the current date is in May
                                    if (date("m", $current_date) == $mon) {
                                        if(in_array($curday, $weekday))
                                        {
    
                                        }
                                        elseif(in_array($curday, $holidays_array))
                                        {
    
                                        }
                                        else
                                        {
                                            $late1++;
                                        }
                                    }
                                    // Move to the next day
                                    $current_date = strtotime("+1 day", $current_date);
                                }
                            }
                            if($leave->change_lev_type==6)
                            {
                                $weekday=array('Saturday','Sunday');
                                $start_date = strtotime($leave->from_leave);
                                $end_date = strtotime($leave->to_leave);
                                $current_date = $start_date;
                                // Loop through each day
                                while ($current_date <= $end_date) {
                                    $hdate=date('Y-m-d',$current_date);
                                    $curday=date('l',strtotime($hdate));
                                    // Check if the current date is in May
                                    if (date("m", $current_date) == $mon) {
                                        if(in_array($curday, $weekday))
                                        {
    
                                        }
                                        elseif(in_array($curday, $holidays_array))
                                        {
    
                                        }
                                        else
                                        {
                                            $early++;
                                        }
                                    }
                                    // Move to the next day
                                    $current_date = strtotime("+1 day", $current_date);
                                }
                            }
                            


                        }
                        $totalcoming = $totaldays - $sus; 
                        $suspension=$sus;
                        $totpres = $totalcoming - $absent;

                        $totabs =  $absent;

                        $totlate = $late1;

                        $totexc = $early;
                        $data = [
                            "month" => $mon,
                            "no_schooldays" => $totaldays,
                            "suspension" => $suspension,
                            "totalcoming" => $totalcoming,
                            "totpres" => $totpres,
                            "totabs" => $totabs,
                            "totlate" => $totlate,
                            "totexc" => $totexc,
                            "holidays"=> $holidays,
                            "holidays_array"=>$holidays_array
                           // "datas" => $getleaves
                        ];
                        array_push($attendance_list, $data);
                    }
                }


                return $this->successResponse($attendance_list, 'Get Pdf Attendance Report successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getsem_studentattendance');
        }
    }

    public function exam_studentslist(Request $request)
    {
        try {
            $Connection = $this->createNewConnection($request->branch_id);

            $studentdetails = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.roll',
                    'en.attendance_no',
                    DB::raw('CONCAT(st.last_name_english, " ", st.first_name_english) as eng_name'),
                    DB::raw('CONCAT(st.last_name, " ", st.first_name) as name'),
                    'st.register_no',
                )
                ->join('students as st', 'st.id', '=', 'en.student_id')
                ->where([
                    ['en.department_id', '=', $request->department_id],
                    ['en.class_id', '=', $request->class_id],
                    ['en.section_id', '=', $request->section_id],
                    ['en.academic_session_id', '=', $request->academic_session_id],
                ])
                ->get();


            return $this->successResponse($studentdetails, 'Student Lists');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in exam_studentslist');
        }
    }
    public function exam_individualstudentslist(Request $request)
    {
        try {
            $Connection = $this->createNewConnection($request->branch_id);

            $studentdetails = $Connection->table('enrolls as en')
                ->select(
                    'en.student_id',
                    'en.roll',
                    'en.attendance_no',
                    DB::raw('CONCAT(st.last_name_english, " ", st.first_name_english) as eng_name'),
                    DB::raw('CONCAT(st.last_name, " ", st.first_name) as name'),
                    'st.register_no',
                )
                ->join('students as st', 'st.id', '=', 'en.student_id')
                ->where([
                    ['en.department_id', '=', $request->department_id],
                    ['en.class_id', '=', $request->class_id],
                    ['en.section_id', '=', $request->section_id],
                    ['en.academic_session_id', '=', $request->academic_session_id],
                    ['en.student_id', '=', $request->student_id],
                ])
                ->first();


            return $this->successResponse($studentdetails, 'Student Lists');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in exam_studentslist');
        }
    }
    public function get_subjectlist(Request $request)
    {
        try {
            $Connection = $this->createNewConnection($request->branch_id);

            $subjectdetails = $Connection->table('subject_assigns as sa')
                ->select(
                    'sa.subject_id',
                    'sb.name',
                    'sa.teacher_id',
                    'st.first_name as teacher'
                )
                ->leftJoin('subjects as sb', 'sb.id', '=', 'sa.subject_id')
                ->leftJoin('staffs as st', 'st.id', '=', 'sa.teacher_id')
                ->where([
                    ['sa.department_id', '=', $request->department_id],
                    ['sa.class_id', '=', $request->class_id],
                    ['sa.section_id', '=', $request->section_id],
                    ['sa.academic_session_id', '=', $request->academic_session_id],
                    ['sb.pdf_report', '=', $request->pdf_report],
                ])
                ->orderBy('order_code', 'asc')
                ->get();

            return $this->successResponse($subjectdetails, 'Get Subject Lists');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in get_subjectlist');
        }
    }
    public function get_mainsubjectlist(Request $request)
    {
        try {
            $Connection = $this->createNewConnection($request->branch_id);
            if ($request->mandatory == '1') {
                $subjectdetails = $Connection->table('subject_assigns as sa')
                    ->select(
                        'sa.subject_id',
                        'sb.name',
                        'sa.teacher_id',
                        'st.first_name as teacher'
                    )
                    ->leftJoin('subjects as sb', 'sb.id', '=', 'sa.subject_id')
                    ->leftJoin('staffs as st', 'st.id', '=', 'sa.teacher_id')
                    ->where([
                        ['sa.department_id', '=', $request->department_id],
                        ['sa.class_id', '=', $request->class_id],
                        ['sa.section_id', '=', $request->section_id],
                        ['sa.academic_session_id', '=', $request->academic_session_id],
                        ['sb.pdf_report', '=', $request->pdf_report],
                        ['sb.subject_type', '=', 'Mandatory'],
                    ])
                    ->orderBy('order_code', 'asc')
                    ->get();
            } else {
                $subjectdetails = $Connection->table('subject_assigns as sa')
                    ->select(
                        'sa.subject_id',
                        'sb.name',
                        'sa.teacher_id',
                        'st.first_name as teacher'
                    )
                    ->leftJoin('subjects as sb', 'sb.id', '=', 'sa.subject_id')
                    ->leftJoin('staffs as st', 'st.id', '=', 'sa.teacher_id')
                    ->where([
                        ['sa.department_id', '=', $request->department_id],
                        ['sa.class_id', '=', $request->class_id],
                        ['sa.section_id', '=', $request->section_id],
                        ['sa.academic_session_id', '=', $request->academic_session_id],
                        ['sb.pdf_report', '=', $request->pdf_report],
                        ['sb.subject_type', '!=', 'Mandatory'],
                    ])
                    ->orderBy('order_code', 'asc')
                    ->get();
            }
            return $this->successResponse($subjectdetails, 'Get Subject Lists');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in get_mainsubjectlist');
        }
    }
    public function exam_papermarks(Request $request)
    {
        try {

            $exam_id = $request->exam_id;
            $class_id = $request->class_id;
            $department_id = $request->department_id;
            $section_id = $request->section_id;
            $subject_id = $request->subject_id;
            $semester_id = $request->semester_id;
            $session_id = $request->session_id;
            $academic_session_id = $request->academic_session_id;
            $student_id = $request->student_id;
            $pdf_report = $request->pdf_report;

            $Connection = $this->createNewConnection($request->branch_id);
            $getSubjectMarks = $Connection->table('exam_papers as ep')
                ->select(
                    'ep.id',
                    'ep.paper_name',
                    'ep.score_type',
                    'sa.score',
                    'sa.grade',
                    'sa.points',
                    'sa.freetext',
                    'sa.memo',
                    'gm.grade as grade_name',
                )
                ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester_id, $session_id, $academic_session_id, $student_id, $department_id) {
                    $q->on('sa.paper_id', '=', 'ep.id')
                        ->on('sa.exam_id', '=', DB::raw("'$exam_id'"))
                        ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                        ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                        ->on('sa.semester_id', '=', DB::raw("'$semester_id'"))
                        ->on('sa.session_id', '=', DB::raw("'$session_id'"))
                        ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                        ->on('sa.student_id', '=', DB::raw("'$student_id'"))
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->leftJoin('grade_marks as gm', 'gm.id', '=', 'sa.points')
                ->where([
                    ['ep.class_id', '=', $request->class_id],
                    ['ep.subject_id', '=', $subject_id],
                    ['ep.academic_session_id', '=', $academic_session_id],
                    ['ep.department_id', '=', $department_id],
                    ['ep.pdf_report', '=', $pdf_report]
                ])
                ->get();
            return $this->successResponse($getSubjectMarks, 'Get EC Mark Detatils');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in exam_papermarks');
        }
    }

    public function getsubjecpapertlist(Request $request)
    {
        try {
            $Connection = $this->createNewConnection($request->branch_id);

            $getpapers = $Connection->table('exam_papers as ep')
                ->select(
                    'ep.id',
                    'ep.paper_name',
                    'ep.score_type',
                    'sb.name'
                )
                ->leftJoin('subjects as sb', 'sb.id', '=', 'ep.subject_id')
                ->where([
                    ['ep.department_id', '=', $request->department_id],
                    ['ep.class_id', '=', $request->class_id],
                    ['ep.subject_id', '=', $request->subject_id],
                    ['ep.academic_session_id', '=', $request->academic_session_id]
                ])
                ->get();
            $paper_list = [];
            foreach ($getpapers as $paper) {
                $getsemester = $Connection->table('semester')->where('academic_session_id', $request->academic_session_id)->orderBy('start_date', 'asc')->get();
                $mark = [];
                foreach ($getsemester as $sem) {
                    $semester = $sem->id;
                    $paper_id = $paper->id;
                    $getmark = $Connection->table('student_marks as sa')
                        ->select(
                            'sa.score',
                            'sa.grade',
                            'sa.points',
                            'sa.freetext',
                            'gm.grade as grade_name',
                        )
                        ->leftJoin('grade_marks as gm', 'gm.id', '=', 'sa.points')
                        ->where([
                            ['sa.class_id', '=', $request->class_id],
                            ['sa.section_id', '=', $request->section_id],
                            ['sa.student_id', '=', $request->student_id],
                            ['sa.subject_id', '=', $request->subject_id],
                            ['sa.paper_id', '=', $paper_id],
                            ['sa.semester_id', '=', $semester]
                        ])
                        ->first();
                    array_push($mark, $getmark);
                }
                $data = [
                    "papers" => $paper,
                    "marks" => $mark
                ];

                array_push($paper_list, $data);
            }

            return $this->successResponse($paper_list, 'Get Subject Paper Lists');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getsubjecpapertlist');
        }
    }

    public function stuexam_marklist(Request $request)
    {
        try {
            $exam_id = $request->exam_id;
            $class_id = $request->class_id;
            $section_id = $request->section_id;
            $subject_id = $request->subject_id;
            $session_id = $request->session_id;
            $academic_session_id = $request->academic_session_id;
            $semester_id = $request->semester_id;
            $student_id = $request->student_id;
            $subject = $request->subject;
            $pdf_report = $request->pdf_report;


            $Connection = $this->createNewConnection($request->branch_id);


            $getSemesterMark = $Connection->table('exam_papers as ep')
                ->select(
                    'ep.id',
                    'sa.score',
                    'sa.grade',
                    'gm.grade as grade_name',
                )
                ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester_id, $session_id, $academic_session_id, $student_id) {
                    $q->on('sa.paper_id', '=', 'ep.id')
                        ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                        ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                        ->on('sa.semester_id', '=', DB::raw("'$semester_id'"))
                        ->on('sa.session_id', '=', DB::raw("'$session_id'"))
                        ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                        ->on('sa.student_id', '=', DB::raw("'$student_id'"))
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->leftJoin('grade_marks as gm', 'gm.id', '=', 'sa.points')
                ->where([
                    ['ep.class_id', '=', $request->class_id],
                    ['ep.subject_id', '=', $subject_id],
                    ['ep.academic_session_id', '=', $academic_session_id],
                    ['ep.pdf_report', '=', $pdf_report]
                ])
                ->first();

            return $this->successResponse($getSemesterMark, 'Get Mark Details');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in stuexam_marklist');
        }
    }
    public function stuexam_avgmarklist(Request $request)
    {
        try {
            $exam_id = $request->exam_id;
            $class_id = $request->class_id;
            $section_id = $request->section_id;
            $subject_id = $request->subject_id;
            $session_id = $request->session_id;
            $academic_session_id = $request->academic_session_id;
            $semester_id = $request->semester_id;
            $student_id = $request->student_id;
            $subject = $request->subject;
            $pdf_report = $request->pdf_report;


            $Connection = $this->createNewConnection($request->branch_id);


            $getSemesterMark = $Connection->table('exam_papers as ep')
                ->select(
                    'ep.id',
                    'sa.score',
                    'sa.grade',
                    DB::raw('AVG(score) as avg'),
                )
                ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester_id, $session_id, $academic_session_id, $student_id) {
                    $q->on('sa.paper_id', '=', 'ep.id')
                        ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                        ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                        ->on('sa.semester_id', '=', DB::raw("'$semester_id'"))
                        ->on('sa.session_id', '=', DB::raw("'$session_id'"))
                        ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                        //->on('sa.student_id', '=', DB::raw("'$student_id'"))
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })
                ->leftJoin('grade_marks as gm', 'gm.id', '=', 'sa.points')
                ->where([
                    ['ep.class_id', '=', $request->class_id],
                    ['ep.subject_id', '=', $subject_id],
                    ['ep.academic_session_id', '=', $academic_session_id],
                    ['ep.pdf_report', '=', $pdf_report]
                ])
                ->first();

            return $this->successResponse($getSemesterMark, 'Get Mark Details');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in stuexam_avgmarklist');
        }
    }
    public function get_overallsubjectlist(Request $request)
    {
        try {
            $Connection = $this->createNewConnection($request->branch_id);

            $subjectdetails = $Connection->table('subject_assigns as sa')
                ->select(
                    'sa.subject_id',
                    'sb.name',
                    'sa.teacher_id',
                    'st.first_name as teacher'
                )
                ->leftJoin('subjects as sb', 'sb.id', '=', 'sa.subject_id')
                ->leftJoin('staffs as st', 'st.id', '=', 'sa.teacher_id')
                ->where([
                    ['sa.department_id', '=', $request->department_id],
                    ['sb.pdf_report', '=', $request->pdf_report],
                ])
                ->orderBy('order_code', 'asc')
                ->get();

            return $this->successResponse($subjectdetails, 'Get Subject Lists');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in get_overallsubjectlist');
        }
    }
    public function get_overallpaperlist(Request $request)
    {
        try {
            $Connection = $this->createNewConnection($request->branch_id);

            $getpapers = $Connection->table('exam_papers as ep')
                ->select(
                    'ep.id',
                    'ep.paper_name',
                    'ep.score_type',
                    'sb.name'
                )
                ->leftJoin('subjects as sb', 'sb.id', '=', 'ep.subject_id')
                ->where([
                    ['ep.department_id', '=', $request->department_id],
                    ['ep.subject_id', '=', $request->subject_id],
                    ['ep.pdf_report', '=', $request->pdf_report]
                ])
                ->get();

            return $this->successResponse($getpapers, 'Get Subject Lists');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in get_overallpaperlist');
        }
    }
    public function getpaperoverallmarklist(Request $request)
    {
        try {
            $Connection = $this->createNewConnection($request->branch_id);

            $getsemester = $Connection->table('semester')->where('academic_session_id', $request->academic_session_id)->orderBy('start_date', 'asc')->get();
            $mark = [];
            $nsem = count($getsemester);
            $s = 0;
            $getmark = [];
            foreach ($getsemester as $sem) {
                $s++;

                if ($nsem == $s) {
                    $semester = $sem->id;

                    $getmark = $Connection->table('student_marks as sa')
                        ->select(
                            'sa.score',
                            'sa.grade',
                            'sa.points',
                            'sa.freetext',
                            'gm.grade as grade_name',
                        )
                        ->leftJoin('grade_marks as gm', 'gm.id', '=', 'sa.points')
                        ->where([
                            ['sa.class_id', '=', $request->class_id],
                            ['sa.section_id', '=', $request->section_id],
                            ['sa.student_id', '=', $request->student_id],
                            ['sa.subject_id', '=', $request->subject_id],
                            ['sa.paper_id', '=', $request->paper_id],
                            ['sa.semester_id', '=', $semester]
                        ])
                        ->first();
                }
            }


            return $this->successResponse($getmark, 'Get  Paper Marks Lists');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getpaperoverallmarklist');
        }
    }
    public function studentclasssection(Request $request)
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
                $studentId = $request->id;
                $class = $Connection->table('classes as cl')
                    ->select('cl.id', 'cl.name', 'cl.short_name', 'cl.name_numeric')
                    ->where([
                        ['cl.department_id', '=', $request->department_id],
                    ])
                    ->get();

                $result = array();
                foreach ($class as $cls) {

                    $classsec = $Connection->table('enrolls as t1')
                        ->select('t1.class_id', 't1.section_id', 't1.academic_session_id', 't2.name_numeric', 't3.name as section', 't4.name as academic_year')
                        ->leftJoin('classes as t2', 't1.class_id', '=', 't2.id')
                        ->leftJoin('sections as t3', 't1.section_id', '=', 't3.id')
                        ->leftJoin('academic_year as t4', 't1.academic_session_id', '=', 't4.id')
                        ->distinct()
                        ->where('t1.student_id', $studentId)
                        ->where('t2.id', $cls->id)
                        ->first();

                    $class_id = (isset($classsec->class_id) && $classsec->class_id != null) ? $classsec->class_id : '';
                    $section_id = (isset($classsec->section_id) && $classsec->section_id != null) ? $classsec->section_id : '';
                    $academic_session_id = (isset($classsec->academic_session_id) && $classsec->academic_session_id != null) ? $classsec->academic_session_id : '';
                    $academic_year = (isset($classsec->academic_year) && $classsec->academic_year != null) ? $classsec->academic_year : '';
                    $studentPlace = '';
                    if (isset($classsec->class_id) && $classsec->class_id != null) {
                        $results = $Connection->table('enrolls as t1')
                            ->select('student_id', 'id', DB::raw('ROW_NUMBER() OVER (ORDER BY id) as student_place'))
                            ->where('class_id', $class_id)
                            ->where('section_id', $section_id)
                            ->where('academic_session_id', $academic_session_id)
                            ->get();

                        foreach ($results as $res) {

                            if ($studentId == $res->student_id) {
                                $studentPlace = $res->student_place;
                            }
                        }
                    }
                    $principal = '';
                    $teacher = '';
                    if ($academic_year != '') {
                        $principaldata = $Connection->table('staffs')
                            ->select('first_name', 'last_name')
                            ->where('designation_id', '1')
                            ->first();
                        $teacherdata = $Connection->table('teacher_allocations as t1')
                            ->select('t1.teacher_id', 't2.first_name', 't2.last_name')
                            ->leftJoin('staffs as t2', 't1.teacher_id', '=', 't2.id')
                            ->where('t1.class_id', $class_id)
                            ->where('t1.section_id', $section_id)
                            ->where('t1.academic_session_id', $academic_session_id)
                            ->first();
                        $pfirst_name = (isset($principaldata->first_name) && $principaldata->first_name != null) ? $principaldata->first_name : '';
                        $plast_name = (isset($principaldata->last_name) && $principaldata->last_name != null) ? $principaldata->last_name : '';
                        $tfirst_name = (isset($teacherdata->first_name) && $teacherdata->first_name != null) ? $teacherdata->first_name : '';
                        $tlast_name = (isset($teacherdata->last_name) && $teacherdata->last_name != null) ? $teacherdata->last_name : '';

                        $principal = $plast_name . ' ' . $pfirst_name;
                        $teacher = $tlast_name . ' ' . $tfirst_name;
                    }
                    $datas = [
                        "class" => $cls->name,
                        "class_numeric" => $cls->name_numeric,
                        "class_id" => $class_id,
                        "section" => (isset($classsec->section) && $classsec->section != null) ? $classsec->section : '',
                        "section_id" => $section_id,
                        "academic_session_id" => $academic_session_id,
                        "academic_year" => (isset($classsec->academic_year) && $classsec->academic_year != null) ? $classsec->academic_year : '',
                        "studentPlace" => $studentPlace,
                        "principal" => $principal,
                        "teacher" => $teacher
                    ];

                    array_push($result, $datas);
                }
                return $this->successResponse($result, 'Get class Section Fetched successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in studentclasssection');
        }
    }
    public function getpdf_report(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $branchID = $request->branch_id;
                $Connection = $this->createNewConnection($request->branch_id);

                $pdflist = $Connection->table('pdf_report')->select('id', 'pdf_name')->get();


                return $this->successResponse($pdflist, 'Get Pdf Report successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getpdf_report');
        }
    }
}
