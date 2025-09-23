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

namespace app\adminapi\validate;

use app\common\validate\BaseValidate;

/**
 * 批量任务验证器
 * Class BatchTaskValidate
 * @package app\adminapi\validate
 */
class BatchTaskValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'integer|gt:0',
        'task_id' => 'integer|gt:0',
        'task_type' => 'require|in:batch_verify,batch_nickname',
        'search_params' => 'array',
        'account_ids' => 'array',
        'task_name' => 'require|length:1,100',
        'account_group_id' => 'require|integer|egt:0',
        'nickname_group_name' => 'require|length:1,100',
    ];

    protected $message = [
        'id.require' => '任务ID不能为空',
        'id.integer' => '任务ID必须为整数',
        'id.gt' => '任务ID必须大于0',
        'task_id.require' => '任务ID不能为空',
        'task_id.integer' => '任务ID必须为整数',
        'task_id.gt' => '任务ID必须大于0',
        'task_type.require' => '任务类型不能为空',
        'task_type.in' => '任务类型不正确',
        'search_params.array' => '搜索参数格式不正确',
        'account_ids.array' => '账号ID列表格式不正确',
        'task_name.require' => '任务名称不能为空',
        'task_name.length' => '任务名称长度必须在1-100个字符之间',
        'account_group_id.require' => '账号分组不能为空',
        'account_group_id.integer' => '账号分组必须为整数',
        'account_group_id.egt' => '账号分组必须大于等于0',
        'nickname_group_name.require' => '昵称分组不能为空',
        'nickname_group_name.length' => '昵称分组名称长度必须在1-100个字符之间',
    ];

    /**
     * @notes 创建任务场景
     * @return BatchTaskValidate
     * @author Claude
     * @date 2025/09/08
     */
    public function sceneCreate()
    {
        return $this->only(['task_type', 'search_params', 'account_ids'])
            ->append('task_type', 'checkCreateParams');
    }

    /**
     * @notes 任务详情场景
     * @return BatchTaskValidate
     * @author Claude
     * @date 2025/09/08
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 任务详情列表场景
     * @return BatchTaskValidate
     * @author Claude
     * @date 2025/09/22
     */
    public function sceneDetailList()
    {
        return $this->only([]);
    }

    /**
     * @notes 取消任务场景
     * @return BatchTaskValidate
     * @author Claude
     * @date 2025/09/08
     */
    public function sceneCancel()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 创建批量改昵称任务场景
     * @return BatchTaskValidate
     * @author Claude
     * @date 2025/09/22
     */
    public function sceneCreateNickname()
    {
        return $this->only(['task_name', 'account_group_id', 'nickname_group_name']);
    }

    /**
     * @notes 检查创建参数
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author Claude
     * @date 2025/09/08
     */
    public function checkCreateParams($value, $rule, $data)
    {
        // 如果两者都为空，则表示处理所有账号（不需要报错）
        // 这里只检查参数格式是否正确

        // 如果提供了账号ID列表，检查数量限制
        if (!empty($data['account_ids'])) {
            if (!is_array($data['account_ids'])) {
                return '账号ID列表必须是数组格式';
            }

            if (count($data['account_ids']) > 1000) {
                return '单次批量操作不能超过1000个账号';
            }

            // 检查ID格式
            foreach ($data['account_ids'] as $accountId) {
                if (!is_numeric($accountId) || $accountId <= 0) {
                    return '账号ID格式不正确';
                }
            }
        }

        // 如果提供了搜索参数，检查参数格式
        if (!empty($data['search_params'])) {
            if (!is_array($data['search_params'])) {
                return '搜索参数必须是数组格式';
            }

            // 验证搜索参数字段，允许前端账号管理页面的所有搜索条件
            $allowedFields = ['nickname', 'area_code', 'phone', 'password', 'has_nickname', 'has_uid', 'status', 'group_id', 'operator_id', 'proxy_status'];
            foreach ($data['search_params'] as $field => $value) {
                if (!in_array($field, $allowedFields)) {
                    return "不支持的搜索字段: {$field}";
                }
            }
        }

        return true;
    }

    /**
     * @notes 检查任务类型
     * @param $value
     * @return bool|string
     * @author Claude
     * @date 2025/09/08
     */
    public function checkTaskType($value)
    {
        $allowedTypes = [
            'batch_verify' => '批量验活',
            'batch_nickname' => '批量改昵称'
        ];

        if (!isset($allowedTypes[$value])) {
            return '不支持的任务类型';
        }

        return true;
    }

    /**
     * @notes 检查任务ID是否存在（支持id或task_id两种参数名）
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author Claude
     * @date 2025/09/22
     */
    public function checkTaskIdExists($value, $rule, $data)
    {
        // 检查是否至少提供了一个任务ID参数
        $taskId = $data['id'] ?? $data['task_id'] ?? null;
        
        if (empty($taskId)) {
            return '任务ID不能为空';
        }
        
        if (!is_numeric($taskId) || $taskId <= 0) {
            return '任务ID必须是大于0的整数';
        }
        
        return true;
    }
}