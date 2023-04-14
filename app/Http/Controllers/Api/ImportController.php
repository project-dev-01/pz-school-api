<?php

namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Helper;
// base controller add
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\BaseController as BaseController;
// encrypt and decrypt
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\User;
use App\Models\Role;
use DateTime;
// notifications
use App\Notifications\ReliefAssignment;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ImportController extends BaseController
{
    // import Csv Employee 
    public function importCsvEmployee(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'file' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            // create new connection
            $Connection = $this->createNewConnection($request->branch_id);

            // $base64 = base64_decode($request->file);
            $base64 = base64_decode($request->file);
            // $file = $request->file('file');
            // File Details 

            $filename = $request->fileName;
            $extension = $request->extension;
            $tempPath = $request->tempPath;
            $fileSize = $request->fileSize;
            $mimeType = $request->mimeType;

            header('Content-type: text/plain; charset=utf-8');
            // Valid File Extensions
            $valid_extension = array("csv");
            // 2MB in Bytes
            $maxFileSize = 2097152;
            // Check file extension
            if (in_array(strtolower($extension), $valid_extension)) {
                // Check file size
                if ($fileSize <= $maxFileSize) {


                    $file = base_path() . '/uploads/delete/' . $filename;
                    $suc = file_put_contents($file, $base64);
                    // File upload location
                    $location = 'uploads/delete/';
                    // Upload file
                    // $file->move($location, $filename);
                    // Import CSV to Database
                    // $filepath = public_path($location."/".$filename);
                    $filepath = $location . "/" . $filename;
                    // $file = fopen($filename, "r");
                    // if ($handle) {
                    //     // Use $handle
                    // } else {
                    //     die("Unable to open file");
                    // }
                    // Reading file
                    $file = fopen($filepath, "r");
                    $importData_arr = array();
                    $i = 0;
                    while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
                        $num = count($filedata);
                        // Skip first row (Remove below comment if you want to skip the first row)
                        if ($i == 0) {
                            $i++;
                            continue;
                        }
                        for ($c = 0; $c < $num; $c++) {
                            $importData_arr[$i][] = $filedata[$c];
                        }
                        $i++;
                    }
                    // exit();
                    fclose($file);
                    // dummyemail

                    $dummyInc = 1;
                    // Insert to MySQL database
                    foreach ($importData_arr as $importData) {


                        $dummyInc++;
                        
                        $first_name = $importData[1];
                        $last_name = $importData[2];
                        $gender = $importData[3];
                        $short_name = $importData[4];
                        $passport = $importData[7];
                        $nric_number = $importData[8];
                        $date_of_birth = $importData[9];
                        $mobile_number = $importData[10];
                        $employment_status = $importData[11];
                        $country = $importData[12];
                        $state = $importData[13];
                        $city = $importData[14];
                        $zip_code = $importData[15];
                        $address_1 = $importData[16];
                        $address_2 = $importData[17];
                        $joining_date = $importData[19];
                        $salary_grade = $importData[23];
                        $email = $importData[27];
                        $password = $importData[28];
                        $confirm_password = $importData[29];
                        $height = $importData[30];
                        $weight = $importData[31];
                        $allergy = $importData[32];
                        $blood_group = $importData[33];
                        $bank_name = $importData[34];
                        $holder_name = $importData[35];
                        $bank_branch = $importData[36];
                        $bank_address = $importData[37];
                        $ifsc_code = $importData[38];
                        $account_no = $importData[39];
                        $twitter_url = $importData[40];
                        $facebook_url = $importData[41];
                        $linkedin_url = $importData[42];

                        $role = $importData[18];


                        $user_data = [
                            'email' => $email,
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'mobile_number' => $mobile_number,
                            'password' => $password,
                            'role' => $role,
                            'confirm_password' => $confirm_password,
                        ];
                    
                        $user_rules = [
                            'email' => 'required',
                            'first_name' => 'required',
                            'role' => 'required',
                            'mobile_number' => 'required',
                            'password' => 'required|min:6',
                            'confirm_password' => 'required|same:password|min:6'
                        ];
                        $userValidator = \Validator::make( $user_data, $user_rules );
                        if($userValidator->passes()) {
                            $dynamic_row = [ 
                                ['table_name'=>'religions','number'=>'5'],
                                ['table_name'=>'races','number'=>'6'],
                                ['table_name'=>'role','number'=>'18'],
                                ['table_name'=>'staff_designations','number'=>'20'],
                                ['table_name'=>'staff_departments','number'=>'21'],
                                ['table_name'=>'staff_positions','number'=>'22'],
                                ['table_name'=>'staff_categories','number'=>'24'],
                                ['table_name'=>'qualifications','number'=>'25'],
                                ['table_name'=>'stream_types','number'=>'26']
                            ];
    
                            $dynamic_data = [];
                            foreach($dynamic_row as $row) {
                                $number = $row['number'];
                                $column = [
                                    'token' => $request->token,
                                    'branch_id' => $request->branch_id,
                                    'name' => $importData[$number],
                                    'table_name' => $row['table_name']
                                ];
                                $row = $this->getLikeColumnName($column);
                                $dynamic_data[$number] = $row;
                            }
    
                            $employee_data = [
                                'first_name' => $first_name,
                                'last_name' => $last_name,
                                'gender' => $gender,
                                'short_name' => $short_name,
                                'religion' => $dynamic_data[5],
                                'race' => $dynamic_data[6],
                                'passport' => $passport,
                                'nric_number' => $nric_number,
                                'birthday' => $date_of_birth,
                                'mobile_no' => $mobile_number,
                                'employment_status' => $employment_status,
                                'country' => $country,
                                'state' => $state,
                                'city' => $city,
                                'post_code' => $zip_code,
                                'present_address' => $address_1,
                                'permanent_address' => $address_2,
                                'joining_date' => $joining_date,
                                'designation_id' => $dynamic_data[20],
                                'department_id' => $dynamic_data[21],
                                'staff_position' => $dynamic_data[22],
                                'salary_grade' => $salary_grade,
                                'staff_category' => $dynamic_data[24],
                                'staff_qualification_id' => $dynamic_data[25],
                                'stream_type_id' => $dynamic_data[26],
                                'email' => $email,
                                'height' => $height,
                                'weight' => $weight,
                                'allergy' => $allergy,
                                'blood_group' => $blood_group,
                                'twitter_url' => $twitter_url,
                                'facebook_url' => $facebook_url,
                                'linkedin_url' => $linkedin_url,
                                'created_at' => date("Y-m-d H:i:s")
                            ];
                            if ($Connection->table('staffs')->where('email', '=', $email)->count() < 1) {
                                $staffId = $Connection->table('staffs')->insertGetId($employee_data);

                                $bank_data = [
                                    'bank_name' => $bank_name,
                                    'holder_name' => $holder_name,
                                    'bank_branch' => $bank_branch,
                                    'bank_address'    => $bank_address,
                                    'ifsc_code' => $ifsc_code,
                                    'account_no' => $account_no
                                ];
                                $bank_rules = [
                                    'bank_name' => 'required',
                                    'holder_name' => 'required',
                                    'bank_branch' => 'required',
                                    'bank_address' => 'required',
                                    'account_no' => 'required',
                                    'ifsc_code' => 'required'
                                ];
                                $bankValidator = \Validator::make( $bank_data, $bank_rules );
                                // add bank details
                                if ($bankValidator->passes()) {
                                    $bank = $Connection->table('staff_bank_accounts')->insert($bank_data);
                                }
    
                                if ($staffId) {
                                    $user = new User();
                                    $user->name = (isset($first_name) ? $first_name : "") . " " . (isset($last_name) ? $last_name : "");
                                    $user->user_id = $staffId;
                                    $user->role_id = $dynamic_data[18];
                                    $user->branch_id = $request->branch_id;
                                    $user->email = $email;
                                    $user->status = "0";
                                    $user->password_changed_at = date("Y-m-d H:i:s");
                                    $user->password = bcrypt($password);
                                    $query = $user->save();
                                }
                            } 
                            
                            
                        }
                        
                        // check exist name
                        // if ($Connection->table($table)->where('name', '=', $name)->count() < 1) {
                        //     // insert data
                        //     $query = $Connection->table($table)->insert($data);
                        // }
                    }
                    if (\File::exists(base_path($filepath))) {
                        \File::delete(base_path($filepath));
                    }
                    return $this->successResponse([], 'Import Successful');
                } else {
                    return $this->send422Error('Validation error.', ['error' => 'File too large. File must be less than 2MB.']);
                }
            } else {
                return $this->send422Error('Validation error.', ['error' => 'Invalid File Extension']);
            }
        }
    }

    
    // getLikeColumnName
    public function getLikeColumnName($request)
    {
        // return $request;
        // create new connection
        $conn = $this->createNewConnection($request['branch_id']);
        // get dat
        $table_name = $request['table_name'];
        $name = explode(',',$request['name']);

        if ($request['table_name']=="role") {
            $data = DB::table('roles')->select(DB::raw("group_concat(id) as id"))->whereIn('role_name',$name)->get();
        } else {
            $data = $conn->table($table_name)->select(DB::raw("group_concat(id) as id"))->whereIn('name',$name )->get();
        }

        $response = "";
        if ($data) {
            $response = $data[0]->id;
        }
        return $response;
    }

    // getLikeColumnName
    // public function getLikeColumnName(Request $request)
    // {
    //     // return $request;
    //     // create new connection
    //     $conn = $this->createNewConnection($request['branch_id']);
    //     // get dat
    //     $table_name = $request['table_name'];
    //     if($request['type']=="1"){

    //         $name = $request['name'];
    //         $data = $conn->table($table_name)->select('id')
    //         ->when($name, function ($query, $name) {
    //             return $query->where('name', 'like', '%' . $name . '%');
    //         })
    //         ->first();
    //     }else{

    //         $name = explode(',',$request['name']);
    //         $data = $conn->table($table_name)->select(DB::raw("group_concat(id) as id"))->whereIn('name',$name )->get();
    //         // return $data;
    //     }
    //     $response = "";
    //     if($data){
    //         return $data[0];
    //         $response = $data[0]['id'];
    //     }
    //     return $response;
    // }
    
}
