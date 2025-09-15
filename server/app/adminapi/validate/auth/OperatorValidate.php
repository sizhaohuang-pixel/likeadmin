<?php
declare (strict_types = 1);

namespace app\adminapi\validate\auth;

use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;
use app\common\validate\BaseValidate;

/**
 * 运营验证器
 * Class OperatorValidate
 * @package app\adminapi\validate\auth
 */
class OperatorValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkOperator',
        'account' => 'require|length:1,32|checkAccountUnique',
        'name' => 'require|length:1,16',
        'password' => 'editPassword',
        'password_confirm' => 'editPassword',
        'disable' => 'require|in:0,1|checkAbleDisable',
        'multipoint_login' => 'require|in:0,1',
        'account_limit' => 'integer|egt:-1|elt:999999',
    ];

    protected $message = [
        'id.require' => '运营id不能为空',
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
        'account_limit.integer' => '账号分配上限必须为整数',
        'account_limit.egt' => '账号分配上限不能小于-1',
        'account_limit.elt' => '账号分配上限不能大于999999',
    ];

    /**
     * @notes 添加场景
     * @return OperatorValidate
     * @author 段誉
     * @date 2024/08/24
     */
    public function sceneAdd()
    {
        return $this->append('password', 'require|length:6,32')
            ->append('password_confirm', 'require|confirm:password')
            ->remove('password', 'editPassword')
            ->remove('password_confirm', 'editPassword')
            ->remove('id', true)
            ->remove('disable', true);
    }

    /**
     * @notes 编辑场景
     * @return OperatorValidate
     * @author 段誉
     * @date 2024/08/24
     */
    public function sceneEdit()
    {
        // 编辑场景保持原有的editPassword自定义验证
        return $this;
    }

    /**
     * @notes 删除场景
     * @return OperatorValidate
     * @author 段誉
     * @date 2024/08/24
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 详情场景
     * @return OperatorValidate
     * @author 段誉
     * @date 2024/08/24
     */
    public function sceneDetail()
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
     * @date 2024/08/24
     */
    public function editPassword($value, $rule, $data)
    {
        // 如果密码和确认密码都为空，则允许通过（不修改密码）
        if (empty($data['password']) && empty($data['password_confirm'])) {
            return true;
        }
        
        // 如果密码不为空，需要验证长度
        if (!empty($data['password'])) {
            $len = strlen($data['password']);
            if ($len < 6 || $len > 32) {
                return '密码长度须在6-32位字符';
            }
        }
        
        // 如果提供了密码但没有确认密码
        if (!empty($data['password']) && empty($data['password_confirm'])) {
            return '请输入确认密码';
        }
        
        // 如果密码和确认密码不一致
        if (!empty($data['password']) && !empty($data['password_confirm']) 
            && $data['password'] !== $data['password_confirm']) {
            return '两次输入的密码不一致';
        }
        
        return true;
    }

    /**
     * @notes 校验运营
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2024/08/24
     */
    public function checkOperator($value, $rule, $data)
    {
        $admin = Admin::findOrEmpty($value);
        if ($admin->isEmpty()) {
            return '运营不存在';
        }

        // 检查是否为运营角色
        $hasOperatorRole = AdminRole::where('admin_id', $value)
            ->where('role_id', 4) // 运营角色ID为4
            ->find();

        if (!$hasOperatorRole) {
            return '该管理员不是运营';
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
            return '运营不存在';
        }

        if ($value && $admin['root']) {
            return '超级管理员不允许被禁用';
        }
        return true;
    }
}
