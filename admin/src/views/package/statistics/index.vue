<template>
    <div class="package-statistics">
        <!-- 统计卡片 -->
        <div class="grid grid-cols-4 gap-4 mb-6">
            <el-card class="stat-card" shadow="never">
                <div class="stat-content">
                    <div class="stat-icon bg-blue-100">
                        <icon name="el-icon-DataBoard" class="text-blue-600" />
                    </div>
                    <div class="stat-info">
                        <div class="stat-number text-blue-600">{{ statistics.total_count }}</div>
                        <div class="stat-label">总记录数</div>
                    </div>
                </div>
            </el-card>
            
            <el-card class="stat-card" shadow="never">
                <div class="stat-content">
                    <div class="stat-icon bg-green-100">
                        <icon name="el-icon-Connection" class="text-green-600" />
                    </div>
                    <div class="stat-info">
                        <div class="stat-number text-green-600">{{ statistics.total_ports }}</div>
                        <div class="stat-label">总端口数</div>
                    </div>
                </div>
            </el-card>
            
            <el-card class="stat-card" shadow="never">
                <div class="stat-content">
                    <div class="stat-icon bg-orange-100">
                        <icon name="el-icon-SuccessFilled" class="text-orange-600" />
                    </div>
                    <div class="stat-info">
                        <div class="stat-number text-orange-600">{{ statistics.valid_count }}</div>
                        <div class="stat-label">有效记录数</div>
                    </div>
                </div>
            </el-card>
            
            <el-card class="stat-card" shadow="never">
                <div class="stat-content">
                    <div class="stat-icon bg-red-100">
                        <icon name="el-icon-WarningFilled" class="text-red-600" />
                    </div>
                    <div class="stat-info">
                        <div class="stat-number text-red-600">{{ statistics.expired_count }}</div>
                        <div class="stat-label">过期记录数</div>
                    </div>
                </div>
            </el-card>
        </div>

        <!-- 详细统计 -->
        <div class="grid grid-cols-2 gap-6">
            <!-- 端口统计 -->
            <el-card class="!border-none" shadow="never">
                <template #header>
                    <span class="text-lg font-medium">端口统计</span>
                </template>
                <div class="space-y-4">
                    <div class="stat-row">
                        <span class="stat-row-label">总端口数：</span>
                        <span class="stat-row-value text-blue-600">{{ statistics.total_ports }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-row-label">有效端口数：</span>
                        <span class="stat-row-value text-green-600">{{ statistics.valid_ports }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-row-label">过期端口数：</span>
                        <span class="stat-row-value text-red-600">{{ statistics.expired_ports }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-row-label">即将过期端口数：</span>
                        <span class="stat-row-value text-orange-600">{{ statistics.expiring_soon_ports }}</span>
                    </div>
                </div>
                
                <!-- 端口分布图 -->
                <div class="mt-6">
                    <div class="text-sm font-medium mb-3">端口分布</div>
                    <div class="space-y-2">
                        <div class="progress-item">
                            <div class="progress-label">有效端口 ({{ validPortPercentage.toFixed(1) }}%)</div>
                            <div class="progress-bar">
                                <div 
                                    class="progress-fill bg-green-500"
                                    :style="{ width: validPortPercentage + '%' }"
                                ></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-label">即将过期 ({{ expiringSoonPortPercentage.toFixed(1) }}%)</div>
                            <div class="progress-bar">
                                <div 
                                    class="progress-fill bg-orange-500"
                                    :style="{ width: expiringSoonPortPercentage + '%' }"
                                ></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-label">已过期 ({{ expiredPortPercentage.toFixed(1) }}%)</div>
                            <div class="progress-bar">
                                <div 
                                    class="progress-fill bg-red-500"
                                    :style="{ width: expiredPortPercentage + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </el-card>

            <!-- 记录统计 -->
            <el-card class="!border-none" shadow="never">
                <template #header>
                    <span class="text-lg font-medium">记录统计</span>
                </template>
                <div class="space-y-4">
                    <div class="stat-row">
                        <span class="stat-row-label">总记录数：</span>
                        <span class="stat-row-value text-blue-600">{{ statistics.total_count }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-row-label">有效记录数：</span>
                        <span class="stat-row-value text-green-600">{{ statistics.valid_count }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-row-label">过期记录数：</span>
                        <span class="stat-row-value text-red-600">{{ statistics.expired_count }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-row-label">即将过期记录数：</span>
                        <span class="stat-row-value text-orange-600">{{ statistics.expiring_soon_count }}</span>
                    </div>
                </div>
                
                <!-- 记录分布图 -->
                <div class="mt-6">
                    <div class="text-sm font-medium mb-3">记录分布</div>
                    <div class="space-y-2">
                        <div class="progress-item">
                            <div class="progress-label">有效记录 ({{ validCountPercentage.toFixed(1) }}%)</div>
                            <div class="progress-bar">
                                <div 
                                    class="progress-fill bg-green-500"
                                    :style="{ width: validCountPercentage + '%' }"
                                ></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-label">即将过期 ({{ expiringSoonCountPercentage.toFixed(1) }}%)</div>
                            <div class="progress-bar">
                                <div 
                                    class="progress-fill bg-orange-500"
                                    :style="{ width: expiringSoonCountPercentage + '%' }"
                                ></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-label">已过期 ({{ expiredCountPercentage.toFixed(1) }}%)</div>
                            <div class="progress-bar">
                                <div 
                                    class="progress-fill bg-red-500"
                                    :style="{ width: expiredCountPercentage + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </el-card>
        </div>

        <!-- 操作按钮 -->
        <div class="flex justify-center mt-6 gap-4">
            <el-button
                v-perms="['package/handle-expired']"
                type="warning"
                @click="handleExpired"
            >
                处理过期套餐
            </el-button>
            <el-button
                v-perms="['package/update-expired-status']"
                type="info"
                @click="updateExpiredStatus"
            >
                更新过期状态
            </el-button>
            <el-button @click="refreshStatistics">
                刷新数据
            </el-button>
        </div>
    </div>
</template>

<script lang="ts" setup name="packageStatistics">
import { packageStatistics, packageHandleExpired, packageUpdateExpiredStatus } from '@/api/package'
import feedback from '@/utils/feedback'

const loading = ref(false)

// 统计数据
const statistics = reactive({
    total_count: 0,
    total_ports: 0,
    valid_count: 0,
    valid_ports: 0,
    expired_count: 0,
    expired_ports: 0,
    expiring_soon_count: 0,
    expiring_soon_ports: 0
})

// 计算百分比
const validPortPercentage = computed(() => {
    return statistics.total_ports > 0 ? (statistics.valid_ports / statistics.total_ports) * 100 : 0
})

const expiredPortPercentage = computed(() => {
    return statistics.total_ports > 0 ? (statistics.expired_ports / statistics.total_ports) * 100 : 0
})

const expiringSoonPortPercentage = computed(() => {
    return statistics.total_ports > 0 ? (statistics.expiring_soon_ports / statistics.total_ports) * 100 : 0
})

const validCountPercentage = computed(() => {
    return statistics.total_count > 0 ? (statistics.valid_count / statistics.total_count) * 100 : 0
})

const expiredCountPercentage = computed(() => {
    return statistics.total_count > 0 ? (statistics.expired_count / statistics.total_count) * 100 : 0
})

const expiringSoonCountPercentage = computed(() => {
    return statistics.total_count > 0 ? (statistics.expiring_soon_count / statistics.total_count) * 100 : 0
})

// 获取统计数据
const getStatistics = async () => {
    loading.value = true
    try {
        const data = await packageStatistics({})
        Object.assign(statistics, data)
    } catch (error) {
        console.error('获取统计数据失败:', error)
    } finally {
        loading.value = false
    }
}

// 处理过期套餐
const handleExpired = async () => {
    await feedback.confirm('确定要处理过期套餐吗？此操作将释放过期套餐的小号。')
    try {
        const result = await packageHandleExpired({})
        feedback.msgSuccess(`处理完成：过期套餐${result.expired_packages}个，释放小号${result.released_accounts}个`)
        getStatistics()
    } catch (error) {
        console.error('处理过期套餐失败:', error)
    }
}

// 更新过期状态
const updateExpiredStatus = async () => {
    try {
        const result = await packageUpdateExpiredStatus({})
        feedback.msgSuccess(`状态更新完成：更新${result.updated_count}条记录`)
        getStatistics()
    } catch (error) {
        console.error('更新过期状态失败:', error)
    }
}

// 刷新统计数据
const refreshStatistics = () => {
    getStatistics()
}

onMounted(() => {
    getStatistics()
})
</script>

<style scoped>
.stat-card {
    @apply border-none;
}

.stat-content {
    @apply flex items-center gap-4;
}

.stat-icon {
    @apply w-12 h-12 rounded-lg flex items-center justify-center;
}

.stat-info {
    @apply flex-1;
}

.stat-number {
    @apply text-2xl font-bold mb-1;
}

.stat-label {
    @apply text-gray-600 text-sm;
}

.stat-row {
    @apply flex justify-between items-center py-2;
}

.stat-row-label {
    @apply text-gray-600;
}

.stat-row-value {
    @apply font-semibold text-lg;
}

.progress-item {
    @apply space-y-1;
}

.progress-label {
    @apply text-xs text-gray-600;
}

.progress-bar {
    @apply w-full h-2 bg-gray-200 rounded-full overflow-hidden;
}

.progress-fill {
    @apply h-full transition-all duration-500;
}
</style>
