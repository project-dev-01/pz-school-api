<?php

namespace App\Http\Controllers\Api;
// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
//
use JWTAuth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;
use App\Models\Log_history;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Stevebauman\Location\Facades\Location;
// base controller add
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Support\Facades\Session;
use Laravel\Passport\Token;
use Exception;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CommonHelper;

class AuthController extends BaseController
{
    //
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper)
    {
        $this->commonHelper = $commonHelper;
    }
    public function authenticateSA(Request $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            //valid credential
            $validator = Validator::make($credentials, [
                'email' => 'required|email',
                'password' => 'required|string|min:6|max:50'
            ]);
            //Send failed response if request is not valid
            if ($validator->fails()) {
                return $this->send422Error('Validation error.', ['error' => $validator->messages()]);
            }
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'branch_id' => $request->branch_id])) {
                $user = Auth::user();
                $token =  $user->createToken('paxsuzen')->accessToken;
                User::where('id', $user->id)->update(['remember_token' => $token]);
                $success['token'] = $token;
                $success['user'] = $user;
                $success['role_name'] = $user->role->role_name;
                return $this->successResponse($success, 'User signed in successfully');
            } else {
                return $this->send401Error('Unauthorised.', ['error' => 'Unauthorised']);
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error authenticateSA');
        }
    }
    public function authenticate(Request $request)
    {
        try {
            // Valid credential
            $validator = Validator::make($request->only('email', 'password', 'branch_id'), [
                'email' => 'required|email',
                'password' => 'required|string|min:6|max:50',
                'branch_id' => 'required'
            ]);
            //Send failed response if request is not valid
            if ($validator->fails()) {
                $errorResponse = $this->send422Error('Validation error.', ['error' => $validator->messages()]);
                return $errorResponse;
            }
            // dd(Session::getId());
            // check auth
            if (Auth::attempt([
                'email' => $request->email,
                'password' => $request->password,
                'branch_id' => $request->branch_id,
                'role_id' => function ($query) {
                    $query->where('role_id', '!=', '7');
                }
            ])) {
                // after auth login
                // return $request;
                $user = Auth::user();
                $token =  $user->createToken('paxsuzen')->accessToken;
                $cacheKey = 'auth_' . md5(json_encode($request->only('email', 'password', 'branch_id')));
                // Cache::forget($cacheKey);
                // Check if the data is cached
                if (Cache::has($cacheKey)) {
                    // Get the cached data
                    $cachedData = Cache::get($cacheKey);
                    // Check if the cached data is an array and it contains the 'token' key
                    if (is_array($cachedData) && array_key_exists('token', $cachedData)) {
                        // Update the token in the cached data
                        $cachedData['data']['token'] = $token;
                        $cachedData['token'] = $token; // Update the top-level token as well
                        // Store the updated data back into the cache
                        Cache::put($cacheKey, $cachedData, now()->addDay());
                        // Return the updated data
                        return $cachedData;
                    }
                }
                // Auth::logoutOtherDevices($request->password);
                if ($user->status == 0) {
                    // update left to 0
                    // $getUser = User::where(['email' => $request->email,'role_id' => ['!=', 7],'branch_id' => $request->branch_id])->first();
                    $getUser = User::where('email', $request->email)
                        ->where('role_id', '!=', 7)
                        ->where('branch_id', $request->branch_id)
                        ->first();
                    $user = User::find($getUser->id);
                    $user->login_attempt = 0;
                    $user->session_id = $token;
                    $user->save();
                    //User::where('id', $user->id)->update(['session_id', \Session::getId()]);
                    $country = "";
                    $country_code = "";
                    $ip_info = "";
                    $ipAddress = \Request::getClientIp(true);
                    // $ipAddress = "162.216.140.3";
                    // Get the client's IP address
                    if ($ipAddress != '::1' || $ipAddress != '127.0.0.1') {
                        try {
                            $url = "http://ip-api.com/json/{$ipAddress}";

                            $response = Http::get($url);

                            $ip_info = $response->json();

                            $country = $ip_info['country'] ?? 'Unknown';
                            $country_code = $ip_info['countryCode'] ?? 'Unknown';
                        } catch (Exception $e) {

                            $country = 'Unknown';
                            $country_code = 'Unknown';
                        }
                    }
                    //dd($ip_info);
                    $data = [
                        'login_id' => $user->id,
                        'user_id' => $user->user_id,
                        'role_id' => isset($request->role_id) ? $request->role_id : null,
                        'branch_id' => $user->branch_id,
                        'ip_address' => \Request::getClientIp(true),
                        'device' => isset($request->user_device) ? $request->user_device : "other",
                        'browser' => isset($request->user_browser) ? $request->user_browser : "other",
                        'os' => isset($request->user_os) ? $request->user_os : "other",
                        'country' => isset($country) ? $country : 'Unknown',
                        'countrycode' => isset($country_code) ? $country_code : 'Unknown',
                        'ip_info' => json_encode($ip_info),
                        'login_time' => date("Y-m-d H:i:s"),
                        'created_at' => date("Y-m-d H:i:s")
                    ];
                    //$query = $staffConn->table('staff_leaves')->insert($data);
                    // $query = Log_history::insert($data);

                    $success['token'] = $token;
                    $success['user'] = $user;
                    $success['role_name'] = $user->role->role_name;
                    $success['subsDetails'] = $user->subsDetails;
                    if ($user->role->id == 5) {
                        $branch_id = $user->subsDetails->id;
                        $Connection = $this->createNewConnection($branch_id);
                        $StudentID = $Connection->table('students as std')
                            ->select(
                                'std.id',
                                DB::raw("CONCAT(std.last_name, ' ', std.first_name) as name")
                            )
                            ->join('enrolls as en', 'std.id', '=', 'en.student_id')
                            ->where('en.active_status', '=', '0')
                            ->where('father_id', '=', $user->user_id)
                            ->orWhere('mother_id', '=', $user->user_id)
                            ->orWhere('guardian_id', '=', $user->user_id)
                            ->get();
                        $success['StudentID'] = $StudentID;
                    }
                    if (isset($user->subsDetails->id)) {
                        $branch_id = $user->subsDetails->id;
                        $Connection = $this->createNewConnection($branch_id);
                        $academicSession = $Connection->table('global_settings as glo')
                            ->select(
                                'glo.year_id',
                                'lan.name as language_name',
                                'glo.footer_text'
                            )
                            ->leftJoin('language as lan', 'lan.id', '=', 'glo.language_id')
                            ->first();
                        $checkInOutTime = $Connection->table('check_in_out_time as ct')
                            ->select(
                                'ct.check_in',
                                'ct.check_out',
                            )->first();
                        $hiddenWeekends = $Connection->table('work_weeks')
                            ->where('status', '=', '1')
                            ->select('day_value')
                            ->pluck('day_value')
                            ->toArray();
                        $success['academicSession'] = $academicSession;
                        $success['checkInOutTime'] = $checkInOutTime;
                        $success['hiddenWeekends'] = $hiddenWeekends;
                    }
                    $successResponse = $this->successResponse($success, 'User signed in successfully');
                    Cache::put($cacheKey, $successResponse, now()->addDay()); // Cache for 1 Day
                    return $successResponse;
                } else if ($user->status == 2) {
                    return $this->send500Error('Your School Role Deleted, please contact the admin', ['error' => 'You have been locked out of your account, please contact the admin']);
                } else {
                    return $this->send500Error('You have been locked out of your account, please contact the admin', ['error' => 'You have been locked out of your account, please contact the admin']);
                }
            } else {
                // $getUser = User::where([['email', '=', $request->email],['role_id','!=','7'], ['branch_id', '=', $request->branch_id]])->first();
                // dd($getUser);
                // $getUser = User::where('email', $request->email)->first();
                $getUser = User::where('email', $request->email)
                    // ->where('role_id', '!=', 7)
                    ->where('branch_id', $request->branch_id)
                    ->first();
                $login_attempt = isset($getUser->login_attempt) ? $getUser->login_attempt : null;
                if (isset($login_attempt)) {
                    if ($login_attempt <= 2) {
                        $login_attempt = ($login_attempt + 1);
                        $user = User::find($getUser->id);
                        $user->login_attempt = $login_attempt;
                        if ($login_attempt > 2) {
                            $user->status = '1';
                        }
                        $user->save();
                        $left = (3 - $login_attempt);
                        if ($left == 0) {
                            return $this->send401Error("Your account has been locked because your password is incorrect. Please contact the admin", ["error" => "Your account has been locked because your password is incorrect. Please contact the admin"]);
                        } else {
                            return $this->send401Error("The password is incorrect and you only have $left attempts left", ["error" => "The password is incorrect and you only have $left attempts left"]);
                        }
                    } else {
                        return $this->send500Error('Your account has been locked after more than 3 attempts. Please contact the admin', ['error' => 'Your account has been locked after more than 3 attempts. Please contact the admin']);
                    }
                } else {
                    // return $this->send401Error('Unauthorised.', ['error' => 'Unauthorised']);
                    $unauthorizedResponse = $this->send401Error('Unauthorised.', ['error' => 'Unauthorised']);
                    return $unauthorizedResponse;
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error authenticate');
        }
    }
    public function authenticateGuest(Request $request)
    {
        try {
            // return 1;
            $credentials = $request->only('email', 'password', 'branch_id');
            //valid credential
            $validator = Validator::make($credentials, [
                'email' => 'required|email',
                'password' => 'required|string|min:6|max:50',
                'branch_id' => 'required'
            ]);
            //Send failed response if request is not valid
            if ($validator->fails()) {
                return $this->send422Error('Validation error.', ['error' => $validator->messages()]);
            }
            // dd(Session::getId());
            // check auth
            if (Auth::attempt(['email' => $request->email, 'role_id' => $request->role_id, 'password' => $request->password, 'branch_id' => $request->branch_id])) {
                // after auth login
                $user = Auth::user();
                $token =  $user->createToken('paxsuzen')->accessToken;

                // return $user;
                // $success['name'] =  $user->name;
                // return $this->successResponse($success, 'User signed in successfully');
                // $user = auth()->user();
                // User::where('id', $user->id)->update(['remember_token' => $token]);
                // Auth::logoutOtherDevices($request->password);
                if ($user->status == 0) {

                    // update left to 0
                    $getUser = User::where(['email' => $request->email, 'role_id' => $request->role_id, 'branch_id' => $request->branch_id])->first();
                    $user = User::find($getUser->id);
                    $user->login_attempt = 0;
                    $user->session_id = $token;
                    $user->save();
                    // dd($user->id);
                    // return 1;
                    //User::where('id', $user->id)->update(['session_id', \Session::getId()]);
                    $country = "";
                    $country_code = "";
                    $ip_info = "";
                    $ipAddress = \Request::getClientIp(true);
                    // $ipAddress = "162.216.140.3";
                    // Get the client's IP address
                    if ($ipAddress != '::1' || $ipAddress != '127.0.0.1') {
                        try {
                            $url = "http://ip-api.com/json/{$ipAddress}";

                            $response = Http::get($url);

                            $ip_info = $response->json();

                            $country = $ip_info['country'] ?? 'Unknown';
                            $country_code = $ip_info['countryCode'] ?? 'Unknown';
                        } catch (Exception $e) {

                            $country = 'Unknown';
                            $country_code = 'Unknown';
                        }
                    }
                    //dd($ip_info);
                    $data = [
                        'login_id' => $user->id,
                        'user_id' => $user->user_id,
                        'role_id' => $request->role_id,
                        'branch_id' => $user->branch_id,
                        'ip_address' => \Request::getClientIp(true),
                        'device' => isset($request->user_device) ? $request->user_device : "other",
                        'browser' => isset($request->user_browser) ? $request->user_browser : "other",
                        'os' => isset($request->user_os) ? $request->user_os : "other",
                        'country' => isset($country) ? $country : 'Unknown',
                        'countrycode' => isset($country_code) ? $country_code : 'Unknown',
                        'ip_info' => json_encode($ip_info),
                        'login_time' => date("Y-m-d H:i:s"),
                        'created_at' => date("Y-m-d H:i:s")
                    ];

                    //$query = $staffConn->table('staff_leaves')->insert($data);
                    $query = Log_history::insert($data);

                    $success['token'] = $token;
                    $success['user'] = $user;
                    $success['role_name'] = $user->role->role_name;
                    $success['subsDetails'] = $user->subsDetails;
                    if ($user->role->id == 5) {
                        $branch_id = $user->subsDetails->id;
                        $Connection = $this->createNewConnection($branch_id);
                        $StudentID = $Connection->table('students')
                            ->select(
                                'id',
                                DB::raw("CONCAT(first_name, ' ', last_name) as name")
                            )
                            ->where('father_id', '=', $user->user_id)
                            ->orWhere('mother_id', '=', $user->user_id)
                            ->orWhere('guardian_id', '=', $user->user_id)
                            ->get();
                        $success['StudentID'] = $StudentID;
                    }
                    if (isset($user->subsDetails->id)) {
                        $branch_id = $user->subsDetails->id;
                        $Connection = $this->createNewConnection($branch_id);
                        $academicSession = $Connection->table('global_settings as glo')
                            ->select(
                                'glo.year_id',
                                'lan.name as language_name',
                                'glo.footer_text'
                            )
                            ->leftJoin('language as lan', 'lan.id', '=', 'glo.language_id')
                            ->first();
                        $checkInOutTime = $Connection->table('check_in_out_time as ct')
                            ->select(
                                'ct.check_in',
                                'ct.check_out',
                            )->first();
                        $hiddenWeekends = $Connection->table('work_weeks')
                            ->where('status', '=', '1')
                            ->select('day_value')
                            ->pluck('day_value')
                            ->toArray();
                        $success['academicSession'] = $academicSession;
                        $success['checkInOutTime'] = $checkInOutTime;
                        $success['hiddenWeekends'] = $hiddenWeekends;
                    }
                    return $this->successResponse($success, 'User signed in successfully');
                } else {
                    return $this->send500Error('You have been locked out of your account, please contact the admin', ['error' => 'You have been locked out of your account, please contact the admin']);
                }
            } else {

                $getUser = User::where([['email', '=', $request->email], ['role_id', '=', $request->role_id], ['branch_id', '=', $request->branch_id]])->first();
                // dd($getUser);
                // $getUser = User::where('email', $request->email)->first();
                $login_attempt = isset($getUser->login_attempt) ? $getUser->login_attempt : null;
                // dd($login_attempt);
                if (isset($login_attempt)) {
                    if ($login_attempt <= 2) {
                        $login_attempt = ($login_attempt + 1);
                        $user = User::find($getUser->id);
                        $user->login_attempt = $login_attempt;
                        if ($login_attempt > 2) {
                            $user->status = '1';
                        }
                        $user->save();
                        $left = (3 - $login_attempt);
                        if ($left == 0) {
                            return $this->send401Error("Your account has been locked because your password is incorrect. Please contact the admin", ["error" => "Your account has been locked because your password is incorrect. Please contact the admin"]);
                        } else {
                            return $this->send401Error("The password is incorrect and you only have $left attempts left", ["error" => "The password is incorrect and you only have $left attempts left"]);
                        }
                    } else {
                        return $this->send500Error('Your account has been locked after more than 3 attempts. Please contact the admin', ['error' => 'Your account has been locked after more than 3 attempts. Please contact the admin']);
                    }
                } else {
                    return $this->send401Error('Unauthorised.', ['error' => 'Unauthorised']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error authenticateGuest');
        }
    }
    public function authenticateWithBranch(Request $request)
    {
        try {
            $credentials = $request->only('email', 'password', 'branch_id');
            //valid credential
            $validator = Validator::make($credentials, [
                'email' => 'required|email',
                'password' => 'required|string|min:6|max:50',
                'branch_id' => 'required'
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return $this->send422Error('Validation error.', ['error' => $validator->messages()]);
            }
            // check auth
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'branch_id' => $request->branch_id])) {
                $user = Auth::user();
                $token =  $user->createToken('paxsuzen')->accessToken;

                if ($user->status == 0) {
                    $success['token'] = $token;
                    $success['user'] = $user;
                    $success['role_name'] = $user->role->role_name;
                    $success['subsDetails'] = $user->subsDetails;

                    //Token created, return with success response and jwt token
                    return $this->successResponse($success, 'User signed in successfully');
                } else {
                    return $this->send500Error('Your Account Locked, Please Contact Admin', ['error' => 'Your Account Locked, Please Contact Admin']);
                }
            } else {
                return $this->send401Error('Unauthorised.', ['error' => 'Unauthorised']);
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error authenticateWithBranch');
        }
    }
    public function logout(Request $request)
    {
        try {
            //Request is validated, do logout
            if ($request->branch_id !== null) {
                if (Auth::check()) {
                    Auth::user()->token()->revoke();
                    return $this->successResponse([], 'User has been logged out successfully');
                } else {
                    return $this->send500Error('Sorry, user cannot be logged out', ['error' => 'Sorry, user cannot be logged out']);
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error logout');
        }
    }

    public function lastlogout(Request $request)
    {
        try {
            // Check if userID is not null
            if ($request->userID !== null) {
                // Retrieve the latest log history for the given userID and role_id
                $logHistory = Log_history::where('login_id', $request->userID)
                    ->where('role_id', $request->role_id)
                    ->latest()
                    ->first();

                // If log history exists, update the logout time
                if ($logHistory) {
                    $logHistory->update(['logout_time' => date("Y-m-d H:i:s")]);
                    return $this->successResponse([$logHistory->id], 'User last logout added successfully');
                } else {
                    // If log history doesn't exist, return an error response
                    return $this->send500Error('Sorry, user cannot be logged out', ['error' => 'Sorry, user cannot be logged out']);
                }
            }
        } catch (Exception $error) {
            // Handle any exceptions
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error lastlogout');
        }
    }
    public function login_historylist(Request $request)
    {
        try {
            $fromDate = $request->frm_ldate . ' 00:00:00';
            $toDate = $request->to_ldate . ' 23:59:59';
            if ($request->role_id == 'All') {
                // $data = Log_history::where('branch_id', $request->branch_id)
                //     ->where('is_active', '0')
                //     ->whereBetween('login_time', [$fromDate, $toDate])
                //     ->get();
                $data = DB::table('log_history')
                    ->join('users', 'log_history.login_id', '=', 'users.id')
                    ->select('log_history.*', 'users.is_active')
                    ->where('users.is_active', '0')
                    ->where('users.branch_id', $request->branch_id)
                    ->whereBetween('log_history.login_time', [$fromDate, $toDate])
                    ->get();
            } else {
                // $data = Log_history::where('branch_id', $request->branch_id)
                //     ->where('is_active', '0')
                //     ->where('role_id', $request->role_id)
                //     ->whereBetween('login_time', [$fromDate, $toDate])->get();
                $requestRoleId = $request->role_id;
                $data = DB::table('log_history')
                    ->join('users', 'log_history.login_id', '=', 'users.id')
                    ->select('log_history.*', 'users.is_active')
                    ->where(function ($query) use ($requestRoleId) {
                        // Check if the requested role ID is found within the saved role IDs
                        $query->whereRaw("FIND_IN_SET(?, users.role_id)", [$requestRoleId]);
                    })
                    ->where('users.is_active', '0')
                    ->where('users.branch_id', $request->branch_id)
                    ->whereBetween('log_history.login_time', [$fromDate, $toDate])
                    ->get();
            }
            $history = array();
            foreach ($data as $item) {
                $user = User::where('user_id', $item->user_id)->where('role_id', $item->role_id)->select('name')->first();
                $role = Role::where('id', $item->role_id)->select('role_name')->first();
                $city = "";
                $state = "";
                $country = "";


                $workingHours = "0";
                $workingMinutes = "0";
                if (!empty($item->logout_time)) {
                    $checkin = strtotime($item->login_time);
                    $checkout = strtotime($item->logout_time);
                    $timediff = $checkout - $checkin;
                    $workingHours = floor($timediff / 3600);
                    $workingMinutes = ($timediff % 3600) / 60;
                }
                $items = array();
                $items['id'] = $item->id;
                $items['ip_address'] = $item->ip_address;
                $items['user_id'] = $item->user_id;
                $items['user_name'] = $user->name;
                $items['role_name'] = $role->role_name;
                $items['role_id'] = $item->role_id;
                $items['device'] = $item->device;
                $items['browser'] = $item->browser;
                $items['os'] = $item->os;
                $items['country'] = $item->country;
                $items['login_time'] = date('d-m-Y h:i:a', strtotime($item->login_time));
                $items['logout_time'] = date('d-m-Y h:i:a', strtotime($item->logout_time));
                $items['spend_time'] = $workingHours . " Hour : " . intval($workingMinutes) . " Min";
                array_push($history, $items);
            }
            return $this->successResponse($history, 'Log History record fetch successfully');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error login_historylist');
        }
    }
    public function resetPassword(Request $request)
    {
        try {
            $credentials = $request->only('email', 'sent_link');
            $validator = Validator::make($credentials, [
                'email' => 'required|email',
                'sent_link' => 'required'
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return $this->send422Error('Validation error.', ['error' => $validator->messages()]);
            }

            $user = User::where('email', '=', $request->email)->first();
            if ($user === null) {
                return $this->send400Error('Email does not Exist.', ['error' => 'Email does not Exist']);
            }

            //Create Password Reset Token
            DB::table('password_resets')->insert([
                'branch_id' => $request->branch_id,
                'email' => $request->email,
                'token' => Str::random(60),
                'created_at' => Carbon::now()
            ]);

            // $user =
            //Get the token just created above
            // $tokenData = DB::table('password_resets')->where('email', $request->email)->first();
            // $user = DB::table('users')->where('email', $request->email)->first();
            $tokenData = DB::table('password_resets')->where('email', $request->email)->orderBy('created_at', 'DESC')->first();
            // return $tokenData->token;
            if ($this->sendResetEmail($request->email, $request->sent_link, $tokenData->token, $request->branch_id)) {
                return $this->successResponse($user, 'A reset link has been sent to your email address.');
            } else {
                return $this->send500Error('A Network Error occurred. Please try again.', ['error' => 'A Network Error occurred. Please try again.']);
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error resetPassword');
        }
    }

    private function sendResetEmail($email, $sent_link, $token, $branch_id)
    {
        try {
            //Retrieve the user from the database
            $user = DB::table('users')->select('name', 'email')->where('email', $email)->where('branch_id', $branch_id)->select('name', 'email')->first();
            //Generate, the password reset link. The token generated is embedded in the link
            // $link = url('/password/reset') . '/' . $token;
            $link = $sent_link . '/password/reset/' . $token;
            // config('constants.mail_link_front_web');
            // dd($link);
            // dd($link);
            if ($email) {
                $data = array('link' => $link, 'name' => $user->name);
                $mailFromAddress = env('MAIL_FROM_ADDRESS', config('constants.client_email'));
                Mail::send('auth.mail', $data, function ($message) use ($email, $mailFromAddress) {
                    $message->to($email, 'User')->subject('Password Reset');
                    $message->from($mailFromAddress, env('MAIL_FROM_NAME'));
                });
                return $user;
            } else {
                return false;
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error sendResetEmail');
        }
    }

    public function resetPasswordValidation(Request $request)
    {
        try {
            $credentials = $request->only('email', 'password', 'password_confirmation');
            $validator = Validator::make($credentials, [
                'email' => 'required|email|exists:users',
                'password' => 'required|min:6|confirmed',
                'password_confirmation' => 'required',

            ]);

            if ($validator->fails()) {
                return $this->send422Error('Validation error.', ['error' => $validator->messages()]);
            }

            $updatePassword = DB::table('password_resets')
                ->where(['email' => $request->email, 'token' => $request->token, 'branch_id' => $request->branch_id])
                ->first();
            //  dd($updatePassword);
            if ($updatePassword) {
                $user = User::where(['email' => $request->email, 'branch_id' => $request->branch_id])
                    ->update(['password' => bcrypt($request->password), 'status' => "0", 'login_attempt' => '0',]);

                DB::table('password_resets')->where(['email' => $request->email, 'branch_id' => $request->branch_id])->delete();

                $user = User::where(['email' => $request->email, 'branch_id' => $request->branch_id])->first();
                return $this->successResponse($user, 'Your password has been changed!');
            } else {
                return $this->send500Error('Invalid token!', ['error' => 'Invalid token!']);
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error resetPasswordValidation');
        }
    }
    public function expireResetPassword(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'email' => 'required|email|exists:users',
                'token' => 'required',
                'password' => [
                    'required',
                    'min:8',
                    'max:30',
                    'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/'
                ],
                'password_confirmation' => 'required|same:password|min:8|max:30'
            ]);

            if ($validator->fails()) {
                return $this->send422Error('Validation error.', ['error' => $validator->messages()]);
            }

            $updatePassword = DB::table('password_resets')
                ->where(['email' => $request->email, 'token' => $request->token, 'password_reminder' => "1"])
                ->first();
            //  dd($updatePassword);
            if ($updatePassword) {
                $updateData =
                    [
                        'password' => bcrypt($request->password),
                        'login_attempt' => "0",
                        'status' => "0",
                        'password_changed_at' => date("Y-m-d H:i:s")
                    ];
                User::where('email', $request->email)
                    ->update($updateData);
                // DB::table('password_resets')->where(['email' => $request->email, 'password_reminder' => "1"])->delete();
                $userDetails = User::select('role_id')->where('email', $request->email)->first();
                $role_id = $userDetails->role_id;
                if ($role_id == 2) {
                    $redirect_route = 'admin.login';
                } elseif ($role_id == 3) {
                    $redirect_route = 'staff.login';
                } elseif ($role_id == 4) {
                    $redirect_route = 'teacher.login';
                } elseif ($role_id == 5) {
                    $redirect_route = 'parent.login';
                } elseif ($role_id == 6) {
                    $redirect_route = 'student.login';
                } else {
                    $redirect_route = 'admin.login';
                }
                // $redirect_route = route('admin.dashboard');
                return $this->successResponse($redirect_route, 'Your password has been changed!');
            } else {
                return $this->send500Error('Invalid token!', ['error' => 'Invalid token!']);
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error expireResetPassword');
        }
    }

    // employee punchcard check
    public function employeePunchCardCheck(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'branch_id' => 'required',
                'id' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $success = [];
                $check_out = NULL;
                $check_in = NULL;
                $hours = NULL;
                $id = $request->id;

                // create new connection
                $conn = $this->createNewConnection($request->branch_id);
                $date = Carbon::now()->format('Y-m-d');
                $time = Carbon::now()->format('H:i:s');
                if ($conn->table('staff_attendances')->where([['date', '=', $date], ['staff_id', '=', $request->id], ['session_id', '=', $request->session_id]])->count() > 0) {


                    $validate = $conn->table('staff_attendances')->where([['date', '=', $date], ['staff_id', '=', $request->id], ['session_id', '=', $request->session_id]])->first();


                    $session = $conn->table('session')->where('id', '=', $request->session_id)->first();
                    if ($validate->check_in && !$validate->check_out) {

                        $session_end = $session->time_to;
                        if ($time > $session_end) {

                            $success['check_out'] = "Late Check Out";
                        } else {
                            $success['check_out'] = "Check Out";
                        }
                        $success['check_in'] = "Checked In";
                        $success['check_in_status'] = "disabled";
                        $success['check_out_status'] = "";
                        $success['check_in_time'] = $validate->check_in;
                        $success['check_out_time'] = "";
                    } else if (!$validate->check_in && !$validate->check_out) {
                        $session_start = $session->time_from;
                        if ($time > $session_start) {

                            $success['check_in'] = "Late Check In";
                        } else {
                            $success['check_in'] = "Check In";
                        }
                        $success['check_out'] = "Check Out";
                        $success['check_in_status'] = "";
                        $success['check_out_status'] = "disabled";
                        $success['check_in_time'] = "";
                        $success['check_out_time'] = "";
                    } else if ($validate->check_in && $validate->check_out) {
                        $success['check_in'] = "Checked In";
                        $success['check_out'] = "Check Out";
                        $success['check_in_status'] = "disabled";
                        $success['check_out_status'] = "disabled";
                        $success['check_in_time'] = $validate->check_in;
                        $success['check_out_time'] = $validate->check_out;
                    } else if (!$validate->check_in && $validate->check_out) {
                        $success['check_in'] = "Not Check In";
                        $success['check_out'] = "Check Out";
                        $success['check_in_status'] = "disabled";
                        $success['check_out_status'] = "disabled";
                        $success['check_in_time'] = $validate->check_in;
                        $success['check_out_time'] = $validate->check_out;
                    }


                    if ($validate->check_out) {
                        $session_end = $session->time_to;
                        if ($validate->check_out > $session_end) {

                            $success['check_out_color'] = "red";
                        } else {
                            $success['check_out_color'] = "";
                        }
                    } else {
                        $success['check_out_color'] = "";
                    }
                    if ($validate->check_in) {
                        $session_start = $session->time_from;
                        if ($validate->check_in > $session_start) {

                            $success['check_in_color'] = "red";
                        } else {
                            $success['check_in_color'] = "";
                        }
                    } else {
                        $success['check_in_color'] = "";
                    }
                } else {
                    $start = $conn->table('session')->where('id', '=', $request->session_id)->first();
                    $session_start = $start->time_from;
                    if ($time > $session_start) {
                        $success['check_in'] = "Late Check In";
                    } else {
                        $success['check_in'] = "Check In";
                    }
                    $success['check_out'] = "Check Out";
                    $success['check_in_status'] = "";
                    $success['check_out_status'] = "disabled";
                    $success['check_in_time'] = "";
                    $success['check_out_time'] = "";
                    $success['check_out_color'] = "";
                    $success['check_in_color'] = "";
                }
                return $this->successResponse($success, 'Status');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error employeePunchCardCheck');
        }
    }

    // employee punchcard
    public function employeePunchCard(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'branch_id' => 'required',
                'id' => 'required',
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                // create new connection
                $success = [];
                $check_out = NULL;
                $check_in = NULL;
                $check_in_location = NULL;
                $check_out_location = NULL;
                $hours = NULL;
                $id = $request->id;
                $conn = $this->createNewConnection($request->branch_id);
                $date = Carbon::now()->format('Y-m-d');
                $time = Carbon::now()->format('H:i:s');

                $currentlocation['latitude'] = $request->latitude;
                $currentlocation['longitude'] = $request->longitude;
                $location = json_encode($currentlocation);
                // dd($currentlocation);

                if ($conn->table('staff_attendances')->where([['date', '=', $date], ['staff_id', '=', $request->id], ['session_id', '=', $request->session_id]])->count() > 0) {

                    $validate = $conn->table('staff_attendances')->where([['date', '=', $date], ['staff_id', '=', $request->id], ['session_id', '=', $request->session_id]])->first();

                    if ($request->check_in == 1) {
                        $check_in = $time;

                        $success['check_in'] = "Checked In";
                        $success['check_out'] = "Check Out";
                        $success['check_in_status'] = "true";
                        $success['check_out_status'] = "";
                        $success['check_in_time'] = $check_in;
                        $success['check_out_time'] = "";
                        $check_in_location = $location;
                    } else if ($request->check_out == 1) {
                        $check_in = $validate->check_in;
                        $check_in_location = $validate->check_in_location;
                        $check_out = $time;

                        if ($check_in) {
                            $diff_in = new Carbon($check_in);
                            $diff_out = new Carbon($check_out);

                            $hours = $diff_out->diff($diff_in)->format('%H:%I');
                            $success['check_in'] = "Checked In";
                        } else {
                            $success['check_in'] = "Not Check In";
                        }

                        $success['check_out'] = "Checked Out";
                        $success['check_in_status'] = "true";
                        $success['check_out_status'] = "true";
                        $success['check_in_time'] = $validate->check_in;
                        $success['check_out_time'] = $check_out;

                        $check_out_location = $location;
                    }

                    $query = $conn->table('staff_attendances')->where('id', $validate->id)->update([
                        'date' => $date,
                        'check_in' => $check_in,
                        'check_out' => $check_out,
                        'check_in_location' => $check_in_location,
                        'check_out_location' => $check_out_location,
                        'status' => "present",
                        'hours' => $hours,
                        'staff_id' => $id,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);
                } else {
                    $query = $conn->table('staff_attendances')->insert([
                        'date' => $date,
                        'check_in' => $time,
                        'check_in_location' => $location,
                        'status' => "present",
                        'staff_id' => $id,
                        'session_id' => $request->session_id,
                        'created_at' => date("Y-m-d H:i:s")
                    ]);



                    $success['check_in'] = "Checked In";
                    $success['check_out'] = "Check Out";
                    $success['check_in_status'] = "true";
                    $success['check_out_status'] = "";
                    $success['check_in_time'] = $time;
                    $success['check_out_time'] = "";
                }
                // return $now;
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    return $this->successResponse($success, 'Attendance has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error employeePunchCard');
        }
    }
    public function get_client_ip()
    {
        try {
            $ipaddress = '';

            if (getenv('HTTP_CLIENT_IP'))

                $ipaddress = getenv('HTTP_CLIENT_IP');

            else if (getenv('HTTP_X_FORWARDED_FOR'))

                $ipaddress = getenv('HTTP_X_FORWARDED_FOR');

            else if (getenv('HTTP_X_FORWARDED'))

                $ipaddress = getenv('HTTP_X_FORWARDED');

            else if (getenv('HTTP_FORWARDED_FOR'))

                $ipaddress = getenv('HTTP_FORWARDED_FOR');

            else if (getenv('HTTP_FORWARDED'))

                $ipaddress = getenv('HTTP_FORWARDED');

            else if (getenv('REMOTE_ADDR'))

                $ipaddress = getenv('REMOTE_ADDR');

            else

                $ipaddress = 'UNKNOWN';

            return $ipaddress;
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error get_client_ip');
        }
    }
    public function allLogout(Request $request)
    {
        try {
            //valid credential
            $validator = Validator::make($request->only('session_id'), [
                'session_id' => 'required'
            ]);
            //Send failed response if request is not valid
            if ($validator->fails()) {
                return $this->send422Error('Validation error.', ['error' => $validator->messages()]);
            }
            try {
                if (Auth::check()) {
                    $user = Auth::user();
                    if ($user->session_id != $request->session_id) {
                        Auth::user()->token()->revoke();
                        // return $this->send401Error('Token Invalid.', ['error' => 'Token Invalid']);
                        // return $this->successResponse([], 'Token Valid');
                        $response = [
                            'code' => 200,
                            'success' => true,
                            'message' => 'Token Invalid'
                        ];
                        return response()->json($response, 200);
                    } else {
                        // return $this->successResponse([], 'Token Valid');
                        $response = [
                            'code' => 200,
                            'success' => false,
                            'message' => 'Token Valid'
                        ];
                        return response()->json($response, 200);
                    }
                } else {
                    $response = [
                        'code' => 200,
                        'success' => false,
                        'message' => 'Sorry, user cannot be logged out'
                    ];
                    return response()->json($response, 200);
                    // return $this->send500Error('Sorry, user cannot be logged out', ['error' => 'Sorry, user cannot be logged out']);
                    // return $this->successResponse([], 'Token Valid');
                }
            } catch (Exception $e) {
                return $this->sendCommonError('No Data Found.', ['error' => $e->getMessage()]);
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error allLogout');
        }
    }
}
