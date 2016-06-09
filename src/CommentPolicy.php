<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment;

use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\User\Models\Guest;
use Xpressengine\User\UserInterface;

class CommentPolicy
{
    protected static $certifiedResolver;

    public function read(UserInterface $user, Comment $comment)
    {
        if ($comment->display === 'hidden') {
            return false;
        }

        if ($comment->display === 'secret'
            && !$user->isManager()
            && $user->getId() != $comment->getAuthor()->getId()
            && $user->getId() != $comment->target->getAuthor()->getId()) {
            return false;
        }

        return true;
    }

    public function update(UserInterface $user, Comment $comment)
    {
        return $this->checkUpdateOrDelete($user, $comment);
    }

    public function delete(UserInterface $user, Comment $comment)
    {
        return $this->checkUpdateOrDelete($user, $comment);
    }

    private function checkUpdateOrDelete(UserInterface $user, Comment $comment)
    {
        if ($user instanceof Guest && $comment->getAuthor()->getId() === null && $this->isCertified($comment) === true) {
            return true;
        }

        if ($comment->getAuthor()->getId() !== null
            && $comment->getAuthor()->getId() == $user->getId()) {
            return true;
        }

        if ($user->isManager()) {
            return true;
        }

        return false;
    }

    public function updateVisible(UserInterface $user, Comment $comment)
    {
        return $this->update($user, $comment) || ($comment->userId == null && $user instanceof Guest);
    }

    public function deleteVisible(UserInterface $user, Comment $comment)
    {
        return $this->delete($user, $comment) || ($comment->userId == null && $user instanceof Guest);
    }

    private function isCertified(Comment $comment)
    {
        $resolver = static::$certifiedResolver;

        return $resolver($comment);
    }

    public static function setCertifiedResolver(callable $resolver)
    {
        static::$certifiedResolver = $resolver;
    }
}
