import request from '@/utils/request'

// 批量验活任务列表
export function apiBatchTaskLists(params: any) {
    return request.get({ url: '/TaskManagement/batch_verify_lists', params })
}

// 创建批量验活任务
export function apiBatchTaskCreate(params: any) {
    return request.post({ url: '/TaskManagement/create_batch_verify', params })
}

// 获取任务详情
export function apiBatchTaskDetail(params: { id: number }) {
    return request.get({ url: '/TaskManagement/batch_verify_detail', params })
}

// 取消任务
export function apiBatchTaskCancel(params: { id: number }) {
    return request.post({ url: '/TaskManagement/cancel_batch_verify', params })
}

// 检查运行中的任务
export function apiBatchTaskCheck(params: { task_type?: string } = {}) {
    return request.get({ url: '/TaskManagement/check_running_task', params })
}

// 获取任务进度
export function apiBatchTaskProgress(params: { id: number }) {
    return request.get({ url: '/TaskManagement/get_task_progress', params })
}

// 获取租户任务统计
export function apiBatchTaskStats(taskType?: string) {
    const params = taskType ? { task_type: taskType } : {}
    return request.get({ url: '/TaskManagement/get_tenant_stats', params })
}

// 获取任务执行详情列表
export function apiBatchTaskDetailList(params: any) {
    return request.get({ url: '/TaskManagement/get_task_detail_list', params })
}

// ==================== 批量改昵称任务相关API ====================

// 批量改昵称任务列表
export function apiBatchNicknameTaskLists(params: any) {
    return request.get({ url: '/TaskManagement/batch_nickname_lists', params })
}

// 创建批量改昵称任务
export function apiBatchNicknameTaskCreate(params: any) {
    return request.post({ url: '/TaskManagement/create_batch_nickname', params })
}

// 获取批量改昵称任务详情
export function apiBatchNicknameTaskDetail(params: { id: number }) {
    return request.get({ url: '/TaskManagement/batch_nickname_detail', params })
}

// 取消批量改昵称任务
export function apiBatchNicknameTaskCancel(params: { id: number }) {
    return request.post({ url: '/TaskManagement/cancel_batch_nickname', params })
}

// 获取账号分组选项
export function apiGetAccountGroups() {
    return request.get({ url: '/TaskManagement/get_account_groups' })
}

// 获取昵称分组选项
export function apiGetNicknameGroups() {
    return request.get({ url: '/TaskManagement/get_nickname_groups' })
}