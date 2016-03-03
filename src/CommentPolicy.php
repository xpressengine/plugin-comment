<?php
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
            && $user->getId() != $comment->author->getId()
            && $user->getId() != $comment->target->author->getId()) {
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
        if ($user instanceof Guest && $comment->author->getId() === null && $this->isCertified($comment) === true) {
            return true;
        }

        if ($comment->author->getId() !== null
            && $comment->author->getId() == $user->getId()) {
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
