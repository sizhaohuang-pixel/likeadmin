<?php
declare (strict_types = 1);

namespace app\adminapi\validate;

use app\common\validate\BaseValidate;

/**
 * 套餐分配验证器
 * Class PackageValidate
 * @package app\adminapi\validate
 */
class PackageValidate extends BaseValidate
{
    /**
     * 设置校验规则
     * @var string[]
     */
    protected $rule = [
        'tenant_id'      => 'require|integer|gt:0',
        'port_count'     => 'require|integer|between:1,10000',
        'expire_days'    => 'require|integer|between:1,3650',
        'remark'         => 'max:255',
        'alt_account_ids' => 'require|array',
        'operator_id'    => 'require|integer|gt:0',
        'need_count'     => 'integer|egt:0',
        'status'         => 'in:0,1',
        'time_range'     => 'array',
        'page'           => 'integer|egt:1',
        'limit'          => 'integer|between:1,100',
        'package_id'     => 'require|integer|gt:0',
        'extend_days'    => 'require|integer|between:1,3650',
        'package_ids'    => 'require|array',

        // 列表查询参数
        'agent_id'       => 'integer|gt:0',
        'start_time'     => 'date',
        'end_time'       => 'date',
        'expire_status'  => 'in:valid,expired,expiring_soon',
        'port_count_min' => 'integer|egt:0',
        'port_count_max' => 'integer|egt:0',
        'remaining_days' => 'integer|egt:0',
        'remaining_days_operator' => 'in:lt,lte,gt,gte,eq',
        'assign_start_time' => 'date',
        'assign_end_time' => 'date',
        'expire_start_time' => 'date',
        'expire_end_time' => 'date',
        'remark_keyword' => 'max:100',
    ];

    /**
     * 参数描述
     * @var string[]
     */
    protected $field = [
        'tenant_id'       => '租户ID',
        'port_count'      => '端口数量',
        'expire_days'     => '有效天数',
        'remark'          => '备注',
        'alt_account_ids' => '小号ID列表',
        'operator_id'     => '客服ID',
        'need_count'      => '需要端口数',
        'status'          => '状态',
        'time_range'      => '时间范围',
        'page'            => '页码',
        'limit'           => '每页数量',
        'package_id'      => '套餐ID',
        'extend_days'     => '续费天数',
        'package_ids'     => '套餐ID列表',

        // 列表查询字段
        'agent_id'        => '代理商ID',
        'start_time'      => '开始时间',
        'end_time'        => '结束时间',
        'expire_status'   => '到期状态',
        'port_count_min'  => '最小端口数',
        'port_count_max'  => '最大端口数',
        'remaining_days'  => '剩余天数',
        'remaining_days_operator' => '剩余天数比较符',
        'assign_start_time' => '分配开始时间',
        'assign_end_time' => '分配结束时间',
        'expire_start_time' => '到期开始时间',
        'expire_end_time' => '到期结束时间',
        'remark_keyword'  => '备注关键词',
    ];

    /**
     * 错误信息
     * @var string[]
     */
    protected $message = [
        'tenant_id.require'       => '请选择租户',
        'tenant_id.integer'       => '租户ID必须是整数',
        'tenant_id.gt'            => '租户ID必须大于0',
        'port_count.require'      => '请输入端口数量',
        'port_count.integer'      => '端口数量必须是整数',
        'port_count.between'      => '端口数量必须在1-10000之间',
        'expire_days.require'     => '请输入有效天数',
        'expire_days.integer'     => '有效天数必须是整数',
        'expire_days.between'     => '有效天数必须在1-3650天之间',
        'remark.max'              => '备注不能超过255个字符',
        'alt_account_ids.require' => '请选择要分配的小号',
        'alt_account_ids.array'   => '小号ID列表格式错误',
        'operator_id.require'     => '请选择客服',
        'operator_id.integer'     => '客服ID必须是整数',
        'operator_id.gt'          => '客服ID必须大于0',
        'need_count.integer'      => '需要端口数必须是整数',
        'need_count.egt'          => '需要端口数不能小于0',
        'status.in'               => '状态值错误',
        'time_range.array'        => '时间范围格式错误',
        'page.integer'            => '页码必须是整数',
        'page.egt'                => '页码必须大于等于1',
        'limit.integer'           => '每页数量必须是整数',
        'limit.between'           => '每页数量必须在1-100之间',
        'package_id.require'      => '套餐ID不能为空',
        'package_id.integer'      => '套餐ID必须是整数',
        'package_id.gt'           => '套餐ID必须大于0',
        'extend_days.require'     => '续费天数不能为空',
        'extend_days.integer'     => '续费天数必须是整数',
        'extend_days.between'     => '续费天数必须在1-3650天之间',
        'package_ids.require'     => '套餐ID列表不能为空',
        'package_ids.array'       => '套餐ID列表必须是数组',
    ];

    /**
     * @notes 套餐分配场景
     * @return PackageValidate
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function sceneAssign()
    {
        return $this->only(['tenant_id', 'port_count', 'expire_days', 'remark']);
    }

    /**
     * @notes 小号分配场景
     * @return PackageValidate
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function sceneAssignAltAccount()
    {
        return $this->only(['alt_account_ids', 'operator_id'])
                    ->append('alt_account_ids', 'checkAltAccountIds');
    }

    /**
     * @notes 端口可用性检查场景
     * @return PackageValidate
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function sceneCheckAvailability()
    {
        return $this->only(['tenant_id', 'need_count']);
    }

    /**
     * @notes 租户套餐查询场景
     * @return PackageValidate
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function sceneTenantPackages()
    {
        return $this->only(['tenant_id']);
    }

    /**
     * @notes 套餐列表查询场景
     * @return PackageValidate
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function sceneLists()
    {
        return $this->only([
            'tenant_id', 'agent_id', 'status', 'expire_status',
            'start_time', 'end_time', 'assign_start_time', 'assign_end_time',
            'expire_start_time', 'expire_end_time', 'port_count_min', 'port_count_max',
            'remaining_days', 'remaining_days_operator', 'remark_keyword', 'package_ids',
            'page', 'limit'
        ])
        ->remove('tenant_id', 'require') // 移除tenant_id的必填验证
        ->remove('package_ids', 'require') // 移除package_ids的必填验证
        ->append('remaining_days_operator', 'checkRemainingDaysOperator')
        ->append('package_ids', 'checkPackageIdsOptional');
    }

    /**
     * @notes 分配历史查询场景
     * @return PackageValidate
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function sceneAssignHistory()
    {
        return $this->only(['tenant_id', 'status', 'time_range', 'page', 'limit'])
                    ->remove('tenant_id', 'require'); // 移除tenant_id的必填验证
    }

    /**
     * @notes 验证小号ID列表
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function checkAltAccountIds($value, $rule, $data)
    {
        if (!is_array($value)) {
            return '小号ID列表必须是数组';
        }

        if (empty($value)) {
            return '请至少选择一个小号';
        }

        if (count($value) > 1000) {
            return '单次最多只能分配1000个小号';
        }

        // 检查数组中的每个值是否为正整数
        foreach ($value as $altAccountId) {
            if (!is_numeric($altAccountId) || $altAccountId <= 0 || $altAccountId != intval($altAccountId)) {
                return '小号ID必须是正整数';
            }
        }

        // 检查是否有重复的小号ID
        if (count($value) !== count(array_unique($value))) {
            return '小号ID列表中不能有重复项';
        }

        return true;
    }

    /**
     * @notes 验证时间范围
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function checkTimeRange($value, $rule, $data)
    {
        if (!is_array($value)) {
            return '时间范围必须是数组';
        }

        if (count($value) !== 2) {
            return '时间范围必须包含开始时间和结束时间';
        }

        $startTime = $value[0] ?? '';
        $endTime = $value[1] ?? '';

        if (empty($startTime) || empty($endTime)) {
            return '开始时间和结束时间不能为空';
        }

        if (strtotime($startTime) === false) {
            return '开始时间格式错误';
        }

        if (strtotime($endTime) === false) {
            return '结束时间格式错误';
        }

        if (strtotime($startTime) > strtotime($endTime)) {
            return '开始时间不能大于结束时间';
        }

        // 检查时间范围不能超过1年
        $timeDiff = strtotime($endTime) - strtotime($startTime);
        if ($timeDiff > 365 * 24 * 60 * 60) {
            return '查询时间范围不能超过1年';
        }

        return true;
    }

    /**
     * @notes 验证端口数量合理性
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function checkPortCount($value, $rule, $data)
    {
        // 基础验证已在rule中定义，这里可以添加业务相关的验证
        
        // 检查是否为合理的端口数量（可根据业务需求调整）
        if ($value > 5000) {
            return '单次分配端口数量不建议超过5000个，如有特殊需求请联系管理员';
        }

        return true;
    }

    /**
     * @notes 验证有效天数合理性
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function checkExpireDays($value, $rule, $data)
    {
        // 基础验证已在rule中定义，这里可以添加业务相关的验证
        
        // 检查是否为合理的有效期（可根据业务需求调整）
        if ($value > 365) {
            return '套餐有效期不建议超过365天，如有特殊需求请联系管理员';
        }

        return true;
    }

    /**
     * @notes 套餐续费场景
     * @return PackageValidate
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function sceneRenew()
    {
        return $this->only(['package_id', 'extend_days'])
                    ->append('extend_days', 'checkExtendDays');
    }

    /**
     * @notes 批量续费场景
     * @return PackageValidate
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function sceneBatchRenew()
    {
        return $this->only(['package_ids', 'extend_days'])
                    ->append('package_ids', 'checkPackageIds')
                    ->append('extend_days', 'checkExtendDays');
    }

    /**
     * @notes 验证续费天数合理性
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function checkExtendDays($value, $rule, $data)
    {
        // 检查是否为合理的续费天数
        if ($value > 365) {
            return '单次续费天数不建议超过365天，如有特殊需求请联系管理员';
        }

        return true;
    }

    /**
     * @notes 验证套餐ID列表
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function checkPackageIds($value, $rule, $data)
    {
        if (!is_array($value)) {
            return '套餐ID列表必须是数组';
        }

        if (empty($value)) {
            return '请至少选择一个套餐';
        }

        if (count($value) > 100) {
            return '单次最多只能续费100个套餐';
        }

        // 检查数组中的每个值是否为正整数
        foreach ($value as $packageId) {
            if (!is_numeric($packageId) || $packageId <= 0 || $packageId != intval($packageId)) {
                return '套餐ID必须是正整数';
            }
        }

        // 检查是否有重复的套餐ID
        if (count($value) !== count(array_unique($value))) {
            return '套餐ID列表中不能有重复项';
        }

        return true;
    }

    /**
     * @notes 验证剩余天数比较符
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function checkRemainingDaysOperator($value, $rule, $data)
    {
        // 如果设置了剩余天数，必须设置比较符
        if (isset($data['remaining_days']) && $data['remaining_days'] !== '' && empty($value)) {
            return '设置剩余天数时必须指定比较符';
        }

        return true;
    }

    /**
     * @notes 验证套餐ID列表（可选）
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function checkPackageIdsOptional($value, $rule, $data)
    {
        if (empty($value)) {
            return true; // 可选参数，允许为空
        }

        // 如果是字符串，转换为数组
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            return '套餐ID列表格式错误';
        }

        if (count($value) > 100) {
            return '最多只能查询100个套餐';
        }

        // 检查数组中的每个值是否为正整数
        foreach ($value as $packageId) {
            if (!is_numeric($packageId) || $packageId <= 0 || $packageId != intval($packageId)) {
                return '套餐ID必须是正整数';
            }
        }

        return true;
    }
}
