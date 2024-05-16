<?php

namespace App\Http\Controllers\Api\V1\Academic;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Exception;

class SubjectController extends BaseController
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
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
