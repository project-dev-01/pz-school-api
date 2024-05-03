<?php

namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
//
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
// base controller add
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Validation\Rule;
use DateTime;
use DateInterval;
use DatePeriod;
use App\Models\Branches;
use App\Models\Section;
use App\Helpers\Helper;
use App\Mail\TestQueueMail;
use App\Models\Classes;
use App\Models\Role;
use App\Models\EventType;
use App\Models\User;
// db connection
use App\Models\Forum_posts;
use App\Models\Forum_count_details;
use App\Models\Forum_post_replies;
use Carbon\Carbon;
use App\Models\Forum_post_replie_counts;
use Illuminate\Support\Arr;
// notifications
use App\Notifications\LeaveApply;
use App\Notifications\LeaveApprove;
use App\Notifications\StudentHomeworkSubmit;
use App\Notifications\TeacherHomework;
use App\Notifications\ParentEmail;
use App\Notifications\StudentEmail;
use App\Notifications\TeacherEmail;
use Illuminate\Support\Facades\Notification;
// encrypt and decrypt
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use File;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use App\Models\Menus;
use App\Models\Menuaccess;
use Illuminate\Support\Facades\Mail;
use App\Helpers\CommonHelper;

class ApiControllerNameSeq extends BaseController
{
    protected CommonHelper $commonHelper;
    public function __construct(CommonHelper $commonHelper)
    {
        $this->commonHelper = $commonHelper;
    }
    
}
