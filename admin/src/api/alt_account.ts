import request from '@/utils/request'

// 小号账户表列表
export function apiAltAccountLists(params: any) {
    return request.get({ url: '/alt_account/lists', params })
}

// 添加小号账户表
export function apiAltAccountAdd(params: any) {
    return request.post({ url: '/alt_account/add', params })
}

// 编辑小号账户表
export function apiAltAccountEdit(params: any) {
    return request.post({ url: '/alt_account/edit', params })
}

// 删除小号账户表
export function apiAltAccountDelete(params: any) {
    return request.post({ url: '/alt_account/delete', params })
}

// 小号账户表详情
export function apiAltAccountDetail(params: any) {
    return request.get({ url: '/alt_account/detail', params })
}

// 分配客服
export function apiAltAccountAssignCustomerService(params: any) {
    return request.post({ url: '/alt_account/assignCustomerService', params })
}

// 获取可分配运营列表
export function apiAltAccountGetAvailableOperators() {
    return request.get({ url: '/alt_account/getAvailableOperators' })
}

// 批量设置小号分组
export function apiAltAccountBatchSetGroup(params: any) {
    return request.post({ url: '/alt_account/batchSetGroup', params })
}

// 批量导入小号
export function apiAltAccountBatchImport(params: any) {
    return request.post({
        url: '/alt_account/batchImport',
        params,
        requestOptions: {
            isReturnDefaultResponse: true  // 返回原始响应，不进行错误处理
        }
    })
}

// 设置代理
export function apiAltAccountSetProxy(params: any) {
    return request.post({ url: '/alt_account/setProxy', params })
}

// 批量设置代理
export function apiAltAccountBatchSetProxy(params: any) {
    return request.post({ url: '/alt_account/batchSetProxy', params })
}

// 清除代理设置
export function apiAltAccountClearProxy(params: any) {
    return request.post({ url: '/alt_account/clearProxy', params })
}

// 获取代理统计信息
export function apiAltAccountGetProxyStatistics() {
    return request.get({ url: '/alt_account/getProxyStatistics' })
}

// 账号验活
export function apiAltAccountVerify(params: any) {
    return request.post({ url: '/alt_account/verify', params })
}