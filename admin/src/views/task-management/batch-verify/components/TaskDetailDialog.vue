<template>
    <el-dialog
        v-model="visible"
        title="任务详情"
        width="90%"
        top="5vh"
        :before-close="handleClose"
        class="task-detail-dialog">
        <div v-loading="loading">
            <!-- 任务基本信息 -->
            <el-card class="mb-4" shadow="never" header="任务基本信息">
                <el-row :gutter="20">
                    <el-col :span="8">
                        <div class="info-item">
                            <span class="label">任务名称：</span>
                            <span class="value">{{ taskDetail.task_name }}</span>
                        </div>
                    </el-col>
                    <el-col :span="8">
                        <div class="info-item">
                            <span class="label">任务状态：</span>
                            <el-tag :type="getStatusTagType(taskDetail.task_status)" size="small">
                                {{ taskDetail.task_status_desc }}
                            </el-tag>
                        </div>
                    </el-col>
                    <el-col :span="8">
                        <div class="info-item">
                            <span class="label">创建人：</span>
                            <span class="value">{{ taskDetail.createAdmin?.name || '未知' }}</span>
                        </div>
                    </el-col>
                </el-row>
                <el-row :gutter="20" class="mt-3">
                    <el-col :span="8">
                        <div class="info-item">
                            <span class="label">开始时间：</span>
                            <span class="value">{{ taskDetail.start_time_text || '未开始' }}</span>
                        </div>
                    </el-col>
                    <el-col :span="8">
                        <div class="info-item">
                            <span class="label">结束时间：</span>
                            <span class="value">{{ taskDetail.end_time_text || '未结束' }}</span>
                        </div>
                    </el-col>
                    <el-col :span="8">
                        <div class="info-item">
                            <span class="label">执行耗时：</span>
                            <span class="value">{{ taskDetail.duration_text || '-' }}</span>
                        </div>
                    </el-col>
                </el-row>
            </el-card>

            <!-- 执行进度 -->
            <el-card class="mb-4" shadow="never" header="执行进度">
                <el-row :gutter="20">
                    <el-col :span="12">
                        <div class="progress-section">
                            <div class="progress-header">
                                <span class="progress-title">总体进度</span>
                                <span class="progress-percent">{{ taskDetail.progress_percent }}%</span>
                            </div>
                            <el-progress 
                                :percentage="taskDetail.progress_percent" 
                                :status="getProgressStatus(taskDetail.task_status)"
                                :stroke-width="20"
                                text-inside
                            />
                        </div>
                    </el-col>
                    <el-col :span="12">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value total">{{ taskDetail.total_count }}</div>
                                <div class="stat-label">总数</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value processed">{{ taskDetail.processed_count }}</div>
                                <div class="stat-label">已处理</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value success">{{ taskDetail.success_count }}</div>
                                <div class="stat-label">成功</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value failed">{{ taskDetail.failed_count }}</div>
                                <div class="stat-label">失败</div>
                            </div>
                        </div>
                    </el-col>
                </el-row>
                <div class="mt-4" v-if="taskDetail.success_rate !== undefined">
                    <span class="label">成功率：</span>
                    <el-tag :type="taskDetail.success_rate >= 80 ? 'success' : taskDetail.success_rate >= 60 ? 'warning' : 'danger'" size="small">
                        {{ taskDetail.success_rate }}%
                    </el-tag>
                </div>
            </el-card>

            <!-- 执行详情列表 -->
            <el-card shadow="never" header="执行详情">
                <div class="detail-controls mb-4">
                    <el-select v-model="detailStatus" placeholder="筛选状态" clearable @change="getDetailList" style="width: 150px;">
                        <el-option label="全部" value="" />
                        <el-option label="待处理" value="pending" />
                        <el-option label="成功" value="success" />
                        <el-option label="失败" value="failed" />
                    </el-select>
                    <el-button type="primary" @click="getDetailList" :loading="detailLoading">
                        刷新详情
                    </el-button>
                </div>
                
                <el-table 
                    v-loading="detailLoading" 
                    :data="detailList.list" 
                    size="small"
                    max-height="400">
                    <el-table-column label="账号ID" prop="account_id" width="80" />
                    <el-table-column label="自定义ID" prop="account_uid" width="120" show-overflow-tooltip>
                        <template #default="{ row }">
                            {{ row.account_uid || '-' }}
                        </template>
                    </el-table-column>
                    <el-table-column label="账号信息" min-width="150" show-overflow-tooltip>
                        <template #default="{ row }">
                            <div v-if="row.account">
                                <div>MID: {{ row.account.mid }}</div>
                                <div class="text-sm text-gray-500">昵称: {{ row.account.nickname || '无' }}</div>
                            </div>
                            <div v-else class="text-gray-400">账号已删除</div>
                        </template>
                    </el-table-column>
                    <el-table-column label="处理状态" prop="status" width="100">
                        <template #default="{ row }">
                            <el-tag :type="getDetailStatusTagType(row.status)" size="small">
                                {{ row.status_icon }} {{ row.status_desc }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="处理结果" prop="result_message" min-width="200" show-overflow-tooltip>
                        <template #default="{ row }">
                            <div v-if="row.result_message">
                                {{ row.result_message }}
                                <el-tag v-if="row.token_refreshed" type="info" size="small" class="ml-2">
                                    🔄 Token已刷新
                                </el-tag>
                            </div>
                            <span v-else class="text-gray-400">-</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="处理时间" prop="process_time_text" width="160">
                        <template #default="{ row }">
                            {{ row.process_time_text || '-' }}
                        </template>
                    </el-table-column>
                </el-table>

                <!-- 详情分页 -->
                <div class="flex mt-4 justify-end" v-if="detailList.count > 0">
                    <el-pagination
                        v-model:current-page="detailPage.page"
                        v-model:page-size="detailPage.limit"
                        :total="detailList.count"
                        :page-sizes="[20, 50, 100]"
                        layout="total, sizes, prev, pager, next, jumper"
                        @current-change="getDetailList"
                        @size-change="getDetailList"
                    />
                </div>
            </el-card>
        </div>

        <template #footer>
            <div class="dialog-footer">
                <el-button 
                    v-if="taskDetail.task_status === 'pending' || taskDetail.task_status === 'running'"
                    type="danger" 
                    @click="handleCancelTask">
                    取消任务
                </el-button>
                <el-button @click="handleClose">关闭</el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup>
import { ref, watch, computed } from 'vue'
import { apiBatchTaskDetail, apiBatchTaskCancel, apiBatchTaskDetailList } from '@/api/task-management'
import feedback from '@/utils/feedback'

interface Props {
    modelValue: boolean
    taskId: number
}

interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'refresh'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// 对话框显示状态
const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// 任务详情数据
const taskDetail = ref<any>({})
const loading = ref(false)

// 执行详情数据
const detailList = ref<any>({ list: [], count: 0 })
const detailLoading = ref(false)
const detailStatus = ref('')
const detailPage = ref({ page: 1, limit: 20 })

// 获取状态标签类型
const getStatusTagType = (status: string) => {
    switch (status) {
        case 'pending': return 'info'
        case 'running': return 'warning'
        case 'completed': return 'success'
        case 'failed': return 'danger'
        case 'cancelled': return 'info'
        default: return ''
    }
}

// 获取详情状态标签类型
const getDetailStatusTagType = (status: string) => {
    switch (status) {
        case 'pending': return 'info'
        case 'success': return 'success'
        case 'failed': return 'danger'
        default: return ''
    }
}

// 获取进度条状态
const getProgressStatus = (status: string) => {
    switch (status) {
        case 'completed': return 'success'
        case 'failed': return 'exception'
        default: return undefined
    }
}

// 获取任务详情
const getTaskDetail = async () => {
    if (!props.taskId) return
    
    loading.value = true
    try {
        console.log('开始获取任务详情, taskId:', props.taskId)
        const res = await apiBatchTaskDetail({ id: props.taskId })
        console.log('API响应:', res)
        
        // 检查响应结构
        if (res && typeof res === 'object') {
            // 如果响应有 code 字段，按标准结构处理
            if ('code' in res) {
                if (res.code === 1) {
                    console.log('任务详情数据:', res.data)
                    taskDetail.value = res.data
                } else {
                    console.error('API返回错误:', res)
                    feedback.msgError(res.msg || '获取任务详情失败')
                }
            } else {
                // 如果响应直接是数据对象，直接使用
                console.log('直接使用响应数据:', res)
                taskDetail.value = res
            }
        } else {
            feedback.msgError('获取任务详情失败：响应数据格式错误')
        }
    } catch (error: any) {
        console.error('API调用异常:', error)
        feedback.msgError(error.message || '获取任务详情失败')
    } finally {
        loading.value = false
    }
}

// 获取执行详情列表
const getDetailList = async () => {
    if (!props.taskId) return
    
    detailLoading.value = true
    try {
        const params = {
            id: props.taskId,
            status: detailStatus.value,
            page: detailPage.value.page,
            limit: detailPage.value.limit
        }
        
        const res = await apiBatchTaskDetailList(params)
        console.log('执行详情列表API响应:', res)
        
        // 检查响应结构
        if (res && typeof res === 'object') {
            // 如果响应有 code 字段，按标准结构处理
            if ('code' in res) {
                if (res.code === 1) {
                    console.log('执行详情列表数据:', res.data)
                    detailList.value = res.data
                }
            } else {
                // 如果响应直接是数据对象，直接使用
                console.log('直接使用执行详情列表数据:', res)
                detailList.value = res
            }
        }
    } catch (error: any) {
        console.error('获取详情列表失败:', error)
    } finally {
        detailLoading.value = false
    }
}

// 取消任务
const handleCancelTask = async () => {
    try {
        await feedback.confirm('确定要取消此任务吗？取消后任务将无法继续执行。')
        
        const res = await apiBatchTaskCancel({ id: props.taskId })
        console.log('取消任务API响应:', res)
        
        // 检查响应结构
        if (res && typeof res === 'object') {
            // 如果响应有 code 字段，按标准结构处理
            if ('code' in res) {
                if (res.code === 1) {
                    feedback.msgSuccess('任务取消成功')
                    taskDetail.value.task_status = 'cancelled'
                    taskDetail.value.task_status_desc = '已取消'
                    emit('refresh')
                } else {
                    feedback.msgError(res.msg || '取消任务失败')
                }
            } else {
                // 如果响应直接是成功数据对象或者没有错误，认为成功
                console.log('取消任务成功，直接响应数据:', res)
                feedback.msgSuccess('任务取消成功')
                taskDetail.value.task_status = 'cancelled'
                taskDetail.value.task_status_desc = '已取消'
                emit('refresh')
            }
        } else {
            feedback.msgError('取消任务失败：响应数据格式错误')
        }
    } catch (error: any) {
        if (error !== 'cancel') {
            feedback.msgError(error.message || '取消任务失败')
        }
    }
}

// 关闭对话框
const handleClose = () => {
    visible.value = false
    detailStatus.value = ''
    detailPage.value = { page: 1, limit: 20 }
}

// 监听任务ID变化
watch(() => props.taskId, (newId) => {
    if (newId && visible.value) {
        getTaskDetail()
        getDetailList()
    }
}, { immediate: true })

// 定时器引用
let detailRefreshInterval: NodeJS.Timeout | null = null

// 启动详情定时刷新
const startDetailAutoRefresh = () => {
    stopDetailAutoRefresh()
    
    detailRefreshInterval = setInterval(async () => {
        if (props.taskId && visible.value) {
            // 如果任务还在运行中，则刷新详情
            if (taskDetail.value.task_status === 'running') {
                try {
                    await getTaskDetail()
                    await getDetailList()
                } catch (error) {
                    console.error('自动刷新任务详情失败:', error)
                }
            } else {
                // 任务完成时停止定时刷新
                stopDetailAutoRefresh()
            }
        }
    }, 5000) // 每5秒刷新一次
}

// 停止详情定时刷新
const stopDetailAutoRefresh = () => {
    if (detailRefreshInterval) {
        clearInterval(detailRefreshInterval)
        detailRefreshInterval = null
    }
}

// 监听对话框显示状态
watch(visible, (newVisible) => {
    if (newVisible && props.taskId) {
        getTaskDetail()
        getDetailList()
        startDetailAutoRefresh()
    } else {
        stopDetailAutoRefresh()
    }
})

// 组件卸载时清理定时器
import { onBeforeUnmount } from 'vue'
onBeforeUnmount(() => {
    stopDetailAutoRefresh()
})
</script>

<style scoped>
.task-detail-dialog :deep(.el-dialog__body) {
    padding: 20px;
    max-height: 80vh;
    overflow-y: auto;
}

.info-item {
    margin-bottom: 12px;
}

.info-item .label {
    font-weight: 500;
    color: #606266;
}

.info-item .value {
    color: #303133;
}

.progress-section {
    padding: 10px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.progress-title {
    font-weight: 500;
    color: #303133;
}

.progress-percent {
    font-weight: bold;
    color: #409EFF;
    font-size: 18px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    padding: 10px;
}

.stat-item {
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    background-color: #f8f9fa;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-value.total {
    color: #909399;
}

.stat-value.processed {
    color: #409EFF;
}

.stat-value.success {
    color: #67C23A;
}

.stat-value.failed {
    color: #F56C6C;
}

.stat-label {
    font-size: 12px;
    color: #606266;
}

.detail-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}
</style>