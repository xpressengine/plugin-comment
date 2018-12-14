<?php
/**
 * CommentPolicy.php
 *
 * PHP version 5
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment;

use Illuminate\Contracts\Auth\Access\Gate;
use Xpressengine\Permission\Instance;
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\User\Models\Guest;
use Xpressengine\User\Models\UnknownUser;
use Xpressengine\User\UserInterface;

/**
 * CommentPolicy
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        https://xpressengine.io
 */
class CommentPolicy
{
    protected $gate;

    protected $handler;

    protected static $certifiedResolver;

    /**
     * CommentPolicy constructor.
     *
     * @param Gate    $gate    gate
     * @param Handler $handler comment Handler
     */
    public function __construct(Gate $gate, Handler $handler)
    {
        $this->gate = $gate;
        $this->handler = $handler;
    }

    /**
     * read
     *
     * @param UserInterface $user    user
     * @param Comment       $comment comment model
     *
     * @return bool
     */
    public function read(UserInterface $user, Comment $comment)
    {
        if ($comment->display === Comment::DISPLAY_HIDDEN) {
            return false;
        }

        if ($comment->display === Comment::DISPLAY_SECRET && $user instanceof Guest) {
            return false;
        }

        if ($comment->display === Comment::DISPLAY_SECRET
            && !$user->isManager()
            && $user->getId() !== $comment->getAuthor()->getId()
            && $user->getId() !== $comment->getTarget()->getAuthor()->getId()
        ) {
            return false;
        }

        return true;
    }

    /**
     * update
     *
     * @param UserInterface $user    user
     * @param Comment       $comment comment model
     *
     * @return bool
     */
    public function update(UserInterface $user, Comment $comment)
    {
        return $this->checkUpdateOrDelete($user, $comment);
    }

    /**
     * delete
     *
     * @param UserInterface $user    user
     * @param Comment       $comment comment model
     *
     * @return bool
     */
    public function delete(UserInterface $user, Comment $comment)
    {
        return $this->checkUpdateOrDelete($user, $comment);
    }

    /**
     * check update or delete
     *
     * @param UserInterface $user    user
     * @param Comment       $comment comment model
     *
     * @return bool
     */
    private function checkUpdateOrDelete(UserInterface $user, Comment $comment)
    {
        if ($user instanceof Guest && $comment->getAuthor() instanceof Guest && $this->isCertified($comment) === true) {
            return true;
        }

        if ($comment->getAuthor()->getId() !== null &&
            $comment->getAuthor()->getId() === $user->getId()) {
            return true;
        }

        if ($user->isManager()) {
            return true;
        }

        if ($this->gate->allows('manage', new Instance($this->handler->getKeyForPerm($comment->instance_id)))) {
            return true;
        }

        return false;
    }

    /**
     * update visible
     *
     * @param UserInterface $user    user
     * @param Comment       $comment comment model
     *
     * @return bool
     */
    public function updateVisible(UserInterface $user, Comment $comment)
    {
        return $this->update($user, $comment) || ($comment->getAuthor() instanceof Guest && $user instanceof Guest);
    }

    /**
     * delete visible
     *
     * @param UserInterface $user    user
     * @param Comment       $comment comment model
     *
     * @return bool
     */
    public function deleteVisible(UserInterface $user, Comment $comment)
    {
        return $this->delete($user, $comment) || ($comment->getAuthor() instanceof Guest && $user instanceof Guest);
    }

    /**
     * is certified
     *
     * @param Comment $comment comment model
     *
     * @return mixed
     */
    private function isCertified(Comment $comment)
    {
        return call_user_func(static::$certifiedResolver, $comment);
    }

    /**
     * set certified resolver
     *
     * @param callable $resolver reslover
     *
     * @return void
     */
    public static function setCertifiedResolver(callable $resolver)
    {
        static::$certifiedResolver = $resolver;
    }
}
