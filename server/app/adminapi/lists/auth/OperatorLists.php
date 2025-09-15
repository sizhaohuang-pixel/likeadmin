<?php
declare (strict_types = 1);

namespace app\adminapi\lists\auth;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;
use app\common\model\auth\SystemRole;
use app\common\service\AdminHierarchyService;

/**
 * 运营列表
 * Class OperatorLists
 * @package app\adminapi\lists\auth
 */
class OperatorLists extends BaseAdminDataLists implements ListsExtendInterface, ListsSearchInterface, ListsSortInterface, ListsExcelInterface
{
    /**
     * 运营角色ID
     */
    private $operatorRoleId = 4;

    /**
     * @notes 搜索条件
     * @return \string[][]
     * @author 段誉
     * @date 2024/08/24
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['name', 'account'],
        ];
    }

    /**
     * @notes 获取运营列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2024/08/24
     */
    public function lists(): array
    {
        $field = [
            'id', 'name', 'account', 'create_time', 'disable', 'root',
            'login_time', 'login_ip', 'multipoint_login', 'avatar', 'parent_id',
            'account_limit'
        ];

        $operatorLists = Admin::field($field)
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->limit($this->limitOffset, $this->limitLength)
            ->order($this->sortOrder)
            ->append(['role_id', 'dept_id', 'jobs_id', 'disable_desc', 'parent_name', 'online_status', 'online_status_text', 'account_limit_text', 'allocated_accounts_count'])
            ->select()
            ->toArray();

        foreach ($operatorLists as $key => $item) {
            $operatorLists[$key]['avatar'] = $item['avatar'] ? $item['avatar'] : '';
            $operatorLists[$key]['role_name'] = $this->getRoleName($item['role_id']);
        }

        return $operatorLists;
    }

    /**
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2024/08/24
     */
    public function count(): int
    {
        return Admin::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }

    /**
     * @notes 查询条件
     * @return array
     * @author 段誉
     * @date 2024/08/24
     */
    public function queryWhere(): array
    {
        $where = [];

        // 获取当前管理员可以查看的管理员ID列表
        $viewableAdminIds = AdminHierarchyService::getViewableAdminIds($this->adminId);

        // 只查询具有运营角色的管理员
        $operatorAdminIds = AdminRole::where('role_id', $this->operatorRoleId)->column('admin_id');

        // 取两个数组的交集，既要是可查看的，又要是运营角色的
        if (!empty($viewableAdminIds) && !empty($operatorAdminIds)) {
            $finalAdminIds = array_intersect($viewableAdminIds, $operatorAdminIds);
            if (!empty($finalAdminIds)) {
                $where[] = ['id', 'in', $finalAdminIds];
            } else {
                // 如果交集为空，返回空结果
                $where[] = ['id', '=', 0];
            }
        } else {
            // 如果任一条件为空，返回空结果
            $where[] = ['id', '=', 0];
        }

        return $where;
    }

    /**
     * @notes 获取角色名称
     * @param array $roleIds
     * @return string
     * @author 段誉
     * @date 2024/08/24
     */
    private function getRoleName(array $roleIds): string
    {
        if (empty($roleIds)) {
            return '';
        }

        $roleNames = SystemRole::whereIn('id', $roleIds)->column('name');
        return implode('、', $roleNames);
    }

    /**
     * @notes 设置导出字段
     * @return string[]
     * @author 段誉
     * @date 2024/08/24
     */
    public function setExcelFields(): array
    {
        return [
            'account' => '账号',
            'name' => '名称',
            'role_name' => '角色',
            'online_status_text' => '在线状态',
            'account_limit_text' => '分配上限',
            'allocated_accounts_count' => '已分配数量',
            'create_time' => '创建时间',
            'login_time' => '最近登录时间',
            'disable_desc' => '状态',
            'parent_name' => '上级',
        ];
    }

    /**
     * @notes 设置导出文件名
     * @return string
     * @author 段誉
     * @date 2024/08/24
     */
    public function setFileName(): string
    {
        return '运营列表';
    }

    /**
     * @notes 设置排序
     * @return string[]
     * @author 段誉
     * @date 2024/08/24
     */
    public function setSortFields(): array
    {
        return ['create_time' => '创建时间', 'id' => 'id'];
    }

    /**
     * @notes 设置默认排序
     * @return string[]
     * @author 段誉
     * @date 2024/08/24
     */
    public function setDefaultOrder(): array
    {
        return ['id' => 'desc'];
    }

    /**
     * @notes 扩展方法
     * @return array
     * @author 段誉
     * @date 2024/08/24
     */
    public function extend(): array
    {
        return [];
    }
}
