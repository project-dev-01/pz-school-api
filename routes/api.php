<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\ApiControllerOne;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommonController;
use App\Http\Controllers\Api\ChatController;

use App\Http\Controllers\Api\TwoFactorAuth;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('indexing_migrate', [CommonController::class, 'indexingMigrate']);

Route::post('login', [AuthController::class, 'authenticate']);
Route::post('loginSA', [AuthController::class, 'authenticateSA']);
Route::post('login_branch', [AuthController::class, 'authenticateWithBranch']);
Route::post('reset_password', [AuthController::class, 'resetPassword']);
Route::post('reset_password_validation', [AuthController::class, 'resetPasswordValidation']);


Route::post('employee/punchcard', [AuthController::class, 'employeePunchCard']);
Route::post('employee/punchcard/check', [AuthController::class, 'employeePunchCardCheck']);
Route::get('get-countries', [CommonController::class, 'countryList']);
Route::post('get-states', [CommonController::class, 'getStateByIdList']);
Route::post('get-cities', [CommonController::class, 'getCityByIdList']);
// password_expired_link
// Route::post('reset/password_expired_link', [ApiControllerOne::class, 'passwordExpiredLink']);
Route::post('reset/password_expired_link', [ApiControllerOne::class, 'passwordExpiredLink']);
Route::post('reset/expire_reset_password', [AuthController::class, 'expireResetPassword']);
// 2fa start
Route::post('2fa/two_fa_generate_secret_qr', [TwoFactorAuth::class, 'twoFaGenerateSecretQr']);
Route::post('2fa/two_fa_otp_valid', [TwoFactorAuth::class, 'twoFaOtpValid']);
Route::post('2fa/update_two_fa_secret', [TwoFactorAuth::class, 'updateTwoFASecret']);

Route::post('get_school_type', [ApiController::class, 'getSchoolType']);
Route::post('get_home_page_details', [ApiController::class, 'getHomePageDetails']);
Route::post('firstlastscript', [CommonController::class, 'fistLastScript']);
Route::post('basesixfour', [CommonController::class, 'basesixfour']);

Route::post('application/relation/list', [ApiController::class, 'getApplicationRelationList']);
Route::post('application/academic_year/list', [ApiController::class, 'applicationAcademicYearList']);
Route::post('application/grade/list', [ApiController::class, 'getApplicationGradeList']);
Route::post('application/add', [ApiController::class, 'addApplication']);
// 2fa end
// Route::group(['middleware' => ['auth:api', 'logroute']], function () {
// Route::group(['middleware' => ['auth:api','check-single-session-api', 'logroute']], function () {
// except log route start
Route::post('all_logout', [AuthController::class, 'allLogout'])->middleware('auth:api', 'throttle:limit_per_user');
// except log route end
Route::group(['middleware' => ['auth:api', 'throttle:limit_per_user', 'logroute']], function () {
    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('get_user', [AuthController::class, 'get_user']);
    // section routes
    Route::post('section/add', [ApiController::class, 'addSection']);
    Route::get('section/list', [ApiController::class, 'getSectionList']);
    Route::post('section/section-details', [ApiController::class, 'getSectionDetails']);
    Route::post('section/update', [ApiController::class, 'updateSectionDetails']);
    Route::post('section/delete', [ApiController::class, 'deleteSection']);

    // branch routes
    Route::post('branch/add', [ApiController::class, 'addBranch']);
    Route::get('branch/list', [ApiController::class, 'getBranchList']);
    Route::post('branch/branch-details', [ApiController::class, 'getBranchDetails']);
    Route::post('branch/update', [ApiController::class, 'updateBranchDetails']);
    Route::post('branch/delete', [ApiController::class, 'deleteBranch']);

    // Class routes
    Route::post('classes/add', [ApiController::class, 'addClass']);
    Route::get('classes/list', [ApiController::class, 'getClassList']);
    Route::post('classes/class-details', [ApiController::class, 'getClassDetails']);
    Route::post('classes/update', [ApiController::class, 'updateClassDetails']);
    Route::post('classes/delete', [ApiController::class, 'deleteClass']);

    Route::post('teacher/class_list', [ApiController::class, 'teacherClassList']);

    // sections allocations routes
    Route::post('allocate_section/add', [ApiController::class, 'addSectionAllocation']);
    Route::get('allocate_section/list', [ApiController::class, 'getSectionAllocationList']);
    Route::post('allocate_section/section_allocation-details', [ApiController::class, 'getSectionAllocationDetails']);
    Route::post('allocate_section/update', [ApiController::class, 'updateSectionAllocation']);
    Route::post('allocate_section/delete', [ApiController::class, 'deleteSectionAllocation']);

    // TeacherAllocations routes
    Route::post('assign_teacher/add', [ApiController::class, 'addTeacherAllocation']);
    Route::get('assign_teacher/list', [ApiController::class, 'getTeacherAllocationList']);
    Route::post('assign_teacher/assign_teacher-details', [ApiController::class, 'getTeacherAllocationDetails']);
    Route::post('assign_teacher/update', [ApiController::class, 'updateTeacherAllocation']);
    Route::post('assign_teacher/delete', [ApiController::class, 'deleteTeacherAllocation']);
    Route::post('branch-by-assign-teacher', [ApiController::class, 'branchIdByTeacherAllocation']);
    // Add Subjects
    Route::post('subjects/add', [ApiController::class, 'addSubjects']);
    Route::get('subjects/list', [ApiController::class, 'getSubjectsList']);
    Route::post('subjects/subjects-details', [ApiController::class, 'getSubjectsDetails']);
    Route::post('subjects/update', [ApiController::class, 'updateSubjects']);
    Route::post('subjects/delete', [ApiController::class, 'deleteSubjects']);

    // class assign
    Route::post('class_assign/add', [ApiController::class, 'addClassAssign']);
    Route::get('class_assign/list', [ApiController::class, 'getClassAssignList']);
    Route::post('class_assign/class_assign-details', [ApiController::class, 'getClassAssignDetails']);
    Route::post('class_assign/update', [ApiController::class, 'updateClassAssign']);
    Route::post('class_assign/delete', [ApiController::class, 'deleteClassAssign']);

    // Teacher subject assign
    Route::post('teacher_assign/add', [ApiController::class, 'addTeacherSubject']);
    Route::get('teacher_assign/list', [ApiController::class, 'getTeacherListSubject']);
    Route::post('teacher_assign/teacher_assign-details', [ApiController::class, 'getTeacherDetailsSubject']);
    Route::post('teacher_assign/update', [ApiController::class, 'updateTeacherSubject']);
    Route::post('teacher_assign/delete', [ApiController::class, 'deleteTeacherSubject']);
    // get_assign_class_subjects
    Route::post('get_assign_class_subjects', [ApiController::class, 'getAssignClassSubjects']);



    // branch id by class
    Route::post('branch-by-class', [ApiController::class, 'branchIdByClass']);
    Route::post('branch-by-section', [ApiController::class, 'branchIdBySection']);
    Route::post('section-by-class', [ApiController::class, 'sectionByClass']);
    Route::post('subject-by-class', [ApiController::class, 'subjectByClass']);
    Route::post('exam-by-subjects', [ApiController::class, 'examBySubjects']);
    Route::post('exam-by-teacher-subjects', [ApiController::class, 'examByTeacherSubjects']);
    Route::post('subject-by-papers', [ApiController::class, 'getSubjectByPaper']);
    Route::post('subject-by-exam-names', [ApiController::class, 'getsubjectByAssignTest']);

    Route::post('timetable-subject', [ApiController::class, 'timetableSubject']);
    Route::get('tot_grade_calcu_byStdsubjectdiv', [ApiController::class, 'totgradecalcubyStudent_subjectdiv']);
    Route::get('tot_grade_master', [ApiController::class, 'totgrademaster']);
    Route::post('all_exams_list', [ApiController::class, 'allexamslist']);
    Route::get('all_std_list', [ApiController::class, 'allstdlist']);
    Route::post('get_grade_bysubject', [ApiController::class, 'getGradebysubject']);
    // Event Type routes
    Route::post('event_type/add', [ApiController::class, 'addEventType']);
    Route::get('event_type/list', [ApiController::class, 'getEventTypeList']);
    Route::post('event_type/event_type-details', [ApiController::class, 'getEventTypeDetails']);
    Route::post('event_type/update', [ApiController::class, 'updateEventType']);
    Route::post('event_type/delete', [ApiController::class, 'deleteEventType']);

    // Event routes
    Route::post('event/add', [ApiController::class, 'addEvent']);
    Route::get('event/list', [ApiController::class, 'getEventList']);
    Route::get('event/list/student', [ApiController::class, 'getEventListStudent']);
    Route::post('event/event-details', [ApiController::class, 'getEventDetails']);
    Route::post('event/update', [ApiController::class, 'updateEvent']);
    Route::post('event/delete', [ApiController::class, 'deleteEvent']);
    Route::post('event/publish', [ApiController::class, 'publishEvent']);
    Route::post('branch-by-event', [ApiController::class, 'branchIdByEvent']);
    // qualifications
    Route::post('qualification/add', [ApiController::class, 'add_qualifications']);
    Route::get('qualification/list', [ApiController::class, 'getQualificationsList']);
    Route::post('qualifications/qualifications-details', [ApiController::class, 'getQualifications']);
    Route::post('qualification/update', [ApiController::class, 'updateQualifications']);
    Route::post('qualification/delete', [ApiController::class, 'deleteQualifications']);
    // staff category
    Route::post('staffcategory/add', [ApiController::class, 'add_staffcategory']);
    Route::get('staffcategory/list', [ApiController::class, 'getstaffcategory']);
    Route::post('staffcategory/staffcategory-details', [ApiController::class, 'getstaffcategory_details']);
    Route::post('staffcategory/update', [ApiController::class, 'updatestaffcategory']);
    Route::post('staffcategory/delete', [ApiController::class, 'deletestaffcategory']);
    // department routes
    Route::post('department/add', [ApiController::class, 'addDepartment']);
    Route::get('department/list', [ApiController::class, 'getDepartmentList']);
    Route::post('department/department-details', [ApiController::class, 'getDepartmentDetails']);
    Route::post('department/update', [ApiController::class, 'updateDepartment']);
    Route::post('department/delete', [ApiController::class, 'deleteDepartment']);
    // exam papers routes
    Route::post('exam_paper/add', [ApiController::class, 'addExamPaper']);
    Route::get('exam_paper/list', [ApiController::class, 'getExamPaperList']);
    Route::post('exam_paper/exam-paper-details', [ApiController::class, 'getExamPaperDetails']);
    Route::post('exam_paper/update', [ApiController::class, 'updateExamPaper']);
    Route::post('exam_paper/delete', [ApiController::class, 'deleteExamPaper']);
    // designations routes
    Route::post('designation/add', [ApiController::class, 'addDesignation']);
    Route::get('designation/list', [ApiController::class, 'getDesignationList']);
    Route::post('designation/designation-details', [ApiController::class, 'getDesignationDetails']);
    Route::post('designation/update', [ApiController::class, 'updateDesignation']);
    Route::post('designation/delete', [ApiController::class, 'deleteDesignation']);

    // staff position routes
    Route::post('staff_position/add', [ApiController::class, 'addStaffPosition']);
    Route::get('staff_position/list', [ApiController::class, 'getStaffPositionList']);
    Route::post('staff_position/staff_position-details', [ApiController::class, 'getStaffPositionDetails']);
    Route::post('staff_position/update', [ApiController::class, 'updateStaffPosition']);
    Route::post('staff_position/delete', [ApiController::class, 'deleteStaffPosition']);

    // Stream Type routes
    Route::post('stream_type/add', [ApiController::class, 'addStreamType']);
    Route::get('stream_type/list', [ApiController::class, 'getStreamTypeList']);
    Route::post('stream_type/stream_type-details', [ApiController::class, 'getStreamTypeDetails']);
    Route::post('stream_type/update', [ApiController::class, 'updateStreamType']);
    Route::post('stream_type/delete', [ApiController::class, 'deleteStreamType']);

    // Religion routes
    Route::post('religion/add', [ApiController::class, 'addReligion']);
    Route::get('religion/list', [ApiController::class, 'getReligionList']);
    Route::post('religion/religion-details', [ApiController::class, 'getReligionDetails']);
    Route::post('religion/update', [ApiController::class, 'updateReligion']);
    Route::post('religion/delete', [ApiController::class, 'deleteReligion']);

    // race routes
    Route::post('race/add', [ApiController::class, 'addRace']);
    Route::get('race/list', [ApiController::class, 'getRaceList']);
    Route::post('race/race-details', [ApiController::class, 'getRaceDetails']);
    Route::post('race/update', [ApiController::class, 'updateRace']);
    Route::post('race/delete', [ApiController::class, 'deleteRace']);

    // Exam Term routes 
    Route::post('exam_term/add', [ApiController::class, 'addExamTerm']);
    Route::get('exam_term/list', [ApiController::class, 'getExamTermList']);
    Route::post('exam_term/exam_term-details', [ApiController::class, 'getExamTermDetails']);
    Route::post('exam_term/update', [ApiController::class, 'updateExamTerm']);
    Route::post('exam_term/delete', [ApiController::class, 'deleteExamTerm']);

    // Exam Hall routes 
    Route::post('exam_hall/add', [ApiController::class, 'addExamHall']);
    Route::get('exam_hall/list', [ApiController::class, 'getExamHallList']);
    Route::post('exam_hall/exam_hall-details', [ApiController::class, 'getExamHallDetails']);
    Route::post('exam_hall/update', [ApiController::class, 'updateExamHall']);
    Route::post('exam_hall/delete', [ApiController::class, 'deleteExamHall']);

    // Exam routes 
    Route::post('exam/add', [ApiController::class, 'addExam']);
    Route::get('exam/list', [ApiController::class, 'getExamList']);
    Route::post('exam/exam-details', [ApiController::class, 'getExamDetails']);
    Route::post('exam/update', [ApiController::class, 'updateExam']);
    Route::post('exam/delete', [ApiController::class, 'deleteExam']);

    // Exam Timetable routes 
    Route::post('exam_timetable/add', [ApiController::class, 'addExamTimetable']);
    Route::post('exam_timetable/list', [ApiController::class, 'listExamTimetable']);
    Route::post('exam_timetable/get', [ApiController::class, 'getExamTimetable']);
    Route::post('exam_timetable/delete', [ApiController::class, 'deleteExamTimetable']);
    // Exam Timetable routes for parent,student
    Route::post('exam_timetable/student_parent', [ApiControllerOne::class, 'examScheduleList']);
    Route::post('exam_timetable/get_student_parent', [ApiControllerOne::class, 'getExamTimetableList']);

    Route::get('relation/list', [ApiController::class, 'getRelationList']);
    // get roles
    Route::post('roles/list', [ApiController::class, 'getRoles']);

    //get Semester
    Route::get('semester/list', [ApiController::class, 'getSemesterList']);

    //get Session
    Route::get('session/list', [ApiController::class, 'getSessionList']);

    // Timetable
    Route::post('timetable/add', [ApiController::class, 'addTimetable']);
    Route::post('timetable/list', [ApiController::class, 'getTimetableList']);
    Route::post('timetable/edit', [ApiController::class, 'editTimetable']);
    Route::post('timetable/update', [ApiController::class, 'updateTimetable']);
    Route::post('timetable/copy', [ApiController::class, 'copyTimetable']);

    // Timetable Bulk
    Route::post('timetable-subject-bulk', [ApiController::class, 'timetableSubjectBulk']);
    Route::post('timetable/add/bulk', [ApiController::class, 'addBulkTimetable']);

    // Grade routes
    Route::post('grade/add', [ApiController::class, 'addGrade']);
    Route::get('grade/list', [ApiController::class, 'getGradeList']);
    Route::post('grade/grade-details', [ApiController::class, 'getGradeDetails']);
    Route::post('grade/update', [ApiController::class, 'updateGrade']);
    Route::post('grade/delete', [ApiController::class, 'deleteGrade']);
    // Grade category routes
    Route::get('grade/category', [ApiController::class, 'gradeCategory']);

    // employee routes
    Route::post('employee/department', [ApiController::class, 'getEmpDepartment']);
    Route::post('employee/designation', [ApiController::class, 'getEmpDesignation']);
    Route::post('employee/add', [ApiController::class, 'addEmployee']);
    Route::get('employee/list', [ApiController::class, 'getEmployeeList']);
    Route::post('employee/employee-details', [ApiController::class, 'getEmployeeDetails']);
    Route::post('employee/update', [ApiController::class, 'updateEmployee']);
    Route::post('employee/delete', [ApiController::class, 'deleteEmployee']);
    // get_qualifications
    Route::get('employee/get_qualifications', [ApiController::class, 'getQualificationsLst']);
    // staff_categories
    Route::get('employee/staff_categories', [ApiController::class, 'staffCategories']);
    // staff_positions
    Route::get('employee/staff_positions', [ApiController::class, 'staffPositions']);
    // stream_types
    Route::get('employee/stream_types', [ApiController::class, 'streamTypes']);
    // stream_types
    Route::get('employee/religion', [ApiController::class, 'getReligion']);
    // stream_types
    Route::get('employee/races', [ApiController::class, 'getRaces']);
    // settings
    Route::post('settings/staff_profile_info', [ApiController::class, 'getStaffProfileInfo']);
    Route::post('change-profile-picture', [ApiController::class, 'updatePicture']);
    Route::post('settings/logo', [ApiController::class, 'changeLogo']);
    Route::post('change-password', [ApiController::class, 'changePassword']);
    Route::post('update-profile-info', [ApiController::class, 'updateProfileInfo']);
    // parent settings
    Route::post('settings/parent_profile_info', [ApiController::class, 'getParentProfileInfo']);
    Route::post('update-parent-profile-info', [ApiController::class, 'updateParentProfileInfo']);
    Route::post('change-parent-profile-picture', [ApiController::class, 'updateParentPicture']);
    // student settings
    Route::post('settings/student_profile_info', [ApiController::class, 'getStudentProfileInfo']);
    Route::post('update-student-profile-info', [ApiController::class, 'updateStudentProfileInfo']);
    Route::post('change-student-profile-picture', [ApiController::class, 'updateStudentPicture']);

    // create database_migrate

    Route::post('database_migrate', [CommonController::class, 'databaseMigrate']);
    // Route::post('indexing_migrate', [CommonController::class, 'indexingMigrate']);
    // forum     
    Route::get('get-category', [CommonController::class, 'categoryList']);
    Route::get('get-dbnames', [CommonController::class, 'dbnameslist']);
    Route::post('get-branchid', [ApiController::class, 'schoolvsbranchid']);
    Route::get('forum/list', [ApiController::class, 'postList']);
    Route::get('forum/edit', [ApiController::class, 'postEdit']);
    Route::post('forum/delete', [ApiController::class, 'postDelete']);
    Route::get('forum/threadslist', [ApiController::class, 'ThreadspostList']);
    Route::get('forum/userthreadslist', [ApiController::class, 'userThreadspostList']);
    Route::get('forum/listcategory', [ApiController::class, 'postListCategory']);
    Route::get('forum/adminlistcategoryvs', [ApiController::class, 'adminpostListCategory']);
    Route::get('forum/singlepost', [ApiController::class, 'singlePost']);
    Route::get('forum/singlecateg', [ApiController::class, 'singleCategoryPosts']);
    Route::get('forum/usersinglecateg', [ApiController::class, 'user_singleCategoryPosts']);
    Route::get('forum/postlistusercreated', [ApiController::class, 'postListUserCreatedOnly']);
    Route::get('forum/listcategoryusercrd', [ApiController::class, 'categorypostListUserCreatedOnly']);
    Route::get('forum/singlepost/replies', [ApiController::class, 'singlePostReplies']);
    Route::get('forum/post/allreplies', [ApiController::class, 'PostAllReplies']);
    Route::post('forum/createpost', [ApiController::class, 'forumcreatepost']);
    Route::post('forum/updatepost', [ApiController::class, 'forumupdatepost']);
    Route::post('forum-likecout', [ApiController::class, 'likescountadded']);
    Route::post('forum-discout', [ApiController::class, 'dislikescountadded']);
    Route::post('forum-heartcout', [ApiController::class, 'heartcountadded']);
    Route::post('forum-viewcout', [ApiController::class, 'viewcountadded']);
    Route::post('forum-viewcout-insert', [ApiController::class, 'viewcountinsert']);
    Route::post('forum-replies-insert', [ApiController::class, 'repliesinsert']);
    Route::post('forum-replikecout', [ApiController::class, 'replikescountadded']);
    Route::post('forum-repdislikecout', [ApiController::class, 'repdislikescountadded']);
    Route::post('forum-repfavorits', [ApiController::class, 'repfavcountadded']);
    Route::post('forum/threads/status/update', [ApiController::class, 'threadstatusupdate']);
    Route::get('forum/usernames/autocomplete', [ApiController::class, 'usernameautocomplete']);
    Route::get('forum/getuserid', [ApiController::class, 'getuserid']);

    // Test Result    
    Route::get('get_testresult_exams', [ApiController::class, 'examslist']);
    Route::post('get_paper_list', [ApiController::class, 'paperlist']);
    Route::post('get_testresult_marks_subject_vs', [ApiController::class, 'subject_vs_marks']);
    Route::post('get_marks_vs_grade', [ApiController::class, 'marks_vs_grade']);
    Route::post('add_student_marks', [ApiController::class, 'addStudentMarks']);
    Route::post('get_subject_division', [ApiController::class, 'getsubjectdivision']);
    Route::post('get_subject_average', [ApiController::class, 'getSubjectAverage']);
    Route::post('add_subject_division', [ApiController::class, 'addsubjectdivision']);
    Route::post('get_student_subject_mark', [ApiController::class, 'getStudentSubjectMark']);
    Route::post('get_student_grade', [ApiController::class, 'getStudentGrade']);
    Route::post('get_subject_division_mark', [ApiController::class, 'getSubDivisionMark']);
    Route::post('get_subject_mark_status', [ApiController::class, 'getSubjectMarkStatus']);
    // get exam paper results
    Route::post('get_exam_paper_results', [ApiControllerOne::class, 'getExamPaperResults']);
    // classroom management
    Route::post('teacher_class', [ApiController::class, 'getTeachersClassName']);
    Route::post('teacher_section', [ApiController::class, 'getTeachersSectionName']);
    Route::post('teacher_subject', [ApiController::class, 'getTeachersSubjectName']);


    Route::post('timetable/student', [ApiController::class, 'studentTimetable']);
    Route::post('timetable/parent', [ApiController::class, 'parentTimetable']);
    // Homework routes
    Route::post('homework/add', [ApiController::class, 'addHomework']);
    Route::post('homework/list', [ApiController::class, 'getHomeworkList']);
    Route::post('homework/view', [ApiController::class, 'viewHomework']);
    Route::post('homework/homework-details', [ApiController::class, 'getHomeworkDetails']);
    Route::get('homework/all_list', [ApiController::class, 'getHomeworkAllList']);

    Route::post('homework/evaluate', [ApiController::class, 'evaluateHomework']);
    Route::post('homework/submit', [ApiController::class, 'submitHomework']);
    Route::post('homework/student', [ApiController::class, 'studentHomework']);
    Route::post('homework/student/filter', [ApiController::class, 'studentHomeworkFilter']);

    //  getStudentAttendence
    Route::post('get_student_attendance', [ApiController::class, 'getStudentAttendence']);
    Route::post('add_student_attendance', [ApiController::class, 'addStudentAttendence']);
    Route::post('get_short_test', [ApiController::class, 'getShortTest']);
    Route::post('add_short_test', [ApiController::class, 'addShortTest']);
    Route::post('add_daily_report', [ApiController::class, 'addDailyReport']);
    Route::post('get_daily_report_remarks', [ApiController::class, 'getDailyReportRemarks']);
    Route::post('add_daily_report_remarks', [ApiController::class, 'addDailyReportRemarks']);
    Route::post('get_classroom_widget_data', [ApiController::class, 'getClassroomWidget']);
    Route::post('add_daily_report_by_student', [ApiController::class, 'addDailyReportByStudent']);

    // get studenet attenedance list
    Route::post('get_attendance_list', [ApiController::class, 'getAttendanceList']);
    Route::post('get_child_subjects', [ApiController::class, 'getChildSubjects']);
    Route::post('get_attendance_list_teacher', [ApiController::class, 'getAttendanceListTeacher']);
    Route::post('get_attendance_list_parent', [ApiController::class, 'getAttendanceListParent']);
    Route::post('get_reasons_by_student', [ApiController::class, 'getReasonsByStudent']);
    // get calendor data by teacher
    Route::get('get_timetable_calendor', [ApiController::class, 'getTimetableCalendor']);
    Route::get('get_event_calendor', [ApiController::class, 'getEventCalendor']);
    Route::get('get_timetable_calendor_student', [ApiController::class, 'getTimetableCalendorStud']);
    Route::get('get_event_calendor_student', [ApiController::class, 'getEventCalendorStud']);
    Route::get('get_event_calendor_admin', [ApiController::class, 'getEventCalendorAdmin']);


    Route::get('get_event_group_calendor', [ApiController::class, 'getEventGroupCalendor']);
    Route::get('get_event_group_calendor_student', [ApiController::class, 'getEventGroupCalendorStud']);
    Route::get('get_event_group_calendor_parent', [ApiController::class, 'getEventGroupCalendorParent']);
    Route::get('get_event_group_calendor_admin', [ApiController::class, 'getEventGroupCalendorAdmin']);


    Route::get('get_bulk_calendor_teacher', [ApiController::class, 'getBulkCalendorTeacher']);
    Route::get('get_bulk_calendor_admin', [ApiController::class, 'getBulkCalendorAdmin']);
    Route::get('get_bulk_calendor_student', [ApiController::class, 'getBulkCalendorStudent']);

    // add timetable schedule
    Route::post('add_calendor_timetable', [ApiController::class, 'addCalendorTimetable']);

    // Hostel routes
    Route::post('hostel/add', [ApiController::class, 'addHostel']);
    Route::get('hostel/list', [ApiController::class, 'getHostelList']);
    Route::post('hostel/hostel-details', [ApiController::class, 'getHostelDetails']);
    Route::post('hostel/update', [ApiController::class, 'updateHostel']);
    Route::post('hostel/delete', [ApiController::class, 'deleteHostel']);


    // Hostel Category routes
    Route::post('hostel_category/add', [ApiController::class, 'addHostelCategory']);
    Route::get('hostel_category/list', [ApiController::class, 'getHostelCategoryList']);
    Route::post('hostel_category/hostel_category-details', [ApiController::class, 'getHostelCategoryDetails']);
    Route::post('hostel_category/update', [ApiController::class, 'updateHostelCategory']);
    Route::post('hostel_category/delete', [ApiController::class, 'deleteHostelCategory']);

    // Hostel Room routes
    Route::post('hostel_room/add', [ApiController::class, 'addHostelRoom']);
    Route::get('hostel_room/list', [ApiController::class, 'getHostelRoomList']);
    Route::post('hostel_room/hostel_room-details', [ApiController::class, 'getHostelRoomDetails']);
    Route::post('hostel_room/update', [ApiController::class, 'updateHostelRoom']);
    Route::post('hostel_room/delete', [ApiController::class, 'deleteHostelRoom']);
    Route::post('vehicle-by-route', [ApiController::class, 'vehicleByRoute']);
    Route::post('room-by-hostel', [ApiController::class, 'roomByHostel']);

    Route::post('floor-by-block', [ApiController::class, 'floorByBlock']);

    // Hostel Block routes
    Route::post('hostel_block/add', [ApiController::class, 'addHostelBlock']);
    Route::get('hostel_block/list', [ApiController::class, 'getHostelBlockList']);
    Route::post('hostel_block/hostel_block-details', [ApiController::class, 'getHostelBlockDetails']);
    Route::post('hostel_block/update', [ApiController::class, 'updateHostelBlock']);
    Route::post('hostel_block/delete', [ApiController::class, 'deleteHostelBlock']);

    // Hostel Floor routes
    Route::post('hostel_floor/add', [ApiController::class, 'addHostelFloor']);
    Route::get('hostel_floor/list', [ApiController::class, 'getHostelFloorList']);
    Route::post('hostel_floor/hostel_floor-details', [ApiController::class, 'getHostelFloorDetails']);
    Route::post('hostel_floor/update', [ApiController::class, 'updateHostelFloor']);
    Route::post('hostel_floor/delete', [ApiController::class, 'deleteHostelFloor']);

    // Admission routes
    Route::post('admission/add', [ApiController::class, 'addAdmission']);

    // Techer list by class and section routes
    Route::post('teacher/list', [ApiController::class, 'getTeacherList']);
    // add to do list
    Route::post('add_to_do_list', [ApiController::class, 'addToDoList']);
    Route::post('update_to_do_list', [ApiController::class, 'updateToDoList']);
    Route::get('get_to_do_list', [ApiController::class, 'getToDoList']);
    Route::post('get_to_do_row', [ApiController::class, 'getToDoListRow']);
    Route::post('delete_to_do_list', [ApiController::class, 'deleteToDoList']);
    Route::get('get_to_do_list_dashboard', [ApiController::class, 'getToDoListDashboard']);
    Route::post('read_update_todo', [ApiController::class, 'readUpdateTodo']);
    Route::post('get_assign_class', [ApiController::class, 'getAssignClass']);
    Route::post('to_do_comments', [ApiController::class, 'toDoComments']);
    Route::get('get_to_do_teacher', [ApiController::class, 'getToDoTeacher']);

    // Student routes
    Route::post('admission/add', [ApiController::class, 'addAdmission']);
    Route::post('student/list', [ApiController::class, 'getStudentList']);
    Route::post('student/update', [ApiController::class, 'updateStudent']);
    Route::post('student/student-details', [ApiController::class, 'getStudentDetails']);
    Route::post('student/delete', [ApiController::class, 'deleteStudent']);

    // parent routes
    Route::post('parent/add', [ApiController::class, 'addParent']);
    Route::get('parent/list', [ApiController::class, 'getParentList']);
    Route::post('parent/parent-details', [ApiController::class, 'getParentDetails']);
    Route::get('parent/name', [ApiController::class, 'getParentName']);
    Route::post('parent/update', [ApiController::class, 'updateParent']);
    Route::post('parent/delete', [ApiController::class, 'deleteParent']);
    // get all teacher list
    Route::get('get_all_teacher_list', [ApiController::class, 'getAllTeacherList']);
    Route::get('get_homework_list_dashboard', [ApiController::class, 'getHomeworkListDashboard']);
    Route::post('get_test_score_dashboard', [ApiController::class, 'getTestScoreDashboard']);
    // student leave apply
    Route::get('get_students_parentdashboard', [ApiController::class, 'get_studentsparentdashboard']);
    Route::post('std_leave_apply', [ApiController::class, 'student_leaveapply']);
    Route::get('get_student_leaves', [ApiController::class, 'get_studentleaves']);
    Route::get('get_leave_reasons', [ApiController::class, 'get_leavereasons']);
    Route::post('studentleave_list', [ApiController::class, 'get_particular_studentleave_list']);
    Route::post('std_leave_apply/reupload_file', [ApiController::class, 'reuploadFileStudent']);
    Route::post('staff_leave_apply/reupload_file', [ApiController::class, 'reuploadFileStaff']);

    Route::post('teacher_leave_approve', [ApiController::class, 'teacher_leaveapprove']);
    Route::post('get_all_student_leaves', [ApiController::class, 'getAllStudentLeaves']);

    Route::get('get_birthday_calendor_teacher', [ApiController::class, 'getBirthdayCalendorTeacher']);
    Route::get('get_birthday_calendor_admin', [ApiController::class, 'getBirthdayCalendorAdmin']);

    // Leave Type routes
    Route::post('leave_type/add', [ApiController::class, 'addLeaveType']);
    Route::get('leave_type/list', [ApiController::class, 'getLeaveTypeList']);
    Route::post('leave_type/leave_type-details', [ApiController::class, 'getLeaveTypeDetails']);
    Route::post('leave_type/update', [ApiController::class, 'updateLeaveType']);
    Route::post('leave_type/delete', [ApiController::class, 'deleteLeaveType']);

    // staff Leave Assign routes
    Route::post('staff_leave_assign/add', [ApiController::class, 'addStaffLeaveAssign']);
    Route::get('staff_leave_assign/list', [ApiController::class, 'getStaffLeaveAssignList']);
    Route::post('staff_leave_assign/staff_leave_assign-details', [ApiController::class, 'getStaffLeaveAssignDetails']);
    Route::post('staff_leave_assign/update', [ApiController::class, 'updateStaffLeaveAssign']);
    Route::post('staff_leave_assign/delete', [ApiController::class, 'deleteStaffLeaveAssign']);

    // Transport Route routes
    Route::post('transport_route/add', [ApiController::class, 'addTransportRoute']);
    Route::get('transport_route/list', [ApiController::class, 'getTransportRouteList']);
    Route::post('transport_route/transport_route-details', [ApiController::class, 'getTransportRouteDetails']);
    Route::post('transport_route/update', [ApiController::class, 'updateTransportRoute']);
    Route::post('transport_route/delete', [ApiController::class, 'deleteTransportRoute']);

    // Vehicle Master routes
    Route::post('transport_vehicle/add', [ApiController::class, 'addTransportVehicle']);
    Route::get('transport_vehicle/list', [ApiController::class, 'getTransportVehicleList']);
    Route::post('transport_vehicle/transport_vehicle-details', [ApiController::class, 'getTransportVehicleDetails']);
    Route::post('transport_vehicle/update', [ApiController::class, 'updateTransportVehicle']);
    Route::post('transport_vehicle/delete', [ApiController::class, 'deleteTransportVehicle']);

    // Transport Stoppage routes
    Route::post('transport_stoppage/add', [ApiController::class, 'addTransportStoppage']);
    Route::get('transport_stoppage/list', [ApiController::class, 'getTransportStoppageList']);
    Route::post('transport_stoppage/transport_stoppage-details', [ApiController::class, 'getTransportStoppageDetails']);
    Route::post('transport_stoppage/update', [ApiController::class, 'updateTransportStoppage']);
    Route::post('transport_stoppage/delete', [ApiController::class, 'deleteTransportStoppage']);

    // Transport Assign routes
    Route::post('transport_assign/add', [ApiController::class, 'addTransportAssign']);
    Route::get('transport_assign/list', [ApiController::class, 'getTransportAssignList']);
    Route::post('transport_assign/transport_assign-details', [ApiController::class, 'getTransportAssignDetails']);
    Route::post('transport_assign/update', [ApiController::class, 'updateTransportAssign']);
    Route::post('transport_assign/delete', [ApiController::class, 'deleteTransportAssign']);
    // staff leave apply
    Route::get('employee-leave/get_leave_types', [ApiController::class, 'getLeaveTypes']);
    Route::post('employee-leave/apply', [ApiController::class, 'staffLeaveApply']);
    Route::post('employee-leave/leave_history', [ApiController::class, 'staffLeaveHistory']);
    Route::get('get_all_staff_details', [ApiController::class, 'getAllStaffDetails']);
    Route::post('employee-leave/approved', [ApiController::class, 'staffLeaveApproved']);
    Route::post('employee-leave/assign_leave_approval', [ApiController::class, 'assignLeaveApproval']);
    Route::post('employee-leave/leave_approval_history_by_staff', [ApiController::class, 'leaveApprovalHistoryByStaff']);
    Route::post('employee-leave/leave_details', [ApiController::class, 'staffLeaveDetails']);
    Route::post('employee-leave/leave_taken_history', [ApiController::class, 'staffLeaveTakenHist']);

    //attendance Routes
    Route::get('attendance/employee_list', [ApiController::class, 'getEmployeeAttendanceList']);
    Route::post('attendance/employee_add', [ApiController::class, 'addEmployeeAttendance']);
    Route::post('attendance/employee_report', [ApiController::class, 'getEmployeeAttendanceReport']);
    // add-task-calendor
    Route::post('calendor/add-task-calendor', [ApiController::class, 'calendorAddTask']);
    Route::get('calendor/list-task-calendor', [ApiController::class, 'calendorListTask']);
    Route::get('calendor/edit-task-calendor', [ApiController::class, 'calendorEditRow']);
    Route::post('calendor/update-task-calendor', [ApiController::class, 'calendorUpdateRow']);
    Route::post('calendor/delete-task-calendor', [ApiController::class, 'calendorDeleteTask']);

    // Education routes
    Route::post('education/add', [ApiController::class, 'addEducation']);
    Route::get('education/list', [ApiController::class, 'getEducationList']);
    Route::post('education/education-details', [ApiController::class, 'getEducationDetails']);
    Route::post('education/update', [ApiController::class, 'updateEducation']);
    Route::post('education/delete', [ApiController::class, 'deleteEducation']);


    Route::post('employee_by_department', [ApiController::class, 'getEmployeeByDepartment']);
    // analytics routes
    Route::post('get_student_list/by_class_section', [ApiController::class, 'getStudListByClassSec']);
    Route::post('get_attendance_late_graph', [ApiController::class, 'getAttendanceReportGraph']);
    Route::post('get_homework_graph_by_student', [ApiController::class, 'viewHomeworkGraphByStudent']);
    Route::post('get_attitude_graph_by_student', [ApiController::class, 'getAttitudeGraphByStudent']);
    Route::post('get_short_test_by_student', [ApiController::class, 'getShortTestByStudent']);
    Route::post('get_subject_average_by_student', [ApiController::class, 'getSubjectAverageByStudent']);
    Route::post('get_exam_marks_by_student', [ApiController::class, 'getExamMarksByStudent']);
    Route::post('get_student_by_all_subjects', [ApiController::class, 'getStudentByAllSubjects']);
    Route::post('get_class_section_by_student', [ApiController::class, 'getClassSectionByStudent']);
    // get schedule exam details
    Route::get('get_schedule_exam_details', [ApiController::class, 'getScheduleExamDetails']);
    Route::get('get_schedule_exam_details_by_teacher', [ApiController::class, 'getScheduleExamDetailsBYTeacher']);
    Route::get('get_schedule_exam_details_by_student', [ApiController::class, 'getScheduleExamDetailsBYStudent']);
    // get unread notifications
    Route::get('unread_notifications', [ApiController::class, 'unreadNotifications']);
    Route::post('mark_as_read', [ApiController::class, 'markAsRead']);
    // get absent late excuse classroom
    Route::post('get_absent_late_excuse', [ApiController::class, 'getAbsentLateExcuse']);
    //get Teacher absent Excuse
    Route::post('get_teacher_absent_excuse', [ApiController::class, 'getTeacherAbsentExcuse']);


    // Group routes
    Route::post('group/add', [ApiController::class, 'addGroup']);
    Route::get('group/list', [ApiController::class, 'getGroupList']);
    Route::post('group/group-details', [ApiController::class, 'getGroupDetails']);
    Route::post('group/update', [ApiController::class, 'updateGroup']);
    Route::post('group/delete', [ApiController::class, 'deleteGroup']);

    // Hostel Group routes
    Route::post('hostel_group/add', [ApiController::class, 'addHostelGroup']);
    Route::get('hostel_group/list', [ApiController::class, 'getHostelGroupList']);
    Route::post('hostel_group/hostel_group-details', [ApiController::class, 'getHostelGroupDetails']);
    Route::post('hostel_group/update', [ApiController::class, 'updateHostelGroup']);
    Route::post('hostel_group/delete', [ApiController::class, 'deleteHostelGroup']);

    // Name routes

    Route::get('student/name', [ApiController::class, 'getStudentName']);
    Route::get('staff/name', [ApiController::class, 'getStaffName']);

    Route::get('get_semester_session', [ApiController::class, 'getSemesterSession']);

    // grade category routes
    Route::post('grade_category/add', [ApiControllerOne::class, 'addGradeCategory']);
    Route::get('grade_category/list', [ApiControllerOne::class, 'getGradeCategoryList']);
    Route::post('grade_category/grade-category-details', [ApiControllerOne::class, 'getGradeCategoryDetails']);
    Route::post('grade_category/update', [ApiControllerOne::class, 'updateGradeCategory']);
    Route::post('grade_category/delete', [ApiControllerOne::class, 'deleteGadeCategory']);
    // get class by all subjects
    Route::post('classes/all_subjects', [ApiControllerOne::class, 'classByAllSubjects']);
    Route::get('paper_type/list', [ApiControllerOne::class, 'getPaperTypeList']);
    // import parent details in csv
    Route::post('importcsv/parent', [ImportController::class, 'importCsvParents']);
    // import students details in csv
    Route::post('importcsv/student', [ImportController::class, 'importCsvStudents']);
    // import timetable details in csv
    Route::post('importcsv/timetable', [ApiControllerOne::class, 'importCsvTimetable']);
    // import add exam timetable details in csv
    Route::post('importcsv/exam_timetable', [ApiControllerOne::class, 'addExamTimetable']);

    // exam results routes
    // by class
    Route::post('exam_results/get_subject_by_class', [ApiControllerOne::class, 'getSubjectByClass']);
    Route::post('exam-by-classSubject', [ApiControllerOne::class, 'examByClassSubject']);
    Route::post('tot_grade_calcu_byclass', [ApiControllerOne::class, 'totgradeCalcuByClass']);
    // by subject
    Route::post('exam_results/get_class_by_section', [ApiControllerOne::class, 'getClassBySection']);
    Route::post('exam-by-classSection', [ApiControllerOne::class, 'examByClassSec']);
    Route::post('tot_grade_calcu_bySubject', [ApiControllerOne::class, 'totgradeCalcuBySubject']);
    // by student
    Route::post('tot_grade_calcu_byStudent', [ApiControllerOne::class, 'totgradeCalcuByStudent']);
    // by overall
    Route::post('tot_grade_calcu_overall', [ApiControllerOne::class, 'tot_grade_calcu_overall']);
    // by individual result
    Route::post('getbyresult', [ApiControllerOne::class, 'getbyresult_student']);
    // report card 
    Route::post('get_by_reportcard', [ApiControllerOne::class, 'getreportcard']);


    // absent reason routes
    Route::post('absent_reason/add', [ApiController::class, 'addAbsentReason']);
    Route::get('absent_reason/list', [ApiController::class, 'getAbsentReasonList']);
    Route::post('absent_reason/absent-reason-details', [ApiController::class, 'getAbsentReasonDetails']);
    Route::post('absent_reason/update', [ApiController::class, 'updateAbsentReason']);
    Route::post('absent_reason/delete', [ApiController::class, 'deleteAbsentReason']);

    // late reason routes
    Route::post('late_reason/add', [ApiController::class, 'addLateReason']);
    Route::get('late_reason/list', [ApiController::class, 'getLateReasonList']);
    Route::post('late_reason/late-reason-details', [ApiController::class, 'getLateReasonDetails']);
    Route::post('late_reason/update', [ApiController::class, 'updateLateReason']);
    Route::post('late_reason/delete', [ApiController::class, 'deleteLateReason']);

    // excused reason routes
    Route::post('excused_reason/add', [ApiController::class, 'addExcusedReason']);
    Route::get('excused_reason/list', [ApiController::class, 'getExcusedReasonList']);
    Route::post('excused_reason/excused-reason-details', [ApiController::class, 'getExcusedReasonDetails']);
    Route::post('excused_reason/update', [ApiController::class, 'updateExcusedReason']);
    Route::post('excused_reason/delete', [ApiController::class, 'deleteExcusedReason']);

    // semester routes
    Route::post('semester/add', [ApiController::class, 'addSemester']);
    Route::get('semester/list', [ApiController::class, 'getSemesterList']);
    Route::post('semester/semester-details', [ApiController::class, 'getSemesterDetails']);
    Route::post('semester/update', [ApiController::class, 'updateSemester']);
    Route::post('semester/delete', [ApiController::class, 'deleteSemester']);
    // department routes
    Route::post('academic_year/add', [ApiControllerOne::class, 'academicYearAdd']);
    Route::get('academic_year/list', [ApiControllerOne::class, 'academicYearList']);
    Route::post('academic_year/academic_year_details', [ApiControllerOne::class, 'academicYearDetails']);
    Route::post('academic_year/update', [ApiControllerOne::class, 'updateAcademicYear']);
    Route::post('academic_year/delete', [ApiControllerOne::class, 'deleteAcademicYear']);
    // add promotion
    Route::post('get_student_list/by_class_section_sem_ses', [ApiControllerOne::class, 'getStudListByClassSecSemSess']);
    Route::post('promotion/add', [ApiControllerOne::class, 'addPromotion']);

    // Gloabl Setting routes
    Route::post('global_setting/add', [ApiController::class, 'addGlobalSetting']);
    Route::get('global_setting/list', [ApiController::class, 'getGlobalSettingList']);
    Route::post('global_setting/global_setting-details', [ApiController::class, 'getGlobalSettingDetails']);
    Route::post('global_setting/update', [ApiController::class, 'updateGlobalSetting']);
    Route::post('global_setting/delete', [ApiController::class, 'deleteGlobalSetting']);

    //checking class room 
    Route::post('class_room_check', [ApiController::class, 'classRoomCheck']);
    // relief assignment
    Route::post('get_all_leave_relief_assignment', [ApiControllerOne::class, 'getAllLeaveReliefAssignment']);
    Route::post('get_subjects_by_staff_id_with_date', [ApiControllerOne::class, 'getSubjectsByStaffIdWithDate']);
    Route::post('relief_assignment_other_teacher', [ApiControllerOne::class, 'reliefAssignmentOtherTeacher']);
    Route::post('get_staff_list_by_timeslot', [ApiControllerOne::class, 'getStaffListByTimeslot']);
    Route::post('get_calendar_details_timetable', [ApiControllerOne::class, 'getCalendarDetailsTimetable']);
    // Route::post('get_staff_details', [ApiControllerOne::class, 'reliefAssignmentOtherTeacher']);

    //count
    Route::get('employee_count', [ApiController::class, 'employeeCount']);
    Route::get('student_count', [ApiController::class, 'studentCount']);
    Route::get('parent_count', [ApiController::class, 'parentCount']);
    Route::get('teacher_count', [ApiController::class, 'teacherCount']);
    Route::get('student_leave_count', [ApiController::class, 'studentLeaveCount']);


    Route::post('lastlogout', [AuthController::class, 'lastlogout']);
    // soap
    Route::post('soap/category/list', [ApiControllerOne::class, 'soapCategoryList']);
    Route::post('soap/sub_category/list', [ApiControllerOne::class, 'soapSubCategoryList']);
    Route::post('soap/filter_by_notes', [ApiControllerOne::class, 'soapFilterByNotes']);
    // soap crud
    Route::post('soap/add', [ApiControllerOne::class, 'soapAdd']);
    Route::get('soap/list', [ApiControllerOne::class, 'getSoapList']);
    Route::post('soap/soap-details', [ApiControllerOne::class, 'getSoapDetails']);
    Route::post('soap/update', [ApiControllerOne::class, 'updateSoap']);
    Route::post('soap/delete', [ApiControllerOne::class, 'deleteSoap']);

    // copy academic to next session
    Route::post('acdemic/copy/assign_teacher', [ApiControllerOne::class, 'acdemicCopyAssignTeacher']);
    Route::post('acdemic/copy/grade_assign', [ApiControllerOne::class, 'copyClassAssign']);
    Route::post('acdemic/copy/subject_teacher_assign', [ApiControllerOne::class, 'copySubjectTeacherAssign']);
    // copy exam master to next session
    Route::post('exam_master/copy/exam_setup', [ApiControllerOne::class, 'copyExamSetup']);
    Route::post('exam_master/copy/exam_paper', [ApiControllerOne::class, 'copyExamPaper']);

    // SoapSubject routes
    Route::post('soap_subject/add', [ApiControllerOne::class, 'addSoapSubject']);
    Route::get('soap_subject/list', [ApiControllerOne::class, 'getSoapSubjectList']);
    Route::post('soap_subject/soap_subject-details', [ApiControllerOne::class, 'getSoapSubjectDetails']);
    Route::post('soap_subject/update', [ApiControllerOne::class, 'updateSoapSubject']);
    Route::post('soap_subject/delete', [ApiControllerOne::class, 'deleteSoapSubject']);


    // SOAP category crud routes
    Route::post('soap_category/add', [ApiControllerOne::class, 'addSoapCategory']);
    Route::get('soap_category/list', [ApiControllerOne::class, 'getSoapCategoryList']);
    Route::post('soap_category/soap_category-details', [ApiControllerOne::class, 'getSoapCategoryDetails']);
    Route::post('soap_category/update', [ApiControllerOne::class, 'updateSoapCategory']);
    Route::post('soap_category/delete', [ApiControllerOne::class, 'deleteSoapCategory']);

    // SOAP sub category crud routes
    Route::post('soap_sub_category/add', [ApiControllerOne::class, 'addSoapSubCategory']);
    Route::get('soap_sub_category/list', [ApiControllerOne::class, 'getSoapSubCategoryList']);
    Route::post('soap_sub_category/soap_sub_category-details', [ApiControllerOne::class, 'getSoapSubCategoryDetails']);
    Route::post('soap_sub_category/update', [ApiControllerOne::class, 'updateSoapSubCategory']);
    Route::post('soap_sub_category/delete', [ApiControllerOne::class, 'deleteSoapSubCategory']);

    // SOAP Notes crud routes
    Route::post('soap_notes/add', [ApiControllerOne::class, 'addSoapNotes']);
    Route::get('soap_notes/list', [ApiControllerOne::class, 'getSoapNotesList']);
    Route::post('soap_notes/soap_notes-details', [ApiControllerOne::class, 'getSoapNotesDetails']);
    Route::post('soap_notes/update', [ApiControllerOne::class, 'updateSoapNotes']);
    Route::post('soap_notes/delete', [ApiControllerOne::class, 'deleteSoapNotes']);
    // download csv api
    Route::post('exam_timetable/list/download', [ApiControllerOne::class, 'getExamTimetableDown']);
    Route::post('staff_attendance/export', [ApiControllerOne::class, 'staffAttendanceReport']);
    Route::post('student_attendance/export', [ApiControllerOne::class, 'studentAttendanceReport']);



    Route::get('student_soap_list', [ApiControllerOne::class, 'studentSoapList']);

    Route::get('old_soap_student/list', [ApiControllerOne::class, 'getOldSoapStudentList']);

    Route::get('soap_student/list', [ApiControllerOne::class, 'getSoapStudentList']);


    Route::post('soap_log/list', [ApiControllerOne::class, 'getSoapLogList']);
    Route::post('soap_log/add', [ApiControllerOne::class, 'addSoapLog']);
    // add fees
    Route::post('fees/yearly/add', [ApiControllerOne::class, 'feesYearlyAdd']);
    Route::post('get_student_details', [ApiControllerOne::class, 'getStudentDetails']);

    // Payment Mode routes
    Route::post('payment_mode/add', [ApiControllerOne::class, 'addPaymentMode']);
    Route::get('payment_mode/list', [ApiControllerOne::class, 'getPaymentModeList']);
    Route::post('payment_mode/payment_mode-details', [ApiControllerOne::class, 'getPaymentModeDetails']);
    Route::post('payment_mode/update', [ApiControllerOne::class, 'updatePaymentMode']);
    Route::post('payment_mode/delete', [ApiControllerOne::class, 'deletePaymentMode']);

    // Payment Status routes
    Route::post('payment_status/add', [ApiControllerOne::class, 'addPaymentStatus']);
    Route::get('payment_status/list', [ApiControllerOne::class, 'getPaymentStatusList']);
    Route::post('payment_status/payment_status-details', [ApiControllerOne::class, 'getPaymentStatusDetails']);
    Route::post('payment_status/update', [ApiControllerOne::class, 'updatePaymentStatus']);
    Route::post('payment_status/delete', [ApiControllerOne::class, 'deletePaymentStatus']);

    // FeesType routes
    Route::post('fees_type/add', [ApiControllerOne::class, 'addFeesType']);
    Route::get('fees_type/list', [ApiControllerOne::class, 'getFeesTypeList']);
    Route::post('fees_type/fees_type-details', [ApiControllerOne::class, 'getFeesTypeDetails']);
    Route::post('fees_type/update', [ApiControllerOne::class, 'updateFeesType']);
    Route::post('fees_type/delete', [ApiControllerOne::class, 'deleteFeesType']);
    // fees allocation
    Route::post('fees/fees_allocation', [ApiControllerOne::class, 'feesAllocation']);
    Route::post('fees/fees_allocated_students', [ApiControllerOne::class, 'feesAllocatedStudents']);
    Route::post('fees/change_payment_mode', [ApiControllerOne::class, 'feesChangePaymentMode']);
    Route::post('fees/active_tab_details', [ApiControllerOne::class, 'feesActiveTabDetails']);
    Route::post('fees/get_pay_mode_id', [ApiControllerOne::class, 'getPayModeID']);
    // fees 
    Route::post('fees/get_fees_allocated_students', [ApiControllerOne::class, 'getFeesAllocatedStudents']);
    Route::post('fees/fees-details', [ApiControllerOne::class, 'getFeesDetails']);
    Route::post('fees/delete', [ApiControllerOne::class, 'deleteFeesDetails']);
    Route::post('fees/student_fees_history', [ApiControllerOne::class, 'studentFeesHistory']);
    Route::post('fees/update', [ApiControllerOne::class, 'updateFees']);
    Route::post('fees/fees_type_group', [ApiControllerOne::class, 'feesTypeGroup']);
    Route::post('fees/fees_status_check', [ApiControllerOne::class, 'feesStatusCheck']);
    // FeesGroup routes
    Route::post('fees_group/add', [ApiControllerOne::class, 'addFeesGroup']);
    Route::get('fees_group/list', [ApiControllerOne::class, 'getFeesGroupList']);
    Route::post('fees_group/fees_group-details', [ApiControllerOne::class, 'getFeesGroupDetails']);
    Route::post('fees_group/update', [ApiControllerOne::class, 'updateFeesGroup']);
    Route::post('fees_group/delete', [ApiControllerOne::class, 'deleteFeesGroup']);
    // ranking_ln_class_grade
    Route::post('ranking_ln_class_grade', [ApiControllerOne::class, 'rankingInClassGrade']);
    Route::post('all_exam_subject_scores', [ApiControllerOne::class, 'allExamSubjectScores']);
    Route::post('all_exam_subject_ranks', [ApiControllerOne::class, 'allExamSubjectRanks']);
    Route::post('exam_subject_mark_high_low_avg', [ApiControllerOne::class, 'examMarksByHighAvgLow']);
    Route::get('exam-by-student', [ApiControllerOne::class, 'examByStudent']);
    Route::post('get-marks-by-student', [ApiControllerOne::class, 'getMarksByStudent']);

    Route::post('get-ten-student', [ApiControllerOne::class, 'getTenStudent']);
    // chat conversations start
    Route::get('chat/get_teacher_list', [ChatController::class, 'chatGetTeacherList']);
    Route::get('chat/get_parent_list', [ChatController::class, 'chatGetParentList']);
    Route::get('chat/get_group_list', [ChatController::class, 'chatGetGroupList']);
    Route::get('chat/get_parentgroup_list', [ChatController::class, 'chatGetParentGroupList']);

    Route::get('chat/parent_chat_teacher_list', [ChatController::class, 'getParentChatTeacherList']);
    Route::get('chat/get_teacher_assign_parent_list', [ChatController::class, 'chatGetTeacherAssignParentList']);
    Route::get('chat/sent_messages', [ChatController::class, 'chatSentMessage']);
    Route::post('chat/storechat', [ChatController::class, 'storechat']);
    Route::post('chat/deletechat', [ChatController::class, 'deletechat']);
    Route::post('chat/chatlist', [ChatController::class, 'chatlists']);
    Route::post('chat/pchatlist', [ChatController::class, 'pchatlists']);

    Route::post('chat/groupchatlists', [ChatController::class, 'groupchatlists']);

    Route::post('chatnotification', [ChatController::class, 'chatnotification']);
    // chat conversations end
    Route::post('class_teacher_classes', [ApiControllerOne::class, 'classTeacherClass']);
    Route::post('class_teacher_sections', [ApiControllerOne::class, 'classTeacherSections']);


    // import Employee Master details in csv
    Route::post('importcsv/employee', [ImportController::class, 'importCsvEmployee']);
    Route::post('get_like_column_name', [ImportController::class, 'getLikeColumnName']);
    Route::post('faq/email', [ApiControllerOne::class, 'faqEmail']);
    Route::post('first/name', [ApiControllerOne::class, 'firstName']);


    Route::get('application/list', [ApiController::class, 'getApplicationList']);
    Route::post('application/application-details', [ApiController::class, 'getApplicationDetails']);
    Route::post('application/approve', [ApiController::class, 'approveApplication']);
    Route::post('application/update', [ApiController::class, 'updateApplication']);
    Route::post('application/delete', [ApiController::class, 'deleteApplication']);

    Route::post('forum_image_store', [ApiController::class, 'forumImageStore']);
    Route::get('get_languages', [ApiControllerOne::class, 'getLanguages']);

    // CheckIn Out Time routes
    Route::post('check_in_out_time/add', [ApiController::class, 'addCheckInOutTime']);
    Route::get('check_in_out_time/list', [ApiController::class, 'getCheckInOutTimeList']);
    Route::post('check_in_out_time/check_in_out_time-details', [ApiController::class, 'getCheckInOutTimeDetails']);
    Route::post('check_in_out_time/update', [ApiController::class, 'updateCheckInOutTime']);
    Route::post('check_in_out_time/delete', [ApiController::class, 'deleteCheckInOutTime']);

    // holidays routes
    Route::post('holidays/add', [ApiControllerOne::class, 'addHolidays']);
    Route::get('holidays/list', [ApiControllerOne::class, 'getHolidaysList']);
    Route::post('holidays/holidays-details', [ApiControllerOne::class, 'getHolidaysDetails']);
    Route::post('holidays/update', [ApiControllerOne::class, 'updateHolidaysDetails']);
    Route::post('holidays/delete', [ApiControllerOne::class, 'deleteHolidays']);

    Route::post('all_student/ranking', [ApiControllerOne::class, 'allStudentRanking']);

});
