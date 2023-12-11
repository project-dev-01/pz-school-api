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
use File;

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

                    $path = base_path().'/public/' . $request->branch_id . '/uploads/';
                    $base64 = base64_decode($request->file);
                    File::ensureDirectoryExists($path);
                    $file = $path . $filename;
                    $picture = file_put_contents($file, $base64);
                    // Upload file
                    // Import CSV to Database
                    $filepath = $path . "/" . $filename;
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
                            
                            if (DB::table('users')->where([['email', '=', $email],['branch_id', '=', $request->branch_id]])->count() < 1) {
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
                            
                            
                        }
                        
                        // check exist name
                        // if ($Connection->table($table)->where('name', '=', $name)->count() < 1) {
                        //     // insert data
                        //     $query = $Connection->table($table)->insert($data);
                        // }
                    }
                    if (\File::exists($filepath)) {
                        \File::delete($filepath);
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
    // import Csv Parents
    public function importCsvParents(Request $request)
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

            $filename = $request->fileName;
            $extension = $request->extension;
            $tempPath = $request->tempPath;
            $fileSize = $request->fileSize;
            $mimeType = $request->mimeType;
           
            // File Details 
            header('Content-type: text/plain; charset=utf-8');
            // Valid File Extensions
            $valid_extension = array("csv");
            // 2MB in Bytes
            $maxFileSize = 2097152;
            // return $maxFileSize;
            // Check file extension
            if (in_array(strtolower($extension), $valid_extension)) {
                // Check file size
                if ($fileSize <= $maxFileSize) {
                    // File upload location
                    $path = base_path().'/public/' . $request->branch_id . '/uploads/';
                    $base64 = base64_decode($request->file);
                    File::ensureDirectoryExists($path);
                    $file = $path . $filename;
                    $picture = file_put_contents($file, $base64);
                    // Upload file
                    // Import CSV to Database
                    $filepath = $path . "/" . $filename;
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
                        // return $importData[1];
                        $dummyInc++;
                        // insert data
                        $first_name =  isset($importData[1]) ? $importData[1] : "";
                        $last_name =  isset($importData[2]) ? $importData[2] : "";
                        $gender =  isset($importData[3]) ? $importData[3] : "";
                        $dob = date("Y-m-d", strtotime($importData[4]));
                        $passport = isset($importData[5]) ? Crypt::encryptString($importData[5]) : "";
                        $nric = isset($importData[6]) ? Crypt::encryptString($importData[6]) : "";
                        $blood_group =  isset($importData[7]) ? $importData[7] : "";
                        $mobile_no = isset($importData[8]) ? Crypt::encryptString($importData[8]) : "";
                        $occupation = isset($importData[12]) ? $importData[12] : "";
                        $income = isset($importData[13]) ? $importData[13] : "";
                        $country = isset($importData[14]) ? $importData[14] : "";
                        $state = isset($importData[15]) ? $importData[15] : "";
                        $city = isset($importData[16]) ? $importData[16] : "";
                        $zip_code = isset($importData[17]) ? $importData[17] : "";
                        $address_1 = isset($importData[18]) ? Crypt::encryptString($importData[18]) : "";
                        $address_2 = isset($importData[19]) ? Crypt::encryptString($importData[19]) : "";
                        $email = $importData[20];
                        $password =  $importData[21];
                        $confirm_password =  $importData[22];
                        $twitter_url = $importData[23];
                        $facebook_url = $importData[24];
                        $linkedin_url = $importData[25];

                        $role = "5";
                        
                        $user_data = [
                            'email' => $email,
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'mobile_number' => $mobile_no,
                            'occupation' => $occupation,
                            'password' => $password,
                            'role' => $role,
                            'confirm_password' => $confirm_password,
                        ];
                    
                        $user_rules = [
                            'email' => 'required',
                            'occupation' => 'required',
                            'first_name' => 'required',
                            'role' => 'required',
                            'mobile_number' => 'required',
                            'password' => 'required|min:6',
                            'confirm_password' => 'required|same:password|min:6'
                        ];

                        $userValidator = \Validator::make( $user_data, $user_rules );
                        if($userValidator->passes()) {
                            $dynamic_row = [ 
                                ['table_name'=>'religions','number'=>'9'],
                                ['table_name'=>'races','number'=>'10'],
                                ['table_name'=>'educations','number'=>'11'],
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
    
                            $parent_data = [
                                'first_name' => $first_name,
                                'last_name' => $last_name,
                                'gender' => $gender,
                                'date_of_birth' => $dob,
                                'passport' => $passport,
                                'nric' => $nric,
                                'blood_group' => $blood_group,
                                'mobile_no' => $mobile_no,
                                'religion' => $dynamic_data[9],
                                'race' => $dynamic_data[10],
                                'education' => $dynamic_data[11],
                                'occupation' => $occupation,
                                'income' => $income,
                                'country' => $country,
                                'state' => $state,
                                'city' => $city,
                                'post_code' => $zip_code,
                                'address' => $address_1,
                                'address_2' => $address_2,
                                'email' => $email,
                                'twitter_url' => $twitter_url,
                                'facebook_url' => $facebook_url,
                                'linkedin_url' => $linkedin_url,
                                'status' => "0",
                                'created_at' => date("Y-m-d H:i:s")
                            ];
                            if (DB::table('users')->where([['email', '=', $email],['branch_id', '=', $request->branch_id]])->count() < 1) {
                                if ($Connection->table('parent')->where('email', '=', $email)->count() < 1) {
                                    $parentId = $Connection->table('parent')->insertGetId($parent_data);
                                    if ($parentId) {
                                        $user = new User();
                                        $user->name = (isset($first_name) ? $first_name : "") . " " . (isset($last_name) ? $last_name : "");
                                        $user->user_id = $parentId;
                                        $user->role_id = $role;
                                        $user->branch_id = $request->branch_id;
                                        $user->email = $email;
                                        $user->status = "0";
                                        $user->password_changed_at = date("Y-m-d H:i:s");
                                        $user->password = bcrypt($password);
                                        $query = $user->save();
                                    }
                                } 
                            }
                        }
                    }
                    
                    if (\File::exists($filepath)) {
                        \File::delete($filepath);
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

    // import Csv Students
    public function importCsvStudents(Request $request)
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

                    
                    // File upload location
                    $path = base_path().'/public/' . $request->branch_id . '/uploads/';
                    $base64 = base64_decode($request->file);
                    File::ensureDirectoryExists($path);
                    $file = $path . $filename;
                    $picture = file_put_contents($file, $base64);
                    // Upload file
                    // Import CSV to Database
                    $filepath = $path . "/" . $filename;
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
                        // dd($importData);
                        $first_name = $importData[1];
                        $last_name = $importData[2];
                        $gender = $importData[3];
                        $blood_group = $importData[4];
                        $date_of_birth = date("Y-m-d", strtotime($importData[5]));;
                        $passport = isset($importData[6]) ? Crypt::encryptString($importData[6]) : "";
                        $nric = isset($importData[7]) ? Crypt::encryptString($importData[7]) : "";
                        $mobile_no = isset($importData[10]) ? Crypt::encryptString($importData[10]) : "";
                        $country = $importData[11];
                        $state = $importData[12];
                        $city = $importData[13];
                        $zip_code = $importData[14];
                        $address_1 = isset($importData[15]) ? Crypt::encryptString($importData[15]) : "";
                        $address_2 = isset($importData[16]) ? Crypt::encryptString($importData[16]) : "";
                        $register_no = $importData[18];
                        $roll_no = $importData[19];
                        $admission_date = $importData[20];
                        $email = isset($importData[25]) ? $importData[25] : null;
                        $password = $importData[26];
                        $confirm_password = $importData[27];
                        $previous['school_name'] = $importData[32];
                        $previous['qualification'] = $importData[33];
                        $previous['remarks'] = $importData[34];
                        $previous_details = json_encode($previous);

                        $role = "6";
                        $user_data = [
                            'email' => $email,
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'mobile_number' => $mobile_no,
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
                                ['table_name'=>'religions','number'=>'8'],
                                ['table_name'=>'races','number'=>'9'],
                                ['table_name'=>'academic_year','number'=>'17'],
                                ['table_name'=>'classes','number'=>'21'],
                                ['table_name'=>'sections','number'=>'22'],
                                ['table_name'=>'session','number'=>'23'],
                                ['table_name'=>'semester','number'=>'24'],
                                ['table_name'=>'parent','number'=>'28'],
                                ['table_name'=>'parent','number'=>'29'],
                                ['table_name'=>'parent','number'=>'30'],
                                ['table_name'=>'relations','number'=>'31'],
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
                                // return $column;
                                $row = $this->getLikeColumnName($column);
                                $dynamic_data[$number] = $row;
                            }
                            $student_data = [
                                'first_name' => $first_name,
                                'last_name' => $last_name,
                                'gender' => $gender,
                                'blood_group' => $blood_group,
                                'birthday' => $date_of_birth,
                                'passport' => $passport,
                                'nric' => $nric,
                                'religion' => $dynamic_data[8],
                                'race' => $dynamic_data[9],
                                'mobile_no' => $mobile_no,
                                'country' => $country,
                                'state' => $state,
                                'city' => $city,
                                'post_code' => $zip_code,
                                'current_address' => $address_1,
                                'permanent_address' => $address_2,
                                'year' => $dynamic_data[17],
                                'register_no' => $register_no,
                                'roll_no' => $roll_no,
                                'admission_date' => $admission_date,
                                'email' => $email,
                                'father_id' => $dynamic_data[28],
                                'mother_id' => $dynamic_data[29],
                                'guardian_id' => $dynamic_data[30],
                                'relation' => $dynamic_data[31],
                                'previous_details' => $previous_details,
                                'status' => "0",
                                'created_at' => date("Y-m-d H:i:s")
                            ];
                            // return $dynamic_data;
                            
                            if (DB::table('users')->where([['email', '=', $email],['branch_id', '=', $request->branch_id]])->count() < 1) {
                                if ($Connection->table('students')->where('email', '=', $email)->count() < 1) {
                                    $studentId = $Connection->table('students')->insertGetId($student_data);

                                    $classDetails = [
                                        'student_id' => $studentId,
                                        'class_id' => $dynamic_data[21],
                                        'section_id' => $dynamic_data[22],
                                        'academic_session_id' => $dynamic_data[17],
                                        'roll' => $roll_no,
                                        'session_id' => isset($dynamic_data[23]) ? $dynamic_data[23] : 0,
                                        'semester_id' => isset($dynamic_data[23]) ? $dynamic_data[24] : 0,
                                    ];
                                    $Connection->table('enrolls')->insert($classDetails);

                                    if ($studentId) {
                                        $user = new User();
                                        $user->name = (isset($first_name) ? $first_name : "") . " " . (isset($last_name) ? $last_name : "");
                                        $user->user_id = $studentId;
                                        $user->role_id = $role;
                                        $user->branch_id = $request->branch_id;
                                        $user->email = $email;
                                        $user->status = "0";
                                        $user->password_changed_at = date("Y-m-d H:i:s");
                                        $user->password = bcrypt($password);
                                        $query = $user->save();
                                    }
                                }
                            } 
                        }
                    }
                    
                    if (\File::exists($filepath)) {
                        \File::delete($filepath);
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
        } else if ($request['table_name']=="parent") {
            $data = $conn->table($table_name)->select("id")->whereIn('first_name',$name)->orWhereIn('last_name',$name)->get();
        } else {
            $data = $conn->table($table_name)->select(DB::raw("group_concat(id) as id"))->whereIn('name',$name )->get();
        }

        // return $data;
        $response = "";
        if (!$data->isEmpty()) {
            if($data[0]->id != null){
                $response = $data[0]->id;
            }
        }
        return $response;
    }
    public function getPromotionDataBulk(Request $request)
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

                    
                    // File upload location
                    $path = base_path().'/public/' . $request->branch_id . '/uploads/';
                    $base64 = base64_decode($request->file);
                    File::ensureDirectoryExists($path);
                    $file = $path . $filename;
                    $picture = file_put_contents($file, $base64);
                    // Upload file
                    // Import CSV to Database
                    $filepath = $path . "/" . $filename;
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
                    foreach ($importData_arr as $importData) 
                    {
                        $dummyInc++;
                       //  dd($importData);
                        $student_name = $importData[1];
                        $student_number = $importData[2];
                        $current_attendance_no = $importData[3];
                        $promoted_attendance_no = $importData[10];

                        $role = "6";   
                        
                            $dynamic_row = [ 
                                ['table_name'=>'staff_departments','number'=>'5'],
                                ['table_name'=>'academic_year','number'=>'4'],
                                ['table_name'=>'classes','number'=>'6'],
                                ['table_name'=>'sections','number'=>'7'],
                                ['table_name'=>'session','number'=>'9'],
                                ['table_name'=>'semester','number'=>'8'],
                                ['table_name'=>'staff_departments','number'=>'12'],
                                ['table_name'=>'academic_year','number'=>'11'],
                                ['table_name'=>'classes','number'=>'13'],
                                ['table_name'=>'sections','number'=>'14'],
                                ['table_name'=>'session','number'=>'16'],
                                ['table_name'=>'semester','number'=>'15'],
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
                                // return $column;
                                $row = $this->getLikeColumnName($column);
                                $dynamic_data[$number] = $row;
                            }
                               
                                $studentId = $Connection->table('students')->select('id')->where('roll_no', '=', $student_number)->first();
                               
                                if (!empty($studentId)) 
                                {
                                   
                                    $classDetails = [
                                        'student_id' =>  $studentId->id,
                                        'department_id' => $dynamic_data[5],
                                        'class_id' => $dynamic_data[6],
                                        'section_id' => $dynamic_data[7],
                                        'academic_session_id' => $dynamic_data[4],
                                        'session_id' => isset($dynamic_data[9]) ? $dynamic_data[9] : 0,
                                        'semester_id' => isset($dynamic_data[8]) ? $dynamic_data[8] : 0,
                                        'attendance_no' => $current_attendance_no,
                                        'promoted_department_id' => $dynamic_data[12],
                                        'promoted_class_id' => $dynamic_data[13],
                                        'promoted_section_id' => $dynamic_data[14],
                                        'promoted_academic_session_id' => $dynamic_data[11],
                                        'roll' => $student_number,
                                        'promoted_session_id' => isset($dynamic_data[16]) ? $dynamic_data[16] : 0,
                                        'promoted_semester_id' => isset($dynamic_data[15]) ? $dynamic_data[15] : 0,
                                        'promoted_attendance_no' => $promoted_attendance_no
                                    ];
                                    
                                    // Insert the record and get the last inserted ID
                                        $insertedId = $Connection->table('temp_promotion')->insertGetId($classDetails);
                                       
                                        // Retrieve the inserted data using the last inserted ID
                                        $insertedRecord = $Connection->table('temp_promotion as tp')
                                        ->select("tp.attendance_no",
                                            "st1.first_name",
                                            "tp.roll",
                                            "d1.name as deptName",
                                            "c1.name as className",
                                            "s1.name as sectionName", 
                                            "sem1.name as semName",
                                            "ses1.name as sesName",
                                            "d2.name as deptPromotionName",
                                            "c2.name as classPromotionName",
                                            "s2.name as sectionPromotionName",
                                            "sem2.name as semPromotionName", 
                                            "ses2.name as sesPromotionName")
                                        ->leftJoin('classes as c1', 'c1.id', '=', 'tp.class_id')
                                        ->leftJoin('classes as c2', 'c2.id', '=', 'tp.promoted_class_id')
                                        ->leftJoin('sections as s1', 's1.id', '=', 'tp.section_id')
                                        ->leftJoin('sections as s2', 's2.id', '=', 'tp.promoted_section_id')
                                        ->leftJoin('staff_departments as d1', 'd1.id', '=', 'tp.department_id')
                                        ->leftJoin('staff_departments as d2', 'd2.id', '=', 'tp.promoted_department_id')
                                        ->leftJoin('students as st1', 'st1.id', '=', 'tp.student_id')
                                        ->leftJoin('semester as sem1', 'sem1.id', '=', 'tp.semester_id')
                                        ->leftJoin('semester as sem2', 'sem2.id', '=', 'tp.promoted_semester_id')
                                        ->leftJoin('session as ses1', 'ses1.id', '=', 'tp.session_id')
                                        ->leftJoin('session as ses2', 'ses2.id', '=', 'tp.promoted_session_id')
                                        ->where('tp.id',"=" ,$insertedId)
                                        ->get()->toArray();

                                        // Add the inserted data to the array
                                        $insertedData[] = $insertedRecord;
                                }
                           // return $insertedData;    
                    }
                   // return $insertedData;
                    if (\File::exists($filepath)) {
                        \File::delete($filepath);
                    }
                    return $this->successResponse(['data' => $insertedData], 'Import Successful');
                } else {
                    return $this->send422Error('Validation error.', ['error' => 'File too large. File must be less than 2MB.']);
                }
            } else {
                return $this->send422Error('Validation error.', ['error' => 'Invalid File Extension']);
            }
        }
    }
    
}
