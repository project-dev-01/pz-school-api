<?php

namespace App\Http\Controllers\Api\V1\Academic;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Exception;

class SubjectTeacherAssignController extends BaseController
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
    // add teacher assign
    public function addTeacherSubject(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'department_id' => 'required',
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'teacher_id' => 'required',
                'subject_id' => 'required',
                'type' => 'required',
                'academic_session_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);

                if ($request->type == "0") {
                    $old = $createConnection->table('subject_assigns')
                        ->where(
                            [
                                ['department_id', $request->department_id],
                                ['section_id', $request->section_id],
                                ['class_id', $request->class_id],
                                ['subject_id', $request->subject_id],
                                ['academic_session_id', $request->academic_session_id],
                                // ['teacher_id', '!=', '0'],
                                // ['teacher_id','0'],
                                ['type', $request->type]
                            ]
                        )
                        ->first();
                }

                // if ($getCount > 0) {
                //     return $this->send422Error('Teacher is already assigned to this class and section', ['error' => 'Teacher is already assigned to this class and section']);
                // } else {
                $arraySubject = array(
                    'department_id' =>  $request->department_id,
                    'class_id' =>  $request->class_id,
                    'section_id' => $request->section_id,
                    'subject_id' => $request->subject_id,
                    'teacher_id' => $request->teacher_id,
                    'academic_session_id' => $request->academic_session_id,
                    'type' => $request->type
                );
                // dd($arraySubject);
                if (isset($old->id)) {
                    // if($old->teacher_id == "0"){

                    // }
                    // // return $this->send422Error('Main teacher is already assigned to this class and section', ['error' => 'Main teacher is already assigned to this class and section']);
                    $arraySubject['updated_at'] = date("Y-m-d H:i:s");
                    $query = $createConnection->table('subject_assigns')->where('id', $old->id)->update($arraySubject);
                } else {
                    $arraySubject['created_at'] = date("Y-m-d H:i:s");
                    $query = $createConnection->table('subject_assigns')->insert($arraySubject);
                }

                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Teacher assign has been successfully saved');
                }
                // }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'addTeacherSubject');
        }
    }
    // get assign teacher subject
    public function getTeacherListSubject(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'academic_session_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                $success = $createConnection->table('subject_assigns as sa')
                    ->select(
                        'sa.id',
                        'sa.class_id',
                        DB::raw("CONCAT(st.first_name, ' ', st.last_name) as teacher_name"),
                        'sa.section_id',
                        'sa.subject_id',
                        'sa.teacher_id',
                        'sa.type',
                        's.name as section_name',
                        'sb.name as subject_name',
                        'c.name as class_name',
                        'stf_dp.name as department_name'
                    )
                    ->join('sections as s', 'sa.section_id', '=', 's.id')
                    ->join('staffs as st', 'sa.teacher_id', '=', 'st.id')
                    ->join('subjects as sb', 'sa.subject_id', '=', 'sb.id')
                    ->join('classes as c', 'sa.class_id', '=', 'c.id')
                    ->leftJoin('staff_departments as stf_dp', 'sa.department_id', '=', 'stf_dp.id')
                    ->where('sa.academic_session_id', $request->academic_session_id)
                    ->orderBy('sa.department_id', 'desc')
                    ->get();
                return $this->successResponse($success, 'Teacher record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getTeacherListSubject');
        }
    }
    // get assign teacher subject row
    public function getTeacherDetailsSubject(Request $request)
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
                return $this->successResponse($classAssign, 'Teacher assign row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getTeacherDetailsSubject');
        }
    }
    // update assign teacher subject
    public function updateTeacherSubject(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'department_id' => 'required',
                'branch_id' => 'required',
                'id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'subject_id' => 'required',
                'teacher_id' => 'required',
                'type' => 'required',
                'academic_session_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                $getCount = 0;
                if ($request->type == "0") {
                    $getCount = $createConnection->table('subject_assigns')
                        ->where(
                            [
                                ['department_id', $request->department_id],
                                ['section_id', '=', $request->section_id],
                                ['class_id', '=', $request->class_id],
                                ['subject_id', '=', $request->subject_id],
                                // ['teacher_id', $request->teacher_id],
                                ['academic_session_id', '=', $request->academic_session_id],
                                ['type', '=', $request->type],
                                ['id', '!=', $request->id]
                            ]
                        )
                        ->count();
                }
                // dd($getCount);
                // $getCount = $createConnection->table('subject_assigns')
                //     ->where(
                //         [
                //             ['section_id', $request->section_id],
                //             ['class_id', $request->class_id],
                //             ['subject_id', $request->subject_id],
                //             // ['teacher_id', $request->teacher_id],
                //             ['id', '!=', $request->id]
                //         ]
                //     )
                //     ->count();
                if ($getCount > 0) {
                    return $this->send422Error('Main subject is already assigned to this class and section', ['error' => 'Main subject is already assigned to this class and section']);
                } else {
                    $arraySubject = array(
                        'department_id' =>  $request->department_id,
                        'class_id' =>  $request->class_id,
                        'section_id' => $request->section_id,
                        'subject_id' => $request->subject_id,
                        'teacher_id' => $request->teacher_id,
                        'type' => $request->type,
                        'academic_session_id' => $request->academic_session_id,
                        'updated_at' => date("Y-m-d H:i:s")
                    );
                    // dd($arraySubject);
                    // update data
                    $query = $createConnection->table('subject_assigns')->where('id', $request->id)->update($arraySubject);
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Teacher subject details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'updateTeacherSubject');
        }
    }
    // delete assign teacher subject
    public function deleteTeacherSubject(Request $request)
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
                    return $this->successResponse($success, 'Subject Teacher been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (\Exception $error) {
            $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteTeacherSubject');
        }
    }
    // getAssignClassSubjects
    public function getAssignClassSubjects(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                $success = $createConnection->table('subject_assigns as sa')
                    ->select('sa.id', 'sa.subject_id', 'sb.name as subject_name')
                    ->join('sections as s', 'sa.section_id', '=', 's.id')
                    ->join('subjects as sb', 'sa.subject_id', '=', 'sb.id')
                    ->join('classes as c', 'sa.class_id', '=', 'c.id')
                    ->where([
                        ['sa.class_id', '=', $request->class_id],
                        ['sa.section_id', '=', $request->section_id],
                        ['sa.type', '=', '0'],
                        ['sa.academic_session_id', '=', $request->academic_session_id],
                    ])
                    ->get();
                return $this->successResponse($success, 'Get Assign class subjects fetch successfully');
            }
        } catch (\Exception $error) {
            $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getAssignClassSubjects');
        }
    }

    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
