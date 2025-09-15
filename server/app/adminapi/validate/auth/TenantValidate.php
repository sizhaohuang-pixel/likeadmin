<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\adminapi\validate\auth;

use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;
use app\common\validate\BaseValidate;

/**
 * 租户验证器
 * Class TenantValidate
 * @package app\adminapi\validate\auth
 */
class TenantValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkTenant',
        'account' => 'require|length:1,32|checkAccountUnique',
        'name' => 'require|length:1,16',
        'password' => 'require|length:6,32|edit',
        'password_confirm' => 'requireWith:password|confirm',
        'disable' => 'require|in:0,1|checkAbleDisable',
        'multipoint_login' => 'require|in:0,1',
    ];

    protected $message = [
        'id.require' => '参数缺失',
        'account.require' => '账号不能为空',
        'account.length' => '账号长度须在1-32位字符',
        'name.require' => '名称不能为空',
        'name.length' => '名称须在1-16位字符',
        'password.require' => '密码不能为空',
        'password.length' => '密码长度须在6-32位字符',
        'password_confirm.requireWith' => '确认密码不能为空',
        'password_confirm.confirm' => '两次输入的密码不一致',
        'disable.require' => '状态值不能为空',
        'disable.in' => '状态值错误',
        'multipoint_login.require' => '多端登录不能为空',
        'multipoint_login.in' => '多端登录值错误',
    ];

    /**
     * @notes 添加场景
     * @return TenantValidate
     * @author 段誉
     * @date 2024/08/24
     */
    public function sceneAdd()
    {
        return $this->remove('password', 'edit')
            ->remove('id', true)
            ->remove('disable', true);
    }

    /**
     * @notes 编辑场景
     * @return TenantValidate
     * @author 段誉
     * @date 2024/08/24
     */
    public function sceneEdit()
    {
        return $this->remove('password', 'require');
    }

    /**
     * @notes 详情场景
     * @return TenantValidate
     * @author 段誉
     * @date 2024/08/24
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 删除场景
     * @return TenantValidate
     * @author 段誉
     * @date 2024/08/24
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 校验租户
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2024/08/24
     */
    public function checkTenant($value, $rule, $data)
    {
        $admin = Admin::findOrEmpty($value);
        if ($admin->isEmpty()) {
            return '租户不存在';
        }

        // 检查是否为租户角色
        $hasTenantRole = AdminRole::where('admin_id', $value)
            ->where('role_id', 2) // 租户角色ID为2
            ->find();

        if (!$hasTenantRole) {
            return '该管理员不是租户';
        }

        return true;
    }

    /**
     * @notes 检查账号唯一性
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2024/08/24
     */
    public function checkAccountUnique($value, $rule, $data)
    {
        $where = [['account', '=', $value]];
        
        // 编辑时排除当前记录
        if (isset($data['id']) && !empty($data['id'])) {
            $where[] = ['id', '<>', $data['id']];
        }
        
        $admin = Admin::where($where)->find();
        if ($admin) {
            return '账号已存在';
        }
        
        return true;
    }

    /**
     * @notes 禁用校验
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2024/08/24
     */
    public function checkAbleDisable($value, $rule, $data)
    {
        $admin = Admin::findOrEmpty($data['id']);
        if ($admin->isEmpty()) {
            return '租户不存在';
        }

        if ($value && $admin['root']) {
            return '超级管理员不允许被禁用';
        }
        return true;
    }

    /**
     * @notes 密码编辑场景
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2024/08/24
     */
    public function edit($value, $rule, $data)
    {
        if (!empty($data['password']) && empty($value)) {
            return '密码不能为空';
        }
        return true;
    }
}
