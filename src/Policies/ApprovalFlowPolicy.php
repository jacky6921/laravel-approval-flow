<?php

namespace Onepage\Approval\Policies;

use Onepage\Approval\Enums\ApprovalStatus;
use App\Models\Administrator;
use Onepage\Approval\Models\ApprovalFlow;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApprovalFlowPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\Administrator  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(Administrator $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\Administrator  $user
     * @param  \App\Models\ApprovalFlow  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(Administrator $user, ApprovalFlow $model)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\Administrator  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(Administrator $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\Administrator  $user
     * @param  \App\Models\ApprovalFlow  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(Administrator $user, ApprovalFlow $model)
    {
        return $model->version_status->is(ApprovalStatus::DRAFT);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\Administrator  $user
     * @param  \App\Models\ApprovalFlow  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(Administrator $user, ApprovalFlow $model)
    {
        return true;
        // return $user->hasAnyRole([
        //     'SuperAdmin',
        //     'Approver',
        //     'Publisher',
        // ]);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\Administrator  $user
     * @param  \App\Models\ApprovalFlow  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(Administrator $user, ApprovalFlow $model)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\Administrator  $user
     * @param  \App\Models\ApprovalFlow  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(Administrator $user, ApprovalFlow $model)
    {
        return false;
    }
}
