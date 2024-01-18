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


class ExamreportController extends BaseController
{
    
    public function getacyeardates(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $branchID=$request->branch_id;
            $Connection = $this->createNewConnection($request->branch_id);
            $getsemester = $Connection->table('semester')->where('academic_session_id',$request->academic_session_id)->orderBy('start_date', 'asc')->get();
            
            $yearData = $Connection->table('semester as sm')
                    ->select(DB::raw('MIN(sm.start_date) AS year_start_date, MAX(sm.end_date) AS year_end_date'))
                    ->where([
                        ['sm.academic_session_id', '=', $request->academic_session_id],
                    ])
                    ->get();
            $data=[
                "semesters"=>$getsemester,
                "acydates"=>$yearData
            ];
            
            return $this->successResponse($data, 'Get Pdf Academic year Report successfully');
        }
    }
    public function getsem_studentattendance(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $branchID=$request->branch_id;
            $Connection = $this->createNewConnection($request->branch_id);
            $getsemester = $Connection->table('semester')->where('academic_session_id',$request->academic_session_id)->orderBy('start_date', 'asc')->get();
            $attendance_list=[];
            foreach($getsemester as $sem)
            {
               
                $semester_id=$sem->id;
                $fromdate=$sem->start_date;
                $enddate=$sem->end_date;
                $froms=date('Y-m-01',strtotime($fromdate));
                $start = new DateTime($froms);
                $end = new DateTime($enddate);
                $startmonth=date('m',strtotime($fromdate));
              
                $endmonth=date('m',strtotime($enddate));
               
                $interval = new DateInterval('P1M'); // 1 month interval
                $period = new DatePeriod($start, $interval, $end);
              

                foreach ($period as $date) {

                
                    $month= trim($date->format('F').PHP_EOL);
                    $montotaldays= trim($date->format('t').PHP_EOL);
                    $mon= trim($date->format('m').PHP_EOL);
                    
                    $year= trim($date->format('m').PHP_EOL);
                    
                    if(intval($mon)==intval($startmonth))
                    {
                        $fromdate1= $fromdate;
                        $todate=trim($date->format('Y-m-t').PHP_EOL);
                    }
                    elseif(intval($mon)==intval($endmonth))
                    {
                        $todate= $enddate;
                        $fromdate1=trim($date->format('Y-m-01').PHP_EOL);
                    }
                    else
                    {
                        $fromdate1=trim($date->format('Y-m-01').PHP_EOL);
                        $todate=trim($date->format('Y-m-t').PHP_EOL);
                    }
                    
                   

                    $suspension=0;
                    $holidays = $Connection->table('holidays as hl')
                    ->select('hl.id')
                    ->whereRaw('hl.date between "' . $fromdate1 . '" and "' . $todate . '"')
                    ->count();
                    $start=strtotime( $fromdate1);
                    $end=strtotime($todate);
                    $datediff = $end - $start;
                    $montotaldays =round($datediff / (60 * 60 * 24));
                    $iter = 24*60*60; // whole day in seconds
                    $count = 0; // keep a count of Sats & Suns
            
                    for($i = $start; $i <= $end; $i=$i+$iter)
                    {
                        if(Date('D',$i) == 'Sat' || Date('D',$i) == 'Sun')
                        {
                            $count++;
                        }
                    }
                    
                    $totalweekends= $count;
                    
                    $totaldays=$montotaldays-$holidays-$totalweekends;
                    $getAttendance = $Connection->table('student_attendances_day as sa')
                    ->select(
                        DB::raw('COUNT(CASE WHEN sa.status = "present" then 1 ELSE NULL END) as "presentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "late" then 1 ELSE NULL END) as "lateCount"'),
                        DB::raw('COUNT(CASE WHEN sa.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
                    )
                    ->where('sa.student_id', '=', $request->student_id)
                    ->where('sa.class_id', '=', $request->class_id)
                    ->where('sa.section_id', '=', $request->section_id)
                    ->where('sa.semester_id', '=', $semester_id)
                    ->whereMonth('sa.date', $month)
                    ->whereYear('sa.date', $year)
                    ->first();
                    $totalcoming= $totaldays-$suspension;
                                $totpres=$getAttendance->presentCount;
                                
                                $totabs=$getAttendance->absentCount;
                                
                                $totlate=$getAttendance->lateCount;
                                
                                $totexc=$getAttendance->excusedCount;
                    $data=[
                        "month"=>$month,
                        "no_schooldays"=>$totaldays,
                        "suspension"=>$suspension,
                        "totalcoming"=>$totalcoming,
                        "totpres"=>$totpres,
                        "totabs"=>$totabs,
                        "totlate"=>$totlate,
                        "totexc"=>$totexc,
                    ];
                    array_push($attendance_list, $data);
                }
                
                    
                    
            }
            
            
            return $this->successResponse($attendance_list, 'Get Pdf Attendance Report successfully');
        }
    }

    public function exam_studentslist(Request $request)
    { 
        $Connection = $this->createNewConnection($request->branch_id);

        $studentdetails = $Connection->table('enrolls as en')
            ->select(
                'en.student_id',
                'en.roll',
                DB::raw('CONCAT(st.first_name, " ", st.last_name) as name'),
                'st.register_no',
            )
            ->leftJoin('students as st', 'st.id', '=', 'en.student_id')               
            ->where([
                ['en.department_id', '=', $request->department_id],
                ['en.class_id', '=', $request->class_id],
                ['en.section_id', '=', $request->section_id],
                ['en.academic_session_id', '=', $request->academic_session_id],
            ])
            ->get();

            
        return $this->successResponse($studentdetails, 'Student Lists');
    }
    public function get_subjectlist(Request $request)
    { 
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
    }
    public function get_mainsubjectlist(Request $request)
    { 
        $Connection = $this->createNewConnection($request->branch_id);
        if($request->mandatory=='1')
            {
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
            }
            else
            {
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
    }
    public function exam_papermarks(Request $request)
    {
       
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
        ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester_id, $session_id, $academic_session_id,$student_id,$department_id) {
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
    }
    
    public function getsubjecpapertlist(Request $request)
    { 
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
        $paper_list=[];
        foreach($getpapers as $paper)
        {
            $getsemester = $Connection->table('semester')->where('academic_session_id',$request->academic_session_id)->orderBy('start_date', 'asc')->get();
            $mark=[];
            foreach($getsemester as $sem)
            {
                $semester=$sem->id;
            $paper_id=$paper->id;
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
            $data=[
                "papers"=> $paper,               
                "marks"=> $mark
            ];
            
            array_push($paper_list, $data);
        }
            
        return $this->successResponse($paper_list, 'Get Subject Paper Lists');
    }
    public function stuexam_marklist(Request $request)
    {        
        $exam_id = $request->exam_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $subject_id= $request->subject_id;
        $session_id = $request->session_id;
        $academic_session_id = $request->academic_session_id;        
        $semester_id = $request->semester_id;  
        $student_id = $request->student_id;      
        $subject = $request->subject;
        $pdf_report= $request->pdf_report;       


        $Connection = $this->createNewConnection($request->branch_id);
        

        $getSemesterMark = $Connection->table('exam_papers as ep')
        ->select(
            'ep.id',
            'sa.score',
            'sa.grade',
            'gm.grade as grade_name',
        )   
        ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester_id, $session_id, $academic_session_id,$student_id) {
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
    }
    public function stuexam_avgmarklist(Request $request)
    {        
        $exam_id = $request->exam_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $subject_id= $request->subject_id;
        $session_id = $request->session_id;
        $academic_session_id = $request->academic_session_id;        
        $semester_id = $request->semester_id;  
        $student_id = $request->student_id;      
        $subject = $request->subject;
        $pdf_report= $request->pdf_report;       


        $Connection = $this->createNewConnection($request->branch_id);
        

        $getSemesterMark = $Connection->table('exam_papers as ep')
        ->select(
            'ep.id',
            'sa.score',
            'sa.grade',
            DB::raw('AVG(score) as avg'),
        )   
        ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester_id, $session_id, $academic_session_id,$student_id) {
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
    }
    
    public function stuexam_spmarklist(Request $request)
    {        
        $exam_id = $request->exam_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
      
        $session_id = $request->session_id;
        $academic_session_id = $request->academic_session_id;        
        $student_id = $request->student_id;        
        $subject = $request->subject;
        $paper=$request->paper;

        $semester1 = 1;
        $semester2 = 2;
        $semester3 = 3;


        $Connection = $this->createNewConnection($request->branch_id);
        $subjectrow = $Connection->table('subjects')->select('id')
        ->where('name', '=', $subject)->first();
        $subject_id=$subjectrow ->id;

        $getSemesterMark1 = $Connection->table('exam_papers as ep')
        ->select(
            'ep.id',
            'sa.freetext'
        )   
        ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester1, $session_id, $academic_session_id,$student_id) {
            $q->on('sa.paper_id', '=', 'ep.id')                
                ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                ->on('sa.semester_id', '=', DB::raw("'$semester1'"))
                ->on('sa.session_id', '=', DB::raw("'$session_id'"))
                ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                ->on('sa.student_id', '=', DB::raw("'$student_id'"))
                ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
        })        
        ->where([
            ['ep.class_id', '=', $request->class_id],
            ['ep.subject_id', '=', $subject_id],
            ['ep.academic_session_id', '=', $academic_session_id],
            ['ep.paper_name', '=', $paper]
        ])   
        ->first();
        $getSemesterMark2 = $Connection->table('exam_papers as ep')
        ->select(
            'ep.id',
            'sa.freetext'
        )    
        ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester2, $session_id, $academic_session_id,$student_id) {
            $q->on('sa.paper_id', '=', 'ep.id')                
                ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                ->on('sa.semester_id', '=', DB::raw("'$semester2'"))
                ->on('sa.session_id', '=', DB::raw("'$session_id'"))
                ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                ->on('sa.student_id', '=', DB::raw("'$student_id'"))
                ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
        }) 
        ->where([
            ['ep.class_id', '=', $request->class_id],
            ['ep.subject_id', '=', $subject_id],
            ['ep.academic_session_id', '=', $academic_session_id],
            ['ep.paper_name', '=', $paper]
        ])   
        ->first();
        $getSemesterMark3 = $Connection->table('exam_papers as ep')
        ->select(
            'ep.id',
            'sa.freetext'
        )   
        ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester3, $session_id, $academic_session_id,$student_id) {
            $q->on('sa.paper_id', '=', 'ep.id')                
                ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                ->on('sa.semester_id', '=', DB::raw("'$semester3'"))
                ->on('sa.session_id', '=', DB::raw("'$session_id'"))
                ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))
                ->on('sa.student_id', '=', DB::raw("'$student_id'"))
                ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
        }) 
        ->where([
            ['ep.class_id', '=', $request->class_id],
            ['ep.subject_id', '=', $subject_id],
            ['ep.academic_session_id', '=', $academic_session_id],
            ['ep.paper_name', '=', $paper]
        ])   
        ->first();
        $data=[
            "Semester1"=> $getSemesterMark1,
            "Semester2"=> $getSemesterMark2,
            "Semester3"=> $getSemesterMark3
        ];
        
        return $this->successResponse($data, 'Mark Detatils');
    }
    public function studentmonthly_attendance(Request $request)
    {        
        
        $date = $request->atdate;    
        //dd($date);
        $year_month=explode('-',$date);
        $Connection = $this->createNewConnection($request->branch_id);
        $getAttendanceCounts = $Connection->table('students as stud')
        ->select(
            DB::raw('COUNT(CASE WHEN sa.status = "present" then 1 ELSE NULL END) as "presentCount"'),
            DB::raw('COUNT(CASE WHEN sa.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
            DB::raw('COUNT(CASE WHEN sa.status = "late" then 1 ELSE NULL END) as "lateCount"'),
            DB::raw('COUNT(CASE WHEN sa.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
        )
        // ->join('enrolls as en', 'en.student_id', '=', 'stud.id')
        ->leftJoin('student_attendances_day as sa', 'sa.student_id', '=', 'stud.id')
        ->join('enrolls as en', function ($join) {
            $join->on('stud.id', '=', 'en.student_id')
                ->on('sa.class_id', '=', 'en.class_id')
                ->on('sa.section_id', '=', 'en.section_id');
        })
        ->where('stud.id', '=', $request->student_id)
        ->whereMonth('sa.date', $year_month[0])
        ->whereYear('sa.date', $year_month[1])
        ->get();
        return $this->successResponse($getAttendanceCounts, 'Attendance Detatils');
    }
    
    public function studentacyear_attendance(Request $request)
    {        
        
        $start = date('Y-m-d', strtotime($request->start));
        $end = date('Y-m-d', strtotime($request->end));
        $Connection = $this->createNewConnection($request->branch_id);
        $getAttendanceCounts = $Connection->table('students as stud')
        ->select(
            DB::raw('COUNT(CASE WHEN sa.status = "present" then 1 ELSE NULL END) as "presentCount"'),
            DB::raw('COUNT(CASE WHEN sa.status = "absent" then 1 ELSE NULL END) as "absentCount"'),
            DB::raw('COUNT(CASE WHEN sa.status = "late" then 1 ELSE NULL END) as "lateCount"'),
            DB::raw('COUNT(CASE WHEN sa.status = "excused" then 1 ELSE NULL END) as "excusedCount"'),
        )
        // ->join('enrolls as en', 'en.student_id', '=', 'stud.id')
        ->leftJoin('student_attendances_day as sa', 'sa.student_id', '=', 'stud.id')
        ->join('enrolls as en', function ($join) {
            $join->on('stud.id', '=', 'en.student_id')
                ->on('sa.class_id', '=', 'en.class_id')
                ->on('sa.section_id', '=', 'en.section_id');
        })
        ->where('stud.id', '=', $request->student_id)
        ->whereRaw('sa.date between "' . $start . '" and "' . $end . '"')
        ->get();
        return $this->successResponse($getAttendanceCounts, 'Attendance Detatils');
    }
    public function getmonthlyholidays(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            $start = date('Y-m-d', strtotime($request->start));
            $end = date('Y-m-d', strtotime($request->end));
            // echo $start;
            // echo "-----";
            // echo $end;
            // exit;
            $holidays = $Connection->table('holidays as hl')
                ->select('hl.id')
                ->whereRaw('hl.date between "' . $start . '" and "' . $end . '"')
                ->count();
            
            return $this->successResponse($holidays, 'Get Holidays Fetched successfully');
        }
    }    
    public function getacyearholidays(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            $start = date('Y-m-d', strtotime($request->start));
            $end = date('Y-m-d', strtotime($request->end));
            // echo $start;
            // echo "-----";
            // echo $end;
            // exit;
            $holidays = $Connection->table('holidays as hl')
                ->select('hl.id')
                ->whereRaw('hl.date between "' . $start . '" and "' . $end . '"')
                ->count();
            
            return $this->successResponse($holidays, 'Get Holidays Fetched successfully');
        }
    }
    public function studentclasssection(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            $studentId=$request->id;
            $class= $Connection->table('classes as cl')
            ->select('cl.id', 'cl.name', 'cl.short_name')
            ->where([
                ['cl.department_id', '=', $request->department_id],
            ])
            ->get();
            
            $result=array();
            foreach($class as $cls)
            {
            
            $classsec = $Connection->table('enrolls as t1')
            ->select('t1.class_id','t1.section_id','t1.academic_session_id','t2.name_numeric', 't3.name as section', 't4.name as academic_year')
            ->leftJoin('classes as t2', 't1.class_id', '=', 't2.id')
            ->leftJoin('sections as t3', 't1.section_id', '=', 't3.id')
            ->leftJoin('academic_year as t4', 't1.academic_session_id', '=', 't4.id')
            ->distinct()
            ->where('t1.student_id', $studentId)
            ->where('t2.id', $cls->id)
            ->first();
            
            $class_id=(isset($classsec->class_id) && $classsec->class_id!=null)?$classsec->class_id:'';
            $section_id=(isset($classsec->section_id) && $classsec->section_id!=null)?$classsec->section_id:'';
            $academic_session_id=(isset($classsec->academic_session_id) && $classsec->academic_session_id!=null)?$classsec->academic_session_id:'';
            $academic_year=(isset($classsec->academic_year) && $classsec->academic_year!=null)?$classsec->academic_year:'';
            $studentPlace='';
            if(isset($classsec->class_id) && $classsec->class_id!=null)
            {
                $results =$Connection->table('enrolls as t1')
                    ->select('student_id', 'id', DB::raw('ROW_NUMBER() OVER (ORDER BY id) as student_place'))
                    ->where('class_id', $class_id)
                    ->where('section_id', $section_id)
                    ->where('academic_session_id', $academic_session_id)
                    ->get();

                foreach ($results as $res) {                   
                
                    if($studentId==$res->student_id)
                    {
                        $studentPlace = $res->student_place;
                    }                
                }
                
            }
            $principal=''; $teacher='';
            if($academic_year!='')
            {
                $principaldata =$Connection->table('staffs')
                    ->select('first_name', 'last_name')
                    ->where('designation_id', '1')
                    ->first();
                $teacherdata =$Connection->table('teacher_allocations as t1')
                    ->select('t1.teacher_id', 't2.first_name', 't2.last_name')
                    ->leftJoin('staffs as t2', 't1.teacher_id', '=', 't2.id')
                    ->where('t1.class_id', $class_id)
                    ->where('t1.section_id', $section_id)
                    ->where('t1.academic_session_id', $academic_session_id)
                    ->first();
                    $pfirst_name=(isset($principaldata->first_name) && $principaldata->first_name!=null)?$principaldata->first_name:'';
                    $plast_name=(isset($principaldata->last_name) && $principaldata->last_name!=null)?$principaldata->last_name:'';
                    $tfirst_name=(isset($teacherdata->first_name) && $teacherdata->first_name!=null)?$teacherdata->first_name:'';
                    $tlast_name=(isset($teacherdata->last_name) && $teacherdata->last_name!=null)?$teacherdata->last_name:'';
            
                    $principal=$pfirst_name.' '.$plast_name;
                    $teacher=$tfirst_name.' '.$tlast_name;
            }
            $datas=[
                "class"=> $cls->name,
                "class_id"=> $class_id,
                "section"=> (isset($classsec->section) && $classsec->section!=null)?$classsec->section:'',
                "section_id"=> $section_id,
                "academic_session_id"=> $academic_session_id,
                "academic_year"=> (isset($classsec->academic_year) && $classsec->academic_year!=null)?$classsec->academic_year:'',
                "studentPlace"=> $studentPlace,
                "principal"=> $principal,
                "teacher"=> $teacher
            ];
            
            array_push($result, $datas);
            }
            return $this->successResponse($result, 'Get class Section Fetched successfully');
        }
    }
    public function stuoverall_marklist(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            $student_id=$request->student_id;
            $class= $Connection->table('classes as cl')
            ->select('cl.id', 'cl.name', 'cl.short_name')
            ->where([
                ['cl.department_id', '=', $request->department_id],
            ])
            ->get();
            $result=array();
            foreach($class as $cls)
            {
            $classsec = $Connection->table('enrolls as t1')
            ->select('t1.class_id','t1.section_id','t1.academic_session_id','t2.name_numeric', 't3.name as section', 't4.name as academic_year')
            ->leftJoin('classes as t2', 't1.class_id', '=', 't2.id')
            ->leftJoin('sections as t3', 't1.section_id', '=', 't3.id')
            ->leftJoin('academic_year as t4', 't1.academic_session_id', '=', 't4.id')            
            ->where('t1.student_id', $student_id)
            ->where('t2.id', $cls->id)
            ->distinct()
            ->first();
            
            $class_id=(isset($classsec->class_id) && $classsec->class_id!=null)?$classsec->class_id:'';
            $section_id=(isset($classsec->section_id) && $classsec->section_id!=null)?$classsec->section_id:'';
            $academic_session_id=(isset($classsec->academic_session_id) && $classsec->academic_session_id!=null)?$classsec->academic_session_id:'';
            $academic_year=(isset($classsec->academic_year) && $classsec->academic_year!=null)?$classsec->academic_year:'';
            $studentPlace='';
            $markscore='';$grademark=''; $markpoints='';$markfreetext=''; $printmark='';$marktype='';
            if(isset($classsec->class_id) && $classsec->class_id!=null)
            {
                $subject = $request->subject;
                $paper=$request->paper;

                $semester1 = 1;
                $semester2 = 2;
                $semester3 = 3;


                $Connection = $this->createNewConnection($request->branch_id);
                $subjectrow = $Connection->table('subjects')->select('id')
                ->where('name', '=', $subject)->first();
                $subject_id=isset($subjectrow ->id)?$subjectrow ->id:0;
                //dd($section_id);
                $getSemesterMark1 = $Connection->table('exam_papers as ep')
                ->select(           
                    'ep.id',
                    'ep.score_type',
                    'sa.score',
                    'sa.grade',
                    'sa.points',
                    'sa.freetext',
                    'gm.grade as grade_name',
                )   
                ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $subject_id, $semester3,  $academic_session_id,$student_id) {
                    $q->on('sa.paper_id', '=', 'ep.id')                
                        ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                        ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                        ->on('sa.semester_id', '=', DB::raw("'$semester3'"))
                        ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))                        
                        ->on('sa.student_id', '=', DB::raw("'$student_id'"))            
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                }) 
                ->leftJoin('grade_marks as gm', 'gm.id', '=', 'sa.points')      
                ->where([
                    ['ep.class_id', '=', $class_id],
                    ['ep.subject_id', '=', $subject_id],
                    ['ep.academic_session_id', '=', $academic_session_id],
                    ['ep.paper_name', '=', $paper]
                ])   
                ->first();

                //$markscore=$getSemesterMark1['score'];
                //$grademark=$getSemesterMark1['grade'];
                $markscore=(isset($getSemesterMark1->score) && $getSemesterMark1->score!=null)?$getSemesterMark1->score:'';
            
                $grademark=(isset($getSemesterMark1->grade) && $getSemesterMark1->grade!=null)?$getSemesterMark1->grade:'';
                $markpoints=(isset($getSemesterMark1->points) && $getSemesterMark1->points!=null)?$getSemesterMark1->points:'';
            
                $markfreetext=(isset($getSemesterMark1->freetext) && $getSemesterMark1->freetext!=null)?$getSemesterMark1->freetext:'';
                $marktype=(isset($getSemesterMark1->score_type) && $getSemesterMark1->score_type!=null)?$getSemesterMark1->score_type:'';
                if($marktype=='Points')
                {
                    $printmark=$markpoints; 
                }
                elseif($marktype=='Freetext')
                {
                    $printmark=$markfreetext; 
                }
                elseif($marktype=='Mark')
                {
                    $printmark=$markscore; 
                }
                else
                {
                    $marktype=$grademark; 
                }
            }
            
            $datas=[
                "class"=> $cls->name,
                "class_id"=> $class_id,                
                "markscore"=> $markscore,
                "grademark"=> $grademark,                
                "markpoints"=> $markpoints,
                "markfreetext"=> $markfreetext,                
                "printmark"=> $printmark,
                "marktype"=> $marktype
            ];
            
            array_push($result, $datas);
            }
            return $this->successResponse($result, 'Get over All Mark successfully');           
        }
    }
    public function stuoverall_spmarklist(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);
            $student_id=$request->student_id;
            $class= $Connection->table('classes as cl')
            ->select('cl.id', 'cl.name', 'cl.short_name')
            ->where([
                ['cl.department_id', '=', $request->department_id],
            ])
            ->get();
            $result=array();
           
            foreach($class as $cls)
            {
                
            $classsec = $Connection->table('enrolls as t1')
            ->select('t1.class_id','t1.section_id','t1.academic_session_id','t2.name_numeric', 't3.name as section', 't4.name as academic_year')
            ->leftJoin('classes as t2', 't1.class_id', '=', 't2.id')
            ->leftJoin('sections as t3', 't1.section_id', '=', 't3.id')
            ->leftJoin('academic_year as t4', 't1.academic_session_id', '=', 't4.id')            
            ->where('t1.student_id', $student_id)
            ->where('t2.id', $cls->id)
            ->distinct()
            ->first();
            
            $class_id=(isset($classsec->class_id) && $classsec->class_id!=null)?$classsec->class_id:'';
            $section_id=(isset($classsec->section_id) && $classsec->section_id!=null)?$classsec->section_id:'';
            $academic_session_id=(isset($classsec->academic_session_id) && $classsec->academic_session_id!=null)?$classsec->academic_session_id:'';
            $academic_year=(isset($classsec->academic_year) && $classsec->academic_year!=null)?$classsec->academic_year:'';
            $studentPlace='';
            $marktext='';
            if(isset($classsec->class_id) && $classsec->class_id!=null)
            {
                $subject = $request->subject;
                $paper=$request->paper;

                $semester1 = 1;
                $semester2 = 2;
                $semester3 = 3;


                $Connection = $this->createNewConnection($request->branch_id);
                $subjectrow = $Connection->table('subjects')->select('id')
                ->where('name', '=', $subject)->first();
                $subject_id=isset($subjectrow ->id)?$subjectrow ->id:0;
                //dd($section_id);
                $getSemesterMark1 = $Connection->table('exam_papers as ep')
                ->select(
                    'ep.id',
                    'sa.freetext'
                )    
                ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $subject_id, $semester3,  $academic_session_id,$student_id) {
                    $q->on('sa.paper_id', '=', 'ep.id')                
                        ->on('sa.class_id', '=', DB::raw("'$class_id'"))
                        ->on('sa.section_id', '=', DB::raw("'$section_id'"))
                        ->on('sa.semester_id', '=', DB::raw("'$semester3'"))
                        ->on('sa.subject_id', '=', DB::raw("'$subject_id'"))                        
                        ->on('sa.student_id', '=', DB::raw("'$student_id'"))            
                        ->on('sa.academic_session_id', '=', DB::raw("'$academic_session_id'"));
                })       
                ->where([
                    ['ep.class_id', '=', $class_id],
                    ['ep.subject_id', '=', $subject_id],
                    ['ep.academic_session_id', '=', $academic_session_id],
                    ['ep.paper_name', '=', $paper]
                ])   
                ->first();
                //dd($getSemesterMark1);
                //$markscore=$getSemesterMark1['score'];
                //$grademark=$getSemesterMark1['grade'];
                $marktext=(isset($getSemesterMark1->freetext) && $getSemesterMark1->freetext!=null)?$getSemesterMark1->freetext:'';
                        
            }
            
            
            $datas=[
                "class"=> $cls->name,
                "class_id"=> $class_id,                
                "marktext"=> $marktext
            ];
            
            array_push($result, $datas);
            }
            return $this->successResponse($result, 'Get over All Specail Mark successfully');
        }
    }
    public function getpdf_report(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $branchID=$request->branch_id;
            $Connection = $this->createNewConnection($request->branch_id);
            
            $pdflist = $Connection->table('pdf_report')->select('id','pdf_name')->get();
            
            
            return $this->successResponse($pdflist, 'Get Pdf Report successfully');
        }
    }
    
   
   
}
