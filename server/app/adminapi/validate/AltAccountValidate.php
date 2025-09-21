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
 * AltAccount验证器
 * Class AltAccountValidate
 * @package app\adminapi\validate
 */
class AltAccountValidate extends BaseValidate
{

     /**
      * 设置校验规则
      * @var string[]
      */
    protected $rule = [
        'id' => 'integer|gt:0',
        'ids' => 'array',
        'area_code' => 'max:8',
        'phone' => 'max:16',
        'password' => 'max:255',
        'mid' => 'require',
        'uid' => 'max:64',
        'nickname' => 'require|max:32',
        'avatar' => 'require',
        'platform'|        'nickname' => 'require|max:32',
        'avatar' => 'require',
        'platform' => 'max:20',
        'accesstoken' => 'require',
        'refreshtoken' => 'require',
        'alt_account_ids' => 'require|array',
        'operator_id' => 'require|integer|gt:0',
        'group_id' => 'integer|egt:0',
        'proxy_url' => 'max:500|checkProxyUrl',
    ];


    /**
     * 参数描述
     * @var string[]
     */
    protected $field = [
        'id' => 'id',
        'ids' => 'ID数组',
        'area_code' => '区号',
        'phone' => '电话',
        'password' => '密码',
        'mid' => '系统ID',
        'uid' => '自定义ID',
        'nickname' => '�ǳ�',
        'avatar' => '头像',
        'platform' => '系统平台',
        'accesstoken' => 'accesstoken',
        'refreshtoken' => 'refreshtoken',
        'alt_account_ids' => '小号ID数组',
        'operator_id' => '运营ID',
        'group_id' => '分组ID',
        'proxy_url' => '代理地址',
    ];


    /**
     * @notes 添加场景
     * @return AltAccountValidate
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function sceneAdd()
    {
        return $this->only(['area_code','phone','password','mid','uid','accesstoken','refreshtoken']);
    }


    /**
     * @notes 编辑场景
     * @return AltAccountValidate
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function sceneEdit()
    {
        return $this->only(['id','area_code','phone','password','mid','accesstoken','refreshtoken','proxy_url'])
               ->remove('accesstoken', 'require')
               ->remove('refreshtoken', 'require');
    }


    /**
     * @notes 删除场景
     * @return AltAccountValidate
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function sceneDelete()
    {
        return $this->only(['id', 'ids']);
    }

    /**
     * @notes 重写验证方法，处理删除场景的特殊逻辑
     * @param array $data
     * @param array $rules
     * @param array $message
     * @return bool
     * @author likeadmin
     * @date 2025/01/01 00:00
     */
    public function check(array $data, array $rules = [], array $message = []): bool
    {
        // 如果是删除场景，需要特殊处理
        if ($this->currentScene === 'delete') {
            // 检查是否有 ids 参数（批量删除）
            if (isset($data['ids']) && is_array($data['ids']) && !empty($data['ids'])) {
                // 批量删除，只验证 ids
                return parent::check($data, ['ids' => 'require|array'], $message);
            }
            // 单个删除，只验证 id
            if (isset($data['id']) && !empty($data['id'])) {
                return parent::check($data, ['id' => 'require|integer|gt:0'], $message);
            }
            // 两个参数都没有
            $this->error = 'id或ids参数不能为空';
            return false;
        }

        return parent::check($data, $rules, $message);
    }


    /**
     * @notes 详情场景
     * @return AltAccountValidate
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }


    /**
     * @notes 分配客服场景
     * @return AltAccountValidate
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function sceneAssignCustomerService()
    {
        return $this->only(['alt_account_ids', 'operator_id']);
    }


    /**
     * @notes 批量设置分组场景
     * @return AltAccountValidate
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function sceneBatchSetGroup()
    {
        return $this->only(['alt_account_ids', 'group_id']);
    }

    /**
     * @notes 设置代理场景
     * @return AltAccountValidate
     * @author likeadmin
     * @date 2025/09/06 22:56
     */
    public function sceneSetProxy()
    {
        return $this->only(['alt_account_ids', 'proxy_url']);
    }

    /**
     * @notes 批量设置代理场景
     * @return AltAccountValidate
     * @author likeadmin
     * @date 2025/09/06 22:56
     */
    public function sceneBatchSetProxy()
    {
        return $this->only(['alt_account_ids', 'proxy_url']);
    }

    /**
     * @notes 清除代理场景
     * @return AltAccountValidate
     * @author likeadmin
     * @date 2025/09/06 22:56
     */
    public function sceneClearProxy()
    {
        return $this->only(['alt_account_ids']);
    }

    /**
     * @notes 账号验活场景
     * @return AltAccountValidate
     * @author 段誉
     * @date 2025/09/08
     */
    public function sceneVerify()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 更新昵称验证
     * @return AltAccountValidate
     */
    public function sceneUpdateNickname()
    {
        return $this->only(['id', 'nickname']);
    }

    /**
     * @notes 更新头像验证
     * @return AltAccountValidate
     */
    public function sceneUpdateAvatar()
    {
        return $this->only(['id', 'avatar']);
    }

    /**
     * @notes 自定义验证规则：检查代理URL格式
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author likeadmin
     * @date 2025/09/06 22:56
     */
    protected function checkProxyUrl($value, $rule, $data)
    {
        if (empty($value)) {
            return true; // 空值允许
        }

        // 使用模型中的验证方法
        if (!\app\common\model\AltAccount::validateProxyUrl($value)) {
            return '代理地址格式不正确，请使用格式：protocol://username:password@host:port';
        }

        return true;
    }

}


