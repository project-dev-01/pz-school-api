<?php

namespace App\Http\Controllers\Api\V1\Academic;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Exception;

class SubjectGradeAssignController extends BaseController
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
    // add class assign
    public function addClassAssign(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'department_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'subject_id' => 'required',
                'academic_session_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);

                $getCount = $createConnection->table('subject_assigns')
                    ->where(
                        [
                            ['section_id', $request->section_id],
                            ['class_id', $request->class_id],
                            ['subject_id', $request->subject_id],
                            ['academic_session_id', $request->academic_session_id],
                        ]
                    )
                    ->count();
                if ($getCount > 0) {
                    return $this->send422Error('This class and section is already assigned', ['error' => 'This class and section is already assigned']);
                } else {
                    $arraySubject = array(
                        'department_id' =>  $request->department_id,
                        'class_id' =>  $request->class_id,
                        'section_id' => $request->section_id,
                        'subject_id' => $request->subject_id,
                        'teacher_id' => 0,
                        'academic_session_id' => $request->academic_session_id,
                        'created_at' => date("Y-m-d H:i:s")
                    );
                    // insert data
                    $query = $createConnection->table('subject_assigns')->insert($arraySubject);
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'Class assign has been successfully saved');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'addClassAssign');
        }
    }
    // get class assign
    public function getClassAssignList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'academic_session_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $class_id = $request->class_id;
                $section_id = $request->section_id;
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                $success = $createConnection->table('subject_assigns as sa')
                    ->select('sa.id', 'sa.class_id', 'sa.section_id', 'sa.subject_id', 'sa.teacher_id', 's.name as section_name', 'sb.name as subject_name', 'c.name as class_name', 'stf_dp.name as department_name')
                    ->join('sections as s', 'sa.section_id', '=', 's.id')
                    // ->leftJoin('staffs as st', 'sa.teacher_id', '=', 'st.id')
                    ->join('subjects as sb', 'sa.subject_id', '=', 'sb.id')
                    ->join('classes as c', 'sa.class_id', '=', 'c.id')
                    ->leftJoin('staff_departments as stf_dp', 'sa.department_id', '=', 'stf_dp.id')
                    ->where([
                        ['sa.type', '=', '0'],
                        ['sa.academic_session_id', $request->academic_session_id]
                    ])
                    ->when($class_id, function ($q)  use ($class_id) {
                        $q->where('sa.class_id', $class_id);
                    })
                    ->when($section_id, function ($q)  use ($section_id) {
                        $q->where('sa.section_id', $section_id);
                    })
                    ->orderBy('sa.department_id', 'desc')
                    ->get();
                return $this->successResponse($success, 'Section Allocation record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getClassAssignList');
        }
    }
    // get class assign row
    public function getClassAssignDetails(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // insert data
                $classAssign = $createConnection->table('subject_assigns')->where('id', $request->id)->first();
                return $this->successResponse($classAssign, 'Class assign row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getClassAssignDetails');
        }
    }
    // update class assign
    public function updateClassAssign(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'department_id' => 'required',
                'branch_id' => 'required',
                'id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'subject_id' => 'required',
                'academic_session_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);

                $getCount = $createConnection->table('subject_assigns')
                    ->where(
                        [
                            ['section_id', $request->section_id],
                            ['class_id', $request->class_id],
                            ['subject_id', $request->subject_id],
                            ['academic_session_id', $request->academic_session_id],
                            ['id', '!=', $request->id]
                        ]
                    )
                    ->count();
                if ($getCount > 0) {
                    return $this->send422Error('This class and section is already assigned', ['error' => 'This class and section is already assigned']);
                } else {
                    $arraySubject = array(
                        'department_id' =>  $request->department_id,
                        'class_id' =>  $request->class_id,
                        'section_id' => $request->section_id,
                        'subject_id' => $request->subject_id,
                        'academic_session_id' => $request->academic_session_id,
                        'updated_at' => date("Y-m-d H:i:s")
                    );
                    // update data
                    $query = $createConnection->table('subject_assigns')->where('id', $request->id)->update($arraySubject);
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Class assign details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'updateClassAssign');
        }
    }

    // delete class assign
    public function deleteClassAssign(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $id = $request->id;
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // get data
                $query = $createConnection->table('subject_assigns')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Class assign have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'deleteClassAssign');
        }
    }
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
