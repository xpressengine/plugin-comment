<?php
/**
 * This file is comment usable interface
 *
 * PHP version 5
 *
 * @author      XE Team (jhyeon1010) <cjh1010@xpressengine.com>
 * @copyright   2014 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
namespace Xpressengine\Plugins\Comment;

/**
 * comment 를 사용하는 대상 객체 기능을 정의 함
 *
 * @author      XE Team (jhyeon1010) <cjh1010@xpressengine.com>
 * @copyright   2014 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
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
     * @return \Xpressengine\Member\Entities\MemberEntityInterface
     */
    public function getAuthor();
}
