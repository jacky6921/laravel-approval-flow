<?php

namespace Onepage\Approval\Facades;

use Illuminate\Support\Facades\Facade;

class ApprovalStatusServices extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'approval-status-service';
    }
}