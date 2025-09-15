<template>
    <div>
        <!-- 统计卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card class="stats-card">
                    <div class="stats-content">
                        <div class="stats-value">{{ stats.total_tasks }}</div>
                        <div class="stats-label">总任务数</div>
                    </div>
                    <div class="stats-icon">
                        <el-icon><icon name="el-icon-Document" /></el-icon>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stats-card running">
                    <div class="stats-content">
                        <div class="stats-value">{{ stats.running_tasks }}</div>
                        <div class="stats-label">执行中任务</div>
                    </div>
                    <div class="stats-icon">
                        <el-icon><icon name="el-icon-Loading" /></el-icon>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stats-card completed">
                    <div class="stats-content">
                        <div class="stats-value">{{ stats.completed_tasks }}</div>
                        <div class="stats-label">已完成任务</div>
                    </div>
                    <div class="stats-icon">
                        <el-icon><icon name="el-icon-Check" /></el-icon>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stats-card failed">
                    <div class="stats-content">
                        <div class="stats-value">{{ stats.failed_tasks }}</div>
                        <div class="stats-label">失败任务</div>
                    </div>
                    <div class="stats-icon">
                        <el-icon><icon name="el-icon-Close" /></el-icon>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 搜索表单 -->
        <el-card class="!border-none mb-4" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" inline>
                <el-form-item label="任务名称" prop="task_name">
                    <el-input class="w-[180px]" v-model="queryParams.task_name" clearable placeholder="请输入任务名称" />
                </el-form-item>
                <el-form-item label="任务状态" prop="task_status">
                    <el-select class="w-[180px]" v-model="queryParams.task_status" clearable placeholder="请选择状态" style="width: 180px;">
                        <el-option label="全部" value=""></el-option>
                        <el-option label="等待中" value="pending"></el-option>
                        <el-option label="执行中" value="running"></el-option>
                        <el-option label="已完成" value="completed"></el-option>
                        <el-option label="已失败" value="failed"></el-option>
                        <el-option label="已取消" value="cancelled"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="创建时间">
                    <el-date-picker
                        v-model="dateRange"
                        type="daterange"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        format="YYYY-MM-DD"
                        value-format="YYYY-MM-DD"
                        @change="handleDateRangeChange"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="customResetPage">查询</el-button>
                    <el-button @click="customResetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 任务列表 -->
        <el-card class="!border-none" shadow="never">
            <div>
                <el-table v-loading="pager.loading" :data="pager.lists">
                    <el-table-column label="任务ID" prop="id" width="80" />
                    <el-table-column label="任务名称" prop="task_name" min-width="200" show-overflow-tooltip />
                    <el-table-column label="任务状态" prop="task_status" width="120">
                        <template #default="{ row }">
                            <el-tag 
                                :type="getStatusTagType(row.task_status)" 
                                :effect="row.task_status === 'running' ? 'plain' : 'light'"
                                size="small"
                                :class="{ 'animate-pulse': row.task_status === 'running' }">
                                {{ row.task_status_desc }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="进度" width="180">
                        <template #default="{ row }">
                            <div class="progress-container">
                                <el-progress 
                                    :percentage="row.progress_percent" 
                                    :status="getProgressStatus(row.task_status)"
                                    :stroke-width="18"
                                    text-inside
                                />
                                <div class="progress-text">
                                    {{ row.processed_count }}/{{ row.total_count }}
                                </div>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="成功/失败" width="120">
                        <template #default="{ row }">
                            <div class="text-center">
                                <div class="text-green-600">✓ {{ row.success_count }}</div>
                                <div class="text-red-600">✗ {{ row.failed_count }}</div>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="执行耗时" prop="duration_text" width="120" />
                    <el-table-column label="创建时间" prop="create_time" width="160">
                        <template #default="{ row }">
                            {{ formatTime(row.create_time) }}
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="160" fixed="right">
                        <template #default="{ row }">
                            <div class="flex items-center gap-1">
                                <el-button type="primary" link size="small" @click="handleViewDetail(row)">
                                    详情
                                </el-button>
                                <el-button 
                                    v-if="row.task_status === 'pending' || row.task_status === 'running'"
                                    type="danger" 
                                    link 
                                    size="small"
                                    @click="handleCancelTask(row)">
                                    取消
                                </el-button>
                                <el-button 
                                    v-if="row.task_status === 'running'"
                                    type="info" 
                                    link 
                                    size="small"
                                    @click="handleRefreshProgress(row)">
                                    刷新
                                </el-button>
                            </div>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
            <div class="flex mt-4 justify-end">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 任务详情弹窗 -->
        <task-detail-dialog 
            v-if="showTaskDetail" 
            v-model="showTaskDetail" 
            :task-id="selectedTaskId"
            @refresh="getLists" />
    </div>
</template>

<script lang="ts" setup name="batchVerifyTaskLists">
import { reactive, ref, onMounted, onActivated, watch } from 'vue'
import { useRoute } from 'vue-router'
import { usePaging } from '@/hooks/usePaging'
import { apiBatchTaskLists, apiBatchTaskCancel, apiBatchTaskProgress, apiBatchTaskStats } from '@/api/task-management'
import feedback from '@/utils/feedback'
import TaskDetailDialog from './components/TaskDetailDialog.vue'

// 查询参数
const queryParams = reactive({
    task_name: '',
    task_status: '',
    start_time: '',
    end_time: ''
})

// 分页器
const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: apiBatchTaskLists,
    params: queryParams
})

// 日期范围
const dateRange = ref<[string, string] | null>(null)

// 处理日期范围变化
const handleDateRangeChange = (value: [string, string] | null) => {
    if (value) {
        queryParams.start_time = value[0]
        queryParams.end_time = value[1]
    } else {
        queryParams.start_time = ''
        queryParams.end_time = ''
    }
}

// 统计数据
const stats = ref({
    total_tasks: 0,
    running_tasks: 0,
    completed_tasks: 0,
    failed_tasks: 0,
    total_accounts: 0,
    success_accounts: 0,
    failed_accounts: 0
})

// 任务详情弹窗
const showTaskDetail = ref(false)
const selectedTaskId = ref(0)

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

// 获取进度条状态
const getProgressStatus = (status: string) => {
    switch (status) {
        case 'completed': return 'success'
        case 'failed': return 'exception'
        default: return undefined
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

// 查看任务详情
const handleViewDetail = (row: any) => {
    selectedTaskId.value = row.id
    showTaskDetail.value = true
}

// 取消任务
const handleCancelTask = async (row: any) => {
    try {
        await feedback.confirm('确定要取消此任务吗？取消后任务将无法继续执行。')
        
        const res = await apiBatchTaskCancel({ id: row.id })
        console.log('取消任务API响应:', res)
        
        // 检查响应结构
        if (res && typeof res === 'object') {
            // 如果响应有 code 字段，按标准结构处理
            if ('code' in res) {
                if (res.code === 1) {
                    feedback.msgSuccess('任务取消成功')
                    getLists()
                } else {
                    feedback.msgError(res.msg || '取消任务失败')
                }
            } else {
                // 如果响应直接是成功数据对象或者没有错误，认为成功
                console.log('取消任务成功，直接响应数据:', res)
                feedback.msgSuccess('任务取消成功')
                getLists()
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

// 刷新任务进度
const handleRefreshProgress = async (row: any, showMessage: boolean = true) => {
    try {
        const res = await apiBatchTaskProgress({ id: row.id })
        if (res.code === 1) {
            // 更新列表中的对应行数据
            const index = pager.lists.findIndex((item: any) => item.id === row.id)
            if (index !== -1) {
                Object.assign(pager.lists[index], {
                    task_status: res.data.task_status,
                    task_status_desc: res.data.task_status_desc,
                    processed_count: res.data.processed_count,
                    success_count: res.data.success_count,
                    failed_count: res.data.failed_count,
                    progress_percent: res.data.progress_percent,
                    duration_text: res.data.duration_text
                })
            }
            if (showMessage) {
                feedback.msgSuccess('进度刷新成功')
            }
            return res.data
        }
        return null
    } catch (error: any) {
        // 忽略取消的请求错误
        if (error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
            console.log(`任务${row.id}进度刷新请求被取消（正常情况）`)
            return null
        }
        
        if (showMessage) {
            feedback.msgError(error.message || '刷新进度失败')
        }
        console.error(`刷新任务${row.id}进度失败:`, error)
        throw error
    }
}

// 获取统计数据
const getStats = async () => {
    try {
        const res = await apiBatchTaskStats()
        console.log('租户统计API响应:', res)
        
        // 检查响应结构
        if (res && typeof res === 'object') {
            // 如果响应有 code 字段，按标准结构处理
            if ('code' in res) {
                if (res.code === 1) {
                    console.log('租户统计数据:', res.data)
                    stats.value = res.data
                }
            } else {
                // 如果响应直接是数据对象，直接使用
                console.log('直接使用租户统计数据:', res)
                stats.value = res
            }
        }
    } catch (error: any) {
        // 忽略取消的请求错误
        if (error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
            console.log('统计数据请求被取消（正常情况）')
            return
        }
        console.error('获取统计数据失败:', error)
    }
}

// 重写重置参数函数
const customResetParams = () => {
    Object.keys(queryParams).forEach(key => {
        queryParams[key as keyof typeof queryParams] = ''
    })
    dateRange.value = null
    resetParams()
}

// 自定义重置页面函数（查询）
const customResetPage = () => {
    resetPage()
}

// 定时器引用
let refreshInterval: NodeJS.Timeout | null = null
let isRefreshing = ref(false)

// 启动定时刷新
const startAutoRefresh = () => {
    stopAutoRefresh() // 确保之前的定时器被清理
    
    refreshInterval = setInterval(async () => {
        if (isRefreshing.value) return // 防止重复刷新
        
        const runningTasks = pager.lists.filter((task: any) => task.task_status === 'running')
        if (runningTasks.length > 0) {
            isRefreshing.value = true
            try {
                // 并发刷新所有运行中的任务，不显示提示消息
                await Promise.all(runningTasks.map(task => handleRefreshProgress(task, false)))
                await getStats() // 刷新统计数据
            } catch (error: any) {
                // 忽略取消的请求错误
                if (error.name !== 'CanceledError' && error.code !== 'ERR_CANCELED') {
                    console.error('自动刷新任务进度失败:', error)
                }
            } finally {
                isRefreshing.value = false
            }
        }
    }, 10000) // 每10秒刷新一次
}

// 停止定时刷新
const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval)
        refreshInterval = null
    }
}

// 获取路由信息
const route = useRoute()

// 刷新页面数据的函数
const refreshPageData = () => {
    console.log('刷新页面数据')
    getLists()
    getStats()
}

onMounted(() => {
    refreshPageData()
    startAutoRefresh()
})

// 页面激活时刷新（解决跳转后不更新的问题）
onActivated(() => {
    console.log('页面激活，刷新数据')
    refreshPageData()
})

// 监听路由变化，确保从其他页面跳转时也能刷新
watch(() => route.fullPath, (newPath) => {
    if (newPath.includes('/task-management/batch-verify')) {
        console.log('路由跳转到任务管理页面，刷新数据')
        setTimeout(() => {
            refreshPageData()
        }, 100) // 稍微延迟确保页面完全加载
    }
})

// 组件卸载时清理定时器
onBeforeUnmount(() => {
    stopAutoRefresh()
})

// 需要导入 onBeforeUnmount
import { onBeforeUnmount } from 'vue'

// 重新绑定函数 - 移除有问题的重写
</script>

<style scoped>
.stats-card {
    position: relative;
    overflow: hidden;
    border: none;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
}

.stats-card .el-card__body {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stats-content {
    flex: 1;
}

.stats-value {
    font-size: 2rem;
    font-weight: bold;
    color: #303133;
    margin-bottom: 8px;
}

.stats-label {
    color: #909399;
    font-size: 14px;
}

.stats-icon {
    font-size: 2.5rem;
    opacity: 0.3;
    color: #409EFF;
}

.stats-card.running .stats-icon {
    color: #E6A23C;
}

.stats-card.completed .stats-icon {
    color: #67C23A;
}

.stats-card.failed .stats-icon {
    color: #F56C6C;
}

.progress-container {
    width: 100%;
}

.progress-text {
    font-size: 12px;
    color: #909399;
    text-align: center;
    margin-top: 4px;
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
</style>