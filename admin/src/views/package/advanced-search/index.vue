<template>
    <div class="advanced-search">
        <el-card class="!border-none" shadow="never">
            <template #header>
                <span class="text-lg font-medium">高级套餐查询</span>
            </template>

            <!-- 基础筛选 -->
            <div class="search-section">
                <h3 class="section-title">基础筛选</h3>
                <el-form :model="queryParams" inline>
                    <el-form-item label="租户">
                        <el-select v-model="queryParams.tenant_id" placeholder="请选择租户" class="w-[200px]" filterable
                            clearable>
                            <el-option v-for="item in tenantOptions" :key="item.id"
                                :label="`${item.name}(${item.account})`" :value="item.id" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="代理商" v-if="isRootUser">
                        <el-select v-model="queryParams.agent_id" placeholder="请选择代理商" class="w-[200px]" filterable
                            clearable>
                            <el-option v-for="item in agentOptions" :key="item.id"
                                :label="`${item.name}(${item.account})`" :value="item.id" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="套餐状态">
                        <el-select v-model="queryParams.status" placeholder="请选择" class="w-[120px]" clearable>
                            <el-option label="有效" :value="1" />
                            <el-option label="无效" :value="0" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="到期状态">
                        <el-select v-model="queryParams.expire_status" placeholder="请选择" class="w-[140px]" clearable>
                            <el-option label="有效" value="valid" />
                            <el-option label="已过期" value="expired" />
                            <el-option label="即将过期" value="expiring_soon" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="套餐ID">
                        <el-input v-model="queryParams.package_ids" placeholder="多个ID用逗号分隔" class="w-[200px]"
                            clearable />
                    </el-form-item>
                </el-form>
            </div>

            <!-- 时间维度筛选 -->
            <div class="search-section">
                <h3 class="section-title">时间维度筛选</h3>
                <el-form :model="queryParams" inline>
                    <el-form-item label="分配时间">
                        <el-date-picker v-model="queryParams.assign_time_range" type="daterange" range-separator="至"
                            start-placeholder="开始日期" end-placeholder="结束日期" format="YYYY-MM-DD"
                            value-format="YYYY-MM-DD" class="w-[240px]" />
                    </el-form-item>
                    <el-form-item label="到期时间">
                        <el-date-picker v-model="queryParams.expire_time_range" type="daterange" range-separator="至"
                            start-placeholder="开始日期" end-placeholder="结束日期" format="YYYY-MM-DD"
                            value-format="YYYY-MM-DD" class="w-[240px]" />
                    </el-form-item>
                    <el-form-item label="通用时间">
                        <el-date-picker v-model="queryParams.general_time_range" type="daterange" range-separator="至"
                            start-placeholder="开始日期" end-placeholder="结束日期" format="YYYY-MM-DD"
                            value-format="YYYY-MM-DD" class="w-[240px]" />
                    </el-form-item>
                </el-form>
            </div>

            <!-- 数值范围筛选 -->
            <div class="search-section">
                <h3 class="section-title">数值范围筛选</h3>
                <el-form :model="queryParams" inline>
                    <el-form-item label="端口数量">
                        <el-input-number v-model="queryParams.port_count_min" :min="0" placeholder="最小值"
                            class="w-[120px]" />
                        <span class="mx-2">-</span>
                        <el-input-number v-model="queryParams.port_count_max" :min="0" placeholder="最大值"
                            class="w-[120px]" />
                    </el-form-item>
                    <el-form-item label="剩余天数">
                        <el-select v-model="queryParams.remaining_days_operator" placeholder="条件" class="w-[80px]">
                            <el-option label="<" value="lt" />
                            <el-option label="<=" value="lte" />
                            <el-option label="=" value="eq" />
                            <el-option label=">=" value="gte" />
                            <el-option label=">" value="gt" />
                        </el-select>
                        <el-input-number v-model="queryParams.remaining_days" :min="0" placeholder="天数"
                            class="w-[120px] ml-2" />
                    </el-form-item>
                </el-form>
            </div>

            <!-- 内容搜索 -->
            <div class="search-section">
                <h3 class="section-title">内容搜索</h3>
                <el-form :model="queryParams" inline>
                    <el-form-item label="备注关键词">
                        <el-input v-model="queryParams.remark_keyword" placeholder="在备注中搜索" class="w-[200px]"
                            clearable />
                    </el-form-item>
                </el-form>
            </div>

            <!-- 操作按钮 -->
            <div class="search-actions">
                <el-button type="primary" @click="handleSearch" :loading="loading">
                    <template #icon>
                        <icon name="el-icon-Search" />
                    </template>
                    高级查询
                </el-button>
                <el-button @click="handleReset">
                    <template #icon>
                        <icon name="el-icon-Refresh" />
                    </template>
                    重置条件
                </el-button>
                <el-button type="success" @click="handleExport" :loading="exportLoading">
                    <template #icon>
                        <icon name="el-icon-Download" />
                    </template>
                    导出结果
                </el-button>
            </div>
        </el-card>

        <!-- 查询结果 -->
        <el-card class="!border-none mt-4" shadow="never" v-if="searchResults.length > 0 || hasSearched">
            <template #header>
                <div class="flex justify-between items-center">
                    <span class="text-lg font-medium">查询结果 ({{ searchResults.length }}条)</span>
                    <div class="flex gap-2">
                        <el-button type="warning" @click="handleBatchRenew" v-if="selectedPackages.length > 0">
                            批量续费 ({{ selectedPackages.length }})
                        </el-button>
                    </div>
                </div>
            </template>

            <div v-loading="loading">
                <el-table :data="searchResults" size="large" @selection-change="handleSelectionChange"
                    v-if="searchResults.length > 0">
                    <el-table-column type="selection" width="55" />
                    <el-table-column label="套餐ID" prop="id" width="80" />
                    <el-table-column label="代理商" prop="agent_name" min-width="120" />
                    <el-table-column label="租户" prop="tenant_name" min-width="120" />
                    <el-table-column label="端口总数" prop="port_total" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="primary">{{ row.port_total || row.port_count }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="已用/空闲" min-width="120">
                        <template #default="{ row }">
                            <div class="text-xs">
                                <span class="text-orange-600">{{ row.port_used || 0 }}</span>
                                /
                                <span class="text-green-600">{{ row.port_free || (row.port_total || row.port_count) -
                                    (row.port_used || 0) }}</span>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="使用率" min-width="100">
                        <template #default="{ row }">
                            <el-progress :percentage="getUsagePercentage(row)" :color="getProgressColor(row)"
                                :stroke-width="6" />
                        </template>
                    </el-table-column>
                    <el-table-column label="分配时间" prop="assign_time_text" min-width="160" />
                    <el-table-column label="到期时间" prop="expire_time_text" min-width="160" />
                    <el-table-column label="状态" min-width="100">
                        <template #default="{ row }">
                            <el-tag :type="row.status_text === '有效' ? 'success' : 'danger'">
                                {{ row.status_text }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="剩余天数" min-width="100">
                        <template #default="{ row }">
                            <el-tag
                                :type="row.remaining_days > 7 ? 'success' : row.remaining_days > 0 ? 'warning' : 'danger'">
                                {{ row.remaining_days }}天
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="备注" prop="remark" min-width="150" show-overflow-tooltip />
                    <el-table-column label="操作" width="120" fixed="right">
                        <template #default="{ row }">
                            <el-button type="warning" link @click="handleRenew(row)">续费</el-button>
                        </template>
                    </el-table-column>
                </el-table>

                <!-- 空状态 -->
                <div v-else class="empty-state">
                    <el-empty description="未找到符合条件的套餐" />
                </div>
            </div>
        </el-card>

        <!-- 续费弹窗 -->
        <renew-popup v-if="showRenew" ref="renewRef" @success="handleSearch" @close="showRenew = false" />

        <!-- 批量续费弹窗 -->
        <batch-renew-popup v-if="showBatchRenew" ref="batchRenewRef" @success="handleSearch"
            @close="showBatchRenew = false" />
    </div>
</template>

<script lang="ts" setup name="advancedSearch">
import { packageLists, packageTenantOptions } from '@/api/package'
import useUserStore from '@/stores/modules/user'
import feedback from '@/utils/feedback'

import RenewPopup from '../lists/renew.vue'
import BatchRenewPopup from '../lists/batch-renew.vue'

const userStore = useUserStore()
const loading = ref(false)
const exportLoading = ref(false)
const hasSearched = ref(false)
const showRenew = ref(false)
const showBatchRenew = ref(false)
const renewRef = shallowRef<InstanceType<typeof RenewPopup>>()
const batchRenewRef = shallowRef<InstanceType<typeof BatchRenewPopup>>()

// 是否为root用户
const isRootUser = computed(() => userStore.userInfo.root === 1)

// 选项数据
const tenantOptions = ref<any[]>([])
const agentOptions = ref<any[]>([])

// 查询结果
const searchResults = ref<any[]>([])
const selectedPackages = ref<any[]>([])

// 查询参数
const queryParams = reactive({
    tenant_id: '',
    agent_id: '',
    status: '',
    expire_status: '',
    package_ids: '',
    assign_time_range: [],
    assign_start_time: '',
    assign_end_time: '',
    expire_time_range: [],
    expire_start_time: '',
    expire_end_time: '',
    general_time_range: [],
    start_time: '',
    end_time: '',
    port_count_min: '',
    port_count_max: '',
    remaining_days: '',
    remaining_days_operator: '',
    remark_keyword: ''
})

// 监听时间范围变化
watch(() => queryParams.assign_time_range, (val) => {
    if (val && val.length === 2) {
        queryParams.assign_start_time = val[0]
        queryParams.assign_end_time = val[1]
    } else {
        queryParams.assign_start_time = ''
        queryParams.assign_end_time = ''
    }
})

watch(() => queryParams.expire_time_range, (val) => {
    if (val && val.length === 2) {
        queryParams.expire_start_time = val[0]
        queryParams.expire_end_time = val[1]
    } else {
        queryParams.expire_start_time = ''
        queryParams.expire_end_time = ''
    }
})

watch(() => queryParams.general_time_range, (val) => {
    if (val && val.length === 2) {
        queryParams.start_time = val[0]
        queryParams.end_time = val[1]
    } else {
        queryParams.start_time = ''
        queryParams.end_time = ''
    }
})

// 获取选项数据
const getOptions = async () => {
    try {
        const tenants = await packageTenantOptions({})
        tenantOptions.value = tenants

        if (isRootUser.value) {
            // 如果是root用户，获取代理商选项
            // agentOptions.value = await getAgentOptions()
        }
    } catch (error) {
        console.error('获取选项数据失败:', error)
    }
}

// 高级查询
const handleSearch = async () => {
    loading.value = true
    hasSearched.value = true
    try {
        // 处理套餐ID列表
        const params = { ...queryParams }
        if (params.package_ids) {
            params.package_ids = params.package_ids.split(',').map((id: string) => parseInt(id.trim())).filter((id: number) => !isNaN(id))
        }

        const response = await packageLists(params)
        searchResults.value = response.data || []
        selectedPackages.value = []

        feedback.msgSuccess(`查询完成，找到${searchResults.value.length}条记录`)
    } catch (error) {
        console.error('查询失败:', error)
        searchResults.value = []
    } finally {
        loading.value = false
    }
}

// 重置条件
const handleReset = () => {
    Object.assign(queryParams, {
        tenant_id: '',
        agent_id: '',
        status: '',
        expire_status: '',
        package_ids: '',
        assign_time_range: [],
        assign_start_time: '',
        assign_end_time: '',
        expire_time_range: [],
        expire_start_time: '',
        expire_end_time: '',
        general_time_range: [],
        start_time: '',
        end_time: '',
        port_count_min: '',
        port_count_max: '',
        remaining_days: '',
        remaining_days_operator: '',
        remark_keyword: ''
    })
    searchResults.value = []
    selectedPackages.value = []
    hasSearched.value = false
}

// 导出结果
const handleExport = async () => {
    if (searchResults.value.length === 0) {
        feedback.msgWarning('没有可导出的数据')
        return
    }

    exportLoading.value = true
    try {
        // 这里实现导出逻辑
        feedback.msgSuccess('导出功能开发中...')
    } catch (error) {
        console.error('导出失败:', error)
    } finally {
        exportLoading.value = false
    }
}

// 处理选择变化
const handleSelectionChange = (selection: any[]) => {
    selectedPackages.value = selection
}

// 续费
const handleRenew = async (row: any) => {
    showRenew.value = true
    await nextTick()
    renewRef.value?.open(row)
}

// 批量续费
const handleBatchRenew = async () => {
    showBatchRenew.value = true
    await nextTick()
    batchRenewRef.value?.open(selectedPackages.value)
}

// 计算端口使用率
const getUsagePercentage = (row: any) => {
    const total = row.port_total || row.port_count || 0
    const used = row.port_used || 0
    if (total === 0) return 0
    return Math.round((used / total) * 100)
}

// 获取进度条颜色
const getProgressColor = (row: any) => {
    const percentage = getUsagePercentage(row)
    if (percentage >= 90) return '#f56c6c'  // 红色
    if (percentage >= 70) return '#e6a23c'  // 橙色
    if (percentage >= 50) return '#409eff'  // 蓝色
    return '#67c23a'  // 绿色
}

onMounted(() => {
    getOptions()
})
</script>

<style scoped>
.search-section {
    @apply mb-6 pb-4 border-b border-gray-200;
}

.section-title {
    @apply text-base font-medium text-gray-900 mb-4;
}

.search-actions {
    @apply flex gap-3 pt-4;
}

.empty-state {
    @apply py-8;
}
</style>
