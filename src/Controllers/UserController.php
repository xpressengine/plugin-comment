<?php
/**
 * UserController.php
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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use XePresenter;
use Hash;
use XeDB;
use XeSkin;
use XeStorage;
use XeEditor;
use XeTag;
use Xpressengine\Editor\PurifierModules\EditorContent;
use Xpressengine\Http\Request;
use Xpressengine\Permission\Instance;
use Xpressengine\Plugins\Comment\CommentUsable;
use Xpressengine\Plugins\Comment\Exceptions\InvalidArgumentException;
use Xpressengine\Plugins\Comment\Handler;
use Xpressengine\Plugins\Comment\Plugin;
use Xpressengine\Support\Purifier;
use Xpressengine\Plugins\Comment\Exceptions\BadRequestException;
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\Plugins\Comment\Exceptions\NotMatchCertifyKeyException;
use Xpressengine\Plugins\Comment\Exceptions\UnknownIdentifierException;
use XeDynamicField;
use Xpressengine\Support\PurifierModules\Html5;
use Xpressengine\User\Models\UnknownUser;

/**
 * UserController
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class UserController extends Controller
{
    /**
     * @var Handler
     */
    protected $handler;

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        $plugin = app('xe.plugin.comment');
        $this->handler = $plugin->getHandler();
    }

    /**
     * index
     *
     * @param Request $request request
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function index(Request $request)
    {
        $targetId = $request->get('target_id');
        $instanceId = $request->get('instance_id');

        $offsetHead = !empty($request->get('offsetHead')) ? $request->get('offsetHead') : null;
        $offsetReply = !empty($request->get('offsetReply')) ? $request->get('offsetReply') : null;

        $config = $this->handler->getConfig($instanceId);

        $take = $request->get('perPage', $config['perPage']);

        $model = $this->handler->createModel($instanceId);
        $query = $model->newQuery()->whereHas('target', function ($query) use ($targetId) {
            $query->where('target_id', $targetId);
        })
//            ->where('approved', Comment::APPROVED_APPROVED)
            ->where('display', '!=', Comment::DISPLAY_HIDDEN);

        // 댓글 총 수
        $totalCount = $query->count();

        $direction = $config->get('reverse') === true ? 'asc' : 'desc';

        if ($offsetHead !== null) {
            $query->where(function ($query) use ($offsetHead, $offsetReply, $direction) {
                $query->where('head', $offsetHead);
                $operator = $direction == 'desc' ? '<' : '>';
                $offsetReply = $offsetReply ?: '';

                $query->where('reply', $operator, $offsetReply);
                $query->orWhere('head', '<', $offsetHead);
            });
        }
        $query->orderBy('head', 'desc')->orderBy('reply', $direction)->take($take + 1);
        // 대상글의 작성자까지 eager load 로 조회하여야 되나
        // 대상글 작성자를 조회하는 relation 명을 지정할 수 없음.
        $comments = $query->with('target.commentable')->get();
        foreach ($comments as $comment) {
            $this->handler->bindUserVote($comment);
        }
        $comments = new Paginator($comments, $take);

        // generator 로 반환 되어 목록에서 재사용이 불가능
        $fieldTypesGenerator = XeDynamicField::gets(sprintf('documents_%s', $instanceId));
        $fieldTypes = [];
        foreach ($fieldTypesGenerator as $fieldType) {
            $fieldTypes[] = $fieldType;
        }

        $instance = new Instance($this->handler->getKeyForPerm($instanceId));

        $skin = XeSkin::getAssigned([Plugin::getId(), $instanceId]);
        $content = $skin->setView('items')->setData([
            'items' => $comments,
            'config' => $config,
            'instance' => $instance,
            'fieldTypes' => $fieldTypes,
        ])->render();

        return XePresenter::makeApi($this->appendAssetsToParam([
            'totalCount' => $totalCount,
            'hasMore' => $comments->hasMorePages(),
            'items' => $content,
        ]));
    }

    /**
     * append assets to param
     *
     * @param array $param parameters
     *
     * @return array
     */
    protected function appendAssetsToParam(array $param)
    {
        return array_merge($param, [
            'XE_ASSET_LOAD' => [
                'css' => \Xpressengine\Presenter\Html\Tags\CSSFile::getFileList(),
                'js' => \Xpressengine\Presenter\Html\Tags\JSFile::getFileList(),
            ],
        ]);
    }

    /**
     * store
     *
     * @param Request $request request
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     * @throws AuthorizationException
     */
    public function store(Request $request)
    {
        // purifier 에 의해 몇몇 태그 속성이 사라짐
        // 정상적인 처리를 위해 원본 내용을 사용하도록 처리
        $purifier = new Purifier();
        $purifier->allowModule(EditorContent::class);
        $purifier->allowModule(HTML5::class);
        $request['content'] = $purifier->purify($request->originInput('content'));

        $rules = [
            'instance_id' => 'Required',
            'target_id' => 'Required',
            'target_type' => 'Required',
            'content' => 'Required|Min:1',
        ];

        if (auth()->guest()) {
            $rules = array_merge($rules, [
                'email' => 'Required|Between:3,64|Email',
                'writer' => 'Required|Between:3,32',
                'certify_key' => 'Required|AlphaNum|Between:4,8',
            ]);
        }

        $this->validate($request, $rules);
        $inputs = $request->except('_token');

        $this->authorize('create', new Instance($this->handler->getKeyForPerm($inputs['instance_id'])));

        if (isset($inputs['certify_key'])) {
            $inputs['certify_key'] = bcrypt($inputs['certify_key']);
        }

        /** @var \Xpressengine\Editor\AbstractEditor $editor */
        $editor = XeEditor::get($inputs['instance_id']);
        $inputs['format'] = $editor->htmlable() ? Comment::FORMAT_HTML : Comment::FORMAT_NONE;
        $inputs['display'] = !!array_get($inputs, 'display') ? Comment::DISPLAY_SECRET : Comment::DISPLAY_VISIBLE;

        /**
         * @deprecated since 0.9.18. no use 'target_author_id' field
         */
        $targetClass = Relation::getMorphedModel($inputs['target_type']) ?: $inputs['target_type'];
        /** @var CommentUsable $targetModel */
        if (!$targetModel = call_user_func([$targetClass, 'find'], $inputs['target_id'])) {
            $e = new InvalidArgumentException;
            $e->setMessage(xe_trans('comment::unknownTargetObject'));
            throw $e;
        }
        // 댓글이 허용되지않은 게시물일 경우 잘못된 요청 에러 처리
        if(!$targetModel->boardData->allow_comment){
            abort(500, xe_trans('comment::notAllowedComment'));
        }
        $inputs['target_author_id'] = $targetModel->getAuthor()->getId();


        /** @var Comment $comment */
        $comment = $this->handler->create($inputs);

        // file 처리
        XeStorage::sync($comment->getKey(), array_get($inputs, $editor->getFileInputName(), []));
        // tag 처리
        XeTag::set($comment->getKey(), array_get($inputs, $editor->getTagInputName(), []), $inputs['instance_id']);

        $config = $this->handler->getConfig($inputs['instance_id']);
        $fieldTypes = XeDynamicField::gets(sprintf('documents_%s', $inputs['instance_id']));

        $skin = XeSkin::getAssigned([Plugin::getId(), $inputs['instance_id']]);
        $content = $skin->setView('items')->setData([
            'items' => [$comment],
            'config' => $config,
            'instance' => new Instance($this->handler->getKeyForPerm($inputs['instance_id'])),
            'fieldTypes' => $fieldTypes,
        ])->render();

        return XePresenter::makeApi($this->appendAssetsToParam([
            'items' => $content,
        ]));
    }

    /**
     * update
     *
     * @param Request $request request
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     * @throws AuthorizationException
     */
    public function update(Request $request)
    {
        // purifier 에 의해 몇몇 태그 속성이 사라짐
        // 정상적인 처리를 위해 원본 내용을 사용하도록 처리
        $purifier = new Purifier();
        $purifier->allowModule(EditorContent::class);
        $purifier->allowModule(HTML5::class);
        $request['content'] = $purifier->purify($request->originInput('content'));

        $rules = [
            'id' => 'Required',
            'instance_id' => 'Required',
            'content' => 'Required|Min:4',
        ];

        if (auth()->guest()) {
            $rules = array_merge($rules, [
                'email' => 'Between:3,64|Email',
                'writer' => 'Required|Between:3,32',
                'certify_key' => 'AlphaNum|Between:4,8',
            ]);
        }

        $this->validate($request, $rules);

        $instanceId = $request->get('instance_id');
        $id = $request->get('id');
        $inputs = $request->except(['instance_id', 'id', '_token']);

        $model = $this->handler->createModel($instanceId);
        /** @var Comment $comment */
        if (!$comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        $this->authorize('update', $comment);

        if (isset($inputs['certify_key'])) {
            $inputs['certify_key'] = bcrypt($inputs['certify_key']);
        }

        /** @var \Xpressengine\Editor\AbstractEditor $editor */
        $editor = XeEditor::get($instanceId);
        $inputs['format'] = $editor->htmlable() ? Comment::FORMAT_HTML : Comment::FORMAT_NONE;
        if ($comment->display !== Comment::DISPLAY_HIDDEN) {
            $inputs['display'] = !!array_get($inputs, 'display') ?
                Comment::DISPLAY_SECRET : Comment::DISPLAY_VISIBLE;
        } else {
            if (isset($inputs['display'])) {
                unset($inputs['display']);
            }
        }

        $comment->fill(array_filter($inputs));

        $comment = $this->handler->put($comment);
        $this->handler->bindUserVote($comment);

        // file 처리
        XeStorage::sync($comment->getKey(), array_get($inputs, $editor->getFileInputName(), []));
        // tag 처리
        XeTag::set($comment->getKey(), array_get($inputs, $editor->getTagInputName(), []), $instanceId);

        $fieldTypes = XeDynamicField::gets(sprintf('documents_%s', $instanceId));

        $skin = XeSkin::getAssigned([Plugin::getId(), $instanceId]);
        $content = $skin->setView('items')->setData([
            'items' => [$comment],
            'config' => $this->handler->getConfig($instanceId),
            'instance' => new Instance($this->handler->getKeyForPerm($instanceId)),
            'fieldTypes' => $fieldTypes,
        ])->render();

        return XePresenter::makeApi($this->appendAssetsToParam([
            'items' => $content,
        ]));
    }

    /**
     * destroy
     *
     * @param Request $request request
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     * @throws AuthorizationException
     */
    public function destroy(Request $request)
    {
        $instanceId = $request->get('instance_id');
        $id = $request->get('id');

        $model = $this->handler->createModel($instanceId);
        if (!$comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        try {
            $this->authorize('delete', $comment);
        } catch (AuthorizationException $e) {
            $this->authorize('delete-visible', $comment);

            return $this->getCertifyForm('destroy', $comment);
        }

        if (!$comment = $this->handler->trash($comment)) {
            return XePresenter::makeApi(['success' => false]);
        }

        if ($comment->display == Comment::DISPLAY_VISIBLE) {
            $skin = XeSkin::getAssigned([Plugin::getId(), $instanceId]);
            $content = $skin->setView('items')->setData([
                'items' => [$comment],
                'config' => $this->handler->getConfig($instanceId),
                'instance' => new Instance($this->handler->getKeyForPerm($instanceId)),
                'fieldTypes' => [],
            ])->render();

            $data = ['items' => $content];
        } else {
            if ($comment->getAuthor()->getId() === auth()->id()) {
                $this->handler->remove($comment);
            }

            $data = [];
        }

        return XePresenter::makeApi(array_merge($data, ['success' => true]));
    }

    /**
     * vote on
     *
     * @param Request $request request
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     * @throws \Exception
     */
    public function voteOn(Request $request)
    {
        $instanceId = $request->get('instance_id');
        $id = $request->get('id');
        $option = $request->get('option');

        if (auth()->guest() !== true) {
            XeDB::beginTransaction();

            try {
                $model = $this->handler->createModel($instanceId);
                $comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first();
                $result = $this->handler->addVote($comment, $option);
            } catch (\Exception $e) {
                XeDB::rollBack();
                throw $e;
            }

            XeDB::commit();

            $data = [
                'result' => $result,
                'assent' => $comment->assent_count,
                'dissent' => $comment->dissent_count,
            ];
        } else {
            $data = ['result' => false];
        }

        return XePresenter::makeApi($data);
    }

    /**
     * vote off
     *
     * @param Request $request request
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     * @throws \Exception
     */
    public function voteOff(Request $request)
    {
        $instanceId = $request->get('instance_id');
        $id = $request->get('id');
        $option = $request->get('option');

        if (auth()->guest() !== true) {
            XeDB::beginTransaction();

            try {
                $model = $this->handler->createModel($instanceId);
                $comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first();
                $result = $this->handler->removeVote($comment, $option);
            } catch (\Exception $e) {
                XeDB::rollBack();
                throw $e;
            }

            XeDB::commit();

            $data = [
                'result' => $result,
                'assent' => $comment->assent_count,
                'dissent' => $comment->dissent_count,
            ];
        } else {
            $data = ['result' => false];
        }

        return XePresenter::makeApi($data);
    }

    /**
     * get voted user list
     *
     * @param Request $request request
     *
     * @return mixed
     */
    public function votedUser(Request $request)
    {
        $instanceId = $request->get('instance_id');
        $id = $request->get('id');
        $option = $request->get('option');

        $model = $this->handler->createModel();
        $comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first();
        $users = $this->handler->voteUsers($comment, $option);

        $users = new LengthAwarePaginator($users, count($users), 10);

        return api_render(Plugin::getId() . '::views.skin.user.default.voted', [
            'users' => $users,
            'data' => [
                'instanceId' => $instanceId,
                'id' => $id,
                'option' => $option,
            ]
        ]);
    }

    /**
     * voted modal
     *
     * @param Request $request request
     *
     * @return mixed
     */
    public function votedModal(Request $request)
    {
        $instanceId = $request->get('instance_id');
        $id = $request->get('id');
        $option = $request->get('option');

        $model = $this->handler->createModel($instanceId);
        $comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first();
        $count = $this->handler->voteUserCount($comment, $option);

        return api_render(Plugin::getId() . '::views.skin.user.default.votedModal', [
            'count' => $count,
            'data' => [
                'instanceId' => $instanceId,
                'id' => $id,
                'option' => $option,
            ]
        ]);
    }

    /**
     * voted list
     *
     * @param Request $request request
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function votedList(Request $request)
    {
        $instanceId = $request->get('instance_id');
        $id = $request->get('id');
        $option = $request->get('option');
        $startId = $request->get('startId');
        $limit = $request->get('limit', 10);

        $model = $this->handler->createModel($instanceId);
        $comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first();
        $logs = $this->handler->votedList($comment, $option, $startId, $limit);

        $list = [];
        foreach ($logs as $log) {
            if (!$user = $log->user) {
                $user = new UnknownUser();
            }

            $profilePage = route('member.profile', ['member' => $user->getId()]);
            $list[] = [
                'id' => $user->getId(),
                'displayName' => $user->getDisplayName(),
                'profileImage' => $user->getProfileImage(),
                'createdAt' => (string)$log->created_at,
                'profilePage' => $profilePage,
            ];
        }

        $nextStartId = 0;
        if (count($logs) == $limit) {
            $nextStartId = $logs->last()->id;
        }

        return XePresenter::makeApi([
            'list' => $list,
            'nextStartId' => $nextStartId,
        ]);
    }

    /**
     * form
     *
     * @param Request $request request
     *
     * @return mixed
     */
    public function form(Request $request)
    {
        $mode = $request->get('mode');

        $method = 'get' . ucfirst($mode) . 'Form';

        return $this->$method($request);
    }

    /**
     * get create form
     *
     * @param Request $request request
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    protected function getCreateForm(Request $request)
    {
        $targetId = $request->get('target_id');
        $instanceId = $request->get('instance_id');
        $targetType = $request->get('target_type');

        try {
            $this->authorize('create', new Instance($this->handler->getKeyForPerm($instanceId)));

            $config = $this->handler->getConfig($instanceId);

            $fieldTypes = XeDynamicField::gets(sprintf('documents_%s', $instanceId));

            $skin = XeSkin::getAssigned([Plugin::getId(), $instanceId]);
            $content = $skin->setView('create')->setData([
                'targetId' => $targetId,
                'instanceId' => $instanceId,
                'targetType' => $targetType,
                'config' => $config,
                'fieldTypes' => $fieldTypes,
            ])->render();

            $data = ['mode' => 'create', 'html' => $content];
        } catch (AuthorizationException $e) {
            $data = ['mode' => 'create'];
        }

        return XePresenter::makeApi($data);
    }

    /**
     * get edit form
     *
     * @param Request $request request
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     * @throws AuthorizationException
     */
    protected function getEditForm(Request $request)
    {
        $instanceId = $request->get('instance_id');
        $id = $request->get('id');

        $model = $this->handler->createModel($instanceId);
        if (!$comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        try {
            $this->authorize('update', $comment);
        } catch (AuthorizationException $e) {
            $this->authorize('update-visible', $comment);

            return $this->getCertifyForm('edit', $comment);
        }

        $config = $this->handler->getConfig($comment->instance_id);

        $fieldTypes = XeDynamicField::gets(sprintf('documents_%s', $instanceId));

        $skin = XeSkin::getAssigned([Plugin::getId(), $instanceId]);
        $content = $skin->setView('edit')->setData([
            'instanceId' => $instanceId,
            'config' => $config,
            'comment' => $comment,
            'fieldTypes' => $fieldTypes,
        ])->render();

        return XePresenter::makeApi(
            ['mode' => 'edit', 'html' => $content, 'etc' => ['files' => \XeEditor::getFiles($comment->getKey())]]
        );
    }

    /**
     * get reply form
     *
     * @param Request $request request
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     * @throws AuthorizationException
     */
    protected function getReplyForm(Request $request)
    {
        $id = $request->get('id');
        $instanceId = $request->get('instance_id');
        $targetType = $request->get('target_type');

        $this->authorize('create', new Instance($this->handler->getKeyForPerm($instanceId)));

        $model = $this->handler->createModel($instanceId);
        if (!$comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        $config = $this->handler->getConfig($comment->instanceId);

        $fieldTypes = XeDynamicField::gets(sprintf('documents_%s', $instanceId));

        $skin = XeSkin::getAssigned([Plugin::getId(), $instanceId]);
        $content = $skin->setView('reply')->setData([
            'config' => $config,
            'comment' => $comment,
            'targetType' => $targetType,
            'fieldTypes' => $fieldTypes,
        ])->render();

        return XePresenter::makeApi(['mode' => 'reply', 'html' => $content]);
    }

    /**
     * get certify form
     *
     * @param string  $mode    mode
     * @param Comment $comment comment model
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    protected function getCertifyForm($mode, $comment)
    {
        $skin = XeSkin::getAssigned([Plugin::getId(), $comment->instanceId]);
        $content = $skin->setView('certify')->setData([
            'mode' => $mode,
            'comment' => $comment
        ])->render();

        return XePresenter::makeApi(['mode' => 'certify', 'html' => $content]);
    }

    /**
     * certify
     *
     * @param Request $request request
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     * @throws AuthorizationException
     */
    public function certify(Request $request)
    {
        $rules = [
            'id' => 'Required',
            'instance_id' => 'Required',
            'email' => 'Required|Between:3,64|Email',
            'certify_key' => 'Required|AlphaNum|Between:4,8',
        ];

        $inputs = $this->validate($request, $rules);

        $model = $this->handler->createModel($inputs['instance_id']);
        if (!$comment = $model->newQuery()->where('instance_id', $inputs['instance_id'])
            ->where('id', $inputs['id'])->first()
        ) {
            throw new UnknownIdentifierException;
        }

        if ($inputs['email'] !== $comment->email
            || Hash::check($inputs['certify_key'], $comment->certifyKey) === false
        ) {
            throw new NotMatchCertifyKeyException;
        }

        $this->handler->certified($comment);

        if ($request->get('mode') == 'edit') {
            return $this->getEditForm($request);
        } elseif ($request->get('mode') == 'destroy') {
            return $this->destroy($request);
        }

        throw new BadRequestException;
    }
}
