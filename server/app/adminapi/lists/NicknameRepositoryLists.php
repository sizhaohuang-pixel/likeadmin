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

namespace app\adminapi\lists;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\NicknameRepository;
use app\common\lists\ListsSearchInterface;

/**
 * 昵称仓库列表
 * Class NicknameRepositoryLists
 * @package app\adminapi\lists
 */
class NicknameRepositoryLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['group_name', 'status'],
            '%like%' => ['nickname'],
        ];
    }

    /**
     * 获取列表
     * @return array
     */
    public function lists(): array
    {
        $field = [
            'id',
            'group_name',
            'nickname', 
            'status',
            'create_time'
        ];

        $lists = NicknameRepository::where($this->searchWhere)
            ->where('tenant_id', $this->adminInfo['admin_id'])
            ->field($field)
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['create_time' => 'desc'])
            ->select()
            ->toArray();

        return $lists;
    }

    /**
     * 获取总数
     * @return int
     */
    public function count(): int
    {
        return NicknameRepository::where($this->searchWhere)
            ->where('tenant_id', $this->adminInfo['admin_id'])
            ->count();
    }
}