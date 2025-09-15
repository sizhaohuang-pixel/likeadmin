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

namespace app\adminapi\validate;

use app\common\validate\BaseValidate;
use app\common\model\AltAccountGroup;

/**
 * AltAccountGroup验证器
 * Class AltAccountGroupValidate
 * @package app\adminapi\validate
 */
class AltAccountGroupValidate extends BaseValidate
{

     /**
      * 设置校验规则
      * @var string[]
      */
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'name' => 'require|length:1,50|checkUniqueName',
        'description' => 'max:255',
        'alt_account_ids' => 'require|array',
        'group_id' => 'integer|egt:0',
    ];


    /**
     * 参数描述
     * @var string[]
     */
    protected $field = [
        'id' => 'ID',
        'name' => '分组名称',
        'description' => '分组描述',
        'alt_account_ids' => '小号ID数组',
        'group_id' => '分组ID',
    ];


    /**
     * @notes 添加场景
     * @return AltAccountGroupValidate
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function sceneAdd()
    {
        return $this->only(['name', 'description']);
    }


    /**
     * @notes 编辑场景
     * @return AltAccountGroupValidate
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function sceneEdit()
    {
        return $this->only(['id', 'name', 'description']);
    }


    /**
     * @notes 删除场景
     * @return AltAccountGroupValidate
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }


    /**
     * @notes 详情场景
     * @return AltAccountGroupValidate
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }


    /**
     * @notes 批量设置分组场景
     * @return AltAccountGroupValidate
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function sceneBatchSetGroup()
    {
        return $this->only(['alt_account_ids', 'group_id']);
    }


    /**
     * @notes 验证分组名称唯一性
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function checkUniqueName($value, $rule, $data)
    {
        // 获取当前租户ID（从请求中获取）
        $request = request();
        $tenantId = $request->adminInfo['admin_id'] ?? 0;
        
        $where = [
            ['tenant_id', '=', $tenantId],
            ['name', '=', $value]
        ];
        
        // 编辑时排除自己
        if (isset($data['id']) && $data['id']) {
            $where[] = ['id', '<>', $data['id']];
        }
        
        $exists = AltAccountGroup::where($where)->find();
        if ($exists) {
            return '分组名称已存在';
        }
        
        return true;
    }

}
