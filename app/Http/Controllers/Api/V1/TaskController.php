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
use Carbon;
class TaskController extends BaseController
{
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper)
    {
        $this->commonHelper = $commonHelper;
    }
    /**
     * @Chandru @since May 18,2024
     * @desc List section
     */
    // addToDoList
    public function addToDoList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'title' => 'required',

                'due_date' => 'required',
                'assign_to' => 'required',
                'priority' => 'required',
                // 'check_list' => 'required',
                'task_description' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);
                $fileDetails = $request->file;

                $fileNames = [];
                if ($fileDetails) {
                    foreach ($fileDetails as $key => $value) {
                        $now = now();
                        $name = strtotime($now);
                        $extension = $value['extension'];
                        $fileName = $name . uniqid() . "." . $extension;
                        $path = '/public/' . $request->branch_id . '/images/todolist/';
                        $base64 = base64_decode($value['base64']);
                        File::ensureDirectoryExists(base_path() . $path);
                        $file = base_path() . $path . $fileName;
                        $upload = file_put_contents($file, $base64);
                        array_push($fileNames, $fileName);
                    }
                }
                $insertArr = [
                    'title' => $request->title,
                    'due_date' => $request->due_date,
                    'assign_to' => $request->assign_to,
                    'priority' => $request->priority,
                    'check_list' => $request->check_list,
                    'task_description' => $request->task_description,
                    'file' => implode(",", $fileNames),
                    'mark_as_complete' => "0",
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $query = $Connection->table('to_do_lists')->insert($insertArr);
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse([], 'To List has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addToDoList');
        }
    }
    // updateToDoList
    public function updateToDoList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'branch_id' => 'required',
                'title' => 'required',
                'due_date' => 'required',
                'assign_to' => 'required',
                'priority' => 'required',
                // 'check_list' => 'required',
                'task_description' => 'required'
            ]);
            // $olf_file = $request->old_file;
            // $old_files = explode(",", $request->old_file);
            // $old_files = $Connection->table('to_do_lists')->where('id', $request->id)->whereRaw('FIND_IN_SET(?,to_do_lists.file)', [$request->old_file])->get();
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);
                $fileDetails = $request->file;

                $fileNames = [];
                $old_file = explode(",", $request->old_file);
                if ($old_file) {
                    $old_updated_file = explode(",", $request->old_updated_file);
                    $delete_files = array_diff($old_file, $old_updated_file);
                    foreach ($delete_files as $delete) {

                        $file = base_path() . '/public/' . $request->branch_id . '/images/todolist/' . $delete;
                        if (file_exists($file)) {
                            unlink($file);
                        }
                    }

                    foreach ($old_updated_file as $file_name) {
                        array_push($fileNames, $file_name);
                    }
                }

                if ($fileDetails) {
                    foreach ($fileDetails as $key => $value) {
                        $now = now();
                        $name = strtotime($now);
                        $extension = $value['extension'];
                        $fileName = $name . uniqid() . "." . $extension;
                        $path = '/public/' . $request->branch_id . '/images/todolist/';
                        $base64 = base64_decode($value['base64']);
                        File::ensureDirectoryExists(base_path() . $path);
                        $file = base_path() . $path . $fileName;
                        $upload = file_put_contents($file, $base64);
                        array_push($fileNames, $fileName);
                    }
                }
                $insertArr = [
                    'title' => $request->title,
                    'due_date' => $request->due_date,
                    'assign_to' => $request->assign_to,
                    'priority' => $request->priority,
                    'check_list' => $request->check_list,
                    'task_description' => $request->task_description,
                    'file' => implode(",", $fileNames),
                    'mark_as_complete' => "0",
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $query = $Connection->table('to_do_lists')->where('id', $request->id)->update($insertArr);
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse([], 'To List has been successfully Updated');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateToDoList');
        }
    }
    // get ToDoList
    public function getToDoList(Request $request)
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
                // get data
                $toDoList = $Connection->table('to_do_lists')->get();
                return $this->successResponse($toDoList, 'To do lists record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getToDoList');
        }
    }
    // get to do row details
    public function getToDoListRow(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'token' => 'required',
                'branch_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // insert data
                $toDoRow = $createConnection->table('to_do_lists')->where('id', $request->id)->first();
                return $this->successResponse($toDoRow, 'to Do Lists row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getToDoListRow');
        }
    }
    // deleteToDoList
    public function deleteToDoList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'id' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                $getRow = $createConnection->table('to_do_lists')->where('id', $request->id)->first();
                if (isset($getRow->file)) {
                    $arrayVal = explode(',', $getRow->file);
                    foreach ($arrayVal as $key => $value) {
                        if ($value) {
                            $file = base_path() . '/public/' . $request->branch_id . '/images/todolist/' . $value;
                            if (file_exists($file)) {
                                unlink($file);
                            }
                        }
                    }
                }
                // get data
                $query = $createConnection->table('to_do_lists')->where('id', $request->id)->delete();
                if ($query) {
                    return $this->successResponse([], 'To Do Lists have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteToDoList');
        }
    }
    // getToDoListDashboard
    public function getToDoListDashboard(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'user_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $now = Carbon::now()->format('Y-m-d');

                // $dateNow = Carbon::now();
                // $tmr = $dateNow->addDays(1);
                // $dateStart = $tmr->format('Y-m-d');

                // $daysToAdd = 7;
                // $end = $tmr->addDays($daysToAdd);
                // $dateEnd = $end->format('Y-m-d');
                // print_r($dateStart);
                // print_r($dateEnd);
                // exit;
                // dd($now);
                $userID = $request->user_id;
                $createConnection = $this->createNewConnection($request->branch_id);
                // $today = $createConnection->table('to_do_lists as tdl')
                //     ->select(
                //         'tdl.id',
                //         'tdl.title',
                //         'tdl.due_date',
                //         'tdl.priority',
                //         'tdl.priority',
                //         'tdl.mark_as_complete',
                //         'rtd.user_id',
                //         DB::raw('count(tdlc.to_do_list_id) as total_comments')
                //     )
                //     ->leftJoin('read_to_do_list as rtd', function ($join) use ($userID) {
                //         $join->on('rtd.to_do_list_id', '=', 'tdl.id')
                //             ->on('rtd.user_id', '=', DB::raw("'$userID'"));
                //     })
                //     ->leftjoin('to_do_list_comments as tdlc', 'tdl.id', '=', 'tdlc.to_do_list_id')
                //     ->orderBy('tdl.due_date', 'desc')
                //     ->where(DB::raw("(DATE_FORMAT(tdl.due_date,'%Y-%m-%d'))"), $now)
                //     ->groupBy('tdl.id')
                //     ->get();

                // $upcoming = $createConnection->table('to_do_lists as tdl')
                //     ->select(
                //         'tdl.id',
                //         'tdl.title',
                //         'tdl.due_date',
                //         'tdl.priority',
                //         'tdl.mark_as_complete',
                //         'rtd.user_id',
                //         DB::raw('count(tdlc.to_do_list_id) as total_comments')
                //     )
                //     ->leftJoin('read_to_do_list as rtd', function ($join) use ($userID) {
                //         $join->on('rtd.to_do_list_id', '=', 'tdl.id')
                //             ->on('rtd.user_id', '=', DB::raw("'$userID'"));
                //     })
                //     ->leftjoin('to_do_list_comments as tdlc', 'tdl.id', '=', 'tdlc.to_do_list_id')
                //     ->orderBy('tdl.due_date', 'desc')
                //     ->where(DB::raw("(DATE_FORMAT(tdl.due_date,'%Y-%m-%d'))"), '>', $now)
                //     ->groupBy('tdl.id')
                //     ->get();
                $query = $createConnection->table('to_do_lists as tdl')
                    ->select(
                        'tdl.id',
                        'tdl.title',
                        'tdl.due_date',
                        'tdl.priority',
                        'tdl.mark_as_complete',
                        'rtd.user_id',
                        DB::raw('count(tdlc.to_do_list_id) as total_comments')
                    )
                    ->leftJoin('read_to_do_list as rtd', function ($join) use ($userID) {
                        $join->on('rtd.to_do_list_id', '=', 'tdl.id')
                            ->on('rtd.user_id', '=', DB::raw("'$userID'"));
                    })
                    ->leftjoin('to_do_list_comments as tdlc', 'tdl.id', '=', 'tdlc.to_do_list_id')
                    ->orderBy('tdl.due_date', 'desc');
                // old
                $old_query = clone $query;
                $old_query->where(DB::raw("(DATE_FORMAT(tdl.due_date,'%Y-%m-%d'))"), '<', $now);
                $old = $old_query->groupBy('tdl.id')->get();
                // today
                $today_query = clone $query;
                $today_query->where(DB::raw("(DATE_FORMAT(tdl.due_date,'%Y-%m-%d'))"), $now);
                $today = $today_query->groupBy('tdl.id')->get();
                // upcoming
                $upcoming_query = clone $query;
                $upcoming_query->where(DB::raw("(DATE_FORMAT(tdl.due_date,'%Y-%m-%d'))"), '>', $now);
                $upcoming = $upcoming_query->groupBy('tdl.id')->get();

                $data = [
                    'old' => $old,
                    'today' => $today,
                    'upcoming' => $upcoming
                ];
                return $this->successResponse($data, 'To Do List fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getToDoListDashboard');
        }
    }
    // readUpdateTodo
    public function readUpdateTodo(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'user_id' => 'required',
                'to_do_list_id' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $main_db = config('constants.main_db');
                // // create new connection
                $Connection = $this->createNewConnection($request->branch_id);
                $checkExist = $Connection->table('read_to_do_list')->where([
                    ['to_do_list_id', '=', $request->to_do_list_id],
                    ['user_id', '=', $request->user_id]
                ])->first();

                if (empty($checkExist)) {
                    // echo "update";
                    $query = $Connection->table('read_to_do_list')->insert([
                        'to_do_list_id' => $request->to_do_list_id,
                        'user_id' => $request->user_id,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                } else {
                    // update data
                    $query = $Connection->table('read_to_do_list')->where('id', $checkExist->id)->update([
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                }
                $userID = $request->user_id;
                $rowData = $Connection->table('to_do_lists as tdl')
                    ->select(
                        'tdl.id',
                        'tdl.title',
                        'tdl.due_date',
                        'tdl.priority',
                        'tdl.assign_to',
                        'tdl.check_list',
                        'tdl.task_description',
                        'tdl.file',
                        'rtd.user_id'
                    )
                    ->leftJoin('read_to_do_list as rtd', function ($join) use ($userID) {
                        $join->on('rtd.to_do_list_id', '=', 'tdl.id')
                            ->on('rtd.user_id', '=', DB::raw("'$userID'"));
                    })
                    ->where('tdl.id', $request->to_do_list_id)
                    ->first();
                // get comments details
                $commentsData = $Connection->table('to_do_list_comments as tdlc')
                    ->select(
                        'tdlc.id',
                        'tdlc.comment',
                        'tdlc.created_at',
                        'us.name'
                    )
                    // change superadmin db here
                    ->leftJoin('' . $main_db . '.users as us', 'us.id', '=', 'tdlc.user_id')
                    // ->leftJoin('paxsuzen_pz-school.users as us', 'us.id', '=', 'tdlc.user_id')
                    ->where([
                        ['tdlc.to_do_list_id', '=', $request->to_do_list_id]
                    ])
                    ->get();
                $data = [
                    "comments" => $commentsData,
                    "to_do_list" => $rowData,
                ];
                return $this->successResponse($data, 'Read to Do have Been updated');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in readUpdateTodo');
        }
    }
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}