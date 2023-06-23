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

class ChatController extends BaseController
{
    // get all teacher
    public function chatGetTeacherList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
        ]);
		$toid=$request->to_id;
		$to_role=$request->role;

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
					DB::raw("(select COUNT('ch.*') from chats as ch where ch.chat_fromid=stf.id AND ch.chat_toid='".$request->to_id."' AND ch.chat_touser='".$request->role."' AND ch.chat_fromuser='Teacher' AND ch.chat_status='Unread' AND flag=1 ) as msgcount"), 
                    'us.role_id',
                    // 'rol.role_name',
                    // 'us.user_id',
                    'us.email',
                    DB::raw("GROUP_CONCAT(rol.role_name) as role" ),
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
                ->groupBy('stf.id')
              
			   // ->limit(10)
				->get();
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
            // get all teachers
            $allTeachers = $conn->table('parent as prnt')
                ->select(
                    'prnt.id',
                    DB::raw("CONCAT(prnt.first_name, ' ', prnt.last_name) as name"),
					DB::raw("(select COUNT('ch.*') from chats as ch where ch.chat_fromid=prnt.id AND ch.chat_toid='".$request->to_id."' AND ch.chat_touser='".$request->role."' AND ch.chat_fromuser='Parent' AND ch.chat_status='Unread' AND flag=1) as msgcount"), 
                    'prnt.photo'
                )->limit(10)->get();
            return $this->successResponse($allTeachers, 'get all teacher record fetch successfully');
        }
    }
    // get teacher assign parents
    public function chatGetTeacherAssignParentList(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'token' => 'required',
            'teacher_id' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            // get teacher allocations parents
            $allTeachers = $conn->table('teacher_allocations as ta')
                ->select(
                    // 'ta.id',
                    'en.student_id',
                    'p.id as parent_id',
                    DB::raw("CONCAT(p.first_name, ' ', p.last_name) as name")
                )
                ->join('enrolls as en', function ($join) {
                    $join->on('en.class_id', '=', 'ta.class_id')
                        ->on('en.section_id', '=', 'ta.section_id')
                        ->on('en.active_status', '=', DB::raw("'0'"));
                    // $join->on('st.mother_id', '=', 'p.id');
                    // $join->orOn('st.guardian_id', '=', 'p.id');
                })
                ->join('parent as p', function ($join) {
                    $join->on('p.ref_guardian_id', '=', 'en.student_id');
                    $join->orOn('p.ref_mother_id', '=', 'en.student_id');
                    $join->orOn('p.ref_father_id', '=', 'en.student_id');
                })
                ->where([
                    ['ta.teacher_id', '=', $request->teacher_id]
                ])
                ->groupBy("en.student_id")
                ->get();
            dd($allTeachers);
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
            
                // insert data
                if (isset($request->chat_document)) {
                    $now = now();
                    $name = strtotime($now);
                    $extension = $request->chat_file_extension;
                    $fileName = $name . "." . $extension;

                    $base64 = base64_decode($request->chat_document);
                    $file = base_path() . '/public/admin-documents/chats/' . $fileName;
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
					['ch.id',$chat_id]
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

			//dd('123');
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $branch_id = $request->branch_id;
			$chat_fromid = $request['chat_fromid'];
			$chat_fromname= $request['chat_fromname'];
			$chat_fromuser= $request['chat_fromuser'];
			$chat_toid= $request['chat_toid'];
			$chat_toname= $request['chat_toname'];
			$chat_touser= $request['chat_touser'];
			$query = $conn->table('chats as ch')->Where([
					['ch.chat_fromid',$chat_toid],
					['ch.chat_fromuser',$chat_touser],
					['ch.chat_toid',$chat_fromid],
					['ch.chat_touser',$chat_fromuser],
					['ch.chat_status','Unread']
				])->update([
                    'ch.chat_status' => "Read",
                    'ch.updated_at' => date("Y-m-d H:i:s")
                ]);
              if($chat_touser=='Group')
				{
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
					['ch.flag','1'],
					['ch.chat_toid',$chat_toid],
					['ch.chat_touser',$chat_touser]
				])->latest()->take(20)->orderBy('id', 'ASC')->get(); 
				}
				else
				{
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
					['ch.flag','1'],
					['ch.chat_fromid',$chat_fromid],
					['ch.chat_fromuser',$chat_fromuser],
					['ch.chat_toid',$chat_toid],
					['ch.chat_touser',$chat_touser]
				])->orWhere([
					['ch.flag','1'],
					['ch.chat_fromid',$chat_toid],
					['ch.chat_fromuser',$chat_touser],
					['ch.chat_toid',$chat_fromid],
					['ch.chat_touser',$chat_fromuser]
				])->latest()->take(20)->orderBy('id', 'ASC')->get(); 
               
           
				}
           
           //dd($success);
            if (!$success) {
                return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
            } else {
                return $this->successResponse($success, 'get all chat record fetch successfully');
            }
            
        
    }
	public function groupchatlists(Request $request)
    {

			//dd('123');
            // create new connection
            $conn = $this->createNewConnection($request->branch_id);
            $branch_id = $request->branch_id;
			$chat_fromid = $request['chat_fromid'];
			$chat_fromname= $request['chat_fromname'];
			$chat_fromuser= $request['chat_fromuser'];
			$chat_toid= $request['chat_toid'];
			$chat_toname= $request['chat_toname'];
			$chat_touser= $request['chat_touser'];
			$query = $conn->table('chats as ch')->Where([
					['ch.chat_fromid',$chat_toid],
					['ch.chat_fromuser',$chat_touser],
					['ch.chat_toid',$chat_fromid],
					['ch.chat_touser',$chat_fromuser],
					['ch.chat_status','Unread']
				])->update([
                    'ch.chat_status' => "Read",
                    'ch.updated_at' => date("Y-m-d H:i:s")
                ]);
				if($chat_touser=='Group')
				{
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
					['ch.chat_toid',$chat_toid],
					['ch.chat_touser',$chat_touser]
				])->latest()->take(20)->orderBy('id', 'ASC')->get(); 
				}
				else
				{
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
					['ch.chat_fromid',$chat_fromid],
					['ch.chat_fromuser',$chat_fromuser],
					['ch.chat_toid',$chat_toid],
					['ch.chat_touser',$chat_touser]
				])->orWhere([
					['ch.chat_fromid',$chat_toid],
					['ch.chat_fromuser',$chat_touser],
					['ch.chat_toid',$chat_fromid],
					['ch.chat_touser',$chat_fromuser]
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
