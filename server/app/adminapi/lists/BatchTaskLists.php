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

namespace app\adminapi\lists;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\BatchTask;

/**
 * 批量任务列表
 * Class BatchTaskLists
 * @package app\adminapi\lists
 */
class BatchTaskLists extends BaseAdminDataLists
{
    public function __construct($params = [])
    {
        parent::__construct();
        // 合并传入的参数到现有参数中
        if (!empty($params)) {
            $this->params = array_merge($this->params, $params);
        }
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author Claude
     * @date 2025/09/08
     */
    public function setSearch(): array
    {
        $allowSearch = ['task_name', 'task_type', 'task_status', 'create_admin_id'];
        return array_intersect(array_keys($this->params), $allowSearch);
    }

    /**
     * @notes 获取列表
     * @return array
     * @author Claude
     * @date 2025/09/08
     */
    public function lists(): array
    {
        // 强制按租户隔离数据
        $tenantId = $this->params['tenant_id'] ?? 0;
        if (!$tenantId) {
            return [
                'lists' => [],
                'count' => 0,
                'page_no' => 1,
                'page_size' => $this->pageSize,
                'page_count' => 0,
                'extend' => null
            ];
        }

        $field = [
            'id',
            'task_name', 
            'task_type',
            'task_status',
            'total_count',
            'processed_count',
            'success_count',
            'failed_count',
            'create_admin_id',
            'start_time',
            'end_time',
            'create_time',
            'error_message'
        ];

        $lists = BatchTask::where('tenant_id', $tenantId)
            ->where($this->setWhere())
            ->with(['createAdmin' => function($query) {
                $query->field('id,name');
            }])
            ->field($field)
            ->append([
                'task_status_desc',
                'task_type_desc',
                'progress_percent',
                'success_rate',
                'start_time_text',
                'end_time_text',
                'duration_text'
            ])
            ->order('create_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        return $lists;
    }

    /**
     * @notes 设置搜索条件
     * @return array
     * @author Claude  
     * @date 2025/09/08
     */
    public function setWhere(): array
    {
        $where = [];

        if (!empty($this->params['task_name'])) {
            $where[] = ['task_name', 'like', '%' . $this->params['task_name'] . '%'];
        }

        if (!empty($this->params['task_type'])) {
            $where[] = ['task_type', '=', $this->params['task_type']];
        }

        if (!empty($this->params['task_status'])) {
            $where[] = ['task_status', '=', $this->params['task_status']];
        }

        if (!empty($this->params['create_admin_id'])) {
            $where[] = ['create_admin_id', '=', $this->params['create_admin_id']];
        }

        // 时间范围搜索
        if (!empty($this->params['start_time']) && !empty($this->params['end_time'])) {
            $startTime = strtotime($this->params['start_time']);
            $endTime = strtotime($this->params['end_time']) + 86399; // 包含结束日期的整天
            $where[] = ['create_time', 'between', [$startTime, $endTime]];
        }

        return $where;
    }

    /**
     * @notes 导出字段
     * @return string[]
     * @author Claude
     * @date 2025/09/08
     */
    public function setExcelFields(): array
    {
        return [
            'id' => '任务ID',
            'task_name' => '任务名称',
            'task_type_desc' => '任务类型',
            'task_status_desc' => '任务状态',
            'total_count' => '总账号数',
            'processed_count' => '已处理数',
            'success_count' => '成功数',
            'failed_count' => '失败数',
            'progress_percent' => '进度(%)',
            'success_rate' => '成功率(%)',
            'create_admin.name' => '创建人',
            'start_time_text' => '开始时间',
            'end_time_text' => '结束时间',
            'duration_text' => '执行耗时',
            'create_time' => '创建时间'
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     * @author Claude
     * @date 2025/09/08
     */
    public function setFileName(): string
    {
        return '批量任务列表_' . date('YmdHis');
    }

    /**
     * @notes 处理导出数据
     * @param $item
     * @return array
     * @author Claude
     * @date 2025/09/08
     */
    public function handleExportData($item): array
    {
        $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        return $item;
    }

    /**
     * @notes 获取记录总数
     * @return int
     * @author Claude
     * @date 2025/09/08
     */
    public function count(): int
    {
        // 强制按租户隔离数据
        $tenantId = $this->params['tenant_id'] ?? 0;
        if (!$tenantId) {
            return 0;
        }

        return BatchTask::where('tenant_id', $tenantId)
            ->where($this->setWhere())
            ->count();
    }
}