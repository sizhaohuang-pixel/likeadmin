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

namespace app\adminapi\logic;


use app\adminapi\logic\auth\OperatorLogic;
use app\common\model\AltAccount;
use app\common\model\AltAccountDeleteRecord;
use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;
use app\common\logic\BaseLogic;
use app\common\service\AdminHierarchyService;
use app\common\service\PortStatisticsService;
use app\common\model\package\AltAccountAssignment;
use think\facade\Db;


/**
 * AltAccount逻辑
 * Class AltAccountLogic
 * @package app\adminapi\logic
 */
class AltAccountLogic extends BaseLogic
{


    /**
     * @notes 添加
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function add(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 设置默认头像
            $defaultAvatar = config('project.default_image.user_avatar');
            $avatar = !empty($params['avatar']) ? $params['avatar'] : $defaultAvatar;

            // 从accesstoken自动解析系统平台类型
            $platform = self::parsePlatformFromToken($params['accesstoken']);

            AltAccount::create([
                'tenant_id' => $currentAdminId, // 自动设置tenant_id为当前用户ID
                'avatar' => $avatar, // 自动填充默认头像
                'area_code' => $params['area_code'],
                'phone' => $params['phone'],
                'password' => $params['password'],
                'mid' => $params['mid'],
                'platform' => $platform, // 自动解析的平台类型
                'accesstoken' => $params['accesstoken'],
                'refreshtoken' => $params['refreshtoken'],
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 编辑
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function edit(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 权限验证：检查小号是否属于当前租户
            $altAccount = AltAccount::findOrEmpty($params['id']);
            if ($altAccount->isEmpty()) {
                throw new \Exception('小号不存在');
            }

            if ($altAccount->tenant_id != $currentAdminId) {
                throw new \Exception('您没有权限编辑该小号');
            }

            $updateData = [
                'area_code' => $params['area_code'] ?? '',
                'phone' => $params['phone'] ?? '',
                'password' => $params['password'] ?? '',
                'mid' => $params['mid'] ?? '',
            ];

            // 只有在提供了accesstoken时才解析平台类型并更新令牌相关字段
            if (!empty($params['accesstoken'])) {
                $platform = self::parsePlatformFromToken($params['accesstoken']);
                $updateData['platform'] = $platform;
                $updateData['accesstoken'] = $params['accesstoken'];
            }

            // 只有在提供了refreshtoken时才更新
            if (!empty($params['refreshtoken'])) {
                $updateData['refreshtoken'] = $params['refreshtoken'];
            }

            // 只有在提供了avatar时才更新
            if (isset($params['avatar'])) {
                $updateData['avatar'] = $params['avatar'];
            }

            // 只有在提供了nickname时才更新
            if (isset($params['nickname'])) {
                $updateData['nickname'] = $params['nickname'];
            }

            // 如果传入了代理URL，则更新
            if (isset($params['proxy_url'])) {
                $updateData['proxy_url'] = $params['proxy_url'];
            }

            AltAccount::where('id', $params['id'])->update($updateData);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 删除
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function delete(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 判断是单个删除还是批量删除
            if (isset($params['ids']) && is_array($params['ids'])) {
                // 批量删除
                $result = self::batchDelete($params['ids'], $currentAdminId);
            } else {
                // 单个删除
                $result = self::singleDelete($params['id'], $currentAdminId);
            }

            Db::commit();
            return $result;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 单个删除
     * @param int $id
     * @param int $currentAdminId
     * @return bool
     * @author likeadmin
     * @date 2025/01/01 00:00
     */
    private static function singleDelete(int $id, int $currentAdminId): bool
    {
        // 权限验证：检查小号是否属于当前租户
        $altAccount = AltAccount::findOrEmpty($id);
        if ($altAccount->isEmpty()) {
            throw new \Exception('小号不存在');
        }

        if ($altAccount->tenant_id != $currentAdminId) {
            throw new \Exception('您没有权限删除该小号');
        }

        // 保存删除记录
        self::saveDeleteRecord($currentAdminId, $currentAdminId, 1);

        // 删除分配关系（如果存在）- 释放端口
        AltAccountAssignment::where('alt_account_id', $id)->delete();

        // 删除小号本身
        return AltAccount::destroy($id);
    }

    /**
     * @notes 批量删除
     * @param array $ids
     * @param int $currentAdminId
     * @return bool
     * @author likeadmin
     * @date 2025/01/01 00:00
     */
    private static function batchDelete(array $ids, int $currentAdminId): bool
    {
        // 验证所有小号是否属于当前租户
        foreach ($ids as $id) {
            $altAccount = AltAccount::findOrEmpty($id);
            if ($altAccount->isEmpty()) {
                throw new \Exception("小号ID {$id} 不存在");
            }

            if ($altAccount->tenant_id != $currentAdminId) {
                throw new \Exception("您没有权限删除小号ID {$id}");
            }
        }

        // 保存批量删除记录
        self::saveDeleteRecord($currentAdminId, $currentAdminId, count($ids));

        // 批量删除分配关系（如果存在）- 释放端口
        AltAccountAssignment::where('alt_account_id', 'in', $ids)->delete();

        // 批量删除小号
        return AltAccount::destroy($ids);
    }

    /**
     * @notes 从JWT accesstoken中解析系统平台类型
     * @param string $accesstoken
     * @return string
     * @author likeadmin
     * @date 2025/01/05 00:00
     */
    private static function parsePlatformFromToken(string $accesstoken): string
    {
        try {
            // JWT格式：header.payload.signature
            $parts = explode('.', $accesstoken);
            if (count($parts) !== 3) {
                return '';
            }

            // 获取payload部分（中间部分）
            $payload = $parts[1];

            // Base64解码（需要处理URL安全的base64）
            $payload = str_replace(['-', '_'], ['+', '/'], $payload);
            // 补齐padding
            $payload = str_pad($payload, strlen($payload) % 4, '=', STR_PAD_RIGHT);

            $decodedPayload = base64_decode($payload);
            if ($decodedPayload === false) {
                return '';
            }

            // JSON解析
            $payloadData = json_decode($decodedPayload, true);
            if (!is_array($payloadData) || !isset($payloadData['ctype'])) {
                return '';
            }

            // 返回ctype字段值
            return $payloadData['ctype'] ?? '';
        } catch (\Exception $e) {
            // 解析失败返回空字符串
            return '';
        }
    }


    /**
     * @notes 获取详情
     * @param $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function detail($params, int $currentAdminId = 0): array
    {
        // 权限验证：检查小号是否属于当前租户
        $altAccount = AltAccount::findOrEmpty($params['id']);
        if ($altAccount->isEmpty()) {
            throw new \Exception('小号不存在');
        }

        if ($altAccount->tenant_id != $currentAdminId) {
            throw new \Exception('您没有权限查看该小号详情');
        }

        return $altAccount->toArray();
    }


    /**
     * @notes 分配客服（运营）
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function assignCustomerService(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            $altAccountIds = array_map('intval', $params['alt_account_ids']);
            $operatorId = (int)$params['operator_id'];

            // 1. 权限验证：运营是否为当前租户的下级
            if (!AdminHierarchyService::hasPermission($currentAdminId, $operatorId)) {
                throw new \Exception('您没有权限将小号分配给该运营人员');
            }

            // 2. 验证运营是否具有运营角色
            $hasOperatorRole = AdminRole::where('admin_id', $operatorId)
                ->where('role_id', OperatorLogic::$operatorRoleId)
                ->find();
            if (!$hasOperatorRole) {
                throw new \Exception('选择的人员不是运营角色');
            }

            // 3. 检查端口可用性（基于套餐分配）
            $needCount = count($altAccountIds);
            $availability = self::checkPortAvailability($currentAdminId, $needCount);
            if (!$availability['available']) {
                throw new \Exception('端口不足，当前可用端口：' . $availability['available_ports'] . '个，需要：' . $needCount . '个');
            }

            // 3.5. 检查客服的账号分配上限（使用悲观锁确保并发安全）
            $canAssign = Admin::canAssignAccounts($operatorId, $needCount, true);
            if (!$canAssign['can_assign']) {
                throw new \Exception($canAssign['message']);
            }

            // 4. 验证所有小号是否属于当前租户且未被分配
            foreach ($altAccountIds as $altAccountId) {
                $altAccount = AltAccount::findOrEmpty($altAccountId);
                if ($altAccount->isEmpty()) {
                    throw new \Exception("小号ID {$altAccountId} 不存在");
                }

                if ($altAccount->tenant_id != $currentAdminId) {
                    throw new \Exception("您没有权限操作小号ID {$altAccountId}");
                }

                if ($altAccount->operator_id > 0) {
                    throw new \Exception("小号ID {$altAccountId} 已被分配给其他客服");
                }
            }

            // 5. 执行分配操作（按套餐优先级分配）
            $assignResult = self::assignAccountsWithPackagePriority($altAccountIds, $currentAdminId, $operatorId);
            if (!$assignResult) {
                throw new \Exception('分配失败：无法按套餐优先级分配');
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 检查端口可用性（基于现有表结构）
     * @param int $tenantId 租户ID
     * @param int $needCount 需要的端口数
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private static function checkPortAvailability(int $tenantId, int $needCount): array
    {
        return PortStatisticsService::checkPortAvailability($tenantId, $needCount);
    }


    /**
     * @notes 获取可分配的运营列表
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function getAvailableOperators(int $currentAdminId = 0): array
    {
        try {
            // 获取当前管理员的下级ID列表
            $subordinateIds = AdminHierarchyService::getSubordinateIds($currentAdminId);

            if (empty($subordinateIds)) {
                return [];
            }

            // 获取具有运营角色的管理员ID
            $operatorAdminIds = AdminRole::where('role_id', OperatorLogic::$operatorRoleId)
                ->column('admin_id');

            // 取交集：既是下级，又是运营角色
            $availableOperatorIds = array_intersect($subordinateIds, $operatorAdminIds);

            if (empty($availableOperatorIds)) {
                return [];
            }

            // 查询运营信息
            return Admin::where('id', 'in', $availableOperatorIds)
                ->where('disable', 0) // 只查询启用的运营
                ->field(['id', 'name', 'account', 'avatar'])
                ->order('id', 'asc')
                ->select()
                ->toArray();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [];
        }
    }


    /**
     * @notes 批量设置小号分组
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function batchSetGroup(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            $altAccountIds = $params['alt_account_ids'];
            $groupId = $params['group_id'];

            // 如果设置分组ID不为0，验证分组是否属于当前租户
            if ($groupId > 0) {
                $group = \app\common\model\AltAccountGroup::findOrEmpty($groupId);
                if ($group->isEmpty()) {
                    throw new \Exception('目标分组不存在');
                }

                if ($group->tenant_id != $currentAdminId) {
                    throw new \Exception('您没有权限将小号分配到该分组');
                }
            }

            // 验证所有小号是否属于当前租户
            foreach ($altAccountIds as $altAccountId) {
                $altAccount = AltAccount::findOrEmpty($altAccountId);
                if ($altAccount->isEmpty()) {
                    throw new \Exception("小号ID {$altAccountId} 不存在");
                }

                if ($altAccount->tenant_id != $currentAdminId) {
                    throw new \Exception("您没有权限操作小号ID {$altAccountId}");
                }
            }

            // 批量更新小号的分组
            $updateData = ['group_id' => $groupId > 0 ? $groupId : null];
            AltAccount::where('id', 'in', $altAccountIds)->update($updateData);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 按套餐优先级分配小号（优先使用最早的套餐）
     * @param array $altAccountIds 小号ID数组
     * @param int $tenantId 租户ID
     * @param int $operatorId 客服ID
     * @return bool
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private static function assignAccountsWithPackagePriority(array $altAccountIds, int $tenantId, int $operatorId): bool
    {
        // 获取租户的套餐按优先级排序（最早分配的优先）
        $packages = \app\common\model\package\PackageAssignment::getTenantPackagesByPriority($tenantId, true);

        if (empty($packages)) {
            // 如果没有有效套餐，使用旧的分配方式（不记录套餐ID）
            return AltAccount::where('id', 'in', $altAccountIds)->update([
                'operator_id' => $operatorId,
                'package_id' => null,
                'update_time' => time()
            ]) !== false;
        }

        // 计算当前各套餐的使用情况（使用新机制：基于package_id统计）
        $currentUsedPorts = AltAccount::where('tenant_id', $tenantId)
            ->where('package_id', '>', 0)  // 使用package_id统计，确保精确性
            ->count();

        $allocationDetails = \app\common\model\package\PackageAssignment::calculatePortAllocationDetails($tenantId, $currentUsedPorts);

        // 按优先级分配小号到套餐
        $assignmentPlan = [];
        $remainingAccounts = count($altAccountIds);

        foreach ($allocationDetails as $detail) {
            if ($remainingAccounts <= 0) break;

            $availableInPackage = $detail['port_free'];
            if ($availableInPackage > 0) {
                $assignToThisPackage = min($remainingAccounts, $availableInPackage);
                $assignmentPlan[] = [
                    'package_id' => $detail['package_id'],
                    'count' => $assignToThisPackage
                ];
                $remainingAccounts -= $assignToThisPackage;
            }
        }

        // 执行分配
        $accountIndex = 0;
        foreach ($assignmentPlan as $plan) {
            $accountsForThisPackage = array_slice($altAccountIds, $accountIndex, $plan['count']);

            AltAccount::where('id', 'in', $accountsForThisPackage)->update([
                'operator_id' => $operatorId,
                'package_id' => $plan['package_id'],
                'update_time' => time()
            ]);

            $accountIndex += $plan['count'];
        }

        return true;
    }

    /**
     * @notes 批量导入小号
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     * @author likeadmin
     * @date 2025/01/05 16:00
     */
    public static function batchImport(array $params, int $currentAdminId = 0): array
    {
        $result = [
            'total_lines' => 0,
            'valid_lines' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'skipped_lines' => 0,
            'errors' => [],
            'import_time' => date('Y-m-d H:i:s')
        ];

        try {
            // 解析文件内容
            $lines = explode("\n", $params['file_content']);
            $result['total_lines'] = count($lines);

            // 过滤空行和注释行
            $validLines = [];
            $lineNumber = 0;
            foreach ($lines as $line) {
                $lineNumber++;
                $line = trim($line);

                // 跳过空行和注释行
                if (empty($line) || strpos($line, '#') === 0) {
                    $result['skipped_lines']++;
                    continue;
                }

                $validLines[] = [
                    'line_number' => $lineNumber,
                    'content' => $line
                ];
            }

            $result['valid_lines'] = count($validLines);

            // 检查导入数量限制
            if (count($validLines) > 1000) {
                self::setError('单次导入不能超过1000条记录');
                return $result;
            }

            // 解析和验证数据
            $importData = [];
            $existingMids = [];

            foreach ($validLines as $lineInfo) {
                $lineNumber = $lineInfo['line_number'];
                $line = $lineInfo['content'];

                // 验证行格式
                $parts = explode('----', $line);
                if (count($parts) !== 3) {
                    $result['errors'][] = [
                        'line_number' => $lineNumber,
                        'line_content' => $line,
                        'error_message' => '行格式错误，应为：mid----accesstoken----refreshtoken',
                        'error_code' => 'INVALID_FORMAT'
                    ];
                    $result['failed_count']++;
                    continue;
                }

                $mid = trim($parts[0]);
                $accesstoken = trim($parts[1]);
                $refreshtoken = trim($parts[2]);

                // 验证必填字段
                if (empty($mid)) {
                    $result['errors'][] = [
                        'line_number' => $lineNumber,
                        'line_content' => $line,
                        'error_message' => 'mid字段不能为空',
                        'error_code' => 'EMPTY_MID'
                    ];
                    $result['failed_count']++;
                    continue;
                }

                if (empty($accesstoken)) {
                    $result['errors'][] = [
                        'line_number' => $lineNumber,
                        'line_content' => $line,
                        'error_message' => 'accesstoken字段不能为空',
                        'error_code' => 'EMPTY_ACCESSTOKEN'
                    ];
                    $result['failed_count']++;
                    continue;
                }

                if (empty($refreshtoken)) {
                    $result['errors'][] = [
                        'line_number' => $lineNumber,
                        'line_content' => $line,
                        'error_message' => 'refreshtoken字段不能为空',
                        'error_code' => 'EMPTY_REFRESHTOKEN'
                    ];
                    $result['failed_count']++;
                    continue;
                }

                // 检查mid重复（数据库中）
                $existingAccount = AltAccount::where('mid', $mid)->where('tenant_id', $currentAdminId)->findOrEmpty();
                if (!$existingAccount->isEmpty()) {
                    $result['errors'][] = [
                        'line_number' => $lineNumber,
                        'line_content' => $line,
                        'error_message' => 'mid已存在',
                        'error_code' => 'DUPLICATE_MID'
                    ];
                    $result['failed_count']++;
                    continue;
                }

                // 检查mid重复（导入数据内部）
                if (in_array($mid, $existingMids)) {
                    $result['errors'][] = [
                        'line_number' => $lineNumber,
                        'line_content' => $line,
                        'error_message' => 'mid在导入数据中重复',
                        'error_code' => 'DUPLICATE_MID_IN_IMPORT'
                    ];
                    $result['failed_count']++;
                    continue;
                }

                // 验证JWT格式
                $platform = self::parsePlatformFromToken($accesstoken);
                if (empty($platform)) {
                    $result['errors'][] = [
                        'line_number' => $lineNumber,
                        'line_content' => $line,
                        'error_message' => 'accesstoken格式无效',
                        'error_code' => 'INVALID_JWT'
                    ];
                    $result['failed_count']++;
                    continue;
                }

                // 数据验证通过，添加到导入列表
                $existingMids[] = $mid;
                $importData[] = [
                    'line_number' => $lineNumber,
                    'mid' => $mid,
                    'accesstoken' => $accesstoken,
                    'refreshtoken' => $refreshtoken,
                    'platform' => $platform,
                    'original_line' => $line
                ];
            }

            // 如果有验证错误且采用"全部成功或全部失败"模式，直接返回
            if (!empty($result['errors'])) {
                // 统计错误类型，不返回详细错误信息
                $errorStats = [];
                foreach ($result['errors'] as $error) {
                    $errorCode = $error['error_code'];
                    if (!isset($errorStats[$errorCode])) {
                        $errorStats[$errorCode] = 0;
                    }
                    $errorStats[$errorCode]++;
                }

                $result['errors'] = [];
                $result['error_stats'] = $errorStats;
                return $result;
            }

            // 执行批量导入
            return self::executeBatchImport($importData, $params['group_id'], $currentAdminId, $result);

        } catch (\Exception $e) {
            self::setError('批量导入异常: ' . $e->getMessage() . ' 文件: ' . $e->getFile() . ' 行号: ' . $e->getLine());
            $result['errors'][] = [
                'line_number' => 0,
                'line_content' => '',
                'error_message' => $e->getMessage(),
                'error_code' => 'SYSTEM_ERROR'
            ];
            return $result;
        }
    }

    /**
     * @notes 执行批量导入
     * @param array $importData
     * @param int $groupId
     * @param int $currentAdminId
     * @param array $result
     * @return array
     * @author likeadmin
     * @date 2025/01/05 16:00
     */
    private static function executeBatchImport(array $importData, int $groupId, int $currentAdminId, array $result): array
    {
        Db::startTrans();
        try {
            // 验证分组权限
            $group = \app\common\model\AltAccountGroup::findOrEmpty($groupId);
            if ($group->isEmpty()) {
                throw new \Exception('目标分组不存在');
            }

            if ($group->tenant_id != $currentAdminId) {
                throw new \Exception('您没有权限将小号分配到该分组');
            }

            // 设置默认头像
            $defaultAvatar = config('project.default_image.user_avatar');

            // 逐条插入数据，处理重复等错误
            $successCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($importData as $index => $data) {
                try {
                    $insertData = [
                        'tenant_id' => $currentAdminId,
                        'group_id' => $groupId,
                        'operator_id' => 0,
                        'avatar' => $defaultAvatar,
                        'nickname' => '',
                        'area_code' => '',
                        'phone' => '',
                        'password' => '',
                        'mid' => $data['mid'],
                        'platform' => $data['platform'],
                        'accesstoken' => $data['accesstoken'],
                        'refreshtoken' => $data['refreshtoken'],
                        'status' => 1,
                        'create_time' => time(),
                        'update_time' => time()
                    ];

                    $altAccount = new AltAccount();
                    $insertResult = $altAccount->save($insertData);

                    if ($insertResult) {
                        $successCount++;
                    } else {
                        $failedCount++;
                        $errors[] = [
                            'line_number' => $index + 1,
                            'line_content' => $data['original_line'],
                            'error_message' => '插入失败',
                            'error_code' => 'INSERT_FAILED'
                        ];
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errorMessage = $e->getMessage();
                    $errorCode = 'UNKNOWN_ERROR';

                    // 判断错误类型
                    if (strpos($errorMessage, 'Duplicate') !== false || strpos($errorMessage, '1062') !== false) {
                        if (strpos($errorMessage, 'mid') !== false) {
                            $errorMessage = 'mid已存在';
                            $errorCode = 'DUPLICATE_MID';
                        } else {
                            $errorMessage = '数据重复';
                            $errorCode = 'DUPLICATE_DATA';
                        }
                    }

                    $errors[] = [
                        'line_number' => $index + 1,
                        'line_content' => $data['original_line'],
                        'error_message' => $errorMessage,
                        'error_code' => $errorCode
                    ];
                }
            }

            $result['success_count'] = $successCount;
            $result['failed_count'] = $failedCount;

            // 只返回错误统计，不返回详细错误信息
            $result['errors'] = [];

            // 统计错误类型
            $errorStats = [];
            foreach ($errors as $error) {
                $errorCode = $error['error_code'];
                if (!isset($errorStats[$errorCode])) {
                    $errorStats[$errorCode] = 0;
                }
                $errorStats[$errorCode]++;
            }
            $result['error_stats'] = $errorStats;

            Db::commit();
            return $result;

        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            $result['errors'][] = [
                'line_number' => 0,
                'line_content' => '',
                'error_message' => $e->getMessage(),
                'error_code' => 'BATCH_INSERT_FAILED'
            ];
            $result['failed_count'] = count($importData);
            $result['success_count'] = 0;
            return $result;
        }
    }

    /**
     * @notes 设置代理
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/09/06 22:57
     */
    public static function setProxy(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            $altAccountIds = array_map('intval', $params['alt_account_ids']);
            $proxyUrl = trim($params['proxy_url'] ?? '');

            // 验证所有小号是否属于当前租户
            foreach ($altAccountIds as $altAccountId) {
                $altAccount = AltAccount::findOrEmpty($altAccountId);
                if ($altAccount->isEmpty()) {
                    throw new \Exception("小号ID {$altAccountId} 不存在");
                }

                if ($altAccount->tenant_id != $currentAdminId) {
                    throw new \Exception("您没有权限操作小号ID {$altAccountId}");
                }
            }

            // 批量更新代理设置
            AltAccount::where('id', 'in', $altAccountIds)->update([
                'proxy_url' => $proxyUrl,
                'update_time' => time()
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量设置代理
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/09/06 22:57
     */
    public static function batchSetProxy(array $params, int $currentAdminId = 0): bool
    {
        // 批量设置代理与单个设置代理逻辑相同
        return self::setProxy($params, $currentAdminId);
    }

    /**
     * @notes 清除代理设置
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/09/06 22:57
     */
    public static function clearProxy(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            $altAccountIds = array_map('intval', $params['alt_account_ids']);

            // 验证所有小号是否属于当前租户
            foreach ($altAccountIds as $altAccountId) {
                $altAccount = AltAccount::findOrEmpty($altAccountId);
                if ($altAccount->isEmpty()) {
                    throw new \Exception("小号ID {$altAccountId} 不存在");
                }

                if ($altAccount->tenant_id != $currentAdminId) {
                    throw new \Exception("您没有权限操作小号ID {$altAccountId}");
                }
            }

            // 批量清除代理设置
            AltAccount::where('id', 'in', $altAccountIds)->update([
                'proxy_url' => null,
                'update_time' => time()
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取代理统计信息
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     * @author likeadmin
     * @date 2025/09/06 22:57
     */
    public static function getProxyStatistics(int $currentAdminId = 0): array
    {
        try {
            $totalCount = AltAccount::where('tenant_id', $currentAdminId)->count();
            $proxyEnabledCount = AltAccount::where('tenant_id', $currentAdminId)
                ->where('proxy_url', '<>', '')
                ->whereNotNull('proxy_url')
                ->count();
            $proxyDisabledCount = $totalCount - $proxyEnabledCount;

            return [
                'total_count' => $totalCount,
                'proxy_enabled_count' => $proxyEnabledCount,
                'proxy_disabled_count' => $proxyDisabledCount,
                'proxy_enabled_rate' => $totalCount > 0 ? round($proxyEnabledCount / $totalCount * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [
                'total_count' => 0,
                'proxy_enabled_count' => 0,
                'proxy_disabled_count' => 0,
                'proxy_enabled_rate' => 0
            ];
        }
    }

    /**
     * @notes 保存删除记录
     * @param int $tenantId 租户ID
     * @param int $operatorAdminId 操作人ID
     * @param int $deleteCount 删除数量
     * @return bool
     * @author likeadmin
     * @date 2025/09/06 23:00
     */
    private static function saveDeleteRecord(int $tenantId, int $operatorAdminId, int $deleteCount): bool
    {
        try {
            AltAccountDeleteRecord::create([
                'tenant_id' => $tenantId,
                'operator_admin_id' => $operatorAdminId,
                'delete_time' => time(),
                'delete_count' => $deleteCount,
                'create_time' => time(),
                'update_time' => time()
            ]);
            return true;
        } catch (\Exception $e) {
            // 删除记录保存失败不影响删除操作，只记录错误日志
            error_log('保存删除记录失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 账号验活
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     * @author 段誉
     * @date 2025/09/08
     */
    public static function verify(array $params, int $currentAdminId = 0): array
    {
        try {
            $accountId = $params['id'];
            
            // 查询账号信息
            $account = AltAccount::findOrEmpty($accountId);
            if ($account->isEmpty()) {
                return [
                    'success' => false,
                    'message' => '账号不存在'
                ];
            }

            // 检查账号是否属于当前租户或管理员有权限访问
            $adminInfo = Admin::findOrEmpty($currentAdminId);
            if ($adminInfo->isEmpty()) {
                return [
                    'success' => false,
                    'message' => '当前用户不存在'
                ];
            }

            // root用户可以操作所有账号
            if ($adminInfo->root != 1) {
                // 非root用户只能操作自己租户下的账号
                if ($account->tenant_id != $currentAdminId) {
                    return [
                        'success' => false,
                        'message' => '没有权限操作此账号'
                    ];
                }
            }

            // 检查必需字段
            if (empty($account->mid)) {
                return [
                    'success' => false,
                    'message' => '账号MID不能为空'
                ];
            }

            if (empty($account->accesstoken)) {
                return [
                    'success' => false,
                    'message' => '访问令牌不能为空'
                ];
            }

            if (empty($account->proxy_url)) {
                return [
                    'success' => false,
                    'message' => '代理地址不能为空'
                ];
            }

            // 调用LINE API进行验活
            $result = \app\common\service\LineApiService::verifyAccount(
                $account->mid,
                $account->accesstoken,
                $account->proxy_url
            );
            
            // 如果返回状态为3（下线），尝试刷新Token
            if (in_array($result['code'], [3,5])) {
                // 检查是否有refreshtoken
                if (!empty($account->refreshtoken)) {
                    $refreshResult = \app\common\service\LineApiService::refreshToken(
                        $account->mid,
                        $account->accesstoken,
                        $account->refreshtoken,
                        $account->proxy_url
                    );

                    if ($refreshResult['success']) {
                        // Token刷新成功，更新数据库中的token信息
                        $account->accesstoken = $refreshResult['data']['accessToken'];
                        $account->refreshtoken = $refreshResult['data']['refreshToken'];
                        $account->save();

                        // 使用新token重新验活
                        $result = \app\common\service\LineApiService::verifyAccount(
                            $account->mid,
                            $account->accesstoken,
                            $account->proxy_url
                        );

                        // 添加刷新成功的提示
                        $result['token_refreshed'] = true;
                        $result['message'] = '账号Token已刷新，' . $result['message'];
                    } else {
                        // Token刷新失败，返回刷新失败的原因
                        $result['token_refresh_failed'] = true;
                        $result['refresh_message'] = $refreshResult['message'];
                        $result['message'] = '下线（Token刷新失败：' . $refreshResult['message'] . '）';
                    }
                } else {
                    // 没有refreshtoken，无法刷新
                    $result['no_refresh_token'] = true;
                    $result['message'] = '下线（无法刷新Token：缺少RefreshToken）';
                }
            }

            // 根据最终验活结果更新账号状态
            $newStatus = self::mapVerifyCodeToStatus($result['code']);
            if ($newStatus !== null) {
                $account->status = $newStatus;
                $account->save();
            }

            // 返回验活结果 - 无论LINE API结果如何，都算验活完成
            return [
                'success' => true, // 验活操作本身成功完成
                'message' => $result['message'],
                'code' => $result['code'],
                'status' => $result['status'] ?? '',
                'data' => $result['data'],
                'account_info' => [
                    'id' => $account->id,
                    'nickname' => $account->nickname,
                    'mid' => $account->mid,
                    'phone' => $account->area_code . $account->phone
                ],
                'updated_status' => $newStatus // 返回更新后的状态
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '验活过程中发生异常：' . $e->getMessage()
            ];
        }
    }

    /**
     * @notes 将验活状态码映射为数据库状态
     * @param int $verifyCode 验活状态码
     * @return int|null
     * @author 段誉
     * @date 2025/09/08
     */
    private static function mapVerifyCodeToStatus(int $verifyCode): ?int
    {
        // LINE API状态码与数据库状态字段完全一致
        // 1=正常, 2=代理不可用, 3=下线, 4=封禁
        switch ($verifyCode) {
            case 1: // LINE API：正常
                return 1; // 数据库：正常
            case 2: // LINE API：代理不可用
                return 2; // 数据库：代理不可用
            case 3: // LINE API：下线
                return 3; // 数据库：下线
            case 4: // LINE API：封禁
                return 4; // 数据库：封禁
            default:
                return null; // 未知状态不更新
        }
    }
}