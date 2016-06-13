<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment;

use App\Http\Controllers\Controller;
use App\Http\Sections\DynamicFieldSection;
use App\Http\Sections\SkinSection;
use App\Http\Sections\ToggleMenuSection;
use Input;
use Validator;
use Xpressengine\Http\Request;
use Xpressengine\Menu\MenuHandler;
use XePresenter;
use XeConfig;
use XeDB;
use Xpressengine\Permission\PermissionSupport;

class ManagerController extends Controller
{
    use PermissionSupport;

    protected $plugin;

    /**
     * @var Handler
     */
    protected $handler;

    public function __construct()
    {
        $this->plugin = app('xe.plugin.comment');
        $this->handler = $this->plugin->getHandler();
        XePresenter::setSettingsSkinTargetId($this->plugin->getId());
    }

    protected function getInstances()
    {
        $map = XeConfig::get('comment_map');
        $instanceIds = [];
        foreach ($map as $instanceId) {
            $instanceIds[] = $instanceId;
        }

        return $instanceIds;
    }

    public function index()
    {
        Input::flash();

        $model = $this->handler->createModel();
        $query = $model->newQuery()
            ->whereIn('instanceId', $this->getInstances())
            ->where('status', 'public');

        if ($options = Input::get('options')) {
            list($searchField, $searchValue) = explode('|', $options);

            $query->where($searchField, $searchValue);
        }

        $comments = $query->with('target')->paginate();

        $map = $this->handler->getInstanceMap();
        $menuItems = app('xe.menu')->createItemModel()->newQuery()->with('route')
            ->whereIn('id', array_keys($map))->get()->getDictionary();

        return XePresenter::make('index', [
            'comments' => $comments,
            'menuItem' => function ($comment) use ($menuItems, $map) {
                return $menuItems[array_search($comment->instanceId, $map)];
            },
            'urlMake' => function ($comment, $menuItem) {
                $module = app('xe.module');
                return url($module->getModuleObject($menuItem->type)
                        ->getTypeItem($comment->target->targetId)
                        ->getLink($menuItem->route) . '#comment-'.$comment->id);
            },
        ]);
    }

    public function approve()
    {
        $approved = Input::get('approved');
        $commentIds = Input::get('id');
        $commentIds = is_array($commentIds) ? $commentIds : [$commentIds];

        $model = $this->handler->createModel();
        $comments = $model->newQuery()
            ->whereIn('instanceId', $this->getInstances())
            ->whereIn('id', $commentIds)->get();

        foreach ($comments as $comment) {
            $comment->approved = $approved;

            $this->handler->put($comment);
        }

        if (Input::get('redirect') != null) {
            return redirect(Input::get('redirect'));
        } else {
            return redirect()->route('manage.comment.index');
        }
    }

    public function toTrash()
    {
        $commentIds = Input::get('id');
        $commentIds = is_array($commentIds) ? $commentIds : [$commentIds];

        $model = $this->handler->createModel();
        $comments = $model->newQuery()
            ->whereIn('instanceId', $this->getInstances())
            ->whereIn('id', $commentIds)->get();

        foreach ($comments as $comment) {
            $this->handler->trash($comment);
        }

        if (Input::get('redirect') != null) {
            return redirect(Input::get('redirect'));
        } else {
            return redirect()->route('manage.comment.index');
        }
    }

    public function trash()
    {
        Input::flash();

        $model = $this->handler->createModel();
        $comments = $model->newQuery()
            ->whereIn('instanceId', $this->getInstances())
            ->where('status', 'trash')->paginate();

        $map = $this->handler->getInstanceMap();
        $menuItems = app('xe.menu')->createItemModel()->newQuery()->with('route')
            ->whereIn('id', array_keys($map))->get()->getDictionary();

        return XePresenter::make('trash', [
            'comments' => $comments,
            'menuItem' => function ($comment) use ($menuItems, $map) {
                return $menuItems[array_search($comment->instanceId, $map)];
            },
        ]);
    }

    public function destroy()
    {
        $commentIds = Input::get('id');
        $commentIds = is_array($commentIds) ? $commentIds : [$commentIds];

        $model = $this->handler->createModel();
        $comments = $model->newQuery()->whereIn('id', $commentIds)->get();

        foreach ($comments as $comment) {
            $this->handler->remove($comment);
        }

        if (Input::get('redirect') != null) {
            return redirect(Input::get('redirect'));
        } else {
            return redirect()->route('manage.comment.index');
        }
    }

    public function restore()
    {
        $commentIds = Input::get('id');
        $commentIds = is_array($commentIds) ? $commentIds : [$commentIds];

        $model = $this->handler->createModel();
        $comments = $model->newQuery()
            ->whereIn('instanceId', $this->getInstances())
            ->whereIn('id', $commentIds)->get();

        foreach ($comments as $comment) {
            $this->handler->restore($comment);
        }

        if (Input::get('redirect') != null) {
            return redirect(Input::get('redirect'));
        } else {
            return redirect()->route('manage.comment.index');
        }
    }

    public function getSetting(MenuHandler $menus, $targetInstanceId)
    {
        $instanceId = $this->handler->getInstanceId($targetInstanceId);
        $config = $this->handler->getConfig($instanceId);

        $permArgs = $this->getPermArguments($this->handler->getKeyForPerm($instanceId), ['create', 'download']);

        $skinSection = new SkinSection($this->plugin->getId(), $instanceId);

        $dynamicFieldSection = new DynamicFieldSection(
            str_replace('.', '_', $config->name),
            $this->handler->createModel()->getConnection()
        );
        $toggleMenuSection = new ToggleMenuSection($this->plugin->getId(), $instanceId);

        $menuItem = $menus->createItemModel()->newQuery()
            ->where('id', $targetInstanceId)->first();

        return XePresenter::make('setting', [
            'targetInstanceId' => $targetInstanceId,
            'config' => $config,
            'permArgs' => $permArgs,
            'skinSection' => $skinSection,
            'dynamicFieldSection' => $dynamicFieldSection,
            'toggleMenuSection' => $toggleMenuSection,
            'menuItem' => $menuItem,
        ]);
    }

    public function postSetting(Request $request, $targetInstanceId)
    {
        $instanceId = $this->handler->getInstanceId($targetInstanceId);

        $configInputs = array_filter($request->except(['redirect', '_token']), function ($key) {
            return substr($key, 0, strlen('create')) !== 'create'
            && substr($key, 0, strlen('download')) !== 'download';
        }, ARRAY_FILTER_USE_KEY);

        $validator = Validator::make([
            'perPage' => $request->get('perPage')
        ], [
            'perPage' => 'Numeric'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => $validator->errors()]);
        }

        $this->handler->configure($instanceId, $configInputs);

        $this->permissionRegister($request, $this->handler->getKeyForPerm($instanceId), ['create', 'download']);

        if ($request->get('redirect') != null) {
            return redirect($request->get('redirect'));
        } else {
            return redirect()->route('manage.comment.setting', $targetInstanceId);
        }
    }

    public function getGlobalSetting()
    {
        $config = $this->handler->getConfig();
        $permArgs = $this->getPermArguments($this->handler->getKeyForPerm(), ['create', 'download']);

        return XePresenter::make('global', ['config' => $config, 'permArgs' => $permArgs]);
    }

    public function postGlobalSetting(Request $request)
    {
        $configInputs = array_filter($request->except(['_token']), function ($key) {
            return substr($key, 0, strlen('create')) !== 'create'
            && substr($key, 0, strlen('download')) !== 'download';
        }, ARRAY_FILTER_USE_KEY);

        /** @var \Illuminate\Validation\Validator $validator */
        $validator = Validator::make(
            ['perPage' => $request->get('perPage')],
            ['perPage' => 'Numeric']
        );

        if ($validator->fails()) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => $validator->errors()]);
        }

        XeDB::beginTransaction();

        try {
            $this->handler->configure(null, $configInputs);

            $this->permissionRegister($request, $this->handler->getKeyForPerm(), ['create', 'download']);

            XeDB::commit();
        } catch (\Exception $e) {
            XeDB::rollBack();

            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => $e->getMessage()]);
        }

        return redirect()->route('manage.comment.setting.global');
    }
}
