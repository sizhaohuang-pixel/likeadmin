import request from '@/utils/request'

// 分配套餐
export function packageAssign(params: any) {
    return request.post({ url: '/package/assign', params })
}

// 查询租户套餐
export function packageTenantPackages(params: any) {
    return request.get({ url: '/package/tenant_packages', params }, { ignoreCancelToken: true })
}

// 分配小号
export function packageAssignAltAccount(params: any) {
    return request.post({ url: '/package/assign_alt_account', params })
}

// 检查端口可用性
export function packageCheckPortAvailability(params: any) {
    return request.get({ url: '/package/check_port_availability', params }, { ignoreCancelToken: true })
}

// 分配历史查询
export function packageAssignHistory(params: any) {
    return request.get({ url: '/package/assign_history', params }, { ignoreCancelToken: true })
}

// 套餐列表
export function packageLists(params: any) {
    return request.get({ url: '/package/lists', params }, { ignoreCancelToken: true })
}

// 统计信息
export function packageStatistics(params: any) {
    return request.get({ url: '/package/statistics', params }, { ignoreCancelToken: true })
}

// 套餐详情
export function packageDetail(params: any) {
    return request.get({ url: '/package/detail', params })
}

// 端口池状态
export function packagePortPoolStatus(params: any) {
    return request.get({ url: '/package/port_pool_status', params }, { ignoreCancelToken: true })
}

// 处理过期套餐
export function packageHandleExpired(params: any) {
    return request.post({ url: '/package/handle_expired', params })
}

// 更新过期状态
export function packageUpdateExpiredStatus(params: any) {
    return request.post({ url: '/package/update_expired_status', params })
}

// 租户选项
export function packageTenantOptions(params: any) {
    return request.get({ url: '/package/tenant_options', params }, { ignoreCancelToken: true })
}

// 客服选项
export function packageOperatorOptions(params: any) {
    return request.get({ url: '/package/operator_options', params }, { ignoreCancelToken: true })
}

// 小号选项
export function packageAltAccountOptions(params: any) {
    return request.get({ url: '/package/alt_account_options', params }, { ignoreCancelToken: true })
}

// 释放小号
export function packageReleaseAltAccount(params: any) {
    return request.post({ url: '/package/release_alt_account', params })
}

// 批量释放小号
export function packageBatchReleaseAltAccount(params: any) {
    return request.post({ url: '/package/batch_release_alt_account', params })
}

// 单个套餐续费
export function packageRenew(params: any) {
    return request.post({ url: '/package/renew', params })
}

// 批量套餐续费
export function packageBatchRenew(params: any) {
    return request.post({ url: '/package/batch_renew', params })
}

// 获取可续费套餐列表
export function packageRenewablePackages(params: any) {
    return request.get({ url: '/package/renewable_packages', params }, { ignoreCancelToken: true })
}
