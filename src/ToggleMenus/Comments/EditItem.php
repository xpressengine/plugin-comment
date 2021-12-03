<?php

declare(strict_types=1);

namespace Xpressengine\Plugins\Comment\ToggleMenus\Comments;

use Xpressengine\Permission\Instance;
use Xpressengine\Plugins\Comment\Plugin as CommentPlugin;
use Xpressengine\Plugins\Comment\Handler as CommentHandler;
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\ToggleMenu\AbstractToggleMenu;

/**
 * Class EditItem
 *
 * @package Xpressengine\Plugins\Comment\ToggleMenus\Comments
 */
class EditItem extends AbstractToggleMenu
{
    /**
     * commentHandler
     *
     * @var CommentHandler
     */
    private $commentHandler;

    /**
     * UpdateItem constructor.
     *
     * @param CommentHandler $commentHandler
     */
    public function __construct(CommentHandler $commentHandler)
    {
        $this->commentHandler = $commentHandler;
    }

    /**
     * Delete Toggle Item's title
     *
     * @return string
     */
    public static function getTitle(): string
    {
        return xe_trans('xe::update');
    }

    /**
     * Delete Toggle Item's text
     *
     * @return string
     */
    public function getText(): string
    {
        return static::getTitle();
    }

    /**
     * getType
     *
     * @return string
     */
    public function getType(): string
    {
        return static::MENUTYPE_EXEC;
    }

    /**
     * getAction
     *
     * @return string
     */
    public function getAction(): string
    {
        return sprintf('CommentToggleMenu.update(event, "%s")', $this->identifier);
    }

    /**
     * getScript
     *
     * @return string
     */
    public function getScript(): string
    {
        return CommentPlugin::asset('assets/js/toggleMenu.js');
    }

    /**
     * allows
     *
     * @return bool
     */
    public function allows(): bool
    {
        $comment = Comment::findOrFail($this->identifier);
        $permissionInstance = new Instance($this->commentHandler->getKeyForPerm($comment->instance_id));

        if (\Gate::allows('manage', $permissionInstance) === true) {
            return true;
        }

        return $comment->user_id === \Auth::id();
    }
}