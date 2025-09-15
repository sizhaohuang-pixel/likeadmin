<template>
    <div class="port-management">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" inline>
                <el-form-item label="租户">
                    <el-select
                        class="w-[200px]"
                        v-model="queryParams.tenant_id"
                        clearable
                        placeholder="请选择租户"
                        filterable
                    >
                        <el-option
                            v-for="item in tenantOptions"
                            :key="item.id"
                            :label="`${item.name}(${item.account})`"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select
                        class="w-[120px]"
                        v-model="queryParams.status"
                        clearable
                        placeholder="请选择"
                    >
                        <el-option label="可用" value="available" />
                        <el-option label="已用" value="used" />
                        <el-option label="即将过期" value="expiring" />
                        <el-option label="已过期" value="expired" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 端口池状态概览 -->
        <el-card class="!border-none mt-4" shadow="never">
            <template #header>
                <span class="text-lg font-medium">端口池状态概览</span>
            </template>
            <div class="grid grid-cols-4 gap-4">
                <div class="stat-item">
                    <div class="stat-number text-blue-600">{{ portStats.total_ports }}</div>
                    <div class="stat-label">总端口数</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number text-green-600">{{ portStats.available_ports }}</div>
                    <div class="stat-label">可用端口</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number text-orange-600">{{ portStats.used_ports }}</div>
                    <div class="stat-label">已用端口</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number text-red-600">{{ portStats.expiring_soon }}</div>
                    <div class="stat-label">即将过期</div>
                </div>
            </div>
        </el-card>

        <!-- 端口详细列表 -->
        <el-card class="!border-none mt-4" shadow="never">
            <template #header>
                <div class="flex justify-between items-center">
                    <span class="text-lg font-medium">端口详细列表</span>
                    <div class="flex gap-2">
                        <el-button
                            v-perms="['package/check-port-availability']"
                            type="info"
                            @click="checkAvailability"
                        >
                            检查可用性
                        </el-button>
                        <el-button
                            v-perms="['package/handle-expired']"
                            type="warning"
                            @click="handleExpiredPorts"
                        >
                            处理过期端口
                        </el-button>
                    </div>
                </div>
            </template>
            <div class="mt-4" v-loading="pager.loading">
                <el-table :data="pager.lists" size="large">
                    <el-table-column label="租户ID" prop="tenant_id" width="100" />
                    <el-table-column label="租户名称" prop="tenant_name" min-width="120" />
                    <el-table-column label="总端口数" prop="total_ports" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="primary">{{ row.total_ports }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="已用端口" prop="used_ports" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="warning">{{ row.used_ports }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="可用端口" prop="available_ports" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="success">{{ row.available_ports }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="即将过期" prop="expiring_soon" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="danger">{{ row.expiring_soon }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="使用率" min-width="120">
                        <template #default="{ row }">
                            <div class="flex items-center gap-2">
                                <el-progress 
                                    :percentage="getUsagePercentage(row)" 
                                    :color="getProgressColor(row)"
                                    :stroke-width="8"
                                    class="flex-1"
                                />
                                <span class="text-sm">{{ getUsagePercentage(row) }}%</span>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="150" fixed="right">
                        <template #default="{ row }">
                            <el-button
                                v-perms="['package/port-pool-status']"
                                type="primary"
                                link
                                @click="handleViewDetail(row)"
                            >
                                查看详情
                            </el-button>
                            <el-button
                                v-perms="['package/assign-alt-account']"
                                type="success"
                                link
                                @click="handleAssignAccount(row)"
                            >
                                分配小号
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 端口详情弹窗 -->
        <port-detail-popup
            v-if="showDetail"
            ref="detailRef"
            @close="showDetail = false"
        />

        <!-- 分配小号弹窗 -->
        <assign-account-popup
            v-if="showAssignAccount"
            ref="assignAccountRef"
            @success="getLists"
            @close="showAssignAccount = false"
        />
    </div>
</template>

<script lang="ts" setup name="portManagement">
import { packagePortPoolStatus, packageTenantOptions, packageCheckPortAvailability, packageHandleExpired } from '@/api/package'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

// import PortDetailPopup from './detail.vue'
// import AssignAccountPopup from './assign-account.vue'

const showDetail = ref(false)
const showAssignAccount = ref(false)
// const detailRef = shallowRef<InstanceType<typeof PortDetailPopup>>()
// const assignAccountRef = shallowRef<InstanceType<typeof AssignAccountPopup>>()
const tenantOptions = ref<any[]>([])

// 端口统计数据
const portStats = reactive({
    total_ports: 0,
    available_ports: 0,
    used_ports: 0,
    expiring_soon: 0
})

// 查询参数
const queryParams = reactive({
    tenant_id: '',
    status: ''
})

// 分页相关
const { pager, getLists, resetParams, resetPage } = usePaging({
    fetchFun: async (params: any) => {
        // 这里需要根据实际API调整，可能需要调用多个接口获取数据
        const tenants = await packageTenantOptions({})
        const result = []
        
        for (const tenant of tenants) {
            try {
                const portData = await packagePortPoolStatus({ tenant_id: tenant.id })
                result.push({
                    tenant_id: tenant.id,
                    tenant_name: tenant.name,
                    ...portData
                })
            } catch (error) {
                console.error(`获取租户${tenant.id}端口状态失败:`, error)
            }
        }
        
        // 计算总体统计
        portStats.total_ports = result.reduce((sum, item) => sum + item.total_ports, 0)
        portStats.available_ports = result.reduce((sum, item) => sum + item.available_ports, 0)
        portStats.used_ports = result.reduce((sum, item) => sum + item.used_ports, 0)
        portStats.expiring_soon = result.reduce((sum, item) => sum + item.expiring_soon, 0)
        
        return {
            lists: result,
            total: result.length
        }
    },
    params: queryParams
})

// 获取租户选项
const getTenantOptions = async () => {
    try {
        const data = await packageTenantOptions({})
        tenantOptions.value = data
    } catch (error) {
        console.error('获取租户选项失败:', error)
    }
}

// 计算使用率百分比
const getUsagePercentage = (row: any) => {
    if (row.total_ports === 0) return 0
    return Math.round((row.used_ports / row.total_ports) * 100)
}

// 获取进度条颜色
const getProgressColor = (row: any) => {
    const percentage = getUsagePercentage(row)
    if (percentage >= 90) return '#f56c6c'
    if (percentage >= 70) return '#e6a23c'
    return '#67c23a'
}

// 检查端口可用性
const checkAvailability = async () => {
    try {
        feedback.msgSuccess('端口可用性检查完成')
        getLists()
    } catch (error) {
        console.error('检查端口可用性失败:', error)
    }
}

// 处理过期端口
const handleExpiredPorts = async () => {
    await feedback.confirm('确定要处理过期端口吗？此操作将释放过期端口的小号。')
    try {
        const result = await packageHandleExpired({})
        feedback.msgSuccess(`处理完成：释放${result.released_accounts}个小号`)
        getLists()
    } catch (error) {
        console.error('处理过期端口失败:', error)
    }
}

// 查看端口详情
const handleViewDetail = async (row: any) => {
    showDetail.value = true
    await nextTick()
    // detailRef.value?.open(row)
}

// 分配小号
const handleAssignAccount = async (row: any) => {
    if (row.available_ports <= 0) {
        feedback.msgError('该租户没有可用端口')
        return
    }
    showAssignAccount.value = true
    await nextTick()
    // assignAccountRef.value?.open(row)
}

onMounted(() => {
    getTenantOptions()
    getLists()
})
</script>

<style scoped>
.stat-item {
    @apply text-center p-4 bg-gray-50 rounded-lg;
}

.stat-number {
    @apply text-3xl font-bold mb-2;
}

.stat-label {
    @apply text-gray-600 text-sm;
}
</style>
