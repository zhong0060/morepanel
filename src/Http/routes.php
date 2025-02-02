<?php

use Ycookies\Morepanel\Http\Controllers;
use Illuminate\Support\Facades\Route;
Route::resource('morepanel/list', Controllers\MorepanelListController::class);
Route::get('morepanel/index', Controllers\MorepanelController::class.'@index');
Route::post('morepanel/panel-user/resetPassword', Controllers\MorepanelController::class.'@resetPassword');
Route::resource('morepanel/paneluser', Controllers\PanelUserController::class);
