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

namespace app\adminapi\controller;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\NicknameRepositoryLists;
use app\adminapi\logic\NicknameRepositoryLogic;
use app\adminapi\validate\NicknameRepositoryValidate;

/**
 * 昵称仓库控制器
 * Class NicknameRepositoryController
 * @package app\adminapi\controller
 */
class NicknameRepositoryController extends BaseAdminController
{
    /**
     * @notes 获取分组列表
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/21
     */
    public function lists()
    {
        return $this->dataLists(new NicknameRepositoryLists());
    }

    /**
     * @notes 获取分组统计列表
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/21
     */
    public function groups()
    {
        $result = NicknameRepositoryLogic::getGroupList($this->adminId);
        return $this->data($result);
    }

    /**
     * @notes 获取分组明细
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/21
     */
    public function detail()
    {
        $params = (new NicknameRepositoryValidate())->goCheck('detail');
        $result = NicknameRepositoryLogic::getGroupDetail($params, $this->adminId);
        return $this->data($result);
    }

    /**
     * @notes 添加分组
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/21
     */
    public function add()
    {
        $requestData = $this->request->post();
        // 临时调试：记录接收到的参数
        trace('NicknameRepository add request data: ' . json_encode($requestData), 'debug');
        
        $params = (new NicknameRepositoryValidate())->post()->goCheck('add');
        $result = NicknameRepositoryLogic::add($params, $this->adminId);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(NicknameRepositoryLogic::getError());
    }

    /**
     * @notes 编辑分组
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/21
     */
    public function edit()
    {
        $params = (new NicknameRepositoryValidate())->post()->goCheck('edit');
        $result = NicknameRepositoryLogic::edit($params, $this->adminId);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(NicknameRepositoryLogic::getError());
    }

    /**
     * @notes 删除分组
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/21
     */
    public function delete()
    {
        $params = (new NicknameRepositoryValidate())->post()->goCheck('delete');
        $result = NicknameRepositoryLogic::delete($params, $this->adminId);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(NicknameRepositoryLogic::getError());
    }

    /**
     * @notes 批量导入昵称
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/21
     */
    public function batchImport()
    {
        $params = (new NicknameRepositoryValidate())->post()->goCheck('batchImport');
        $result = NicknameRepositoryLogic::batchImport($params, $this->adminId);
        
        if (!empty($result['errors']) && $result['success_count'] === 0) {
            return $this->fail('导入失败', $result);
        }
        
        return $this->success('导入完成', $result, 1, 1);
    }

    /**
     * @notes 导出昵称
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/21
     */
    public function export()
    {
        $params = (new NicknameRepositoryValidate())->goCheck('export');
        $result = NicknameRepositoryLogic::export($params, $this->adminId);
        
        if ($result['success']) {
            // 添加UTF-8 BOM头确保编码正确
            $content = "\xEF\xBB\xBF" . $result['content'];
            
            // 返回文件下载响应
            return response($content)
                ->header([
                    'Content-Type' => 'text/plain; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
                    'Content-Length' => strlen($content)
                ]);
        }
        
        return $this->fail($result['message']);
    }

    /**
     * @notes 获取统计信息
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/21
     */
    public function statistics()
    {
        $result = NicknameRepositoryLogic::getStatistics($this->adminId);
        return $this->data($result);
    }
}