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

class SuperVisionController extends BaseController
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
    // addHostelCategory
    public function addHostelCategory(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($conn->table('hostel_category')->where('name', '=', $request->name)->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {

                    // insert data
                    $query = $conn->table('hostel_category')->insert([
                        'name' => $request->name,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'Hostel Category has been successfully saved');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addHostelCategory');
        }
    }
    // getHostelCategoryList
    public function getHostelCategoryList(Request $request)
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
                $HostelCategoryDetails = $conn->table('hostel_category')->get();
                return $this->successResponse($HostelCategoryDetails, 'Hostel Category record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHostelCategoryList');
        }
    }
    // get HostelCategory row details
    public function getHostelCategoryDetails(Request $request)
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
                $HostelCategoryDetails = $conn->table('hostel_category')->where('id', $id)->first();
                return $this->successResponse($HostelCategoryDetails, 'Hostel Category row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHostelCategoryDetails');
        }
    }
    // update HostelCategory
    public function updateHostelCategory(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($conn->table('hostel_category')->where([['name', '=', $request->name], ['id', '!=', $id]])->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {
                    // update data
                    $query = $conn->table('hostel_category')->where('id', $id)->update([
                        'name' => $request->name,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Hostel Category Details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateHostelCategory');
        }
    }
    // delete HostelCategory
    public function deleteHostelCategory(Request $request)
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
                $query = $conn->table('hostel_category')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Hostel Category have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteHostelCategory');
        }
    }
    // addHostelRoom
    public function addHostelRoom(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'hostel_id' => 'required',
                'no_of_beds' => 'required',
                'block' => 'required',
                'floor' => 'required',
                'bed_fee' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($conn->table('hostel_room')->where([['name', $request->name], ['block', $request->block]])->count() > 0) {
                    return $this->send422Error('Room Already Exist', ['error' => 'Room Already Exist']);
                } else {
                    // insert data
                    $query = $conn->table('hostel_room')->insert([
                        'name' => $request->name,
                        'hostel_id' => $request->hostel_id,
                        'no_of_beds' => $request->no_of_beds,
                        'block' => $request->block,
                        'floor' => $request->floor,
                        'bed_fee' => $request->bed_fee,
                        'remarks' => $request->remarks,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'Hostel Room has been successfully saved');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addHostelRoom');
        }
    }
    // getHostelRoomList
    public function getHostelRoomList(Request $request)
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
                $HostelRoomDetails = $conn->table('hostel_room')->select('hostel_room.*', 'hostel.name as hostel', 'hostel_block.block_name as block', 'hostel_floor.floor_name as floor')
                    ->leftJoin('hostel', 'hostel_room.hostel_id', '=', 'hostel.id')
                    ->leftJoin('hostel_block', 'hostel_room.block', '=', 'hostel_block.id')
                    ->leftJoin('hostel_floor', 'hostel_room.floor', '=', 'hostel_floor.id')
                    ->get();
                return $this->successResponse($HostelRoomDetails, 'Hostel Room record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHostelRoomList');
        }
    }
    // get HostelRoom row details
    public function getHostelRoomDetails(Request $request)
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
                $HostelRoomDetails = $conn->table('hostel_room')->where('id', $id)->first();
                return $this->successResponse($HostelRoomDetails, 'Hostel Room row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHostelRoomDetails');
        }
    }
    // update HostelRoom
    public function updateHostelRoom(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'hostel_id' => 'required',
                'no_of_beds' => 'required',
                'block' => 'required',
                'floor' => 'required',
                'bed_fee' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($conn->table('hostel_room')->where([['name', '=', $request->name], ['block', $request->block], ['id', '!=', $id]])->count() > 0) {
                    return $this->send422Error('Room Already Exist', ['error' => 'Room Already Exist']);
                } else {
                    // update data
                    $query = $conn->table('hostel_room')->where('id', $id)->update([
                        'name' => $request->name,
                        'hostel_id' => $request->hostel_id,
                        'no_of_beds' => $request->no_of_beds,
                        'block' => $request->block,
                        'floor' => $request->floor,
                        'bed_fee' => $request->bed_fee,
                        'remarks' => $request->remarks,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Hostel Room Details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateHostelRoom');
        }
    }
    // delete HostelRoom
    public function deleteHostelRoom(Request $request)
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
                $query = $conn->table('hostel_room')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Hostel Room have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteHostelRoom');
        }
    }
    // addHostel
    public function addHostel(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($conn->table('hostel')->where('name', '=', $request->name)->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {

                    $watchman = NULL;
                    if (!empty($request->watchman)) {
                        $watchman =  implode(",", $request->watchman);
                    }
                    // insert data
                    $query = $conn->table('hostel')->insert([
                        'name' => $request->name,
                        'category_id' => $request->category,
                        'watchman' => $watchman,
                        'address' => $request->address,
                        'remarks' => $request->remarks,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'Hostel has been successfully saved');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addHostel');
        }
    }
    // get Hostel List
    public function getHostelList(Request $request)
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
                $Conn = $this->createNewConnection($request->branch_id);
                // get data
                $Hostel = $Conn->table('hostel')->select('hostel_category.name as category', 'hostel.*', DB::raw("GROUP_CONCAT(DISTINCT  s.first_name, ' ', s.last_name) as watchman"))
                    ->leftJoin('hostel_category', 'hostel.category_id', '=', 'hostel_category.id')
                    ->leftJoin("staffs as s", DB::raw("FIND_IN_SET(s.id,hostel.watchman)"), ">", DB::raw("'0'"))
                    ->groupBy('hostel.id')
                    ->get();
                return $this->successResponse($Hostel, 'Hostel record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHostelList');
        }
    }
    // get Hostel row details
    public function getHostelDetails(Request $request)
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
                $HostelDetails = $conn->table('hostel')->where('id', $id)->first();
                return $this->successResponse($HostelDetails, 'Hostel row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHostelDetails');
        }
    }
    // update Hostel
    public function updateHostel(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($conn->table('hostel')->where([['name', '=', $request->name], ['id', '!=', $id]])->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {
                    $watchman = NULL;
                    if (!empty($request->watchman)) {
                        $watchman =  implode(",", $request->watchman);
                    }
                    // update data
                    $query = $conn->table('hostel')->where('id', $id)->update([
                        'name' => $request->name,
                        'category_id' => $request->category,
                        'watchman' => $watchman,
                        'address' => $request->address,
                        'remarks' => $request->remarks,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Hostel Details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateHostel');
        }
    }
    // delete Hostel
    public function deleteHostel(Request $request)
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
                $query = $conn->table('hostel')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Hostel have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteHostel');
        }
    }
    // floor By Block
    public function floorByBlock(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'block_id' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Conn = $this->createNewConnection($request->branch_id);
                // get data
                $block_id = $request->block_id;
                $floor = $Conn->table('hostel_floor as hf')->select('hf.id', 'hf.floor_name')
                    ->join('hostel_block as hb', 'hf.block_id', '=', 'hb.id')
                    ->where('hf.block_id', $block_id)
                    ->get();
                // return $floor;
                return $this->successResponse($floor, 'Floor record fetched successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in floorByBlock');
        }
    }
    // add HostelBlock
    public function addHostelBlock(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'block_name' => 'required',
                'block_warden' => 'required',
                'total_floor' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);


                $block_warden = NULL;
                if (!empty($request->block_warden)) {
                    $block_warden =  implode(",", $request->block_warden);
                }


                $block_leader = NULL;
                if (!empty($request->block_leader)) {
                    $block_leader =  implode(",", $request->block_leader);
                }

                // insert data
                $query = $conn->table('hostel_block')->insert([
                    'block_name' => $request->block_name,
                    'block_warden' => $block_warden,
                    'total_floor' => $request->total_floor,
                    'block_leader' => $block_leader,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Hostel Block has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addHostelBlock');
        }
    }
    // getHostelBlockList
    public function getHostelBlockList(Request $request)
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

                $hostelBlockDetails = $conn->table('hostel_block as hb')->select('hb.*', DB::raw("GROUP_CONCAT(DISTINCT  s.first_name, ' ', s.last_name) as block_warden"), DB::raw("GROUP_CONCAT(DISTINCT  st.first_name, ' ', st.last_name) as block_leader"))
                    ->leftJoin("staffs as s", DB::raw("FIND_IN_SET(s.id,hb.block_warden)"), ">", DB::raw("'0'"))
                    ->leftJoin("students as st", DB::raw("FIND_IN_SET(st.id,hb.block_leader)"), ">", DB::raw("'0'"))
                    ->groupBy('hb.id')
                    ->get();
                return $this->successResponse($hostelBlockDetails, 'Hostel Block record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHostelBlockList');
        }
    }
    // get HostelBlock row details
    public function getHostelBlockDetails(Request $request)
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
                $hostelBlockDetails = $conn->table('hostel_block')->where('id', $id)->first();
                return $this->successResponse($hostelBlockDetails, 'Hostel Block row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHostelBlockDetails');
        }
    }
    // update HostelBlock
    public function updateHostelBlock(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'block_name' => 'required',
                'block_warden' => 'required',
                'total_floor' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);

                $block_warden = NULL;
                if (!empty($request->block_warden)) {
                    $block_warden =  implode(",", $request->block_warden);
                }


                $block_leader = NULL;
                if (!empty($request->block_leader)) {
                    $block_leader =  implode(",", $request->block_leader);
                }
                // update data
                $query = $conn->table('hostel_block')->where('id', $id)->update([
                    'block_name' => $request->block_name,
                    'block_warden' => $block_warden,
                    'total_floor' => $request->total_floor,
                    'block_leader' => $block_leader,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Hostel Block Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateHostelBlock');
        }
    }
    // delete HostelBlock
    public function deleteHostelBlock(Request $request)
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
                $query = $conn->table('hostel_block')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Hostel Block have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteHostelBlock');
        }
    }
    // add HostelFloor
    public function addHostelFloor(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'floor_name' => 'required',
                'block_id' => 'required',
                'floor_warden' => 'required',
                'total_room' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);

                $floor_warden = NULL;
                if (!empty($request->floor_warden)) {
                    $floor_warden =  implode(",", $request->floor_warden);
                }

                $floor_leader = NULL;
                if (!empty($request->floor_leader)) {
                    $floor_leader =  implode(",", $request->floor_leader);
                }

                // insert data
                $query = $conn->table('hostel_floor')->insert([
                    'floor_name' => $request->floor_name,
                    'block_id' => $request->block_id,
                    'floor_warden' => $floor_warden,
                    'floor_leader' => $floor_leader,
                    'total_room' => $request->total_room,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Hostel Floor has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addHostelFloor');
        }
    }
    // getHostelFloorList
    public function getHostelFloorList(Request $request)
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
                $hostelFloorDetails = $conn->table('hostel_floor as hf')
                    ->select('hf.*', 'b.block_name as block_id', DB::raw("GROUP_CONCAT(DISTINCT  s.first_name, ' ', s.last_name) as floor_warden"), DB::raw("GROUP_CONCAT(DISTINCT  st.first_name, ' ', st.last_name) as floor_leader"))
                    ->leftJoin("staffs as s", DB::raw("FIND_IN_SET(s.id,hf.floor_warden)"), ">", DB::raw("'0'"))
                    ->leftJoin("students as st", DB::raw("FIND_IN_SET(st.id,hf.floor_leader)"), ">", DB::raw("'0'"))
                    ->leftJoin('hostel_block as b', 'hf.block_id', '=', 'b.id')
                    ->groupBy('hf.id')
                    ->get();
                return $this->successResponse($hostelFloorDetails, 'Hostel Floor record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHostelFloorList');
        }
    }
    // get HostelFloor row details
    public function getHostelFloorDetails(Request $request)
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
                $hostelFloorDetails = $conn->table('hostel_floor')->where('id', $id)->first();
                return $this->successResponse($hostelFloorDetails, 'Hostel Floor row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getHostelFloorDetails');
        }
    }
    // update HostelFloor
    public function updateHostelFloor(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'floor_name' => 'required',
                'block_id' => 'required',
                'floor_warden' => 'required',
                'total_room' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);

                $floor_warden = NULL;
                if (!empty($request->floor_warden)) {
                    $floor_warden =  implode(",", $request->floor_warden);
                }

                $floor_leader = NULL;
                if (!empty($request->floor_leader)) {
                    $floor_leader =  implode(",", $request->floor_leader);
                }

                // update data
                $query = $conn->table('hostel_floor')->where('id', $id)->update([
                    'floor_name' => $request->floor_name,
                    'block_id' => $request->block_id,
                    'floor_warden' => $floor_warden,
                    'floor_leader' => $floor_leader,
                    'total_room' => $request->total_room,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Hostel Floor Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateHostelFloor');
        }
    }
    // delete HostelFloor
    public function deleteHostelFloor(Request $request)
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
                $query = $conn->table('hostel_floor')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Hostel Floor have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteHostelFloor');
        }
    }


    // room By Hostel
    public function roomByHostel(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'hostel_id' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Conn = $this->createNewConnection($request->branch_id);
                // get data
                $hostel_id = $request->hostel_id;
                $hostel = $Conn->table('hostel_room')->select('hostel_room.id as room_id', 'hostel_room.name as room_name')
                    ->where('hostel_room.hostel_id', $hostel_id)
                    ->get();
                return $this->successResponse($hostel, 'Room record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in roomByHostel');
        }
    }

    // vehicle By Route
    public function vehicleByRoute(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'token' => 'required',
                'route_id' => 'required',
            ]);


            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $Conn = $this->createNewConnection($request->branch_id);
                // get data
                $route_id = $request->route_id;
                $route = $Conn->table('transport_assign')->select('transport_vehicle.id as vehicle_id', 'transport_vehicle.vehicle_no')
                    ->join('transport_vehicle', 'transport_assign.vehicle_id', '=', 'transport_vehicle.id')
                    ->where('transport_assign.route_id', $route_id)
                    ->get();
                // return $route;
                return $this->successResponse($route, 'Vehicle record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in vehicleByRoute');
        }
    }


    // add TransportRoute
    public function addTransportRoute(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'start_place' => 'required',
                'stop_place' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($conn->table('transport_route')->where('name', '=', $request->name)->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {
                    // insert data
                    $query = $conn->table('transport_route')->insert([
                        'name' => $request->name,
                        'start_place' => $request->start_place,
                        'stop_place' => $request->stop_place,
                        'remarks' => $request->remarks,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'Transport Route has been successfully saved');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addTransportRoute');
        }
    }
    // getTransportRouteList
    public function getTransportRouteList(Request $request)
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
                $transportRouteDetails = $conn->table('transport_route')->get();
                return $this->successResponse($transportRouteDetails, 'Transport Route record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getTransportRouteList');
        }
    }
    // get TransportRoute row details
    public function getTransportRouteDetails(Request $request)
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
                $transportRouteDetails = $conn->table('transport_route')->where('id', $id)->first();
                return $this->successResponse($transportRouteDetails, 'Transport Route row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getTransportRouteDetails');
        }
    }
    // update TransportRoute
    public function updateTransportRoute(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'start_place' => 'required',
                'stop_place' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // check exist name
                if ($conn->table('transport_route')->where([['name', '=', $request->name], ['id', '!=', $id]])->count() > 0) {
                    return $this->send422Error('Name Already Exist', ['error' => 'Name Already Exist']);
                } else {
                    // update data
                    $query = $conn->table('transport_route')->where('id', $id)->update([
                        'name' => $request->name,
                        'start_place' => $request->start_place,
                        'stop_place' => $request->stop_place,
                        'remarks' => $request->remarks,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if ($query) {
                        return $this->successResponse($success, 'Transport Route Details have Been updated');
                    } else {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateTransportRoute');
        }
    }
    // delete TransportRoute
    public function deleteTransportRoute(Request $request)
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
                $query = $conn->table('transport_route')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Transport Route have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteTransportRoute');
        }
    }

    // add TransportVehicle
    public function addTransportVehicle(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'vehicle_no' => 'required',
                'capacity' => 'required',
                'insurance_renewal' => 'required',
                'driver_phone' => 'required',
                'driver_name' => 'required',
                'driver_license' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // insert data
                $query = $conn->table('transport_vehicle')->insert([
                    'vehicle_no' => $request->vehicle_no,
                    'capacity' => $request->capacity,
                    'insurance_renewal' => $request->insurance_renewal,
                    'driver_phone' => $request->driver_phone,
                    'driver_name' => $request->driver_name,
                    'driver_license' => $request->driver_license,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Transport Vehicle has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addTransportVehicle');
        }
    }
    // getTransportVehicleList
    public function getTransportVehicleList(Request $request)
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
                $transportVehicleDetails = $conn->table('transport_vehicle')->get();
                return $this->successResponse($transportVehicleDetails, 'Transport Vehicle record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getTransportVehicleList');
        }
    }
    // get TransportVehicle row details
    public function getTransportVehicleDetails(Request $request)
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
                $transportVehicleDetails = $conn->table('transport_vehicle')->where('id', $id)->first();
                return $this->successResponse($transportVehicleDetails, 'Transport Vehicle row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getTransportVehicleDetails');
        }
    }
    // update TransportVehicle
    public function updateTransportVehicle(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'vehicle_no' => 'required',
                'capacity' => 'required',
                'insurance_renewal' => 'required',
                'driver_phone' => 'required',
                'driver_name' => 'required',
                'driver_license' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                // update data
                $query = $conn->table('transport_vehicle')->where('id', $id)->update([
                    'vehicle_no' => $request->vehicle_no,
                    'capacity' => $request->capacity,
                    'insurance_renewal' => $request->insurance_renewal,
                    'driver_phone' => $request->driver_phone,
                    'driver_name' => $request->driver_name,
                    'driver_license' => $request->driver_license,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Transport Vehicle Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateTransportVehicle');
        }
    }
    // delete TransportVehicle
    public function deleteTransportVehicle(Request $request)
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
                $query = $conn->table('transport_vehicle')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Transport Vehicle have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteTransportVehicle');
        }
    }
    // add TransportAssign
    public function addTransportAssign(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'route_id' => 'required',
                'stoppage_id' => 'required',
                'vehicle_id' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);

                if ($conn->table('transport_assign')->where([['route_id', '=', $request->route_id], ['stoppage_id', '=', $request->stoppage_id], ['vehicle_id', '=', $request->vehicle_id]])->count() > 0) {
                    return $this->send422Error('Vehicle Already Assigned', ['error' => 'Vehicle Already Assigned']);
                } else {
                    // insert data
                    $query = $conn->table('transport_assign')->insert([
                        'route_id' => $request->route_id,
                        'stoppage_id' => $request->stoppage_id,
                        'vehicle_id' => $request->vehicle_id,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'Transport Assign has been successfully saved');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addTransportAssign');
        }
    }
    // getTransportAssignList
    public function getTransportAssignList(Request $request)
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
                $transportAssignDetails = $conn->table('transport_assign as ta')
                    ->select('ta.*', 'tr.name as route_name', 'tv.vehicle_no', 'ts.stop_position')
                    ->join('transport_route as tr', 'ta.route_id', '=', 'tr.id')
                    ->join('transport_vehicle as tv', 'ta.vehicle_id', '=', 'tv.id')
                    ->join('transport_stoppage as ts', 'ta.stoppage_id', '=', 'ts.id')->get();
                return $this->successResponse($transportAssignDetails, 'Transport Assign record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getTransportAssignList');
        }
    }
    // get TransportAssign row details
    public function getTransportAssignDetails(Request $request)
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
                $transportAssignDetails = $conn->table('transport_assign')->where('id', $id)->first();
                return $this->successResponse($transportAssignDetails, 'Transport Assign row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getTransportAssignDetails');
        }
    }
    // update TransportAssign
    public function updateTransportAssign(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'route_id' => 'required',
                'stoppage_id' => 'required',
                'vehicle_id' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);

                // update data
                $query = $conn->table('transport_assign')->where('id', $id)->update([
                    'route_id' => $request->route_id,
                    'stoppage_id' => $request->stoppage_id,
                    'vehicle_id' => $request->vehicle_id,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Transport Assign Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateTransportAssign');
        }
    }
    // delete TransportAssign
    public function deleteTransportAssign(Request $request)
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
                $query = $conn->table('transport_assign')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Transport Assign have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteTransportAssign');
        }
    }



    // add TransportStoppage
    public function addTransportStoppage(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'route_fare' => 'required',
                'stop_position' => 'required',
                'stop_time' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $conn = $this->createNewConnection($request->branch_id);

                // insert data
                $query = $conn->table('transport_stoppage')->insert([
                    'stop_position' => $request->stop_position,
                    'stop_time' => $request->stop_time,
                    'route_fare' => $request->route_fare,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Transport Stoppage has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in addTransportStoppage');
        }
    }
    // getTransportStoppageList
    public function getTransportStoppageList(Request $request)
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
                $transportStoppageDetails = $conn->table('transport_stoppage')->get();
                return $this->successResponse($transportStoppageDetails, 'Transport Stoppage record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getTransportStoppageList');
        }
    }
    // get TransportStoppage row details
    public function getTransportStoppageDetails(Request $request)
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
                $transportStoppageDetails = $conn->table('transport_stoppage')->where('id', $id)->first();
                return $this->successResponse($transportStoppageDetails, 'Transport Stoppage row fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getTransportStoppageDetails');
        }
    }
    // update TransportStoppage
    public function updateTransportStoppage(Request $request)
    {
        try {
            $id = $request->id;
            $validator = \Validator::make($request->all(), [
                'route_fare' => 'required',
                'stop_position' => 'required',
                'stop_time' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);

                // update data
                $query = $conn->table('transport_stoppage')->where('id', $id)->update([
                    'stop_position' => $request->stop_position,
                    'stop_time' => $request->stop_time,
                    'route_fare' => $request->route_fare,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Transport Stoppage Details have Been updated');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in updateTransportStoppage');
        }
    }
    // delete TransportStoppage
    public function deleteTransportStoppage(Request $request)
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
                $query = $conn->table('transport_stoppage')->where('id', $id)->delete();

                $success = [];
                if ($query) {
                    return $this->successResponse($success, 'Transport Stoppage have been deleted successfully');
                } else {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in deleteTransportStoppage');
        }
    }

    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
