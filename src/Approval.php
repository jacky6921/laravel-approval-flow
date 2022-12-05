<?php

namespace Onepage\Approval;

trait Approval
{
    /**
     * Approval attributes excluded from the Audit.
     *
     * @var array
     */
    public $hasApproval = true;
}
