<?php

use App\Http\Controllers\TeamController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Tournament\TournamentController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Category\EventFormController;
use App\Http\Controllers\EHub\LicenseController;
use App\Http\Controllers\League\LeagueController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\ArticleController;
use App\Http\Controllers\Organization\OrganizationEventController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Tournament\PointEventController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

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

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::get('/test-socket', function () {
    // Static test payload
    $json = '{
        "event": {
            "initialized": false
        },
        "name": "Stage 15"
    }';

    $data = [
        'data' => json_decode($json),
        'room' => 'teste/event1/stage1',
        'update' => 'manage-event-stage'
    ];

    // Send to Node.js Socket.IO server
    Http::post('http://127.0.0.1:3001/broadcast', $data);

    return response()->json([
        'status' => 'sent',
        'payload' => $data
    ]);
});

Route::controller(UserController::class)->group(function() {
    Route::post('/users', 'create');
});

Route::controller(AuthController::class)->group(function(){
    Route::post('/auth/login', 'login');
});

Route::controller(EmailVerificationController::class)->group(function(){
    Route::post('/auth/email/send-code',   'sendCode');
    Route::post('/auth/email/verify-code', 'verifyCode');
});

Route::controller(LicenseController::class)->group(function(){
    Route::get('/license', 'showAvailableLicenses');
});

Route::controller(TournamentController::class)->group(function(){
    Route::get('/tournament', 'search');
    Route::post('/league/{leagueRoute}/tournament', 'create');
    Route::get('/league/{leagueRoute}/tournament/periods', 'showPeriods');
    Route::get('/league/{leagueRoute}/tournament/{tournamentRoute}', 'showDetails');
});

Route::controller(LeagueController::class)->group(function(){
    Route::get('/league/{leagueRoute}', 'show');
});

Route::controller(CategoryController::class)->group(function(){
    Route::get('/category', 'showCategories');
    Route::get('/category/{categoryRoute}', 'showSubCategories');
});

Route::controller(EventFormController::class)->group(function(){
    Route::get('/category/{categoryRoute}/event-form/{runmodeKey}', 'getForm');
});

Route::controller(OrganizationController::class)->group(function(){
    Route::get('/organization/{orgRoute}', 'show');
});

Route::controller(ArticleController::class)->group(function(){
    Route::get('/organization/{orgRoute}/articles', 'index');
    Route::get('/organization/{orgRoute}/article/{articleSlug}', 'show');
});

Route::controller(OrganizationEventController::class)->group(function(){
    Route::get('/organization/{orgRoute}/events', 'index');
    Route::get('/organization/{orgRoute}/event/{eventRoute}', 'show');
});

Route::middleware('auth:sanctum')->group(function() {
    // Route::resource('users', UserController::class);
    // Route::resource('teams', TeamController::class);
    // Route::resource('auth', AuthController::class);

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::controller(LicenseController::class)->group(function(){
        Route::post('/license/adquire', 'adquireLicense')->name('paypal');
        Route::post('/license/canceled', 'canceledLicensePayment')->name('paypal.payment.canceled');
        Route::post('/license/success', 'adquiredLicense')->name('paypal.payment.successful');
    });

    Route::controller(UserController::class)->group(function(){
        Route::get('/notification', 'getNotifications');
        Route::patch('/notification/{id}', 'setNotificationRead');
        Route::delete('/notification/{id}', 'deleteNotification');

        Route::get('/user/token', 'getToken');

        Route::get('/user/profile', 'getProfile');
        Route::patch('/user/profile', 'updateProfile');
        Route::patch('/user/password', 'changePassword');
        Route::delete('/user', 'deleteAccount');
    });

    Route::controller(LeagueController::class)->group(function(){
        Route::post('/league', 'create');
        Route::patch('/league/{leagueRoute}/profile', 'updateProfile');
        Route::delete('/league/{leagueRoute}', 'delete');
        Route::get('/league', 'show');
    });

    Route::controller(PaymentController::class)->group(function(){
        Route::get('/payment-status', 'showAvailableStatus');
        Route::get('/currency', 'showAvailableCurrencies');
        Route::patch('/league/{leagueRoute}/tournament/{tournamentRoute}/participant/payment', 'updateProfile');
    });

    Route::controller(TournamentController::class)->group(function(){
        Route::post('/league/{leagueRoute}/tournament', 'create');
        Route::get('/league/{leagueRoute}/tournament', 'showOnLeagueDashboard');
        Route::patch('/league/{leagueRoute}/tournament/{tournamentRoute}', 'updateProfile');
        Route::post('/league/{leagueRoute}/tournament/{tournamentRoute}/participant/subscribe', '');
    });

    Route::controller(PointEventController::class)->group(function(){
        Route::post('/league/{leagueRoute}/tournament/{tournamentRoute}/event', 'create');
        Route::get('/league/{leagueRoute}/tournament/{tournamentRoute}/event/{eventRoute}', 'show');
        Route::post('/league/{leagueRoute}/tournament/{tournamentRoute}/event/{eventRoute}/round', 'createRound');
    });

    Route::controller(TeamController::class)->group(function(){
        Route::get('/my-team', 'showMyTeams');
        Route::post('/my-team/create', 'create');
        Route::get('/my-team/{id}', 'showMyTeams');
        Route::patch('/my-team/{id}', 'update');
    });

    Route::controller(OrganizationController::class)->group(function(){
        Route::get('/organizations/mine', 'getMine');
        Route::post('/organization', 'create');
        Route::patch('/organization/{orgRoute}/profile', 'updateProfile');
        Route::delete('/organization/{orgRoute}', 'delete');
        Route::get('/organization/{orgRoute}/members', 'getMembers');
        Route::post('/organization/{orgRoute}/member', 'addMember');
        Route::patch('/organization/{orgRoute}/member/{userId}', 'updateMemberRole');
        Route::delete('/organization/{orgRoute}/member/{userId}', 'removeMember');
        Route::post('/organization/{orgRoute}/transfer-ownership', 'transferOwnership');
        Route::get('/organization/{orgRoute}/invites', 'getInvites');
        Route::post('/organization/{orgRoute}/invite/{inviteId}/resend', 'resendInvite');
        Route::delete('/organization/{orgRoute}/invite/{inviteId}', 'removeInvite');
        Route::post('/invite/accept/{token}', 'acceptInvite');
    });

    Route::controller(ArticleController::class)->group(function(){
        Route::post('/organization/{orgRoute}/article', 'store');
        Route::patch('/organization/{orgRoute}/article/{articleId}', 'update');
        Route::delete('/organization/{orgRoute}/article/{articleId}', 'destroy');
        Route::post('/organization/{orgRoute}/article/image', 'uploadImage');
    });

    Route::controller(OrganizationEventController::class)->group(function(){
        Route::post('/organization/{orgRoute}/event', 'store');
        Route::patch('/organization/{orgRoute}/event/{eventRoute}', 'update');
        Route::delete('/organization/{orgRoute}/event/{eventRoute}', 'destroy');
    });
});

Route::fallback(function (){
    abort(404, 'API resource not found');
});
