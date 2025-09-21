import request from '@/utils/request'
import type { 
    NicknameGroup, 
    NicknameDetailQuery, 
    NicknameGroupForm, 
    NicknameGroupEditForm,
    NicknameImportForm,
    NicknameImportResult,
    NicknameExportResult,
    NicknameStatistics
} from '@/typings/nickname-repository'

// 获取分组统计列表
export function apiNicknameRepositoryGroups() {
    return request.get({ url: '/nickname_repository/groups' })
}

// 获取详细列表（分页）
export function apiNicknameRepositoryLists(params?: any) {
    return request.get({ url: '/nickname_repository/lists', params })
}

// 获取分组明细
export function apiNicknameRepositoryDetail(params: NicknameDetailQuery) {
    return request.get({ url: '/nickname_repository/detail', params })
}

// 添加分组
export function apiNicknameRepositoryAdd(params: NicknameGroupForm) {
    return request.post({ url: '/nickname_repository/add', params })
}

// 编辑分组
export function apiNicknameRepositoryEdit(params: NicknameGroupEditForm) {
    return request.post({ url: '/nickname_repository/edit', params })
}

// 删除分组
export function apiNicknameRepositoryDelete(params: { group_name: string }) {
    return request.post({ url: '/nickname_repository/delete', params })
}

// 批量导入昵称
export function apiNicknameRepositoryBatchImport(params: NicknameImportForm): Promise<NicknameImportResult> {
    return request.post({ url: '/nickname_repository/batch_import', params })
}

// 导出昵称（下载文件）
export function apiNicknameRepositoryExport(params: { group_name: string }) {
    return request.get({ 
        url: '/nickname_repository/export', 
        params,
        responseType: 'blob'
    }, {
        isTransformResponse: false
    })
}

// 获取统计信息
export function apiNicknameRepositoryStatistics(): Promise<NicknameStatistics> {
    return request.get({ url: '/nickname_repository/statistics' })
}