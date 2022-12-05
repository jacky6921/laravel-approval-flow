<?php

namespace Onepage\Approval\Services;

use App\Enums\AdministratorPermission;
use Astrotomic\Translatable\Contracts\Translatable;
use Closure;
use Exception;
use Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Onepage\Approval\Enums\ApprovalStatus;
use Onepage\Approval\Models\ApprovalFlow;
use ApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class ApprovalStatusService
{
    public function getAuthAdministrator()
    {
        if (!$administrator = auth('administrators')->user()) {
            throw new Exception('administrator unauthorized');
        }

        return $administrator;
    }

    public function getRouteResourceName($modelType)
    {
        return lcfirst(Str::pluralStudly(class_basename($modelType)));
    }

    public function getModelMediaCollectionNames($model)
    {
        return data_get($model, 'mediaCollectionNames', []);
    }

    /**
     * Show current model available action
     */
    public function getAvailableAction(Model $model)
    {
        return [
            'submitApprove' => $this->approveRoute($model, 'submitApprove'),
            'approve' => $this->approveRoute($model, 'approve'),
            'publish' => $this->approveRoute($model, 'publish'),
            'reject' => $this->approveRoute($model, 'reject'),
            'clone' => $this->approveRoute($model, 'clone'),
            'edit' => $this->approveRoute($model, 'edit'),
            'show' => $this->approveRoute($model, 'show'),
        ];
    }

    /**
     * return route for approve action
     */
    private function approveRoute($model, $action)
    {
        if ($model->id === null) {
            return '';
        }

        $paramName = $this->getRouteResourceName($model->model_type ?? $model);

        switch ($action) {
            case 'submitApprove':
                return $this->canSubmitApprove($model) && Route::has("wcms.approvalFlows.submit-approve")
                    ? route("wcms.approvalFlows.submit-approve", $model->id)
                    : false;
            case 'approve':
                return $this->canApprove($model) && Route::has("wcms.approvalFlows.approve")
                    ? route("wcms.approvalFlows.approve", $model->id)
                    : false;
            case 'publish':
                return $this->canPublish($model) && Route::has("wcms.approvalFlows.publish")
                    ? route("wcms.approvalFlows.publish", $model->id)
                    : false;
            case 'reject':
                return $this->canReject($model) && Route::has("wcms.approvalFlows.reject")
                    ? route("wcms.approvalFlows.reject", $model->id)
                    : false;
            case 'clone':
                return $this->canNewDraft($model) && Route::has("wcms.approvalFlows.clone")
                    ? route("wcms.approvalFlows.clone", $model->id)
                    : false;
            case 'edit':
                return $this->isDraft($model) && Route::has("wcms.{$paramName}.edit")
                    ? route("wcms.{$paramName}.edit", $model->id)
                    : false;
                // case 'disable':
                //     return $this->canDisable($model) && Route::has("wcms.{$paramName}.disable")
                //         ? route("wcms.{$paramName}.disable", $model->id)
                //         : false;
                // case 'enable':
                //     return $this->canEnable($model) && Route::has("wcms.{$paramName}.enable")
                //         ? route("wcms.{$paramName}.enable", $model->id)
                //         : false;
            case 'show':
                $administrator = $this->getAuthAdministrator();

                return Gate::forUser($administrator)->allows('view', $model)
                    ? route("wcms.{$paramName}.show", $model->id)
                    : false;
            default:
                return false;
        }
    }

    /**
     * Approve current model
     */
    public function approve(Model $model, Closure $closure = null)
    {
        if (false === $this->canApprove($model)) {
            throw new Exception("You cannot approve this resource");
        }

        $administrator = $this->getAuthAdministrator();

        $query = $model->newQuery();

        if ($closure) {
            $query = $model->newQuery();
            $currentPendingPublish =  $query->where(
                fn ($query) => $query->where('id', $model->parent_id)
                    ->orWhere(fn ($query) => $query->where('parent_id', $model->parent_id)->where('parent_id', '!=', null))
            )->where(
                fn ($query) => $query->where('version_status', ApprovalStatus::PENDING_PUBLISH)
            )->first();

            $model = $closure($model, $currentPendingPublish);
            // $model->save();
        }

        $model->version_status = ApprovalStatus::PENDING_PUBLISH;
        $model->approved_by = $administrator->id;
        $model->save();

        return $model;
    }


    /**
     * Publish current model
     */
    public function publish(Model $model, Closure $closure = null)
    {
        if (false === $this->canPublish($model)) {
            throw new Exception("You cannot publish this resource");
        }

        $administrator = $this->getAuthAdministrator();

        DB::beginTransaction();
        try {
            if ($closure) {
                $query = $model->newQuery();
                $currentPulished =  $query->where(
                    fn ($query) => $query->where('id', $model->parent_id)
                        ->orWhere(fn ($query) => $query->where('parent_id', $model->parent_id)->where('parent_id', '!=', null))
                )->where(
                    fn ($query) => $query->where('version_status', ApprovalStatus::PUBLISHED)
                        ->orWhere('version_status', ApprovalStatus::DISABLED)
                )->first();

                $model = $closure($model, $currentPulished);
                // $model->save();
            }

            if ($model->parent_id) {
                $query = $model->newQuery();

                $query->where(
                    fn ($query) => $query->where('id', $model->parent_id)
                        ->orWhere('parent_id', $model->parent_id)
                )->where(
                    fn ($query) => $query->where('version_status', ApprovalStatus::PUBLISHED)
                        ->orWhere('version_status', ApprovalStatus::DISABLED)
                )->update(['version_status' => ApprovalStatus::ARCHIVE]);
            }
            $model->version_status = ApprovalStatus::PUBLISHED;
            $model->published_by = $administrator->id;
            $model->save();

            $entityModel = new $model->model_type;

            $modelRepository = app($entityModel->repository);

            $publishedModel = $model->model_id ? $modelRepository->find($model->model_id) : null;

            $values = $model->values;

            if ($publishedModel) {
                $publishedModel = $modelRepository->update($values, $publishedModel);
            } else {
                $publishedModel = $modelRepository->create($values);
                $model->model_id = $publishedModel->id;
                $model->save();

                if ($model->parent_id) {
                    $model->newQuery()->where(
                        fn ($query) => $query->where('id', $model->parent_id)
                            ->orWhere('parent_id', $model->parent_id)
                    )->update(['model_id' => $publishedModel->id]);
                }
            }

            if ($publishedModel instanceof HasMedia) {
                $model->media->each(function ($mediaItem) use ($model, $publishedModel) {
                    try {
                        $mediaItem->copy($publishedModel, $mediaItem->collection_name);
                    } catch (\Exception $e) {
                        logger("Publish Copy Media Fail", ['model_id' => $model->id, 'collection_name' => $mediaItem->collection_name, 'e' => $e]);
                        $modelValues = $model->values;

                        $modelValues = data_set($modelValues, $mediaItem->collection_name, []);

                        $model->values = $modelValues;

                        $model->save();
                    }
                });
            }

            if (method_exists($publishedModel, 'afterPublishedEvent')) {
                $publishedModel->afterPublishedEvent($values);
            }

            DB::commit();
        } catch (\Throwable $th) {
            logger('publish', [$th]);
            DB::rollBack();
            return false;
        }


        return $publishedModel;
    }

    /**
     * Reject current model
     */
    public function reject(Model $model)
    {
        if (false === $this->canReject($model)) {
            throw new Exception("You cannot reject this resource");
        }

        $model->version_status = ApprovalStatus::DRAFT;
        $model->save();

        return $model;
    }

    /**
     * Submit current model to approval
     */
    public function submitToApproval(Model $model)
    {
        if (false === $this->canSubmitApprove($model)) {
            throw new Exception("You cannot submit this approval to approval");
        }

        $administrator = $this->getAuthAdministrator();

        $model->version_status = ApprovalStatus::PENDING_APPROVAL;
        $model->submitted_by = $administrator->id;
        $model->save();

        return $model;
    }

    /**
     * Clone current model
     */
    public function clone(Model $model, Closure $closure = null)
    {
        $modelClassName = $model->model_type;

        if (false === $this->canNewDraft($model)) {
            throw new Exception("You can't clone this resource.");
        }

        $administrator = $this->getAuthAdministrator();

        $query = $model->newQuery();
        $version = $query->where('id', $model->id)
            ->when(
                $model->parent_id,
                fn ($query) => $query->orWhere('parent_id', $model->parent_id),
                fn ($query) => $query->orWhere('parent_id', $model->id)
            )->max('version');

        $newModel = $model->replicate(['version', 'version_status', 'published_at']);
        $newModel->version = $version + 1;
        $newModel->version_status = ApprovalStatus::DRAFT;
        $newModel->parent_id = $model->parent_id ?: $model->id;
        $newModel->created_by = $administrator->id;
        $newModel->save();

        // Copy Translation
        if ($model instanceof Translatable) {
            $translations = $model->getTranslationsArray();
            $newModel->fill($translations);
            $newModel->save();
        }

        if ($model instanceof HasMedia) {
            $model->media->each(function ($mediaItem) use ($model, $newModel, $modelClassName) {

                try {
                    $mediaItem->copy($newModel, $mediaItem->collection_name);
                } catch (\Exception $e) {
                    logger("clone model media copy fail", ['model_id' => $model->id, 'new_model_id' => $newModel->id, 'collection_name' => $mediaItem->collection_name, 'e' => $e]);
                    $collectionName = $mediaItem->collection_name;
                    if ($modelClassName == 'App\Models\PageComponent') {
                        $collectionName = "values.$collectionName";
                    }
                    $values = $newModel->values;

                    $values = data_set($values, $collectionName, []);

                    $newModel->values = $values;

                    $newModel->save();
                }
            });

            $newModel->refresh();

            // if ($modelClassName == 'App\Models\PageComponent') {
            $newModelValues = $newModel->values;

            foreach ($newModel->media as $media) {

                $collectionName = data_get($media, 'collection_name');
                $uuid = data_get($media, 'uuid');

                if ($modelClassName == 'App\Models\PageComponent') {
                    $collectionName = "values.$collectionName";
                }

                $newModelValues = data_set($newModelValues, $collectionName, [
                    $uuid => $media->toArray()
                ]);
            }

            $newModel->values = $newModelValues;

            $newModel->save();
            // }
        }

        if ($closure) {
            $newModel = $closure($newModel);
            $newModel->save();
        }

        return $newModel;
    }



    /**
     * Check if current model can be approve
     */
    public function canApprove(Model $model)
    {
        $administrator = $this->getAuthAdministrator();

        return $model->id !== null
            && $model->version_status !== null
            && $administrator->can('approve')
            && $model->version_status->is(ApprovalStatus::PENDING_APPROVAL);
    }

    /**
     * Check if current model can be approve
     */
    public function canPublish(Model $model)
    {
        $administrator = $this->getAuthAdministrator();

        return $model->id !== null
            && $model->version_status !== null
            && $administrator->can('publish')
            && $model->version_status->is(ApprovalStatus::PENDING_PUBLISH);
    }

    /**
     * Check if current model can be approve
     */
    public function canSubmitApprove(Model $model)
    {
        return $model->id !== null
            && $model->version_status !== null
            && Gate::allows('update', $model)
            && $model->version_status->is(ApprovalStatus::DRAFT);
    }

    /**
     * Check if current model can be reject
     */
    public function canReject(Model $model)
    {
        $administrator = $this->getAuthAdministrator();

        return $model->id !== null
            && $model->version_status !== null
            && ($administrator->can('approve') || $administrator->can('publish'))
            && ($model->version_status->is(ApprovalStatus::PENDING_APPROVAL) || $model->version_status->is(ApprovalStatus::PENDING_PUBLISH));
    }

    /**
     * Check if current model can be enable
     */
    public function canEnable(Model $model)
    {
        return $model->id !== null
            && $model->version_status !== null
            && $this->hasFlag([AdministratorPermission::APPROVER])
            && $model->version_status->is(ApprovalStatus::DISABLED);
    }

    /**
     * Check if current model can be disable
     */
    public function canDisable(Model $model)
    {
        return $model->id !== null
            && $model->version_status !== null
            && $this->hasFlag([AdministratorPermission::APPROVER])
            && $model->version_status->is(ApprovalStatus::PUBLISHED);
    }

    /**
     * Check if current model can create new draft
     */
    public function canNewDraft(Model $model)
    {
        if ($this->isDraft($model)) {
            return false;
        }

        if (!($model->version_status->is(ApprovalStatus::PUBLISHED) || $model->version_status->is(ApprovalStatus::ARCHIVE))) {
            return false;
        }

        $query = ApprovalFlow::query();

        $hasOtherDraft = $query->when(
            $model->parent_id,
            fn ($query) => $query->where('parent_id', $model->parent_id)
                ->whereIn('version_status', [ApprovalStatus::DRAFT, ApprovalStatus::PENDING_APPROVAL, ApprovalStatus::PENDING_PUBLISH]),
            fn ($query) => $query->where('parent_id', $model->id)
                ->whereIn('version_status', [ApprovalStatus::DRAFT, ApprovalStatus::PENDING_APPROVAL, ApprovalStatus::PENDING_PUBLISH])
        )->count();

        if ($hasOtherDraft) {
            return false;
        }

        /**
         * Check if the user is authorised to create using according to the Model-Policy
         */
        return Gate::allows('create', $model);
        // return $this->hasFlag([
        //     AdministratorPermission::ADMINISTRATOR,
        //     AdministratorPermission::APPROVER,
        //     AdministratorPermission::EDITOR
        // ]) && $model->version_status->in([
        //     ApprovalStatus::PUBLISHED,
        //     ApprovalStatus::ARCHIVE,
        //     ApprovalStatus::DISABLED,
        // ]);
    }

    /**
     * Check if current model is Draft
     *
     * @param Model $model
     * @return boolean
     */
    public function isDraft(Model $model)
    {
        return $model->id !== null
            && $model->version_status !== null
            && $model->version_status->is(ApprovalStatus::DRAFT);
    }
}
