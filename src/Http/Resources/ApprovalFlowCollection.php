<?php

namespace Onepage\Approval\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use ApprovalStatusServices;

class ApprovalFlowCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => data_get($this, 'id'),
            'model_type'  =>  data_get($this, 'model_type'),
            'model_id'  =>  data_get($this, 'model_id'),
            'parent_id'  =>  data_get($this, 'parent_id'),

            'version'  =>  data_get($this, 'version'),
            'version_status'  =>  data_get($this, 'version_status'),

            'created_by'  =>  data_get($this, 'created_by'),
            'submitted_by'  =>  data_get($this, 'submitted_by'),
            'approved_by'  =>  data_get($this, 'approved_by'),
            'published_by' =>  data_get($this, 'published_by'),

            //links
            'action_routes' => ApprovalStatusServices::getAvailableAction($this->resource)


            // 'action_routes' => ApprovalServices::getAvailableAction($this->resource),
        ];
    }

    /**
     * Translations WCMS
     */
    // $this->mergeWhen(
    //     $this->whenLoaded('translations'),
    //     $this->presenter()->unpackTranslations
    // ),

    /**
     * Media WCMS
     */
    // $this->mergeWhen($this->whenLoaded('media'), [
    //     'avatar' =>  $this->getMedia('avatar'),
    // ]),

    /**
     * Translations API
     */
    // $this->mergeWhen(
    //     $this->whenLoaded('translations'),
    //     $this->presenter()->unpackTranslationsWithLocale
    // ),

    /**
     * Media without locale code API
     */
    // $this->mergeWhen($this->whenLoaded('media'), [
    //     'avatar' =>  $this->getMedia('avatar')
    //                     ->map(fn($item) => $item->getUrl()),
    // ]),

    /**
     * Media with locale code API
     */
    // $this->mergeWhen(
    //     $this->whenLoaded('media'),
    //     $this->presenter()->unpackMediaTranslationsWithLocale([
    //         'main_visual',
    //     ])
    // ),
}
