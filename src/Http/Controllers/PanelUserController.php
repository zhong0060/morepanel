<?php

namespace Ycookies\Morepanel\Http\Controllers;

use App\Seller\Models\Administrator as AdministratorModel;
use App\Seller\Models\Administrator;

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Auth\Permission;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\Modal;
use Dcat\Admin\Widgets\Tree;
use Illuminate\Support\Facades\URL;

// 本公共管理面板修改用户有待完善

class PanelUserController extends AdminController {
    public function title()
    {
        return trans('admin.administrator');
    }

    protected function grid()
    {
        return Grid::make(Administrator::with(['roles']), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('username');
            $grid->column('name');

            if (config('admin.permission.enable')) {
                $grid->column('roles')->pluck('name')->label('primary', 3);

                $permissionModel = config('admin.database.permissions_model');
                $roleModel = config('admin.database.roles_model');
                $nodes = (new $permissionModel())->allNodes();
                $grid->column('permissions')
                    ->if(function () {
                        return ! $this->roles->isEmpty();
                    })
                    ->showTreeInDialog(function (Grid\Displayers\DialogTree $tree) use (&$nodes, $roleModel) {
                        $tree->nodes($nodes);

                        foreach (array_column($this->roles->toArray(), 'slug') as $slug) {
                            if ($roleModel::isAdministrator($slug)) {
                                $tree->checkAll();
                            }
                        }
                    })
                    ->else()
                    ->display('');
            }

            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->quickSearch(['id', 'name', 'username']);

            $grid->showQuickEditButton();
            $grid->enableDialogCreate();
            $grid->showColumnSelector();
            $grid->disableEditButton();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                if ($actions->getKey() == AdministratorModel::DEFAULT_ID) {
                    $actions->disableDelete();
                }
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, Administrator::with(['roles']), function (Show $show) {
            $show->field('id');
            $show->field('username');
            $show->field('name');

            $show->field('avatar', __('admin.avatar'))->image();

            if (config('admin.permission.enable')) {
                $show->field('roles')->as(function ($roles) {
                    if (! $roles) {
                        return;
                    }

                    return collect($roles)->pluck('name');
                })->label();

                $show->field('permissions')->unescape()->as(function () {
                    $roles = $this->roles->toArray();

                    $permissionModel = config('admin.database.permissions_model');
                    $roleModel = config('admin.database.roles_model');
                    $permissionModel = new $permissionModel();
                    $nodes = $permissionModel->allNodes();

                    $tree = Tree::make($nodes);

                    $isAdministrator = false;
                    foreach (array_column($roles, 'slug') as $slug) {
                        if ($roleModel::isAdministrator($slug)) {
                            $tree->checkAll();
                            $isAdministrator = true;
                        }
                    }

                    if (! $isAdministrator) {
                        $keyName = $permissionModel->getKeyName();
                        $tree->check(
                            $roleModel::getPermissionId(array_column($roles, $keyName))->flatten()
                        );
                    }

                    return $tree->render();
                });
            }

            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    public function form()
    {
        $panel_code = request()->get('panel_code');
        $panelCode = ucfirst($panel_code);
        $model = "\App\\".$panelCode."\\Models\Administrator";
        return Form::make($model::with(['roles']), function (Form $form) use($panel_code) {
            $userTable = config($panel_code.'.database.users_table');

            $connection = config($panel_code.'.database.connection');

            $id = $form->getKey();

            $form->display('id', 'ID');
            $form->hidden('panel_code')->value($panel_code);
            $form->text('username', trans('admin.username'))
                ->required()
                ->creationRules(['required', "unique:{$connection}.{$userTable}"])
                ->updateRules(['required', "unique:{$connection}.{$userTable},username,$id"]);
            $form->text('name', trans('admin.name'))->required();
            $form->mobile('phone','手机号');
            $form->datetime('expired_at','到期时间');
            $form->image('avatar', trans('admin.avatar'))->autoUpload();

            if ($id) {
                $form->password('password', trans('admin.password'))
                    ->minLength(5)
                    ->maxLength(20)
                    ->customFormat(function () {
                        return '';
                    });
            } else {
                $form->password('password', trans('admin.password'))
                    ->required()
                    ->minLength(5)
                    ->maxLength(20);
            }

            $form->password('password_confirmation', trans('admin.password_confirmation'))->same('password');

            $form->ignore(['password_confirmation']);

            if (config('admin.permission.enable')) {
                $form->multipleSelect('roles', trans('admin.roles'))
                    ->options(function () {
                        $roleModel = config('admin.database.roles_model');

                        return $roleModel::all()->pluck('name', 'id');
                    })
                    ->customFormat(function ($v) {
                        return array_column($v, 'id');
                    });
            }

            $form->display('created_at', trans('admin.created_at'));
            $form->display('updated_at', trans('admin.updated_at'));

            if ($id == AdministratorModel::DEFAULT_ID) {
                $form->disableDeleteButton();
            }
        })->saving(function (Form $form) {
            if ($form->password && $form->model()->get('password') != $form->password) {
                $form->password = bcrypt($form->password);
            }
            $form->deleteInput('panel_code');
            if (! $form->password) {
                $form->deleteInput('password');
            }
        });
    }

    public function destroy($id)
    {
        if (in_array(AdministratorModel::DEFAULT_ID, Helper::array($id))) {
            Permission::error();
        }

        return parent::destroy($id);
    }
}
