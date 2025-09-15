<?php
declare (strict_types = 1);

namespace app\adminapi\validate;

use app\common\validate\BaseValidate;

/**
 * 小号批量导入验证器
 * Class AltAccountBatchImportValidate
 * @package app\adminapi\validate
 */
class AltAccountBatchImportValidate extends BaseValidate
{
    protected $rule = [
        'file_content' => 'require',
        'group_id' => 'require|integer|gt:0',
    ];

    protected $message = [
        'file_content.require' => '文件内容不能为空',
        'group_id.require' => '必须选择目标分组',
        'group_id.integer' => '分组ID必须是整数',
        'group_id.gt' => '必须选择有效的分组',
    ];

    protected $field = [
        'file_content' => '文件内容',
        'group_id' => '分组ID',
    ];

    /**
     * @notes 批量导入场景
     * @return AltAccountBatchImportValidate
     * @author likeadmin
     * @date 2025/01/05 16:00
     */
    public function sceneBatchImport()
    {
        return $this->only(['file_content', 'group_id']);
    }
}
