<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment;

use Illuminate\Database\Schema\Blueprint;
use Xpressengine\Permission\Grant;
use Xpressengine\Plugin\AbstractPlugin;
use Xpressengine\Plugins\Comment\Models\Comment;
use Route;
use View;
use Gate;
use XeDB;
use XeConfig;
use XeEditor;
use XeLang;
use XeRegister;
use XeSkin;
use XeStorage;
use XeTag;
use XeToggleMenu;
use XeTrash;
use XeUI;
use Schema;
use Xpressengine\User\Rating;

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
        if (XeConfig::get('comment_map') === null) {
            XeConfig::set('comment_map', []);
        }
    }

    /**
     * @return void
     */
    public function install()
    {
        // put translation source
        XeLang::putFromLangDataSource('comment', base_path('plugins/comment/langs/lang.php'));

        // pivot table
        $this->migrate();

        /** @var Handler $handler */
        $handler = $this->getHandler();

        $grant = new Grant();
        $grant->set('create', [
            Grant::RATING_TYPE => Rating::MEMBER,
            Grant::GROUP_TYPE => [],
            Grant::USER_TYPE => [],
            Grant::EXCEPT_TYPE => [],
            Grant::VGROUP_TYPE => []
        ]);
        $grant->set('manage', [
            Grant::RATING_TYPE => Rating::MANAGER,
            Grant::GROUP_TYPE => [],
            Grant::USER_TYPE => [],
            Grant::EXCEPT_TYPE => [],
            Grant::VGROUP_TYPE => []
        ]);
        app('xe.permission')->register($handler->getKeyForPerm(), $grant);
        // 기본 설정
        XeConfig::set('comment', $handler->getDefaultConfig());

        XeToggleMenu::setActivates('comment', null, []);
    }

    private function migrate()
    {
        $schema = Schema::setConnection(XeDB::connection('document')->master());
        $schema->create($this->targetTable, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->increments('id');
            $table->string('docId', 36);
            $table->string('targetId', 36);
            $table->string('targetAuthorId', 36);

            $table->unique('docId');
            $table->index('targetId');
        });
    }

    public function boot()
    {
        $this->setRoutes();
        $this->registerSettingsMenu();

        Gate::policy(Comment::class, CommentPolicy::class);
        CommentPolicy::setCertifiedResolver(function (Comment $comment) {
            return static::getHandler()->isCertified($comment);
        });

        XeUI::setAlias('comment', 'uiobject/comment@comment');

        XeSkin::setDefaultSkin($this->getId(), 'comment/skin/comment@default');
        XeSkin::setDefaultSettingsSkin($this->getId(), 'comment/settingsSkin/comment@default');

        XeTrash::register(RecycleBin::class);

        app()->singleton('xe.plugin.comment', function () {
            return $this;
        });

        app()->singleton(Handler::class, function ($app) {
            $proxyClass = $app['xe.interception']->proxy(Handler::class);
            $counter = $app['xe.counter']->make($app['request'], Handler::COUNTER_VOTE, ['assent', 'dissent']);
            return new $proxyClass(
                $app['xe.document'],
                $app['session.store'],
                $counter,
                $app['xe.auth'],
                $app['xe.permission'],
                $app['xe.config'],
                $app['xe.keygen']
            );
        });
        app()->alias(Handler::class, 'xe.plugin.comment.handler');

        $this->createIntercept();
    }

    private function createIntercept()
    {
        intercept(
            Handler::class . '@createInstance',
            static::getId() . '::comment.createInstance',
            function ($func, $targetInstanceId, $division = false) {
                $func($targetInstanceId, $division);

                $instanceId = $this->getHandler()->getInstanceId($targetInstanceId);
                XeEditor::setInstance($instanceId, 'editor/ckeditor@ckEditor');
            }
        );

        intercept(Handler::class . '@remove', static::getId() . '::comment.relateRemove', function ($func, $comment) {
            XeStorage::unBindAll($comment->getKey(), true);
            XeTag::set($comment->getKey(), []);

            return $func($comment);
        });
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

            Route::group(['prefix' => 'setting/{targetInstanceId}', 'as' => 'manage.comment.setting'], function () {
                Route::get('/', function ($targetInstanceId) {
                    return redirect()->route('manage.comment.setting.config', $targetInstanceId);
                });
                Route::get('config', ['as' => '.config', 'uses' => 'SettingController@getConfig']);
                Route::post('config', ['as' => '.config', 'uses' => 'SettingController@postConfig']);

                Route::get('perm', ['as' => '.perm', 'uses' => 'SettingController@getPerm']);
                Route::post('perm', ['as' => '.perm', 'uses' => 'SettingController@postPerm']);

                Route::get('skin', ['as' => '.skin', 'uses' => 'SettingController@getSkin']);
                Route::get('editor', ['as' => '.editor', 'uses' => 'SettingController@getEditor']);
                Route::get('df', ['as' => '.df', 'uses' => 'SettingController@getDF']);
                Route::get('tm', ['as' => '.tm', 'uses' => 'SettingController@getTM']);
            });

            Route::group(['prefix' => 'global', 'as' => 'manage.comment.setting.global'], function () {
                Route::get('/', function () {
                    return redirect()->route('manage.comment.setting.global.config');
                });
                Route::get('config', ['as' => '.config', 'uses' => 'SettingController@getGlobalConfig']);
                Route::post('config', ['as' => '.config', 'uses' => 'SettingController@postGlobalConfig']);

                Route::get('perm', ['as' => '.perm', 'uses' => 'SettingController@getGlobalPerm']);
                Route::post('perm', ['as' => '.perm', 'uses' => 'SettingController@postGlobalPerm']);
            });

        }, ['namespace' => 'Xpressengine\\Plugins\\Comment\\Controllers']);

        Route::fixed('comment', function () {
            Route::get('index', ['as' => 'plugin.comment.index', 'uses' => 'UserController@index']);
            Route::post('store', ['as' => 'plugin.comment.store', 'uses' => 'UserController@store']);
            Route::post('update', ['as' => 'plugin.comment.update', 'uses' => 'UserController@update']);
            Route::post('destroy', ['as' => 'plugin.comment.destroy', 'uses' => 'UserController@destroy']);

            Route::get('form', ['as' => 'plugin.comment.form', 'uses' => 'UserController@form']);
            Route::post('certify', ['as' => 'plugin.comment.certify', 'uses' => 'UserController@certify']);
            Route::get('voteInfo', ['as' => 'plugin.comment.vote.info', 'uses' => 'UserController@voteInfo']);
            Route::post('voteOn', ['as' => 'plugin.comment.vote.on', 'uses' => 'UserController@voteOn']);
            Route::post('voteOff', ['as' => 'plugin.comment.vote.off', 'uses' => 'UserController@voteOff']);

            Route::get('votedUser', ['as' => 'plugin.comment.voted.user', 'uses' => 'UserController@votedUser']);
            Route::get('votedModal', ['as' => 'plugin.comment.voted.modal', 'uses' => 'UserController@votedModal']);
            Route::get('votedList', ['as' => 'plugin.comment.voted.list', 'uses' => 'UserController@votedList']);
        }, ['namespace' => 'Xpressengine\\Plugins\\Comment\\Controllers']);
    }

    private function registerSettingsMenu()
    {
        $menus = [
            'contents.comment' => [
                'title' => 'comment::comment',
                'display' => true,
                'description' => 'blur blur~',
                'ordering' => 3000
            ],
            'contents.comment.content' => [
                'title' => 'comment::commentMange',
                'display' => true,
                'description' => 'blur blur~',
                'ordering' => 3010
            ],
            'contents.comment.trash' => [
                'title' => 'comment::trash',
                'display' => true,
                'description' => 'blur blur~',
                'ordering' => 3020
            ],
        ];
        foreach ($menus as $id => $menu) {
            XeRegister::push('settings/menu', $id, $menu);
        }
    }

    public function getSettingsURI()
    {
        return route('manage.comment.setting.global');
    }

    public function getInstanceSettingURI($instanceId)
    {
        return route('manage.comment.setting', $instanceId);
    }

    public function getHandler()
    {
        return app(Handler::class);
    }

    public function pluginPath()
    {
        return __DIR__;
    }

    public function assetPath()
    {
        return str_replace(base_path(), '', $this->pluginPath()) . '/assets';
    }


    /**
     * @param null $installedVersion install version
     * @return void
     */
    public function update($installedVersion = null)
    {
        // ver 0.9.1
        if (XeConfig::get(XeToggleMenu::getConfigKey('comment', null)) == null) {
            XeToggleMenu::setActivates('comment', null, []);
        }

        // ver 0.9.13
        $handler = $this->getHandler();
        $permission = app('xe.permission')->getOrNew($handler->getKeyForPerm());
        if (!$permission['manage']) {
            $grant = new Grant();
            $create = $permission['create'];
            foreach ($create as $type => $value) {
                $grant->add('create', $type, $value);
            }

            $grant->set('manage', [
                Grant::RATING_TYPE => Rating::MANAGER,
                Grant::GROUP_TYPE => [],
                Grant::USER_TYPE => [],
                Grant::EXCEPT_TYPE => [],
                Grant::VGROUP_TYPE => []
            ]);
            app('xe.permission')->register($handler->getKeyForPerm(), $grant);
        }

        XeLang::putFromLangDataSource('comment', base_path('plugins/comment/langs/lang.php'));
    }

    /**
     * @return boolean
     */
    public function checkUpdated($installedVersion = NULL)
    {
        // ver 0.9.1
        if (XeConfig::get(XeToggleMenu::getConfigKey('comment', null)) == null) {
            return false;
        }

        // ver 0.9.13
        $handler = $this->getHandler();
        $permission = app('xe.permission')->getOrNew($handler->getKeyForPerm());
        if (!$permission['manage']) {
            return false;
        }

        return true;
    }
}
