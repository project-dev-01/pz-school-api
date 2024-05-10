<?php

namespace App\Http\Controllers\Api\V1;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
// base controller add
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use DateTime;
use DateInterval;
use DatePeriod;
use App\Helpers\Helper;
use App\Models\Classes;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use File;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use App\Helpers\CommonHelper;

class GradeController extends BaseController
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
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
