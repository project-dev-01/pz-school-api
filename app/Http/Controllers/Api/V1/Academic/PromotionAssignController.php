<?php

namespace App\Http\Controllers\Api\V1\Academic;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Exception;

class PromotionAssignController extends BaseController
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
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
