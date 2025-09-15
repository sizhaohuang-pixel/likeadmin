<?php
declare (strict_types = 1);

namespace app\adminapi\controller\auth;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\auth\OperatorLists;
use app\adminapi\validate\auth\OperatorValidate;
use app\adminapi\logic\auth\OperatorLogic;
use app\common\service\TenantAdminService;

/**
 * 运营控制器
 * Class OperatorController
 * @package app\adminapi\controller\auth
 */
class OperatorController extends BaseAdminController
{
    /**
     * @notes 运营列表
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/08/24
     */
    public function lists()
    {
        return $this->dataLists(new OperatorLists());
    }

    /**
     * @notes 添加运营
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/08/24
     */
    public function add()
    {
        try {
            // 验证租户权限 - 只有租户才能创建运营
            TenantAdminService::validateTenant($this->adminId, '创建运营');
            
            $params = (new OperatorValidate())->post()->goCheck('add');
            $result = OperatorLogic::add($params, $this->adminId);
            if (true === $result) {
                return $this->success('操作成功', [], 1, 1);
            }
            return $this->fail(OperatorLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 编辑运营
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/08/24
     */
    public function edit()
    {
        $params = (new OperatorValidate())->post()->goCheck('edit');
        $result = OperatorLogic::edit($params, $this->adminId);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(OperatorLogic::getError());
    }

    /**
     * @notes 删除运营
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/08/24
     */
    public function delete()
    {
        $params = (new OperatorValidate())->post()->goCheck('delete');
        $result = OperatorLogic::delete($params, $this->adminId);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(OperatorLogic::getError());
    }

    /**
     * @notes 查看运营详情
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/08/24
     */
    public function detail()
    {
        $params = (new OperatorValidate())->goCheck('detail');
        try {
            $result = OperatorLogic::detail($params, $this->adminId);
            return $this->data($result);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
