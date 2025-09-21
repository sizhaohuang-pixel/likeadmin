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

use think\facade\Route;

// 昵称仓库管理路由
Route::group('nickname_repository', function() {
    // 分组统计列表
    Route::get('groups', 'NicknameRepository/groups');
    // 详细列表（分页）
    Route::get('lists', 'NicknameRepository/lists');
    // 分组明细
    Route::get('detail', 'NicknameRepository/detail');
    // 添加分组
    Route::post('add', 'NicknameRepository/add');
    // 编辑分组
    Route::post('edit', 'NicknameRepository/edit');
    // 删除分组
    Route::post('delete', 'NicknameRepository/delete');
    // 批量导入
    Route::post('batch_import', 'NicknameRepository/batchImport');
    // 导出
    Route::get('export', 'NicknameRepository/export');
    // 统计信息
    Route::get('statistics', 'NicknameRepository/statistics');
});