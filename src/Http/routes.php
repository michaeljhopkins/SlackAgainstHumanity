<?php

Route::group(['prefix' => 'cards'], function () {
    Route::get('deal', '\Hopkins\SlackAgainstHumanity\Game\Handler@deal');
    Route::get('start', '\Hopkins\SlackAgainstHumanity\Game\Handler@start');
    Route::get('show', '\Hopkins\SlackAgainstHumanity\Game\Handler@show');
    Route::post('play', '\Hopkins\SlackAgainstHumanity\Game\Handler@play');
    Route::post('choose', '\Hopkins\SlackAgainstHumanity\Game\Handler@choose');
    Route::get('quit', '\Hopkins\SlackAgainstHumanity\Game\Handler@quit');
});
