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

namespace app\adminapi\lists\auth;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsExcelInterface;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;
use app\common\model\auth\SystemRole;
use app\common\service\AdminHierarchyService;
use app\common\model\package\PackageAssignment;
use app\common\model\AltAccount;
use app\common\service\PortStatisticsService;

/**
 * 租户列表
 * Class TenantLists
 * @package app\adminapi\lists\auth
 */
class TenantLists extends BaseAdminDataLists implements ListsExtendInterface, ListsSearchInterface, ListsSortInterface, ListsExcelInterface
{
    /**
     * 租户角色ID
     */
    private $tenantRoleId = 2;

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
     * @notes 获取管理列表
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
            'login_time', 'login_ip', 'multipoint_login', 'avatar', 'parent_id'
        ];

        $adminLists = Admin::field($field)
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->limit($this->limitOffset, $this->limitLength)
            ->order($this->sortOrder)
            ->append(['role_id', 'dept_id', 'jobs_id', 'disable_desc', 'parent_name', 'online_status', 'online_status_text'])
            ->select()
            ->toArray();

        // 角色数组（'角色id'=>'角色名称')
        $roleLists = SystemRole::column('name', 'id');

        // 租户列表增加角色名称和端口统计信息
        foreach ($adminLists as $k => $v) {
            $roleName = '';
            if (!empty($v['role_id'])) {
                foreach ($v['role_id'] as $roleId) {
                    $roleName .= $roleLists[$roleId] ?? '';
                    $roleName .= '/';
                }
            }
            $adminLists[$k]['role_name'] = trim($roleName, '/');

            // 添加端口统计信息（使用统一的服务类）
            $tenantId = $v['id'];
            $portStats = PortStatisticsService::getTenantPortStats($tenantId);
            $adminLists[$k]['total_ports'] = $portStats['total_ports'];           // 端口总数（当前有效端口数量）
            $adminLists[$k]['used_ports'] = $portStats['used_ports'];             // 已用端口
            $adminLists[$k]['available_ports'] = $portStats['available_ports'];   // 空闲端口
            $adminLists[$k]['expired_ports'] = $portStats['expired_ports'];       // 过期端口
        }

        return $adminLists;
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
     * @notes 查询条件 - 只查询租户角色的管理员，并应用层级权限
     * @return array
     * @author 段誉
     * @date 2024/08/24
     */
    public function queryWhere()
    {
        $where = [];

        // 获取具有租户角色的管理员ID
        $tenantAdminIds = AdminRole::where('role_id', $this->tenantRoleId)->column('admin_id');

        // 获取当前管理员可以查看的管理员ID
        $currentAdminId = $this->adminId ?? 0;
        $viewableAdminIds = AdminHierarchyService::getViewableAdminIds($currentAdminId);

        // 取交集：既是租户，又在权限范围内
        $allowedTenantIds = array_intersect($tenantAdminIds, $viewableAdminIds);

        if (!empty($allowedTenantIds)) {
            $where[] = ['id', 'in', $allowedTenantIds];
        } else {
            // 如果没有可查看的租户，返回空结果
            $where[] = ['id', '=', 0];
        }

        return $where;
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
            'total_ports' => '端口总数',
            'used_ports' => '已用端口',
            'available_ports' => '空闲端口',
            'expired_ports' => '过期端口',
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
        return '租户列表';
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

    public function extend()
    {
        // TODO: Implement extend() method.
    }


}
