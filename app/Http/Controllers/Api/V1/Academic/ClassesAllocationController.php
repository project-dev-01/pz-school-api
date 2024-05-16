<?php

namespace App\Http\Controllers\Api\V1\Academic;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Exception;

class ClassesAllocationController extends BaseController
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
    public function addSectionAllocation(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'department_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($createConnection->table('section_allocations')->where([['section_id', $request->section_id], ['class_id', $request->class_id]])->count() > 0) {
                    return $this->send422Error('Already Allocated Section', ['error' => 'Already Allocated Section']);
                } else {
                    // insert data
                    $query = $createConnection->table('section_allocations')->insert([
                        'department_id' => $request->department_id,
                        'class_id' => $request->class_id,
                        'section_id' => $request->section_id,
                        'capacity' => $request->capacity,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'Section Allocation has been successfully saved');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'addSectionAllocation');
        }
    }
    // get sections allocation
    public function getSectionAllocationList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $secConn = $this->createNewConnection($request->branch_id);
                // get data
                $sectionAllocation = $secConn->table('section_allocations as sa')
                    ->select('sa.id', 'sa.capacity', 'sa.class_id', 'sa.section_id', 's.name as section_name', 'c.name as class_name', 'c.name_numeric', 'stf_dp.name as department_name')
                    ->join('sections as s', 'sa.section_id', '=', 's.id')
                    ->join('classes as c', 'sa.class_id', '=', 'c.id')
                    ->leftJoin('staff_departments as stf_dp', 'sa.department_id', '=', 'stf_dp.id')
                    ->orderBy('sa.department_id', 'desc')
                    ->orderBy('c.name', 'asc')
                    ->orderBy('s.name', 'asc')
                    ->get();
                return $this->successResponse($sectionAllocation, 'Section Allocation record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getSectionAllocationList');
        }
    }

    // get getSectionAllocationDetails details
    public function getSectionAllocationDetails(Request $request)
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
                $sectionDetails = $createConnection->table('section_allocations')->where('id', $request->id)->first();
                return $this->successResponse($sectionDetails, 'Section Allocation row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'getSectionAllocationDetails');
        }
    }
    // update Section Allocations

    public function updateSectionAllocation(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'department_id' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'branch_id' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $id = $request->id;
                // create new connection
                $createConnection = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($createConnection->table('section_allocations')->where([['section_id', $request->section_id], ['class_id', $request->class_id], ['id', '!=', $id]])->count() > 0) {
                    return $this->send422Error('Already Allocated Section', ['error' => 'Already Allocated Section']);
                } else {
                    // update data
                    $query = $createConnection->table('section_allocations')->where('id', $id)->update([
                        'department_id' => $request->department_id,
                        'class_id' => $request->class_id,
                        'section_id' => $request->section_id,
                        'capacity' => $request->capacity,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Section Allocation Details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'updateSectionAllocation');
        }
    }
    // delete deleteSectionAllocation
    public function deleteSectionAllocation(Request $request)
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
                $query = $createConnection->table('section_allocations')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Section Allocation have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'deleteSectionAllocation');
        }
    }
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
