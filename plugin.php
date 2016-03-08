<?php
namespace Xpressengine\Plugins\Comment;

use Illuminate\Database\Schema\Blueprint;
use Xpressengine\Plugin\AbstractPlugin;
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\Translation\Translator;
use Route;
use Skin;
use XeTrash;
use View;
use Gate;

class Plugin extends AbstractPlugin
{
    private $targetTable = 'comment_target';

    private $handler;
    /**
     * activate
     *
     * @param null $installedVersion installed version
     * @return void
     */
    public function activate($installedVersion = null)
    {
        /** @var Handler $handler */
        $handler = $this->getHandler();
        // 기본 권한
        $handler->setPermission(null, $handler->getDefaultPermission());
        // 기본 설정
        \XeConfig::set('comment', $handler->getDefaultConfig());
        if (\XeConfig::get('comment_map') === null) {
            \XeConfig::set('comment_map', []);
        }
    }

    /**
     * @return void
     */
    public function install()
    {
        // put translation source
        /** @var Translator $trans */
        $trans = app('xe.translator');
        $trans->putFromLangDataSource('comment', base_path('plugins/comment/langs/lang.php'));

        // pivot table
        $this->migrate();
    }

    private function migrate()
    {
        \Schema::create($this->targetTable, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->increments('id');
            $table->string('docId', 36);
            $table->string('targetId', 36);
            $table->string('targetAuthorId', 36);

            $table->unique('docId');
            $table->index('targetId');
        });
    }

    /**
     * @param null $installedVersion install version
     * @return bool
     */
    public function checkInstall($installedVersion = null)
    {
        return \Schema::hasTable($this->targetTable);
    }

    public function boot()
    {
        $this->setRoutes();
        $this->registerSettingsMenu();

        Gate::policy(Comment::class, CommentPolicy::class);
        CommentPolicy::setCertifiedResolver(function (Comment $comment) {
            return static::getHandler()->isCertified($comment);
        });

        app('xe.uiobject')->setAlias('comment', 'uiobject/comment@comment');

        Skin::setDefaultSkin($this->getId(), 'comment/skin/comment@default');
        Skin::setDefaultSettingsSkin($this->getId(), 'comment/settingsSkin/comment@default');

        XeTrash::register(Waste::class);

        $app = app();

        $app['xe.plugin.comment'] = $app->share(
            function ($app) {
                return $this;
            }
        );
    }

    private function setRoutes()
    {
        Route::settings($this->getId(), function () {
            Route::get('/', ['as' => 'manage.comment.index', 'uses' => 'ManagerController@index', 'settings_menu' => 'contents.comment.content']);
            Route::post('approve', ['as' => 'manage.comment.approve', 'uses' => 'ManagerController@approve']);
            Route::post('totrash', ['as' => 'manage.comment.totrash', 'uses' => 'ManagerController@toTrash']);

            Route::get('trash', ['as' => 'manage.comment.trash', 'uses' => 'ManagerController@trash', 'settings_menu' => 'contents.comment.trash']);
            Route::post('destroy', ['as' => 'manage.comment.destroy', 'uses' => 'ManagerController@destroy']);
            Route::post('restore', ['as' => 'manage.comment.restore', 'uses' => 'ManagerController@restore']);

            Route::post('setting', [
                'as' => 'manage.comment.setting',
                'uses' => 'ManagerController@postSetting'
            ]);
        }, ['namespace' => __NAMESPACE__]);

        Route::fixed('comment', function () {
            Route::get('index', ['as' => 'plugin.comment.index', 'uses' => 'UserController@index']);
            Route::post('store', ['as' => 'plugin.comment.store', 'uses' => 'UserController@store']);
            Route::post('update', ['as' => 'plugin.comment.update', 'uses' => 'UserController@update']);
            Route::post('destroy', ['as' => 'plugin.comment.destroy', 'uses' => 'UserController@destroy']);

            Route::get('form', 'UserController@form');
            Route::post('certify', ['as' => 'plugin.comment.certify', 'uses' => 'UserController@certify']);
            Route::get('voteInfo', 'UserController@voteInfo');
            Route::post('voteOn', 'UserController@voteOn');
            Route::post('voteOff', 'UserController@voteOff');
            Route::get('voteUser', 'UserController@voteUser');


            Route::post('file/upload', 'UserController@fileUpload');
            Route::get('file/source/{id}', 'UserController@fileSource');
            Route::get('file/download/{instanceId}/{fileId}', [
                'as' => 'plugin.comment.download',
                'uses' => 'UserController@fileDownload'
            ]);
            Route::get('suggestion/hashTag', 'UserController@suggestionHashTag');
            Route::get('suggestion/mention', 'UserController@suggestionMention');
        }, ['namespace' => __NAMESPACE__]);
    }

    private function registerSettingsMenu()
    {
        $menus = [
            'contents.comment' => [
                'title' => '댓글',
                'display' => true,
                'description' => 'blur blur~',
                'ordering' => 3000
            ],
            'contents.comment.content' => [
                'title' => '댓글 관리',
                'display' => true,
                'description' => 'blur blur~',
                'link' => route('manage.comment.index'),
                'ordering' => 3010
            ],
            'contents.comment.trash' => [
                'title' => '휴지통',
                'display' => true,
                'description' => 'blur blur~',
                'link' => route('manage.comment.trash'),
                'ordering' => 3020
            ],
        ];
        foreach ($menus as $id => $menu) {
            app('xe.register')->push('settings/menu', $id, $menu);
        }
    }

    public function getHandler()
    {
        if (!$this->handler) {
            $counter = app('xe.counter')->make(app('request'), Handler::COUNTER_VOTE, ['assent', 'dissent']);
            $this->handler = new Handler(
                app('xe.document'),
                app('session.store'),
                $counter,
                app('xe.auth'),
                app('xe.permission'),
                app('xe.config'),
                app('xe.keygen')
            );
        }

        return $this->handler;
    }

    public function pluginPath()
    {
        return __DIR__;
    }

    public function assetPath()
    {
        return str_replace(base_path(), '', $this->pluginPath()) . '/assets';
    }
}
