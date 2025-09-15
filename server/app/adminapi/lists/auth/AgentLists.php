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
use app\common\lists\ListsExcelInterface;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;
use app\common\model\auth\SystemRole;
use app\common\service\AdminHierarchyService;
use app\common\service\PlatformAdminService;

/**
 * 代理商列表
 * Class AgentLists
 * @package app\adminapi\lists\auth
 */
class AgentLists extends BaseAdminDataLists implements ListsExtendInterface, ListsSearchInterface, ListsSortInterface, ListsExcelInterface
{
    /**
     * @notes 代理角色ID
     * @var int
     */
    private $agentRoleId = 1; // 代理角色的ID

    /**
     * @notes 设置导出字段
     * @return string[]
     * @author 段誉
     * @date 2021/12/29 10:08
     */
    public function setExcelFields(): array
    {
        return [
            'account' => '账号',
            'name' => '名称',
            'role_name' => '角色',
            'online_status_text' => '在线状态',
            'create_time' => '创建时间',
            'login_time' => '最近登录时间',
            'login_ip' => '最近登录IP',
            'disable_desc' => '状态',
            'parent_name' => '上级',
        ];
    }

    /**
     * @notes 设置导出文件名
     * @return string
     * @author 段誉
     * @date 2021/12/29 10:08
     */
    public function setFileName(): string
    {
        return '代理商列表';
    }

    /**
     * @notes 设置搜索条件
     * @return \string[][]
     * @author 段誉
     * @date 2021/12/29 10:07
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['name', 'account'],
        ];
    }

    /**
     * @notes 设置支持排序字段
     * @return string[]
     * @author 段誉
     * @date 2021/12/29 10:07
     * @remark 格式: ['前端传过来的字段名' => '数据库中的字段名'];
     */
    public function setSortFields(): array
    {
        return ['create_time' => 'create_time', 'id' => 'id'];
    }

    /**
     * @notes 设置默认排序
     * @return string[]
     * @author 段誉
     * @date 2021/12/29 10:06
     */
    public function setDefaultOrder(): array
    {
        return ['id' => 'desc'];
    }

    /**
     * @notes 查询条件 - 只查询代理商角色的管理员，并应用层级权限
     * @return array
     * @author 段誉
     * @date 2022/11/29 11:33
     */
    public function queryWhere()
    {
        $where = [];

        // 获取具有代理角色的管理员ID
        $agentAdminIds = AdminRole::where('role_id', $this->agentRoleId)->column('admin_id');

        // 获取当前管理员可以查看的管理员ID
        $currentAdminId = $this->adminId ?? 0;
        $viewableAdminIds = AdminHierarchyService::getViewableAdminIds($currentAdminId);

        // 取交集：既是代理商，又在权限范围内
        $allowedAgentIds = array_intersect($agentAdminIds, $viewableAdminIds);

        if (!empty($allowedAgentIds)) {
            $where[] = ['id', 'in', $allowedAgentIds];
        } else {
            // 如果没有可查看的代理商，返回空结果
            $where[] = ['id', '=', 0];
        }

        return $where;
    }

    /**
     * @notes 获取代理商列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2021/12/29 10:05
     */
    public function lists(): array
    {
        $field = [
            'id', 'name', 'account', 'create_time', 'disable', 'root',
            'login_time', 'login_ip', 'multipoint_login', 'avatar', 'parent_id'
        ];

        $agentLists = Admin::field($field)
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->limit($this->limitOffset, $this->limitLength)
            ->order($this->sortOrder)
            ->append(['role_id', 'disable_desc', 'parent_name', 'online_status', 'online_status_text'])
            ->select()
            ->toArray();

        // 角色数组（'角色id'=>'角色名称')
        $roleLists = SystemRole::column('name', 'id');

        //代理商列表增加角色名称
        foreach ($agentLists as $k => $v) {
            $roleName = '';
            if ($v['root'] == 1) {
                $roleName = '系统管理员';
            } else {
                foreach ($v['role_id'] as $roleId) {
                    $roleName .= $roleLists[$roleId] ?? '';
                    $roleName .= '/';
                }
            }

            $agentLists[$k]['role_name'] = trim($roleName, '/');
        }

        return $agentLists;
    }

    /**
     * @notes 获取数量
     * @return int
     * @author 令狐冲
     * @date 2021/7/13 00:52
     */
    public function count(): int
    {
        return Admin::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }

    /**
     * @notes 扩展数据
     * @return array
     * @author 段誉
     * @date 2021/12/29 10:05
     */
    public function extend()
    {
        return [];
    }
}
