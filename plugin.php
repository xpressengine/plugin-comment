<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment;

use Xpressengine\Permission\Grant;
use Xpressengine\Plugin\AbstractPlugin;
use Xpressengine\Plugins\Comment\Migrations\Migration;
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

        XeDB::transaction(function () {
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

            // pivot table
            // schema 처리는 transaction 에 의해 롤백 되지 않으므로 가장 마지막에 수행 함
            (new Migration())->up();
        });
    }

    public function checkInstalled()
    {
        return (new Migration())->tableExists();
    }

    public function uninstall()
    {
        XeDB::transaction(function () {
            $map = XeConfig::getOrNew('comment_map');
            foreach ($map as $instanceId) {
                // document instance 및 instance config 제거
                $this->getHandler()->drop($instanceId);
                // instance permission 제거
                app('xe.permission')->destroy($this->getHandler()->getKeyForPerm($instanceId));
                // toggle menu 설정 제거
                XeConfig::removeByName(XeToggleMenu::getConfigKey('comment', $instanceId));
            }

            // 최상위 permissin 제거
            app('xe.permission')->destroy($this->getHandler()->getKeyForPerm());
            // 최상위 config 제거
            XeConfig::removeByName('comment');
            // map data 제거
            XeConfig::removeByName('comment_map');
            // 최상위 설정 제거
            XeConfig::removeByName(XeToggleMenu::getConfigKey('comment', null));

            // drop pivot table
            // schema 처리는 transaction 에 의해 롤백 되지 않으므로 가장 마지막에 수행 함
            (new Migration())->down();
        });
    }

    public function boot()
    {
        $this->routes();
        $this->intercept();
        $this->registerSettingsMenu();

        Gate::policy(Comment::class, CommentPolicy::class);
        CommentPolicy::setCertifiedResolver(function (Comment $comment) {
            return $this->getHandler()->isCertified($comment);
        });

        XeUI::setAlias('comment', 'uiobject/comment@comment');

        XeSkin::setDefaultSkin('comment', 'comment/skin/comment@default');
        XeSkin::setDefaultSettingsSkin('comment', 'comment/settingsSkin/comment@default');

        XeTrash::register(RecycleBin::class);
    }

    public function register()
    {
        app()->singleton('xe.plugin.comment', function () {
            return $this;
        });

        app()->singleton(Handler::class, function ($app) {
            $proxyClass = $app['xe.interception']->proxy(Handler::class, 'XeComment');
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
        app()->alias(Handler::class, 'xe.comment');
    }

    private function intercept()
    {
        intercept(
            'XeComment@createInstance',
             'comment::setEditor',
            function ($func, $targetInstanceId, $division = false) {
                $func($targetInstanceId, $division);

                $instanceId = $this->getHandler()->getInstanceId($targetInstanceId);
                XeEditor::setInstance($instanceId, 'editor/ckeditor@ckEditor');
            }
        );

        intercept('XeComment@remove', 'comment::relateRemove', function ($func, $comment) {
            XeStorage::unBindAll($comment->getKey(), true);
            XeTag::set($comment->getKey(), []);

            return $func($comment);
        });
    }

    private function routes()
    {
        Route::group(['namespace' => 'Xpressengine\\Plugins\\Comment\\Controllers'], function () {
            require plugins_path('comment/routes.php');
        });
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
