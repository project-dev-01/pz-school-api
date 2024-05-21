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

class SettingsController extends BaseController
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
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
