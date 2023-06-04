<?php

namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
//
use JWTAuth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Stevebauman\Location\Facades\Location;
// base controller add
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Support\Facades\Session;
use Laravel\Passport\Token;

class AuthController extends BaseController
{
    //

    public function authenticateSA(Request $request)
    {
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
    }
    public function authenticate(Request $request)
    {
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
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'branch_id' => $request->branch_id])) {
            // after auth login
            // return $request;
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
                $getUser = User::where(['email' => $request->email, 'branch_id' => $request->branch_id])->first();
                $user = User::find($getUser->id);
                $user->login_attempt = 0;
                $user->session_id = $token;
                $user->save();
                // dd($user->id);
                // User::where('id', $user->id)->update(['session_id', \Session::getId()]);
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
                        ->get();
                    $success['StudentID'] = $StudentID;
                }
                if (isset($user->subsDetails->id)) {
                    $branch_id = $user->subsDetails->id;
                    $Connection = $this->createNewConnection($branch_id);
                    $academicSession = $Connection->table('global_settings')
                        ->select(
                            'year_id',
                            'footer_text',
                            'timezone'
                        )
                        ->first();
                    $success['academicSession'] = $academicSession;
                }
                return $this->successResponse($success, 'User signed in successfully');
            } else {
                return $this->send500Error('You have been locked out of your account, please contact the admin', ['error' => 'You have been locked out of your account, please contact the admin']);
            }
        } else {
            $getUser = User::where([['email', '=', $request->email], ['branch_id', '=', $request->branch_id]])->first();
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
                        return $this->send400Error("Your account has been locked because your password is incorrect. Please contact the admin", ["error" => "Your account has been locked because your password is incorrect. Please contact the admin"]);
                    } else {
                        return $this->send400Error("The password is incorrect and you only have $left attempts left", ["error" => "The password is incorrect and you only have $left attempts left"]);
                    }
                } else {
                    return $this->send500Error('Your account has been locked after more than 3 attempts. Please contact the admin', ['error' => 'Your account has been locked after more than 3 attempts. Please contact the admin']);
                }
            } else {
                return $this->send401Error('Unauthorised.', ['error' => 'Unauthorised']);
            }
        }
    }
    public function authenticateWithBranch(Request $request)
    {
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
    }
    public function logout(Request $request)
    {
        //Request is validated, do logout
        if (Auth::check()) {
            Auth::user()->token()->revoke();
            return $this->successResponse([], 'User has been logged out successfully');
        } else {
            return $this->send500Error('Sorry, user cannot be logged out', ['error' => 'Sorry, user cannot be logged out']);
        }
    }
    public function resetPassword(Request $request)
    {

        $credentials = $request->only('email');
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
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
        if ($this->sendResetEmail($request->email, $tokenData->token)) {
            return $this->successResponse($user, 'A reset link has been sent to your email address.');
        } else {
            return $this->send500Error('A Network Error occurred. Please try again.', ['error' => 'A Network Error occurred. Please try again.']);
        }
    }

    private function sendResetEmail($email, $token)
    {

        //Retrieve the user from the database
        $user = DB::table('users')->select('name', 'email')->where('email', $email)->select('name', 'email')->first();
        //Generate, the password reset link. The token generated is embedded in the link
        // $link = url('/password/reset') . '/' . $token;
        $link = config('constants.mail_link_front_web') . '/password/reset/' . $token;
        // config('constants.mail_link_front_web');
        // dd($link);
        // dd($link);
        if ($email) {
            $data = array('link' => $link, 'name' => $user->name);
            // Mail::send('auth.mail', $data, function ($message) use ($email) {
            //     $message->to('rajeshsakthi645@gmail.com', 'members')->subject('Password Reset');
            //     $message->from('rajeshsakthi645@gmail.com', 'Password Reset');
            // });
            Mail::send('auth.mail', $data, function ($message) use ($email) {
                $message->to($email, 'User')->subject('Password Reset');
                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });
            return $user;
        } else {
            return false;
        }
    }

    public function resetPasswordValidation(Request $request)
    {
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
            ->where(['email' => $request->email, 'token' => $request->token])
            ->first();
        //  dd($updatePassword);
        if ($updatePassword) {
            $user = User::where('email', $request->email)
                ->update(['password' => bcrypt($request->password)]);

            DB::table('password_resets')->where(['email' => $request->email])->delete();

            return $this->successResponse('success', 'Your password has been changed!');
        } else {
            return $this->send500Error('Invalid token!', ['error' => 'Invalid token!']);
        }
    }
    public function expireResetPassword(Request $request)
    {
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
    }

    // employee punchcard check
    public function employeePunchCardCheck(Request $request)
    {
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
    }

    // employee punchcard
    public function employeePunchCard(Request $request)
    {
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
    }
    function get_client_ip()

    {

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
    }
    public function allLogout(Request $request)
    {
        //valid credential
        $validator = Validator::make($request->only('session_id'), [
            'session_id' => 'required'
        ]);
        //Send failed response if request is not valid
        if ($validator->fails()) {
            return $this->send422Error('Validation error.', ['error' => $validator->messages()]);
        }
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->session_id != $request->session_id) {
                Auth::user()->token()->revoke();
                return $this->successResponse([], 'User has been logged out successfully');
            } else {
                return $this->send500Error('Token Valid', ['error' => 'Token Valid']);
            }
        } else {
            return $this->send500Error('Sorry, user cannot be logged out', ['error' => 'Sorry, user cannot be logged out']);
        }
    }
}
