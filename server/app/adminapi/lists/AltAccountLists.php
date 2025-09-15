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

namespace app\adminapi\lists;


use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\AltAccount;
use app\common\lists\ListsSearchInterface;


/**
 * AltAccount列表
 * Class AltAccountLists
 * @package app\adminapi\lists
 */
class AltAccountLists extends BaseAdminDataLists implements ListsSearchInterface
{
    protected int $currentAdminId;

    public function __construct(int $currentAdminId = 0)
    {
        parent::__construct();
        $this->currentAdminId = $currentAdminId;
        
        // 清理无效的搜索条件，proxy_status不是数据库字段，需要特殊处理
        $this->cleanInvalidSearchWhere();
    }
    
    /**
     * @notes 清理无效的搜索条件
     * @author Claude
     * @date 2025/09/08
     */
    private function cleanInvalidSearchWhere()
    {
        // 移除虚拟字段的搜索条件，因为它们不是数据库字段，需要特殊处理
        $virtualFields = ['proxy_status', 'has_nickname', 'has_uid'];
        $this->searchWhere = array_filter($this->searchWhere, function($condition) use ($virtualFields) {
            // 搜索条件格式：[字段名, 操作符, 值]
            return !in_array($condition[0], $virtualFields);
        });
    }


    /**
     * @notes 设置搜索条件
     * @return \string[][]
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function setSearch(): array
    {
        return [
            '=' => ['nickname', 'area_code', 'phone', 'password', 'mid', 'status', 'group_id', 'operator_id'],
            '%like%' => ['operator_name'], // 支持按运营姓名/账号模糊搜索
            // proxy_status, has_nickname, has_uid 需要特殊处理，不在标准搜索条件中
        ];
    }

    /**
     * @notes 设置租户权限过滤条件
     * @return array
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    protected function getTenantWhere(): array
    {
        // 只能查看自己创建的小号
        return ['tenant_id' => $this->currentAdminId];
    }


    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function lists(): array
    {
        $where = array_merge($this->searchWhere, $this->getTenantWhere());

        // 处理运营姓名搜索
        $operatorNameSearch = '';
        if (isset($this->searchWhere['operator_name'])) {
            $operatorNameSearch = $this->searchWhere['operator_name'];
            unset($where['operator_name']); // 从普通where条件中移除
        }

        // 处理虚拟字段搜索，需要特殊处理，不能直接加入where条件
        $proxyStatusSearch = $this->request->param('proxy_status', '');
        if (!empty($proxyStatusSearch)) {
            unset($where['proxy_status']); // 从普通where条件中移除
        }

        $hasNicknameSearch = $this->request->param('has_nickname', '');
        if (!empty($hasNicknameSearch)) {
            unset($where['has_nickname']); // 从普通where条件中移除
        }

        $hasUidSearch = $this->request->param('has_uid', '');
        if (!empty($hasUidSearch)) {
            unset($where['has_uid']); // 从普通where条件中移除
        }

        $query = AltAccount::where($where)
            ->field(['id', 'tenant_id', 'group_id', 'operator_id', 'avatar', 'nickname', 'area_code', 'phone', 'password', 'mid', 'uid', 'platform', 'status', 'proxy_url']);

        // 如果有运营姓名搜索，添加关联查询
        if (!empty($operatorNameSearch)) {
            $query->leftJoin('la_admin admin', 'la_alt_account.operator_id = admin.id')
                ->where(function($query) use ($operatorNameSearch) {
                    $query->whereLike('admin.name', "%{$operatorNameSearch}%")
                          ->whereOr('admin.account', 'like', "%{$operatorNameSearch}%");
                });
        }

        // 添加代理状态过滤
        if (!empty($proxyStatusSearch)) {
            if ($proxyStatusSearch === 'none') {
                // 未设置代理：proxy_url为空或NULL
                $query->where(function($q) {
                    $q->whereNull('proxy_url')->whereOr('proxy_url', '');
                });
            } elseif ($proxyStatusSearch === 'set') {
                // 已设置代理：proxy_url不为空
                $query->where('proxy_url', '<>', '')->whereNotNull('proxy_url');
            }
        }

        // 添加昵称存在性过滤
        if (!empty($hasNicknameSearch)) {
            if ($hasNicknameSearch === 'yes') {
                // 存在昵称：nickname不为空
                $query->where('nickname', '<>', '')->whereNotNull('nickname');
            } elseif ($hasNicknameSearch === 'no') {
                // 不存在昵称：nickname为空或NULL
                $query->where(function($q) {
                    $q->whereNull('nickname')->whereOr('nickname', '');
                });
            }
        }

        // 添加自定义ID存在性过滤
        if (!empty($hasUidSearch)) {
            if ($hasUidSearch === 'yes') {
                // 存在自定义ID：uid不为空
                $query->where('uid', '<>', '')->whereNotNull('uid');
            } elseif ($hasUidSearch === 'no') {
                // 不存在自定义ID：uid为空或NULL
                $query->where(function($q) {
                    $q->whereNull('uid')->whereOr('uid', '');
                });
            }
        }

        $list = $query->field([
                'la_alt_account.id', 'la_alt_account.avatar', 'la_alt_account.nickname', 
                'la_alt_account.platform', 'la_alt_account.area_code', 'la_alt_account.phone', 
                'la_alt_account.password', 'la_alt_account.mid', 'la_alt_account.uid', 
                'la_alt_account.status', 'la_alt_account.create_time', 'la_alt_account.update_time',
                'la_alt_account.group_id', 'la_alt_account.operator_id', 'la_alt_account.tenant_id',
                'la_alt_account.accesstoken', 'la_alt_account.proxy_url'
            ])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['la_alt_account.id' => 'desc'])
            ->append(['group_name', 'operator_name', 'proxy_status'])
            ->select()
            ->toArray();

        return $list;
    }


    /**
     * @notes 获取数量
     * @return int
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function count(): int
    {
        $where = array_merge($this->searchWhere, $this->getTenantWhere());

        // 处理运营姓名搜索
        $operatorNameSearch = '';
        if (isset($this->searchWhere['operator_name'])) {
            $operatorNameSearch = $this->searchWhere['operator_name'];
            unset($where['operator_name']); // 从普通where条件中移除
        }

        // 处理虚拟字段搜索，需要特殊处理，不能直接加入where条件
        $proxyStatusSearch = $this->request->param('proxy_status', '');
        if (!empty($proxyStatusSearch)) {
            unset($where['proxy_status']); // 从普通where条件中移除
        }

        $hasNicknameSearch = $this->request->param('has_nickname', '');
        if (!empty($hasNicknameSearch)) {
            unset($where['has_nickname']); // 从普通where条件中移除
        }

        $hasUidSearch = $this->request->param('has_uid', '');
        if (!empty($hasUidSearch)) {
            unset($where['has_uid']); // 从普通where条件中移除
        }

        $query = AltAccount::where($where);

        // 如果有运营姓名搜索，添加关联查询
        if (!empty($operatorNameSearch)) {
            $query->leftJoin('la_admin admin', 'la_alt_account.operator_id = admin.id')
                ->where(function($query) use ($operatorNameSearch) {
                    $query->whereLike('admin.name', "%{$operatorNameSearch}%")
                          ->whereOr('admin.account', 'like', "%{$operatorNameSearch}%");
                });
        }

        // 添加代理状态过滤
        if (!empty($proxyStatusSearch)) {
            if ($proxyStatusSearch === 'none') {
                // 未设置代理：proxy_url为空或NULL
                $query->where(function($q) {
                    $q->whereNull('proxy_url')->whereOr('proxy_url', '');
                });
            } elseif ($proxyStatusSearch === 'set') {
                // 已设置代理：proxy_url不为空
                $query->where('proxy_url', '<>', '')->whereNotNull('proxy_url');
            }
        }

        // 添加昵称存在性过滤
        if (!empty($hasNicknameSearch)) {
            if ($hasNicknameSearch === 'yes') {
                // 存在昵称：nickname不为空
                $query->where('nickname', '<>', '')->whereNotNull('nickname');
            } elseif ($hasNicknameSearch === 'no') {
                // 不存在昵称：nickname为空或NULL
                $query->where(function($q) {
                    $q->whereNull('nickname')->whereOr('nickname', '');
                });
            }
        }

        // 添加自定义ID存在性过滤
        if (!empty($hasUidSearch)) {
            if ($hasUidSearch === 'yes') {
                // 存在自定义ID：uid不为空
                $query->where('uid', '<>', '')->whereNotNull('uid');
            } elseif ($hasUidSearch === 'no') {
                // 不存在自定义ID：uid为空或NULL
                $query->where(function($q) {
                    $q->whereNull('uid')->whereOr('uid', '');
                });
            }
        }

        return $query->count();
    }

}