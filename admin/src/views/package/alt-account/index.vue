<template>
    <div class="alt-account-management">
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
                <el-form-item label="客服">
                    <el-select
                        class="w-[200px]"
                        v-model="queryParams.operator_id"
                        clearable
                        placeholder="请选择客服"
                        filterable
                    >
                        <el-option
                            v-for="item in operatorOptions"
                            :key="item.id"
                            :label="`${item.name}(${item.account})`"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="小号状态">
                    <el-select
                        class="w-[120px]"
                        v-model="queryParams.status"
                        clearable
                        placeholder="请选择"
                    >
                        <el-option label="未分配" value="unassigned" />
                        <el-option label="已分配" value="assigned" />
                        <el-option label="已释放" value="released" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 小号分配统计 -->
        <el-card class="!border-none mt-4" shadow="never">
            <template #header>
                <span class="text-lg font-medium">小号分配统计</span>
            </template>
            <div class="grid grid-cols-4 gap-4">
                <div class="stat-item">
                    <div class="stat-number text-blue-600">{{ accountStats.total_accounts }}</div>
                    <div class="stat-label">总小号数</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number text-green-600">{{ accountStats.available_accounts }}</div>
                    <div class="stat-label">可分配小号</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number text-orange-600">{{ accountStats.assigned_accounts }}</div>
                    <div class="stat-label">已分配小号</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number text-red-600">{{ accountStats.released_accounts }}</div>
                    <div class="stat-label">已释放小号</div>
                </div>
            </div>
        </el-card>

        <!-- 小号列表 -->
        <el-card class="!border-none mt-4" shadow="never">
            <template #header>
                <div class="flex justify-between items-center">
                    <span class="text-lg font-medium">小号管理</span>
                    <div class="flex gap-2">
                        <el-button
                            v-perms="['package/assign-alt-account']"
                            type="primary"
                            @click="handleBatchAssign"
                        >
                            批量分配
                        </el-button>
                        <el-button
                            type="warning"
                            @click="handleBatchRelease"
                        >
                            批量释放
                        </el-button>
                    </div>
                </div>
            </template>
            <div class="mt-4" v-loading="pager.loading">
                <el-table 
                    :data="pager.lists" 
                    size="large"
                    @selection-change="handleSelectionChange"
                >
                    <el-table-column type="selection" width="55" />
                    <el-table-column label="小号ID" prop="id" width="100" />
                    <el-table-column label="昵称" prop="nickname" min-width="120" />
                    <el-table-column label="手机号" prop="phone" min-width="130" />
                    <el-table-column label="所属租户" prop="tenant_name" min-width="120" />
                    <el-table-column label="分配客服" prop="operator_name" min-width="120">
                        <template #default="{ row }">
                            <span v-if="row.operator_name">{{ row.operator_name }}</span>
                            <el-tag v-else type="info">未分配</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="分配时间" prop="assign_time_text" min-width="160">
                        <template #default="{ row }">
                            <span v-if="row.assign_time_text">{{ row.assign_time_text }}</span>
                            <span v-else class="text-gray-400">-</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="状态" min-width="100">
                        <template #default="{ row }">
                            <el-tag 
                                :type="getStatusType(row.status)"
                            >
                                {{ getStatusText(row.status) }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="150" fixed="right">
                        <template #default="{ row }">
                            <el-button
                                v-if="!row.operator_name"
                                v-perms="['package/assign-alt-account']"
                                type="primary"
                                link
                                @click="handleAssign(row)"
                            >
                                分配
                            </el-button>
                            <el-button
                                v-else
                                type="warning"
                                link
                                @click="handleRelease(row)"
                            >
                                释放
                            </el-button>
                            <el-button
                                type="info"
                                link
                                @click="handleViewDetail(row)"
                            >
                                详情
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 分配小号弹窗 -->
        <assign-popup
            v-if="showAssign"
            ref="assignRef"
            @success="getLists"
            @close="showAssign = false"
        />

        <!-- 批量分配弹窗 -->
        <batch-assign-popup
            v-if="showBatchAssign"
            ref="batchAssignRef"
            @success="getLists"
            @close="showBatchAssign = false"
        />
    </div>
</template>

<script lang="ts" setup name="altAccountManagement">
import { packageAltAccountOptions, packageTenantOptions, packageOperatorOptions, packageAssignAltAccount } from '@/api/package'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

// import AssignPopup from './assign.vue'
// import BatchAssignPopup from './batch-assign.vue'

const showAssign = ref(false)
const showBatchAssign = ref(false)
// const assignRef = shallowRef<InstanceType<typeof AssignPopup>>()
// const batchAssignRef = shallowRef<InstanceType<typeof BatchAssignPopup>>()

const tenantOptions = ref<any[]>([])
const operatorOptions = ref<any[]>([])
const selectedAccounts = ref<any[]>([])

// 小号统计数据
const accountStats = reactive({
    total_accounts: 0,
    available_accounts: 0,
    assigned_accounts: 0,
    released_accounts: 0
})

// 查询参数
const queryParams = reactive({
    tenant_id: '',
    operator_id: '',
    status: ''
})

// 分页相关
const { pager, getLists, resetParams, resetPage } = usePaging({
    fetchFun: async (params: any) => {
        // 获取小号列表数据
        const accounts = await packageAltAccountOptions(params)
        
        // 计算统计数据（这里是模拟数据，实际应该从API获取）
        accountStats.total_accounts = accounts.length
        accountStats.available_accounts = accounts.filter((item: any) => !item.operator_name).length
        accountStats.assigned_accounts = accounts.filter((item: any) => item.operator_name).length
        accountStats.released_accounts = 0 // 需要根据实际状态计算
        
        return {
            lists: accounts.map((item: any) => ({
                ...item,
                status: item.operator_name ? 'assigned' : 'unassigned',
                tenant_name: '租户名称', // 需要根据实际数据填充
                assign_time_text: item.operator_name ? '2025-08-27 10:00:00' : null // 需要根据实际数据填充
            })),
            total: accounts.length
        }
    },
    params: queryParams
})

// 获取选项数据
const getOptions = async () => {
    try {
        const [tenants, operators] = await Promise.all([
            packageTenantOptions({}),
            packageOperatorOptions({})
        ])
        tenantOptions.value = tenants
        operatorOptions.value = operators
    } catch (error) {
        console.error('获取选项数据失败:', error)
    }
}

// 获取状态类型
const getStatusType = (status: string) => {
    switch (status) {
        case 'assigned': return 'success'
        case 'unassigned': return 'info'
        case 'released': return 'warning'
        default: return 'info'
    }
}

// 获取状态文本
const getStatusText = (status: string) => {
    switch (status) {
        case 'assigned': return '已分配'
        case 'unassigned': return '未分配'
        case 'released': return '已释放'
        default: return '未知'
    }
}

// 处理选择变化
const handleSelectionChange = (selection: any[]) => {
    selectedAccounts.value = selection
}

// 分配小号
const handleAssign = async (row: any) => {
    showAssign.value = true
    await nextTick()
    // assignRef.value?.open(row)
}

// 释放小号
const handleRelease = async (row: any) => {
    await feedback.confirm('确定要释放这个小号吗？')
    try {
        // 这里需要调用释放小号的API
        feedback.msgSuccess('小号释放成功')
        getLists()
    } catch (error) {
        console.error('释放小号失败:', error)
    }
}

// 查看详情
const handleViewDetail = (row: any) => {
    feedback.msgInfo('查看小号详情功能开发中...')
}

// 批量分配
const handleBatchAssign = () => {
    if (selectedAccounts.value.length === 0) {
        feedback.msgWarning('请先选择要分配的小号')
        return
    }
    showBatchAssign.value = true
    // batchAssignRef.value?.open(selectedAccounts.value)
}

// 批量释放
const handleBatchRelease = async () => {
    if (selectedAccounts.value.length === 0) {
        feedback.msgWarning('请先选择要释放的小号')
        return
    }
    
    await feedback.confirm(`确定要释放选中的${selectedAccounts.value.length}个小号吗？`)
    try {
        // 这里需要调用批量释放小号的API
        feedback.msgSuccess(`成功释放${selectedAccounts.value.length}个小号`)
        getLists()
    } catch (error) {
        console.error('批量释放小号失败:', error)
    }
}

onMounted(() => {
    getOptions()
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
