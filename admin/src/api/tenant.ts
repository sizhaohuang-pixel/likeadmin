import request from '@/utils/request'

// 租户列表
export function tenantLists(params: any) {
    return request.get({ url: '/auth.tenant/lists', params }, { ignoreCancelToken: true })
}

// 租户添加
export function tenantAdd(params: any) {
    return request.post({ url: '/auth.tenant/add', params })
}

// 租户编辑
export function tenantEdit(params: any) {
    return request.post({ url: '/auth.tenant/edit', params })
}

// 租户删除
export function tenantDelete(params: any) {
    return request.post({ url: '/auth.tenant/delete', params })
}

// 租户详情
export function tenantDetail(params: any) {
    return request.get({ url: '/auth.tenant/detail', params })
}

// 获取租户端口统计信息
export function tenantPortStats() {
    return request.get({ url: '/auth.tenant/portStats' })
}
