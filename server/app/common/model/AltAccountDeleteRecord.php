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

namespace app\common\model;

use app\common\model\BaseModel;

/**
 * AltAccountDeleteRecord模型
 * Class AltAccountDeleteRecord
 * @package app\common\model
 */
class AltAccountDeleteRecord extends BaseModel
{

    protected $name = 'alt_account_delete_record';

    /**
     * @notes 关联操作人
     * @return \think\model\relation\BelongsTo
     * @author likeadmin
     * @date 2025/09/06 23:00
     */
    public function operatorAdmin()
    {
        return $this->belongsTo(\app\common\model\auth\Admin::class, 'operator_admin_id', 'id');
    }

    /**
     * @notes 获取操作人姓名
     * @param $value
     * @param $data
     * @return string
     * @author likeadmin
     * @date 2025/09/06 23:00
     */
    public function getOperatorNameAttr($value, $data): string
    {
        if (empty($data['operator_admin_id'])) {
            return '未知';
        }

        $operator = \app\common\model\auth\Admin::field(['id', 'name', 'account'])
            ->find($data['operator_admin_id']);

        if (empty($operator)) {
            return '用户已删除';
        }

        // 转换为数组
        $operatorData = $operator->toArray();

        // 组合显示：姓名(账号) 或 账号
        $name = trim($operatorData['name'] ?? '');
        $account = trim($operatorData['account'] ?? '');

        if (!empty($name) && $name !== 'Admin' && $name !== 'admin' && $name !== $account) {
            return "{$name}({$account})";
        }

        return $account ?: '未知';
    }

    /**
     * @notes 获取删除时间格式化文本
     * @param $value
     * @return string
     * @author likeadmin
     * @date 2025/09/06 23:00
     */
    public function getDeleteTimeTextAttr($value): string
    {
        if (empty($this->delete_time)) {
            return '';
        }
        return date('Y-m-d H:i:s', $this->delete_time);
    }


}