<?php

namespace App\Http\Controllers\Api\V1\Academic;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\BaseController as BaseController;
use Exception;

class AssignGradeTeacherController extends BaseController
{
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper)
    {
        $this->commonHelper = $commonHelper;
    }

    // add TeacherAllocation
    public function addTeacherAllocation(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'department_id' => 'required',
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'teacher_id' => 'required',
                'type' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // check exist
                if ($request->type == "0") {
                    $old = $createConnection->table('teacher_allocations')
                        ->where(
                            [
                                ['department_id', $request->department_id],
                                ['section_id', $request->section_id],
                                ['class_id', $request->class_id],
                                ['academic_session_id', $request->academic_session_id],
                                ['type', '0']
                            ]
                        )
                        ->first();
                }

                // dd($arraySubject);
                if (isset($old->id)) {
                    return $this->send422Error('Main Class Teacher Already Assigned', ['error' => 'Main Class Teacher Already Assigned']);
                    // $arraySubject['updated_at'] = date("Y-m-d H:i:s");
                    // $query = $createConnection->table('subject_assigns')->where('id', $old->id)->update($arraySubject);
                } else {
                    $arrayData = array(
                        'department_id' => $request->department_id,
                        'class_id' => $request->class_id,
                        'section_id' => $request->section_id,
                        'teacher_id' => $request->teacher_id,
                        'type' => $request->type,
                        'academic_session_id' => $request->academic_session_id,
                        'created_at' => date("Y-m-d H:i:s")
                    );
                    // insert data
                    $query = $createConnection->table('teacher_allocations')->insert($arrayData);
                    $success = [];
                    // unset($arrayData['teacher_id']);

                    // $createConnection->table('subject_assigns')->where($arrayData)->update([
                    //     'teacher_id' => $request->teacher_id,
                    //     'updated_at' => date("Y-m-d H:i:s")
                    // ]);

                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'Teacher Allocation has been successfully saved');
                    }
                }
                // if ($createConnection->table('teacher_allocations')->where([['section_id', $request->section_id], ['class_id', $request->class_id]])->count() > 0) {
                //     return $this->send422Error('Class Teacher Already Assigned', ['error' => 'Class Teacher Already Assigned']);
                // } else {


                // }


            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'addTeacherAllocation');
        }
    }

    // get TeacherAllocation
    public function getTeacherAllocationList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // insert data
                $success = $createConnection->table('teacher_allocations as ta')
                    ->select(
                        'ta.id',
                        'stf_dp.name as department_name',
                        'ta.class_id',
                        'ta.section_id',
                        'ta.teacher_id',
                        'ta.type',
                        's.name as section_name',
                        'c.name as class_name',
                        DB::raw("CONCAT(st.last_name, ' ', st.first_name) as teacher_name")
                    )
                    ->join('sections as s', 'ta.section_id', '=', 's.id')
                    ->join('staffs as st', 'ta.teacher_id', '=', 'st.id')
                    ->join('classes as c', 'ta.class_id', '=', 'c.id')
                    ->leftJoin('staff_departments as stf_dp', 'ta.department_id', '=', 'stf_dp.id')
                    ->where('ta.academic_session_id', $request->academic_session_id)
                    ->orderBy('ta.department_id', 'desc')
                    ->get();
                return $this->successResponse($success, 'Teacher Allocation record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getTeacherAllocationList');
        }
    }
    // get TeacherAllocation row details
    public function getTeacherAllocationDetails(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // insert data
                $sectionDetails = $createConnection->table('teacher_allocations')->where('id', $request->id)->first();
                return $this->successResponse($sectionDetails, 'Teacher Allocation row fetch successfully');

                // $teacher_allocation__id = $request->teacher_allocation__id;
                // $teacherAllocationDetails = TeacherAllocation::find($teacher_allocation__id);
                // return $this->successResponse($teacherAllocationDetails, 'Teacher Allocation row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getTeacherAllocationDetails');
        }
    }
    // update TeacherAllocation
    public function updateTeacherAllocation(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'department_id' => 'required',
                'branch_id' => 'required',
                'id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'teacher_id' => 'required',
                'type' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $id = $request->id;
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // check exist name
                // if ($createConnection->table('teacher_allocations')->where([['section_id', $request->section_id], ['class_id', $request->class_id], ['id', '!=', $id]])->count() > 0) {
                //     return $this->send422Error('Class Teacher Already Assigned', ['error' => 'Class Teacher Already Assigned']);
                // } else {
                // }

                $getCount = 0;
                if ($request->type == "0") {
                    $getCount = $createConnection->table('teacher_allocations')
                        ->where(
                            [
                                ['department_id', $request->department_id],
                                ['section_id', $request->section_id],
                                ['class_id', $request->class_id],
                                ['type', $request->type],
                                ['academic_session_id', $request->academic_session_id],
                                ['id', '!=', $request->id]
                            ]
                        )
                        ->count();
                }
                if ($getCount > 0) {
                    return $this->send422Error('Main Class Teacher Already Assigned', ['error' => 'Main Class Teacher Already Assigned']);
                } else {
                    $arrayData = array(
                        'department_id' => $request->department_id,
                        'class_id' => $request->class_id,
                        'section_id' => $request->section_id,
                        'teacher_id' => $request->teacher_id,
                        'type' => $request->type,
                        'academic_session_id' => $request->academic_session_id,
                        'updated_at' => date("Y-m-d H:i:s")
                    );
                    // dd($arrayData);
                    // update data
                    $query = $createConnection->table('teacher_allocations')->where('id', $id)->update($arrayData);
                    // unset($arrayData['teacher_id']);

                    // $createConnection->table('subject_assigns')->where($arrayData)->update([
                    //     'teacher_id' => $request->teacher_id,
                    //     'updated_at' => date("Y-m-d H:i:s")
                    // ]);
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Teacher Allocation Details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'updateTeacherAllocation');
        }
    }
    // delete TeacherAllocation
    public function deleteTeacherAllocation(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $id = $request->id;
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // get data
                $query = $createConnection->table('teacher_allocations')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Teacher Allocation have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'deleteTeacherAllocation');
        }
    }
}
