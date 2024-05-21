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

class LeaveManagementController extends BaseController
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
    // addLeaveType
    public function addLeaveType(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'leave_days' => 'required',
                'gender' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($conn->table('leave_types')->where('name', '=', $request->name)->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {
                    // insert data
                    $leave_type_id = $conn->table('leave_types')->insertGetId([
                        'name' => $request->name,
                        'short_name' => $request->short_name,
                        'leave_days' => $request->leave_days,
                        'gender' => $request->gender,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                    if ($leave_type_id) {
                        $gender = $request->gender;
                        if ($gender != "All") {
                            $staff = $conn->table('staffs')->where('gender', $gender)->get();
                        } else {
                            $staff = $conn->table('staffs')->get();
                        }
                        foreach ($staff as $st) {
                            $conn->table('staff_leave_assign')->insert([
                                'staff_id' => $st->id,
                                'leave_type' => $leave_type_id,
                                'leave_days' => $request->leave_days,
                                'created_at' => date("Y-m-d H:i:s")
                            ]);
                        }
                    }
                    // cache clear start
                    $cache_leave_types = config('constants.cache_leave_types');
                    $this->clearCache($cache_leave_types, $request->branch_id);
                    // cache clear end
                    $success = [];
                    if (!$leave_type_id) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'Leave Type has been successfully saved');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addLeaveType');
        }
    }
    // getLeaveTypeList
    public function getLeaveTypeList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // get data
                $cache_time = config('constants.cache_time');
                $cache_leave_types = config('constants.cache_leave_types');

                $cacheKey = $cache_leave_types . $request->branch_id;

                // Check if the data is cached
                if (Cache::has($cacheKey)) {
                    // If cached, return cached data
                    $leaveTypeDetails = Cache::get($cacheKey);
                } else {
                    // create new connection
                    $conn = $this->createNewConnection($request->branch_id);
                    // get data
                    $leaveTypeDetails = $conn->table('leave_types')->get();
                    // Cache the fetched data for future requests
                    Cache::put($cacheKey, $leaveTypeDetails, now()->addHours($cache_time)); // Cache for 24 hours
                }
                return $this->successResponse($leaveTypeDetails, 'Leave Type record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getLeaveTypeList');
        }
    }
    // get LeaveType row details
    public function getLeaveTypeDetails(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'branch_id' => 'required',
                'token' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $id = $request->id;
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $leaveTypeDetails = $conn->table('leave_types')->where('id', $id)->first();
                return $this->successResponse($leaveTypeDetails, 'Leave Type row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getLeaveTypeDetails');
        }
    }
    // update LeaveType
    public function updateLeaveType(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'leave_days' => 'required',
                'gender' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($conn->table('leave_types')->where([['name', '=', $request->name], ['id', '!=', $id]])->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {
                    // update data
                    $query = $conn->table('leave_types')->where('id', $id)->update([
                        'name' => $request->name,
                        'short_name' => $request->short_name,
                        'leave_days' => $request->leave_days,
                        'gender' => $request->gender,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);

                    if ($id) {
                        $gender = $request->gender;
                        if ($gender != "All") {
                            $staff = $conn->table('staffs')->where('gender', $gender)->get();
                        } else {
                            $staff = $conn->table('staffs')->get();
                        }
                        foreach ($staff as $st) {
                            $updatecheck = $conn->table('staff_leave_assign')->where([['staff_id', '=', $st->id], ['leave_type', '=', $id], ['status', '=', "0"]])->first();
                            if ($updatecheck) {
                                $conn->table('staff_leave_assign')->where('id', $updatecheck->id)->update([
                                    'staff_id' => $st->id,
                                    'leave_type' => $id,
                                    'leave_days' => $request->leave_days,
                                    'created_at' => date("Y-m-d H:i:s")
                                ]);
                            } else {
                                $addcheck = $conn->table('staff_leave_assign')->where([['staff_id', '=', $st->id], ['leave_type', '=', $id], ['status', '=', "1"]])->first();
                                if (!$addcheck) {
                                    $conn->table('staff_leave_assign')->insert([
                                        'staff_id' => $st->id,
                                        'leave_type' => $id,
                                        'leave_days' => $request->leave_days,
                                        'created_at' => date("Y-m-d H:i:s")
                                    ]);
                                }
                            }
                        }
                    }
                    // cache clear start
                    $cache_leave_types = config('constants.cache_leave_types');
                    $this->clearCache($cache_leave_types, $request->branch_id);
                    // cache clear end
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Leave Type Details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateLeaveType');
        }
    }
    // delete LeaveType
    public function deleteLeaveType(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $query = $conn->table('leave_types')->where('id', $id)->delete();
                if ($query) {
                    $conn->table('staff_leave_assign')->where('leave_type', $id)->delete();
                }
                // cache clear start
                $cache_leave_types = config('constants.cache_leave_types');
                $this->clearCache($cache_leave_types, $request->branch_id);
                // cache clear end
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Leave Type have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteLeaveType');
        }
    }


    // addStaffLeaveAssign
    public function addStaffLeaveAssign(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'staff_id' => 'required',
                'leave_type' => 'required',
                'leave_days' => 'required',
                'academic_session_id' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // insert data
                $query = $conn->table('staff_leave_assign')->insert([
                    'staff_id' => $request->staff_id,
                    'leave_type' => $request->leave_type,
                    'leave_days' => $request->leave_days,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Staff Leave Assign has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addStaffLeaveAssign');
        }
    }
    // getStaffLeaveAssignList
    public function getStaffLeaveAssignList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $department = $request->department;
                $staff_id = $request->staff_id;
                $StaffLeaveAssignDetails = $conn->table('staff_leave_assign as sla')
                    ->select('sla.id', 'sla.staff_id', DB::raw("CONCAT(st.first_name, ' ', st.last_name) as staff_name"), DB::raw("GROUP_CONCAT(lt.short_name) as leave_type"))
                    ->join('staffs as st', 'sla.staff_id', '=', 'st.id')
                    ->join('leave_types as lt', 'sla.leave_type', '=', 'lt.id')

                    ->when($department, function ($query, $department) {
                        return $query->where('st.department_id', $department);
                    })
                    ->when($staff_id, function ($query, $staff_id) {
                        return $query->where('sla.staff_id', $staff_id);
                    })
                    ->where('st.is_active', '=', '0')
                    ->groupBy('sla.staff_id')
                    ->get();
                return $this->successResponse($StaffLeaveAssignDetails, 'Staff Leave Assign record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getStaffLeaveAssignList');
        }
    }
    // get StaffLeaveAssign row details
    public function getStaffLeaveAssignDetails(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'staff_id' => 'required',
                'branch_id' => 'required',
                'token' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $id = $request->id;
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $StaffLeaveAssignDetails['staff'] = $conn->table('staffs as s')->select('s.id as staff_id', DB::raw("CONCAT(s.first_name, ' ', s.last_name) as staff_name"))->where('s.id', $request->staff_id)->first();
                $StaffLeaveAssignDetails['leave'] = $conn->table('staff_leave_assign as sla')
                    ->select('sla.id', 'lt.name as leave_name', 'sla.leave_days', 'sla.leave_type as leave_type_id')
                    ->join('leave_types as lt', 'sla.leave_type', '=', 'lt.id')
                    ->where('sla.staff_id', $request->staff_id)
                    ->get();
                return $this->successResponse($StaffLeaveAssignDetails, 'Staff Leave Assign row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getStaffLeaveAssignDetails');
        }
    }
    // update StaffLeaveAssign
    public function updateStaffLeaveAssign(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // update data
                foreach ($request->leave_assign as $leave) {
                    $query = $conn->table('staff_leave_assign')->where('id', $leave['id'])->update([
                        'leave_type' => $leave['leave_type'],
                        'leave_days' => $leave['leave_days'],
                        'status' => "1",
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                }
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Staff Leave Assign Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateStaffLeaveAssign');
        }
    }
    // delete StaffLeaveAssign
    public function deleteStaffLeaveAssign(Request $request)
    {

        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // get data
                $query = $conn->table('staff_leave_assign')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Staff Leave Assign have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteStaffLeaveAssign');
        }
    }

    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
