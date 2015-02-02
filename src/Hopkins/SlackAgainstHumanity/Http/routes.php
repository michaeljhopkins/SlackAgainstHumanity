<?php

Route::group(['prefix' => 'cards'], function () {
    Route::get('deal',function(){Queue::push('\Idop\Things\Games\Cards\Handler@deal',Input::all());});
    Route::get('start',function(){Queue::push('\Idop\Things\Games\Cards\Handler@start',Input::all());});
    Route::get('show',function(){Queue::push('\Idop\Things\Games\Cards\Handler@show',Input::all());});
    Route::post('play',function(){Queue::push('\Idop\Things\Games\Cards\Handler@play',Input::all());});
    Route::post('choose',function(){Queue::push('\Idop\Things\Games\Cards\Handler@choose',Input::all());});
    Route::get('quit',function(){Queue::push('\Idop\Things\Games\Cards\Handler@quit',Input::all());});
});