<?php

namespace Ycookies\Morepanel;

use Dcat\Admin\Extend\ServiceProvider;
use Dcat\Admin\Admin;
use Ycookies\Morepanel\Console\Commands\PanelCommand;
use Illuminate\Support\Facades\Route;

class MorepanelServiceProvider extends ServiceProvider
{
	protected $js = [
        'js/index.js',

    ];
	protected $css = [
		'css/index.css',
	];

    // 定义菜单
    protected $menu = [
        [
            'title' => '多应用后台',
            'uri'   => 'morepanel/list',
            'icon'  => 'feather icon-layout',
        ]
    ];

    // 路由白名单
    protected $exceptRoutes = [
        'auth' => [
            'panelautologin'
        ]
    ];

	public function register()
	{
        $this->commands([
            PanelCommand::class,
        ]);
	}


	public function init()
	{


		parent::init();
        Route::group(['middleware' => 'web'], function () {
            Route::get('/panelautologin/{panel}/{user}', \Ycookies\Morepanel\Http\Controllers\MorepanelController::class.'@panelautologin')
                ->name('panelautologin')
                ->middleware('signed');
        });
		
	}

	public function settingForm()
	{
		return new Setting($this);
	}
}
