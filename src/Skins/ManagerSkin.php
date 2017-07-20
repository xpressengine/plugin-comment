<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment\Skins;

use Route;
use XeMenu;
use Xpressengine\Skin\AbstractSkin;

class ManagerSkin extends AbstractSkin
{
    public function render()
    {
        $prefix = sprintf('%s::views.skin.manager', app('xe.plugin.comment')->getId());
        $view = view(
            sprintf('%s.%s', $prefix, $this->view),
            $this->data
        );

        $segment = explode('/', pathinfo($view->getPath(), PATHINFO_DIRNAME));
        $type = array_pop($segment);
        if (in_array($type, ['global', 'instance'])) {
            return view(
                sprintf('%s.%s.%s', $prefix, $type, '_frame'),
                [
                    'content' => $view,
                    '_active' => substr($this->view, strrpos($this->view, '.')+1),
                    'targetInstanceId' => $targetInstanceId = Route::current()->parameter('targetInstanceId'),
                    'menuItem' => XeMenu::items()->find($targetInstanceId),
                ]
            );
        }

        return $view;
    }
}
