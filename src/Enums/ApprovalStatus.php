<?php
namespace Onepage\Approval\Enums;

enum ApprovalStatus:int
{
    case ARCHIVE          = 10;
    case DISABLED         = 20;
    case DRAFT            = 30;
    case PENDING_APPROVAL = 40;
    case PENDING_PUBLISH  = 50;
    case PUBLISHED        = 60;

    /**
     * Compare self type with target type
     *
     * @param ApprovalStatus $value
     * @param ApprovalStatus $strict
     * 
     * @return boolean
     */
    public function is(ApprovalStatus $value, bool $strict = true): bool
    {
        if ($strict) {
            return $value === $this;
        }

        return $value == $this;
    }
}
