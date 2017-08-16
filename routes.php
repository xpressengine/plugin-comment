<?php

Route::settings('comment', function () {
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

});

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
});
