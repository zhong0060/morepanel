<?php

namespace Ycookies\Morepanel\Console\Commands;

use Dcat\Admin\Support\Helper;
use Illuminate\Filesystem\Filesystem;
//use  Dcat\Admin\Console\InstallCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Ycookies\Morepanel\Models\MorepanelList;

class PanelCommand extends InstallCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'panel:app {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new panel';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->exeSqlFile();
        $this->addConfig();
        $this->initAdminDirectory();
        $app = Helper::slug($namespace = $this->argument('name'));

        // 修改配置信息
        Config::set('admin.multi_app.'.lcfirst($app), true);
        // 永久保存配置信息
        //Artisan::call('config:cache');

        $this->info('Done.');
    }

    protected function addConfig()
    {
        /* @var Filesystem $files */
        $files = $this->laravel['files'];
        $app_name = Helper::slug($namespace = $this->argument('name'));
        $info = MorepanelList::where(['panel_code'=> $app_name])->first();
        $panel_name = '速码邦';
        $panel_logo = '/vendor/dcat-admin/images/logo.png';
        $panel_color = 'default';
        $panel_brief = '';
        if($info){
            $panel_name = $info->panel_name;
            $panel_logo = $info->panel_logo;
            $panel_color = $info->panel_color;
            $panel_brief = $info->panel_brief;

        }
        $files->put(
            $config = config_path($app_name.'.php'),
            str_replace(
                ['DummyNamespace', 'DummyApp','AppName','AppLogo','AppColor','AppBrief'],
                [$namespace, $app_name,$panel_name,$panel_logo,$panel_color,$panel_brief],
                $files->get(__DIR__.'/stubs/config.stub')
            )
        );


        config(['admin' => include $config]);
    }

    /**
     * Set admin directory.
     *
     * @return void
     */
    protected function setDirectory()
    {
        $this->directory = app_path($this->argument('name'));
    }
}
