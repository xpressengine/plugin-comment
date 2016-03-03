<?php
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
