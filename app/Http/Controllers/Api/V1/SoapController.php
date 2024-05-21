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

class SoapController extends BaseController
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
    // addSoapCategory
    public function addSoapCategory(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'soap_type_id' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // insert data
                $query = $conn->table('soap_category')->insert([
                    'name' => $request->name,
                    'soap_type_id' => $request->soap_type_id,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Category has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addSoapCategory');
        }
    }
    // getSoapCategoryList
    public function getSoapCategoryList(Request $request)
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
                // get data
                $SoapCategoryDetails = $conn->table('soap_category')->get();
                return $this->successResponse($SoapCategoryDetails, 'Category record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getSoapCategoryList');
        }
    }
    // get SoapCategory row details
    public function getSoapCategoryDetails(Request $request)
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
                $SoapCategoryDetails = $conn->table('soap_category')->where('id', $id)->first();
                return $this->successResponse($SoapCategoryDetails, 'Category row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getSoapCategoryDetails');
        }
    }
    // update SoapCategory
    public function updateSoapCategory(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'soap_type_id' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // update data
                $query = $conn->table('soap_category')->where('id', $id)->update([
                    'name' => $request->name,
                    'soap_type_id' => $request->soap_type_id,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Category Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateSoapCategory');
        }
    }
    // delete SoapCategory
    public function deleteSoapCategory(Request $request)
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
                $query = $conn->table('soap_category')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Category have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteSoapCategory');
        }
    }

    // addSoapSubCategory
    public function addSoapSubCategory(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'soap_category_id' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // insert data
                if ($request->photo) {
                    $path = '/public/' . $request->branch_id . '/soap/images/';

                    $fileName = 'SCIMG_' . date('Ymd') . uniqid() . '.' . $request->file_extension;
                    $base64 = base64_decode($request->photo);
                    File::ensureDirectoryExists(base_path() . $path);
                    $file = base_path() . $path . $fileName;
                    $suc = file_put_contents($file, $base64);
                } else {
                    $fileName = "";
                }
                $query = $conn->table('soap_sub_category')->insert([
                    'name' => $request->name,
                    'soap_category_id' => $request->soap_category_id,
                    'photo' => $fileName,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Sub Category has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addSoapSubCategory');
        }
    }
    // getSoapSubCategoryList
    public function getSoapSubCategoryList(Request $request)
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
                // get data
                $SoapSubCategoryDetails = $conn->table('soap_sub_category as sc')->select('sc.id', 'sc.name', 'c.name as soap_category_id')
                    ->leftJoin('soap_category as c', 'sc.soap_category_id', '=', 'c.id')->get();
                return $this->successResponse($SoapSubCategoryDetails, 'Sub Category record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getSoapSubCategoryList');
        }
    }
    // get SoapSubCategory row details
    public function getSoapSubCategoryDetails(Request $request)
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
                $SoapSubCategoryDetails = $conn->table('soap_sub_category as sc')->select('sc.*', 'c.soap_type_id')
                    ->leftJoin('soap_category as c', 'sc.soap_category_id', '=', 'c.id')
                    ->where('sc.id', $id)->first();
                return $this->successResponse($SoapSubCategoryDetails, 'Sub Category row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getSoapSubCategoryDetails');
        }
    }
    // update SoapSubCategory
    public function updateSoapSubCategory(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'soap_category_id' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                if ($request->photo) {
                    $path = '/public/' . $request->branch_id . '/soap/images/';
                    $oldPicture = $conn->table('soap_sub_category')->where('id', $id)->first();

                    // return $oldPicture->photo;
                    if ($oldPicture->photo != '') {
                        if (\File::exists(base_path($path . $oldPicture->photo))) {
                            \File::delete(base_path($path . $oldPicture->photo));
                        }
                    }
                    $fileName = 'SCIMG_' . date('Ymd') . uniqid() . '.' . $request->file_extension;
                    $base64 = base64_decode($request->photo);
                    $file = base_path() . $path . $fileName;
                    $suc = file_put_contents($file, $base64);
                } else {
                    if ($request->old_photo) {
                        $fileName = $request->old_photo;
                    } else {
                        $fileName = "";
                    }
                }
                // update data
                $query = $conn->table('soap_sub_category')->where('id', $id)->update([
                    'name' => $request->name,
                    'soap_category_id' => $request->soap_category_id,
                    'photo' => $fileName,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Sub Category Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateSoapSubCategory');
        }
    }
    // delete SoapSubCategory
    public function deleteSoapSubCategory(Request $request)
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
                $query = $conn->table('soap_sub_category')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Sub Category have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteSoapSubCategory');
        }
    }


    // addSoapNotes
    public function addSoapNotes(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'notes' => 'required',
                'soap_category_id' => 'required',
                'soap_sub_category_id' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // insert data
                $query = $conn->table('soap_notes')->insert([
                    'notes' => $request->notes,
                    'soap_category_id' => $request->soap_category_id,
                    'soap_sub_category_id' => $request->soap_sub_category_id,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Notes has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addSoapNotes');
        }
    }
    // getSoapNotesList
    public function getSoapNotesList(Request $request)
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
                // get data
                $SoapNotesDetails = $conn->table('soap_notes as n')->select('n.id', 'n.notes', 'c.name as soap_category_id', 'sc.name as soap_sub_category_id')
                    ->leftJoin('soap_category as c', 'n.soap_category_id', '=', 'c.id')
                    ->leftJoin('soap_sub_category as sc', 'n.soap_sub_category_id', '=', 'sc.id')
                    ->get();
                return $this->successResponse($SoapNotesDetails, 'Notes record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getSoapNotesList');
        }
    }
    // get SoapNotes row details
    public function getSoapNotesDetails(Request $request)
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
                $SoapNotesDetails = $conn->table('soap_notes as n')->select('n.*', 'c.soap_type_id')
                    ->leftJoin('soap_category as c', 'n.soap_category_id', '=', 'c.id')->where('n.id', $id)->first();
                return $this->successResponse($SoapNotesDetails, 'Notes row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getSoapNotesDetails');
        }
    }
    // update SoapNotes
    public function updateSoapNotes(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'notes' => 'required',
                'soap_category_id' => 'required',
                'soap_sub_category_id' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // update data
                $query = $conn->table('soap_notes')->where('id', $id)->update([
                    'notes' => $request->notes,
                    'soap_category_id' => $request->soap_category_id,
                    'soap_sub_category_id' => $request->soap_sub_category_id,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Notes Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateSoapNotes');
        }
    }
    // delete SoapNotes
    public function deleteSoapNotes(Request $request)
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
                $query = $conn->table('soap_notes')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Notes have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteSoapNotes');
        }
    }

    // addSoapSubject
    public function addSoapSubject(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'title' => 'required',
                'header' => 'required',
                'body' => 'required',
                'soap_type_id' => 'required',
                'student_id' => 'required',
                'referred_by' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // insert data
                $query = $conn->table('soap_subject')->insert([
                    'title' => $request->title,
                    'header' => $request->header,
                    'body' => $request->body,
                    'soap_type_id' => $request->soap_type_id,
                    'student_id' => $request->student_id,
                    'referred_by' => $request->referred_by,
                    'date' => date('Y-m-d'),
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Subject has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addSoapSubject');
        }
    }
    // getSoapSubjectList
    public function getSoapSubjectList(Request $request)
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
                // get data
                $SoapSubjectDetails = $conn->table('soap_subject')->select('*', DB::raw('CONCAT(s.first_name, " ", s.last_name) as referred_by'))
                    ->join('staffs as s', 'soap_subject.referred_by', '=', 's.id')->get();
                return $this->successResponse($SoapSubjectDetails, 'Subject record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getSoapSubjectList');
        }
    }
    // get SoapSubject row details
    public function getSoapSubjectDetails(Request $request)
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
                $SoapSubjectDetails = $conn->table('soap_subject')->where('id', $id)->first();
                return $this->successResponse($SoapSubjectDetails, 'Subject row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getSoapSubjectDetails');
        }
    }
    // update SoapSubject
    public function updateSoapSubject(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'title' => 'required',
                'header' => 'required',
                'body' => 'required',
                'soap_type_id' => 'required',
                'student_id' => 'required',
                'referred_by' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // update data
                $query = $conn->table('soap_subject')->where('id', $id)->update([
                    'title' => $request->title,
                    'header' => $request->header,
                    'body' => $request->body,
                    'soap_type_id' => $request->soap_type_id,
                    'student_id' => $request->student_id,
                    'referred_by' => $request->referred_by,
                    'date' => date('Y-m-d'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Subject Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateSoapSubject');
        }
    }
    // delete SoapSubject
    public function deleteSoapSubject(Request $request)
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
                $query = $conn->table('soap_subject')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Subject have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteSoapSubject');
        }
    }

    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
