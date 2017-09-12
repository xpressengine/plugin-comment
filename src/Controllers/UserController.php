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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Request;
use XePresenter;
use Validator;
use Hash;
use Auth;
use XeDB;
use Counter;
use XeSkin;
use XeStorage;
use XeEditor;
use XeTag;
use Xpressengine\Editor\PurifierModules\EditorTool;
use Xpressengine\Permission\Instance;
use Xpressengine\Support\Exceptions\AccessDeniedHttpException;
use Xpressengine\Support\Purifier;
use Xpressengine\Plugins\Comment\Exceptions\BadRequestException;
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\Plugins\Comment\Exceptions\NotMatchCertifyKeyException;
use Xpressengine\Plugins\Comment\Exceptions\UnknownIdentifierException;
use Xpressengine\Plugins\Comment\Exceptions\InvalidArgumentException;
use XeDynamicField;
use Gate;
use Xpressengine\Support\PurifierModules\Html5;
use Xpressengine\User\Models\UnknownUser;

class UserController extends Controller
{
    /**
     * @var Handler
     */
    protected $handler;
    /**
     * @var \Xpressengine\Skin\AbstractSkin
     */
    protected $skin;

    public function __construct()
    {
        $plugin = app('xe.plugin.comment');
        $this->handler = $plugin->getHandler();
        $this->skin = XeSkin::getAssigned($plugin->getId());

        XePresenter::setSkinTargetId($plugin->getId());
    }

    public function index()
    {
        $targetId = Request::get('target_id');
        $instanceId = Request::get('instance_id');
        $targetAuthorId = Request::get('target_author_id');

        $offsetHead = !empty(Request::get('offsetHead')) ? Request::get('offsetHead') : null;
        $offsetReply = !empty(Request::get('offsetReply')) ? Request::get('offsetReply') : null;

        $config = $this->handler->getConfig($instanceId);

        $take = Request::get('perPage', $config['perPage']);

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
        $comments = $query->with('target.author')->get();
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

        $content = $this->skin->setView('items')->setData([
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

    protected function appendAssetsToParam(array $param)
    {
        return array_merge($param, [
            'XE_ASSET_LOAD' => [
                'css' => \Xpressengine\Presenter\Html\Tags\CSSFile::getFileList(),
                'js' => \Xpressengine\Presenter\Html\Tags\JSFile::getFileList(),
            ],
        ]);
    }

    public function store()
    {
        $instanceId = Request::get('instance_id');
        $inputs = Request::except(['_token']);

        // purifier 에 의해 몇몇 태그 속성이 사라짐
        // 정상적인 처리를 위해 원본 내용을 사용하도록 처리
        $purifier = new Purifier();
        $purifier->allowModule(EditorTool::class);
        $purifier->allowModule(HTML5::class);
        $originInput = Request::originAll();
        $inputs['content'] = $purifier->purify($originInput['content']);

        if (Gate::denies('create', new Instance($this->handler->getKeyForPerm($instanceId)))) {
            throw new AccessDeniedHttpException;
        }

        $rules = [
            'target_id' => 'Required',
            'content' => 'Required|Min:1',
        ];

        if (Auth::guest()) {
            $rules = array_merge($rules, [
                'email' => 'Required|Between:3,64|Email',
                'writer' => 'Required|Between:3,32',
                'certify_key' => 'Required|AlphaNum|Between:4,8',
            ]);
        }

        $validator = Validator::make($inputs, $rules);

        if ($validator->fails()) {
            $e = new InvalidArgumentException;
            $e->setMessage($validator->errors()->first());

            throw $e;
        }

        if (isset($inputs['certify_key']) === true) {
            $inputs['certify_key'] = Hash::make($inputs['certify_key']);
        }

        /** @var \Xpressengine\Editor\AbstractEditor $editor */
        $editor = XeEditor::get($instanceId);
        $inputs['format'] = $editor->htmlable() ? Comment::FORMAT_HTML : Comment::FORMAT_NONE;

        /** @var Comment $comment */
        $comment = $this->handler->create($inputs);

        // file 처리
        XeStorage::sync($comment->getKey(), array_get($inputs, $editor->getFileInputName(), []));
        // tag 처리
        XeTag::set($comment->getKey(), array_get($inputs, $editor->getTagInputName(), []), $instanceId);

        $config = $this->handler->getConfig($instanceId);
        $fieldTypes = XeDynamicField::gets(sprintf('documents_%s', $instanceId));

        $content = $this->skin->setView('items')->setData([
            'items' => [$comment],
            'config' => $config,
            'instance' => new Instance($this->handler->getKeyForPerm($instanceId)),
            'fieldTypes' => $fieldTypes,
        ])->render();

        return XePresenter::makeApi($this->appendAssetsToParam([
            'items' => $content,
        ]));
    }

    public function update()
    {
        $instanceId = Request::get('instance_id');
        $config = $this->handler->getConfig($instanceId);
        $id = Request::get('id');
        $inputs = Request::except(['instance_id', 'id', '_token']);

        // purifier 에 의해 몇몇 태그 속성이 사라짐
        // 정상적인 처리를 위해 원본 내용을 사용하도록 처리
        $purifier = new Purifier();
        $purifier->allowModule(EditorTool::class);
        $purifier->allowModule(HTML5::class);
        $originInput = Request::originAll();
        $inputs['content'] = $purifier->purify($originInput['content']);

        $rules = [
            'target_id' => 'Required',
            'content' => 'Required|Min:4',
        ];

        $model = $this->handler->createModel($instanceId);
        /** @var Comment $comment */
        if (!$comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        if (Auth::guest()) {
            $rules = array_merge($rules, [
                'email' => 'Between:3,64|Email',
                'writer' => 'Required|Between:3,32',
                'certify_key' => 'AlphaNum|Between:4,8',
            ]);
        }

        $validator = Validator::make($inputs, $rules);

        if ($validator->fails()) {
            $e = new InvalidArgumentException;
            $e->setMessage($validator->errors()->first());

            throw $e;
        }

        if (Gate::denies('update', $comment)) {
            throw new AccessDeniedHttpException;
        }

        if (isset($inputs['certify_key'])) {
            $inputs['certify_key'] = Hash::make($inputs['certify_key']);
        }

        /** @var \Xpressengine\Editor\AbstractEditor $editor */
        $editor = XeEditor::get($instanceId);
        $inputs['format'] = $editor->htmlable() ? Comment::FORMAT_HTML : Comment::FORMAT_NONE;

        $comment->fill(array_filter($inputs));

        $comment = $this->handler->put($comment);
        $this->handler->bindUserVote($comment);

        // file 처리
        XeStorage::sync($comment->getKey(), array_get($inputs, $editor->getFileInputName(), []));
        // tag 처리
        XeTag::set($comment->getKey(), array_get($inputs, $editor->getTagInputName(), []), $instanceId);

        $fieldTypes = XeDynamicField::gets(sprintf('documents_%s', $instanceId));

        $content = $this->skin->setView('items')->setData([
            'items' => [$comment],
            'config' => $config,
            'instance' => new Instance($this->handler->getKeyForPerm($instanceId)),
            'fieldTypes' => $fieldTypes,
        ])->render();

        return XePresenter::makeApi($this->appendAssetsToParam([
            'items' => $content,
        ]));
    }

    public function destroy()
    {
        $instanceId = Request::get('instance_id');
        $id = Request::get('id');

        $model = $this->handler->createModel($instanceId);
        if (!$comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        if (Gate::denies('delete', $comment)) {
            if (Gate::allows('delete-visible', $comment)) {
                return $this->getCertifyForm('destroy', $comment);
            }

            throw new AccessDeniedHttpException;
        }

        if (!$comment = $this->handler->trash($comment)) {
            return XePresenter::makeApi(['success' => false]);
        }

        if ($comment->display == Comment::DISPLAY_VISIBLE) {
            $content = $this->skin->setView('items')->setData([
                'items' => [$comment],
                'config' => $this->handler->getConfig($instanceId),
                'instance' => new Instance($this->handler->getKeyForPerm($instanceId)),
                'fieldTypes' => [],
            ])->render();

            $data = ['items' => $content];
        } else {
            if ($comment->getAuthor()->getId() === Auth::id()) {
                $this->handler->remove($comment);
            }

            $data = [];
        }

        return XePresenter::makeApi(array_merge($data, ['success' => true]));
    }

    public function voteOn()
    {
        $instanceId = Request::get('instance_id');
        $id = Request::get('id');
        $option = Request::get('option');

        if (Auth::guest() !== true) {
            XeDB::beginTransaction();

            try {
                $model = $this->handler->createModel($instanceId);
                $comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first();
                $comment = $this->handler->addVote($comment, $option);
            } catch (\Exception $e) {
                XeDB::rollBack();
                throw $e;
            }

            XeDB::commit();

            $data = [
                'assent' => $comment->assent_count,
                'dissent' => $comment->dissent_count,
            ];
        } else {
            $data = [];
        }

        return XePresenter::makeApi($data);
    }

    public function voteOff()
    {
        $instanceId = Request::get('instance_id');
        $id = Request::get('id');
        $option = Request::get('option');

        if (Auth::guest() !== true) {
            XeDB::beginTransaction();

            try {
                $model = $this->handler->createModel($instanceId);
                $comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first();
                $comment = $this->handler->removeVote($comment, $option);
            } catch (\Exception $e) {
                XeDB::rollBack();
                throw $e;
            }

            XeDB::commit();

            $data = [
                'assent' => $comment->assent_count,
                'dissent' => $comment->dissent_count,
            ];
        } else {
            $data = [];
        }

        return XePresenter::makeApi($data);
    }

    public function votedUser()
    {
        $instanceId = Request::get('instance_id');
        $id = Request::get('id');
        $option = Request::get('option');

        $model = $this->handler->createModel();
        $comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first();
        $users = $this->handler->voteUsers($comment, $option);

        $users = new LengthAwarePaginator($users, count($users), 10);

        return apiRender('voted', [
            'users' => $users,
            'data' => [
                'instanceId' => $instanceId,
                'id' => $id,
                'option' => $option,
            ]
        ]);
    }
    
    public function votedModal()
    {
        $instanceId = Request::get('instance_id');
        $id = Request::get('id');
        $option = Request::get('option');

        $model = $this->handler->createModel($instanceId);
        $comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first();
        $count = $this->handler->voteUserCount($comment, $option);

        return apiRender('votedModal', [
            'count' => $count,
            'data' => [
                'instanceId' => $instanceId,
                'id' => $id,
                'option' => $option,
            ]
        ]);
    }
    
    public function votedList()
    {
        $instanceId = Request::get('instance_id');
        $id = Request::get('id');
        $option = Request::get('option');
        $startId = Request::get('startId');
        $limit = Request::get('limit', 10);

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

    public function form()
    {
        $mode = Request::get('mode');

        $method = 'get' . ucfirst($mode) . 'Form';

        return $this->$method();
    }

    protected function getCreateForm()
    {
        $targetId = Request::get('target_id');
        $instanceId = Request::get('instance_id');
        $targetAuthorId = Request::get('target_author_id');

        if (Gate::allows('create', new Instance($this->handler->getKeyForPerm($instanceId)))) {
            $config = $this->handler->getConfig($instanceId);

            $fieldTypes = XeDynamicField::gets(sprintf('documents_%s', $instanceId));

            $content = $this->skin->setView('create')->setData([
                'targetId' => $targetId,
                'instanceId' => $instanceId,
                'targetAuthorId' => $targetAuthorId,
                'config' => $config,
                'fieldTypes' => $fieldTypes,
            ])->render();

            $data = ['mode' => 'create', 'html' => $content];
        } else {
            $data = ['mode' => 'create'];
        }

        return XePresenter::makeApi($data);
    }

    protected function getEditForm()
    {
        $targetId = Request::get('target_id');
        $instanceId = Request::get('instance_id');
        $id = Request::get('id');

        $model = $this->handler->createModel($instanceId);
        if (!$comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        if (Gate::denies('update', $comment)) {
            if (Gate::allows('update-visible', $comment)) {
                return $this->getCertifyForm('edit', $comment);
            }

            throw new AccessDeniedHttpException;
        }

        $config = $this->handler->getConfig($comment->instanceId);

        $fieldTypes = XeDynamicField::gets(sprintf('documents_%s', $instanceId));

        $content = $this->skin->setView('edit')->setData([
            'targetId' => $targetId,
            'instanceId' => $instanceId,
            'config' => $config,
            'comment' => $comment,
            'fieldTypes' => $fieldTypes,
        ])->render();

        return XePresenter::makeApi(['mode' => 'edit', 'html' => $content, 'etc' => ['files' => \XeEditor::getFiles($comment->getKey())]]);
    }

    protected function getReplyForm()
    {
        $id = Request::get('id');
        $instanceId = Request::get('instance_id');

        if (Gate::denies('create', new Instance($this->handler->getKeyForPerm($instanceId)))) {
            throw new AccessDeniedHttpException;
        }

        $model = $this->handler->createModel($instanceId);
        if (!$comment = $model->newQuery()->where('instance_id', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        $config = $this->handler->getConfig($comment->instanceId);

        $fieldTypes = XeDynamicField::gets(sprintf('documents_%s', $instanceId));

        $content = $this->skin->setView('reply')->setData([
            'config' => $config,
            'comment' => $comment,
            'fieldTypes' => $fieldTypes,
        ])->render();

        return XePresenter::makeApi(['mode' => 'reply', 'html' => $content]);
    }

    protected function getCertifyForm($mode, $comment)
    {
        $content = $this->skin->setView('certify')->setData([
            'mode' => $mode,
            'comment' => $comment
        ])->render();

        return XePresenter::makeApi(['mode' => 'certify', 'html' => $content]);
    }

    public function certify()
    {
        $inputs = Request::except('_token');

        $rules = [
            'id' => 'Required',
            'instanceId' => 'Required',
            'email' => 'Required|Between:3,64|Email',
            'certify_key' => 'Required|AlphaNum|Between:4,8',
        ];

        $validator = Validator::make($inputs, $rules);

        if ($validator->fails()) {
            // todo: validation lang 과 translation lang 호환 처리
            $e = new InvalidArgumentException;
            $e->setMessage($validator->errors()->first());

            throw $e;
        }

        $model = $this->handler->createModel($inputs['instance_id']);
        if (!$comment = $model->newQuery()->where('instance_id', $inputs['instanceId'])->where('id', $inputs['id'])->first()) {
            throw new UnknownIdentifierException;
        }

        if (
            $inputs['email'] !== $comment->email
            || Hash::check($inputs['certify_key'], $comment->certifyKey) === false
        ) {
            throw new NotMatchCertifyKeyException;
        }

        $this->handler->certified($comment);

        if (Request::get('mode') == 'edit') {
            return $this->getEditForm();
        } elseif (Request::get('mode') == 'destroy') {
            return $this->destroy();
        }

        throw new BadRequestException;
    }
}
