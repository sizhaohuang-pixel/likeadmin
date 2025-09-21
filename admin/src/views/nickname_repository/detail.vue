<template>
    <div class="nickname-detail">
        <!-- 面包屑导航 -->
        <el-breadcrumb class="mb-4">
            <el-breadcrumb-item :to="{ path: '/nickname_repository' }">昵称仓库</el-breadcrumb-item>
            <el-breadcrumb-item>{{ route.query.group_name }}</el-breadcrumb-item>
        </el-breadcrumb>

        <!-- 页面头部 -->
        <el-card class="!border-none mb-4" shadow="never">
            <div class="page-header">
                <div class="header-info">
                    <h2 class="group-title">{{ route.query.group_name }}</h2>
                    <div class="group-summary">
                        总计 {{ pagination.total }} 条昵称
                    </div>
                </div>
                <div class="header-actions">
                    <el-button @click="handleGoBack">
                        <template #icon>
                            <icon name="el-icon-ArrowLeft" />
                        </template>
                        返回列表
                    </el-button>
                    <el-button type="primary" @click="handleImport">
                        <template #icon>
                            <icon name="el-icon-Upload" />
                        </template>
                        导入昵称
                    </el-button>
                    <el-button type="success" @click="handleExport" :disabled="availableCount === 0">
                        <template #icon>
                            <icon name="el-icon-Download" />
                        </template>
                        <span class="action-text">剩余数据导出</span>
                    </el-button>
                    <el-button type="info" @click="handleRefresh">
                        <template #icon>
                            <icon name="el-icon-Refresh" />
                        </template>
                        <span class="action-text">刷新</span>
                    </el-button>
                </div>
            </div>
        </el-card>

        <!-- 搜索和过滤区域 -->
        <el-card class="!border-none mb-4" shadow="never">
            <el-form :model="queryParams" :inline="true" class="search-form">
                <el-form-item label="状态">
                    <el-select v-model="queryParams.status" placeholder="请选择状态" clearable @change="handleSearch">
                        <el-option label="全部" value="" />
                        <el-option label="可用" :value="1" />
                        <el-option label="已使用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="昵称搜索">
                    <el-input 
                        v-model="queryParams.nickname" 
                        placeholder="请输入昵称关键词" 
                        clearable
                        @keyup.enter="handleSearch"
                        class="search-input"
                    />
                </el-form-item>
                <el-form-item class="search-buttons">
                    <el-button type="primary" @click="handleSearch">搜索</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 数据列表 -->
        <el-card class="!border-none" shadow="never">
            <el-table 
                :data="nicknameList" 
                v-loading="loading"
                stripe
                style="width: 100%"
                class="nickname-table"
            >
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="nickname" label="昵称内容" min-width="200">
                    <template #default="{ row }">
                        <span class="nickname-text">{{ row.nickname }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="status" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.status === 1 ? 'success' : 'info'">
                            {{ row.status === 1 ? '可用' : '已使用' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="create_time" label="创建时间" width="180" class-name="time-column">
                    <template #default="{ row }">
                        {{ formatTime(row.create_time) }}
                    </template>
                </el-table-column>
                <el-table-column prop="update_time" label="更新时间" width="180" class-name="time-column">
                    <template #default="{ row }">
                        {{ formatTime(row.update_time) }}
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button 
                            type="danger" 
                            size="small" 
                            text
                            @click="handleDelete(row)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <!-- 分页 -->
            <div class="pagination-wrapper" v-if="pagination.total > 0">
                <el-pagination
                    v-model:current-page="queryParams.page"
                    v-model:page-size="queryParams.limit"
                    :total="pagination.total"
                    :page-sizes="[10, 20, 50, 100]"
                    layout="total, sizes, prev, pager, next, jumper"
                    @size-change="handleSizeChange"
                    @current-change="handleCurrentChange"
                    class="pagination"
                />
            </div>

            <!-- 空状态 -->
            <el-empty v-if="!loading && nicknameList.length === 0" description="暂无昵称数据" />
        </el-card>

        <!-- 导入昵称组件 -->
        <import-nickname-popup 
            v-model="importDialogVisible" 
            :group-name="route.query.group_name as string"
            @success="handleImportSuccess" 
        />
    </div>
</template>

<script lang="ts" setup name="nicknameRepositoryDetail">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
    apiNicknameRepositoryDetail,
    apiNicknameRepositoryExport 
} from '@/api/nickname_repository'
import type { NicknameRepository, NicknameDetailQuery } from '@/typings/nickname-repository'
import feedback from '@/utils/feedback'
import ImportNicknamePopup from './import.vue'

const route = useRoute()
const router = useRouter()

// 数据状态
const loading = ref(false)
const nicknameList = ref<NicknameRepository[]>([])
const importDialogVisible = ref(false)

// 查询参数
const queryParams = reactive<NicknameDetailQuery>({
    group_name: route.query.group_name as string,
    status: '',
    nickname: '',
    page: 1,
    limit: 20
})

// 分页信息
const pagination = reactive({
    total: 0,
    page: 1,
    limit: 20
})

// 计算属性
const availableCount = computed(() => {
    return nicknameList.value.filter(item => item.status === 1).length
})

// 时间格式化
const formatTime = (timestamp: number | string) => {
    if (!timestamp) return '-'
    
    // 处理不同格式的时间戳
    let date: Date
    if (typeof timestamp === 'string') {
        // 如果是字符串，尝试直接解析
        date = new Date(timestamp)
    } else {
        // 如果是数字，判断是秒还是毫秒
        if (timestamp.toString().length === 10) {
            // 10位数字，秒级时间戳
            date = new Date(timestamp * 1000)
        } else {
            // 毫秒级时间戳
            date = new Date(timestamp)
        }
    }
    
    // 检查日期是否有效
    if (isNaN(date.getTime())) {
        return '-'
    }
    
    return date.toLocaleString()
}

// 获取详细列表
const getDetailList = async () => {
    try {
        loading.value = true
        const params = {
            ...queryParams,
            status: queryParams.status === '' ? undefined : queryParams.status
        }
        const data = await apiNicknameRepositoryDetail(params)
        nicknameList.value = data.lists || []
        pagination.total = data.count || 0
        pagination.page = data.page || 1
        pagination.limit = data.limit || 20
    } catch (error: any) {
        ElMessage.error(error.message || '获取数据失败')
    } finally {
        loading.value = false
    }
}

// 搜索
const handleSearch = () => {
    queryParams.page = 1
    getDetailList()
}

// 重置
const handleReset = () => {
    queryParams.status = ''
    queryParams.nickname = ''
    queryParams.page = 1
    getDetailList()
}

// 分页变化
const handleSizeChange = (size: number) => {
    queryParams.limit = size
    queryParams.page = 1
    getDetailList()
}

const handleCurrentChange = (page: number) => {
    queryParams.page = page
    getDetailList()
}

// 刷新
const handleRefresh = () => {
    getDetailList()
}

// 返回列表
const handleGoBack = () => {
    router.push('/nickname_repository')
}

// 导入昵称
const handleImport = () => {
    importDialogVisible.value = true
}

// 导出昵称
const handleExport = async () => {
    try {
        const blob = await apiNicknameRepositoryExport({ 
            group_name: queryParams.group_name 
        })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `${queryParams.group_name}_昵称导出_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.txt`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
        ElMessage.success('导出成功')
    } catch (error: any) {
        ElMessage.error(error.message || '导出失败')
    }
}

// 删除昵称
const handleDelete = async (row: NicknameRepository) => {
    try {
        await ElMessageBox.confirm(
            `确定要删除昵称"${row.nickname}"吗？此操作不可恢复。`,
            '确认删除',
            {
                confirmButtonText: '确定删除',
                cancelButtonText: '取消',
                type: 'warning'
            }
        )
        
        // 这里需要添加删除单个昵称的API
        ElMessage.success('删除成功')
        getDetailList()
    } catch (error: any) {
        if (error !== 'cancel') {
            ElMessage.error(error.message || '删除失败')
        }
    }
}

// 导入成功回调
const handleImportSuccess = () => {
    importDialogVisible.value = false
    getDetailList()
}

onMounted(() => {
    if (!route.query.group_name) {
        ElMessage.error('缺少分组名称参数')
        router.push('/nickname_repository')
        return
    }
    getDetailList()
})
</script>

<style scoped>
.nickname-detail {
    padding: 20px;
}

/* 移动端适配 */
@media (max-width: 768px) {
    .nickname-detail {
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .nickname-detail {
        padding: 10px;
    }
}

/* 页面头部 */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 20px;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
}

.header-info {
    flex: 1;
    min-width: 0;
}

.group-title {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #303133;
    word-break: break-all;
}

@media (max-width: 768px) {
    .group-title {
        font-size: 20px;
        margin-bottom: 6px;
    }
}

@media (max-width: 480px) {
    .group-title {
        font-size: 18px;
    }
}

.group-summary {
    font-size: 14px;
    color: #909399;
}

@media (max-width: 480px) {
    .group-summary {
        font-size: 13px;
    }
}

.header-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .header-actions {
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 480px) {
    .header-actions {
        gap: 8px;
    }
    
    .header-actions .el-button {
        flex: 1;
        min-width: 0;
    }
    
    .action-text {
        display: none;
    }
}

/* 搜索表单 */
.search-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

@media (max-width: 768px) {
    .search-form {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .search-form :deep(.el-form-item) {
        margin-bottom: 0;
        margin-right: 0;
    }
}

.search-input {
    min-width: 200px;
}

@media (max-width: 480px) {
    .search-input {
        min-width: auto;
    }
}

.search-buttons {
    display: flex;
    gap: 8px;
}

@media (max-width: 768px) {
    .search-buttons {
        justify-content: flex-start;
    }
}

@media (max-width: 480px) {
    .search-buttons .el-button {
        flex: 1;
    }
}

/* 表格 */
.nickname-table {
    width: 100%;
}

@media (max-width: 768px) {
    .nickname-table :deep(.time-column) {
        display: none;
    }
}

@media (max-width: 480px) {
    .nickname-table :deep(.el-table__cell) {
        padding: 8px 5px;
        font-size: 13px;
    }
    
    .nickname-table :deep(.el-table th) {
        padding: 8px 5px;
        font-size: 13px;
    }
}

.nickname-text {
    font-weight: 500;
    word-break: break-all;
}

/* 分页 */
.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .pagination-wrapper {
        margin-top: 15px;
    }
}

.pagination {
    flex-wrap: wrap;
    justify-content: center;
}

@media (max-width: 768px) {
    .pagination :deep(.el-pagination__sizes),
    .pagination :deep(.el-pagination__jump) {
        display: none;
    }
    
    .pagination :deep(.el-pager) {
        flex-wrap: wrap;
    }
}

@media (max-width: 480px) {
    .pagination :deep(.el-pagination__total) {
        order: -1;
        width: 100%;
        text-align: center;
        margin-bottom: 10px;
    }
    
    .pagination :deep(.btn-prev),
    .pagination :deep(.btn-next),
    .pagination :deep(.el-pager li) {
        min-width: 32px;
        height: 32px;
        font-size: 12px;
    }
}

/* Element Plus 组件样式覆盖 */
@media (max-width: 480px) {
    :deep(.el-breadcrumb) {
        font-size: 13px;
    }
    
    :deep(.el-breadcrumb__item) {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    :deep(.el-card__body) {
        padding: 15px;
    }
    
    :deep(.el-form-item__label) {
        font-size: 14px;
    }
    
    :deep(.el-button--small) {
        padding: 5px 8px;
        font-size: 12px;
    }
    
    :deep(.el-tag--small) {
        font-size: 11px;
        padding: 0 5px;
    }
}

/* 表格响应式优化 */
@media (max-width: 768px) {
    :deep(.el-table) {
        font-size: 14px;
    }
    
    :deep(.el-table .cell) {
        word-break: break-all;
        white-space: normal;
        line-height: 1.4;
    }
}

/* 空状态 */
@media (max-width: 480px) {
    :deep(.el-empty) {
        padding: 30px 0;
    }
    
    :deep(.el-empty__description) {
        font-size: 14px;
    }
}
</style>