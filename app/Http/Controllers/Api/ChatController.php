<?php

namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// base controller add
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\BaseController as BaseController;
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
use Exception;
use App\Helpers\CommonHelper;

class ChatController extends BaseController
{
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper)
    {
        $this->commonHelper = $commonHelper;
    }
    // get all teacher
    public function chatGetTeacherList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
        ]);
        $toid = $request->to_id;
        $to_role = $request->role;

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $main_db = config('constants.main_db');
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get all teachers

            $allTeachers = $conn->table('staffs as stf')
                ->select(
                    'stf.id as staff_id',
                    DB::raw("CONCAT(stf.first_name, ' ', stf.last_name) as name"),
                    // DB::raw("(select COUNT('ch.*') from chats as ch where ch.chat_fromid=stf.id AND ch.chat_toid='".$request->to_id."' AND ch.chat_touser='".$request->role."' AND ch.chat_fromuser='Teacher' AND ch.chat_status='Unread' AND flag=1 ) as msgcount"), 
                    'us.role_id',
                    // 'rol.role_name',
                    // 'us.user_id',
                    'us.email',
                    DB::raw("GROUP_CONCAT(rol.role_name) as role"),
                    'stf.photo'
                )
                ->join('' . $main_db . '.users as us', function ($join) use ($request) {
                    $join->on('stf.id', '=', 'us.user_id')
                        ->where('us.branch_id', $request->branch_id);
                })
                // ->join('' . $main_db . '.users as us', 'stf.id', '=', 'us.user_id')

                // ->join('' . $main_db . '.roles as rol', 'rol.id', '=', 'us.role_id')
                // ->where(function ($query) {
                //     // foreach ($search_terms as $item) {
                //     $query->whereRaw('FIND_IN_SET(?,us.role_id)', ['4'])
                //         ->orWhereRaw('FIND_IN_SET(?,us.role_id)', ['3']);
                //     // }
                // })
                ->join('' . $main_db . '.roles as rol', function ($join) {
                    $join->on(\DB::raw("FIND_IN_SET(rol.id,us.role_id)"), ">", \DB::raw("'0'"))
                        ->whereRaw('FIND_IN_SET(?,us.role_id)', ['4'])
                        ->orWhereRaw('FIND_IN_SET(?,us.role_id)', ['3']);
                })
                // ->join('' . $main_db . '.roles as rol', 'rol.id', '=', 'us.role_id')
                // ->leftJoin("staff_departments as sdp", DB::raw("FIND_IN_SET(sdp.id,stf.department_id)"), ">", DB::raw("'0'"))
                // ->where([
                //     ['us.branch_id', '=', $request->branch_id]
                // ])
                // ->whereIn('us.role_id', ['4'])
                // ->whereNotIn('stf.id', [$request->staff_id])

                ->when($request->role == "Teacher", function ($q)  use ($request) {
                    $q->whereNotIn('stf.id', [$request->id]);
                })
                ->where('stf.is_active', '=', '0')
                ->groupBy('stf.id')

                // ->limit(10)
                ->get()->toArray();
            foreach ($allTeachers as $teacher) {
                // return $teacher;
                $created = $conn->table('chats as ch')
                    ->select('ch.id as chat_id', 'ch.created_at')
                    ->where(function ($query) use ($request, $teacher) {
                        $query->where('chat_fromid', '=', $request->id)
                            ->where('chat_toid',  '=', $teacher->staff_id)
                            ->where('chat_fromuser', $request->role)
                            ->where('chat_touser', 'Teacher');
                    })
                    ->orWhere(function ($query2) use ($request, $teacher) {
                        $query2->where('chat_fromid', '=', $teacher->staff_id)
                            ->where('chat_toid',  '=', $request->id)
                            ->where('chat_fromuser', 'Teacher')
                            ->where('chat_touser', $request->role);
                    })
                    ->where('ch.flag', '1')
                    ->latest()->first();
                // ->get();
                // return $request->staff_id;
                $count = count($conn->table('chats as ch')->select('ch.id as chat_id', 'ch.created_at')
                    ->where('ch.chat_fromid', '=', $teacher->staff_id)
                    ->where('ch.chat_toid',  '=', $request->id)
                    ->where('ch.chat_status', 'Unread')
                    ->where('ch.chat_fromuser', 'Teacher')
                    ->where('ch.chat_touser', $request->role)
                    ->where('ch.flag', '1')
                    ->get());
                $teacher->msgcount = $count;
                $teacher->created_at = isset($created->created_at) ? $created->created_at : "";
            }
            $col = array_column($allTeachers, "created_at");
            array_multisort($col, SORT_DESC, $allTeachers);

            // return $allTeachers;
            // $new = [];

            // return usort($allTeachers, "created_at");
            return $this->successResponse($allTeachers, 'get all teacher record fetch successfully');
        }
    }
    // get all parents
    public function chatGetParentList(Request $request)
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
            // get all parents
            $allParents = $conn->table('parent as prnt')
                ->select(
                    'prnt.id',
                    DB::raw("CONCAT(prnt.first_name, ' ', prnt.last_name) as name"),
                    // DB::raw("(select COUNT('ch.*') from chats as ch where ch.chat_fromid=prnt.id AND ch.chat_toid='".$request->to_id."' AND ch.chat_touser='".$request->role."' AND ch.chat_fromuser='Parent' AND ch.chat_status='Unread' AND flag=1) as msgcount"), 
                    'prnt.photo'
                )->limit(10)->get()->toArray();

            foreach ($allParents as $parent) {
                // return $parent;
                $created = $conn->table('chats as ch')
                    ->select('ch.id as chat_id', 'ch.created_at')
                    ->where(function ($query) use ($request, $parent) {
                        $query->where('chat_fromid', '=', $request->id)
                            ->where('chat_toid',  '=', $parent->id)
                            ->where('chat_fromuser', $request->role)
                            ->where('chat_touser', 'Parent');
                    })
                    ->orWhere(function ($query2) use ($request, $parent) {
                        $query2->where('chat_fromid', '=', $parent->id)
                            ->where('chat_toid',  '=', $request->id)
                            ->where('chat_fromuser', 'Parent')
                            ->where('chat_touser', $request->role);
                    })
                    ->where('ch.flag', '1')
                    ->latest()->first();
                // ->get();
                // return $request->staff_id;
                $count = count($conn->table('chats as ch')->select('ch.id as chat_id', 'ch.created_at')
                    ->where('ch.chat_fromid', '=', $parent->id)
                    ->where('ch.chat_toid',  '=', $request->id)
                    ->where('ch.chat_status', 'Unread')
                    ->where('ch.chat_fromuser', 'Parent')
                    ->where('ch.chat_touser', $request->role)
                    ->where('ch.flag', '1')
                    ->get());
                $parent->msgcount = $count;
                $parent->created_at = isset($created->created_at) ? $created->created_at : "";
            }
            $col = array_column($allParents, "created_at");
            array_multisort($col, SORT_DESC, $allParents);
            return $this->successResponse($allParents, 'get all Parent record fetch successfully');
        }
    }
    // get teacher assign parents
    public function chatGetTeacherAssignParentList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'teacher_id' => 'required',
            'role' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $main_db = config('constants.main_db');
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $subject_assigns_teachers = $conn->table('subject_assigns as sa')
                ->select(
                    // 'sa.class_id',
                    // 'sa.section_id',
                    // 'sa.teacher_id',
                    'us.id as uuid',
                    'p.id',
                    DB::raw("CONCAT(p.first_name, ' ', p.last_name) as name"),
                    'p.photo'
                )

                ->join('enrolls as en', function ($join) {
                    $join->on('en.class_id', '=', 'sa.class_id')
                        ->on('en.section_id', '=', 'sa.section_id')
                        ->on('en.active_status', '=', DB::raw("'0'"));
                })
                ->join('parent as p', function ($join) {
                    $join->on('p.ref_guardian_id', '=', 'en.student_id');
                    $join->orOn('p.ref_mother_id', '=', 'en.student_id');
                    $join->orOn('p.ref_father_id', '=', 'en.student_id');
                })
                ->join('' . $main_db . '.users as us', function ($join) use ($request) {
                    $join->on('p.id', '=', 'us.user_id')
                        ->where('us.branch_id', $request->branch_id);
                })
                ->where([
                    ['sa.teacher_id', '=', $request->teacher_id]
                ])
                ->groupBy('sa.class_id', 'sa.section_id', 'en.student_id', 'p.id')
                ->get()->toArray();
            // ->limit(10)->get();
            foreach ($subject_assigns_teachers as $parent) {
                // return $parent;
                $created = $conn->table('chats as ch')
                    ->select('ch.id as chat_id', 'ch.created_at')
                    ->where(function ($query) use ($request, $parent) {
                        $query->where('chat_fromid', '=', $request->teacher_id)
                            ->where('chat_toid',  '=', $parent->id)
                            ->where('chat_fromuser', $request->role)
                            ->where('chat_touser', 'Parent');
                    })
                    ->orWhere(function ($query2) use ($request, $parent) {
                        $query2->where('chat_fromid', '=', $parent->id)
                            ->where('chat_toid',  '=', $request->teacher_id)
                            ->where('chat_fromuser', 'Parent')
                            ->where('chat_touser', $request->role);
                    })
                    ->where('ch.flag', '1')
                    ->latest()->first();
                // ->get();
                // return $request->staff_id;
                $count = count($conn->table('chats as ch')->select('ch.id as chat_id', 'ch.created_at')
                    ->where('ch.chat_fromid', '=', $parent->id)
                    ->where('ch.chat_toid',  '=', $request->teacher_id)
                    ->where('ch.chat_status', 'Unread')
                    ->where('ch.chat_fromuser', 'Parent')
                    ->where('ch.chat_touser', $request->role)
                    ->where('ch.flag', '1')
                    ->get());
                $parent->msgcount = $count;
                $parent->created_at = isset($created->created_at) ? $created->created_at : "";
            }
            $col = array_column($subject_assigns_teachers, "created_at");
            array_multisort($col, SORT_DESC, $subject_assigns_teachers);
            // dd($subject_assigns_teachers);
            // get teacher allocations parents
            // $allTeachers = $conn->table('teacher_allocations as ta')
            //     ->select(
            //         // 'ta.id',
            //         // 'en.student_id',
            //         // 'p.id as parent_id',
            //         // DB::raw("CONCAT(p.first_name, ' ', p.last_name) as name")
            //         'p.id',
            //         DB::raw("CONCAT(p.first_name, ' ', p.last_name) as name"),
            //         // DB::raw("(select COUNT('ch.*') from chats as ch where ch.chat_fromid=p.id AND ch.chat_toid='".$request->to_id."' AND ch.chat_touser='".$request->role."' AND ch.chat_fromuser='Parent' AND ch.chat_status='Unread' AND flag=1) as msgcount"), 
            //         'p.photo'
            //     )
            //     ->join('enrolls as en', function ($join) {
            //         $join->on('en.class_id', '=', 'ta.class_id')
            //             ->on('en.section_id', '=', 'ta.section_id')
            //             ->on('en.active_status', '=', DB::raw("'0'"));
            //         // $join->on('st.mother_id', '=', 'p.id');
            //         // $join->orOn('st.guardian_id', '=', 'p.id');
            //     })
            //     ->join('parent as p', function ($join) {
            //         $join->on('p.ref_guardian_id', '=', 'en.student_id');
            //         $join->orOn('p.ref_mother_id', '=', 'en.student_id');
            //         $join->orOn('p.ref_father_id', '=', 'en.student_id');
            //     })
            //     ->where([
            //         ['ta.teacher_id', '=', $request->teacher_id]
            //     ])
            //     ->groupBy("en.student_id")
            //     ->get();
            return $this->successResponse($subject_assigns_teachers, 'get assign teacher record fetch successfully');
        }
    }
    // get parent chat teacher list
    public function getParentChatTeacherList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $main_db = config('constants.main_db');
            $conn = $this->createNewConnection($request->branch_id);
            // get teacher allocations parents

            $allTeachers = $conn->table('staffs as stf')
                ->select(
                    'us.id as uuid',
                    'stf.id as staff_id',
                    DB::raw("CONCAT(stf.first_name, ' ', stf.last_name) as name"),
                    // DB::raw("(select COUNT('ch.*') from chats as ch where ch.chat_fromid=stf.id AND ch.chat_toid='".$request->to_id."' AND ch.chat_touser='".$request->role."' AND ch.chat_fromuser='Teacher' AND ch.chat_status='Unread' AND flag=1 ) as msgcount"), 
                    'us.role_id',
                    // 'rol.role_name',
                    // 'us.user_id',
                    'us.email',
                    // DB::raw("GROUP_CONCAT(rol.role_name) as role" ),
                    'stf.photo'
                )
                ->join('' . $main_db . '.users as us', function ($join) use ($request) {
                    $join->on('stf.id', '=', 'us.user_id')
                        ->where('us.branch_id', $request->branch_id);
                })
                ->join('' . $main_db . '.roles as rol', function ($join) {
                    $join->on(\DB::raw("FIND_IN_SET(rol.id,us.role_id)"), ">", \DB::raw("'0'"))
                        ->whereRaw('FIND_IN_SET(?,us.role_id)', ['4'])
                        ->orWhereRaw('FIND_IN_SET(?,us.role_id)', ['3']);
                })
                ->join('subject_assigns as sa', function ($join) use ($request) {
                    $join->on('sa.teacher_id', '=', 'stf.id')
                        ->where('sa.academic_session_id', $request->academic_session_id);
                })
                ->join('enrolls as en', function ($join) use ($request) {
                    $join->on('en.class_id', '=', 'sa.class_id')
                        ->on('en.section_id', '=', 'sa.section_id')
                        ->on('en.active_status', '=', DB::raw("'0'"))
                        ->where('en.academic_session_id', $request->academic_session_id)
                        ->where('en.student_id', $request->student_id);
                    // $join->on('st.mother_id', '=', 'p.id');
                    // $join->orOn('st.guardian_id', '=', 'p.id');
                })
                ->groupBy("stf.id")
                ->get()->toArray();
            foreach ($allTeachers as $teacher) {
                // return $teacher;
                // dd($teacher);
                $created = $conn->table('chats as ch')
                    ->select('ch.id as chat_id', 'ch.created_at')
                    ->where(function ($query) use ($request, $teacher) {
                        $query->where('chat_fromid', '=', $request->id)
                            ->where('chat_toid',  '=', $teacher->staff_id)
                            ->where('chat_fromuser', $request->role)
                            ->where('chat_touser', 'Teacher');
                    })
                    ->orWhere(function ($query2) use ($request, $teacher) {
                        $query2->where('chat_fromid', '=', $teacher->staff_id)
                            ->where('chat_toid',  '=', $request->id)
                            ->where('chat_fromuser', 'Teacher')
                            ->where('chat_touser', $request->role);
                    })
                    ->where('ch.flag', '1')
                    ->latest()->first();
                // ->get();
                // return $request->staff_id;
                $count = count($conn->table('chats as ch')->select('ch.id as chat_id', 'ch.created_at')
                    ->where('ch.chat_fromid', '=', $teacher->staff_id)
                    ->where('ch.chat_toid',  '=', $request->id)
                    ->where('ch.chat_status', 'Unread')
                    ->where('ch.chat_fromuser', 'Teacher')
                    ->where('ch.chat_touser', $request->role)
                    ->where('ch.flag', '1')
                    ->get());
                $teacher->msgcount = $count;
                $teacher->created_at = isset($created->created_at) ? $created->created_at : "";
            }
            $col = array_column($allTeachers, "created_at");
            array_multisort($col, SORT_DESC, $allTeachers);
            return $this->successResponse($allTeachers, 'get assign teacher record fetch successfully');
        }
    }
    // chat sent message
    public function chatSentMessage(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'message' => 'required|max:255',
            'from_id' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get all teachers
            $message = [
                'from_id' => $request->from_id,
                'to_id' => $request->to_id,
                'to_type' => $request->to_type,
                'message' => $request->message,
                'status' => '0',
                'message_type' => $request->message_type,
                'file_name' => isset($request->message_type) ? $request->message_type : null,
                'url_details' => isset($request->url_details) ? $request->url_details : null,
                'created_at' => date("Y-m-d H:i:s")
            ];
            // insert data
            $query = $conn->table('messages')->insert($message);
            $success = [];
            if (!$query) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'Message sent successfully');
            }
        }
    }
    public function storechat(Request $request)
    {

        //dd('123');
        // create new connection
        $conn = $this->createNewConnection($request->branch_id);
        $branch_id = $request->branch_id;
        $file_path = base_path() . '/public/' . $branch_id . '/chats/';


        // Checking whether file exists or not
        if (!file_exists($file_path)) {

            // Create a new file or direcotry
            mkdir($file_path, 0777, true);
        }
        // insert data
        if (isset($request->chat_document)) {
            $now = now();
            $name = strtotime($now);
            $extension = $request->chat_file_extension;
            $fileName = $name . "." . $extension;

            $base64 = base64_decode($request->chat_document);
            $file = $file_path . $fileName;
            $suc = file_put_contents($file, $base64);
        } else {
            $fileName = null;
        }
        $data = [
            'chat_fromid' => $request['chat_fromid'],
            'chat_fromname' => $request['chat_fromname'],
            'chat_fromuser' => $request['chat_fromuser'],
            'chat_toid' => $request['chat_toid'],
            'chat_toname' => $request['chat_toname'],
            'chat_touser' => $request['chat_touser'],
            'chat_content' => $request['chat_content'],
            'chat_status' => $request['chat_status'],
            'chat_document' => $fileName,
            'chat_file_extension' => $request['chat_file_extension'],
            'flag' => '1',
            'created_at' => date("Y-m-d H:i:s")
        ];
        //$query = $staffConn->table('staff_leaves')->insert($data);
        $query = $conn->table('chats')->insert($data);
        $success = [];
        if (!$query) {
            return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
        } else {
            return $this->successResponse($success, 'Message sent successfully');
        }
    }
    public function deletechat(Request $request)
    {

        //dd('123');
        // create new connection
        $conn = $this->createNewConnection($request->branch_id);
        $branch_id = $request->branch_id;
        $chat_id = $request['chat_id'];
        //  $query = $conn->table('chats')->insert($data);
        $query = $conn->table('chats as ch')->Where([
            ['ch.id', $chat_id]
        ])->update([
            'ch.flag' => "0",
            'ch.updated_at' => date("Y-m-d H:i:s")
        ]);
        $success = [];
        if (!$query) {
            return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
        } else {
            return $this->successResponse($success, 'Message Deleted successfully');
        }
    }

    public function chatlists(Request $request)
    {

        $conn = $this->createNewConnection($request->branch_id);
        $branch_id = $request->branch_id;
        $chat_fromid = $request['chat_fromid'];
        $chat_fromname = $request['chat_fromname'];
        $chat_fromuser = $request['chat_fromuser'];
        $chat_toid = $request['chat_toid'];
        $chat_toname = $request['chat_toname'];
        $chat_touser = $request['chat_touser'];        
        $limit = $request['limit'];
        $query = $conn->table('chats as ch')->Where([
            ['ch.chat_fromid', $chat_toid],
            ['ch.chat_fromuser', $chat_touser],
            ['ch.chat_toid', $chat_fromid],
            ['ch.chat_touser', $chat_fromuser],
            ['ch.chat_status', 'Unread']
        ])->update([
            'ch.chat_status' => "Read",
            'ch.updated_at' => date("Y-m-d H:i:s")
        ]);
        if ($chat_touser == "Parent")
            $isrole1 = 5;
        elseif ($chat_touser == "Teacher")
            $isrole1 = 4;
			if (isset($request['chat_user_id'])) {
            try {
                User::where('id', $request['chat_user_id'])->update(['last_seen' => date("Y-m-d H:i:s")]);
            } catch (Exception $e) {
                // return $this->sendCommonError('No Data Found.', ['error' => $e->getMessage()]);
            }
        }
        if ($chat_touser == 'Group') {
            $success['list'] = $conn->table('chats as ch')
                ->select(
                    'ch.id',
                    'ch.chat_fromid',
                    'ch.chat_fromname',
                    'ch.chat_fromuser',
                    'ch.chat_toid',
                    'ch.chat_toname',
                    'ch.chat_touser',
                    'ch.chat_content',
                    'ch.chat_status',
                    'ch.chat_document',
                    'ch.chat_file_extension',
                    'ch.created_at',

                    DB::raw('DATE_FORMAT(ch.created_at, "%d-%M-%Y") as chatdate'),
                    DB::raw('DATE_FORMAT(ch.created_at, "%H:%i") as chattime'),
                    'ch.flag'
                )
                ->where([
                    ['ch.flag', '1'],
                    ['ch.chat_toid', $chat_toid],
                    ['ch.chat_touser', $chat_touser]
                ])->latest()->take($limit)->orderBy('ch.id', 'DESC')->get();

                $groupqry = $conn->table('groups')->select('staff','student','parent')->where('id', $chat_toid)->first();
                $staffs = explode(',' , $groupqry->staff);  
                $students = explode(',' , $groupqry->student);
                $parents = explode(',' , $groupqry->parent);   
                $groupcount=count($staffs)+count($students)+count($parents);     
                $success['logstatus'] = 'Online';
                $success['groupcount'] =$groupcount;
                $gorupli=[];
                foreach($staffs as $s1)
                {
                    $sfqry = $conn->table('staffs')->select('first_name','last_name')->where('id', $s1)->first();
                    $li['username']=$sfqry->first_name.' '.$sfqry->last_name;
                    $li['usertype']="Staff";
                    array_push($gorupli, $li);
                }
                foreach($students as $s2)
                {
                    $stqry = $conn->table('students')->select('first_name','last_name')->where('id', $s2)->first();
                    $li['username']=$stqry->first_name.' '.$stqry->last_name;
                    $li['usertype']="Student";
                    array_push($gorupli, $li);
                }
                foreach($parents as $p1)
                {
                    $prqry = $conn->table('parent')->select('first_name','last_name')->where('id', $p1)->first();
                    $li['username']=$prqry->first_name.' '.$prqry->last_name;;
                    $li['usertype']="Parent";
                    array_push($gorupli, $li);
                }
                $success['groupnamelist'] =$gorupli;
        } else {
            $success['list'] = $conn->table('chats as ch')
                ->select(
                    'ch.id',
                    'ch.chat_fromid',
                    'ch.chat_fromname',
                    'ch.chat_fromuser',
                    'ch.chat_toid',
                    'ch.chat_toname',
                    'ch.chat_touser',
                    'ch.chat_content',
                    'ch.chat_status',
                    'ch.chat_document',
                    'ch.chat_file_extension',
                    'ch.created_at',

                    DB::raw('DATE_FORMAT(ch.created_at, "%d-%M-%Y") as chatdate'),
                    DB::raw('DATE_FORMAT(ch.created_at, "%H:%i") as chattime'),
                    'ch.flag'
                )
                ->where([
                    ['ch.flag', '1'],
                    ['ch.chat_fromid', $chat_fromid],
                    ['ch.chat_fromuser', $chat_fromuser],
                    ['ch.chat_toid', $chat_toid],
                    ['ch.chat_touser', $chat_touser]
                ])->orWhere([
                    ['ch.flag', '1'],
                    ['ch.chat_fromid', $chat_toid],
                    ['ch.chat_fromuser', $chat_touser],
                    ['ch.chat_toid', $chat_fromid],
                    ['ch.chat_touser', $chat_fromuser]
                ])->latest()->take($limit)->orderBy('ch.id', 'DESC')->get();
            if($chat_touser=='Parent')
			{
				$isrole1=5;
			}
			elseif($chat_touser=='Teacher')
			{
				$isrole1=4;
			}
            $getRow = User::where('user_id', $chat_toid)

            ->where('branch_id', $request->branch_id)

            ->whereRaw("find_in_set('$isrole1',role_id)")

            ->first();
            $last_seen = date("Y-m-d H:i:s",strtotime($getRow->last_seen));
            $current_time =date("Y-m-d H:i:s");
            $success['logstatus'] = (((strtotime($current_time) - strtotime($last_seen)) / 60) > 5) ? 'Offline' : 'Online';
        }

        if (!$success) {
            return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
        } else {
            return $this->successResponse($success, 'get all chat record fetch successfully');
        }
    }
    public function pchatlists(Request $request)
    {

        //dd('123');
        // create new connection
        $conn = $this->createNewConnection($request->branch_id);
        $branch_id = $request->branch_id;
        $chat_fromid = $request['chat_fromid'];
        $chat_fromname = $request['chat_fromname'];
        $chat_fromuser = $request['chat_fromuser'];
        $chat_toid = $request['chat_toid'];
        $chat_toname = $request['chat_toname'];
        $chat_touser = $request['chat_touser'];              
        $limit = $request['limit'];
        $query = $conn->table('chats as ch')->Where([
            ['ch.chat_fromid', $chat_toid],
            ['ch.chat_fromuser', $chat_touser],
            ['ch.chat_toid', $chat_fromid],
            ['ch.chat_touser', $chat_fromuser],
            ['ch.chat_status', 'Unread']
        ])->update([
            'ch.chat_status' => "Read",
            'ch.updated_at' => date("Y-m-d H:i:s")
        ]);
        if ($chat_touser == "Parent")
            $isrole1 = 5;
        elseif ($chat_touser == "Teacher")
            $isrole1 = 4;
			if (isset($request['chat_user_id'])) {
            try {
                User::where('id', $request['chat_user_id'])->update(['last_seen' => date("Y-m-d H:i:s")]);
            } catch (Exception $e) {
                // return $this->sendCommonError('No Data Found.', ['error' => $e->getMessage()]);
            }
        }
        if ($chat_touser == 'Group') {
            $success['list'] = $conn->table('chats as ch')
                ->select(
                    'ch.id',
                    'ch.chat_fromid',
                    'ch.chat_fromname',
                    'ch.chat_fromuser',
                    'ch.chat_toid',
                    'ch.chat_toname',
                    'ch.chat_touser',
                    'ch.chat_content',
                    'ch.chat_status',
                    'ch.chat_document',
                    'ch.chat_file_extension',
                    'ch.created_at',

                    DB::raw('DATE_FORMAT(ch.created_at, "%d-%M-%Y") as chatdate'),
                    DB::raw('DATE_FORMAT(ch.created_at, "%H:%i") as chattime'),
                    'ch.flag'
                )
                ->where([
                    ['ch.flag', '1'],
                    ['ch.chat_toid', $chat_toid],
                    ['ch.chat_touser', $chat_touser]
                ])->latest()->take($limit)->orderBy('ch.id', 'DESC')->get();
                $groupqry = $conn->table('groups')->select('staff','student','parent')->where('id', $chat_toid)->first();
                $staffs = explode(',' , $groupqry->staff);  
                $students = explode(',' , $groupqry->student);
                $parents = explode(',' , $groupqry->parent);   
                $groupcount=count($staffs)+count($students)+count($parents);     
                $success['logstatus'] = 'Online';
                $success['groupcount'] =$groupcount;
                $gorupli=[];
                foreach($staffs as $s1)
                {
                    $sfqry = $conn->table('staffs')->select('first_name','last_name')->where('id', $s1)->first();
                    $li['username']=$sfqry->first_name.' '.$sfqry->last_name;
                    $li['usertype']="Staff";
                    array_push($gorupli, $li);
                }
                foreach($students as $s2)
                {
                    $stqry = $conn->table('students')->select('first_name','last_name')->where('id', $s2)->first();
                    $li['username']=$stqry->first_name.' '.$stqry->last_name;
                    $li['usertype']="Student";
                    array_push($gorupli, $li);
                }
                foreach($parents as $p1)
                {
                    $prqry = $conn->table('parent')->select('first_name','last_name')->where('id', $p1)->first();
                    $li['username']=$prqry->first_name.' '.$prqry->last_name;;
                    $li['usertype']="Parent";
                    array_push($gorupli, $li);
                }
                $success['groupnamelist'] =$gorupli;
        } else {
            $success['list'] = $conn->table('chats as ch')
                ->select(
                    'ch.id',
                    'ch.chat_fromid',
                    'ch.chat_fromname',
                    'ch.chat_fromuser',
                    'ch.chat_toid',
                    'ch.chat_toname',
                    'ch.chat_touser',
                    'ch.chat_content',
                    'ch.chat_status',
                    'ch.chat_document',
                    'ch.chat_file_extension',
                    'ch.created_at',

                    DB::raw('DATE_FORMAT(ch.created_at, "%d-%M-%Y") as chatdate'),
                    DB::raw('DATE_FORMAT(ch.created_at, "%H:%i") as chattime'),
                    'ch.flag'
                )
                ->where([
                    ['ch.flag', '1'],
                    ['ch.chat_fromid', $chat_fromid],
                    ['ch.chat_fromuser', $chat_fromuser],
                    ['ch.chat_toid', $chat_toid],
                    ['ch.chat_touser', $chat_touser]
                ])->orWhere([
                    ['ch.flag', '1'],
                    ['ch.chat_fromid', $chat_toid],
                    ['ch.chat_fromuser', $chat_touser],
                    ['ch.chat_toid', $chat_fromid],
                    ['ch.chat_touser', $chat_fromuser]
                ])->latest()->take($limit)->orderBy('ch.id', 'DESC')->get();
				if($chat_touser=='Parent')
                {
                    $isrole1=5;
                }
                elseif($chat_touser=='Teacher')
                {
                    $isrole1=4;
                }
                $getRow = User::where('user_id', $chat_toid)
    
                ->where('branch_id', $request->branch_id)
    
                ->whereRaw("find_in_set('$isrole1',role_id)")
    
                ->first();
            $last_seen = date("Y-m-d H:i:s",strtotime($getRow->last_seen));
            $current_time =date("Y-m-d H:i:s");
            $success['logstatus'] = (((strtotime($current_time) - strtotime($last_seen)) / 60) > 5) ? 'Offline' : 'Online';
        }

        //dd($success);
        if (!$success) {
            return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
        } else {
            return $this->successResponse($success, 'get all chat record fetch successfully');
        }
    }
	public function chatnotification(Request $request)
    {	
		try
        {
            $conn = $this->createNewConnection($request->branch_id);
            $branch_id = $request->branch_id;
            if($request->role_id!==null || $request->role_id !==null)
            {

                if($request->role_id=='4')
                {
                    $roll="Teacher";
                }
                elseif($request->role_id=='5')
                {
                    $roll="Parent";
                }
                /*$success[]="select COUNT('ch.*') from chats as ch where  ch.chat_toid='".$request->userID."' AND ch.chat_touser='".$roll."' AND ch.chat_status='Unread' AND ch.flag=1";*/
                $success=$conn->table('chats as ch')->select(DB::raw("COUNT(*) as count_row"))
                                    ->where('chat_toid', $request->userID)							
                                    ->where('chat_touser', $roll)
                                    ->where('chat_status', 'Unread')
                                    ->where('flag', '1')
                                    ->get(); 
                
                if($success) {
                    
                    return $this->successResponse($success, 'Get Chat Count successfully');
                } else {
                    return $this->send500Error('Sorry, Not Get Chat Count ', ['error' => 'Sorry,Not Get Chat Count ']);
                }
            }
        }
        catch (\Exception $error) {

            return  $this->commonHelper->generalReturn('403', 'error', $error, 'Error Chat Nofications');
        }
    }
    public function groupchatlists(Request $request)
    {

        //dd('123');
        // create new connection
        $conn = $this->createNewConnection($request->branch_id);
        $branch_id = $request->branch_id;
        $chat_fromid = $request['chat_fromid'];
        $chat_fromname = $request['chat_fromname'];
        $chat_fromuser = $request['chat_fromuser'];
        $chat_toid = $request['chat_toid'];
        $chat_toname = $request['chat_toname'];
        $chat_touser = $request['chat_touser'];
        $query = $conn->table('chats as ch')->Where([
            ['ch.chat_fromid', $chat_toid],
            ['ch.chat_fromuser', $chat_touser],
            ['ch.chat_toid', $chat_fromid],
            ['ch.chat_touser', $chat_fromuser],
            ['ch.chat_status', 'Unread']
        ])->update([
            'ch.chat_status' => "Read",
            'ch.updated_at' => date("Y-m-d H:i:s")
        ]);
        if ($chat_touser == 'Group') {
            $success = $conn->table('chats as ch')
                ->select(
                    'ch.id',
                    'ch.chat_fromid',
                    'ch.chat_fromname',
                    'ch.chat_fromuser',
                    'ch.chat_toid',
                    'ch.chat_toname',
                    'ch.chat_touser',
                    'ch.chat_content',
                    'ch.chat_status',
                    'ch.chat_document',
                    'ch.chat_file_extension',
                    'ch.created_at',

                    DB::raw('DATE_FORMAT(ch.created_at, "%d-%M-%Y") as chatdate'),
                    DB::raw('DATE_FORMAT(ch.created_at, "%H:%i") as chattime'),
                    'ch.flag'
                )
                ->where([
                    ['ch.chat_toid', $chat_toid],
                    ['ch.chat_touser', $chat_touser]
                ])->latest()->take(20)->orderBy('id', 'ASC')->get();
        } else {
            $success = $conn->table('chats as ch')
                ->select(
                    'ch.id',
                    'ch.chat_fromid',
                    'ch.chat_fromname',
                    'ch.chat_fromuser',
                    'ch.chat_toid',
                    'ch.chat_toname',
                    'ch.chat_touser',
                    'ch.chat_content',
                    'ch.chat_status',
                    'ch.chat_document',
                    'ch.chat_file_extension',
                    'ch.created_at',

                    DB::raw('DATE_FORMAT(ch.created_at, "%d-%M-%Y") as chatdate'),
                    DB::raw('DATE_FORMAT(ch.created_at, "%H:%i") as chattime'),
                    'ch.flag'
                )
                ->where([
                    ['ch.chat_fromid', $chat_fromid],
                    ['ch.chat_fromuser', $chat_fromuser],
                    ['ch.chat_toid', $chat_toid],
                    ['ch.chat_touser', $chat_touser]
                ])->orWhere([
                    ['ch.chat_fromid', $chat_toid],
                    ['ch.chat_fromuser', $chat_touser],
                    ['ch.chat_toid', $chat_fromid],
                    ['ch.chat_touser', $chat_fromuser]
                ])->latest()->take(20)->orderBy('id', 'ASC')->get();
        }

        if (!$success) {
            return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
        } else {
            return $this->successResponse($success, 'get all chat record fetch successfully');
        }
    }
    // get all Groups
    public function chatGetGroupList(Request $request)
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
            // get all teachers
            $staff = $request->staff_id;
            $allTeachers = [];
            $query = $conn->table('groups as gs')
                ->select(
                    'gs.id',
                    'gs.name'
                );
            if (isset($staff)) {
                $allTeachers = $query->whereRaw("find_in_set($staff,gs.staff)")->get();
            }
            return $this->successResponse($allTeachers, 'get all Group record fetch successfully');
        }
    }
    // get all Groups
    public function chatGetParentGroupList(Request $request)
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
            // get all teachers
            $parent = $request->parent_id;
            $allTeachers = [];
            $query = $conn->table('groups as gs')
                ->select(
                    'gs.id',
                    'gs.name'
                );
            if (isset($parent)) {
                $allTeachers = $query->whereRaw("find_in_set($parent,gs.parent)")->get();
            }
            return $this->successResponse($allTeachers, 'get all Group record fetch successfully');
        }
    }
}
