<?php
/**
 * SettingController.php
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

namespace Xpressengine\Plugins\Comment\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Sections\DynamicFieldSection;
use App\Http\Sections\EditorSection;
use App\Http\Sections\SkinSection;
use App\Http\Sections\ToggleMenuSection;
use XeDB;
use XeMenu;
use XePresenter;
use Xpressengine\Http\Request;
use Xpressengine\Permission\PermissionSupport;

/**
 * SettingController
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class SettingController extends Controller
{
    use PermissionSupport;

    protected $plugin;

    protected $handler;

    /**
     * SettingController constructor.
     */
    public function __construct()
    {
        $this->plugin = app('xe.plugin.comment');
        $this->handler = $this->plugin->getHandler();
        XePresenter::setSettingsSkinTargetId($this->plugin->getId());
    }

    /**
     * get config
     *
     * @param string $targetInstanceId target instance id
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function getConfig($targetInstanceId)
    {
        $config = $this->handler->getConfig($this->handler->getInstanceId($targetInstanceId));

        return XePresenter::make('instance.config', [
            'targetInstanceId' => $targetInstanceId,
            'config' => $config,
        ]);
    }

    /**
     * post config
     *
     * @param Request $request          request
     * @param string  $targetInstanceId target instance id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postConfig(Request $request, $targetInstanceId)
    {
        $this->validate($request, ['perPage' => 'Numeric']);

        $this->handler->configure($this->handler->getInstanceId($targetInstanceId), $request->except(['_token']));

        return redirect()->back();
    }

    /**
     * get perm
     *
     * @param string $targetInstanceId target instance id
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function getPerm($targetInstanceId)
    {
        $permArgs = $this->getPermArguments(
            $this->handler->getKeyForPerm($this->handler->getInstanceId($targetInstanceId)),
            ['create', 'manage']
        );

        return XePresenter::make('instance.perm', [
            'targetInstanceId' => $targetInstanceId,
            'permArgs' => $permArgs,
        ]);
    }

    /**
     * post perm
     *
     * @param Request $request          request
     * @param string  $targetInstanceId target instance id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postPerm(Request $request, $targetInstanceId)
    {
        $this->permissionRegister(
            $request,
            $this->handler->getKeyForPerm($this->handler->getInstanceId($targetInstanceId)),
            ['create', 'manage']
        );

        return redirect()->back();
    }

    /**
     * get skin
     *
     * @param string $targetInstanceId target instance id
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function getSkin($targetInstanceId)
    {
        return XePresenter::make('instance.skin', [
            'section' => new SkinSection($this->plugin->getId(), $this->handler->getInstanceId($targetInstanceId)),
        ]);
    }

    /**
     * get editor
     *
     * @param string $targetInstanceId target instance id
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function getEditor($targetInstanceId)
    {
        return XePresenter::make('instance.editor', [
            'section' => new EditorSection($this->handler->getInstanceId($targetInstanceId)),
        ]);
    }

    /**
     * get DF
     *
     * @param string $targetInstanceId target instance id
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function getDF($targetInstanceId)
    {
        $section = new DynamicFieldSection(
            sprintf('documents_%s', $this->handler->getInstanceId($targetInstanceId)),
            $this->handler->createModel()->getConnection()
        );

        return XePresenter::make('instance.df', [
            'section' => $section,
        ]);
    }

    /**
     * get TM
     *
     * @param string $targetInstanceId target instance id
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function getTM($targetInstanceId)
    {
        return XePresenter::make('instance.tm', [
            'section' => new ToggleMenuSection(
                $this->plugin->getId(),
                $this->handler->getInstanceId($targetInstanceId)
            ),
        ]);
    }

    /**
     * get global config
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function getGlobalConfig()
    {
        return XePresenter::make('global.config', ['config' => $this->handler->getConfig()]);
    }

    /**
     * post global config
     *
     * @param Request $request request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postGlobalConfig(Request $request)
    {
        $this->validate($request, ['perPage' => 'Numeric']);

        $this->handler->configure(null, $request->except(['_token']));

        return redirect()->back();
    }

    /**
     * get global perm
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function getGlobalPerm()
    {
        $permArgs = $this->getPermArguments($this->handler->getKeyForPerm(), ['create', 'manage']);

        return XePresenter::make('global.perm', ['permArgs' => $permArgs]);
    }

    /**
     * post global perm
     *
     * @param Request $request request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postGlobalPerm(Request $request)
    {
        $this->permissionRegister($request, $this->handler->getKeyForPerm(), ['create', 'manage']);

        return redirect()->back();
    }

    /**
     * config 설정 페이지로 redirection
     *
     * @param string $targetInstanceId target instance id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToConfig($targetInstanceId)
    {
        return redirect()->route('comment::setting.config', $targetInstanceId);
    }

    /**
     * global 페이지로 redirection
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGlobal()
    {
        return redirect()->route('comment::setting.global.config');
    }
}
