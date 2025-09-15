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
use app\common\model\AltAccountDeleteRecord;
use app\common\lists\ListsSearchInterface;

/**
 * AltAccountDeleteRecord列表
 * Class AltAccountDeleteRecordLists
 * @package app\adminapi\lists
 */
class AltAccountDeleteRecordLists extends BaseAdminDataLists implements ListsSearchInterface
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
     * @date 2025/09/06 23:20
     */
    public function setSearch(): array
    {
        return [
            '=' => ['operator_admin_id'],
            'between_time' => ['delete_time']
        ];
    }

    /**
     * @notes 设置租户权限过滤条件
     * @return array
     * @author likeadmin
     * @date 2025/09/06 23:20
     */
    protected function getTenantWhere(): array
    {
        // 只能查看自己租户的删除记录
        return ['tenant_id' => $this->currentAdminId];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author likeadmin
     * @date 2025/09/06 23:20
     */
    public function lists(): array
    {
        $where = array_merge($this->searchWhere, $this->getTenantWhere());

        // 处理操作人姓名搜索 - 直接从请求参数获取
        $operatorNameSearch = '';
        if (!empty(request()->param('operator_name'))) {
            $operatorNameSearch = trim(request()->param('operator_name'));
        }

        $query = AltAccountDeleteRecord::where($where);

        // 如果有操作人姓名搜索，添加关联查询
        if (!empty($operatorNameSearch)) {
            $query->leftJoin('la_admin admin', 'la_alt_account_delete_record.operator_admin_id = admin.id')
                ->where(function($query) use ($operatorNameSearch) {
                    $query->whereLike('admin.name', "%{$operatorNameSearch}%")
                          ->whereOr('admin.account', 'like', "%{$operatorNameSearch}%");
                })
                ->field(['la_alt_account_delete_record.id', 'la_alt_account_delete_record.tenant_id', 'la_alt_account_delete_record.operator_admin_id', 'la_alt_account_delete_record.delete_time', 'la_alt_account_delete_record.delete_count', 'la_alt_account_delete_record.create_time']);
        } else {
            $query->field(['id', 'tenant_id', 'operator_admin_id', 'delete_time', 'delete_count', 'create_time']);
        }

        $list = $query->limit($this->limitOffset, $this->limitLength)
            ->order(['la_alt_account_delete_record.id' => 'desc'])
            ->append(['delete_time_text', 'operator_name'])
            ->select()
            ->toArray();

        return $list;
    }

    /**
     * @notes 获取数量
     * @return int
     * @author likeadmin
     * @date 2025/09/06 23:20
     */
    public function count(): int
    {
        $where = array_merge($this->searchWhere, $this->getTenantWhere());

        // 处理操作人姓名搜索 - 直接从请求参数获取
        $operatorNameSearch = '';
        if (!empty(request()->param('operator_name'))) {
            $operatorNameSearch = trim(request()->param('operator_name'));
        }

        $query = AltAccountDeleteRecord::where($where);

        // 如果有操作人姓名搜索，添加关联查询
        if (!empty($operatorNameSearch)) {
            $query->leftJoin('la_admin admin', 'la_alt_account_delete_record.operator_admin_id = admin.id')
                ->where(function($query) use ($operatorNameSearch) {
                    $query->whereLike('admin.name', "%{$operatorNameSearch}%")
                          ->whereOr('admin.account', 'like', "%{$operatorNameSearch}%");
                });
        }

        return $query->count('la_alt_account_delete_record.id');
    }
}