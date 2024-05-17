<?php

namespace App\Http\Controllers\Api\V1;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

// base controller add
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\BaseController as BaseController;
use Exception;

use App\Helpers\CommonHelper;
//created by
class AcademicController extends BaseController
{
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper)
    {
        $this->commonHelper = $commonHelper;
    }

    // add class
    public function addClass(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'department_id' => 'required',
                'short_name' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($createConnection->table('classes')->where([['name', '=', $request->name], ['department_id', '=', $request->department_id]])->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {
                    // insert data
                    $query = $createConnection->table('classes')->insert([
                        'department_id' => $request->department_id,
                        'name' => $request->name,
                        'short_name' => $request->short_name,
                        'name_numeric' => $request->name_numeric,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                    //cache clear Start
                    $cache_classes = config('constants.cache_classes');
                    $this->clearCache($cache_classes, $request->branch_id);
                    //cache clear End
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'New Grade has been successfully saved');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'addClass');
        }
    }

    // get classes
    public function getClassList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // get data
                $cache_time = config('constants.cache_time');
                $cache_classes = config('constants.cache_classes');
                //dd($cache_academic_years);
                //$Department = $Connection->table('academic_year')->get();
                $cacheKey = $cache_classes . $request->branch_id;
                //$this->clearCache($cache_classes,$request->branch_id);
                // Check if the data is cached
                if (Cache::has($cacheKey)) {
                    // If cached, return cached data
                    $class = Cache::get($cacheKey);
                } else {
                    // create new connection
                    $classConn = $this->createNewConnection($request->branch_id);
                    // get data
                    $class = $classConn->table('classes as cl')
                        ->select('cl.id', 'cl.name', 'cl.short_name', 'cl.name_numeric', 'cl.department_id', 'stf_dp.name as department_name')
                        ->leftJoin('staff_departments as stf_dp', 'cl.department_id', '=', 'stf_dp.id')
                        ->orderBy('cl.department_id', 'desc')
                        ->get();
                    Cache::put($cacheKey, $class, now()->addHours($cache_time)); // Cache for 24 hours
                }
                return $this->successResponse($class, 'Grade record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getClassList');
        }
    }
    // get class row details
    public function getClassDetails(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'class_id' => 'required',
                'token' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // insert data
                $sectionDetails = $createConnection->table('classes')->where('id', $request->class_id)->first();
                return $this->successResponse($sectionDetails, 'Grade row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getClassDetails');
        }
    }
    // update class
    public function updateClassDetails(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'department_id' => 'required',
                'name' => 'required',
                'short_name' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $class_id = $request->class_id;
                // create new connection
                $staffConn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($staffConn->table('classes')->where([['name', '=', $request->name], ['department_id', '=', $request->department_id], ['id', '!=', $class_id]])->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {
                    // update data
                    $query = $staffConn->table('classes')->where('id', $class_id)->update([
                        'department_id' => $request->department_id,
                        'name' => $request->name,
                        'short_name' => $request->short_name,
                        'name_numeric' => $request->name_numeric,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    //cache clear Start
                    $cache_classes = config('constants.cache_classes');
                    $this->clearCache($cache_classes, $request->branch_id);
                    //cache clear End
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Grade Details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'updateClassDetails');
        }
    }

    // delete class
    public function deleteClass(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'class_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $class_id = $request->class_id;
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // get data
                $query = $createConnection->table('classes')->where('id', $class_id)->delete();
                //cache clear Start
                $cache_classes = config('constants.cache_classes');
                $this->clearCache($cache_classes, $request->branch_id);
                //cache clear End
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Grade have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'deleteClass');
        }
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

    // add subjects
    public function addSubjects(Request $request)
    {
        try {

            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'branch_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($createConnection->table('subjects')->where([['name', $request->name]])->count() > 0) {
                    return $this->send422Error('Already Allocated Subjects', ['error' => 'Already Allocated Subjects']);
                } else {
                    // insert data
                    $query = $createConnection->table('subjects')->insert([
                        'name' => $request->name,
                        'subject_code' => $request->subject_code,
                        'subject_type' => $request->subject_type,
                        'short_name' => $request->short_name,
                        'subject_color_calendor' => $request->subject_color,
                        'subject_author' => $request->subject_author,
                        'subject_type_2' => $request->subject_type_2,
                        'pdf_report' => isset($request->pdf_report) ? $request->pdf_report : 0,
                        'times_per_week' => isset($request->times_per_week) ? $request->times_per_week : null,
                        'exam_exclude' => $request->exam_exclude,
                        'order_code' => isset($request->order_code) ? $request->order_code : null,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                    // cache clear start
                    $cache_subjects = config('constants.cache_subjects');
                    $this->clearCache($cache_subjects, $request->branch_id);
                    // cache clear end
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'Subjects has been successfully saved');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'addSubjects');
        }
    }
    // get all subjects data
    public function getSubjectsList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // get data
                $cache_time = config('constants.cache_time');
                $cache_subjects = config('constants.cache_subjects');
                $cacheKey = $cache_subjects . $request->branch_id;

                // Check if the data is cached
                if (Cache::has($cacheKey)) {
                    // If cached, return cached data
                    $subjectDetails = Cache::get($cacheKey);
                } else {
                    // create new connection
                    $secConn = $this->createNewConnection($request->branch_id);
                    // get data
                    $subjectDetails = $secConn->table('subjects')->orderBy(DB::raw('ISNULL(order_code), order_code'), 'ASC')->get();
                    // Cache the fetched data for future requests
                    Cache::put($cacheKey, $subjectDetails, now()->addHours($cache_time)); // Cache for 24 hours
                }
                return $this->successResponse($subjectDetails, 'Subject record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getSubjectsList');
        }
    }
    // get row subjects
    public function getSubjectsDetails(Request $request)
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
                $sectionDetails = $createConnection->table('subjects')->where('id', $request->id)->first();
                return $this->successResponse($sectionDetails, 'Subject row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getSubjectsDetails');
        }
    }
    // update subjects
    public function updateSubjects(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($createConnection->table('subjects')->where([['name', $request->name], ['id', '!=', $request->id]])->count() > 0) {
                    return $this->send422Error('Already Allocated Subjects', ['error' => 'Already Allocated Subjects']);
                } else {
                    // update data
                    $query = $createConnection->table('subjects')->where('id', $request->id)->update([
                        'name' => $request->name,
                        'subject_code' => $request->subject_code,
                        'subject_type' => $request->subject_type,
                        'short_name' => $request->short_name,
                        'subject_color_calendor' => $request->subject_color,
                        'subject_author' => $request->subject_author,
                        'subject_type_2' => $request->subject_type_2,
                        'pdf_report' => isset($request->pdf_report) ? $request->pdf_report : 0,
                        'times_per_week' => isset($request->times_per_week) ? $request->times_per_week : null,
                        'exam_exclude' => $request->exam_exclude,
                        'order_code' => isset($request->order_code) ? $request->order_code : null,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    // cache clear start
                    $cache_subjects = config('constants.cache_subjects');
                    $this->clearCache($cache_subjects, $request->branch_id);
                    // cache clear end
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Subject Details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'updateSubjects');
        }
    }
    // delete subjects
    public function deleteSubjects(Request $request)
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
                $query = $createConnection->table('subjects')->where('id', $id)->delete();

                // cache clear start
                $cache_subjects = config('constants.cache_subjects');
                $this->clearCache($cache_subjects, $request->branch_id);
                // cache clear end
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Subjects have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'deleteSubjects');
        }
    }



    // Timetable Subject
    public function timetableSubject(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'semester_id' => 'required',
                'session_id' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // return $request;
                // create new connection
                $classConn = $this->createNewConnection($request->branch_id);

                $Timetable = $classConn->table('timetable_class')->select(
                    'timetable_class.*',
                    DB::raw('CONCAT(staffs.first_name, " ", staffs.last_name) as teacher_name'),
                    'subjects.name as subject_name'
                )
                    ->leftJoin('staffs', 'timetable_class.teacher_id', '=', 'staffs.id')
                    ->leftJoin('subjects', 'timetable_class.subject_id', '=', 'subjects.id')
                    ->where([
                        ['timetable_class.day', $request->day],
                        ['timetable_class.class_id', $request->class_id],
                        ['timetable_class.semester_id', $request->semester_id],
                        ['timetable_class.session_id', $request->session_id],
                        ['timetable_class.section_id', $request->section_id],
                        ['timetable_class.academic_session_id', $request->academic_session_id]
                    ])
                    ->orderBy('time_start', 'asc')
                    ->orderBy('time_end', 'asc')
                    ->get()->toArray();
                $output['timetable'] = $Timetable;
                $output['teacher'] = $classConn->table('subject_assigns as sa')->select(
                    's.id',
                    DB::raw('CONCAT(s.last_name, " ", s.first_name) as name')
                )
                    ->join('staffs as s', 'sa.teacher_id', '=', 's.id')
                    ->where('sa.class_id', $request->class_id)
                    ->where('sa.section_id', $request->section_id)
                    ->where('sa.academic_session_id', $request->academic_session_id)
                    // type zero mean main
                    ->where('sa.type', '=', '0')
                    ->groupBy('sa.teacher_id')
                    ->get();
                $output['subject'] = $classConn->table('subject_assigns as sa')->select('s.id', 's.name')
                    ->join('subjects as s', 'sa.subject_id', '=', 's.id')
                    ->where('sa.class_id', $request->class_id)
                    ->where('sa.section_id', $request->section_id)
                    ->where('sa.academic_session_id', $request->academic_session_id)
                    ->where('sa.type', '=', '0')
                    // ->where('sa.exam_exclude', '=', '0')
                    // get teacher
                    // ->where('sa.teacher_id', '!=', '0')
                    ->get();
                $output['exam_hall'] = $classConn->table('exam_hall')->get();

                return $this->successResponse($output, 'Teacher and Subject record fetch successfully');
            }
        } catch (\Exception $error) {
            $this->commonHelper->generalReturn('403', 'error', $error, 'Error in timetableSubject');
        }
    }

    // Timetable Subject Bulk
    public function timetableSubjectBulk(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // return $request;
                // create new connection
                $classConn = $this->createNewConnection($request->branch_id);
                $class_id = $request->class_id;

                $Timetable = $classConn->table('timetable_bulk')->select(
                    'timetable_bulk.*',
                    DB::raw('CONCAT(staffs.first_name, " ", staffs.last_name) as teacher_name')
                )
                    ->leftJoin('staffs', 'timetable_bulk.teacher_id', '=', 'staffs.id')
                    ->where([
                        ['timetable_bulk.day', $request->day],
                        ['timetable_bulk.class_id', $request->class_id],
                        ['timetable_bulk.semester_id', $request->semester_id],
                        ['timetable_bulk.session_id', $request->session_id],
                        ['timetable_bulk.academic_session_id', $request->academic_session_id],
                    ])
                    ->orderBy('time_start', 'asc')
                    ->orderBy('time_end', 'asc')
                    ->get()->toArray();
                $output['timetable'] = $Timetable;
                $output['teacher'] = $classConn->table('subject_assigns as sa')->select(
                    's.id',
                    DB::raw('CONCAT(s.last_name, " ", s.first_name) as name')
                )
                    ->join('staffs as s', 'sa.teacher_id', '=', 's.id')
                    ->when($class_id != "All", function ($q)  use ($class_id) {
                        $q->where('sa.class_id', $class_id);
                    })
                    // type zero mean main
                    ->where('sa.type', '=', '0')
                    ->where('sa.academic_session_id', $request->academic_session_id)
                    ->groupBy('sa.teacher_id')
                    ->get();
                $output['exam_hall'] = $classConn->table('exam_hall')->get();

                return $this->successResponse($output, 'Teacher and Subject record fetch successfully');
            }
        } catch (\Exception $error) {
            $this->commonHelper->generalReturn('403', 'error', $error, 'Error in timetableSubjectBulk');
        }
    }

    // add Timetable
    public function addTimetable(Request $request)
    {
        try {
            // dd($request);
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'day' => 'required',
                'timetable' => 'required',
                'academic_session_id' => 'required'
            ]);


            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $staffConn = $this->createNewConnection($request->branch_id);

                // calendor data populate
                $getObjRow = $staffConn->table('semester as s')
                    ->select('start_date', 'end_date')
                    ->where('id', $request->semester_id)
                    ->get();
                if (count($getObjRow) == 0) {
                    // Use the $yearData as needed
                    $getObjRow = $staffConn->table('semester as sm')
                        ->select('sm.start_date', 'sm.end_date')
                        ->where('sm.academic_session_id', '=', $request->academic_session_id)
                        ->get();
                }
                $timetable = $request->timetable;
                $oldest = $staffConn->table('timetable_class')->where([['class_id', $request->class_id], ['section_id', $request->section_id], ['semester_id', $request->semester_id], ['session_id', $request->session_id], ['day', $request->day], ['academic_session_id', $request->academic_session_id]])->WhereNull('bulk_id')->get()->toArray();

                // return $oldest;
                $diff = array_diff(array_column($oldest, 'id'), array_column($timetable, 'id'));

                if (isset($diff)) {
                    foreach ($diff as $del) {

                        // $delete =  $staffConn->table('timetable_class')->where('id', $del)->delete();
                        // // delete calendor data
                        // $staffConn->table('calendors')->where('time_table_id', $del)->delete();
                        if ($staffConn->table('timetable_class')->where('id', '=', $del)->count() > 0) {
                            // record found
                            // echo "time table" . $del;
                            $delete =  $staffConn->table('timetable_class')->where('id', $del)->delete();
                        }
                        // delete calendor data
                        if ($staffConn->table('calendors')->where('time_table_id', '=', $del)->count() > 0) {
                            // record found
                            // dd($del);
                            // echo "calendor" . $del;
                            $staffConn->table('calendors')->where('time_table_id', $del)->delete();
                        }
                    }
                }

                // return $timetable;

                foreach ($timetable as $table) {

                    // return $table;
                    $session_id = 0;
                    $semester_id = 0;

                    $break_type = NULL;
                    $break = 0;
                    $subject_id = 0;
                    $teacher_id = NULL;


                    if (isset($request['session_id'])) {
                        $session_id = $request['session_id'];
                    }
                    if (isset($request['semester_id'])) {
                        $semester_id = $request['semester_id'];
                    }
                    if (isset($table['break_type'])) {
                        $break_type = $table['break_type'];
                    }
                    if (isset($table['break'])) {
                        $break = 1;
                    }
                    if (!empty($table['teacher'])) {
                        $teacher_id =  implode(",", $table['teacher']);
                    }
                    if (isset($table['subject'])) {
                        $subject_id = $table['subject'];
                    }
                    //  dd($break_type);
                    $insertOrUpdateID = 0;
                    if (isset($table['id'])) {
                        // echo "<pre>";
                        // echo $teacher_id;
                        $query = $staffConn->table('timetable_class')->where('id', $table['id'])->update([
                            'class_id' => $request['class_id'],
                            'section_id' => $request['section_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'subject_id' => $subject_id,
                            'teacher_id' => (isset($teacher_id) ? $teacher_id : 0),
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                        $insertOrUpdateID = $table['id'];
                    } else {
                        // echo "<pre>";
                        // echo $teacher_id;
                        // exit;
                        $query = $staffConn->table('timetable_class')->insertGetId([
                            'class_id' => $request['class_id'],
                            'section_id' => $request['section_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'subject_id' => $subject_id,
                            'teacher_id' => (isset($teacher_id) ? $teacher_id : 0),
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'created_at' => date("Y-m-d H:i:s")
                        ]);
                        $insertOrUpdateID = $query;
                    }
                    $bulkID = NuLL;
                    // return $break;
                    $this->addCalendorTimetable($request, $table, $getObjRow, $insertOrUpdateID, $bulkID);
                }
                // cache clear start
                $cache_timetable = config('constants.cache_timetable');
                $this->clearCache($cache_timetable, $request->branch_id);
                // cache clear end
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'TimeTable has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addTimetable');
        }
    }
    // copy Timetable
    public function copyTimetable(Request $request)
    {

        try {
            // dd($request);
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'day' => 'required',
                'timetable' => 'required',
                'academic_session_id' => 'required'
            ]);


            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $staffConn = $this->createNewConnection($request->branch_id);

                // calendor data populate
                $getObjRow = $staffConn->table('semester as s')
                    ->select('start_date', 'end_date')
                    ->where('id', $request->semester_id)
                    ->get();
                if (count($getObjRow) == 0) {
                    // Use the $yearData as needed
                    $getObjRow = $staffConn->table('semester as sm')
                        ->select('sm.start_date', 'sm.end_date')
                        ->where('sm.academic_session_id', '=', $request->academic_session_id)
                        ->get();
                }
                $timetable = $request->timetable;
                $oldest = $staffConn->table('timetable_class')->where([['class_id', $request->class_id], ['section_id', $request->section_id], ['semester_id', $request->semester_id], ['session_id', $request->session_id], ['day', $request->day], ['academic_session_id', $request->academic_session_id]])->WhereNull('bulk_id')->get()->toArray();

                // return $oldest;
                $diff = array_diff(array_column($oldest, 'id'), array_column($timetable, 'id'));
                // dd($diff);
                if (isset($diff)) {
                    foreach ($diff as $del) {

                        // $delete =  $staffConn->table('timetable_class')->where('id', $del)->delete();
                        // // delete calendor data
                        // $staffConn->table('calendors')->where('time_table_id', $del)->delete();
                        if ($staffConn->table('timetable_class')->where('id', '=', $del)->count() > 0) {
                            // record found
                            // echo "time table" . $del;
                            $delete =  $staffConn->table('timetable_class')->where('id', $del)->delete();
                        }
                        // delete calendor data
                        if ($staffConn->table('calendors')->where('time_table_id', '=', $del)->count() > 0) {
                            // record found
                            // dd($del);
                            // echo "calendor" . $del;
                            $staffConn->table('calendors')->where('time_table_id', $del)->delete();
                        }
                    }
                }

                // return $timetable;

                foreach ($timetable as $table) {

                    // return $table;
                    $session_id = 0;
                    $semester_id = 0;

                    $break_type = NULL;
                    $break = 0;
                    $subject_id = 0;
                    $teacher_id = NULL;


                    if (isset($request['session_id'])) {
                        $session_id = $request['session_id'];
                    }
                    if (isset($request['semester_id'])) {
                        $semester_id = $request['semester_id'];
                    }
                    if (isset($table['break_type'])) {
                        $break_type = $table['break_type'];
                    }
                    if (isset($table['break'])) {
                        $break = 1;
                    }
                    if (!empty($table['teacher'])) {
                        $teacher_id =  implode(",", $table['teacher']);
                    }
                    if (isset($table['subject'])) {
                        $subject_id = $table['subject'];
                    }
                    //  dd($break_type);
                    $insertOrUpdateID = 0;
                    if (isset($table['id'])) {
                        // echo "<pre>";
                        // echo $teacher_id;
                        $query = $staffConn->table('timetable_class')->where('id', $table['id'])->update([
                            'class_id' => $request['class_id'],
                            'section_id' => $request['section_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'subject_id' => $subject_id,
                            'teacher_id' => (isset($teacher_id) ? $teacher_id : 0),
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                        $insertOrUpdateID = $table['id'];
                    } else {
                        // echo "<pre>";
                        // echo $teacher_id;
                        // exit;
                        $query = $staffConn->table('timetable_class')->insertGetId([
                            'class_id' => $request['class_id'],
                            'section_id' => $request['section_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'subject_id' => $subject_id,
                            'teacher_id' => (isset($teacher_id) ? $teacher_id : 0),
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'created_at' => date("Y-m-d H:i:s")
                        ]);
                        $insertOrUpdateID = $query;
                    }
                    $bulkID = NuLL;
                    // return $break;
                    // if(isset($break_type)){

                    // }
                    $this->addCalendorTimetable($request, $table, $getObjRow, $insertOrUpdateID, $bulkID);
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'TimeTable has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in copyTimetable');
        }
    }
    // add Bulk Timetable
    public function addBulkTimetable(Request $request)
    {
        try {
            // dd($request);
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'day' => 'required',
                'timetable' => 'required',
                'academic_session_id' => 'required'
            ]);


            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $staffConn = $this->createNewConnection($request->branch_id);

                // calendor data populate
                $getObjRow = $staffConn->table('semester as s')
                    ->select('start_date', 'end_date')
                    ->where('id', $request->semester_id)
                    ->get();
                if (count($getObjRow) == 0) {
                    // Use the $yearData as needed
                    $getObjRow = $staffConn->table('semester as sm')
                        ->select('sm.start_date', 'sm.end_date')
                        ->where('sm.academic_session_id', '=', $request->academic_session_id)
                        ->get();
                }
                $timetable = $request->timetable;
                $oldest = $staffConn->table('timetable_bulk')->where([['class_id', $request->class_id], ['semester_id', $request->semester_id], ['session_id', $request->session_id], ['day', $request->day], ['academic_session_id', $request->academic_session_id]])->get()->toArray();

                $diff = array_diff(array_column($oldest, 'id'), array_column($timetable, 'id'));

                if (isset($diff)) {
                    foreach ($diff as $del) {

                        if ($staffConn->table('timetable_class')->where('bulk_id', '=', $del)->count() > 0) {
                            $delete =  $staffConn->table('timetable_class')->where('bulk_id', $del)->get();
                            // delete calendor data
                            foreach ($delete as $d) {
                                if ($staffConn->table('calendors')->where('time_table_id', '=', $d->id)->count() > 0) {
                                    $staffConn->table('calendors')->where('time_table_id', $d->id)->delete();
                                }
                            }

                            // delete timetable data
                            $staffConn->table('timetable_class')->where('bulk_id', $del)->delete();
                        }

                        if ($staffConn->table('timetable_bulk')->where('id', '=', $del)->count() > 0) {
                            // record found
                            $staffConn->table('timetable_bulk')->where('id', $del)->delete();
                        }
                    }
                }
                foreach ($timetable as $table) {

                    // return $table;
                    $session_id = 0;
                    $semester_id = 0;

                    $break_type = NULL;
                    $break = 0;
                    $teacher_id = 0;


                    if (isset($request['session_id'])) {
                        $session_id = $request['session_id'];
                    }
                    if (isset($request['semester_id'])) {
                        $semester_id = $request['semester_id'];
                    }
                    if (isset($table['break_type'])) {
                        $break_type = $table['break_type'];
                    }
                    if (isset($table['break'])) {
                        $break = 1;
                    }
                    if (!empty($table['teacher'])) {
                        $teacher_id =  implode(",", $table['teacher']);
                    }
                    //  dd($break_type);
                    $bulkID = 0;
                    if (isset($table['id'])) {
                        // echo "<pre>";
                        // echo $teacher_id;
                        $query = $staffConn->table('timetable_bulk')->where('id', $table['id'])->update([
                            'class_id' => $request['class_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'teacher_id' => $teacher_id,
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                        $timeTableUpdate = $staffConn->table('timetable_class')->where('bulk_id', $table['id'])->update([
                            'break' => $break,
                            'break_type' => $break_type,
                            'teacher_id' => $teacher_id,
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'type' => "All",
                            'academic_session_id' => $request['academic_session_id'],
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);

                        $bulkID = $table['id'];
                        $class = $staffConn->table('timetable_class')->where('bulk_id', $bulkID)->get();
                        if ($class) {
                            foreach ($class as $cla) {
                                $timeTableID = $cla->id;
                                $request['section_id'] = "$cla->section_id";
                                // update calendor
                                $this->addCalendorTimetable($request, $table, $getObjRow, $timeTableID, $bulkID);
                            }
                        }

                        // $calendorUpdate = $staffConn->table('calendors')->where('bulk_id', $table['id'])->update([

                        //     "title" =>  $break_type,
                        //     'teacher_id' => $teacher_id,
                        //     'updated_at' => date("Y-m-d H:i:s"),
                        // ]);
                    } else {
                        // echo "<pre>";
                        // echo $teacher_id;
                        $query = $staffConn->table('timetable_bulk')->insertGetId([
                            'class_id' => $request['class_id'],
                            'break' => $break,
                            'break_type' => $break_type,
                            'teacher_id' => $teacher_id,
                            'class_room' => $table['class_room'],
                            'time_start' => $table['time_start'],
                            'time_end' => $table['time_end'],
                            'semester_id' => $semester_id,
                            'session_id' => $session_id,
                            'day' => $request['day'],
                            'academic_session_id' => $request['academic_session_id'],
                            'created_at' => date("Y-m-d H:i:s")
                        ]);
                        $bulkID = $query;

                        $class = [];
                        // fetch class and section
                        if ($request['class_id'] == "All") {
                            $class = $staffConn->table('section_allocations')->select('class_id', 'section_id')->get();
                        } else {
                            $class = $staffConn->table('section_allocations')->select('class_id', 'section_id')->where('class_id', $request['class_id'])->get();
                        }
                        if ($class) {
                            foreach ($class as $cla) {
                                $timeTableID = $staffConn->table('timetable_class')->insertGetId([
                                    'class_id' => $cla->class_id,
                                    'section_id' => $cla->section_id,
                                    'break' => $break,
                                    'break_type' => $break_type,
                                    'teacher_id' => $teacher_id,
                                    'class_room' => $table['class_room'],
                                    'time_start' => $table['time_start'],
                                    'time_end' => $table['time_end'],
                                    'semester_id' => $semester_id,
                                    'session_id' => $session_id,
                                    'day' => $request['day'],
                                    'bulk_id' => $bulkID,
                                    'type' => "All",
                                    'academic_session_id' => $request['academic_session_id'],
                                    'created_at' => date("Y-m-d H:i:s")
                                ]);
                                $request['class_id'] = "$cla->class_id";
                                $request['section_id'] = "$cla->section_id";
                                // update calendor
                                $this->addCalendorTimetable($request, $table, $getObjRow, $timeTableID, $bulkID);
                            }
                        }
                    }
                }
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'TimeTable has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addBulkTimetable');
        }
    }


    // get Timetable List
    public function getTimetableList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // get data
                //$cache_time = config('constants.cache_time');
                //$cache_timetable = config('constants.cache_timetable');
                //$cacheKey = $cache_timetable . $request->branch_id;

                // Check if the data is cached
                //if (Cache::has($cacheKey)) {
                // If cached, return cached data
                // $output = Cache::get($cacheKey);
                //} else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);
                // get data
                // $Timetable = $con->table('timetable_class')->where('class_id',$request->class_id)->where('section_id',$request->section_id)->orderBy('time_start', 'asc')->orderBy('time_end', 'asc')->get()->toArray();
                $Timetable = $con->table('timetable_class')->select(
                    'timetable_class.*',
                    DB::raw('GROUP_CONCAT(staffs.first_name, " ", staffs.last_name) as teacher_name'),
                    'subjects.name as subject_name',
                    'exam_hall.hall_no'
                )
                    // ->leftJoin('staffs', 'timetable_class.teacher_id', '=', 'staffs.id')
                    ->leftJoin("staffs", DB::raw("FIND_IN_SET(staffs.id,timetable_class.teacher_id)"), ">", DB::raw("'0'"))
                    ->leftJoin('subjects', 'timetable_class.subject_id', '=', 'subjects.id')
                    ->leftJoin('exam_hall', 'timetable_class.class_room', '=', 'exam_hall.id')
                    ->where([
                        ['timetable_class.class_id', $request->class_id],
                        ['timetable_class.semester_id', $request->semester_id],
                        // ['timetable_class.session_id', $request->session_id],
                        ['timetable_class.section_id', $request->section_id],
                        ['timetable_class.academic_session_id', $request->academic_session_id]
                    ])
                    ->orderBy('time_start', 'asc')
                    ->orderBy('time_end', 'asc')
                    ->groupBy("timetable_class.id")
                    ->get()->toArray();

                if ($Timetable) {
                    $mapfunction = function ($s) {
                        return $s->day;
                    };
                    $count = array_count_values(array_map($mapfunction, $Timetable));
                    $max = max($count);

                    $output['timetable'] = $Timetable;
                    $output['max'] = $max;
                    $output['week'] = $count;
                    // Cache the fetched data for future requests
                    //Cache::put($cacheKey, $output, now()->addHours($cache_time)); // Cache for 24 hours
                    return $this->successResponse($output, 'Timetable record fetch successfully');
                } else {
                    return $this->send404Error('No Data Found.', ['error' => 'No Data Found']);
                }
                //}
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getTimetableList');
        }
    }

    // edit
    public function editTimetable(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'day' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);

                $Timetable = $con->table('timetable_class')->select(
                    'timetable_class.*',
                    DB::raw('CONCAT(staffs.first_name, " ", staffs.last_name) as teacher_name'),
                    'subjects.name as subject_name'
                )
                    ->leftJoin('staffs', 'timetable_class.teacher_id', '=', 'staffs.id')
                    ->leftJoin('subjects', 'timetable_class.subject_id', '=', 'subjects.id')
                    ->where([
                        ['timetable_class.day', $request->day],
                        ['timetable_class.class_id', $request->class_id],
                        ['timetable_class.semester_id', $request->semester_id],
                        ['timetable_class.session_id', $request->session_id],
                        ['timetable_class.section_id', $request->section_id],
                        ['timetable_class.academic_session_id', $request->academic_session_id]
                    ])
                    ->orderBy('time_start', 'asc')->orderBy('time_end', 'asc')
                    ->get()->toArray();
                // dd($Timetable);
                // return $Timetable;
                if ($Timetable) {
                    $mapfunction = function ($s) {
                        return $s->day;
                    };
                    $count = array_count_values(array_map($mapfunction, $Timetable));
                    $max = max($count);

                    $output['timetable'] = $Timetable;
                    $output['max'] = $max;
                    $output['details']['day'] = $request->day;
                    $output['details']['class'] = $con->table('classes')->select('classes.id as class_id', 'classes.name as class_name')->where('id', $request->class_id)->first();
                    $output['details']['section'] = $con->table('sections')->select('sections.id as section_id', 'sections.name as section_name')->where('id', $request->section_id)->first();

                    $semester = $con->table('semester')->select('semester.id as semester_id', 'semester.name as semester_name')->where('id', $request->semester_id)->first();
                    if ($semester) {
                        $semester = $semester;
                    } else {
                        $semester['semester_id'] = 0;
                    }
                    $output['details']['semester'] = $semester;

                    $session = $con->table('session')->select('session.id as session_id', 'session.name as session_name')->where('id', $request->session_id)->first();
                    if ($session) {
                        $session = $session;
                    } else {
                        $session['session_id'] = 0;
                    }
                    $output['details']['session'] = $session;

                    $output['teacher'] = $con->table('subject_assigns as sa')->select(
                        's.id',
                        DB::raw('CONCAT(s.last_name, " ", s.first_name) as name')
                    )
                        ->join('staffs as s', 'sa.teacher_id', '=', 's.id')
                        ->where('sa.class_id', $request->class_id)
                        ->where('sa.section_id', $request->section_id)
                        ->where('sa.academic_session_id', $request->academic_session_id)
                        ->groupBy('sa.teacher_id')
                        ->get();
                    $output['subject'] = $con->table('subject_assigns as sa')->select('s.id', 's.name')
                        ->join('subjects as s', 'sa.subject_id', '=', 's.id')
                        ->where('sa.class_id', $request->class_id)
                        ->where('sa.section_id', $request->section_id)
                        ->where('sa.academic_session_id', $request->academic_session_id)
                        ->get();
                    $output['exam_hall'] = $con->table('exam_hall')->get();

                    return $this->successResponse($output, 'Timetable record fetch successfully');
                } else {
                    return $this->send404Error('No Data Found.', ['error' => 'No Data Found']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in editTimetable');
        }
    }

    // update Timetable
    public function updateTimetable(Request $request)
    {
        try {
            // dd($request);
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'day' => 'required',
                'timetable' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $staffConn = $this->createNewConnection($request->branch_id);

                $timetable = $request->timetable;
                // calendor data populate
                $getObjRow = $staffConn->table('semester as s')
                    ->select('start_date', 'end_date')
                    ->where('id', $request->semester_id)
                    ->get();
                if (count($getObjRow) == 0) {
                    // Use the $yearData as needed
                    $getObjRow = $staffConn->table('semester as sm')
                        ->select('sm.start_date', 'sm.end_date')
                        ->where('sm.academic_session_id', '=', $request->academic_session_id)
                        ->get();
                }
                $oldest = $staffConn->table('timetable_class')
                    ->where([
                        ['timetable_class.day', $request->day],
                        ['timetable_class.class_id', $request->class_id],
                        ['timetable_class.semester_id', $request->semester_id],
                        ['timetable_class.session_id', $request->session_id],
                        ['timetable_class.section_id', $request->section_id],
                        ['timetable_class.academic_session_id', $request->academic_session_id]
                    ])
                    ->WhereNull('bulk_id')
                    ->get()->toArray();
                // dd($oldest);
                $diff = array_diff(array_column($oldest, 'id'), array_column($timetable, 'id'));
                // dd($diff);
                if (isset($diff)) {
                    foreach ($diff as $del) {
                        // dd($del);
                        if ($staffConn->table('timetable_class')->where('id', '=', $del)->count() > 0) {
                            // record found
                            // echo "time table" . $del;
                            $delete =  $staffConn->table('timetable_class')->where('id', $del)->delete();
                        }
                        // delete calendor data
                        if ($staffConn->table('calendors')->where('time_table_id', '=', $del)->count() > 0) {
                            // record found
                            // dd($del);
                            // echo "calendor" . $del;
                            $staffConn->table('calendors')->where('time_table_id', $del)->delete();
                        }
                    }
                }

                // exit;

                foreach ($timetable as $table) {

                    $session_id = 0;
                    $semester_id = 0;
                    // $break;
                    // $subject_id;
                    // $teacher_id;
                    if (isset($request['session_id'])) {
                        $session_id = $request['session_id'];
                    }
                    if (isset($request['semester_id'])) {
                        $semester_id = $request['semester_id'];
                    }

                    if (isset($table['break'])) {
                        $break = 1;
                        $subject_id = 0;
                        $teacher_id = 0;
                    } else {
                        $break = 0;
                        $subject_id = $table['subject'];
                        if (!empty($table['teacher'])) {
                            $teacher_id =  implode(",", $table['teacher']);
                        }
                    }
                    $insertOrUpdateID =  $table['id'];
                    $query = $staffConn->table('timetable_class')->where('id', $table['id'])->update([
                        'class_id' => $request['class_id'],
                        'section_id' => $request['section_id'],
                        'break' => $break,
                        'subject_id' => $subject_id,
                        'teacher_id' => $teacher_id,
                        'class_room' => $table['class_room'],
                        'time_start' => $table['time_start'],
                        'time_end' => $table['time_end'],
                        'semester_id' => $semester_id,
                        'session_id' => $session_id,
                        'day' => $request['day'],
                        'academic_session_id' => $request['academic_session_id'],
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    // update calendor
                    $bulkID = NULL;
                    $this->addCalendorTimetable($request, $table, $getObjRow, $insertOrUpdateID, $bulkID);
                }

                // cache clear start
                $cache_timetable = config('constants.cache_timetable');
                $this->clearCache($cache_timetable, $request->branch_id);
                // cache clear end
                $success = [];
                return $this->successResponse($success, 'TimeTable has been Update Successfully');
                // if (!$query) {
                //     return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                // } else {
                //     return $this->successResponse($success, 'TimeTable has been Update Successfully');
                // }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateTimetable');
        }
    }


    // get student timetable List
    public function studentTimetable(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'student_id' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);
                $student = $con->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'en.class_id',
                        'en.section_id',
                        'en.session_id',
                        'en.semester_id'
                    )
                    ->join('students as st', 'st.id', '=', 'en.student_id')
                    ->where([
                        ['en.student_id', '=', $request->student_id],
                        ['en.academic_session_id', '=', $request->academic_session_id],
                        // get active session
                        ['en.active_status', '=', '0']
                    ])
                    ->groupBy('en.student_id')
                    ->first();
                $output = [];
                if (isset($student)) {
                    $Timetable = $con->table('timetable_class')->select(
                        'timetable_class.*',
                        DB::raw('CONCAT(staffs.first_name, " ", staffs.last_name) as teacher_name'),
                        'subjects.name as subject_name'
                    )
                        ->leftJoin('staffs', 'timetable_class.teacher_id', '=', 'staffs.id')->leftJoin('subjects', 'timetable_class.subject_id', '=', 'subjects.id')
                        ->where('timetable_class.class_id', $student->class_id)
                        ->where('timetable_class.section_id', $student->section_id)
                        ->where('timetable_class.session_id', $student->session_id)
                        ->where('timetable_class.semester_id', $student->semester_id)
                        ->where('timetable_class.academic_session_id', $request->academic_session_id)
                        ->orderBy('timetable_class.time_start', 'asc')
                        ->orderBy('timetable_class.time_end', 'asc')
                        ->get()->toArray();


                    if ($Timetable) {
                        $mapfunction = function ($s) {
                            return $s->day;
                        };
                        $count = array_count_values(array_map($mapfunction, $Timetable));
                        $max = max($count);

                        $output['timetable'] = $Timetable;


                        $output['max'] = $max;
                        $output['details']['class'] = $con->table('classes')->select('classes.id as class_id', 'classes.name as class_name')->where('id', $student->class_id)->first();
                        $output['details']['section'] = $con->table('sections')->select('sections.id as section_id', 'sections.name as section_name')->where('id', $student->section_id)->first();
                        $output['details']['semester'] = $con->table('semester')->select('semester.id as semester_id', 'semester.name as semester_name')->where('id', $student->semester_id)->first();
                        $output['details']['session'] = $con->table('session')->select('session.id as session_id', 'session.name as session_name')->where('id', $student->session_id)->first();
                    }                // return $output;
                    return $this->successResponse($output, 'Timetable record fetch successfully');
                } else {
                    return $this->send404Error('No Data Found.', ['error' => 'No Data Found']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in studentTimetable');
        }
    }

    // get parent timetable List
    public function parentTimetable(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'parent_id' => 'required',
                'children_id' => 'required',
                'academic_session_id' => 'required'
            ]);

            // return $request;

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $con = $this->createNewConnection($request->branch_id);
                $student = $con->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'en.class_id',
                        'en.section_id',
                        'en.session_id',
                        'en.semester_id'
                    )
                    ->join('students as st', 'st.id', '=', 'en.student_id')
                    ->where([
                        ['en.student_id', '=', $request->children_id],
                        ['en.academic_session_id', '=', $request->academic_session_id],
                        // get active session
                        ['en.active_status', '=', '0']
                    ])
                    ->groupBy('en.student_id')
                    ->first();
                $output = [];
                if (isset($student)) {
                    $Timetable = $con->table('timetable_class')->select(
                        'timetable_class.*',
                        DB::raw('CONCAT(staffs.first_name, " ", staffs.last_name) as teacher_name'),
                        'subjects.name as subject_name'
                    )
                        ->leftJoin('staffs', 'timetable_class.teacher_id', '=', 'staffs.id')
                        ->leftJoin('subjects', 'timetable_class.subject_id', '=', 'subjects.id')
                        ->where('timetable_class.class_id', $student->class_id)
                        ->where('timetable_class.section_id', $student->section_id)
                        ->where('timetable_class.session_id', $student->session_id)
                        ->where('timetable_class.semester_id', $student->semester_id)
                        ->where('timetable_class.academic_session_id', $request->academic_session_id)
                        ->orderBy('timetable_class.time_start', 'asc')
                        ->orderBy('timetable_class.time_end', 'asc')
                        ->get()->toArray();

                    // return $Timetable;
                    if ($Timetable) {
                        $mapfunction = function ($s) {
                            return $s->day;
                        };
                        $count = array_count_values(array_map($mapfunction, $Timetable));
                        $max = max($count);

                        $output['timetable'] = $Timetable;
                        $output['max'] = $max;
                        $output['details']['class'] = $con->table('classes')->select('classes.id as class_id', 'classes.name as class_name')->where('id', $student->class_id)->first();
                        $output['details']['section'] = $con->table('sections')->select('sections.id as section_id', 'sections.name as section_name')->where('id', $student->section_id)->first();
                        $output['details']['semester'] = $con->table('semester')->select('semester.id as semester_id', 'semester.name as semester_name')->where('id', $student->semester_id)->first();
                        $output['details']['session'] = $con->table('session')->select('session.id as session_id', 'session.name as session_name')->where('id', $student->session_id)->first();
                    }

                    return $this->successResponse($output, 'Timetable record fetch successfully');
                } else {
                    return $this->send404Error('No Data Found.', ['error' => 'No Data Found']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in parentTimetable');
        }
    }

    // get student list by entrolls
    public function getStudListByClassSecSemSess(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'session_id' => 'required',
                'semester_id' => 'required',
                'academic_session_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $Connection = $this->createNewConnection($request->branch_id);
                $getSubjectMarks = $Connection->table('enrolls as en')
                    ->select(
                        'en.student_id',
                        'en.class_id',
                        'en.section_id',
                        DB::raw("CONCAT(st.last_name, ' ', st.first_name) as name"),
                        'st.id as id',
                        'st.register_no',
                        'st.roll_no',
                        'st.photo'
                    )
                    ->join('students as st', 'st.id', '=', 'en.student_id')
                    ->where([
                        ['en.class_id', '=', $request->class_id],
                        ['en.section_id', '=', $request->section_id],
                        ['en.semester_id', '=', $request->semester_id],
                        ['en.session_id', '=', $request->session_id],
                        ['en.academic_session_id', '=', $request->academic_session_id],
                        ['en.active_status', '=', '0']
                    ])
                    ->orderBy('st.first_name', 'asc')
                    ->get();
                return $this->successResponse($getSubjectMarks, 'Students record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getStudListByClassSecSemSess');
        }
    }
    //add attendance
    function addPromotion(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'promote_year' => 'required',
                'promote_class_id' => 'required',
                'promote_semester_id' => 'required',
                'promote_session_id' => 'required',
                'promotion' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Connection = $this->createNewConnection($request->branch_id);

                $promotion = $request->promotion;
                if (!empty($promotion)) {
                    foreach ($promotion as $key => $value) {
                        // dd($value['attendance_id']);
                        // dd($value);
                        if (isset($value['promotion_status'])) {
                            $student_id = (isset($value['student_id']) ? $value['student_id'] : 0);
                            $register_no = (isset($value['register_no']) ? $value['register_no'] : 0);
                            $roll_no = (isset($value['roll_no']) ? $value['roll_no'] : 0);
                            $promote_section_id = (isset($value['promote_section_id']) ? $value['promote_section_id'] : 0);
                            // here update studentID as promote
                            $Connection->table('enrolls')
                                ->where('student_id', '=', $student_id)
                                ->update(['active_status' => 1]);
                            $dataPromote = array(
                                'student_id' => $student_id,
                                'academic_session_id' => $request->promote_year,
                                'class_id' => $request->promote_class_id,
                                'section_id' => $promote_section_id,
                                'semester_id' => $request->promote_semester_id,
                                'session_id' => $request->promote_session_id,
                                'active_status' => 0,
                                'attendance_no' => $roll_no
                            );
                            $row = $Connection->table('enrolls')
                                ->select(
                                    'id',
                                    'class_id',
                                    'section_id',
                                    'attendance_no as roll',
                                    'session_id',
                                    'semester_id'
                                )->where([
                                    ['student_id', '=', $student_id],
                                    ['class_id', '=', $request->promote_class_id],
                                    ['section_id', '=', $promote_section_id],
                                    ['semester_id', '=', $request->promote_semester_id],
                                    ['session_id', '=', $request->promote_session_id]
                                ])->first();
                            // if (isset($value['promotion_status'])) {
                            if (isset($row->id)) {
                                $dataPromote['updated_at'] = date("Y-m-d H:i:s");
                                $Connection->table('enrolls')->where('id', $row->id)->update($dataPromote);
                            } else {
                                $dataPromote['created_at'] = date("Y-m-d H:i:s");
                                $Connection->table('enrolls')->insert($dataPromote);
                            }
                            // } else {
                            //     $dePromote = array(
                            //         'student_id' => $student_id,
                            //         'class_id' => $row->class_id,
                            //         'section_id' => $row->section_id,
                            //         'semester_id' => $row->semester_id,
                            //         'session_id' => $row->session_id,
                            //         'roll' => $row->roll
                            //     );
                            //     if (isset($row->id)) {
                            //         $dePromote['updated_at'] = date("Y-m-d H:i:s");
                            //         $Connection->table('enrolls')->where('id', $row->id)->update($dePromote);
                            //     } else {
                            //         $dePromote['created_at'] = date("Y-m-d H:i:s");
                            //         $Connection->table('enrolls')->insert($dePromote);
                            //     }
                            // }
                        }
                    }
                }
                return $this->successResponse([], 'Promotion successfuly.');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addPromotion');
        }
    }
    public function getPromotionDataBulkSave(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $conn = $this->createNewConnection($request->branch_id);
                $promotion_data = $request->updatedData;
                // return $promotion_data;
                foreach ($promotion_data as $row) {
                    $id = $row['id'];
                    $attendance_no = $row['attendance_no'];

                    $conn->table('temp_promotion')->where('id', $id)->update(['attendance_no' => $attendance_no, 'status' => 1]);
                }

                return $this->successResponse($promotion_data, 'Data successfully added');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getPromotionDataBulkSave');
        }
    }
    public function getPromotionBulkStudentList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $conn = $this->createNewConnection($request->branch_id);
                $allocation = $conn->table('teacher_allocations as ta')
                    ->select(
                        'class_id',
                        'section_id',
                        'department_id'
                    )
                    ->where('ta.teacher_id', $request->teacher_id)
                    ->first();
                $grade_id =  $allocation->class_id;
                if (isset($request->grade_id)) {
                    $grade_id = $request->grade_id;
                }
                $section_id = $allocation->section_id;
                if (isset($request->section_id)) {
                    $section_id = $request->section_id;
                }
                $sort_id = "All";
                if (isset($request->sort_id)) {
                    $sort_id = $request->sort_id;
                }

                if ($sort_id == 1) {
                    $promotion_data1 = $conn->table('temp_promotion as tp')
                        ->select(
                            "tp.id",
                            "tp.attendance_no",
                            DB::raw("CONCAT(st1.last_name, ' ', st1.first_name) as name"),
                            "tp.register_no",
                            "d1.name as deptName",
                            "c1.name as className",
                            "s1.name as sectionName",
                            "d2.name as deptPromotionName",
                            "c2.name as classPromotionName",
                            "s2.name as sectionPromotionName",
                            "tp.status"
                        )
                        ->leftJoin('classes as c1', 'c1.id', '=', 'tp.class_id')
                        ->leftJoin('classes as c2', 'c2.id', '=', 'tp.promoted_class_id')
                        ->leftJoin('sections as s1', 's1.id', '=', 'tp.section_id')
                        ->leftJoin('sections as s2', 's2.id', '=', 'tp.promoted_section_id')
                        ->leftJoin('staff_departments as d1', 'd1.id', '=', 'tp.department_id')
                        ->leftJoin('staff_departments as d2', 'd2.id', '=', 'tp.promoted_department_id')
                        ->leftJoin('students as st1', 'st1.id', '=', 'tp.student_id')
                        ->where('tp.class_id', '=', $grade_id)
                        ->when(!empty($section_id), function ($query) use ($section_id) {
                            return $query->where('tp.section_id', '=', $section_id);
                        })
                        ->whereIn('tp.status', [1, 2, 3, 4])
                        ->orderBy('tp.section_id', 'asc')
                        ->get();
                } elseif ($sort_id == 2) {
                    $promotion_data1 = $conn->table('temp_promotion as tp')
                        ->select(
                            "tp.id",
                            "tp.attendance_no",
                            DB::raw("CONCAT(st1.last_name, ' ', st1.first_name) as name"),
                            "tp.register_no",
                            "d1.name as deptName",
                            "c1.name as className",
                            "s1.name as sectionName",
                            "d2.name as deptPromotionName",
                            "c2.name as classPromotionName",
                            "s2.name as sectionPromotionName",
                            "tp.status"
                        )
                        ->leftJoin('classes as c1', 'c1.id', '=', 'tp.class_id')
                        ->leftJoin('classes as c2', 'c2.id', '=', 'tp.promoted_class_id')
                        ->leftJoin('sections as s1', 's1.id', '=', 'tp.section_id')
                        ->leftJoin('sections as s2', 's2.id', '=', 'tp.promoted_section_id')
                        ->leftJoin('staff_departments as d1', 'd1.id', '=', 'tp.department_id')
                        ->leftJoin('staff_departments as d2', 'd2.id', '=', 'tp.promoted_department_id')
                        ->leftJoin('students as st1', 'st1.id', '=', 'tp.student_id')
                        ->where('tp.class_id', '=', $grade_id)
                        ->when(!empty($section_id), function ($query) use ($section_id) {
                            return $query->where('tp.section_id', '=', $section_id);
                        })
                        ->whereIn('tp.status', [1, 2, 3, 4])
                        ->orderBy('tp.promoted_section_id', 'asc')
                        ->get();
                } else {
                    $promotion_data1 = $conn->table('temp_promotion as tp')
                        ->select(
                            "tp.id",
                            "tp.attendance_no",
                            DB::raw("CONCAT(st1.last_name, ' ', st1.first_name) as name"),
                            "tp.register_no",
                            "d1.name as deptName",
                            "c1.name as className",
                            "s1.name as sectionName",
                            "d2.name as deptPromotionName",
                            "c2.name as classPromotionName",
                            "s2.name as sectionPromotionName",
                            "tp.status",
                            "te1.date_of_termination"
                        )
                        ->leftJoin('classes as c1', 'c1.id', '=', 'tp.class_id')
                        ->leftJoin('classes as c2', 'c2.id', '=', 'tp.promoted_class_id')
                        ->leftJoin('sections as s1', 's1.id', '=', 'tp.section_id')
                        ->leftJoin('sections as s2', 's2.id', '=', 'tp.promoted_section_id')
                        ->leftJoin('staff_departments as d1', 'd1.id', '=', 'tp.department_id')
                        ->leftJoin('staff_departments as d2', 'd2.id', '=', 'tp.promoted_department_id')
                        ->leftJoin('students as st1', 'st1.id', '=', 'tp.student_id')
                        ->leftJoin('termination as te1', 'te1.student_id', '=', 'tp.student_id')
                        ->when($grade_id == 'All', function ($query) use ($allocation) {
                            return $query->where('tp.class_id', '=', $allocation->class_id);
                        })
                        ->when($section_id == 'All', function ($query) use ($allocation) {
                            return $query->where('tp.section_id', '=', $allocation->section_id);
                        })
                        ->get();
                }


                return $this->successResponse($promotion_data1, 'Data successfully added');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getPromotionBulkStudentList');
        }
    }
    public function getPromotionUnassignedStudentList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                // 'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $conn = $this->createNewConnection($request->branch_id);
                $grade_id = "All";
                if (isset($request->grade_id)) {
                    $grade_id = $request->grade_id;
                }
                $section_id = "All";
                if (isset($request->section_id)) {
                    $section_id = $request->section_id;
                }
                $promotion_data_not_in_enroll = $conn->table('temp_promotion as tp')
                    ->select(
                        "tp.attendance_no",
                        DB::raw("CONCAT(st1.last_name, ' ', st1.first_name) as name"),
                        "st1.admission_date",
                        "tp.register_no",
                        "d1.name as deptName",
                        "c1.name as className",
                        "s1.name as sectionName",
                        "st1.status"
                    )
                    ->leftJoin('classes as c1', 'c1.id', '=', 'tp.class_id')
                    ->leftJoin('sections as s1', 's1.id', '=', 'tp.section_id')
                    ->leftJoin('staff_departments as d1', 'd1.id', '=', 'tp.department_id')
                    ->leftJoin('students as st1', 'st1.id', '=', 'tp.student_id')
                    ->leftJoin('semester as sem1', 'sem1.id', '=', 'tp.semester_id')
                    ->leftJoin('session as ses1', 'ses1.id', '=', 'tp.session_id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('enrolls')
                            ->whereRaw('enrolls.student_id = tp.student_id');
                    })
                    ->when($grade_id != "All", function ($query) use ($grade_id) {
                        return $query->where('tp.class_id', '=', $grade_id);
                    })
                    ->when($section_id != "All", function ($query) use ($section_id) {
                        return $query->where('tp.section_id', '=', $section_id);
                    })
                    ->whereIn('tp.status', [1, 2, 3, 4])
                    ->get();

                return $this->successResponse($promotion_data_not_in_enroll, 'Data successfully added');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getPromotionUnassignedStudentList');
        }
    }
    public function getPromotionUnassignedFreezedData(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                // 'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // $branch =4;
                $branch = $request->branch_id;
                $conn = $this->createNewConnection($branch);
                $promotion_data_not_in_enroll = $conn->table('temp_promotion as tp')
                    ->select(
                        "tp.attendance_no",
                        DB::raw("CONCAT(st1.last_name, ' ', st1.first_name) as name"),
                        "st1.admission_date",
                        "tp.register_no",
                        "d1.name as deptName",
                        "c1.name as className",
                        "s1.name as sectionName",
                        "st1.status"
                    )
                    ->leftJoin('classes as c1', 'c1.id', '=', 'tp.class_id')
                    ->leftJoin('sections as s1', 's1.id', '=', 'tp.section_id')
                    ->leftJoin('staff_departments as d1', 'd1.id', '=', 'tp.department_id')
                    ->leftJoin('students as st1', 'st1.id', '=', 'tp.student_id')
                    ->leftJoin('semester as sem1', 'sem1.id', '=', 'tp.semester_id')
                    ->leftJoin('session as ses1', 'ses1.id', '=', 'tp.session_id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('enrolls')
                            ->whereRaw('enrolls.student_id = tp.student_id');
                    })
                    ->whereIn('tp.status', [1, 2, 3, 4])
                    ->get();

                return $this->successResponse($promotion_data_not_in_enroll, 'Data successfully added');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getPromotionUnassignedFreezedData');
        }
    }
    public function getPromotionTerminationStudentList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                // 'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $conn = $this->createNewConnection($request->branch_id);

                $grade_id = "All";
                if (isset($request->grade_id)) {
                    $grade_id = $request->grade_id;
                }
                $section_id = "All";
                if (isset($request->section_id)) {
                    $section_id = $request->section_id;
                }
                $promotion_data_inactive_students = $conn->table('temp_promotion as tp')
                    ->select(
                        "tp.attendance_no",
                        DB::raw("CONCAT(st1.last_name, ' ', st1.first_name) as name"),
                        "st1.admission_date",
                        "te1.date_of_termination",
                        "tp.register_no",
                        "d1.name as deptName",
                        "c1.name as className",
                        "s1.name as sectionName"
                    )
                    ->leftJoin('classes as c1', 'c1.id', '=', 'tp.class_id')
                    ->leftJoin('sections as s1', 's1.id', '=', 'tp.section_id')
                    ->leftJoin('staff_departments as d1', 'd1.id', '=', 'tp.department_id')
                    ->leftJoin('students as st1', 'st1.id', '=', 'tp.student_id')
                    ->leftJoin('termination as te1', 'te1.student_id', '=', 'tp.student_id')
                    ->when($grade_id != "All", function ($query) use ($grade_id) {
                        return $query->where('tp.class_id', '=', $grade_id);
                    })
                    ->when($section_id != "All", function ($query) use ($section_id) {
                        return $query->where('tp.section_id', '=', $section_id);
                    })
                    ->whereIn('tp.status', [1, 2, 3, 4])
                    ->whereNotNull('te1.date_of_termination')
                    ->get();

                return $this->successResponse($promotion_data_inactive_students, 'Data successfully added');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getPromotionTerminationStudentList');
        }
    }
    public function getPromotionPreparedDataAdd(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $conn = $this->createNewConnection($request->branch_id);
                $promotion_data = $request->updatedData;
                // return $promotion_data;
                foreach ($promotion_data as $id) {

                    $conn->table('temp_promotion')->where('id', $id)->update(['status' => 2]);
                }

                return $this->successResponse($promotion_data, 'Data successfully added');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getPromotionPreparedDataAdd');
        }
    }
    public function getPromotionFreezedData(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $conn = $this->createNewConnection($request->branch_id);
                $status = "All";
                if (isset($request->status)) {
                    $status = $request->status;
                }
                $promotion_data = $conn->table('temp_promotion as tp')
                    ->select(
                        "tp.id",
                        "d1.name as deptName",
                        "c1.name as className",
                        "tp.status"
                    )
                    ->leftJoin('classes as c1', 'c1.id', '=', 'tp.class_id')
                    ->leftJoin('staff_departments as d1', 'd1.id', '=', 'tp.department_id')
                    ->when($status != "All", function ($query) use ($status) {
                        return $query->where('tp.status', '=', $status);
                    })
                    ->whereIn('tp.status', [1, 2, 3, 4])
                    //->groupBy('c1.name')
                    ->get();

                return $this->successResponse($promotion_data, 'Data successfully added');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getPromotionFreezedData');
        }
    }
    public function addPromotionStatusData(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $conn = $this->createNewConnection($request->branch_id);
                $promotion_data = $request->statusData;
                // return $promotion_data;
                foreach ($promotion_data as $rowData) {
                    $id = $rowData['id'];
                    $status = $rowData['selectedStatus'];

                    $conn->table('temp_promotion')->where('id', $id)->update(['status' => $status]);
                }

                return $this->successResponse($promotion_data, 'Data successfully added');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addPromotionStatusData');
        }
    }
    public function addPromotionFinalData(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $conn = $this->createNewConnection($request->branch_id);
                $promotion_data = $request->promotionFinalData;

                foreach ($promotion_data as $rowData) {
                    $promotion_final = $conn->table('temp_promotion')->where('id', $rowData['id'])->get();
                    if ($promotion_final) {
                        //return $this->successResponse($promotion_final[0]->student_id, 'Data successfully added');
                        $conn->table('enrolls')
                            ->where('student_id', '=', $promotion_final[0]->student_id)
                            ->update(['active_status' => 1]);
                        // Insert data into the 'enrolls' table
                        // $enrollData = [
                        //     'student_id' => $promotion_final[0]->student_id,
                        //     'attendance_no' => $promotion_final[0]->attendance_no,
                        //     'department_id' => $promotion_final[0]->department_id,
                        //     'class_id'  => $promotion_final[0]->class_id,
                        //     'section_id'  => $promotion_final[0]->section_id,
                        //     'roll'  => $promotion_final[0]->roll,
                        //     'academic_session_id'  => $promotion_final[0]->academic_session_id,
                        //     'semester_id'  => $promotion_final[0]->semester_id,
                        //     'session_id'  => $promotion_final[0]->session_id,
                        //     'active_status' => 1
                        // ];
                        // $enrollId = $conn->table('enrolls')->insertGetId($enrollData);
                        $enrollData2 = [
                            'student_id' => $promotion_final[0]->student_id,
                            'attendance_no' => $promotion_final[0]->attendance_no,
                            'department_id' => $promotion_final[0]->promoted_department_id,
                            'class_id'  => $promotion_final[0]->promoted_class_id,
                            'section_id'  => $promotion_final[0]->promoted_section_id,
                            'academic_session_id'  => $promotion_final[0]->promoted_academic_session_id,
                            'semester_id'  => $promotion_final[0]->promoted_semester_id,
                            'session_id'  => $promotion_final[0]->promoted_session_id
                        ];
                        $enrollId2 = $conn->table('enrolls')->insertGetId($enrollData2);

                        if ($enrollId2) {
                            // If the insertion was successful, delete from 'temp_promotion'
                            $conn->table('temp_promotion')->where('id', $rowData['id'])->delete();
                        }
                    }
                }

                return $this->successResponse($promotion_data, 'All Student promoted successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addPromotionFinalData');
        }
    }
    public function downloadPromotionData(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $conn = $this->createNewConnection($request->branch_id);
                $class_id = $request->class_id;
                $section_id = $request->section_id;
                $download_data = $conn->table('enrolls as en')->select(
                    DB::raw('CONCAT(s.last_name, " ", s.first_name) as student_name'),
                    'd.name',
                    'cl.name as class_name',
                    'se.name as section_name',
                    's.register_no',
                    'en.attendance_no',
                    'ay.name as academic_year'
                )
                    ->leftJoin('students as s', 'en.student_id', '=', 's.id')
                    ->leftJoin('staff_departments as d', 'en.department_id', '=', 'd.id')
                    ->leftJoin('classes as cl', 'en.class_id', '=', 'cl.id')
                    ->leftJoin('sections as se', 'en.section_id', '=', 'se.id')
                    ->leftJoin('academic_year as ay', 'en.academic_session_id', '=', 'ay.id')
                    ->where('en.class_id', $class_id)
                    ->when($section_id, function ($query, $section_id) {
                        return $query->where('en.section_id', $section_id);
                    })
                    ->where('en.active_status', '=', '0')
                    ->get();

                // $download_data = $conn->table('section_allocations as sa')->select('d.name', 'cl.name as class_name', 's.name as section_name')
                //     ->leftJoin('staff_departments as d', 'sa.department_id', '=', 'd.id')
                //     ->leftJoin('classes as cl', 'sa.class_id', '=', 'cl.id')
                //     ->join('sections as s', 'sa.section_id', '=', 's.id')
                //     ->where('sa.class_id', $class_id)
                //     ->when($section_id, function ($query, $section_id) {
                //         return $query->where('sa.section_id', $section_id);
                //     })
                //     ->get();

                return $this->successResponse($download_data, 'Data successfully added');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in downloadPromotionData');
        }
    }
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
