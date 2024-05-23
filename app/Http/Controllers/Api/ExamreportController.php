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
    public function __construct(CommonHelper $commonHelper) {
        $this->commonHelper = $commonHelper;
    }
    public function getec_marks(Request $request)
    {
        try{

        $exam_id = $request->exam_id;
        $class_id = $request->class_id;
        $department_id = $request->department_id;
        $section_id = $request->section_id;
        //$subject_id = $request->subject_id;
        $semester_id = $request->semester_id;
        $session_id = $request->session_id;
        $academic_session_id = $request->academic_session_id;        
        $student_id = $request->student_id;
        $paper_name= $request->paper_name;
        $Connection = $this->createNewConnection($request->branch_id);
        $getsubject = $Connection->table('subjects as sb')
        ->select(
            'sb.id as subject_id',
            'sb.name'
        )       
        // ->where('sb.name', '=', 'EC')
        ->where('sb.name', '=', 'English Communication')
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
            ['ep.paper_name', 'like', $paper_name]
        ])   
        ->first();
        
        
        return $this->successResponse($getSubjectMarks, 'Get EC Mark Detatils');
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getec_marks');
        }
    }
    public function getec_teacher(Request $request)
    {
        try{
        $class_id = $request->class_id;
        $department_id = $request->department_id;
        $section_id = $request->section_id;
        //$subject_id = $request->subject_id;
        $semester_id = $request->semester_id;
        $session_id = $request->session_id;
        $academic_session_id = $request->academic_session_id;        
        $student_id = $request->student_id;
        $paper_name= $request->paper_name;
        $Connection = $this->createNewConnection($request->branch_id);
        $getsubject = $Connection->table('subjects as sb')
        ->select(
            'sb.id as subject_id',
            'sb.name'
        )       
        ->where('sb.name', '=', 'EC')
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getec_teacher');
        }
    }
    public function getsubjectpapermark(Request $request)
    {
        try{ 
        $Connection = $this->createNewConnection($request->branch_id);
        $getsubject = $Connection->table('subjects as sb')
        ->select(
            'sb.id as subject_id',
            'sb.name'
        )       
        ->where('sb.name', 'like', $request->subject)        
        ->first();
        $subject_id = $getsubject->subject_id;
        
        $paper_list=[];
        foreach($request->papers as $paper)
        {
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
        $getsemester = $Connection->table('semester')->where('academic_session_id',$request->academic_session_id)->orderBy('start_date', 'asc')->get();
            $mark=['','',''];
            if(!empty($getpapers))
            {
                $mark=[];
                foreach($getsemester as $sem)
                {
                    $semester=$sem->id;
                    $paper_id=$getpapers->id;
                    
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
                    array_push($mark,$getmark);
                }
            }
            $data=[
                "papers"=> $paper,               
                "marks"=> $mark
            ];
            
            array_push($paper_list, $data);
        }
            
        return $this->successResponse($paper_list, 'Get Subject Paper Lists');
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getsubjectpapermark');
        }
    }
    public function classteacher_principal(Request $request)
    {
        try{        
        
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $academic_session_id = $request->academic_session_id;
        $Connection = $this->createNewConnection($request->branch_id);
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

        $datas=[
        
            "class_id"=> $class_id,
        
            "section_id"=> $section_id,
            "academic_session_id"=> $academic_session_id,
            
            "principal"=> $principal,
            "teacher"=> $teacher
        ];
        return $this->successResponse($datas, 'Get Class Teacher & Principal Details Successfully.');
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in classteacher_principal');
        }
    }
    public function stuexam_ppmarklist(Request $request)
    {
        try{        
        $exam_id = $request->exam_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $subject_id= $request->subject_id;
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
        ->where([
            ['ep.class_id', '=', $request->class_id],
            ['ep.subject_id', '=', $subject_id],
            ['ep.academic_session_id', '=', $academic_session_id],
            ['ep.paper_name', 'like', $paper]
        ])   
        ->first();      
        
        return $this->successResponse($getSemesterMark, 'Get Personal Point Mark Details Successfully.');
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in stuexam_ppmarklist');
        }
    }
    public function stuexam_ppavgmarklist(Request $request)
    {
        try{        
        $exam_id = $request->exam_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $subject_id= $request->subject_id;
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
            ['ep.paper_name', 'like', $paper]
        ])   
        ->first();      
        
        return $this->successResponse($getSemesterMark, 'Get Personal Point Avg Mark Details Successfully');
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in stuexam_ppavgmarklist');
        }
    }
    public function getpaperoverallmarklist1(Request $request)
    {
        try{ 
        $exam_id = $request->exam_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $subject_id= $request->subject_id;
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
        $getsemester = $Connection->table('semester')->where('academic_session_id',$request->academic_session_id)->orderBy('start_date', 'asc')->get();
            $mark=[];
            $nsem=count($getsemester);
            $s=0;
            $getmark=[];
            foreach($getsemester as $sem)
            {
                $s++;
               
                if($nsem==$s)
                {
                    $semester=$sem->id;
            
                
                $getmark = $Connection->table('exam_papers as ep')
                ->select(
                    'sa.score',
                    'sa.grade',
                    'sa.points',
                    'sa.freetext',
                    'gm.grade as grade_name',
                    'ep.score_type',
                )    
                ->leftJoin('student_marks as sa', function ($q) use ($class_id, $section_id, $exam_id, $subject_id, $semester, $session_id, $academic_session_id,$student_id) {
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getpaperoverallmarklist1');
        }
    }
    public function getacyeardates(Request $request)
    {
        try{
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getacyeardates');
        }
    }
    public function getsem_studentattendance(Request $request)
    {
        try{
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
                    
                    $year= trim($date->format('Y').PHP_EOL);
                    /*if($year==2024)
                    {
                        dd($mon,$year);
                    }*/
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
                    ->whereMonth('sa.date', $mon)
                    ->whereYear('sa.date', $year)
                    ->first();
                    $totalcoming= $totaldays-$suspension;
                                $totpres=$getAttendance->presentCount;
                                
                                $totabs=$getAttendance->absentCount;
                                
                                $totlate=$getAttendance->lateCount;
                                
                                $totexc=$getAttendance->excusedCount;
                    $data=[
                        "month"=>$mon,
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getsem_studentattendance');
        }
    }

    public function exam_studentslist(Request $request)
    {
        try{ 
        $Connection = $this->createNewConnection($request->branch_id);

        $studentdetails = $Connection->table('enrolls as en')
            ->select(
                'en.student_id',
                'en.roll',
                DB::raw('CONCAT(st.first_name, " ", st.last_name) as name'),
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in exam_studentslist');
        }
    }
    public function get_subjectlist(Request $request)
    {
        try{ 
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in get_subjectlist');
        }
    }
    public function get_mainsubjectlist(Request $request)
    {
        try{ 
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in get_mainsubjectlist');
        }
    }
    public function exam_papermarks(Request $request)
    {
        try{
       
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in exam_papermarks');
        }
    }
    
    public function getsubjecpapertlist(Request $request)
    {
        try{ 
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
         } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getsubjecpapertlist');
        }
    }

    public function stuexam_marklist(Request $request)
    {
        try{        
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
         } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in stuexam_marklist');
        }
    }
    public function stuexam_avgmarklist(Request $request)
    {
        try{        
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
         } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in stuexam_avgmarklist');
        }
    }
    public function get_overallsubjectlist(Request $request)
    {
        try{ 
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
         } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in get_overallsubjectlist');
        }
    }
    public function get_overallpaperlist(Request $request)
    {
        try{ 
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
         } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in get_overallpaperlist');
        }
    }
    public function getpaperoverallmarklist(Request $request)
    {
        try{ 
        $Connection = $this->createNewConnection($request->branch_id);
        
        $getsemester = $Connection->table('semester')->where('academic_session_id',$request->academic_session_id)->orderBy('start_date', 'asc')->get();
            $mark=[];
            $nsem=count($getsemester);
            $s=0;
            $getmark=[];
            foreach($getsemester as $sem)
            {
                $s++;
               
                if($nsem==$s)
                {
                    $semester=$sem->id;
            
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getpaperoverallmarklist');
        }
    }
    public function studentclasssection(Request $request)
    {
        try{
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
            ->select('cl.id', 'cl.name', 'cl.short_name','cl.name_numeric')
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
                "class_numeric"=> $cls->name_numeric,
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in studentclasssection');
        }
    }
    public function getpdf_report(Request $request)
    {
        try{
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
        } catch(Exception $error) {
            return $this->commonHelper->generalReturn('403','error',$error,'Error in getpdf_report');
        }
    }  
}
