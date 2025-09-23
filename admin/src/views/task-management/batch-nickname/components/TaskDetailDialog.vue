<template>
    <div>
        <el-dialog 
            v-model="dialogVisible" 
            title="任务执行详情" 
            width="90%"
            :close-on-click-modal="false"
            @close="handleClose">
            
            <div v-loading="loading" class="task-detail-content">
                <!-- 任务基本信息 -->
                <el-card class="task-info-card" shadow="never">
                    <template #header>
                        <div class="card-header">
                            <span>任务信息</span>
                            <el-button 
                                v-if="taskInfo.task_status === 'running'"
                                type="primary" 
                                size="small"
                                @click="refreshTaskInfo">
                                刷新
                            </el-button>
                        </div>
                    </template>
                    
                    <div class="task-info">
                        <el-row :gutter="20">
                            <el-col :span="8">
                                <div class="info-item">
                                    <span class="label">任务ID：</span>
                                    <span class="value">{{ taskInfo.id }}</span>
                                </div>
                            </el-col>
                            <el-col :span="8">
                                <div class="info-item">
                                    <span class="label">任务名称：</span>
                                    <span class="value">{{ taskInfo.task_name }}</span>
                                </div>
                            </el-col>
                            <el-col :span="8">
                                <div class="info-item">
                                    <span class="label">任务状态：</span>
                                    <el-tag 
                                        :type="getTaskStatusTagType(taskInfo.task_status)" 
                                        :effect="taskInfo.task_status === 'running' ? 'plain' : 'light'"
                                        size="small"
                                        :class="{ 'animate-pulse': taskInfo.task_status === 'running' }">
                                        {{ taskInfo.task_status_desc }}
                                    </el-tag>
                                </div>
                            </el-col>
                        </el-row>
                        
                        <el-row :gutter="20" class="mt-3">
                            <el-col :span="8">
                                <div class="info-item">
                                    <span class="label">总账号数：</span>
                                    <span class="value">{{ taskInfo.total_count }}</span>
                                </div>
                            </el-col>
                            <el-col :span="8">
                                <div class="info-item">
                                    <span class="label">已处理：</span>
                                    <span class="value">{{ taskInfo.processed_count }}</span>
                                </div>
                            </el-col>
                            <el-col :span="8">
                                <div class="info-item">
                                    <span class="label">执行进度：</span>
                                    <span class="value">{{ taskInfo.progress_percent }}%</span>
                                </div>
                            </el-col>
                        </el-row>
                        
                        <el-row :gutter="20" class="mt-3">
                            <el-col :span="8">
                                <div class="info-item">
                                    <span class="label">成功数量：</span>
                                    <span class="value text-green-600">{{ taskInfo.success_count }}</span>
                                </div>
                            </el-col>
                            <el-col :span="8">
                                <div class="info-item">
                                    <span class="label">失败数量：</span>
                                    <span class="value text-red-600">{{ taskInfo.failed_count }}</span>
                                </div>
                            </el-col>
                            <el-col :span="8">
                                <div class="info-item">
                                    <span class="label">执行耗时：</span>
                                    <span class="value">{{ taskInfo.duration_text || '-' }}</span>
                                </div>
                            </el-col>
                        </el-row>
                        
                        <el-row :gutter="20" class="mt-3">
                            <el-col :span="12">
                                <div class="info-item">
                                    <span class="label">创建时间：</span>
                                    <span class="value">{{ formatTime(taskInfo.create_time) }}</span>
                                </div>
                            </el-col>
                            <el-col :span="12">
                                <div class="info-item">
                                    <span class="label">开始时间：</span>
                                    <span class="value">{{ formatTime(taskInfo.start_time) }}</span>
                                </div>
                            </el-col>
                        </el-row>
                        
                        <!-- 错误信息 -->
                        <el-row v-if="taskInfo.error_message" class="mt-3">
                            <el-col :span="24">
                                <div class="info-item">
                                    <span class="label">错误信息：</span>
                                    <span class="value text-red-600">{{ taskInfo.error_message }}</span>
                                </div>
                            </el-col>
                        </el-row>
                        
                        <!-- 进度条 -->
                        <div class="progress-section mt-4">
                            <el-progress 
                                :percentage="taskInfo.progress_percent || 0" 
                                :status="getProgressStatus(taskInfo.task_status)"
                                :stroke-width="20"
                                text-inside
                            />
                        </div>
                    </div>
                </el-card>

                <!-- 执行详情列表 -->
            <el-card shadow="never" header="执行详情">
                <div class="detail-controls mb-4">
                    <el-select v-model="detailQuery.status" placeholder="筛选状态" clearable @change="getDetailList" style="width: 150px;">
                        <el-option label="全部" value="" />
                        <el-option label="待处理" value="pending" />
                        <el-option label="成功" value="success" />
                        <el-option label="失败" value="failed" />
                    </el-select>
                    <el-button type="primary" @click="refreshDetailList" :loading="detailLoading">
                        刷新详情
                    </el-button>
                </div>
                    
                <el-table 
                    v-loading="detailLoading" 
                    :data="detailPager.lists" 
                    size="small"
                    max-height="400">
                    <el-table-column label="账号ID" prop="account_id" width="80" />
                    <el-table-column label="自定义ID" prop="account_uid" width="120" show-overflow-tooltip>
                        <template #default="{ row }">
                            {{ row.account_uid || '-' }}
                        </template>
                    </el-table-column>
                    <el-table-column label="账号昵称" prop="account_nickname" min-width="120" show-overflow-tooltip>
                        <template #default="{ row }">
                            {{ row.account_nickname || row.account?.nickname || '-' }}
                        </template>
                    </el-table-column>
                    <el-table-column label="账号手机" prop="account_phone" min-width="120">
                        <template #default="{ row }">
                            {{ row.account_phone || row.account?.phone || '-' }}
                        </template>
                    </el-table-column>
                    <el-table-column label="处理状态" prop="status" width="100">
                        <template #default="{ row }">
                            <el-tag :type="getStatusTagType(row.status)" size="small">
                                {{ row.status_icon }} {{ row.status_desc }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="处理结果" prop="result_message" min-width="200" show-overflow-tooltip>
                        <template #default="{ row }">
                            <div v-if="row.result_message || row.error_message">
                                {{ row.result_message || row.error_message }}
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
                <div class="flex mt-4 justify-end" v-if="detailPager.count > 0">
                    <el-pagination
                        v-model:current-page="detailPager.page"
                        v-model:page-size="detailPager.limit"
                        :total="detailPager.count"
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
                    <el-button @click="handleClose">关闭</el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="TaskDetailDialog">
import { ref, reactive, computed, watch } from 'vue'
import { usePaging } from '@/hooks/usePaging'
import { apiBatchNicknameTaskDetail, apiBatchTaskDetailList } from '@/api/task-management'
import feedback from '@/utils/feedback'

// Props
interface Props {
    modelValue: boolean
    taskId: number
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'refresh': []
}>()

// 弹窗显示状态
const dialogVisible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// 加载状态
const loading = ref(false)
const detailLoading = ref(false)

// 任务信息
const taskInfo = ref<any>({})

// 详情查询参数
const detailQuery = reactive({
    task_id: 0,
    status: ''
})

// 详情分页器
const { pager: detailPager, getLists: getDetailList, resetPage: resetDetailPage } = usePaging({
    fetchFun: async (params: any) => {
        console.log('获取任务详情列表，参数:', params)
        console.log('当前detailQuery:', detailQuery)
        try {
            const result = await apiBatchTaskDetailList(params)
            console.log('任务详情列表API响应:', result)
            return result
        } catch (error: any) {
            // 忽略请求被取消的错误
            if (error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
                console.log('任务详情列表请求被取消（正常情况）')
                return { list: [], count: 0 }
            }
            throw error
        }
    },
    params: detailQuery,
    size: 10
})

// 获取任务状态标签类型
const getTaskStatusTagType = (status: string) => {
    switch (status) {
        case 'pending': return 'info'
        case 'running': return 'warning'
        case 'completed': return 'success'
        case 'failed': return 'danger'
        case 'cancelled': return 'info'
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

// 获取执行状态标签类型
const getStatusTagType = (status: string) => {
    switch (status) {
        case 'success': return 'success'
        case 'failed': return 'danger'
        case 'pending': return 'info'
        default: return 'info'
    }
}

// 格式化时间
const formatTime = (time: number | string) => {
    if (!time) return '-'
    
    // 如果是时间字符串格式 (YYYY-MM-DD HH:mm:ss)
    if (typeof time === 'string' && time.includes('-') && time.includes(':')) {
        return time.replace('T', ' ').substring(0, 19)
    }
    
    // 如果是时间戳
    const ts = typeof time === 'string' ? parseInt(time) : time
    if (!ts || isNaN(ts)) return '-'
    return new Date(ts * 1000).toLocaleString()
}

// 获取任务详情
const getTaskDetail = async () => {
    console.log('getTaskDetail 被调用，任务ID:', props.taskId)
    if (!props.taskId) {
        console.log('任务ID为空，终止获取任务详情')
        return
    }
    
    try {
        loading.value = true
        console.log('开始调用 apiBatchNicknameTaskDetail API，参数:', { id: props.taskId })
        const res = await apiBatchNicknameTaskDetail({ id: props.taskId })
        console.log('任务详情API响应:', res)
        
        if (res && typeof res === 'object') {
            if ('code' in res && res.code === 1) {
                taskInfo.value = res.data || {}
                console.log('任务详情设置成功:', taskInfo.value)
            } else {
                taskInfo.value = res
                console.log('任务详情设置成功(直接):', taskInfo.value)
            }
        }
    } catch (error: any) {
        // 忽略请求被取消的错误
        if (error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
            console.log('任务详情请求被取消（正常情况）')
            return
        }
        console.error('获取任务详情失败:', error)
        feedback.msgError(error.message || '获取任务详情失败')
    } finally {
        loading.value = false
    }
}

// 刷新任务信息
const refreshTaskInfo = async () => {
    await getTaskDetail()
    emit('refresh') // 通知父组件刷新列表
}

// 刷新详情列表
const refreshDetailList = () => {
    getDetailList()
}

// 关闭弹窗
const handleClose = () => {
    emit('update:modelValue', false)
}

// 监听弹窗显示状态
watch(dialogVisible, (visible) => {
    console.log('任务详情弹窗状态变化:', visible, '任务ID:', props.taskId)
    if (visible && props.taskId) {
        detailQuery.task_id = props.taskId
        console.log('开始获取任务详情，任务ID:', props.taskId)
        console.log('设置detailQuery.task_id为:', detailQuery.task_id)
        getTaskDetail()
        // 稍微延迟一下获取详情列表，确保任务详情已经加载
        setTimeout(() => {
            resetDetailPage()
        }, 100)
    }
})

// 监听任务ID变化
watch(() => props.taskId, (newTaskId) => {
    console.log('任务ID变化:', newTaskId, '弹窗显示状态:', dialogVisible.value)
    if (newTaskId && dialogVisible.value) {
        detailQuery.task_id = newTaskId
        console.log('重新获取任务详情，任务ID:', newTaskId)
        console.log('重新设置detailQuery.task_id为:', detailQuery.task_id)
        getTaskDetail()
        setTimeout(() => {
            resetDetailPage()
        }, 100)
    }
})
</script>

<style scoped>
.task-detail-content {
    min-height: 200px;
}

.task-info-card,
.detail-list-card {
    border: 1px solid #e4e7ed;
}

.detail-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    color: #303133;
}

.task-info {
    padding: 0;
}

.info-item {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}

.info-item .label {
    color: #606266;
    font-size: 14px;
    min-width: 80px;
    flex-shrink: 0;
}

.info-item .value {
    color: #303133;
    font-size: 14px;
    word-break: break-all;
}

.progress-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #f5f7fa;
}

.mt-3 {
    margin-top: 12px;
}

.mt-4 {
    margin-top: 16px;
}

.text-green-600 {
    color: #67c23a;
}

.text-red-600 {
    color: #f56c6c;
}

.animate-pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.text-gray-400 {
    color: #a1a1aa;
}

.ml-2 {
    margin-left: 8px;
}

.mb-4 {
    margin-bottom: 16px;
}
</style>