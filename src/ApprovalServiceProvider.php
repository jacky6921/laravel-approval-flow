<?php

namespace Onepage\Approval;

use Illuminate\Support\ServiceProvider;
use Onepage\Approval\Contracts\ApprovalFlowRepository;
use Onepage\Approval\Repositories\ApprovalFlowRepositoryEloquent;
use Onepage\Approval\Services\ApprovalStatusService;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ApprovalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('approval-status-service', function ($app) {
            return new ApprovalStatusService;
        });
        $this->app->bind(ApprovalFlowRepository::class, ApprovalFlowRepositoryEloquent::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        /*
        * migrations
        */
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Route::macro('approval', function (string $baseUrl = 'approval') {
            return Route::group(['prefix' => $baseUrl, 'namespace' => "Onepage\Approval\Http\Controllers"], function () {
                Route::post("approvalFlows/clone/{approvalFlow}", "ApprovalFlowController@clone")->name("approvalFlows.clone");
                Route::post("approvalFlows/approve/{approvalFlow}", "ApprovalFlowController@approve")->name("approvalFlows.approve");
                Route::post("approvalFlows/publish/{approvalFlow}", "ApprovalFlowController@publish")->name("approvalFlows.publish");
                Route::post("approvalFlows/submit-approve/{approvalFlow}", "ApprovalFlowController@submitToApproval")->name("approvalFlows.submit-approve");
                Route::post("approvalFlows/reject/{approvalFlow}", "ApprovalFlowController@reject")->name("approvalFlows.reject");
            });
        });
        /*
        * routes
        */
        // $this->loadRoutesFrom(__DIR__ . '/../routes/approval.php');
    }
}
