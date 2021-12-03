<?php
/**
 * ManagerSkin.php
 *
 * PHP version 7
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment\Skins;

use Route;
use XeMenu;
use Xpressengine\Skin\AbstractSkin;

/**
 * ManagerSkin
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class ManagerSkin extends AbstractSkin
{
    /**
     * render
     *
     * @return \Illuminate\Contracts\Support\Renderable|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
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
                    '_active' => substr($this->view, strrpos($this->view, '.') + 1),
                    'targetInstanceId' => $targetInstanceId = Route::current()->parameter('targetInstanceId'),
                    'menuItem' => XeMenu::items()->find($targetInstanceId),
                ]
            );
        }

        return $view;
    }
}
