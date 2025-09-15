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

namespace app\common\model;

use app\common\model\BaseModel;

/**
 * AltAccountGroup模型
 * Class AltAccountGroup
 * @package app\common\model
 */
class AltAccountGroup extends BaseModel
{
    
    protected $name = 'alt_account_group';
    
    /**
     * @notes 关联小号列表
     * @return \think\model\relation\HasMany
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function altAccounts()
    {
        return $this->hasMany(AltAccount::class, 'group_id', 'id');
    }

    /**
     * @notes 关联同租户下的小号列表
     * @return \think\model\relation\HasMany
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function altAccountsInTenant()
    {
        return $this->hasMany(AltAccount::class, 'group_id', 'id')
            ->where('alt_account.tenant_id', 'exp', 'alt_account_group.tenant_id');
    }
    
    /**
     * @notes 获取分组下的小号数量
     * @param $value
     * @param $data
     * @return int
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function getAltAccountCountAttr($value, $data): int
    {
        // 统计该分组下属于同一租户的小号数量
        return AltAccount::where([
            ['group_id', '=', $data['id']],
            ['tenant_id', '=', $data['tenant_id']]
        ])->count();
    }
}
