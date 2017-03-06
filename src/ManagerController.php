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
use App\Http\Sections\EditorSection;
use App\Http\Sections\SkinSection;
use App\Http\Sections\ToggleMenuSection;
use Input;
use Validator;
use Xpressengine\Http\Request;
use Xpressengine\Menu\MenuHandler;
use XePresenter;
use XeConfig;
use XeDB;
use Xpressengine\Menu\Models\MenuItem;
use Xpressengine\Menu\ModuleHandler;
use Xpressengine\Permission\PermissionSupport;
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\Support\Exceptions\InvalidArgumentException;

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

    public function index(MenuHandler $menus, ModuleHandler $modules)
    {
        Input::flash();

        $model = $this->handler->createModel();
        $query = $model->newQuery()
            ->whereIn('instanceId', $this->getInstances())
            ->where('status', '!=', Comment::STATUS_TRASH);

        if ($options = Input::get('options')) {
            list($searchField, $searchValue) = explode('|', $options);

            $query->where($searchField, $searchValue);
        }

        $comments = $query->orderBy(Comment::CREATED_AT)->with('target')->paginate();

        $map = $this->handler->getInstanceMap();
        $menuItems = $menus->getItemIn(array_keys($map), 'route')->getDictionary();

        return XePresenter::make('index', [
            'comments' => $comments,
            'menuItem' => function ($comment) use ($menuItems, $map) {
                $index = array_search($comment->instanceId, $map);
                if (isset($menuItems[$index]) === false) {
                    $tmpMenuItem = new MenuItem;
                    $tmpMenuItem->title = $index;
                    return $tmpMenuItem;
                } else {
                    return $menuItems[$index];
                }
            },
            'urlMake' => function ($comment, $menuItem) use ($modules) {
                if (isset($menuItem->type) == true) {
                    if ($module = $modules->getModuleObject($menuItem->type)) {
                        if ($item = $module->getTypeItem($comment->target->targetId)) {
                            return app('url')->to($item->getLink($menuItem->route) . '#comment-'.$comment->id);
                        }
                    }
                }

                return null;
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

        switch ($approved) {
            case Comment::APPROVED_APPROVED:
                $method = 'approve';
                break;
            case Comment::APPROVED_REJECTED:
                $method = 'reject';
                break;
            default :
                throw new InvalidArgumentException;
                break;
        }

        foreach ($comments as $comment) {
            $this->handler->$method($comment);
        }

        return redirect()->back();
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

        return redirect()->back();
    }

    public function trash(MenuHandler $menus)
    {
        Input::flash();

        $model = $this->handler->createModel();
        $comments = $model->newQuery()
            ->whereIn('instanceId', $this->getInstances())
            ->where('status', Comment::STATUS_TRASH)
            ->orderBy(Comment::CREATED_AT)->paginate();

        $map = $this->handler->getInstanceMap();
        $menuItems = $menus->getItemIn(array_keys($map), 'route')->getDictionary();

        return XePresenter::make('trash', [
            'comments' => $comments,
            'menuItem' => function ($comment) use ($menuItems, $map) {
                $index = array_search($comment->instanceId, $map);
                if (isset($menuItems[$index]) === false) {
                    $tmpMenuItem = new MenuItem;
                    $tmpMenuItem->title = $index;
                    return $tmpMenuItem;
                } else {
                    return $menuItems[$index];
                }
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

        return redirect()->back();
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

        return redirect()->back();
    }

    public function getSetting(MenuHandler $menus, $targetInstanceId)
    {
        $instanceId = $this->handler->getInstanceId($targetInstanceId);
        $config = $this->handler->getConfig($instanceId);

        $permArgs = $this->getPermArguments($this->handler->getKeyForPerm($instanceId), ['create']);

        $skinSection = new SkinSection($this->plugin->getId(), $instanceId);
        $editorSection = new EditorSection($instanceId);

        $dynamicFieldSection = new DynamicFieldSection(
            sprintf('documents_%s', $instanceId),
            $this->handler->createModel()->getConnection()
        );
        $toggleMenuSection = new ToggleMenuSection($this->plugin->getId(), $instanceId);

        $menuItem = $menus->getItem($targetInstanceId);

        return XePresenter::make('setting', [
            'targetInstanceId' => $targetInstanceId,
            'config' => $config,
            'permArgs' => $permArgs,
            'skinSection' => $skinSection,
            'editorSection' => $editorSection,
            'dynamicFieldSection' => $dynamicFieldSection,
            'toggleMenuSection' => $toggleMenuSection,
            'menuItem' => $menuItem,
        ]);
    }

    public function postSetting(Request $request, $targetInstanceId)
    {
        $instanceId = $this->handler->getInstanceId($targetInstanceId);

        $configInputs = array_filter($request->except(['_token']), function ($key) {
            return substr($key, 0, strlen('create')) !== 'create';
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

        $this->permissionRegister($request, $this->handler->getKeyForPerm($instanceId), ['create']);

        return redirect()->back();
    }

    public function getGlobalSetting()
    {
        $config = $this->handler->getConfig();

        $permArgs = $this->getPermArguments($this->handler->getKeyForPerm(), ['create']);

        return XePresenter::make('global', ['config' => $config, 'permArgs' => $permArgs]);
    }

    public function postGlobalSetting(Request $request)
    {
        $configInputs = array_filter($request->except(['_token']), function ($key) {
            return substr($key, 0, strlen('create')) !== 'create';
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

            $this->permissionRegister($request, $this->handler->getKeyForPerm(), ['create']);

            XeDB::commit();
        } catch (\Exception $e) {
            XeDB::rollBack();

            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => $e->getMessage()]);
        }

        return redirect()->route('manage.comment.setting.global');
    }
}
