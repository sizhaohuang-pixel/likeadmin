export interface NicknameRepository {
    id: number
    group_name: string
    nickname: string
    tenant_id: number
    status: number
    create_time: number
    update_time?: number
}

export interface NicknameGroup {
    group_name: string
    total_count: number
    available_count: number
}

export interface NicknameGroupStats extends NicknameGroup {
    usage_rate?: number
}

export interface NicknameImportResult {
    total_lines: number
    valid_lines: number
    success_count: number
    failed_count: number
    skipped_lines: number
    errors: Array<{
        line_number: number
        nickname: string
        error_message: string
        error_code: string
    }>
    import_time: string
}

export interface NicknameExportResult {
    success: boolean
    content?: string
    filename?: string
    count?: number
    message?: string
}

export interface NicknameStatistics {
    total_groups: number
    total_nicknames: number
    available_nicknames: number
    used_nicknames: number
    usage_rate: number
}

export interface NicknameDetailQuery {
    group_name: string
    status?: number | string
    nickname?: string
    page?: number
    limit?: number
}

export interface NicknameGroupForm {
    group_name: string
}

export interface NicknameGroupEditForm {
    old_group_name: string
    new_group_name: string
}

export interface NicknameImportForm {
    group_name: string
    file_content: string
}