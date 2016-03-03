<?php
namespace Xpressengine\Plugins\Comment\Exceptions;

use Xpressengine\Plugins\Comment\CommentException;
use Symfony\Component\HttpFoundation\Response;

class BadRequestException extends CommentException
{
    protected $message = 'comment::BadRequest';
    protected $statusCode = Response::HTTP_BAD_REQUEST;
}
