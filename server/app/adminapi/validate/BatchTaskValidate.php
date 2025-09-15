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
        'id' => 'require|integer|gt:0',
        'task_type' => 'require|in:batch_verify',
        'search_params' => 'array',
        'account_ids' => 'array',
    ];

    protected $message = [
        'id.require' => '任务ID不能为空',
        'id.integer' => '任务ID必须为整数',
        'id.gt' => '任务ID必须大于0',
        'task_type.require' => '任务类型不能为空',
        'task_type.in' => '任务类型不正确',
        'search_params.array' => '搜索参数格式不正确',
        'account_ids.array' => '账号ID列表格式不正确',
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
            'batch_verify' => '批量验活'
        ];

        if (!isset($allowedTypes[$value])) {
            return '不支持的任务类型';
        }

        return true;
    }
}