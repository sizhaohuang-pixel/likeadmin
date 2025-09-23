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

namespace app\adminapi\controller;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\TaskManagementLogic;
use app\adminapi\lists\BatchTaskLists;
use app\adminapi\validate\BatchTaskValidate;

/**
 * 任务管理控制器
 * Class TaskManagementController
 * @package app\adminapi\controller
 */
class TaskManagementController extends BaseAdminController
{
    /**
     * @notes 批量验活任务列表
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/08
     */
    public function batch_verify_lists()
    {
        // 权限验证：只能查看自己租户的任务
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        // 临时调试信息 - 直接返回调试数据
        if ($this->request->get('debug')) {
            return $this->success('调试信息', [
                'currentAdminId' => $currentAdminId,
                'tenantId' => $tenantId,
                'adminInfo' => $this->adminInfo,
                'params' => $this->request->get()
            ]);
        }
        
        if (!$tenantId) {
            return $this->fail('权限不足，无法访问任务管理，当前用户ID: ' . $currentAdminId);
        }
        
        $params = $this->request->get();
        $params['tenant_id'] = $tenantId; // 强制限制租户范围
        
        return $this->dataLists(new BatchTaskLists($params));
    }

    /**
     * @notes 创建批量验活任务
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/08
     */
    public function create_batch_verify()
    {
        $params = (new BatchTaskValidate())->post()->goCheck('create');
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足，无法创建任务');
        }
        
        // 限制只能操作自己租户的账号
        $params['tenant_id'] = $tenantId;
        $params['create_admin_id'] = $currentAdminId;
        
        $result = TaskManagementLogic::createBatchVerifyTask($params);
        if ($result) {
            return $this->success('批量验活任务创建成功', $result);
        }
        return $this->fail(TaskManagementLogic::getError());
    }

    /**
     * @notes 获取任务详情
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/08
     */
    public function batch_verify_detail()
    {
        $params = (new BatchTaskValidate())->goCheck('detail');
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足');
        }
        
        $params['tenant_id'] = $tenantId;
        $result = TaskManagementLogic::getBatchVerifyDetail($params);
        
        if ($result) {
            return $this->data($result);
        }
        return $this->fail(TaskManagementLogic::getError());
    }

    /**
     * @notes 取消任务
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/08
     */
    public function cancel_batch_verify()
    {
        $params = (new BatchTaskValidate())->post()->goCheck('cancel');
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足');
        }
        
        $params['tenant_id'] = $tenantId;
        $result = TaskManagementLogic::cancelBatchVerifyTask($params);
        
        if ($result) {
            return $this->success('任务取消成功');
        }
        return $this->fail(TaskManagementLogic::getError());
    }

    /**
     * @notes 检查运行中的任务
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/08
     */
    public function check_running_task()
    {
        $taskType = $this->request->get('task_type', '');
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足');
        }
        
        $result = TaskManagementLogic::checkRunningTask($tenantId, $taskType);
        return $this->data($result);
    }

    /**
     * @notes 获取任务进度
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/08
     */
    public function get_task_progress()
    {
        $params = (new BatchTaskValidate())->goCheck('detail');
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足');
        }
        
        $params['tenant_id'] = $tenantId;
        $result = TaskManagementLogic::getTaskProgress($params);
        
        if ($result) {
            return $this->data($result);
        }
        return $this->fail(TaskManagementLogic::getError());
    }

    /**
     * @notes 获取租户任务统计
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/08
     */
    public function get_tenant_stats()
    {
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足');
        }
        
        // 获取任务类型参数，用于分离不同任务类型的统计
        $taskType = $this->request->get('task_type', '');
        
        $result = TaskManagementLogic::getTenantTaskStats($tenantId, $taskType);
        return $this->data($result);
    }

    /**
     * @notes 获取任务执行详情列表
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/08
     */
    public function get_task_detail_list()
    {
        // 获取所有请求参数
        $allParams = $this->request->get();
        
        // 检查任务ID参数
        $taskId = $allParams['id'] ?? $allParams['task_id'] ?? null;
        if (empty($taskId) || !is_numeric($taskId) || $taskId <= 0) {
            return $this->fail('任务ID不能为空');
        }
        
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足');
        }
        
        // 统一参数格式，确保使用id作为参数名
        $params = [
            'id' => $taskId,
            'tenant_id' => $tenantId,
            'status' => $allParams['status'] ?? '',
            'page' => $allParams['page'] ?? $allParams['page_no'] ?? 1,
            'limit' => $allParams['limit'] ?? $allParams['page_size'] ?? 20
        ];
        
        $result = TaskManagementLogic::getTaskDetailList($params);
        return $this->data($result);
    }

    /**
     * 获取当前管理员的租户ID
     * @param int $adminId
     * @return int|null
     */
    private function getTenantId(int $adminId): ?int
    {
        // 通过数据库直接查询角色信息
        $adminRoles = \think\facade\Db::name('admin_role')
            ->alias('ar')
            ->join('system_role sr', 'ar.role_id = sr.id')
            ->where('ar.admin_id', $adminId)
            ->column('sr.name');
        
        if (empty($adminRoles)) {
            return null;
        }
        
        // 如果是租户角色，返回自己的ID
        if (in_array('租户', $adminRoles)) {
            return $adminId;
        }
        
        // 如果是客服角色，返回上级租户ID
        if (in_array('客服', $adminRoles)) {
            $admin = \app\common\model\auth\Admin::find($adminId);
            if ($admin && $admin->parent_id) {
                // 验证上级是否为租户
                $parentRoles = \think\facade\Db::name('admin_role')
                    ->alias('ar')
                    ->join('system_role sr', 'ar.role_id = sr.id')
                    ->where('ar.admin_id', $admin->parent_id)
                    ->column('sr.name');
                
                if (in_array('租户', $parentRoles)) {
                    return $admin->parent_id;
                }
            }
        }
        
        return null; // 其他角色无权限
    }

    /**
     * @notes 批量改昵称任务列表
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/22
     */
    public function batch_nickname_lists()
    {
        // 权限验证：只能查看自己租户的任务
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足，无法访问任务管理');
        }
        
        $params = $this->request->get();
        $params['tenant_id'] = $tenantId; // 强制限制租户范围
        $params['task_type'] = 'batch_nickname'; // 只查询批量改昵称任务
        
        return $this->dataLists(new BatchTaskLists($params));
    }

    /**
     * @notes 创建批量改昵称任务
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/22
     */
    public function create_batch_nickname()
    {
        $params = (new BatchTaskValidate())->post()->goCheck('createNickname');
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足，无法创建任务');
        }
        
        // 限制只能操作自己租户的账号
        $params['tenant_id'] = $tenantId;
        $params['create_admin_id'] = $currentAdminId;
        
        $result = TaskManagementLogic::createBatchNicknameTask($params);
        if ($result) {
            return $this->success('批量改昵称任务创建成功', $result);
        }
        return $this->fail(TaskManagementLogic::getError());
    }

    /**
     * @notes 获取批量改昵称任务详情
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/22
     */
    public function batch_nickname_detail()
    {
        $params = (new BatchTaskValidate())->goCheck('detail');
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足');
        }
        
        $params['tenant_id'] = $tenantId;
        $result = TaskManagementLogic::getBatchNicknameDetail($params);
        
        if ($result) {
            return $this->data($result);
        }
        return $this->fail(TaskManagementLogic::getError());
    }

    /**
     * @notes 取消批量改昵称任务
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/22
     */
    public function cancel_batch_nickname()
    {
        $params = (new BatchTaskValidate())->post()->goCheck('cancel');
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足');
        }
        
        $params['tenant_id'] = $tenantId;
        $result = TaskManagementLogic::cancelBatchNicknameTask($params);
        
        if ($result) {
            return $this->success('任务取消成功');
        }
        return $this->fail(TaskManagementLogic::getError());
    }

    /**
     * @notes 获取账号分组选项
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/22
     */
    public function get_account_groups()
    {
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足');
        }
        
        $result = TaskManagementLogic::getAccountGroups($tenantId);
        return $this->data($result);
    }

    /**
     * @notes 获取昵称分组选项
     * @return \think\response\Json
     * @author Claude
     * @date 2025/09/22
     */
    public function get_nickname_groups()
    {
        $currentAdminId = $this->adminInfo['admin_id'];
        $tenantId = $this->getTenantId($currentAdminId);
        
        if (!$tenantId) {
            return $this->fail('权限不足');
        }
        
        $result = TaskManagementLogic::getNicknameGroups($tenantId);
        return $this->data($result);
    }
}