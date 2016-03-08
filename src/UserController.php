<?php
namespace Xpressengine\Plugins\Comment;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\Paginator;
use Input;
use Presenter;
use Validator;
use Hash;
use Auth;
use XeDB;
use Counter;
use Skin;
use XeStorage;
use Xpressengine\Media\Models\Image;
use Xpressengine\Permission\Instance;
use Xpressengine\Plugins\Comment\Exceptions\BadRequestException;
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\Storage\File;
use Xpressengine\Support\Exceptions\AccessDeniedHttpException;
use Xpressengine\Plugins\Comment\Exceptions\NotMatchCertifyKeyException;
use Xpressengine\Plugins\Comment\Exceptions\UnknownIdentifierException;
use Xpressengine\Plugins\Comment\Exceptions\InvalidArgumentException;
use DynamicField;
use Gate;
use Xpressengine\User\Models\User;

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
        $this->skin = Skin::getAssigned($plugin->getId());
    }

    public function index()
    {
        $targetId = Input::get('targetId');
        $instanceId = Input::get('instanceId');
        $targetAuthorId = Input::get('targetAuthorId');

        $offsetHead = !empty(Input::get('offsetHead')) ? Input::get('offsetHead') : null;
        $offsetReply = !empty(Input::get('offsetReply')) ? Input::get('offsetReply') : null;

        $config = $this->handler->getConfig($instanceId);

        $take = Input::get('perPage', $config['perPage']);

        $model = $this->handler->createModel();
        $query = $model->newQuery()->whereHas('target', function ($query) use ($targetId) {
            $query->where('targetId', $targetId);
        })->where('approved', 'approved')->where('display', '!=', 'hidden');

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
        $comments = $query->with('target.author', 'files')->get();
        foreach ($comments as $comment) {
            $this->handler->bindUserVote($comment);
        }
        $comments = new Paginator($comments, $take);

        // generator 로 반환 되어 목록에서 재사용이 불가능
        $fieldTypesGenerator = XeDynamicField::gets(str_replace('.', '_', $config->name));
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

        return Presenter::makeApi([
            'totalCount' => $totalCount,
            'hasMore' => $comments->hasMorePages(),
            'items' => $content,
        ]);
    }

    public function store()
    {
        $instanceId = Input::get('instanceId');

        $inputs = Input::except(['_token']);

        // purifier 에 의해 몇몇 태그 속성이 사라짐
        // 정상적인 처리를 위해 원본 내용을 사용하도록 처리
        $originInput = Input::originAll();
        $inputs['content'] = $originInput['content'];

        $fileIds = array_only($inputs, '_files');
        $inputs = array_except($inputs, ['_files']);

        if (Gate::denies('create', new Instance($this->handler->getKeyForPerm($instanceId)))) {
            throw new AccessDeniedHttpException;
        }

        $rules = [
            'targetId' => 'Required',
            'content' => 'Required|Min:1',
        ];

        if (Auth::guest()) {
            $rules = array_merge($rules, [
                'email' => 'Required|Between:3,64|Email',
                'writer' => 'Required|Between:3,32',
                'certifyKey' => 'Required|AlphaNum|Between:4,8',
            ]);
        }

        $validator = Validator::make($inputs, $rules);

        if ($validator->fails()) {
            // todo: validation lang 과 translation lang 호환 처리
            $e = new InvalidArgumentException;
            $e->setMessage($validator->errors()->first());

            throw $e;
        }

        if (isset($inputs['certifyKey']) === true) {
            $inputs['certifyKey'] = Hash::make($inputs['certifyKey']);
        }

        /** @var Comment $comment */
        $comment = $this->handler->create($inputs);
        $files = File::whereIn('id', $fileIds)->get();
        foreach ($files as $file) {
            XeStorage::bind($comment->getKey(), $file);
        }

        $config = $this->handler->getConfig($instanceId);

        $fieldTypes = XeDynamicField::gets(str_replace('.', '_', $config->name));

        $instance = new Instance($this->handler->getKeyForPerm($instanceId));

        $content = $this->skin->setView('items')->setData([
            'items' => [$comment],
            'config' => $config,
            'instance' => $instance,
            'fieldTypes' => $fieldTypes,
        ])->render();

        return Presenter::makeApi([
            'items' => $content,
        ]);
    }

    public function update()
    {
        $instanceId = Input::get('instanceId');
        $id = Input::get('id');
        $inputs = Input::except(['instanceId', 'id', '_token']);

        // purifier 에 의해 몇몇 태그 속성이 사라짐
        // 정상적인 처리를 위해 원본 내용을 사용하도록 처리
        $originInput = Input::originAll();
        $inputs['content'] = $originInput['content'];

        $rules = [
            'targetId' => 'Required',
            'content' => 'Required|Min:4',
        ];

        $fileIds = array_only($inputs, '_files');
        $inputs = array_except($inputs, ['_files']);

        $model = $this->handler->createModel();
        if (!$comment = $model->newQuery()->where('instanceId', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        if (Auth::guest()) {
            $rules = array_merge($rules, [
                'email' => 'Between:3,64|Email',
                'writer' => 'Required|Between:3,32',
                'certifyKey' => 'AlphaNum|Between:4,8',
            ]);
        }

        $validator = Validator::make($inputs, $rules);

        if ($validator->fails()) {
            // todo: validation lang 과 translation lang 호환 처리
            $e = new InvalidArgumentException;
            $e->setMessage($validator->errors()->first());

            throw $e;
        }

        if (Gate::denies('update', $comment)) {
            throw new AccessDeniedHttpException;
        }

        foreach ($inputs as $name => $value) {
            if (empty($value)) {
                continue;
            }

            if ($name == 'certifyKey') {
                $value = Hash::make($value);
            }

            $comment->{$name} = $value;
        }

        $comment = $this->handler->put($comment);
        $this->handler->bindUserVote($comment);

        $newFiles = File::whereIn('id', $fileIds)->get();
        $removes = $comment->files->diff($newFiles);
        foreach ($removes as $file) {
            XeStorage::unBind($comment->getKey(), $file, true);
        }
        foreach ($newFiles as $file) {
            try {
                XeStorage::bind($comment->getKey(), $file);
            } catch (QueryException $e) {
                if ($e->getCode() != "23000") {
                    throw $e;
                }
            }
        }
        unset($comment->files);

        $config = $this->handler->getConfig($instanceId);

        $fieldTypes = XeDynamicField::gets(str_replace('.', '_', $config->name));

        $instance = new Instance($this->handler->getKeyForPerm($instanceId));

        $content = $this->skin->setView('items')->setData([
            'items' => [$comment],
            'config' => $config,
            'instance' => $instance,
            'fieldTypes' => $fieldTypes,
        ])->render();

        return Presenter::makeApi([
            'items' => $content,
        ]);
    }

    public function destroy()
    {
        $instanceId = Input::get('instanceId');
        $id = Input::get('id');

        $model = $this->handler->createModel();
        if (!$comment = $model->newQuery()->where('instanceId', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        if (Gate::denies('delete', $comment)) {
            if (Gate::allows('delete-visible', $comment)) {
                return $this->getCertifyForm('destroy', $comment);
            }

            throw new AccessDeniedHttpException;
        }

        $this->handler->trash($comment);

        $config = $this->handler->getConfig($instanceId);
        if ($config->get('removeType') == 'blind') {
            $items[] = $this->handler->get($instanceId, $id);

            $instance = new Instance($this->handler->getKeyForPerm($instanceId));

            $content = $this->skin->setView('items')->setData([
                'instanceId' => $instanceId,
                'items' => $items,
                'config' => $config,
                'instance' => $instance,
            ])->render();

            $data = ['items' => $content];
        } else {
            $data = [];
        }

        return Presenter::makeApi($data);
    }

    public function voteOn()
    {
        $instanceId = Input::get('instanceId');
        $id = Input::get('id');
        $option = Input::get('option');

        if (Auth::guest() !== true) {
            XeDB::beginTransaction();

            try {
                $model = $this->handler->createModel();
                $comment = $model->newQuery()->where('instanceId', $instanceId)->where('id', $id)->first();
                $comment = $this->handler->addVote($comment, $option);
            } catch (\Exception $e) {
                XeDB::rollBack();
                throw $e;
            }

            XeDB::commit();

            $data = [
                'assent' => $comment->assentCount,
                'dissent' => $comment->dissentCount,
            ];
        } else {
            $data = [];
        }

        return Presenter::makeApi($data);
    }

    public function voteOff()
    {
        $instanceId = Input::get('instanceId');
        $id = Input::get('id');
        $option = Input::get('option');

        if (Auth::guest() !== true) {
            XeDB::beginTransaction();

            try {
                $model = $this->handler->createModel();
                $comment = $model->newQuery()->where('instanceId', $instanceId)->where('id', $id)->first();
                $comment = $this->handler->removeVote($comment, $option);
            } catch (\Exception $e) {
                XeDB::rollBack();
                throw $e;
            }

            XeDB::commit();

            $data = [
                'assent' => $comment->assentCount,
                'dissent' => $comment->dissentCount,
            ];
        } else {
            $data = [];
        }

        return Presenter::makeApi($data);
    }

    public function voteUser()
    {
        $instanceId = Input::get('instanceId');
        $id = Input::get('id');
        $option = Input::get('option');

        $model = $this->handler->createModel();
        $comment = $model->newQuery()->where('instanceId', $instanceId)->where('id', $id)->first();
        $users = $this->handler->voteUsers($comment, $option);

        $content = $this->skin->setView('vote')->setData(['users' => $users])->render();

        return Presenter::makeApi(['items' => $content]);
    }

    public function form()
    {
        $mode = Input::get('mode');

        $method = 'get' . ucfirst($mode) . 'Form';

        return $this->$method();
    }

    protected function getCreateForm()
    {
        $targetId = Input::get('targetId');
        $instanceId = Input::get('instanceId');
        $targetAuthorId = Input::get('targetAuthorId');

        if (Gate::allows('create', new Instance($this->handler->getKeyForPerm($instanceId)))) {
            $config = $this->handler->getConfig($instanceId);

            $fieldTypes = XeDynamicField::gets(str_replace('.', '_', $config->name));

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

        return Presenter::makeApi($data);
    }

    protected function getEditForm()
    {
        $targetId = Input::get('targetId');
        $instanceId = Input::get('instanceId');
        $id = Input::get('id');

        $model = $this->handler->createModel();
        if (!$comment = $model->newQuery()->where('instanceId', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        if (Gate::denies('update', $comment)) {
            if (Gate::allows('update-visible', $comment)) {
                return $this->getCertifyForm('edit', $comment);
            }

            throw new AccessDeniedHttpException;
        }

        $config = $this->handler->getConfig($comment->instanceId);

        $fieldTypes = XeDynamicField::gets(str_replace('.', '_', $config->name));

        $content = $this->skin->setView('edit')->setData([
            'targetId' => $targetId,
            'instanceId' => $instanceId,
            'config' => $config,
            'comment' => $comment,
            'fieldTypes' => $fieldTypes,
        ])->render();

        return Presenter::makeApi(['mode' => 'edit', 'html' => $content]);
    }

    protected function getReplyForm()
    {
        $id = Input::get('id');
        $instanceId = Input::get('instanceId');

        if (Gate::denies('create', new Instance($this->handler->getKeyForPerm($instanceId)))) {
            throw new AccessDeniedHttpException;
        }

        $model = $this->handler->createModel();
        if (!$comment = $model->newQuery()->where('instanceId', $instanceId)->where('id', $id)->first()) {
            throw new UnknownIdentifierException;
        }

        $config = $this->handler->getConfig($comment->instanceId);

        $fieldTypes = XeDynamicField::gets(str_replace('.', '_', $config->name));

        $content = $this->skin->setView('reply')->setData([
            'config' => $config,
            'comment' => $comment,
            'fieldTypes' => $fieldTypes,
        ])->render();

        return Presenter::makeApi(['mode' => 'reply', 'html' => $content]);
    }

    protected function getCertifyForm($mode, $comment)
    {
        $content = $this->skin->setView('certify')->setData([
            'mode' => $mode,
            'comment' => $comment
        ])->render();

        return Presenter::makeApi(['mode' => 'certify', 'html' => $content]);
    }

    public function certify()
    {
        $inputs = Input::except('_token');

        $rules = [
            'id' => 'Required',
            'instanceId' => 'Required',
            'email' => 'Required|Between:3,64|Email',
            'certifyKey' => 'Required|AlphaNum|Between:4,8',
        ];

        $validator = Validator::make($inputs, $rules);

        if ($validator->fails()) {
            // todo: validation lang 과 translation lang 호환 처리
            $e = new InvalidArgumentException;
            $e->setMessage($validator->errors()->first());

            throw $e;
        }

        $model = $this->handler->createModel();
        if (!$comment = $model->newQuery()->where('instanceId', $inputs['instanceId'])->where('id', $inputs['id'])->first()) {
            throw new UnknownIdentifierException;
        }

        if (
            $inputs['email'] !== $comment->email
            || Hash::check($inputs['certifyKey'], $comment->certifyKey) === false
        ) {
            throw new NotMatchCertifyKeyException;
        }

        $this->handler->certified($comment);

        if (Input::get('mode') == 'edit') {
            return $this->getEditForm();
        } elseif (Input::get('mode') == 'destroy') {
            return $this->destroy();
        }

        throw new BadRequestException;
    }

    public function fileUpload()
    {
        /** @var \Xpressengine\Storage\Storage $storage */
        $storage = app('xe.storage');

        $uploadedFile = null;
        if (Input::file('file') !== null) {
            $uploadedFile = Input::file('file');
        } elseif (Input::file('image') !== null) {
            $uploadedFile = Input::file('image');
        }

        if ($uploadedFile === null) {
            $e = new InvalidArgumentException;
            $e->setMessage(xe_trans('comment_service::Require', ['attribute' => 'file']));

            throw $e;
        }

        $file = $storage->upload($uploadedFile, 'attached/comment');

        /** @var \Xpressengine\Media\MediaManager $mediaManager */
        $mediaManager = app('xe.media');
        $thumbnails = null;
        $media = null;
        if ($mediaManager->is($file) === true) {
            $media = $mediaManager->make($file);
            $thumbnails = $mediaManager->createThumbnails($media, 'spill');
        }

        return Presenter::makeApi([
            'file' => $file,
            'media' => $media,
            'thumbnails' => $thumbnails,
        ]);
    }

    public function fileSource($id)
    {
        // todo: authorization 필요 여부 검토

        $file = File::find($id);

        /** @var \Xpressengine\Media\MediaManager $mediaManager */
        $mediaManager = app('xe.media');
        if ($mediaManager->is($file) === true) {
            $dimension = 'L';
            if (app('request')->isMobile() === true) {
                $dimension = 'M';
            }

            $image = Image::getThumbnail($mediaManager->make($file), 'spill', $dimension);

            header('Content-type: ' . $image->mime);

            echo $image->getContent();
        }
    }

    public function fileDownload($instanceId, $id)
    {
        if (Gate::denies('download', new Instance($this->handler->getKeyForPerm($instanceId)))) {
            throw new AccessDeniedHttpException;
        }

        /** @var \Xpressengine\Storage\Storage $storage */
        $storage = app('xe.storage');
        $file = File::find($id);

        $storage->download($file);
    }

    public function suggestionHashTag()
    {
        $string = Input::get('string');

        if (empty($string) === true) {
            return Presenter::makeApi([]);
        }

        /** @var \Xpressengine\Tag\TagHandler $tagHandler */
        $tagHandler = app('xe.tag');
        $tags = $tagHandler->similar($string);

        $suggestions = [];
        foreach ($tags as $tag) {
            $suggestions[] = [
                'id' => $tag->id,
                'word' => $tag->word,
            ];
        }

        return Presenter::makeApi($suggestions);
    }

    public function suggestionMention()
    {
        $string = Input::get('string');

        /** @var User[] $users */
        $users = User::where('displayName', 'like', $string . '%')->get();

        $suggestions = [];
        foreach ($users as $user) {
            $suggestions[] = [
                'id' => $user->getId(),
                'displayName' => $user->getDisplayName(),
                'profileImage' => $user->getProfileImage(),
            ];
        }
        return Presenter::makeApi($suggestions);
    }
}
