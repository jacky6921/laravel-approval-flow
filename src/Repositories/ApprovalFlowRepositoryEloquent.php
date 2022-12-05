<?php

namespace Onepage\Approval\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use Onepage\Approval\Contracts\ApprovalFlowRepository;
use Onepage\Approval\Models\ApprovalFlow;
use Onepage\Approval\Enums\ApprovalStatus;

/**
 * Class ApprovalFlowRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ApprovalFlowRepositoryEloquent extends BaseRepository implements ApprovalFlowRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ApprovalFlow::class;
    }

    /**
     * Boot up the repository
     */
    public function boot()
    {
        //
    }

    /**
     * Get specific resource
     */
    public function publishedItems()
    {
        return $this->scopeQuery(fn ($query) => $query->where(
            fn ($query) => $query->where(
                fn ($query) => $query->where('version', 1)
                ->where('version_status', '!=', ApprovalStatus::ARCHIVE)
            )->orWhere(
                fn ($query) => $query->where('version', '>', 1)
                ->where('version_status', ApprovalStatus::PUBLISHED)
            )
        ));
        // return $this->scopeQuery(
        //     fn ($query) => $query->where(
        //         fn ($query) => $query->where('version', 1)
        //         ->where('version_status', '!=', ApprovalStatus::ARCHIVE)
        //     )->orWhere(
        //         fn ($query) => $query->where('version', '>', 1)
        //         ->where('version_status', ApprovalStatus::PUBLISHED)
        //     )
        // );
    }

    /**
     * Get current appoval related items
     *
     * @param [type] $id
     * @param [type] $parent_id
     * @param boolean $includeSelf
     * @return void
     */
    public function relatedVersion($id, $parent_id, $includeSelf = false)
    {
        return $this->scopeQuery(
            fn ($query) => $query->where(
                fn ($query) => $query->where(
                    fn ($query) =>
                        $query->where('parent_id', $id)
                        ->when(
                            $parent_id,
                            fn ($query) =>
                            $query->orWhere('parent_id', $parent_id)
                                ->orWhere('id', $parent_id)
                        )
                )->when(
                    $includeSelf,
                    fn ($query) => $query->orWhere('id', $id),
                    fn ($query) => $query->where('id', '!=', $id)
                )
            )
        );
        // return $this->scopeQuery(
        //     fn ($query) => $query->where(
        //         fn ($query) =>
        //             $query->where('parent_id', $id)
        //             ->when(
        //                 $parent_id,
        //                 fn ($query) =>
        //                 $query->orWhere('parent_id', $parent_id)
        //                     ->orWhere('id', $parent_id)
        //             )
        //     )->when(
        //         $includeSelf,
        //         fn ($query) => $query->orWhere('id', $id),
        //         fn ($query) => $query->where('id', '!=', $id)
        //     )
        // );
    }
}
