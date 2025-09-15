import request from '@/utils/request'

// 删除记录列表
export function apiAltAccountDeleteRecordLists(params: any) {
    return request.get({ url: '/alt_account_delete_record/lists', params })
}