    <?php
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Broadcast;
    use  App\Http\Controllers\Lookup\LookupController;
    use  App\Http\Controllers\Auth\LoginController;
    use  App\Http\Controllers\Auth\RegisterController;
    use  App\Http\Controllers\Auth\ForgetpasswordController;
    use  App\Http\Controllers\Auth\ProfileController;
    use  App\Http\Controllers\Auth\ProfilepictureController;
    use  App\Http\Controllers\Auth\PostController;
    use  App\Http\Controllers\Postcomments\CommentController;
    use App\Http\Controllers\Accessrolemenu\AccessrolemenuController;
    use App\Http\Controllers\System\Menus\MenuController;
    use App\Http\Controllers\System\Securityroles\SecurityroleController;
    use App\Http\Controllers\System\Roles\RoleController;
    use App\Http\Controllers\SearchAccount\UserController;
    use App\Http\Controllers\Select2\SelectController;
    use App\Http\Controllers\ChatController;
    use App\Http\Controllers\Follow\ClientsBAL;
    use App\Http\Controllers\SearchAccount\SearchHistoryBAL;
    use App\Http\Controllers\CV\UserLanguage;
    use App\Http\Controllers\CV\UserEducations;
    use App\Http\Controllers\CV\UserSkills;
    use App\Http\Controllers\CV\UserSeminars;
    use App\Http\Controllers\CV\UserTrainings;
    use App\Http\Controllers\CV\UserCertificates;
    use App\Http\Controllers\CV\UserWorkExperiences;
    use App\Http\Controllers\Follow\FollowController;
    use App\Http\Controllers\Jobs\JobPostingController;
    use App\Http\Controllers\Jobs\JobListController;
    use App\Http\Controllers\PhoneValidationController;
    use App\Http\Controllers\Jobs\QuestionController;
    use App\Http\Controllers\Jobs\AppliedJobController;
    use App\Http\Controllers\ReactionController;
    use App\Events\MessageSent; 
    use App\Events\Message;
    use App\Events\NotificationCountUpdated;
    use App\Http\Controllers\System\Submenu\Submenus;
    use App\Http\Controllers\System\Users\AppUsersController;
    use App\Http\Controllers\StorageBackblaze\PhotoUploadController;
    use App\Http\Controllers\Events\EventController;
    use App\Http\Controllers\Auth\GoogleAuthController;



    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider and all of them will
    | be assigned to the "api" middleware group. Make something great!
    |
    */  

    // PUBLIC
    // Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
    // Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

    Route::get("auth/google/redirect",[GoogleAuthController::class,"redirectToGoogle"]);
    Route::get("auth/google/callback",[GoogleAuthController::class,"handleGoogleCallback"]);
    Route::post('auth/google/set-role', [GoogleAuthController::class, 'setGoogleRole']);



    Route::post('login',[LoginController::class,'login'])->name('login');

    Route::post('resetpassword',[ForgetpasswordController::class,'resetpassword'])->name('resetpassword');

    Route::post('forgetpassword',[ForgetpasswordController::class,'forgetpassword'])->name('forgetpassword');

    Route::post('register',[RegisterController::class,'register'])->name('register');

    Route::post('accountactivation',[RegisterController::class,'accountactivation'])->name('accountactivation');
    Route::post('send-message', function (Request $request) {
    $message = $request->input('message');
    event(new MessageSent($message)); // ✅ Corrected event class name
    return response()->json(['success' => true, 'message' => $message]);
    });

    Route::get('testmail', function() {
        Mail::raw('Test email from Laravel', function($message) {
            $message->to('nexsuz.official13@gmail.com')
                    ->subject('Test Mail');
        });
        return 'Email sent';
    });

    Route::middleware('auth:api')->post('/profile/broadcasting/auth', function () {
    return Broadcast::auth(request());
    });
    
    // PROTECTED ROUTES
    Route::middleware(['auth:sanctum','checkstatus'])->group(function () {
    //accessmenu
    Route::Resource('accessmenu',AccessrolemenuController::class)->names('accessmenu');
    Route::post('saveAccessMenu', [SecurityroleController::class, 'saveAccessMenu']);
    Route::delete('deleteMenu', [SecurityroleController::class, 'deleteMenu']);
    // Route::get('getModule', [AccessrolemenuController::class, 'getModule']);
    Route::post('logout',[LoginController::class,'logout'])->name('logout');
    // PROFILE resource
    Route::Resource('profile',ProfileController::class)->names('profile');
    Route::get('user/profile',[ProfileController::class,'userAuth'])->name('user/profile');
    Route::resource('profile_pic',ProfilepictureController::class)->names('profile_pic');
    Route::post('uploadCoverPhoto', [ProfileController::class, 'uploadCoverPhoto']);
    //users
    Route::get('getUsers', [AppUsersController::class, 'getUsers']);

    // Menus
    Route::Resource('menu',MenuController::class)->names('menu');
    Route::post('saveMenu', [MenuController::class, 'saveMenu']);
    Route::get('getAllModules', [MenuController::class, 'getAllModules']);
    Route::delete('deleteMenu/{transNo}', [MenuController::class, 'deleteMenu']);
    Route::put('updateMenuById/{id}', [MenuController::class, 'updateMenuById']);


    //submenu
    Route::get('getSubmenuByTransNo/{transNo}', [Submenus::class, 'getSubmenuByMenuTransNo']);
    Route::delete('deleteSubmenu/{id}', [Submenus::class, 'deleteSubmenu']);
    Route::post('saveSubmenus', [Submenus::class, 'saveSubmenus']);
    Route::put('updateSubmenuById/{id}', [Submenus::class, 'updateSubmenuById']);
    // Security roles
    Route::Resource('security',SecurityroleController::class)->names('security');
    //Role
    Route::Resource('role',RoleController::class)->names('role');
    // SELECT2 ALL REQUEST
    Route::post('rolecode',[SelectController::class,'rolecode'])->name('rolecode');
    // lookup information
    Route::get('userlists',[LookupController::class,'userlists'])->name('userlists');
    //search fullname
    Route::get('searchUsers', [UserController::class, 'searchUsers']);
    //get Onlineusers
    Route::get('getIsOnline', [LoginController::class, 'getIsOnline']);

    //chat meesages realtime
    Route::post('send-message', [ChatController::class, 'sendMessage']);
    Route::post('messages/read', [ChatController::class, 'markAsRead']);
    Route::get('receivemessages/{receiverId}', [ChatController::class, 'fetchMessages']);
    Route::get('getActiveUsers', [ChatController::class, 'getActiveUsers']);
    Route::put('messagesIsread', [ChatController::class, 'markAsReadMessage']);

    Route::post('savePost', [PostController::class, 'savePost']);
    Route::post('updatePostByTransNo/{transNo}', [PostController::class, 'updatePostByTransNo']);
    Route::get('update_count', [ChatController::class, 'updateNotificationCount']);
    Route::get('getDataPost', [PostController::class, 'getDataPost']);

    Route::get('getNotificationsIsUnRead', [ChatController::class, 'getNotificationsIsUnRead']);
    Route::get('getNotificationsIsRead', [ChatController::class, 'getNotificationsIsRead']);

    Route::post('messages/mark_allAsread', [ChatController::class, 'markAllAsRead']);
    Route::get('messages_receive/{receiverId}', [ChatController::class, 'messages_receive']);
    Route::get('getMessagesAll', [ChatController::class, 'getMessagesAll']);

    //Post 
    Route::resource('post',PostController::class)->names('post');
    Route::post('deleteindidualpost/{id}', [PostController::class, 'deleteIndividualPost']);
    Route::post('reactToPostById', [FollowController::class, 'reactToPostById']);
    Route::get('getReactionByPostId/{id}', [FollowController::class, 'getReactionByPostId']);

    //Comment
    Route::resource('comment',CommentController::class)->names('comment');
    Route::post('commentreply', [CommentController::class, 'commentreply']);

    //Reactions  
    Route::resource('reaction',App\Http\Controllers\Postreaction\PostreactionController::class)->names('reaction');
    Route::get('getReactionPost/{post_id}', [ReactionController::class, 'getReactionPost']);

    //Follow  App\Http\Controllers\Follow
    Route::resource('follow',FollowController::class)->names('follow');
    Route::get('getPost', [FollowController::class, 'getPost']);
    Route::get('posts/followed', [FollowController::class, 'getFollowedPosts']);
    
    // Route::post('/post-attachment/{id}', [PostController::class, 'deleteIndividualPost']);
    //List clients base on rrofile
    Route::get('getListClients', [ClientsBAL::class, 'getListClients']);
    Route::get('getFollowStatus/{code}', [ClientsBAL::class, 'getFollowStatus']);
    Route::get('getPendingFollowStatus/{code}', [ClientsBAL::class, 'getPendingFollowStatus']);
    //List clients base on PENDING
    Route::get('getPendingFollowRequests', [ClientsBAL::class, 'getPendingFollowRequests']);
    Route::get('getfollowingPending', [ClientsBAL::class, 'getfollowingPending']);
    Route::put('acceptFollowRequest/{followerCode}', [ClientsBAL::class, 'acceptFollowRequest']);
    //unfollow
    Route::delete('unfollow/{id}', [ClientsBAL::class, 'unfollow']);
    // Suggested users based on profession or industry of followed people
    Route::get('getPeopleyoumayknow', [ClientsBAL::class, 'getPeopleyoumayknow']);
    Route::get('getPeopleRecentActivity', [ClientsBAL::class, 'getPeopleRecentActivity']);
    Route::delete('deleteSearchHistory', [SearchHistoryBAL::class, 'deleteSearchHistory']);
    Route::post('saveSearchHistory', [SearchHistoryBAL::class, 'saveSearchHistory']);
    Route::get('getSearchHistory', [SearchHistoryBAL::class, 'getSearchHistory']);
    //enhance cv
    Route::post('saveProfile', [ProfileController::class, 'saveProfile']);
    Route::get('getProfileData', [ProfileController::class, 'getProfileData']);
    Route::get('getProfileByCode', [ProfileController::class, 'getProfileByCode']);
    //LANGAUGE
    Route::post('saveLanguage', [UserLanguage::class, 'saveLanguage']);
    Route::get('getLanguagesByCode', [UserLanguage::class, 'getLanguagesByCode']);
    Route::delete('deleteLanguageById/{id}', [UserLanguage::class, 'deleteLanguageById']);
    //education
    Route::post('saveEducation', [UserEducations::class, 'saveEducation']);
    Route::put('updateEducationById/{id}', [UserEducations::class, 'updateEducationById']);
    Route::get('getEducationsByCode', [UserEducations::class, 'getEducationsByCode']);
    Route::get('getEducationById/{id}', [UserEducations::class, 'getEducationById']);
    Route::delete('deleteEducation/{id}', [UserEducations::class, 'deleteEducation']);
    //Skills
    Route::post('saveSkills', [UserSkills::class, 'saveSkills']);
    Route::get('getSkills', [UserSkills::class, 'getSkills']);
    Route::put('skills/{id}', [UserSkills::class, 'updateSkill']);
    Route::delete('deleteSkills/{id}', [UserSkills::class, 'delete']);
    //seminar
    Route::post('saveSeminar', [UserSeminars::class, 'saveSeminar']);
    Route::put('updateSeminar/{id}', [UserSeminars::class, 'updateSeminar']);
    Route::get('getSeminarByCode', [UserSeminars::class, 'getSeminarByCode']);
    Route::delete('delete/{id}', [UserSeminars::class, 'delete']);
    //trainings
    Route::post('saveTrainings', [UserTrainings::class, 'saveTrainings']);
    Route::get('getTrainings', [UserTrainings::class, 'getTrainings']);
    Route::put('updateTrainings/{id}', [UserTrainings::class, 'updateTrainings']);
    Route::delete('deleteTraining/{id}', [UserTrainings::class, 'deleteTraining']);
    //certificate
    Route::post('saveCertificates', [UserCertificates::class, 'saveCertificates']);
    Route::get('getCertificates', [UserCertificates::class, 'getCertificates']);
    Route::delete('deleteCertificate/{id}', [UserCertificates::class, 'deleteCertificate']);
    Route::put('updateCertificates/{id}', [UserCertificates::class, 'updateCertificates']);
    //work experience
    Route::post('saveEmployment', [UserWorkExperiences::class, 'saveEmployment']);
    Route::get('getEmployment', [UserWorkExperiences::class, 'getEmployment']);
    Route::delete('deleteEmployment/{id}', [UserWorkExperiences::class, 'deleteEmployment']);
    Route::put('updateWorkExperience/{id}', [UserWorkExperiences::class, 'updateWorkExperience']);
    //GET CV DATA ALL
    Route::get('getProfileCV', [ProfileController::class, 'getProfileCV']);
    //JobPosting
    Route::post('saveJobPosting', [JobPostingController::class, 'saveJobPosting']);
    Route::post('job-posting', [JobPostingController::class, 'saveOrUpdateJobPosting']);
    Route::put('job-posting/{transNo}', [JobPostingController::class, 'saveOrUpdateJobPosting']);

    Route::put('updateJobPosting/{transNo}', [JobPostingController::class, 'updateJobPosting']); 
    Route::get('getJobPostingsByCode', [JobPostingController::class, 'getJobPostingsByCode']);
    Route::get('getJobPostingByTransNo/{transNo}', [JobPostingController::class, 'getJobPostingByTransNo']);
    Route::delete('deleteJobPosting/{transNo}', [JobPostingController::class, 'deleteJobPosting']);
    //JobList
    Route::get('getActiveJobs', [JobListController::class, 'getActiveJobs']);
    Route::get('getActiveJobsByCode/{code}', [JobListController::class, 'getActiveJobsByCode']);
    Route::get('getAppliedStatus/{transNo}', [AppliedJobController::class, 'getAppliedStatus']);
    //company profile
    Route::get('company/profile/{code}', [ProfileController::class, 'userAuthByCode']);
    Route::get('country_codes', function () {
    $phones = file_get_contents("http://country.io/phone.json");
    $names = file_get_contents("http://country.io/names.json");

    return response()->json([
    'phones' => json_decode($phones, true),
    'names'  => json_decode($names, true),
    ]);
    });
    Route::post('validate_phone', [PhoneValidationController::class, 'validate_phone']);
    //AppliedQuestions
    Route::post('addQuestions', [QuestionController::class, 'addQuestions']);
    Route::get('getQuestions/{jobId}', [QuestionController::class, 'getQuestionById']);
    Route::delete('deleteQuestionById/{question_id}', [QuestionController::class, 'deleteQuestionById']);
    //AppliedJobs
    Route::post('saveAppliedJob', [AppliedJobController::class, 'saveAppliedJob']);
    Route::get('getAppliedJob', [AppliedJobController::class, 'getAppliedJob']);
    Route::get('getAppliedJobByTransNo/{transNo}', [AppliedJobController::class, 'getAppliedJobByTransNo']);
    Route::get('getInterviewAppliedJobs', [AppliedJobController::class, 'getInterviewAppliedJobs']);
    Route::get('getAllAppliedJobsByCode', [AppliedJobController::class, 'getAllAppliedJobsByCode']);
    Route::get('getAppliedJobByUsers/{transNo}', [AppliedJobController::class, 'getAppliedJobByUsers']);
    
    Route::post('updateAppliedStatus/{applied_id}', [AppliedJobController::class, 'updateAppliedStatus']);
    //Post Reactions
    // Route::post('saveReaction', [ReactionController::class, 'saveReaction']);
    Route::get('react/{postId}', [ReactionController::class, 'getReactions']);
    Route::post('saveReaction', [ReactionController::class, 'saveReaction']);


    //CLIENT DASHBOARD
    Route::get('getJobVacanciesCountByCode', [JobListController::class, 'getJobVacanciesCountByCode']);
    Route::get('getAppliedJobCount', [JobListController::class, 'getAppliedJobCount']);
    Route::get('getPendingReviews', [JobListController::class, 'getPendingReviews']);
    Route::get('getHired', [JobListController::class, 'getHired']);


    //storage
    Route::post('uploadphoto', [PhotoUploadController::class, 'upload']);

    Route::post('test', function (Request $request) {
        $file = $request->file('photo');
        $fileName = $file->getClientOriginalName();
        $path = Storage::disk('b2')->putFile('photos', $file, 'public');
        return ['url' => Storage::disk('b2')->url($path)];
    });

    //backbaze photo routes
    Route::get('photos/list', [PhotoUploadController::class, 'list']);
    Route::post('photos/delete', [PhotoUploadController::class, 'delete']);

    //EVENTS
    Route::post('events/save', [EventController::class, 'save']);
    Route::delete('events/delete/{id}', [EventController::class, 'delete']);
    Route::put('events/update/{id}', [EventController::class, 'update']);
    Route::get('events/getevents', [EventController::class, 'getEvents']);
    Route::get('events/getEventByUuid/{uuid}', [EventController::class, 'getEventByUuid']);

    //PROFILE   
    Route::post('updateProfile', [ProfilepictureController::class, 'updateProfile']);

});

  