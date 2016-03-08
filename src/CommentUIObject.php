<?php
/**
 * This file is comment ui object class
 *
 * PHP version 5
 *
 * @author      XE Team (jhyeon1010) <cjh1010@xpressengine.com>
 * @copyright   2014 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
namespace Xpressengine\Plugins\Comment;

use Xpressengine\UIObject\AbstractUIObject;
use View;
use XeFrontend;
use Skin;
use Xpressengine\Plugins\Comment\Exceptions\InvalidArgumentException;

/**
 * 댓글 ui object 랜더링
 *
 * @author      XE Team (jhyeon1010) <cjh1010@xpressengine.com>
 * @copyright   2014 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
class CommentUIObject extends AbstractUIObject
{
    /**
     * To do on boot
     *
     * @return void
     */
    public static function boot()
    {
        // nothing to do
    }

    /**
     * Rendering the view
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public function render()
    {
        /** @var CommentUsable $target */
        $target = $this->arguments['target'];

        if (!$target instanceof CommentUsable) {
            $e = new InvalidArgumentException;
            $e->setMessage(xe_trans('comment::InstanceMust', ['name' => 'CommentUsable::class']));

            throw $e;
        }

        $plugin = app('xe.plugin.comment');
        /** @var Handler $handler */
        $handler = $plugin->getHandler();
        $instanceId = $handler->getInstanceId($target->getInstanceId());

        $config = $handler->getConfig($instanceId);

        XeFrontend::js('/assets/vendor/core/js/toggleMenu.js')->appendTo('head')->before('/assets/vendor/react/react-with-addons.js')->load();
        XeFrontend::js('/assets/vendor/core/js/temporary.js')->appendTo('head')->before('/assets/vendor/react/react-with-addons.js')->load();
        XeFrontend::js($plugin->assetPath().'/service.js')->appendTo('head')->load();

//        $skin = Skin::getInstance($plugin->getId());
        $skin = Skin::getAssigned($plugin->getId());
        $view = $skin->setView('container')->setData(compact('config'))->render();

        $view = View::make(sprintf('%s::views.uio', $plugin->getId()),
            array_merge(compact('instanceId', 'target', 'config'), ['inner' => $view])
        )->render();

        return $view;
    }
}
