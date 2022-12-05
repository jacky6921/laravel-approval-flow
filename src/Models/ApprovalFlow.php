<?php

namespace Onepage\Approval\Models;

use Prettus\Repository\Traits\PresentableTrait;
use Prettus\Repository\Contracts\Presentable;
use Illuminate\Database\Eloquent\Model;
use Onepage\Approval\Enums\ApprovalStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Onepage\Approval\Presenters\ApprovalFlowPresenter;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibraryPro\Models\TemporaryUpload;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\Image\Manipulations;

/**
 * Class ApprovalFlow.
 *
 * @package namespace App\Models;
 */
class ApprovalFlow extends Model implements Presentable, HasMedia
{
    use PresentableTrait;
    use InteractsWithMedia;

    /**
     * Specify Presenter class name
     *
     * @return string
     */
    protected $presenter = ApprovalFlowPresenter::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'values',
        'parent_id',
        'version',
        'version_status',
        'created_by',
        'submitted_by',
        'approved_by',
        'published_by'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        //
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'values' => 'array',
        'version_status' => ApprovalStatus::class,
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        //
    ];

    public function publishedModel()
    {
        return $this->belongsTo("\\$this->model_type", $this->model_id);
        // $this->belongsTo();
    }
}
