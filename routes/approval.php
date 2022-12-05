<?php

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::approval('approvalFlows', 'ApprovalFlowController');
// Route::middleware(['wcms.inertia','auth:administrators'])->namespace('Onepage\Approval\Http\Controllers')->name('approval.')->prefix('wcms')->group(function () {
 
        Route::get('test', 'Onepage\Approval\Http\Controllers\ApprovalFlowController@submitToApproval');
        // Route::post("approvalFlows/clone/{approvalFlow}", "ApprovalFlowController@clone")->name("approvalFlows.clone");
            // Route::approval('approvalFlows', 'ApprovalFlowController');

// });
