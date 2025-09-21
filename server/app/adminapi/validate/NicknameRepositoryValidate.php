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

namespace app\adminapi\validate;

use app\common\validate\BaseValidate;

/**
 * 昵称仓库验证器
 * Class NicknameRepositoryValidate
 * @package app\adminapi\validate
 */
class NicknameRepositoryValidate extends BaseValidate
{
    protected $rule = [
        'group_name' => 'require|length:1,100',
        'old_group_name' => 'require|length:1,100',
        'new_group_name' => 'require|length:1,100',
        'file_content' => 'require',
        'nickname' => 'length:0,20',
        'status' => 'in:0,1',
        'page' => 'integer|egt:1',
        'limit' => 'integer|between:1,100'
    ];

    protected $message = [
        'group_name.require' => '分组名称不能为空',
        'group_name.length' => '分组名称长度必须在1-100个字符之间',
        'old_group_name.require' => '原分组名称不能为空',
        'old_group_name.length' => '原分组名称长度必须在1-100个字符之间',
        'new_group_name.require' => '新分组名称不能为空',
        'new_group_name.length' => '新分组名称长度必须在1-100个字符之间',
        'file_content.require' => '文件内容不能为空',
        'nickname.length' => '昵称长度不能超过20个字符',
        'status.in' => '状态值必须为0或1',
        'page.integer' => '页码必须为整数',
        'page.egt' => '页码必须大于等于1',
        'limit.integer' => '每页数量必须为整数',
        'limit.between' => '每页数量必须在1-100之间'
    ];

    /**
     * 添加分组场景
     * @return NicknameRepositoryValidate
     */
    public function sceneAdd()
    {
        return $this->only(['group_name']);
    }

    /**
     * 编辑分组场景
     * @return NicknameRepositoryValidate
     */
    public function sceneEdit()
    {
        return $this->only(['old_group_name', 'new_group_name']);
    }

    /**
     * 删除分组场景
     * @return NicknameRepositoryValidate
     */
    public function sceneDelete()
    {
        return $this->only(['group_name']);
    }

    /**
     * 批量导入场景
     * @return NicknameRepositoryValidate
     */
    public function sceneBatchImport()
    {
        return $this->only(['group_name', 'file_content']);
    }

    /**
     * 导出场景
     * @return NicknameRepositoryValidate
     */
    public function sceneExport()
    {
        return $this->only(['group_name']);
    }

    /**
     * 分组明细场景
     * @return NicknameRepositoryValidate
     */
    public function sceneDetail()
    {
        return $this->only(['group_name', 'status', 'nickname', 'page', 'limit']);
    }
}