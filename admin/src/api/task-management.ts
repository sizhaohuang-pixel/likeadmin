import request from '@/utils/request'

// 批量验活任务列表
export function apiBatchTaskLists(params: any) {
    return request.get({ url: '/task_management/batch_verify_lists', params })
}

// 创建批量验活任务
export function apiBatchTaskCreate(params: any) {
    return request.post({ url: '/task_management/create_batch_verify', params })
}

// 获取任务详情
export function apiBatchTaskDetail(params: { id: number }) {
    return request.get({ url: '/task_management/batch_verify_detail', params })
}

// 取消任务
export function apiBatchTaskCancel(params: { id: number }) {
    return request.post({ url: '/task_management/cancel_batch_verify', params })
}

// 检查运行中的任务
export function apiBatchTaskCheck(params: { task_type?: string } = {}) {
    return request.get({ url: '/task_management/check_running_task', params })
}

// 获取任务进度
export function apiBatchTaskProgress(params: { id: number }) {
    return request.get({ url: '/task_management/get_task_progress', params })
}

// 获取租户任务统计
export function apiBatchTaskStats() {
    return request.get({ url: '/task_management/get_tenant_stats' })
}

// 获取任务执行详情列表
export function apiBatchTaskDetailList(params: any) {
    return request.get({ url: '/task_management/get_task_detail_list', params })
}