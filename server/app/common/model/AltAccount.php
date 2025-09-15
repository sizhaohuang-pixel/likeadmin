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
use app\common\service\FileService;

/**
 * AltAccount模型
 * Class AltAccount
 * @package app\common\model
 */
class AltAccount extends BaseModel
{

    protected $name = 'alt_account';

    /**
     * @notes 头像获取器 - 头像路径添加域名
     * @param $value
     * @return string
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function getAvatarAttr($value): string
    {
        return trim($value) ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 关联分组
     * @return \think\model\relation\BelongsTo
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function group()
    {
        return $this->belongsTo(AltAccountGroup::class, 'group_id', 'id');
    }

    /**
     * @notes 关联套餐
     * @return \think\model\relation\BelongsTo
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function package()
    {
        return $this->belongsTo(\app\common\model\package\PackageAssignment::class, 'package_id', 'id');
    }

    /**
     * @notes 关联运营（客服）
     * @return \think\model\relation\BelongsTo
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function operator()
    {
        return $this->belongsTo(\app\common\model\auth\Admin::class, 'operator_id', 'id');
    }

    /**
     * @notes 获取分组名称
     * @param $value
     * @param $data
     * @return string
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function getGroupNameAttr($value, $data): string
    {
        if (empty($data['group_id'])) {
            return '未分组';
        }

        $group = AltAccountGroup::findOrEmpty($data['group_id']);
        return $group->isEmpty() ? '未知分组' : $group->name;
    }

    /**
     * @notes 获取运营（客服）昵称
     * @param $value
     * @param $data
     * @return string
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function getOperatorNameAttr($value, $data): string
    {
        if (empty($data['operator_id'])) {
            return '未分配';
        }

        $operator = \app\common\model\auth\Admin::field(['id', 'name', 'account'])
            ->find($data['operator_id']);

        if (empty($operator)) {
            return '未知客服';
        }

        // 转换为数组
        $operatorData = $operator->toArray();

        // 组合显示：姓名(账号) 或 账号
        $name = trim($operatorData['name'] ?? '');
        $account = trim($operatorData['account'] ?? '');

        if (!empty($name) && $name !== 'Admin' && $name !== 'admin' && $name !== $account) {
            return "{$name}({$account})";
        }

        return $account ?: '未知客服';
    }

    /**
     * @notes 获取代理配置信息
     * @param $value
     * @return array
     * @author likeadmin
     * @date 2025/09/06 22:55
     */
    public function getProxyConfigAttr($value): array
    {
        if (empty($this->proxy_url)) {
            return [
                'enabled' => false,
                'type' => '',
                'host' => '',
                'port' => '',
                'username' => '',
                'password' => ''
            ];
        }

        $proxyInfo = $this->parseProxyUrl($this->proxy_url);
        return array_merge($proxyInfo, ['enabled' => true]);
    }

    /**
     * @notes 解析代理URL
     * @param string $proxyUrl
     * @return array
     * @author likeadmin
     * @date 2025/09/06 22:55
     */
    private function parseProxyUrl(string $proxyUrl): array
    {
        $result = [
            'type' => '',
            'host' => '',
            'port' => '',
            'username' => '',
            'password' => ''
        ];

        try {
            // 解析代理URL，格式：protocol://username:password@host:port
            $parsed = parse_url($proxyUrl);
            
            if ($parsed === false) {
                return $result;
            }

            $result['type'] = $parsed['scheme'] ?? '';
            $result['host'] = $parsed['host'] ?? '';
            $result['port'] = $parsed['port'] ?? '';
            $result['username'] = $parsed['user'] ?? '';
            $result['password'] = $parsed['pass'] ?? '';

        } catch (\Exception $e) {
            // 解析失败返回空结果
        }

        return $result;
    }

    /**
     * @notes 验证代理URL格式
     * @param string $proxyUrl
     * @return bool
     * @author likeadmin
     * @date 2025/09/06 22:55
     */
    public static function validateProxyUrl(string $proxyUrl): bool
    {
        if (empty($proxyUrl)) {
            return true; // 空值允许
        }

        // 检查基本URL格式
        $parsed = parse_url($proxyUrl);
        if ($parsed === false) {
            return false;
        }

        // 检查必需的组件
        if (empty($parsed['scheme']) || empty($parsed['host'])) {
            return false;
        }

        // 检查支持的协议类型
        $supportedSchemes = ['http', 'https', 'socks4', 'socks5'];
        if (!in_array(strtolower($parsed['scheme']), $supportedSchemes)) {
            return false;
        }

        // 检查端口范围
        if (isset($parsed['port']) && ($parsed['port'] < 1 || $parsed['port'] > 65535)) {
            return false;
        }

        return true;
    }

    /**
     * @notes 获取代理状态文本
     * @param $value
     * @param $data
     * @return string
     * @author likeadmin
     * @date 2025/09/06 22:55
     */
    public function getProxyStatusAttr($value, $data): string
    {
        if (empty($data['proxy_url'])) {
            return '未设置';
        }

        $proxyConfig = $this->getProxyConfigAttr($data['proxy_url']);
        if (!$proxyConfig['enabled']) {
            return '配置错误';
        }

        return '已启用';
    }
}