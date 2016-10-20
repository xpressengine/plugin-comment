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
use Xpressengine\Translation\Translator;
use Route;
use XeTrash;
use XeSkin;
use View;
use Gate;
use XeDB;
use XeConfig;
use XeEditor;
use XeToggleMenu;
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
        /** @var Translator $trans */
        $trans = app('xe.translator');
        $trans->putFromLangDataSource('comment', base_path('plugins/comment/langs/lang.php'));

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
        $grant->set('download', [
            Grant::RATING_TYPE => Rating::MEMBER,
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

//    /**
//     * @param null $installedVersion install version
//     * @return bool
//     */
//    public function checkInstalled($installedVersion = null)
//    {
//        $schema = Schema::setConnection(XeDB::connection('document')->master());
//        return $schema->hasTable($this->targetTable);
//    }

    public function boot()
    {
        $this->setRoutes();
        $this->registerSettingsMenu();

        Gate::policy(Comment::class, CommentPolicy::class);
        CommentPolicy::setCertifiedResolver(function (Comment $comment) {
            return static::getHandler()->isCertified($comment);
        });

        app('xe.uiobject')->setAlias('comment', 'uiobject/comment@comment');

        XeSkin::setDefaultSkin($this->getId(), 'comment/skin/comment@default');
        XeSkin::setDefaultSettingsSkin($this->getId(), 'comment/settingsSkin/comment@default');

        XeTrash::register(RecycleBin::class);

        $app = app();

        $app['xe.plugin.comment'] = $app->share(
            function ($app) {
                return $this;
            }
        );

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

        intercept(Handler::class . '@remove', static::getId() . '::comment.realteRemove', function ($func, $comment) {
            app('xe.storage')->unBindAll($comment->getKey());
            app('xe.tag')->set($comment->getKey(), []);

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

            Route::get('setting/{targetInstanceId}', [
                'as' => 'manage.comment.setting',
                'uses' => 'ManagerController@getSetting'
            ]);

            Route::post('setting/{targetInstanceId}', [
                'as' => 'manage.comment.setting',
                'uses' => 'ManagerController@postSetting'
            ]);

            Route::get('global', [
                'as' => 'manage.comment.setting.global',
                'uses' => 'ManagerController@getGlobalSetting'
            ]);

            Route::post('global', [
                'as' => 'manage.comment.setting.global',
                'uses' => 'ManagerController@postGlobalSetting'
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

            Route::get('votedUser', 'UserController@votedUser');
            Route::get('votedModal', ['as' => 'plugin.comment.voted.modal', 'uses' => 'UserController@votedModal']);
            Route::get('votedList', ['as' => 'plugin.comment.voted.list', 'uses' => 'UserController@votedList']);
        }, ['namespace' => __NAMESPACE__]);
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
            app('xe.register')->push('settings/menu', $id, $menu);
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
        if (!$this->handler) {
            $proxyClass = app('xe.interception')->proxy(Handler::class);
            $counter = app('xe.counter')->make(app('request'), Handler::COUNTER_VOTE, ['assent', 'dissent']);
            $this->handler = new $proxyClass(
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

        /** @var Translator $trans */
        $trans = app('xe.translator');
        $trans->putFromLangDataSource('comment', base_path('plugins/comment/langs/lang.php'));
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

        return true;
    }
}
