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

class ForumController extends BaseController
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
    // forum all post branch id wise
    public function postList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'user_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // $success = DB::table('forum_posts')
                //     ->leftJoin('forum_categorys', 'forum_categorys.id', '=', 'forum_posts.category')
                //     // ->leftJoin('forum_count_details', function ($join) {
                //     //     $join->on('forum_posts.id', '=', 'forum_count_details.created_post_id');
                //     // })
                //     ->leftJoin('forum_count_details', 'forum_posts.id', '=', 'forum_count_details.created_post_id')
                //     ->select('forum_posts.id', 'forum_posts.user_id', 'forum_posts.user_name', 'forum_posts.topic_title', 'forum_categorys.category_names', DB::raw("SUM(forum_count_details.likes) as likes"), DB::raw("SUM(forum_count_details.dislikes)as dislikes"), DB::raw("SUM(forum_count_details.favorite)as favorite"), DB::raw("SUM(forum_count_details.replies)as replies"), DB::raw("SUM(forum_count_details.views)as views"), 'forum_count_details.activity', 'forum_posts.created_at', 'forum_posts.topic_header')
                //     ->where('forum_posts.branch_id', '=', $request->branch_id)
                //     //        ->groupBy('forum_count_details.created_post_id')
                //     ->get();

                $success = DB::table("forum_posts")

                    ->select(
                        'forum_posts.id as id',
                        'forum_posts.topic_title',
                        'forum_posts.user_id as user_id',
                        'forum_posts.user_name',
                        'forum_posts.tags',
                        'forum_categorys.category_names',
                        'forum_posts.topic_header',
                        'forum_posts.created_at',
                        'forum_posts.category',
                        'forum_count_details.likes',
                        'forum_count_details.dislikes',
                        'forum_count_details.views',
                        'forum_count_details.replies',
                        'forum_count_details.favorite',
                        'favorite',
                        'activity'
                    )

                    ->leftjoin(
                        DB::raw("(SELECT user_id,user_name,created_post_id,SUM(likes) as likes,SUM(dislikes) as dislikes,SUM(views) as views,SUM(replies) as replies ,SUM(favorite) as favorite,activity FROM forum_count_details GROUP BY created_post_id) as forum_count_details"),
                        function ($join) {
                            $join->on("forum_count_details.created_post_id", "=", "forum_posts.id");
                        }
                    )
                    ->leftjoin(
                        DB::raw("(SELECT id as category_id,category_names from forum_categorys) as forum_categorys"),
                        function ($join) {

                            $join->on("forum_categorys.category_id", "=", "forum_posts.category");
                        }
                    )
                    ->where('forum_posts.branch_id', '=', $request->branch_id)
                    ->where('forum_posts.threads_status', '=', 2)
                    ->whereRaw("find_in_set($request->user_id,forum_posts.tags)")
                    ->get();


                // $subjectdata = DB::table('forum_posts')->select()

                ////////////////////////////////////////////////
                // ->leftJoin('forum_count_details', function ($join) {
                //     $join->on('forum_count_details.created_post_id', '=', 'forum_posts.id')
                //         ->orWhere('forum_posts.user_id', '`c.user_id`');


                //     $branchid=$request->branch_id;
                //     $success = DB::query()->fromSub(function ($query) use ($branchid) {
                //         $query->from('forum_posts')
                //             ->select('id as created_post_id','topic_header,created_at','category')
                //             ->where('forum_posts.branch_id','=',DB::raw("'$branchid'"))
                //             ->leftJoin('forum_count_details','forum_posts.id','=','forum_count_details.created_post_replies_id')
                //             ->select('created_post_id',DB::raw("SUM(forum_count_details.likes) as likes"),DB::raw("SUM(forum_count_details.dislikes) as dislikes"),DB::raw("SUM(forum_count_details.views) as views"),DB::raw("SUM(forum_count_details.replies) as replies"),DB::raw("SUM(forum_count_details.favorite) as favorite"),'activity')
                //             ->Groupby('created_post_id')
                //             ->leftJoin('forum_categorys','forum_posts.category','=','forum_categorys.category_id');
                //     },'aa')
                //     ->select('*');
                //    dd($success);

                return $this->successResponse($success, 'Post record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in postList');
        }
    }
    // forum get Post by id
    public function postEdit(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'user_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $success = DB::table("forum_posts")

                    ->select(
                        'forum_posts.id as id',
                        'forum_posts.topic_title',
                        'forum_posts.user_id as user_id',
                        'forum_posts.user_name',
                        'forum_posts.tags',
                        'forum_posts.types',
                        'forum_posts.body_content',
                        'forum_categorys.category_names',
                        'forum_posts.topic_header',
                        'forum_posts.created_at',
                        'forum_posts.category',
                        'forum_count_details.likes',
                        'forum_count_details.dislikes',
                        'forum_count_details.views',
                        'forum_count_details.replies',
                        'forum_count_details.favorite',
                        'favorite',
                        'activity'
                    )

                    ->leftjoin(
                        DB::raw("(SELECT user_id,user_name,created_post_id,SUM(likes) as likes,SUM(dislikes) as dislikes,SUM(views) as views,SUM(replies) as replies ,SUM(favorite) as favorite,activity FROM forum_count_details GROUP BY created_post_id) as forum_count_details"),
                        function ($join) {
                            $join->on("forum_count_details.created_post_id", "=", "forum_posts.id");
                        }
                    )
                    ->leftjoin(
                        DB::raw("(SELECT id as category_id,category_names from forum_categorys) as forum_categorys"),
                        function ($join) {

                            $join->on("forum_categorys.category_id", "=", "forum_posts.category");
                        }
                    )
                    ->where('forum_posts.branch_id', '=', $request->branch_id)
                    ->where('forum_posts.threads_status', '=', 2)
                    ->where('forum_posts.id', '=', $request->id)
                    ->first();


                // $subjectdata = DB::table('forum_posts')->select()

                ////////////////////////////////////////////////
                // ->leftJoin('forum_count_details', function ($join) {
                //     $join->on('forum_count_details.created_post_id', '=', 'forum_posts.id')
                //         ->orWhere('forum_posts.user_id', '`c.user_id`');


                //     $branchid=$request->branch_id;
                //     $success = DB::query()->fromSub(function ($query) use ($branchid) {
                //         $query->from('forum_posts')
                //             ->select('id as created_post_id','topic_header,created_at','category')
                //             ->where('forum_posts.branch_id','=',DB::raw("'$branchid'"))
                //             ->leftJoin('forum_count_details','forum_posts.id','=','forum_count_details.created_post_replies_id')
                //             ->select('created_post_id',DB::raw("SUM(forum_count_details.likes) as likes"),DB::raw("SUM(forum_count_details.dislikes) as dislikes"),DB::raw("SUM(forum_count_details.views) as views"),DB::raw("SUM(forum_count_details.replies) as replies"),DB::raw("SUM(forum_count_details.favorite) as favorite"),'activity')
                //             ->Groupby('created_post_id')
                //             ->leftJoin('forum_categorys','forum_posts.category','=','forum_categorys.category_id');
                //     },'aa')
                //     ->select('*');
                //    dd($success);

                return $this->successResponse($success, 'Post record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in postEdit');
        }
    }
    // forum all Threads post branch id wise
    public function ThreadspostList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $success = DB::table("forum_posts")
                    ->select(
                        'forum_posts.id as id',
                        'forum_posts.topic_title',
                        'forum_posts.user_id as user_id',
                        'forum_posts.user_name',
                        'forum_categorys.category_names',
                        'forum_posts.topic_header',
                        'forum_posts.created_at',
                        'forum_posts.category',
                        'forum_count_details.likes',
                        'forum_count_details.dislikes',
                        'forum_count_details.views',
                        'forum_count_details.replies',
                        'forum_count_details.favorite',
                        'favorite',
                        'activity'
                    )

                    ->leftjoin(
                        DB::raw("(SELECT user_id,user_name,created_post_id,SUM(likes) as likes,SUM(dislikes) as dislikes,SUM(views) as views,SUM(replies) as replies ,SUM(favorite) as favorite,activity FROM forum_count_details GROUP BY created_post_id) as forum_count_details"),
                        function ($join) {
                            $join->on("forum_count_details.created_post_id", "=", "forum_posts.id");
                        }
                    )
                    ->leftjoin(
                        DB::raw("(SELECT id as category_id,category_names from forum_categorys) as forum_categorys"),
                        function ($join) {

                            $join->on("forum_categorys.category_id", "=", "forum_posts.category");
                        }
                    )
                    ->where('forum_posts.branch_id', '=', $request->branch_id)
                    ->where('forum_posts.threads_status', '=', 1)
                    ->get();
                return $this->successResponse($success, 'Threads Post record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in ThreadspostList');
        }
    }
    // forum post list category wise
    public function postListCategory(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $success = DB::table('forum_posts')
                    ->leftJoin('forum_categorys', 'forum_categorys.id', '=', 'forum_posts.category')
                    ->leftJoin('forum_count_details', function ($join) {
                        $join->on('forum_count_details.created_post_id', '=', 'forum_posts.id')
                            ->orWhere('forum_posts.user_id', '`c.user_id`');
                    })
                    ->select('forum_posts.id', 'forum_posts.user_id', 'forum_posts.user_name', 'forum_posts.topic_title', 'forum_categorys.id as categId', 'forum_categorys.category_names', 'forum_count_details.likes', 'forum_count_details.dislikes', 'forum_count_details.favorite', 'forum_count_details.replies', 'forum_count_details.views', 'forum_count_details.activity', 'forum_posts.created_at', 'forum_posts.topic_header')
                    ->where([
                        ['forum_posts.branch_id', '=', $request->branch_id],
                        ['forum_posts.threads_status', '=', 2]
                    ])
                    ->whereRaw("find_in_set($request->user_id,forum_posts.tags)")
                    ->groupBy('forum_posts.category')
                    ->get();

                return $this->successResponse($success, 'Post List fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in postListCategory');
        }
    }
    public function adminpostListCategory(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $success = DB::table('forum_posts')
                    ->leftJoin('forum_categorys', 'forum_categorys.id', '=', 'forum_posts.category')
                    ->leftJoin('forum_count_details', function ($join) {
                        $join->on('forum_count_details.created_post_id', '=', 'forum_posts.id')
                            ->orWhere('forum_posts.user_id', '`c.user_id`');
                    })
                    ->select('forum_posts.id', 'forum_posts.user_id', 'forum_posts.user_name', 'forum_posts.topic_title', 'forum_categorys.id as categId', 'forum_categorys.category_names', 'forum_count_details.likes', 'forum_count_details.dislikes', 'forum_count_details.favorite', 'forum_count_details.replies', 'forum_count_details.views', 'forum_count_details.activity', 'forum_posts.created_at', 'forum_posts.topic_header')
                    ->where([
                        ['forum_posts.branch_id', '=', $request->branch_id],
                        ['forum_posts.threads_status', '=', 2]
                    ])
                    ->groupBy('forum_posts.category')
                    ->get();

                return $this->successResponse($success, 'Admin Post categ List fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in adminpostListCategory');
        }
    }
    public function userThreadspostList(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'user_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $success = DB::table("forum_posts")
                    ->select(
                        'forum_posts.id as id',
                        'forum_posts.topic_title',
                        'forum_posts.user_id as user_id',
                        'forum_posts.user_name',
                        'forum_categorys.category_names',
                        'forum_posts.topic_header',
                        'forum_posts.created_at',
                        'forum_posts.category',
                        'forum_count_details.likes',
                        'forum_count_details.dislikes',
                        'forum_count_details.views',
                        'forum_count_details.replies',
                        'forum_count_details.favorite',
                        'favorite',
                        'activity',
                        'forum_posts.threads_status'
                    )

                    ->leftjoin(
                        DB::raw("(SELECT user_id,user_name,created_post_id,SUM(likes) as likes,SUM(dislikes) as dislikes,SUM(views) as views,SUM(replies) as replies ,SUM(favorite) as favorite,activity FROM forum_count_details GROUP BY created_post_id) as forum_count_details"),
                        function ($join) {
                            $join->on("forum_count_details.created_post_id", "=", "forum_posts.id");
                        }
                    )
                    ->leftjoin(
                        DB::raw("(SELECT id as category_id,category_names from forum_categorys) as forum_categorys"),
                        function ($join) {

                            $join->on("forum_categorys.category_id", "=", "forum_posts.category");
                        }
                    )
                    ->where('forum_posts.branch_id', '=', $request->branch_id)
                    ->where('forum_posts.user_id', '=', $request->user_id)
                    ->get();
                return $this->successResponse($success, 'Threads Post record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in userThreadspostList');
        }
    }
    // forum single category posts
    public function singleCategoryPosts(Request $request)
    {
        try {

            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'categId' => 'required',
                'user_id' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $success = DB::table('forum_posts')
                    ->select('forum_posts.id', 'forum_posts.category as category', 'forum_posts.topic_title as topic_title', 'forum_posts.topic_header as topic_header', 'forum_posts.body_content as body_content', 'forum_posts.user_name as user_name', 'forum_posts.user_id as user_id', DB::raw('DATE_FORMAT(forum_posts.created_at, "%b %e %Y") as date'), 'forum_count_details.likes as likes', 'forum_count_details.dislikes as dislikes', 'forum_count_details.favorite as favorite', 'forum_count_details.replies as replies', 'forum_count_details.views as views', 'forum_count_details.activity as activity', 'forum_count_details.id as pkcount_details_id', 'forum_categorys.category_names', 'forum_posts.created_at')
                    ->leftJoin('forum_count_details', 'forum_posts.id', '=', 'forum_count_details.created_post_id')
                    ->leftJoin('forum_categorys', 'forum_categorys.id', '=', 'forum_posts.category')
                    ->where([
                        ['forum_posts.branch_id', '=', $request->branch_id],
                        //  ['forum_posts.user_id', '=', $request->user_id],
                        ['forum_posts.category', '=', $request->categId],
                        ['forum_posts.threads_status', '=', 2]
                    ])
                    ->groupBy('forum_posts.id')
                    ->get();
                return  $this->successResponse($success, 'Single Post category vs successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in singleCategoryPosts');
        }
    }
    // forum single category posts
    public function user_singleCategoryPosts(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'categId' => 'required',
                'user_id' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $success = DB::table('forum_posts')
                    ->select('forum_posts.id', 'forum_posts.category as category', 'forum_posts.topic_title as topic_title', 'forum_posts.topic_header as topic_header', 'forum_posts.body_content as body_content', 'forum_posts.user_name as user_name', 'forum_posts.user_id as user_id', DB::raw('DATE_FORMAT(forum_posts.created_at, "%b %e %Y") as date'), 'forum_count_details.likes as likes', 'forum_count_details.dislikes as dislikes', 'forum_count_details.favorite as favorite', 'forum_count_details.replies as replies', 'forum_count_details.views as views', 'forum_count_details.activity as activity', 'forum_count_details.id as pkcount_details_id', 'forum_categorys.category_names', 'forum_posts.created_at')
                    ->leftJoin('forum_count_details', 'forum_posts.id', '=', 'forum_count_details.created_post_id')
                    ->leftJoin('forum_categorys', 'forum_categorys.id', '=', 'forum_posts.category')
                    ->where([
                        ['forum_posts.branch_id', '=', $request->branch_id],
                        ['forum_posts.user_id', '=', $request->user_id],
                        ['forum_posts.category', '=', $request->categId],
                        ['forum_posts.threads_status', '=', 2]
                    ])
                    ->groupBy('forum_posts.id')
                    ->get();
                return  $this->successResponse($success, 'Single Post category vs successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in user_singleCategoryPosts');
        }
    }
    // forum user created post branch id wise
    public function postListUserCreatedOnly(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'user_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $success = DB::table('forum_posts')
                    ->leftJoin('forum_categorys', 'forum_categorys.id', '=', 'forum_posts.category')
                    ->select('forum_posts.id', 'forum_posts.user_id', 'forum_posts.user_name', 'forum_posts.topic_title', 'forum_categorys.category_names', 'forum_posts.created_at', 'forum_posts.topic_header')
                    ->where([
                        ['forum_posts.branch_id', '=', $request->branch_id],
                        ['forum_posts.user_id', '=', $request->user_id],
                        ['forum_posts.threads_status', '=', 2]
                    ])
                    // ->groupBy('forum_posts.user_id')
                    ->get();

                return $this->successResponse($success, 'User Created Post List successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in postListUserCreatedOnly');
        }
    }
    // forum user created category post branch id and user id wise
    public function categorypostListUserCreatedOnly(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'user_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $success = DB::table('forum_posts')
                    ->leftJoin('forum_categorys', 'forum_categorys.id', '=', 'forum_posts.category')
                    ->leftJoin('forum_count_details', function ($join) {
                        $join->on('forum_count_details.created_post_id', '=', 'forum_posts.id')
                            ->orWhere('forum_posts.user_id', '`c.user_id`');
                    })
                    ->select('forum_posts.id', 'forum_posts.user_id', 'forum_posts.user_name', 'forum_posts.topic_title', 'forum_categorys.id as categId', 'forum_categorys.category_names', 'forum_count_details.likes', 'forum_count_details.dislikes', 'forum_count_details.favorite', 'forum_count_details.replies', 'forum_count_details.views', 'forum_count_details.activity', 'forum_posts.created_at', 'forum_posts.topic_header')
                    ->where([
                        ['forum_posts.branch_id', '=', $request->branch_id],
                        ['forum_posts.user_id', '=', $request->user_id],
                        ['forum_posts.threads_status', '=', 2]
                    ])
                    ->groupBy('forum_posts.category')
                    ->get();

                return $this->successResponse($success, 'user vs category grid data fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in categorypostListUserCreatedOnly');
        }
    }
    // forum post replies branch id and post id wise
    public function userRepliespostall(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'id' => 'required',
                'user_id' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $success = DB::table('forum_post_replies')
                    ->select('forum_post_replies.id as pk_replies_id', 'forum_post_replies.created_at', 'forum_post_replies.created_post_id as created_post_id', 'forum_post_replies.branch_id as branch_id', 'forum_post_replies.user_id as user_id', 'forum_post_replies.user_name as user_name', 'replies_com', 'forum_post_replie_counts.id as pk_replies_count_id', 'forum_post_replie_counts.likes as likes', 'forum_post_replie_counts.dislikes as dislikes', 'forum_post_replie_counts.favorits as favorits', DB::raw('DATE_FORMAT(forum_post_replies.created_at, "%b %e %Y") as date'))
                    ->leftJoin('forum_post_replie_counts', 'forum_post_replies.id', '=', 'forum_post_replie_counts.created_post_replies_id')
                    //->where('forum_post_replies.created_post_id', '=', $request->id)
                    ->where([
                        ['forum_post_replies.branch_id', '=', $request->branch_id],
                        ['forum_post_replies.created_post_id', '=', $request->id]
                    ])
                    ->get();
                return  $this->successResponse($success, 'Post replies fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in userRepliespostall');
        }
    }
    // forum single post branch id and user id wise
    public function singlePost(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'id' => 'required',
                'branch_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $success = DB::table('forum_posts')
                    ->select('forum_posts.id', 'forum_posts.user_id', 'forum_posts.category as category', 'forum_posts.topic_title as topic_title', 'forum_posts.topic_header as topic_header', 'forum_posts.body_content as body_content', 'forum_posts.user_name as user_name', DB::raw('DATE_FORMAT(forum_posts.created_at, "%b %e %Y") as date'), DB::raw("SUM(forum_count_details.likes) as likes"), DB::raw("SUM(forum_count_details.dislikes) as dislikes"), DB::raw("SUM(forum_count_details.favorite) as favorite"), DB::raw("SUM(forum_count_details.replies) as replies"), DB::raw("SUM(forum_count_details.views) as views"), 'forum_count_details.activity as activity', 'forum_count_details.id as pkcount_details_id', 'forum_categorys.category_names', 'forum_posts.created_at')
                    ->leftJoin('forum_count_details', 'forum_posts.id', '=', 'forum_count_details.created_post_id')
                    ->join('forum_categorys', 'forum_posts.category', '=', 'forum_categorys.id')
                    ->where('forum_posts.branch_id', '=', $request->branch_id)
                    ->where('forum_posts.id', '=', $request->id)
                    ->groupBy('forum_count_details.created_post_id')
                    ->get();



                // DB::table('forum_posts')
                // ->select('forum_posts.id', 'forum_posts.topic_title as topic_title', 'forum_posts.topic_header as topic_header', 'forum_posts.body_content as body_content', 'forum_posts.user_name as user_name',  DB::raw('DATE_FORMAT(forum_posts.created_at, "%b %e %Y") as date'), 'forum_posts.category as category', 'forum_categorys.category_names as category_names')
                // ->leftJoin('forum_categorys','forum_posts.category','=','forum_categorys.id')
                // ->where('forum_posts.id','=',$request->id)
                // ->where('forum_posts.branch_id','=',$request->branch_id)
                // ->get();
                //     //like counts
                // $success['likescount'] =DB::table('forum_count_details')
                // ->select('forum_count_details.user_id',DB::raw("SUM('forum_count_details.likes') as likes"), DB::raw("SUM('forum_count_details.dislikes') as dislikes"), DB::raw("SUM('forum_count_details.favorite') as favorite"), DB::raw("SUM('forum_count_details.replies') as replies"), DB::raw("SUM('forum_count_details.views') as views"), 'forum_count_details.activity as activity', 'forum_count_details.id as pkcount_details_id')
                // ->where('forum_count_details.branch_id','=',$request->branch_id)
                // ->where('forum_count_details.created_post_id','=',$request->id)
                // ->groupBy('created_post_id')
                // ->get();

                // DB::table('forum_posts')
                //     ->select('forum_posts.id', 'forum_posts.category as category', 'forum_posts.topic_title as topic_title', 'forum_posts.topic_header as topic_header', 'forum_posts.body_content as body_content', 'forum_posts.user_name as user_name', DB::raw('DATE_FORMAT(forum_posts.created_at, "%b %e %Y") as date'), 'forum_count_details.likes as likes', 'forum_count_details.dislikes as dislikes', 'forum_count_details.favorite as favorite', 'forum_count_details.replies as replies', 'forum_count_details.views as views', 'forum_count_details.activity as activity', 'forum_count_details.id as pkcount_details_id', 'forum_categorys.category_names', 'forum_posts.created_at')
                //     ->leftJoin('forum_count_details', 'forum_posts.id', '=', 'forum_count_details.created_post_id')
                //     ->leftJoin('forum_count_details', 'forum_posts.user_id', '=', 'forum_count_details.user_id')
                //     ->leftJoin('forum_categorys', 'forum_posts.category', '=', 'forum_categorys.id')
                //     ->where([
                //         ['forum_posts.branch_id', '=', $request->branch_id],
                //         ['forum_posts.id', '=', $request->id],
                //         ['forum_posts.user_id', '=', $request->user_id]
                //     ])
                //     ->get();
                return  $this->successResponse($success, 'Single Post list fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in singlePost');
        }
    }
    // forum post replies branch id and post id wise
    public function singlePostReplies(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'id' => 'required',
                'user_id' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $branchid = $request->branch_id;
                $id = $request->id;
                $success = DB::query()->fromSub(function ($query) use ($branchid, $id) {
                    $query->from('forum_post_replies')
                        ->select('forum_post_replies.id as pk_replies_id', 'forum_post_replies.created_at', 'forum_post_replies.created_post_id as created_post_id', 'forum_post_replies.branch_id as branch_id', 'forum_post_replies.user_id as user_id', 'forum_post_replies.user_name as user_name', 'replies_com', 'forum_post_replie_counts.id as pk_replies_count_id', 'forum_post_replie_counts.likes as likes', 'forum_post_replie_counts.dislikes as dislikes', 'forum_post_replie_counts.favorits as favorits', DB::raw('DATE_FORMAT(forum_post_replies.created_at, "%b %e %Y") as date'))
                        ->leftJoin('forum_post_replie_counts', 'forum_post_replies.id', '=', 'forum_post_replie_counts.created_post_replies_id')
                        ->where('forum_post_replies.branch_id', '=', DB::raw("'$branchid'"))
                        ->where('forum_post_replies.created_post_id', '=', DB::raw("'$id'"));
                }, 'aa')
                    ->select('*')
                    ->where('aa.created_post_id', '=', $request->id)
                    ->get();



                // DB::table('forum_post_replies')
                //     ->select('forum_post_replies.id as pk_replies_id', 'forum_post_replies.created_at', 'forum_post_replies.created_post_id as created_post_id', 'forum_post_replies.branch_id as branch_id', 'forum_post_replies.user_id as user_id', 'forum_post_replies.user_name as user_name', 'replies_com', 'forum_post_replie_counts.id as pk_replies_count_id', 'forum_post_replie_counts.likes as likes', 'forum_post_replie_counts.dislikes as dislikes', 'forum_post_replie_counts.favorits as favorits', DB::raw('DATE_FORMAT(forum_post_replies.created_at, "%b %e %Y") as date'))
                //     ->leftJoin('forum_post_replie_counts', 'forum_post_replies.id', '=', 'forum_post_replie_counts.created_post_replies_id')
                //     //->where('forum_post_replies.created_post_id', '=', $request->id)
                //     ->where([
                //         ['forum_post_replies.branch_id', '=', $request->branch_id],
                //         ['forum_post_replies.created_post_id', '=', $request->id]
                //     ])
                //     ->groupBy('forum_post_replie_counts.created_post_id')
                //     ->get();
                return  $this->successResponse($success, 'Single Post replies fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in singlePostReplies');
        }
    }
    public function forumCreatePost(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'branch_id' => 'required',
                'user_id' => 'required',
                'user_name' => 'required',
                'token' => 'required',
                'topic_title' => 'required',
                'topic_header' => 'required',
                'types' => 'required',
                'body_content' => 'required',
                'category' => 'required',
                'tags' => 'required',
                // 'imagesorvideos' => 'required',
                'threads_status' => 'required'
            ]);
            //dd($request);
            if (!$validator->passes()) {
                return $this->send422Error('Validation errors.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $class = new Forum_posts();
                $getCount = Forum_posts::where('topic_title', '=', $request->topic_title)->get();
                //dd($getCount);
                if ($getCount->count() > 0) {
                    return $this->send422Error('Topic Title Already Exist', ['error' => 'Topic Title Already Exist']);
                } else {
                    $class->branch_id = $request->branch_id;
                    $class->user_id = $request->user_id;
                    $class->user_name = $request->user_name;
                    $class->topic_title = $request->topic_title;
                    $class->topic_header = $request->topic_header;
                    $class->types = $request->types;
                    $class->body_content = $request->body_content;
                    $class->category = $request->category;
                    $class->tags = $request->tags;
                    // $class->imagesorvideos = $request->imagesorvideos;
                    $class->threads_status = $request->threads_status;
                    $class->created_at = date("Y-m-d H:i:s");
                    $query = $class->save();
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'New post has been successfully created');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in forumCreatePost');
        }
    }
    public function forumUpdatePost(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'branch_id' => 'required',
                'user_id' => 'required',
                'user_name' => 'required',
                'token' => 'required',
                'topic_title' => 'required',
                'topic_header' => 'required',
                'body_content' => 'required',
                'category' => 'required',
                //  'tags' => 'required',
                //  'imagesorvideos' => 'required',
                'threads_status' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation errors.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                //  return $request;
                $getCount = Forum_posts::where('topic_title', '=', $request->topic_title)->where('id', '!=', $request->id)->get();
                //dd($getCount);
                if ($getCount->count() > 0) {
                    return $this->send422Error('Topic Title Already Exist', ['error' => 'Topic Title Already Exist']);
                } else {
                    $query = Forum_posts::find($request->id)->update([
                        'user_id' => $request->user_id,
                        'user_name' => $request->user_name,
                        'topic_title' => $request->topic_title,
                        'topic_header' => $request->topic_header,
                        'types' => $request->types,
                        'body_content' => $request->body_content,
                        'category' => $request->category,
                        'tags' => $request->tags,
                        // 'imagesorvideos' => $request->imagesorvideos,
                        'threads_status' => $request->threads_status,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    $success = [];
                    if (!$query) {
                        return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                    } else {
                        return $this->successResponse($success, 'New post has been successfully Updated');
                    }
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in forumUpdatePost');
        }
    }
    public function likescountadded(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'created_post_id' => 'required',
                'user_id' => 'required',
                'user_name' => 'required',
                'branch_id' => 'required',
                'likes' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                //   dd($request);
                $likesinsert = [
                    "created_post_id" => $request->created_post_id,
                    "user_id" => $request->user_id,
                    "user_name" => $request->user_name,
                    "branch_id" => $request->branch_id,
                    "likes" =>  $request->likes,
                    "flag" => 1,
                    'created_at' => date("Y-m-d H:i:s")
                ];

                $checkExist = DB::table('forum_count_details')->where([
                    ['created_post_id', '=', $request->created_post_id],
                    ['user_id', '=', $request->user_id],
                    ['flag', '>', 0]
                ])->first();

                if (empty($checkExist)) {
                    // echo "update";
                    DB::table('forum_count_details')->insert($likesinsert);
                } else {
                    $checkdislikecount = $checkExist->likes;

                    if ($checkdislikecount <= 0) {
                        // update data
                        $query = DB::table('forum_count_details')
                            ->where('id', $checkExist->id)
                            ->update([
                                'likes' => $request->likes,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                    }
                }
                $success = DB::table('forum_count_details')
                    ->select(DB::raw("SUM(likes) as likes"))
                    ->where('created_post_id', $request->created_post_id)
                    ->get();
                return $this->successResponse($success, 'like successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in likescountadded');
        }
    }
    public function dislikescountadded(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required'

            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $dislikesinsert = [
                    "created_post_id" => $request->created_post_id,
                    "user_id" => $request->user_id,
                    "user_name" => $request->user_name,
                    "branch_id" => $request->branch_id,
                    "dislikes" =>  $request->dislikes,
                    "flag" => 1,
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $checkExist = DB::table('forum_count_details')->where([
                    ['created_post_id', '=', $request->created_post_id],
                    ['user_id', '=', $request->user_id],
                    ['branch_id', '=', $request->branch_id],
                    ['flag', '>', 0]
                ])->first();

                if (empty($checkExist)) {
                    // echo "insert";
                    DB::table('forum_count_details')->insert($dislikesinsert);
                } else {
                    $checkdislikecount = $checkExist->dislikes;
                    if ($checkdislikecount <= 0) {
                        // update data
                        $query = DB::table('forum_count_details')
                            ->where('id', $checkExist->id)
                            ->update([
                                'dislikes' => $request->dislikes,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                    }
                }
                $success = DB::table('forum_count_details')
                    ->select(DB::raw("SUM(dislikes) as dislikes"))
                    ->where('created_post_id', $request->created_post_id)
                    ->get();
                return $this->successResponse($success, 'Rep Dislike successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in dislikescountadded');
        }
    }
    // forum heart count add
    public function heartcountadded(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'created_post_id' => 'required',
                'user_id' => 'required',
                'user_name' => 'required',
                'favorite' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $favoritsinsert = [
                    "created_post_id" => $request->created_post_id,
                    "user_id" => $request->user_id,
                    "user_name" => $request->user_name,
                    "branch_id" => $request->branch_id,
                    "favorite" =>  $request->favorite,
                    "flag" => 1,
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $checkExist = DB::table('forum_count_details')->where([
                    ['created_post_id', '=', $request->created_post_id],
                    ['user_id', '=', $request->user_id],
                    ['branch_id', '=', $request->branch_id],
                    ['flag', '>', 0]
                ])->first();

                if (empty($checkExist)) {
                    // echo "insert";
                    DB::table('forum_count_details')->insert($favoritsinsert);
                } else {
                    $checkfavoritscount = $checkExist->favorite;
                    if ($checkfavoritscount <= 0) {
                        // update data
                        $query = DB::table('forum_count_details')
                            ->where('id', $checkExist->id)
                            ->update([
                                'favorite' => $request->favorite,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                    }
                }
                $success = DB::table('forum_count_details')
                    ->select(DB::raw("SUM(favorite) as favorite"))
                    ->where('created_post_id', $request->created_post_id)
                    ->get();
                return $this->successResponse($success, 'Rep Favorits successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in heartcountadded');
        }
    }
    // forum post all replies branch id and post id wise
    public function PostAllReplies(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'user_id' => 'required',
                'branch_id' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $branchid = $request->branch_id;
                $user_id = $request->user_id;
                $success = DB::query()->fromSub(function ($query) use ($branchid, $user_id) {
                    $query->from('forum_posts')
                        ->select('forum_post_replies.id as post_replies_id', 'forum_posts.topic_title', 'forum_posts.branch_id', 'forum_post_replies.created_post_id', 'forum_post_replies.user_id', 'forum_post_replies.user_name', 'forum_post_replies.replies_com', 'forum_categorys.category_names', 'forum_post_replies.created_at')
                        ->leftJoin('forum_post_replies', 'forum_posts.id', '=', 'forum_post_replies.created_post_id')
                        ->leftJoin('forum_categorys', 'forum_posts.category', '=', 'forum_categorys.id');
                }, 'aa')
                    ->select('*')
                    ->where('user_id', '=', DB::raw("'$user_id'"))
                    ->where('branch_id', '=', DB::raw("'$branchid'"))
                    ->get();

                return  $this->successResponse($success, 'Post all replies fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in PostAllReplies');
        }
    }


    // forum view count add
    public function viewcountadded(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'id' => 'required',
                'token' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                DB::table('forum_count_details')
                    ->where('id', $request->id)
                    ->increment('views', 1);
                $success = DB::table('forum_count_details')
                    ->select('views', 'likes', 'dislikes', 'favorite')
                    ->where('id', $request->id)
                    ->get();

                return $this->successResponse($success, 'views successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in viewcountadded');
        }
    }
    // forum like count add
    public function likescountadded(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'created_post_id' => 'required',
                'user_id' => 'required',
                'user_name' => 'required',
                'branch_id' => 'required',
                'likes' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                //   dd($request);
                $likesinsert = [
                    "created_post_id" => $request->created_post_id,
                    "user_id" => $request->user_id,
                    "user_name" => $request->user_name,
                    "branch_id" => $request->branch_id,
                    "likes" =>  $request->likes,
                    "flag" => 1,
                    'created_at' => date("Y-m-d H:i:s")
                ];

                $checkExist = DB::table('forum_count_details')->where([
                    ['created_post_id', '=', $request->created_post_id],
                    ['user_id', '=', $request->user_id],
                    ['flag', '>', 0]
                ])->first();

                if (empty($checkExist)) {
                    // echo "update";
                    DB::table('forum_count_details')->insert($likesinsert);
                } else {
                    $checkdislikecount = $checkExist->likes;

                    if ($checkdislikecount <= 0) {
                        // update data
                        $query = DB::table('forum_count_details')
                            ->where('id', $checkExist->id)
                            ->update([
                                'likes' => $request->likes,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                    }
                }
                $success = DB::table('forum_count_details')
                    ->select(DB::raw("SUM(likes) as likes"))
                    ->where('created_post_id', $request->created_post_id)
                    ->get();
                return $this->successResponse($success, 'like successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in likescountadded');
        }
    }
    // forum dislike count add
    public function dislikescountadded(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required'

            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $dislikesinsert = [
                    "created_post_id" => $request->created_post_id,
                    "user_id" => $request->user_id,
                    "user_name" => $request->user_name,
                    "branch_id" => $request->branch_id,
                    "dislikes" =>  $request->dislikes,
                    "flag" => 1,
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $checkExist = DB::table('forum_count_details')->where([
                    ['created_post_id', '=', $request->created_post_id],
                    ['user_id', '=', $request->user_id],
                    ['branch_id', '=', $request->branch_id],
                    ['flag', '>', 0]
                ])->first();

                if (empty($checkExist)) {
                    // echo "insert";
                    DB::table('forum_count_details')->insert($dislikesinsert);
                } else {
                    $checkdislikecount = $checkExist->dislikes;
                    if ($checkdislikecount <= 0) {
                        // update data
                        $query = DB::table('forum_count_details')
                            ->where('id', $checkExist->id)
                            ->update([
                                'dislikes' => $request->dislikes,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                    }
                }
                $success = DB::table('forum_count_details')
                    ->select(DB::raw("SUM(dislikes) as dislikes"))
                    ->where('created_post_id', $request->created_post_id)
                    ->get();
                return $this->successResponse($success, 'Rep Dislike successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in dislikescountadded');
        }
    }
    public function viewcountinsert(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'user_id' => 'required',
                'user_name' => 'required',
                'create_post_id' => 'required',
                'views' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $section = new Forum_count_details();
                $section->user_id = $request->user_id;
                $section->user_name = $request->user_name;
                $section->created_post_id = $request->create_post_id;
                $section->views = $request->views;
                $section->branch_id = $request->branch_id;
                $section->flag = 1;
                $query = $section->save();
                $success = [];
                if (!$query) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    $success = DB::table('forum_count_details')
                        ->select(DB::raw("SUM(views) as views"), 'id')
                        ->where('created_post_id', $request->create_post_id)
                        ->get();
                    return $this->successResponse($success, 'View has been successfully hit');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in viewcountinsert');
        }
    }
    public function repliesinsert(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required',
                'user_id' => 'required',
                'user_name' => 'required',
                'create_post_id' => 'required',
                'replies_com' => 'required'
            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {

                $Creted_post_replies_id = DB::table('forum_post_replies')->insertGetId([
                    'user_id' => $request->user_id,
                    'user_name' => $request->user_name,
                    'created_post_id' => $request->create_post_id,
                    'branch_id' => $request->branch_id,
                    'replies_com' => $request->replies_com,
                    'created_at' => date("Y-m-d H:i:s")
                ]);
                if (!$Creted_post_replies_id) {
                    return $this->send500Error('Something went wrong.', ['error' => 'Something went wrong']);
                } else {
                    //
                    $checkExist = DB::table('forum_count_details')->where([
                        ['created_post_id', '=', $request->create_post_id]
                    ])->first();
                    DB::table('forum_count_details')
                        ->where('id', $checkExist->id)
                        ->increment('replies', 1);
                    //
                    $getval = array($request->user_id, $request->user_name, $request->create_post_id, $request->replies_com, $Creted_post_replies_id);
                    return $this->successResponse($getval, 'Command has been successfully saved');
                }
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in repliesinsert');
        }
    }
    public function replikescountadded(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required'

            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                //
                $likesinsert = [
                    "created_post_id" => $request->created_post_id,
                    "created_post_replies_id" => $request->created_post_replies_id,
                    "user_id" => $request->user_id,
                    "user_name" => $request->user_name,
                    "branch_id" => $request->branch_id,
                    "likes" =>  $request->likes,
                    "flag" => 1,
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $checkExist = DB::table('forum_post_replie_counts')->where([
                    ['created_post_id', '=', $request->created_post_id],
                    ['created_post_replies_id', '=', $request->created_post_replies_id],
                    ['user_id', '=', $request->user_id],
                    ['flag', '>', 0]
                ])->first();

                if (empty($checkExist)) {
                    // echo "update";
                    DB::table('forum_post_replie_counts')->insert($likesinsert);
                } else {
                    $checkdislikecount = $checkExist->likes;

                    if ($checkdislikecount <= 0) {
                        // update data
                        $query = DB::table('forum_post_replie_counts')
                            ->where('id', $checkExist->id)
                            ->update([
                                'likes' => $request->likes,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                    }
                }
                $success = DB::table('forum_post_replie_counts')
                    ->select(DB::raw("SUM(likes) as likes"))
                    ->where('created_post_replies_id', $request->created_post_replies_id)
                    ->get();
                return $this->successResponse($success, 'Replike successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in replikescountadded');
        }
    }
    // forum replies dislikes count add
    public function repdislikescountadded(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required'

            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                //
                $dislikesinsert = [
                    "created_post_id" => $request->created_post_id,
                    "created_post_replies_id" => $request->created_post_replies_id,
                    "user_id" => $request->user_id,
                    "user_name" => $request->user_name,
                    "branch_id" => $request->branch_id,
                    "dislikes" =>  $request->dislikes,
                    "flag" => 1,
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $checkExist = DB::table('forum_post_replie_counts')->where([
                    ['created_post_id', '=', $request->created_post_id],
                    ['created_post_replies_id', '=', $request->created_post_replies_id],
                    ['user_id', '=', $request->user_id],
                    ['branch_id', '=', $request->branch_id],
                    ['flag', '>', 0]
                ])->first();

                if (empty($checkExist)) {
                    // echo "insert";
                    DB::table('forum_post_replie_counts')->insert($dislikesinsert);
                } else {
                    $checkdislikecount = $checkExist->dislikes;
                    if ($checkdislikecount <= 0) {
                        // update data
                        $query = DB::table('forum_post_replie_counts')
                            ->where('id', $checkExist->id)
                            ->update([
                                'dislikes' => $request->dislikes,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                    }
                }
                $success = DB::table('forum_post_replie_counts')
                    ->select(DB::raw("SUM(dislikes) as dislikes"))
                    ->where('created_post_replies_id', $request->created_post_replies_id)
                    ->get();
                return $this->successResponse($success, 'Rep Dislike successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in repdislikescountadded');
        }
    }
    public function repfavcountadded(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'created_post_id' => 'required',
                'created_post_replies_id' => 'required',
                'user_id' => 'required',
                'user_name' => 'required',
                'favorits' => 'required'

            ]);

            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                //
                $favoritsinsert = [
                    "created_post_id" => $request->created_post_id,
                    "created_post_replies_id" => $request->created_post_replies_id,
                    "user_id" => $request->user_id,
                    "user_name" => $request->user_name,
                    "branch_id" => $request->branch_id,
                    "favorits" =>  $request->favorits,
                    "flag" => 1,
                    'created_at' => date("Y-m-d H:i:s")
                ];
                $checkExist = DB::table('forum_post_replie_counts')->where([
                    ['created_post_id', '=', $request->created_post_id],
                    ['created_post_replies_id', '=', $request->created_post_replies_id],
                    ['user_id', '=', $request->user_id],
                    ['branch_id', '=', $request->branch_id],
                    ['flag', '>', 0]
                ])->first();

                if (empty($checkExist)) {
                    // echo "insert";
                    DB::table('forum_post_replie_counts')->insert($favoritsinsert);
                } else {
                    $checkfavoritscount = $checkExist->favorits;
                    if ($checkfavoritscount <= 0) {
                        // update data
                        $query = DB::table('forum_post_replie_counts')
                            ->where('id', $checkExist->id)
                            ->update([
                                'favorits' => $request->favorits,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                    }
                }
                $success = DB::table('forum_post_replie_counts')
                    ->select(DB::raw("SUM(favorits) as favorits"))
                    ->where('created_post_replies_id', $request->created_post_replies_id)
                    ->get();
                return $this->successResponse($success, 'Rep Disfav successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in repfavcountadded');
        }
    }
    public function threadstatusupdate(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [

                'id' => 'required',
                'branch_id' => 'required',
                'token' => 'required',
                'user_id' => 'required'
            ]);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                $success = DB::table('forum_posts')->where('id', $request->id)->update([
                    'threads_status' => $request->threads_status,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            }
            return  $this->successResponse($success, 'Thread status successfully Updated');
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in threadstatusupdate');
        }
    }
    public function usernameautocomplete(Request $request)
    {
        // $validator = \Validator::make($request->all(), [

        //     'token' => 'required'

        // ]);
        // //dd($validator);
        // if (!$validator->passes()) {
        //     return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
        // } else {
        //        // create new connection
        //     $success = DB::table('users')->select('id','name')
        //     ->where('id','!=',1)
        //     ->where('id','!=',$request->user_id)
        //     ->get();
        //  //   $success = Category::all();
        //     return $this->successResponse($success, 'user name record fetch successfully');
        // }
        try {
            $validator = \Validator::make($request->all(), [

                'token' => 'required'

            ]);
            //dd($validator);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $success = DB::table('roles')->select('id', 'role_name as name')
                    ->where('id', '!=', 1)
                    ->where('id', '!=', $request->user_id)
                    ->get();
                //   $success = Category::all();
                return $this->successResponse($success, 'user name record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in usernameautocomplete');
        }
    }
    public function getuserid(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'token' => 'required',
                'branch_id' => 'required'

            ]);
            //dd($validator);
            if (!$validator->passes()) {
                return $this->send422Error('Validation error.', ['error' => $validator->errors()->toArray()]);
            } else {
                // create new connection
                $success = DB::table('users')->select('id', 'name')
                    ->where('id', '!=', $request->branch_id)
                    ->get();
                //  dd($success);
                //   $success = Category::all();
                return $this->successResponse($success, 'user name record fetch successfully');
            }
        } catch (Exception $error) {
            return $this->commonHelper->generalReturn('403', 'error', $error, 'Error in getuserid');
        }
    }
    protected function clearCache($cache_name, $branchId)
    {
        $cacheKey = $cache_name . $branchId;
        \Log::info('cacheClear ' . json_encode($cacheKey));
        Cache::forget($cacheKey);
    }
}
