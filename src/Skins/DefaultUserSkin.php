<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment\Skins;

use Xpressengine\Skin\AbstractSkin;
use View;

class DefaultUserSkin extends AbstractSkin
{
    public function render()
    {
        return View::make(
            sprintf('%s::views.skin.user.default.%s', app('xe.plugin.comment')->getId(), $this->view),
            $this->data
        )->render();
    }
}
