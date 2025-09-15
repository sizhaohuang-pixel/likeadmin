import request from '@/utils/request'

// 客服列表
export function operatorLists(params: any) {
    return request.get({ url: '/auth.operator/lists', params }, { ignoreCancelToken: true })
}

// 客服添加
export function operatorAdd(params: any) {
    return request.post({ url: '/auth.operator/add', params })
}

// 客服编辑
export function operatorEdit(params: any) {
    return request.post({ url: '/auth.operator/edit', params })
}

// 客服删除
export function operatorDelete(params: any) {
    return request.post({ url: '/auth.operator/delete', params })
}

// 客服详情
export function operatorDetail(params: any) {
    return request.get({ url: '/auth.operator/detail', params })
}
