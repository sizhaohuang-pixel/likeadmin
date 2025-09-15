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
use app\adminapi\lists\AltAccountLists;
use app\adminapi\logic\AltAccountLogic;
use app\adminapi\validate\AltAccountValidate;


/**
 * AltAccount控制器
 * Class AltAccountController
 * @package app\adminapi\controller
 */
class AltAccountController extends BaseAdminController
{


    /**
     * @notes 获取列表
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function lists()
    {
        return $this->dataLists(new AltAccountLists($this->adminId));
    }


    /**
     * @notes 添加
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function add()
    {
        $params = (new AltAccountValidate())->post()->goCheck('add');
        $result = AltAccountLogic::add($params, $this->adminId);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(AltAccountLogic::getError());
    }


    /**
     * @notes 编辑
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function edit()
    {
        $params = (new AltAccountValidate())->post()->goCheck('edit');
        $result = AltAccountLogic::edit($params, $this->adminId);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(AltAccountLogic::getError());
    }


    /**
     * @notes 删除
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function delete()
    {
        $params = (new AltAccountValidate())->post()->goCheck('delete');
        $result = AltAccountLogic::delete($params, $this->adminId);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(AltAccountLogic::getError());
    }


    /**
     * @notes 获取详情
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function detail()
    {
        $params = (new AltAccountValidate())->goCheck('detail');
        $result = AltAccountLogic::detail($params, $this->adminId);
        return $this->data($result);
    }


    /**
     * @notes 分配客服（运营）
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function assignCustomerService()
    {
        $params = (new AltAccountValidate())->post()->goCheck('assignCustomerService');
        $result = AltAccountLogic::assignCustomerService($params, $this->adminId);
        if (true === $result) {
            return $this->success('分配客服成功', [], 1, 1);
        }
        return $this->fail(AltAccountLogic::getError());
    }


    /**
     * @notes 获取可分配的运营列表
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function getAvailableOperators()
    {
        $result = AltAccountLogic::getAvailableOperators($this->adminId);
        return $this->data($result);
    }


    /**
     * @notes 批量设置小号分组
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function batchSetGroup()
    {
        $params = (new AltAccountValidate())->post()->goCheck('batchSetGroup');
        $result = AltAccountLogic::batchSetGroup($params, $this->adminId);
        if (true === $result) {
            return $this->success('设置分组成功', [], 1, 1);
        }
        return $this->fail(AltAccountLogic::getError());
    }

    /**
     * @notes 批量导入小号
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/01/05 16:00
     */
    public function batchImport()
    {
        try {
            // 记录开始日志
            error_log("批量导入开始 - adminId: " . $this->adminId);

            $params = (new \app\adminapi\validate\AltAccountBatchImportValidate())->post()->goCheck('batchImport');

            // 记录参数日志
            error_log("批量导入参数 - group_id: " . $params['group_id'] . ", file_content_length: " . strlen($params['file_content']));

            $result = AltAccountLogic::batchImport($params, $this->adminId);

            // 检查Logic是否有错误
            if (AltAccountLogic::hasError()) {
                return $this->success(AltAccountLogic::getError(), $result, 1, 1);
            }

            // 统一返回导入结果，无论成功失败都返回成功状态
            $message = sprintf(
                '导入完成：成功 %d 条，失败 %d 条',
                $result['success_count'],
                $result['failed_count']
            );

            return $this->success($message, $result, 1, 1);
        } catch (\Exception $e) {
            // 记录异常日志
            error_log("批量导入异常: " . $e->getMessage() . " 文件: " . $e->getFile() . " 行号: " . $e->getLine());
            error_log("异常堆栈: " . $e->getTraceAsString());

            // 捕获所有异常，返回统一格式
            return $this->success('导入完成：成功 0 条，失败 0 条（系统错误）', [
                'total_lines' => 0,
                'valid_lines' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'skipped_lines' => 0,
                'errors' => []
            ], 1, 1);
        }
    }

    /**
     * @notes 设置代理
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/06 22:58
     */
    public function setProxy()
    {
        $params = (new AltAccountValidate())->post()->goCheck('setProxy');
        $result = AltAccountLogic::setProxy($params, $this->adminId);
        if (true === $result) {
            return $this->success('设置代理成功', [], 1, 1);
        }
        return $this->fail(AltAccountLogic::getError());
    }

    /**
     * @notes 批量设置代理
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/06 22:58
     */
    public function batchSetProxy()
    {
        $params = (new AltAccountValidate())->post()->goCheck('batchSetProxy');
        $result = AltAccountLogic::batchSetProxy($params, $this->adminId);
        if (true === $result) {
            return $this->success('批量设置代理成功', [], 1, 1);
        }
        return $this->fail(AltAccountLogic::getError());
    }

    /**
     * @notes 清除代理设置
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/06 22:58
     */
    public function clearProxy()
    {
        $params = (new AltAccountValidate())->post()->goCheck('clearProxy');
        $result = AltAccountLogic::clearProxy($params, $this->adminId);
        if (true === $result) {
            return $this->success('清除代理成功', [], 1, 1);
        }
        return $this->fail(AltAccountLogic::getError());
    }

    /**
     * @notes 获取代理统计信息
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/06 22:58
     */
    public function getProxyStatistics()
    {
        $result = AltAccountLogic::getProxyStatistics($this->adminId);
        return $this->data($result);
    }

    /**
     * @notes 账号验活
     * @return \think\response\Json
     * @author 段誉
     * @date 2025/09/08
     */
    public function verify()
    {
        $params = (new AltAccountValidate())->post()->goCheck('verify');
        $result = AltAccountLogic::verify($params, $this->adminId);
        
        if (isset($result['success']) && $result['success']) {
            return $this->success('验活完成', $result, 1, 1);
        }
        
        return $this->fail($result['message'] ?? '验活失败');
    }


}