<?php
/**
 * CommentUsable.php
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

use Xpressengine\Routing\InstanceRoute;

/**
 * CommentUsable
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        https://xpressengine.io
 */
interface CommentUsable
{
    /**
     * Returns unique identifier
     *
     * @return string
     */
    public function getUid();

    /**
     * Returns instance identifier
     *
     * @return mixed
     */
    public function getInstanceId();

    /**
     * Returns author
     *
     * @return \Xpressengine\User\UserInterface
     */
    public function getAuthor();

    /**
     * Returns the link
     *
     * @param InstanceRoute $route route instance
     *
     * @return string
     */
    public function getLink(InstanceRoute $route);

//    /**
//     * Get morph type for relation
//     *
//     * class name or alias of morph map
//     *
//     * ```
//     * use Illuminate\Database\Eloquent\Relations\Relation;
//     *
//     * Relation::morphMap([
//     *  'posts' => 'App\Post',
//     *  'videos' => 'App\Video',
//     * ]);
//     * ```
//     *
//     * @return string
//     */
//    public function getMorphType();
}
