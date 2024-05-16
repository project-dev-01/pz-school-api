<?php

namespace App\Http\Controllers\Api\V1\Academic;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Exception;

class ClassesController extends BaseController
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
    public function getSectionList(Request $request)
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
                $cache_sections = config('constants.cache_sections');
                //dd($cache_academic_years);
                //$Department = $Connection->table('academic_year')->get();
                $cacheKey = $cache_sections . $request->branch_id;

                // Check if the data is cached
                if (Cache::has($cacheKey)) {
                    // If cached, return cached data
                    $section = Cache::get($cacheKey);
                } else {
                    // create new connection
                    $secConn = $this->createNewConnection($request->branch_id);
                    // get data
                    $section = $secConn->table('sections')->orderBy('name', 'asc')->get();
                    Cache::put($cacheKey, $section, now()->addHours($cache_time)); // Cache for 24 hours
                }
                return $this->successResponse($section, 'Classes record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getSectionList');
        }
    }
    /**
     * @Chandru @since May 15,2024
     * @desc Add section
     */
    public function addSection(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'name' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);

                // check exist name
                if ($createConnection->table('sections')->where('name', '=', $request->name)->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {
                    // insert data
                    $query = $createConnection->table('sections')->insert([
                        'name' => $request->name,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                    // cache clear start
                    $cache_sections = config('constants.cache_sections');
                    $this->clearCache($cache_sections, $request->branch_id);
                    // cache clear end
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'New Classes has been successfully saved');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'addSection');
        }
    }
    // get section row details
    public function getSectionDetails(Request $request)
    {

        try {
            $validator = \Validator::make($request->all(), [
                'section_id' => 'required',
                'token' => 'required',
                'branch_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // insert data
                $sectionDetails = $createConnection->table('sections')->where('id', $request->section_id)->first();
                return $this->successResponse($sectionDetails, 'Classes row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getSectionDetails');
        }
    }
    public function updateSectionDetails(Request $request)
    {

        try {

            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'name' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $section_id = $request->sid;
                // create new connection
                $staffConn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($staffConn->table('sections')->where([['name', '=', $request->name], ['id', '!=', $section_id]])->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {
                    // update data
                    $query = $staffConn->table('sections')->where('id', $section_id)->update([
                        'name' => $request->name,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    // cache clear start
                    $cache_sections = config('constants.cache_sections');
                    $this->clearCache($cache_sections, $request->branch_id);
                    // cache clear end
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Classes Details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateSectionDetails');
        }
    }
    // delete Section
    public function deleteSection(Request $request)
    {
        try {
            $section_id = $request->sid;
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'sid' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // get data
                $query = $createConnection->table('sections')->where('id', $section_id)->delete();
                // cache clear start
                $cache_sections = config('constants.cache_sections');
                $this->clearCache($cache_sections, $request->branch_id);
                // cache clear end
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Classes have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteSection');
        }
    }
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
