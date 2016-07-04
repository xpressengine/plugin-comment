<?php
namespace Xpressengine\Plugins\Comment\Skins;

use Xpressengine\Skin\AbstractSkin;

class EditorSkin extends AbstractSkin
{
    public function render()
    {
        return view(
            sprintf('%s::views.skin.editor.%s', app('xe.plugin.comment')->getId(), $this->view),
            $this->data
        );
    }
}
