<?php
declare (strict_types = 1);

namespace app\adminapi\controller;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\PackageLists;
use app\adminapi\logic\PackageLogic;
use app\adminapi\validate\PackageValidate;

/**
 * 套餐分配控制器
 * Class PackageController
 * @package app\adminapi\controller
 */
class PackageController extends BaseAdminController
{
    /**
     * @notes 套餐分配
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function assign()
    {
        $params = (new PackageValidate())->post()->goCheck('assign');

        // 确保数值类型正确
        $params['tenant_id'] = (int)$params['tenant_id'];
        $params['port_count'] = (int)$params['port_count'];
        $params['expire_days'] = (int)$params['expire_days'];

        $result = PackageLogic::assign($params, $this->adminId);

        if (true === $result) {
            // 返回分配成功的详细信息
            $assignInfo = [
                'tenant_id' => $params['tenant_id'],
                'port_count' => $params['port_count'],
                'expire_days' => $params['expire_days'],
                'assign_time' => date('Y-m-d H:i:s'),
                'expire_time' => date('Y-m-d H:i:s', time() + ($params['expire_days'] * 24 * 60 * 60))
            ];
            return $this->success('套餐分配成功', $assignInfo, 1, 1);
        }

        return $this->fail(PackageLogic::getError());
    }

    /**
     * @notes 获取租户套餐信息（虚拟端口池版本）
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function tenantPackages()
    {
        $params = (new PackageValidate())->get()->goCheck('tenantPackages');
        $result = PackageLogic::getTenantPackages((int)$params['tenant_id'], $this->adminId);

        if (!empty($result)) {
            return $this->data($result);
        }

        return $this->fail(PackageLogic::getError() ?: '获取套餐信息失败');
    }

    /**
     * @notes 分配小号给客服
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function assignAltAccount()
    {
        $params = (new PackageValidate())->post()->goCheck('assignAltAccount');

        // 确保数值类型正确
        $params['operator_id'] = (int)$params['operator_id'];
        if (isset($params['alt_account_ids']) && is_array($params['alt_account_ids'])) {
            $params['alt_account_ids'] = array_map('intval', $params['alt_account_ids']);
        }

        // 调用AltAccountLogic的分配方法（已集成套餐分配系统）
        $result = \app\adminapi\logic\AltAccountLogic::assignCustomerService($params, $this->adminId);

        if (true === $result) {
            return $this->success('小号分配成功', [], 1, 1);
        }

        return $this->fail(\app\adminapi\logic\AltAccountLogic::getError());
    }

    /**
     * @notes 检查端口可用性
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function checkPortAvailability()
    {
        $params = (new PackageValidate())->get()->goCheck('checkAvailability');
        $result = PackageLogic::checkPortAvailability((int)$params['tenant_id'], (int)($params['need_count'] ?? 0));

        return $this->data($result);
    }

    /**
     * @notes 获取分配历史
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function assignHistory()
    {
        $params = (new PackageValidate())->get()->goCheck('assignHistory');
        $result = PackageLogic::getAssignHistory($params, $this->adminId);

        if (!empty($result)) {
            // 在控制器中强制格式化时间字段
            if (!empty($result['data'])) {
                foreach ($result['data'] as &$item) {
                    // 强制转换并格式化时间
                    $item['assign_time_text'] = $item['assign_time'] ? date('Y-m-d H:i:s', (int)$item['assign_time']) : '';
                    $item['expire_time_text'] = $item['expire_time'] ? date('Y-m-d H:i:s', (int)$item['expire_time']) : '';
                    $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i:s', (int)$item['create_time']) : '';
                    $item['update_time_text'] = $item['update_time'] ? date('Y-m-d H:i:s', (int)$item['update_time']) : '';
                }
            }
            return $this->data($result);
        }

        return $this->fail(PackageLogic::getError() ?: '获取分配历史失败');
    }

    /**
     * @notes 套餐分配列表
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function lists()
    {
        // 验证查询参数
        $params = (new PackageValidate())->get()->goCheck('lists');

        return $this->dataLists(new PackageLists());
    }

    /**
     * @notes 获取套餐统计信息
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function statistics()
    {
        $lists = new PackageLists();
        $statistics = $lists->getStatistics();
        
        return $this->data($statistics);
    }

    /**
     * @notes 处理过期套餐（定时任务接口）
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function handleExpired()
    {
        $result = PackageLogic::handleExpiredPackages();
        
        if (isset($result['error'])) {
            return $this->fail('处理过期套餐失败：' . $result['error']);
        }
        
        return $this->success('处理完成', $result, 1, 1);
    }

    /**
     * @notes 获取租户端口池状态
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function portPoolStatus()
    {
        $params = (new PackageValidate())->get()->goCheck('tenantPackages');
        $result = PackageLogic::calculatePortPoolStatus((int)$params['tenant_id']);

        return $this->data($result);
    }

    /**
     * @notes 批量更新过期套餐状态
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function updateExpiredStatus()
    {
        $updatedCount = \app\common\model\package\PackageAssignment::updateExpiredPackages();
        
        return $this->success('更新完成', [
            'updated_count' => $updatedCount,
            'update_time' => date('Y-m-d H:i:s')
        ], 1, 1);
    }

    /**
     * @notes 获取套餐详情
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function detail()
    {
        $id = $this->request->get('id/d', 0);
        
        if (empty($id)) {
            return $this->fail('参数错误');
        }
        
        $detail = \app\common\model\package\PackageAssignment::with(['agent', 'tenant'])
            ->findOrEmpty($id);
            
        if ($detail->isEmpty()) {
            return $this->fail('套餐记录不存在');
        }
        
        // 权限验证：非root用户只能查看自己分配的记录
        $currentAdmin = \app\common\model\auth\Admin::findOrEmpty($this->adminId);
        if ($currentAdmin->root != 1 && $detail->agent_id != $this->adminId) {
            return $this->fail('您没有权限查看该记录');
        }
        
        $detailArray = $detail->toArray();
        
        // 添加额外信息
        $detailArray['agent_name'] = $detail->agent->name ?? '未知';
        $detailArray['tenant_name'] = $detail->tenant->name ?? '未知';
        $detailArray['assign_time_text'] = $detail->assign_time ? date('Y-m-d H:i:s', $detail->assign_time) : '';
        $detailArray['expire_time_text'] = $detail->expire_time ? date('Y-m-d H:i:s', $detail->expire_time) : '';
        $detailArray['status_text'] = $detail->status_text;
        $detailArray['remaining_days'] = $detail->remaining_days;
        
        return $this->data($detailArray);
    }

    /**
     * @notes 获取租户列表（用于下拉选择）
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function tenantOptions()
    {
        $currentAdmin = \app\common\model\auth\Admin::findOrEmpty($this->adminId);

        $query = \app\common\model\auth\Admin::field('id,name,account');

        // 权限过滤：非root用户只能看到自己的下级租户
        if ($currentAdmin->root != 1) {
            $query->where('parent_id', $this->adminId);
        }

        $tenants = $query->where('disable', 0)
                        ->order('id', 'desc')
                        ->select();

        // 转换为纯数组，去除模型的额外属性
        $result = [];
        foreach ($tenants as $tenant) {
            $result[] = [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'account' => $tenant->account
            ];
        }

        return $this->data($result);
    }

    /**
     * @notes 获取租户列表（用于下拉选择）- 兼容连字符URL
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function tenant_options()
    {
        return $this->tenantOptions();
    }

    /**
     * @notes 获取客服列表（用于下拉选择）
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function operatorOptions()
    {
        // 获取当前租户的下级客服（运营）
        $operators = \app\common\model\auth\Admin::field('id,name,account')
            ->where('disable', 0)
            ->where('parent_id', $this->adminId) // 客服的上级是当前租户
            ->order('id', 'desc')
            ->select();

        // 转换为纯数组，去除模型的额外属性
        $result = [];
        foreach ($operators as $operator) {
            $result[] = [
                'id' => $operator->id,
                'name' => $operator->name,
                'account' => $operator->account
            ];
        }

        return $this->data($result);
    }

    /**
     * @notes 获取客服列表（用于下拉选择）- 兼容连字符URL
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function operator_options()
    {
        return $this->operatorOptions();
    }

    /**
     * @notes 获取小号列表（用于分配选择）
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function altAccountOptions()
    {
        // 获取未分配的小号列表
        $assignedAccountIds = \app\common\model\package\AltAccountAssignment::where('tenant_id', $this->adminId)
            ->column('alt_account_id');

        $query = \app\common\model\AltAccount::field('id,nickname,phone')
            ->where('tenant_id', $this->adminId)
            ->where('status', 1); // 假设1为正常状态

        if (!empty($assignedAccountIds)) {
            $query->whereNotIn('id', $assignedAccountIds);
        }

        $altAccounts = $query->order('id', 'desc')
                           ->limit(1000) // 限制返回数量
                           ->select()
                           ->toArray();

        return $this->data($altAccounts);
    }

    /**
     * @notes 获取小号列表（用于分配选择）- 兼容连字符URL
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function alt_account_options()
    {
        return $this->altAccountOptions();
    }

    // ==================== 兼容连字符URL的方法 ====================

    /**
     * @notes 获取租户套餐信息 - 兼容连字符URL
     * @return \think\response\Json
     */
    public function tenant_packages()
    {
        return $this->tenantPackages();
    }

    /**
     * @notes 分配小号给客服 - 兼容连字符URL
     * @return \think\response\Json
     */
    public function assign_alt_account()
    {
        return $this->assignAltAccount();
    }

    /**
     * @notes 检查端口可用性 - 兼容连字符URL
     * @return \think\response\Json
     */
    public function check_port_availability()
    {
        return $this->checkPortAvailability();
    }

    /**
     * @notes 获取分配历史 - 兼容连字符URL
     * @return \think\response\Json
     */
    public function assign_history()
    {
        return $this->assignHistory();
    }

    /**
     * @notes 处理过期套餐 - 兼容连字符URL
     * @return \think\response\Json
     */
    public function handle_expired()
    {
        return $this->handleExpired();
    }

    /**
     * @notes 更新过期状态 - 兼容连字符URL
     * @return \think\response\Json
     */
    public function update_expired_status()
    {
        return $this->updateExpiredStatus();
    }

    /**
     * @notes 套餐续费
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function renew()
    {
        $params = (new PackageValidate())->post()->goCheck('renew');

        $result = PackageLogic::renew($params, $this->adminId);

        if (true === $result) {
            return $this->success('套餐续费成功', [], 1, 1);
        }

        return $this->fail(PackageLogic::getError());
    }

    /**
     * @notes 批量续费套餐
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function batchRenew()
    {
        $params = (new PackageValidate())->post()->goCheck('batchRenew');

        $result = PackageLogic::batchRenew($params, $this->adminId);

        if (true === $result) {
            return $this->success('批量续费成功', [], 1, 1);
        }

        return $this->fail(PackageLogic::getError());
    }

    /**
     * @notes 获取可续费套餐列表
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function renewablePackages()
    {
        $params = (new PackageValidate())->get()->goCheck('tenantPackages');

        // 获取租户的所有套餐（包括即将过期和已过期的）
        $packages = \app\common\model\package\PackageAssignment::where('tenant_id', $params['tenant_id'])
            ->where('agent_id', $this->adminId) // 只能续费自己分配的套餐
            ->field('id,tenant_id,port_count,assign_time,expire_time,status,remark')
            ->with(['tenant' => function($query) {
                $query->field('id,name,account');
            }])
            ->order('expire_time', 'asc')
            ->select()
            ->toArray();

        // 添加状态信息
        foreach ($packages as &$package) {
            $package['remaining_days'] = max(0, ceil(($package['expire_time'] - time()) / 86400));
            $package['is_expired'] = $package['expire_time'] <= time();
            $package['is_expiring_soon'] = $package['expire_time'] > time() && $package['expire_time'] <= time() + (7 * 24 * 60 * 60);
        }

        return $this->data($packages);
    }

    /**
     * @notes 获取虚拟端口池状态 - 兼容连字符URL
     * @return \think\response\Json
     */
    public function port_pool_status()
    {
        return $this->portPoolStatus();
    }

    /**
     * @notes 套餐续费 - 兼容连字符URL
     * @return \think\response\Json
     */
    public function batch_renew()
    {
        return $this->batchRenew();
    }

    /**
     * @notes 获取可续费套餐列表 - 兼容连字符URL
     * @return \think\response\Json
     */
    public function renewable_packages()
    {
        return $this->renewablePackages();
    }
}
