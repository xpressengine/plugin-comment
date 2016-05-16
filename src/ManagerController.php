<?php
namespace Xpressengine\Plugins\Comment;

use App\Http\Controllers\Controller;
use App\Http\Sections\DynamicFieldSection;
use App\Http\Sections\SkinSection;
use App\Http\Sections\ToggleMenuSection;
use Input;
use Validator;
use Xpressengine\Menu\MenuHandler;
use Xpressengine\Permission\Grant;
use XePresenter;
use XeConfig;
use XeDB;
use Xpressengine\User\Models\UserGroup;

class ManagerController extends Controller
{
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

        $permission = $this->handler->getPermission($instanceId);

        $mode = function ($action) use ($permission) {
            return $permission->pure($action) ? 'manual' : 'inherit';
        };

        $allGroup = UserGroup::get();
        $permArgs = [
            'create' => [
                'mode' => $mode('create'),
                'grant' => $permission['create'],
                'title' => 'create',
                'groups' => $allGroup,
            ],
            'download' => [
                'mode' => $mode('download'),
                'grant' => $permission['download'],
                'title' => 'download',
                'groups' => $allGroup,
            ]
        ];

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

    public function postSetting($targetInstanceId)
    {
        $instanceId = $this->handler->getInstanceId($targetInstanceId);

        $inputs = Input::except(['redirect', '_token']);

        $configInputs = $permInputs = [];
        foreach ($inputs as $name => $value) {
            if (substr($name, 0, strlen('create')) === 'create'
            || substr($name, 0, strlen('download')) === 'download') {
                $permInputs[$name] = $value;
            } else {
                $configInputs[$name] = $value;
            }
        }

        $validator = Validator::make([
            'perPage' => Input::get('perPage')
        ], [
            'perPage' => 'Numeric'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => $validator->errors()]);
        }

        $this->handler->configure($instanceId, $configInputs);

        $grantInfo = [
            'create' => $this->makeGrant($permInputs, 'create'),
            'download' => $this->makeGrant($permInputs, 'download'),
        ];

        $grant = new Grant();
        foreach (array_filter($grantInfo) as $action => $info) {
            $grant->set($action, $info);
        }

        $this->handler->setPermission($instanceId, $grant);

        if (Input::get('redirect') != null) {
            return redirect(Input::get('redirect'));
        } else {
            return redirect()->route('manage.comment.setting', $targetInstanceId);
        }
    }

    private function makeGrant($inputs, $action)
    {
        if (array_get($inputs, $action . 'Mode') === 'inherit') {
            return null;
        }

        return [
            Grant::RATING_TYPE => array_get($inputs, $action . 'Rating'),
            Grant::GROUP_TYPE => array_get($inputs, $action . 'Group') ?: [],
            Grant::USER_TYPE => array_filter(explode(',', array_get($inputs, $action . 'User'))),
            Grant::EXCEPT_TYPE => array_filter(explode(',', array_get($inputs, $action . 'Except'))),
            Grant::VGROUP_TYPE => array_get($inputs, $action . 'VGroup') ?: [],
        ];
    }

    public function getGlobalSetting()
    {
        $config = $this->handler->getConfig();
        $permission = $this->handler->getPermission();

        $allGroup = UserGroup::get();
        $permArgs = [
            'create' => [
                'grant' => $permission['create'],
                'title' => 'create',
                'groups' => $allGroup,
            ],
            'download' => [
                'grant' => $permission['download'],
                'title' => 'download',
                'groups' => $allGroup,
            ]
        ];
        
        return XePresenter::make('global', ['config' => $config, 'permArgs' => $permArgs]);
    }

    public function postGlobalSetting()
    {
        $inputs = Input::except(['_token']);

        $configInputs = $permInputs = [];
        foreach ($inputs as $name => $value) {
            if (substr($name, 0, strlen('create')) === 'create'
                || substr($name, 0, strlen('download')) === 'download') {
                $permInputs[$name] = $value;
            } else {
                $configInputs[$name] = $value;
            }
        }

        /** @var \Illuminate\Validation\Validator $validator */
        $validator = Validator::make(
            ['perPage' => Input::get('perPage')],
            ['perPage' => 'Numeric']
        );

        if ($validator->fails()) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => $validator->errors()]);
        }

        XeDB::beginTransaction();

        try {
            $this->handler->configure(null, $configInputs);

            $grantInfo = [
                'create' => $this->makeGrant($permInputs, 'create'),
                'download' => $this->makeGrant($permInputs, 'download'),
            ];

            $grant = new Grant();
            foreach (array_filter($grantInfo) as $action => $info) {
                $grant->set($action, $info);
            }

            $this->handler->setPermission(null, $grant);

            XeDB::commit();
        } catch (\Exception $e) {
            XeDB::rollBack();

            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => $e->getMessage()]);
        }

        return redirect()->route('manage.comment.setting.global');
    }
}