import request from '@/utils/request'

// 代理商列表
export function agentLists(params: any) {
    return request.get({ url: '/auth.agent/lists', params }, { ignoreCancelToken: true })
}

// 代理商添加
export function agentAdd(params: any) {
    return request.post({ url: '/auth.agent/add', params })
}

// 代理商编辑
export function agentEdit(params: any) {
    return request.post({ url: '/auth.agent/edit', params })
}

// 代理商删除
export function agentDelete(params: any) {
    return request.post({ url: '/auth.agent/delete', params })
}

// 代理商详情
export function agentDetail(params: any) {
    return request.get({ url: '/auth.agent/detail', params })
}
