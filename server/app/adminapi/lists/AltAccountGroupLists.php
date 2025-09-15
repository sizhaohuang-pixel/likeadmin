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

namespace app\adminapi\lists;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\AltAccountGroup;
use app\common\lists\ListsSearchInterface;

/**
 * AltAccountGroup列表
 * Class AltAccountGroupLists
 * @package app\adminapi\lists
 */
class AltAccountGroupLists extends BaseAdminDataLists implements ListsSearchInterface
{
    protected int $currentAdminId;

    public function __construct(int $currentAdminId = 0)
    {
        parent::__construct();
        $this->currentAdminId = $currentAdminId;
    }


    /**
     * @notes 设置搜索条件
     * @return \string[][]
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function setSearch(): array
    {
        return [
            '=' => ['name'],
            '%like%' => ['description'],
        ];
    }

    /**
     * @notes 设置租户权限过滤条件
     * @return array
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    protected function getTenantWhere(): array
    {
        // 只能查看自己创建的分组
        return ['tenant_id' => $this->currentAdminId];
    }


    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function lists(): array
    {
        $where = array_merge($this->searchWhere, $this->getTenantWhere());
        return AltAccountGroup::where($where)
            ->field(['id', 'tenant_id', 'name', 'description', 'create_time', 'update_time'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['id' => 'desc'])
            ->append(['alt_account_count'])
            ->select()
            ->toArray();
    }


    /**
     * @notes 获取数量
     * @return int
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function count(): int
    {
        $where = array_merge($this->searchWhere, $this->getTenantWhere());
        return AltAccountGroup::where($where)->count();
    }

}
