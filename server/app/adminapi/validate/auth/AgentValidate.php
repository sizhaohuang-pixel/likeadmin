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

use app\common\validate\BaseValidate;
use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;

/**
 * 代理商验证器
 * Class AgentValidate
 * @package app\adminapi\validate\auth
 */
class AgentValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkAgent',
        'account' => 'require|length:1,32|checkAccountUnique',
        'name' => 'require|length:1,16',
        'password' => 'require|length:6,32|edit',
        'password_confirm' => 'requireWith:password|confirm',
        'disable' => 'require|in:0,1|checkAbleDisable',
        'multipoint_login' => 'require|in:0,1',
    ];

    protected $message = [
        'id.require' => '代理商id不能为空',
        'account.require' => '账号不能为空',
        'account.length' => '账号长度须在1-32位字符',
        'password.require' => '密码不能为空',
        'password.length' => '密码长度须在6-32位字符',
        'password_confirm.requireWith' => '确认密码不能为空',
        'password_confirm.confirm' => '两次输入的密码不一致',
        'name.require' => '名称不能为空',
        'name.length' => '名称须在1-16位字符',
        'disable.require' => '请选择状态',
        'disable.in' => '状态值错误',
        'multipoint_login.require' => '请选择是否支持多处登录',
        'multipoint_login.in' => '多处登录状态值为误',
    ];

    /**
     * @notes 添加场景
     * @return AgentValidate
     * @author 段誉
     * @date 2021/12/29 15:46
     */
    public function sceneAdd()
    {
        return $this->remove('password', 'edit')
            ->remove('id', true)
            ->remove('disable', true);
    }

    /**
     * @notes 详情场景
     * @return AgentValidate
     * @author 段誉
     * @date 2021/12/29 15:46
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 编辑场景
     * @return AgentValidate
     * @author 段誉
     * @date 2021/12/29 15:47
     */
    public function sceneEdit()
    {
        return $this->remove('password', 'require|length')
            ->append('id', 'require|checkAgent');
    }

    /**
     * @notes 删除场景
     * @return AgentValidate
     * @author 段誉
     * @date 2021/12/29 15:47
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 编辑情况下，检查是否填密码
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2021/12/29 10:19
     */
    public function edit($value, $rule, $data)
    {
        if (empty($data['password']) && empty($data['password_confirm'])) {
            return true;
        }
        $len = strlen($value);
        if ($len < 6 || $len > 32) {
            return '密码长度须在6-32位字符';
        }
        return true;
    }

    /**
     * @notes 检查指定代理商是否存在且为代理角色
     * @param $value
     * @return bool|string
     * @author 段誉
     * @date 2021/12/29 10:19
     */
    public function checkAgent($value)
    {
        $admin = Admin::findOrEmpty($value);
        if ($admin->isEmpty()) {
            return '代理商不存在';
        }

        // 检查是否具有代理角色
        $hasAgentRole = AdminRole::where('admin_id', $value)
            ->where('role_id', 1) // 代理角色ID为1
            ->find();
        
        if (!$hasAgentRole) {
            return '该管理员不是代理商';
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
     * @date 2022/8/11 9:59
     */
    public function checkAbleDisable($value, $rule, $data)
    {
        $admin = Admin::findOrEmpty($data['id']);
        if ($admin->isEmpty()) {
            return '代理商不存在';
        }

        if ($value && $admin['root']) {
            return '超级管理员不允许被禁用';
        }
        return true;
    }
}
