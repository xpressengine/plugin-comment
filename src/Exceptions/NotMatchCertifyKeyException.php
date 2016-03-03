<?php
namespace Xpressengine\Plugins\Comment\Exceptions;

use Xpressengine\Plugins\Comment\CommentException;
use Symfony\Component\HttpFoundation\Response;

class NotMatchCertifyKeyException extends CommentException
{
    protected $message = 'comment::PasswordNotMatch';
    protected $statusCode = Response::HTTP_UNAUTHORIZED;
}
