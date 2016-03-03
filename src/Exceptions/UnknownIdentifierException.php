<?php
/**
 * Created by PhpStorm.
 * User: jhyeon
 * Date: 15. 10. 13.
 * Time: 오후 3:41
 */

namespace Xpressengine\Plugins\Comment\Exceptions;

use Xpressengine\Plugins\Comment\CommentException;
use Symfony\Component\HttpFoundation\Response;

class UnknownIdentifierException extends CommentException
{
    protected $message = 'comment::UnknownId';
    protected $statusCode = Response::HTTP_BAD_REQUEST;
}
