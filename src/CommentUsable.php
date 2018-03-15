<?php
/**
 * This file is comment usable interface
 *
 * @author      XE Developers (jhyeon1010) <cjh1010@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Crop. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment;

use Xpressengine\Routing\InstanceRoute;

/**
 * comment 를 사용하는 대상 객체 기능을 정의 함
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
