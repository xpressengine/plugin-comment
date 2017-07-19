<?php
/**
 * SettingController.php
 *
 * PHP version 5
 *
 * @category
 * @package
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Sections\DynamicFieldSection;
use App\Http\Sections\EditorSection;
use App\Http\Sections\SkinSection;
use App\Http\Sections\ToggleMenuSection;
use Validator;
use XeDB;
use XeMenu;
use XePresenter;
use Xpressengine\Http\Request;
use Xpressengine\Permission\PermissionSupport;

class SettingController extends Controller
{
    use PermissionSupport;

    protected $plugin;

    protected $handler;

    public function __construct()
    {
        $this->plugin = app('xe.plugin.comment');
        $this->handler = $this->plugin->getHandler();
        XePresenter::setSettingsSkinTargetId($this->plugin->getId());
    }

    public function getConfig($targetInstanceId)
    {
        $config = $this->handler->getConfig($this->handler->getInstanceId($targetInstanceId));

        return XePresenter::make('instance.config', [
            'targetInstanceId' => $targetInstanceId,
            'config' => $config,
        ]);
    }

    public function postConfig(Request $request, $targetInstanceId)
    {
        $validator = Validator::make($request->all(), ['perPage' => 'Numeric']);

        if ($validator->fails()) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => $validator->errors()]);
        }

        $this->handler->configure($this->handler->getInstanceId($targetInstanceId), $request->except(['_token']));

        return redirect()->back();
    }

    public function getPerm($targetInstanceId)
    {
        $permArgs = $this->getPermArguments(
            $this->handler->getKeyForPerm($this->handler->getInstanceId($targetInstanceId)),
            ['create']
        );

        return XePresenter::make('instance.perm', [
            'targetInstanceId' => $targetInstanceId,
            'permArgs' => $permArgs,
        ]);
    }

    public function postPerm(Request $request, $targetInstanceId)
    {
        $this->permissionRegister(
            $request,
            $this->handler->getKeyForPerm($this->handler->getInstanceId($targetInstanceId)),
            ['create']
        );

        return redirect()->back();
    }

    public function getSkin($targetInstanceId)
    {
        return XePresenter::make('instance.skin', [
            'section' => new SkinSection($this->plugin->getId(), $this->handler->getInstanceId($targetInstanceId)),
        ]);
    }

    public function getEditor($targetInstanceId)
    {
        return XePresenter::make('instance.editor', [
            'section' => new EditorSection($this->handler->getInstanceId($targetInstanceId)),
        ]);
    }

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

    public function getTM($targetInstanceId)
    {
        return XePresenter::make('instance.tm', [
            'section' => new ToggleMenuSection($this->plugin->getId(), $this->handler->getInstanceId($targetInstanceId)),
        ]);
    }

    public function getGlobalConfig()
    {
        return XePresenter::make('global.config', ['config' => $this->handler->getConfig()]);
    }

    public function postGlobalConfig(Request $request)
    {
        /** @var \Illuminate\Validation\Validator $validator */
        $validator = Validator::make($request->all(), ['perPage' => 'Numeric']);

        if ($validator->fails()) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => $validator->errors()]);
        }

        $this->handler->configure(null, $request->except(['_token']));

        return redirect()->back();
    }

    public function getGlobalPerm()
    {
        $permArgs = $this->getPermArguments($this->handler->getKeyForPerm(), ['create']);

        return XePresenter::make('global.perm', ['permArgs' => $permArgs]);
    }

    public function postGlobalPerm(Request $request)
    {
        $this->permissionRegister($request, $this->handler->getKeyForPerm(), ['create']);

        return redirect()->back();
    }
}
