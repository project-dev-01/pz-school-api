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
                    // 'us.role_id',
                    // 'us.user_id',
                    'us.email',
                    'rol.role_name',
                    'stf.photo'
                )
                ->join('' . $main_db . '.users as us', 'stf.id', '=', 'us.user_id')
                ->join('' . $main_db . '.roles as rol', 'rol.id', '=', 'us.role_id')
                ->where([
                    ['us.branch_id', '=', $request->branch_id]
                ])
                ->whereIn('us.role_id', ['4'])
                ->groupBy('stf.id')
                ->limit(10)->get();
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
}
