<?php
namespace Xpressengine\Plugins\Comment\Exceptions;

use Xpressengine\Plugins\Comment\CommentException;

class InstanceIdGenerateFailException extends CommentException
{
    protected $message = 'comment::instanceIdGenerateFail';
}
