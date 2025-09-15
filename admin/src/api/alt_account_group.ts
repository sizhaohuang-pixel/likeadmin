import request from '@/utils/request'

// 分组列表
export function apiAltAccountGroupLists(params: any) {
    return request.get({ url: '/alt_account_group/lists', params }, { ignoreCancelToken: true })
}

// 分组添加
export function apiAltAccountGroupAdd(params: any) {
    return request.post({ url: '/alt_account_group/add', params })
}

// 分组编辑
export function apiAltAccountGroupEdit(params: any) {
    return request.post({ url: '/alt_account_group/edit', params })
}

// 分组删除
export function apiAltAccountGroupDelete(params: any) {
    return request.post({ url: '/alt_account_group/delete', params })
}

// 分组详情
export function apiAltAccountGroupDetail(params: any) {
    return request.get({ url: '/alt_account_group/detail', params })
}

// 获取分组选项列表
export function apiAltAccountGroupGetGroupOptions() {
    return request.get({ url: '/alt_account_group/getGroupOptions' })
}
