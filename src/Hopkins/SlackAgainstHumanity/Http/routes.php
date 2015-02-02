<?php

Route::group(['prefix' => 'cards'], function () {
    Route::get('deal', function () {Queue::push('\Hopkins\SlackAgainstHumanity\Game\Handler@deal', Input::all());});
    Route::get('start', function () {Queue::push('\Hopkins\SlackAgainstHumanity\Game\Handler@start', Input::all());});
    Route::get('show', function () {Queue::push('\Hopkins\SlackAgainstHumanity\Game\Handler@show', Input::all());});
    Route::post('play', function () {Queue::push('\Hopkins\SlackAgainstHumanity\Game\Handler@play', Input::all());});
    Route::post('choose', function () {Queue::push('\Hopkins\SlackAgainstHumanity\Game\Handler@choose', Input::all());});
    Route::get('quit', function () {Queue::push('\Hopkins\SlackAgainstHumanity\Game\Handler@quit', Input::all());});
});
