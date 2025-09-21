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

use app\common\model\NicknameRepository;
use app\common\logic\BaseLogic;
use app\common\service\AdminHierarchyService;
use think\facade\Db;

/**
 * 昵称仓库逻辑
 * Class NicknameRepositoryLogic
 * @package app\adminapi\logic
 */
class NicknameRepositoryLogic extends BaseLogic
{
    /**
     * 获取分组列表
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     */
    public static function getGroupList(int $currentAdminId = 0): array
    {
        try {
            $stats = NicknameRepository::getGroupStats($currentAdminId);
            
            return [
                'lists' => $stats,
                'count' => count($stats)
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [
                'lists' => [],
                'count' => 0
            ];
        }
    }
    
    /**
     * 获取分组明细
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     */
    public static function getGroupDetail(array $params, int $currentAdminId = 0): array
    {
        try {
            $groupName = $params['group_name'] ?? '';
            $status = isset($params['status']) && $params['status'] !== '' ? (int)$params['status'] : null;
            $page = max(1, (int)($params['page'] ?? 1));
            $limit = max(1, min(100, (int)($params['limit'] ?? 20)));
            
            if (empty($groupName)) {
                throw new \Exception('分组名称不能为空');
            }
            
            $query = NicknameRepository::getGroupNicknames($currentAdminId, $groupName, $status);
            
            // 搜索昵称
            if (!empty($params['nickname'])) {
                $query->where('nickname', 'like', '%' . $params['nickname'] . '%');
            }
            
            $total = $query->count();
            $lists = $query->page($page, $limit)->select()->toArray();
            
            return [
                'lists' => $lists,
                'count' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [
                'lists' => [],
                'count' => 0,
                'page' => 1,
                'limit' => 20,
                'pages' => 0
            ];
        }
    }
    
    /**
     * 添加分组
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     */
    public static function add(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            $groupName = trim($params['group_name'] ?? '');
            
            if (empty($groupName)) {
                throw new \Exception('分组名称不能为空');
            }
            
            // 检查分组是否已存在
            $existCount = NicknameRepository::where('tenant_id', $currentAdminId)
                ->where('group_name', $groupName)
                ->count();
                
            if ($existCount > 0) {
                throw new \Exception('分组名称已存在');
            }
            
            // 创建一个占位记录，确保分组存在
            // 注意：这个占位符在前端显示时会被过滤掉
            NicknameRepository::create([
                'group_name' => $groupName,
                'nickname' => '_placeholder_',
                'tenant_id' => $currentAdminId,
                'status' => NicknameRepository::STATUS_USED,
                'create_time' => time(),
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
     * 编辑分组名称
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     */
    public static function edit(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            $oldGroupName = trim($params['old_group_name'] ?? '');
            $newGroupName = trim($params['new_group_name'] ?? '');
            
            if (empty($oldGroupName) || empty($newGroupName)) {
                throw new \Exception('分组名称不能为空');
            }
            
            if ($oldGroupName === $newGroupName) {
                throw new \Exception('新分组名称与原名称相同');
            }
            
            // 检查新分组名是否已存在
            $existCount = NicknameRepository::where('tenant_id', $currentAdminId)
                ->where('group_name', $newGroupName)
                ->count();
                
            if ($existCount > 0) {
                throw new \Exception('新分组名称已存在');
            }
            
            // 更新所有相关记录
            $updateCount = NicknameRepository::where('tenant_id', $currentAdminId)
                ->where('group_name', $oldGroupName)
                ->update([
                    'group_name' => $newGroupName,
                    'update_time' => time()
                ]);
                
            if ($updateCount === 0) {
                throw new \Exception('分组不存在或没有数据');
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
     * 删除分组
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     */
    public static function delete(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            $groupName = trim($params['group_name'] ?? '');
            
            if (empty($groupName)) {
                throw new \Exception('分组名称不能为空');
            }
            
            // 软删除所有相关记录
            $deleteCount = NicknameRepository::where('tenant_id', $currentAdminId)
                ->where('group_name', $groupName)
                ->delete();
                
            if ($deleteCount === 0) {
                throw new \Exception('分组不存在或已删除');
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
     * 批量导入昵称
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
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
            $groupName = trim($params['group_name'] ?? '');
            $fileContent = $params['file_content'] ?? '';
            
            if (empty($groupName)) {
                throw new \Exception('分组名称不能为空');
            }
            
            if (empty($fileContent)) {
                throw new \Exception('文件内容不能为空');
            }
            
            // 检测文件编码并转换为UTF-8
            $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'GBK', 'GB2312'], true);
            if ($encoding !== 'UTF-8') {
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
            }
            
            // 解析文件内容
            $lines = explode("\n", $fileContent);
            $result['total_lines'] = count($lines);
            
            // 过滤和验证数据
            $validNicknames = [];
            $existingNicknames = [];
            $lineNumber = 0;
            
            foreach ($lines as $line) {
                $lineNumber++;
                $nickname = trim($line);
                
                // 跳过空行和注释行
                if (empty($nickname) || strpos($nickname, '#') === 0) {
                    $result['skipped_lines']++;
                    continue;
                }
                
                // 验证昵称长度
                if (mb_strlen($nickname, 'UTF-8') > 20) {
                    $result['errors'][] = [
                        'line_number' => $lineNumber,
                        'nickname' => $nickname,
                        'error_message' => '昵称长度超过20个字符',
                        'error_code' => 'NICKNAME_TOO_LONG'
                    ];
                    $result['failed_count']++;
                    continue;
                }
                
                // 检查昵称是否已存在（全表范围内检查）
                if (NicknameRepository::nicknameExists($currentAdminId, $nickname)) {
                    // 重复昵称跳过，不记录为错误
                    $result['skipped_lines']++;
                    continue;
                }
                
                // 检查导入数据内部重复
                if (in_array($nickname, $existingNicknames)) {
                    // 导入数据内部重复，跳过不记录为错误
                    $result['skipped_lines']++;
                    continue;
                }
                
                $existingNicknames[] = $nickname;
                $validNicknames[] = [
                    'group_name' => $groupName,
                    'nickname' => $nickname,
                    'tenant_id' => $currentAdminId
                ];
            }
            
            $result['valid_lines'] = count($validNicknames);
            
            // 检查导入数量限制
            if (count($validNicknames) > 5000) {
                throw new \Exception('单次导入不能超过5000条记录');
            }
            
            // 执行批量导入
            if (!empty($validNicknames)) {
                $result['success_count'] = NicknameRepository::batchInsert($validNicknames);
            }
            
            return $result;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            $result['errors'][] = [
                'line_number' => 0,
                'nickname' => '',
                'error_message' => $e->getMessage(),
                'error_code' => 'SYSTEM_ERROR'
            ];
            return $result;
        }
    }
    
    /**
     * 导出昵称
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     */
    public static function export(array $params, int $currentAdminId = 0): array
    {
        try {
            $groupName = trim($params['group_name'] ?? '');
            
            if (empty($groupName)) {
                throw new \Exception('分组名称不能为空');
            }
            
            // 获取可用的昵称
            $nicknames = NicknameRepository::getGroupNicknames($currentAdminId, $groupName, NicknameRepository::STATUS_AVAILABLE)
                ->field(['nickname'])
                ->select()
                ->toArray();
            
            $content = implode("\n", array_column($nicknames, 'nickname'));
            $fileName = $groupName . '_昵称导出_' . date('Y-m-d_H-i-s') . '.txt';
            
            return [
                'success' => true,
                'content' => $content,
                'filename' => $fileName,
                'count' => count($nicknames)
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取统计信息
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     */
    public static function getStatistics(int $currentAdminId = 0): array
    {
        try {
            // 正确的分组总数统计：先查询不重复的分组名，再计数（排除占位符）
            $groupNames = NicknameRepository::where('tenant_id', $currentAdminId)
                ->where('nickname', '<>', '_placeholder_')  // 排除占位符记录
                ->field('group_name')
                ->group('group_name')
                ->column('group_name');
            $totalGroups = count($groupNames);
                
            $totalNicknames = NicknameRepository::where('tenant_id', $currentAdminId)
                ->where('nickname', '<>', '_placeholder_')  // 排除占位符记录
                ->count();
            $availableNicknames = NicknameRepository::where('tenant_id', $currentAdminId)
                ->where('nickname', '<>', '_placeholder_')  // 排除占位符记录
                ->where('status', NicknameRepository::STATUS_AVAILABLE)
                ->count();
            $usedNicknames = $totalNicknames - $availableNicknames;
            
            return [
                'total_groups' => $totalGroups,
                'total_nicknames' => $totalNicknames,
                'available_nicknames' => $availableNicknames,
                'used_nicknames' => $usedNicknames,
                'usage_rate' => $totalNicknames > 0 ? round($usedNicknames / $totalNicknames * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [
                'total_groups' => 0,
                'total_nicknames' => 0,
                'available_nicknames' => 0,
                'used_nicknames' => 0,
                'usage_rate' => 0
            ];
        }
    }
}