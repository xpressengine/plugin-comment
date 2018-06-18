<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment\Controllers;

use App\Http\Controllers\Controller;
use XePresenter;
use XeConfig;
use XeDB;
use Xpressengine\Http\Request;
use Xpressengine\Menu\MenuHandler;
use Xpressengine\Menu\Models\MenuItem;
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

    public function index(Request $request, MenuHandler $menus)
    {
        $request->flash();

        $model = $this->handler->createModel();
        $query = $model->newQuery()
            ->whereIn('instance_id', $this->getInstances())
            ->where('status', '!=', Comment::STATUS_TRASH);

        $totalCount = count($query->get());

        $statusMessage = xe_trans('comment::status');

        if ($options = $request->get('options')) {
            list($searchField, $searchValue) = explode('|', $options);

            $query->where($searchField, $searchValue);

            $messageType = '';

            if ($searchField == 'display') {
                switch ($searchValue) {
                    case Comment::DISPLAY_VISIBLE:
                        $messageType = 'public';
                        break;

                    case Comment::DISPLAY_SECRET:
                        $messageType = 'secret';
                        break;
                }
            } elseif ($searchField == 'approved') {
                $messageType = 'approved.';

                switch ($searchValue) {
                    case Comment::APPROVED_APPROVED:
                        $messageType .= 'approved';
                        break;

                    case Comment::APPROVED_WAITING:
                        $messageType .= 'waiting';
                        break;

                    case Comment::APPROVED_REJECTED:
                        $messageType .= 'reject';
                        break;
                }
            }

            if ($messageType) {
                $statusMessage = xe_trans('comment::manage.' . $messageType);
            }
        }

        $searchTargetWord = $request->get('search_target');

        if ($request->has('search_target')) {
            $query = $this->makeWhere($query, $request);
        }

        $comments = $query->orderBy(Comment::CREATED_AT)->with('target')->paginate();

        $map = $this->handler->getInstanceMap();
        $menuItems = $menus->items()->fetchIn(array_keys($map), 'route')->getDictionary();

        return XePresenter::make('docs.index', [
            'comments' => $comments,
            'menuItem' => function ($comment) use ($menuItems, $map) {
                $index = array_search($comment->instance_id, $map);
                if (isset($menuItems[$index]) === false) {
                    $tmpMenuItem = new MenuItem;
                    $tmpMenuItem->title = $index;
                    return $tmpMenuItem;
                } else {
                    return $menuItems[$index];
                }
            },
            'urlMake' => function ($comment, $menuItem) use ($menus) {
                if (isset($menuItem->type) == true) {
                    if ($module = $menus->getModuleHandler()->getModuleObject($menuItem->type)) {
                        if ($comment->getTarget() && $item = $module->getTypeItem($comment->getTarget()->target_id)) {
                            return app('url')->to($item->getLink($menuItem->route) . '#comment-'.$comment->id);
                        }
                    }
                }

                return null;
            },
            'searchTargetWord' => $searchTargetWord,
            'statusMessage' => $statusMessage,
            'totalCount' => $totalCount,
        ]);
    }

    public function approve(Request $request)
    {
        $approved = $request->get('approved');
        $commentIds = $request->get('id');
        $commentIds = is_array($commentIds) ? $commentIds : [$commentIds];

        $model = $this->handler->createModel();
        $comments = $model->newQuery()
            ->whereIn('instance_id', $this->getInstances())
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

    public function toTrash(Request $request)
    {
        $commentIds = $request->get('id');
        $commentIds = is_array($commentIds) ? $commentIds : [$commentIds];

        $model = $this->handler->createModel();
        $comments = $model->newQuery()
            ->whereIn('instance_id', $this->getInstances())
            ->whereIn('id', $commentIds)->get();

        foreach ($comments as $comment) {
            $this->handler->trash($comment);
        }

        return redirect()->back();
    }

    public function trash(Request $request, MenuHandler $menus)
    {
        $request->flash();

        $model = $this->handler->createModel();
        $comments = $model->newQuery()
            ->whereIn('instance_id', $this->getInstances())
            ->where('status', Comment::STATUS_TRASH)
            ->orderBy(Comment::CREATED_AT)->paginate();

        $map = $this->handler->getInstanceMap();
        $menuItems = $menus->items()->fetchIn(array_keys($map), 'route')->getDictionary();

        return XePresenter::make('docs.trash', [
            'comments' => $comments,
            'menuItem' => function ($comment) use ($menuItems, $map) {
                $index = array_search($comment->instance_id, $map);
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

    public function destroy(Request $request)
    {
        $commentIds = $request->get('id');
        $commentIds = is_array($commentIds) ? $commentIds : [$commentIds];

        $model = $this->handler->createModel();
        $comments = $model->newQuery()->whereIn('id', $commentIds)->get();

        foreach ($comments as $comment) {
            $this->handler->remove($comment);
        }

        return redirect()->back();
    }

    public function restore(Request $request)
    {
        $commentIds = $request->get('id');
        $commentIds = is_array($commentIds) ? $commentIds : [$commentIds];

        $model = $this->handler->createModel();
        $comments = $model->newQuery()
            ->whereIn('instance_id', $this->getInstances())
            ->whereIn('id', $commentIds)->get();

        foreach ($comments as $comment) {
            $this->handler->restore($comment);
        }

        return redirect()->back();
    }

    protected function makeWhere($query, $request)
    {
        if ($request->get('search_target') == 'content') {
            $query = $query->where('pure_content', 'like', sprintf('%%%s%%', implode('%', explode(' ', $request->get('search_keyword')))));
        }
        if ($request->get('search_target') == 'author') {
            $query = $query->where('writer', 'like', sprintf('%%%s%%', $request->get('search_keyword')));
        }
        if ($request->get('search_target') == 'ip') {
            $query = $query->where('ipaddress', 'like', sprintf('%%%s%%', implode('%', explode(' ', $request->get('search_keyword')))));
        }

        return $query;
    }
}
