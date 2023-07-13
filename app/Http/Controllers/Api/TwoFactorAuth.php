<?php

// namespace App\Http\Controllers\Api;

// use Illuminate\Http\Request;

// use Illuminate\Support\Facades\Validator;
// use Illuminate\Support\Facades\Auth;
// use Symfony\Component\HttpFoundation\Response;
// use App\Http\Controllers\Api\BaseController as BaseController;
// use App\Models\User;
// use PragmaRX\Google2FAQRCode\Google2FA;
// use Illuminate\Support\Facades\DB;
// use Laravel\Passport\Token;

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

class TwoFactorAuth extends BaseController
{
    // two fa otp and qr
    public function twoFaGenerateSecretQr(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'email' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        } else {
            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            $secret = $google2fa->generateSecretKey();
            // $google2fa = new \PragmaRX\Google2FA\Google2FA();
            $qrtext = $google2fa->getQRCodeUrl(
                // 'example.com',
                config('app.name'),
                $request->email,
                $secret
            );
            $user = new User;
            $encrypt_key =  $user->setGoogle2faSecretAttribute($secret);
            // $QR_Image_Url = $qrtext;
            $QR_Image_Url = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . $qrtext;
            $data = [
                "encrypt_key" => $encrypt_key,
                "secret" => $secret,
                "qr_url" => $QR_Image_Url,
            ];
            return $this->successResponse($data, 'generate successfully');
        }
    }
    // otp verified
    public function twoFaOtpValid(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
            'branch_id' => 'required',
            'otp' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error', ['error' => $validator->errors()->toArray()]);
        } else {
            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            $email = $request->email;
            $user_provided_code = $request->otp;
            // $user = User::find($email);
            $user = User::where('email', '=', $email)->first();
            if (!$user) {
                // handle the case if no user is found
                return $this->send404Error('no user is found', ['error' => 'no user is found']);
            }
            $secret_key = isset($request->google2fa_secret) ? $request->google2fa_secret : $user->google2fa_secret;
            if ($google2fa->verifyKey($secret_key, $user_provided_code)) {
                // echo "Code is valid";
                // Code is valid
                // return $this->successResponse([], 'Otp is valid');
                // after auth login
                // check auth
                if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'branch_id' => $request->branch_id])) {
                    // after auth login
                    $user = Auth::user();
                    $token =  $user->createToken('paxsuzen')->accessToken;
                    // $success['name'] =  $user->name;
                    // return $this->successResponse($success, 'User signed in successfully');
                    // $user = auth()->user();
                    // User::where('id', $user->id)->update(['remember_token' => $token]);
                    // Auth::logoutOtherDevices($request->password);
                    if ($user->status == 0) {
                        // update left to 0
                        $getUser = User::where('email', $request->email)->first();
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
                            $academicSession = $Connection->table('global_settings as glo')
                                ->select(
                                    'glo.year_id',
                                    'lan.name as language_name',
                                    'glo.footer_text'
                                )
                                ->leftJoin('language as lan', 'lan.id', '=', 'glo.language_id')
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
            } else {
                // Code is NOT valid
                // echo "NOT valid";
                return $this->send404Error([], 'Otp is not valid');
            }
        }
    }
    // updateTwoFASecret
    public function updateTwoFASecret(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'email' => 'required',
            'google2fa_secret' => 'required'
        ]);
        if (!$validator->passes()) {
            return $this->send422Error('Validation error', ['error' => $validator->errors()->toArray()]);
        } else {
            $user = User::where('email', '=', $request->email)->first();
            if (!$user) {
                // handle the case if no user is found
                return $this->send404Error('no user is found', ['error' => 'no user is found']);
            }
            $encrypt_key =  $user->setGoogle2faSecretAttribute($request->google2fa_secret);
            User::where('id', $user->id)->update(['google2fa_secret' => $encrypt_key]);
            return $this->successResponse([], 'updated secret successfully');
        }
    }
}
