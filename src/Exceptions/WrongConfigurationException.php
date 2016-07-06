<?php
namespace Xpressengine\Plugins\Comment\Exceptions;

use Xpressengine\Plugins\Comment\CommentException;

class WrongConfigurationException extends CommentException
{
    protected $message = 'comment::wrongConfig';
}
