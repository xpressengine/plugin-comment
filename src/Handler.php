<?php
namespace Xpressengine\Plugins\Comment;

use Illuminate\Session\Store as SessionStore;
use Xpressengine\Config\ConfigManager;
use Xpressengine\Document\DocumentHandler;
use Xpressengine\Keygen\Keygen;
use Xpressengine\Permission\PermissionHandler;
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\User\Models\Guest;
use Xpressengine\User\UserInterface;
use Xpressengine\User\GuardInterface as Authenticator;
use Xpressengine\Counter\Counter;
use Xpressengine\User\Rating;
use Xpressengine\Permission\Grant;

class Handler
{
    const PLUGIN_PREFIX = 'comment';

    const COUNTER_VOTE = 'comment_vote';

    protected $documents;

    protected $session;

    protected $counter;

    protected $auth;

    protected $permissions;

    protected $configs;

    protected $keygen;

    protected $model = Comment::class;

    private $defaultConfig = [
        'division' => false,
        'useAssent' => false,
        'useDissent' => false,
        'useApprove' => false,
        'secret' => false,
        'perPage' => 20,
        'useWysiwyg' => false,
        'removeType' => 'batch',
        'reverse' => false
    ];

    public function __construct(
        DocumentHandler $documents,
        SessionStore $session,
        Counter $counter,
        Authenticator $auth,
        PermissionHandler $permissions,
        ConfigManager $configs,
        Keygen $keygen
    )
    {
        $this->documents = $documents;
        $this->session = $session;
        $this->counter = $counter;
        $this->auth = $auth;
        $this->permissions = $permissions;
        $this->configs = $configs;
        $this->keygen = $keygen;
    }

    /**
     * 새로운 인스턴스 설정
     *
     * @param string $targetInstanceId target instance identifier
     * @param bool $division if true, set table division
     * @return void
     */
    public function createInstance($targetInstanceId, $division = false)
    {
        $instanceId = $this->keygen->generate();
        $this->documents->createInstance($instanceId, ['division' => $division]);

        $this->configs->set($this->getKeyForConfig($instanceId), [
            'division' => $division,
        ]);

        $this->instanceMapping($targetInstanceId, $instanceId);

        $this->setPermission($instanceId, new Grant());
    }

    protected function instanceMapping($targetInstanceId, $commentInstanceId)
    {
        $config = $this->configs->get('comment_map');
        $config->set($targetInstanceId, $commentInstanceId);

        $this->configs->modify($config);
    }

    public function getInstanceId($targetInstanceId)
    {
        $config = $this->configs->get('comment_map');

        return $config->get($targetInstanceId);
    }

    public function getTargetInstanceId($instanceId)
    {
        $config = $this->configs->get('comment_map');
        foreach ($config as $target => $id) {
            if ($id == $instanceId) {
                return $target;
            }
        }

        return null;
    }

    protected function getKeyForConfig($instanceId)
    {
        return static::PLUGIN_PREFIX . '.' . $instanceId;
    }

    public function configure($instanceId, array $information)
    {
        $key = $this->getKeyForConfig($instanceId);

        if (!$config = $this->configs->get($key)) {
            throw new \Exception();
        }

        $information = array_merge($this->defaultConfig, $information);
        // division 설정은 최초 인스턴스 생성시 결정되며 변경할 수 없다.
        $information = array_except($information, 'division');

        foreach ($information as $name => $value) {
            $config->set($name, $value);
        }

        $this->configs->modify($config);
    }

    /**
     * 인스턴스 유무
     *
     * @param string $instanceId instance identifier
     * @return bool
     */
    public function existInstance($instanceId)
    {
        return $this->configs->get($this->getKeyForConfig($instanceId)) !== null;
    }

    /**
     * instance 에 속한 comment 를 제거함, table 도 삭제 됨
     *
     * @param string $instanceId instance identifier
     * @return void
     * @throws \Exception
     */
    public function drop($instanceId)
    {
        $key = $this->getKeyForConfig($instanceId);
        if (!$config = $this->configs->get($key)) {
            throw new \Exception();
        }

        // 실질적인 comment row 의 삭제는 document 쪽에서 instance 삭제시 처리함
        $this->documents->destroyInstance($instanceId);

        $this->configs->remove($config);

        $target = $this->getTargetInstanceId($instanceId);
        $this->configs->setVal(implode('.', [$key, $target]), null);
    }

    public function getConfig($instanceId)
    {
        $config = $this->configs->get($this->getKeyForConfig($instanceId));

        if ($config === null) {
            $config = $this->configs->getOrNew($this->getKeyForConfig($instanceId));
            foreach ($this->defaultConfig as $key => $value) {
                $config->set($key, $value);
            }
        }

        return $config;
    }

    public function create(array $inputs, UserInterface $user = null)
    {
        $user = $user ?: $this->auth->user();

        if (!$user instanceof Guest) {
            $inputs['userId'] = $user->getId();
            $inputs['writer'] = $user->getDisplayName();
        }

        $doc = $this->documents->add($inputs);
        $comment = $this->createModel()->newQuery()->find($doc->getKey());

        $comment->target()->create([
            'targetId' => $inputs['targetId'],
            'targetAuthorId' => $inputs['targetAuthorId']
        ]);

        if ($user instanceof Guest) {
            $this->certified($comment);
        }

        return $comment;
    }

    public function put(Comment $comment)
    {
        if ($comment->isDirty()) {
            return $this->documents->put($comment);
        }

        return $comment;
    }

    public function trash(Comment $comment)
    {
        $comment->setTrash();

        return $this->put($comment);
    }

    public function restore(Comment $comment)
    {
        // todo: blind trash 상태에서 휴지통 비우기된 댓글은 복구되지 않음 처리 필요
        // todo: 부모객채가 display 가능한 상태 인지 확인하여 아닌 경우 exception 처리 필요
        // todo: 또는 복구 정책도 document 를 따르던가?

        // todo: implementing

        $comment->setRestore();

        return $this->put($comment);
    }

    public function remove(Comment $comment)
    {
        // todo: 휴지통 상태의 것만 삭제 가능하고 상태에 따라 다른 처리 core의 comment handler 참조
        $comment->target->delete();
        
        return $this->documents->remove($comment);
    }

    /**
     * session 에서 사용될 key 를 반환
     *
     * @return string
     */
    protected function getKeyForCertified()
    {
        return static::PLUGIN_PREFIX . '.certified';
    }

    /**
     * 현재 사용자에 해당 댓글이 인증되었다고 표시함
     *
     * @param Comment $comment comment instance
     * @return void
     */
    public function certified(Comment $comment)
    {
        $key = $this->getKeyForCertified();

        if (!$data = $this->session->get($key)) {
            $data = [];
        }

        $this->session->set($key, array_merge($data, [$comment->id => time() + 600]));
    }

    /**
     * 현재 사용자가 해당 댓글에 인증이 되었는지 판별
     *
     * @param Comment $comment comment instance
     * @return bool
     */
    public function isCertified(Comment $comment)
    {
        $data = $this->session->get($this->getKeyForCertified());

        return !(!$data || !isset($data[$comment->id]) || $data[$comment->id] < time());
    }

    /**
     * 찬성 or 추천 or 좋아요
     *
     * @param Comment $comment comment entity
     * @param string $option 'assent' or 'dissent'
     * @param UserInterface $author user instance
     * @return Comment
     */
    public function addVote(Comment $comment, $option, UserInterface $author = null)
    {
        $author = $author ?: $this->auth->user();

        $this->counter->add($comment->id, $author, $option);

        $column = $this->voteOptToColumn($option);
        $comment->{$column} = $comment->{$column} + 1;

        return $this->documents->put($comment);
    }

    /**
     * 반대 or 비추천 or 싫어요
     *
     * @param Comment $comment comment entity
     * @param string $option 'assent' or 'dissent'
     * @param UserInterface $author user instance
     * @return Comment
     */
    public function removeVote(Comment $comment, $option, UserInterface $author = null)
    {
        $author = $author ?: $this->auth->user();

        $this->counter->remove($comment->id, $author, $option);

        $column = $this->voteOptToColumn($option);
        $comment->{$column} = $comment->{$column} - 1;

        return $this->documents->put($comment);
    }

    private function voteOptToColumn($opt)
    {
        if ($opt == 'assent') {
            $column = 'assentCount';
        } elseif ($opt == 'dissent') {
            $column = 'dissentCount';
        } else {
            throw new \InvalidArgumentException;
        }

        return $column;
    }

    public function voteUsers(Comment $comment, $option)
    {
        return $this->counter->getUsers($comment->id, $option);
    }

    public function bindUserVote(Comment $comment)
    {
        if (!$this->auth->guest() && $log = $this->counter->getByName($comment->id, $this->auth->user())) {
            $comment->setVoteType($log->counterOption);
        }
    }

    /**
     * 대상 객체에 속하는 댓글을 이동시킴
     *
     * @param CommentUsable $target comment usable instance
     * @return void
     * @throws \Exception
     */
    public function moveByTarget(CommentUsable $target)
    {
        if (!$newInstanceId = $this->getInstanceId($target->getInstanceId())) {
            throw new \Exception;
        }

        $model = $this->createModel();
        $comments = $model->newQuery()->whereHas('target', function ($query) use ($target) {
            $query->where('targetId', $target->getUid());
        })->get();

        foreach ($comments as $comment) {
            $comment->instanceId = $newInstanceId;

            $this->documents->put($comment);
        }
    }

    public function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    public function getDefaultPermission()
    {
        $grant = new Grant();
        $grant->set('create', [
            Grant::RATING_TYPE => Rating::MEMBER,
            Grant::GROUP_TYPE => [],
            Grant::USER_TYPE => [],
            Grant::EXCEPT_TYPE => [],
            Grant::VGROUP_TYPE => []
        ]);
        $grant->set('download', [
            Grant::RATING_TYPE => Rating::MEMBER,
            Grant::GROUP_TYPE => [],
            Grant::USER_TYPE => [],
            Grant::EXCEPT_TYPE => [],
            Grant::VGROUP_TYPE => []
        ]);

        return $grant;
    }

    /**
     * @param $instanceId
     * @return mixed
     */
    public function getPermission($instanceId)
    {
        return $this->permissions->findOrNew($this->getKeyForPerm($instanceId));
    }

    /**
     * @param $instanceId
     * @param Grant $grant
     */
    public function setPermission($instanceId, Grant $grant)
    {
        $this->permissions->register($this->getKeyForPerm($instanceId), $grant);
    }

    /**
     * @param $instanceId
     */
    public function removePermission($instanceId)
    {
        $this->permissions->destroy($this->getKeyForPerm($instanceId));
    }
    
    public function getKeyForPerm($instanceId)
    {
        $name = static::PLUGIN_PREFIX;

        return $instanceId === null ? $name : $name . '.' . $instanceId;
    }

    public function createModel()
    {
        $class = $this->getModel();

        return new $class;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setModel($model)
    {
        $this->model = '\\' . ltrim($model, '\\');
    }
}