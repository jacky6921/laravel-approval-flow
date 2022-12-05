<?php

namespace Onepage\Approval\Services;

use App\Enums\AdministratorPermission;
use App\Models\Administrator;
use Astrotomic\Translatable\Contracts\Translatable;
use Closure;
use Exception;
use Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Onepage\Approval\Contracts\ApprovalFlowRepository;
use Onepage\Approval\Enums\ApprovalStatus;
use Illuminate\Support\Arr;
use Onepage\Approval\Models\ApprovalFlow;
use ApprovalStatusServices;
use Prettus\Repository\Eloquent\BaseRepository;

class ApprovalService
{
    protected $collection;

    /**
     * Approval Service constructor.
     *
     * @param modelRepository
     */
    public function __construct(public BaseRepository $modelRepository)
    {
        $this->approvalFlowRepository = app(ApprovalFlowRepository::class);
        $this->model = $this->modelRepository->makeModel();
    }

    // --------------------------- Base Methods ------------------------

    private function getAuthAdministrator()
    {
        return ApprovalStatusServices::getAuthAdministrator();
    }

    private function getClass()
    {
        return get_class($this->model);
    }

    private function checkModelUseApproval()
    {
        return data_get($this->model, 'hasApproval', false);
    }

    private function checkModelUseMedia()
    {
        return !!$this->model->media;
    }

    private function getModelMediaCollectionNames()
    {
        return ApprovalStatusServices::getModelMediaCollectionNames($this->model);
    }

    /**
     * ForwardCallTo repository functions
     *
     * @param method
     * @param parameters
     */
    public function __call($method, $parameters)
    {
        // dd($this->collection, $method, $parameters);
        $this->collection = $this->forwardCallTo($this->collection, $method, $parameters);
        return $this;
    }

    public function list()
    {
        // $this->collection = $this->approvalFlowRepository->whereField('model_type', $this->getClass());
        // return $this;
        if ($this->checkModelUseApproval()) {
            return $this->approvalFlowRepository->whereField('model_type', $this->getClass());
        }
        return $this->modelRepository;
    }

    public function get()
    {

        if (is_a($this->collection, 'Illuminate\Support\Collection') || is_a($this->collection, 'Illuminate\Pagination\LengthAwarePaginator')) {
            return  $this->collection;
        }
        return   $this->collection->get();
    }

    public function convertToModel($_array)
    {
        return $this->model::hydrate($_array);
    }

    public function syncModelMedia($model, $mediaRequest, $collectionName)
    {
        if (is_array($mediaRequest) === false) {
            $mediaRequest = [];
        }

        $syncMediaFail = false;

        try {
            $model
                ->syncFromMediaLibraryRequest($mediaRequest)
                ->toMediaCollection($collectionName);

            $model->refresh();
        } catch (\Exception $e) {
            logger("syncModelMedia Fail", ['mediaRequest' => $mediaRequest, 'collectionName' => $collectionName, 'model' => $model, 'message' => $e]);
            //if media file is not exist, remove it on component values.
            if ($this->checkModelUseApproval()) {
                $syncMediaFail = true;

                $values = $model->values;

                $values = data_set($values, $collectionName, []);

                $model->values = $values;

                $model->save();
            }
        }

        if ($this->checkModelUseApproval()) {
            if (!$syncMediaFail) {
                $attributes = $model->values;

                $mediaData = $model->getFirstMedia($collectionName);

                if ($mediaData != null) {
                    $mediaData = $mediaData->only(['uuid', 'original_url']);
                    data_set($mediaData, 'preview_url', data_get($mediaData, 'original_url', ''));
                    data_set($attributes, $collectionName, [data_get($mediaData, 'uuid') => $mediaData]);
                } else {
                    data_set($attributes, $collectionName, null);
                }

                $model->update($attributes);
                $model->save();
            }
        }
    }

    // --------------------------- CRUD Methods ------------------------

    public function create($attributes, $modelId = null, $versionStatus = ApprovalStatus::DRAFT)
    {
        return DB::transaction(function () use ($attributes, $modelId, $versionStatus) {
            if ($this->checkModelUseApproval()) {
                $administrator = $this->getAuthAdministrator();

                $data = [
                    'model_id' => $modelId,
                    'model_type' => $this->getClass(),
                    'values' => $attributes,
                    'version' => 1,
                    'version_status' => $versionStatus,
                    'created_by' => $administrator->id,
                ];

                $model = $this->approvalFlowRepository->create($data);
            } else {
                $model = $this->modelRepository->create($attributes);
            }

            if ($this->checkModelUseMedia()) {
                $mediaAttributes = Arr::only($attributes, $this->getModelMediaCollectionNames());

                foreach ($mediaAttributes as $collectionName => $attribute) {
                    $this->syncModelMedia($model, $attribute, $collectionName);
                }
            }

            return $model;
        });
    }

    public function update($attributes, $model)
    {
        return DB::transaction(function () use ($attributes, $model) {
            if ($this->checkModelUseApproval()) {
                $model = $this->approvalFlowRepository->update([
                    'values' => $attributes
                ], $model);
            } else {
                $model = $this->modelRepository->update($attributes, $model);
            }

            if ($this->checkModelUseMedia()) {
                $mediaAttributes = Arr::only($attributes, $this->getModelMediaCollectionNames());

                foreach ($mediaAttributes as $collectionName => $attribute) {
                    $this->syncModelMedia($model, $attribute, $collectionName);
                }
            }

            return $model;
        });
    }

    public function destroy($model)
    {
        DB::beginTransaction();
        try {
            if ($this->checkModelUseApproval()) {
                $modelId = $model->model_id;

                $approvalFlowIds = $this->list()->relatedVersion(data_get($model, 'id'), data_get($model, 'parent_id'), true)->get()->pluck('id')->toArray();

                $deleted = ApprovalFlow::destroy($approvalFlowIds);

                if ($modelId) {
                    $publishedModel = $this->modelRepository->find($modelId);

                    if ($publishedModel) {
                        if (method_exists($publishedModel, 'beforeDeleteEvent')) {
                            $publishedModel->beforeDeleteEvent();
                        }

                        $this->modelRepository->delete($publishedModel);
                    }
                }
            } else {
                $deleted = $this->modelRepository->delete($model);
            }

            DB::commit();
            return $deleted;
        } catch (\Throwable $th) {
            logger('destroy', ['model' => $model, 'Throwable' => $th]);
            DB::rollBack();
            return false;
        }
    }

    /**
     * Determine if current user has roles
     */
    private function hasFlag(array $role): bool
    {
        $administrator = $this->getAuthAdministrator();

        return $administrator->permission->hasFlag($role);
    }
}
