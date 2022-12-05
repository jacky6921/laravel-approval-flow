<?php

namespace Onepage\Approval\Http\Controllers;

use Illuminate\Http\Request;
// use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Contracts\AdministratorRepository;
use Onepage\Approval\Contracts\ApprovalFlowRepository;
use Onepage\Approval\Models\ApprovalFlow;
use ApprovalStatusServices;
use Illuminate\Support\Str;

/**
 * Class ExampleController.
 *
 * @package namespace Onepage\Approval\Http\Controllers;
 */
class ApprovalFlowController extends Controller
{
    /**
     * @var ApprovalFlowRepository
     */
    protected $repository;

    /**
     * ExampleController constructor.
     *
     * @param ApprovalFlowRepository $repository
     */
    public function __construct(ApprovalFlowRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Clone the specified resource
     */
    public function clone(ApprovalFlow $approvalFlow)
    {
        $routeResourceName = ApprovalStatusServices::getRouteResourceName($approvalFlow->model_type);

        $route = $routeResourceName ? "wcms.$routeResourceName.show" : "wcms.dashboard";
        $routeParam = $routeResourceName ? $approvalFlow->id : [];

        $approvalFlow = ApprovalStatusServices::clone($approvalFlow);

        return redirect()->route($route, $routeParam)
            ->with('message', 'Action Taken: Clone');
    }

    /**
     * Submit the specified resource to Approval
     */
    public function submitToApproval(ApprovalFlow $approvalFlow)
    {
        $routeResourceName = ApprovalStatusServices::getRouteResourceName($approvalFlow->model_type);

        $route = $routeResourceName ? "wcms.$routeResourceName.show" : "wcms.dashboard";
        $routeParam = $routeResourceName ? $approvalFlow->id : [];

        $approvalFlow = ApprovalStatusServices::submitToApproval($approvalFlow);

        return redirect()->route($route, $routeParam)->with('message', 'Action Taken: Submit to Approval');
    }

    /**
     * Approve the specified resource
     */
    public function approve(ApprovalFlow $approvalFlow)
    {
        $routeResourceName = ApprovalStatusServices::getRouteResourceName($approvalFlow->model_type);

        $route = $routeResourceName ? "wcms.$routeResourceName.show" : "wcms.dashboard";
        $routeParam = $routeResourceName ? $approvalFlow->id : [];

        $approvalFlow = ApprovalStatusServices::approve($approvalFlow);

        return redirect()->route($route, $routeParam)->with('message', 'Action Taken: Approved');
    }

    /**
     * Approve the specified resource
     */
    public function publish(ApprovalFlow $approvalFlow)
    {
        $routeResourceName = ApprovalStatusServices::getRouteResourceName($approvalFlow->model_type);

        $route = $routeResourceName ? "wcms.$routeResourceName.show" : "wcms.dashboard";
        $routeParam = $routeResourceName ? $approvalFlow->id : [];

        $publishedModel = ApprovalStatusServices::publish($approvalFlow);

        $message = "Action Taken: Published";
        
        if (!$publishedModel) {
            $message = "Server Error";
        }

        return redirect()->route($route, $routeParam)->with('message', $message);
    }

    /**
     * Reject the specified resource
     */
    public function reject(ApprovalFlow $approvalFlow)
    {
        $routeResourceName = ApprovalStatusServices::getRouteResourceName($approvalFlow->model_type);

        $route = $routeResourceName ? "wcms.$routeResourceName.show" : "wcms.dashboard";
        $routeParam = $routeResourceName ? $approvalFlow->id : [];

        $approvalFlow = ApprovalStatusServices::reject($approvalFlow);

        return redirect()->route($route, $routeParam)->with('message', 'Action Taken: Rejected');
    }
}
