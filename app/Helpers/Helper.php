<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
// encrypt and decrypt
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class Helper
{

    // code generaaator
    public static function CodeGenerator($model, $trow, $length = null, $prefix)
    {
        $length = 4; 
        $data = $model::orderBy('id', 'desc')->first(); // 4. Make sure that $model has 'id' as a sortable field.
        if (!$data) {
            $og_length = $length;
            $last_number = '';
        } else {
            // 5. Consider adding validation for $trow to ensure it exists in the model.
            $code = substr($data->$trow, strlen($prefix) + 1);

            // 6. Verify that $code can be treated as a number.
            $actial_last_number = ($code / 1) * 1;
            $increment_last_number = ((int)$actial_last_number) + 1;
            $last_number_length = strlen($increment_last_number);
            $og_length = $length - $last_number_length;
            $last_number = $increment_last_number;
        }

        $zeros = str_repeat("0", $og_length); // 7. Improved way to generate $zeros.

        return $prefix . '-' . $zeros . $last_number; // 8. Consider whether the format of the output is suitable for all use cases.
    }


    // get api call
    public static function GetMethod($url)
    {
        $response = Http::get($url, [
            'token' => session()->get('token'),
            'branch_id' => session()->get('branch_id')
        ]);
        return $response->json();
    }
    // get api call
    public static function GETMethodWithData($url, $data)
    {
        $data["token"] = session()->get('token');
        $data["branch_id"] = session()->get('branch_id');
        $response = Http::get($url, $data);
        return $response->json();
    }
    // post api call
    public static function PostMethod($url, $data)
    {
        $data["token"] = session()->get('token');
        $data["branch_id"] = session()->get('branch_id');
        $response = Http::post($url, $data);
        return $response->json();
    }

    // get api call
    public static function DataTableGetMethod($url, $data)
    {
        $data["token"] = session()->get('token');
        $data["branch_id"] = session()->get('branch_id');
        $response = Http::get($url, $data);
        return $response->json();
    }
    // decrypt string
    public static function decryptStringData($string)
    {
        try {
            $data = Crypt::decryptString($string);
        } catch (DecryptException $e) {
            $data = "";
        }
        return $data;
    }
    // greeting message
    public static function greetingMessage()
    {
        $greetings = "";
        /* This sets the $time variable to the current hour in the 24 hour clock format */
        $time = date("H");
        /* Set the $timezone variable to become the current timezone */
        $timezone = date("e");
        /* If the time is less than 1200 hours, show good morning */
        if ($time < "12") {
            $greetings = "Good Morning";
        } else {
            /* If the time is grater than or equal to 1200 hours, but less than 1700 hours, so good afternoon */
            if ($time >= "12" && $time < "17") {
                $greetings = "Good Afternoon";
            } else {
                /* Should the time be between or equal to 1700 and 1900 hours, show good evening */
                if ($time >= "17" && $time < "19") {
                    $greetings = "Good Evening";
                } else {
                    /* Finally, show good night if the time is greater than or equal to 1900 hours */
                    if ($time >= "19") {
                        $greetings = "Good Night";
                    }
                }
            }
        }
        return $greetings;
    }

    // get like column
    public static function getLikeColumn($url, $data)
    {
        $data["token"] = session()->get('token');
        $data["branch_id"] = session()->get('branch_id');
        return $data;
        $response = Http::post($url, $data);
        return $response->json();
    }
}
