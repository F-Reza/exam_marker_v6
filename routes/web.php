<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    PasswordController,
    DashboardController,
    AssessmentController,
    BulkAssessmentController,
    ResultController,
    StudentController,
    ParentCommunicationController,
    AdminPlanController,
    FileController,
    AnalyticsController,
    PublicReportController,
    ProcessingController,
    ReportController,
    SettingsController,
    BillingController,
    HelpController,
    AdminAIProcessingController,
    CommunicationController,
    TeamController,
    NotificationController,
    PortalController,
    AdminUserController,
    PaymentController,
    AdminPaymentGatewayController,
    AdminAISettingController
};

use App\Http\Controllers\Admin\{
    TransactionController,
    SupportController,
    SystemConfigurationController,
    AdminAuditController
};



Route::get('/cc', function () {
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('optimize:clear');
    return redirect()->back()->with('success', 'Cache cleared successfully');
})->name('clear-all');


/*
|--------------------------------------------------------------------------
| PUBLIC
|--------------------------------------------------------------------------
*/

Route::view('/', 'home')->name('home');
Route::view('/pricing', 'pricing')->name('pricing');
Route::view('/how-it-works', 'how-it-works')->name('how');

Route::get('/parent-report/{token}', [PublicReportController::class, 'show'])->name('parent.report.public');
Route::get('/parent-report/{token}/pdf', [PublicReportController::class, 'pdf'])->name('parent.report.pdf');

Route::match(['GET', 'POST'], '/payments/callback/{provider}/{tx}', [PaymentController::class, 'callback'])->name('payments.callback');

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function() {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [PasswordController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| EMAIL VERIFICATION
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function() {
    Route::get('/email/verify', function() {
        return view('auth.verify-email');
    })->name('verification.notice');
    
    Route::get('/email/verify/{id}/{hash}', function(\Illuminate\Foundation\Auth\EmailVerificationRequest $r) {
        $r->fulfill();
        return redirect()->route('dashboard')->with('success', 'Email verified.');
    })->middleware('signed')->name('verification.verify');
    
    Route::post('/email/verification-notification', function(\Illuminate\Http\Request $r) {
        $r->user()->sendEmailVerificationNotification();
        return back()->with('success', 'Verification link sent.');
    })->middleware('throttle:6,1')->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED & VERIFIED USERS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/student-portal', [PortalController::class, 'student'])->middleware('role:student')->name('portal.student');
    Route::get('/teacher-workspace', [PortalController::class, 'teacher'])->middleware('role:teacher')->name('portal.teacher');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('feature:graphs')->name('analytics');

    /*
    |--------------------------------------------------------------------------
    | Papers
    |--------------------------------------------------------------------------
    */

    Route::resource('assessments', AssessmentController::class)->except(['edit', 'update']);
    
    Route::get('/bulk-check', [BulkAssessmentController::class, 'create'])
        ->middleware('feature:bulk_answer_upload')
        ->name('assessments.bulk.create');
    
    Route::post('/bulk-check', [BulkAssessmentController::class, 'store'])
        ->middleware('feature:bulk_answer_upload')
        ->name('assessments.bulk.store');

    Route::post('/assessments/{assessment}/start', [AssessmentController::class, 'start'])->name('assessments.start');
    Route::get('/assessments/{assessment}/processing', [ProcessingController::class, 'show'])->name('assessments.processing');
    Route::get('/assessments/{assessment}/status', [ProcessingController::class, 'status'])->name('assessments.status');

    /*
    |--------------------------------------------------------------------------
    | Results
    |--------------------------------------------------------------------------
    */

    Route::get('/assessments/{assessment}/results', [ResultController::class, 'show'])->name('results.show');
    Route::get('/results/{result}', [ResultController::class, 'showResult'])->name('results.view');
    
    Route::patch('/results/{result}', [ResultController::class, 'update'])
        ->middleware('feature:manual_mark_editing')
        ->name('results.update');

    Route::post('/results/{result}/recheck', [ResultController::class, 'recheckQuestion'])
        ->middleware('feature:question_recheck')
        ->name('results.recheck-question');

    Route::get('/assessments/{assessment}/finalise', function($assessment) {
        return redirect()->route('results.show', $assessment)
            ->with('error', 'Please use the Finalise button on the results page.');
    })->name('results.finalise.redirect');

    Route::post('/assessments/{assessment}/finalise', [ResultController::class, 'finalise'])
        ->middleware('feature:manual_mark_editing')
        ->name('results.finalise');

    Route::post('/assessments/{assessment}/unfinalise', [ResultController::class, 'unfinalise'])
        ->name('results.unfinalise');

    Route::get('/assessments/{assessment}/report', [ResultController::class, 'report'])
        ->middleware('feature:result_report_download')
        ->name('results.report');

    Route::get('/assessments/{assessment}/report/download', [ResultController::class, 'downloadReport'])
        ->middleware('feature:result_report_download')
        ->name('results.report.download');

    Route::get('/assessments/{assessment}/corrected-paper', [ResultController::class, 'correctedPaper'])
        ->middleware('feature:corrected_paper_download')
        ->name('results.corrected');

    /*
    |--------------------------------------------------------------------------
    | FILES
    |--------------------------------------------------------------------------
    */

    Route::get('/files/{assessment}/{type}', [FileController::class, 'show'])
        ->whereIn('type', ['qp', 'ms', 'wa'])
        ->name('files.show');

    // 🔥 ROUTE FOR ATTACHMENTS (INSERTS) - FIXES THE ERROR
    Route::get('/files/{assessment}/attachment/{attachment}', [FileController::class, 'attachment'])
        ->name('files.attachment');

    /*
    |--------------------------------------------------------------------------
    | STUDENTS
    |--------------------------------------------------------------------------
    */

    Route::resource('students', StudentController::class)
        ->except(['show'])
        ->middleware('feature:student_records');

    Route::post('/students/import', [StudentController::class, 'import'])
        ->middleware('feature:bulk_student_import')
        ->name('students.import');

    Route::get('/students-import-template', [StudentController::class, 'template'])
        ->middleware('feature:bulk_student_import')
        ->name('students.template');

    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    /*
    |--------------------------------------------------------------------------
    | COMMUNICATION
    |--------------------------------------------------------------------------
    */

    Route::get('/communications', [CommunicationController::class, 'index'])
        ->middleware('feature:parent_email')
        ->name('communications.index');

    Route::post('/assessments/{assessment}/parent-report', [ParentCommunicationController::class, 'send'])
        ->middleware('feature:parent_email')
        ->name('parent-report.send');

    Route::post('/assessments/{assessment}/parent-mobile-link', [ParentCommunicationController::class, 'sendMobileLink'])
        ->middleware('feature:parent_sms')
        ->name('parent-report.mobile');

    /*
    |--------------------------------------------------------------------------
    | TEAM
    |--------------------------------------------------------------------------
    */

    Route::get('/team', [TeamController::class, 'index'])
        ->middleware(['feature:multiple_teachers', 'role:coaching'])
        ->name('team.index');

    Route::post('/team', [TeamController::class, 'store'])
        ->middleware(['feature:multiple_teachers', 'role:coaching'])
        ->name('team.store');

    Route::put('/team/{teamMember}', [TeamController::class, 'update'])
        ->middleware(['feature:multiple_teachers', 'role:coaching'])
        ->name('team.update');

    Route::delete('/team/{teamMember}', [TeamController::class, 'destroy'])
        ->middleware(['feature:multiple_teachers', 'role:coaching'])
        ->name('team.destroy');

    /*
    |--------------------------------------------------------------------------
    | SETTINGS
    |--------------------------------------------------------------------------
    */

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');

    /*
    |--------------------------------------------------------------------------
    | BILLING
    |--------------------------------------------------------------------------
    */

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/change', [BillingController::class, 'change'])->name('billing.change');

    Route::get('/checkout/{plan}', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::post('/checkout/{plan}/pay', [PaymentController::class, 'pay'])->name('payments.pay');

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [NotificationController::class, 'read'])->name('notifications.read');

    /*
    |--------------------------------------------------------------------------
    | Help & Support
    |--------------------------------------------------------------------------
    */

    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    Route::post('/help', [HelpController::class, 'store'])->name('help.store');

    /*
    |--------------------------------------------------------------------------
    | PLATFORM ADMIN
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function() {

        /*
        | AI Processing
        */
        Route::get('/ai-processing', [AdminAIProcessingController::class,'index'])->name('ai-processing.index');

        /*
        | AI Provider Settings
        */

        Route::get('/ai-settings',[AdminAISettingController::class,'index'])->name('ai-settings.index');
        Route::put('/ai-settings',[AdminAISettingController::class,'update'])->name('ai-settings.update');



        /*
        | Plans
        */
        Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
        Route::put('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
        Route::post('/assign-plan', [AdminPlanController::class, 'assign'])->name('plans.assign');

        /*
        | Users
        */
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::get('/users/search', [AdminUserController::class, 'search'])->name('users.search');

        /*
        | Payment
        */
        Route::get('/payment-gateways', [AdminPaymentGatewayController::class, 'index'])->name('payment-gateways.index');
        Route::put('/payment-gateways/{gateway}', [AdminPaymentGatewayController::class, 'update'])->name('payment-gateways.update');

        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        */

        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/search',[TransactionController::class,'search'])->name('transactions.search');
        Route::get('/transactions/{transaction}',[TransactionController::class,'show'])->name('transactions.show');
        Route::delete('/transactions/{transaction}',[TransactionController::class,'destroy'])->name('transactions.destroy');
   



        /*
        | Support
        */
        Route::get('/support', [SupportController::class, 'index'])->name('support.index');
        Route::put('/support/{ticket}', [SupportController::class, 'reply'])->name('support.reply');

        /*
        |--------------------------------------------------------------------------
        | System Configuration
        |--------------------------------------------------------------------------
        */
        Route::get('/config', [SystemConfigurationController::class, 'index'])->name('config.index');
        Route::post('/config', [SystemConfigurationController::class, 'update'])->name('config.update');
        Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit.index');
        Route::delete('/audit/delete', [AdminAuditController::class, 'destroy'])->name('audit.delete');


    });
});