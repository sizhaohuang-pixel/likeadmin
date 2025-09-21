<template>
    <el-dialog 
        v-model="dialogVisible" 
        :title="dialogTitle" 
        :width="dialogWidth"
        :close-on-click-modal="false"
        destroy-on-close
    >
        <div class="nickname-detail-dialog">
            <!-- 搜索和过滤区域 -->
            <el-form :model="queryParams" :inline="true" class="search-form mb-4">
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
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">搜索</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <!-- 数据列表 -->
            <el-table 
                :data="nicknameList" 
                v-loading="loading"
                stripe
                style="width: 100%"
                max-height="400"
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
        </div>

        <template #footer>
            <div class="dialog-footer">
                <el-button @click="handleClose">关闭</el-button>
                <el-button type="success" @click="handleExport" :disabled="availableCount === 0">
                    剩余数据导出
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup name="nicknameDetailDialog">
import { ref, reactive, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { 
    apiNicknameRepositoryDetail,
    apiNicknameRepositoryExport 
} from '@/api/nickname_repository'
import type { NicknameRepository, NicknameDetailQuery } from '@/typings/nickname-repository'

interface Props {
    modelValue: boolean
    groupName: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
    'update:modelValue': [value: boolean]
}>()

// 弹窗显示状态
const dialogVisible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// 数据状态
const loading = ref(false)
const nicknameList = ref<NicknameRepository[]>([])

// 查询参数
const queryParams = reactive<NicknameDetailQuery>({
    group_name: '',
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
const dialogTitle = computed(() => `昵称明细 - ${props.groupName}`)

const availableCount = computed(() => {
    return nicknameList.value.filter(item => item.status === 1).length
})

// 响应式对话框宽度
const dialogWidth = computed(() => {
    if (typeof window !== 'undefined') {
        return window.innerWidth < 768 ? '95%' : '800px'
    }
    return '800px'
})

// 监听分组名称变化
watch(() => props.groupName, (newGroupName) => {
    if (newGroupName) {
        queryParams.group_name = newGroupName
        resetSearch()
    }
}, { immediate: true })

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
    if (!props.groupName) return
    
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
    resetSearch()
    getDetailList()
}

const resetSearch = () => {
    queryParams.status = ''
    queryParams.nickname = ''
    queryParams.page = 1
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

// 导出昵称
const handleExport = async () => {
    try {
        const response = await apiNicknameRepositoryExport({ 
            group_name: queryParams.group_name 
        })
        
        // 检查响应是否为 Blob
        if (!(response instanceof Blob)) {
            throw new Error('导出数据格式错误')
        }
        
        const url = window.URL.createObjectURL(response)
        const link = document.createElement('a')
        link.href = url
        link.download = `${queryParams.group_name}_昵称导出_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.txt`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
        ElMessage.success('导出成功')
    } catch (error: any) {
        console.error('导出错误:', error)
        ElMessage.error(error.message || '导出失败')
    }
}

// 关闭弹窗
const handleClose = () => {
    dialogVisible.value = false
}

// 监听弹窗打开，自动加载数据
watch(() => props.modelValue, (visible) => {
    if (visible && props.groupName) {
        getDetailList()
    }
})
</script>

<style scoped>
.nickname-detail-dialog {
    min-height: 300px;
}

.search-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

.search-input {
    min-width: 200px;
}

.nickname-text {
    font-weight: 500;
    word-break: break-all;
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.pagination {
    flex-wrap: wrap;
    justify-content: center;
}

/* 移动端适配 */
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
    
    .search-input {
        min-width: auto;
    }
    
    :deep(.time-column) {
        display: none;
    }
}

@media (max-width: 480px) {
    .nickname-detail-dialog {
        min-height: 250px;
    }
    
    :deep(.el-table__cell) {
        padding: 8px 5px;
        font-size: 13px;
    }
    
    :deep(.el-table th) {
        padding: 8px 5px;
        font-size: 13px;
    }
    
    .pagination :deep(.el-pagination__sizes),
    .pagination :deep(.el-pagination__jump) {
        display: none;
    }
}
</style>