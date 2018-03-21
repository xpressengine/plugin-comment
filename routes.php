<?php

Route::settings('comment', function () {
    Route::group(['as' => 'comment::manage.'], function () {
        Route::get('/', ['as' => 'index', 'uses' => 'ManagerController@index', 'settings_menu' => 'contents.comment.content']);
        Route::post('approve', ['as' => 'approve', 'uses' => 'ManagerController@approve']);
        Route::post('totrash', ['as' => 'totrash', 'uses' => 'ManagerController@toTrash']);

        Route::get('trash', ['as' => 'trash', 'uses' => 'ManagerController@trash', 'settings_menu' => 'contents.comment.trash']);
        Route::post('destroy', ['as' => 'destroy', 'uses' => 'ManagerController@destroy']);
        Route::post('restore', ['as' => 'restore', 'uses' => 'ManagerController@restore']);
    });

    Route::group(['prefix' => 'setting/{targetInstanceId}'], function () {
        /* @deprecated since 0.9.15 */
        Route::get('/', ['as' => 'manage.comment.setting', 'uses' => 'SettingController@redirectToConfig']);
        Route::get('/', ['as' => 'comment::setting', 'uses' => 'SettingController@redirectToConfig']);

        Route::group(['as' => 'comment::setting.'], function () {
            Route::get('config', ['as' => 'config', 'uses' => 'SettingController@getConfig']);
            Route::post('config', ['as' => 'config', 'uses' => 'SettingController@postConfig']);

            Route::get('perm', ['as' => 'perm', 'uses' => 'SettingController@getPerm']);
            Route::post('perm', ['as' => 'perm', 'uses' => 'SettingController@postPerm']);

            Route::get('skin', ['as' => 'skin', 'uses' => 'SettingController@getSkin']);
            Route::get('editor', ['as' => 'editor', 'uses' => 'SettingController@getEditor']);
            Route::get('df', ['as' => 'df', 'uses' => 'SettingController@getDF']);
            Route::get('tm', ['as' => 'tm', 'uses' => 'SettingController@getTM']);
        });
    });

    Route::get('global', ['as' => 'comment::setting.global', 'uses' => 'SettingController@redirectToGlobal']);
    Route::group(['prefix' => 'global', 'as' => 'comment::setting.global.'], function () {
        Route::get('config', ['as' => 'config', 'uses' => 'SettingController@getGlobalConfig']);
        Route::post('config', ['as' => 'config', 'uses' => 'SettingController@postGlobalConfig']);

        Route::get('perm', ['as' => 'perm', 'uses' => 'SettingController@getGlobalPerm']);
        Route::post('perm', ['as' => 'perm', 'uses' => 'SettingController@postGlobalPerm']);
    });
});

Route::fixed('comment', function () {
    Route::get('index', ['as' => 'index', 'uses' => 'UserController@index']);
    Route::post('store', ['as' => 'store', 'uses' => 'UserController@store']);
    Route::post('update', ['as' => 'update', 'uses' => 'UserController@update']);
    Route::post('destroy', ['as' => 'destroy', 'uses' => 'UserController@destroy']);

    Route::get('form', ['as' => 'form', 'uses' => 'UserController@form']);
    Route::post('certify', ['as' => 'certify', 'uses' => 'UserController@certify']);
    Route::get('voteInfo', ['as' => 'vote.info', 'uses' => 'UserController@voteInfo']);
    Route::post('voteOn', ['as' => 'vote.on', 'uses' => 'UserController@voteOn']);
    Route::post('voteOff', ['as' => 'vote.off', 'uses' => 'UserController@voteOff']);

    Route::get('votedUser', ['as' => 'voted.user', 'uses' => 'UserController@votedUser']);
    Route::get('votedModal', ['as' => 'voted.modal', 'uses' => 'UserController@votedModal']);
    Route::get('votedList', ['as' => 'voted.list', 'uses' => 'UserController@votedList']);
}, ['as' => 'comment::']);
