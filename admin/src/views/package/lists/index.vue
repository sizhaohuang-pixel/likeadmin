<template>
    <div class="package-lists">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" inline>
                <el-form-item label="代理商">
                    <el-input class="w-[200px]" v-model="queryParams.agent_name" clearable placeholder="代理商姓名"
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="租户">
                    <el-input class="w-[200px]" v-model="queryParams.tenant_name" clearable placeholder="租户姓名"
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select class="w-[120px]" v-model="queryParams.status" clearable placeholder="请选择">
                        <el-option label="有效" :value="1" />
                        <el-option label="过期" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="到期状态">
                    <el-select class="w-[140px]" v-model="queryParams.expire_status" clearable placeholder="请选择">
                        <el-option label="有效" value="valid" />
                        <el-option label="已过期" value="expired" />
                        <el-option label="即将过期" value="expiring_soon" />
                    </el-select>
                </el-form-item>
                <el-form-item label="端口数">
                    <el-input class="w-[100px]" v-model="queryParams.port_count_min" placeholder="最小"
                        @keyup.enter="resetPage" />
                    <span class="mx-2">-</span>
                    <el-input class="w-[100px]" v-model="queryParams.port_count_max" placeholder="最大"
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="分配时间">
                    <el-date-picker class="w-[240px]" v-model="queryParams.assign_time_range" type="daterange"
                        range-separator="至" start-placeholder="开始日期" end-placeholder="结束日期" format="YYYY-MM-DD"
                        value-format="YYYY-MM-DD" />
                </el-form-item>
                <el-form-item label="到期时间">
                    <el-date-picker class="w-[240px]" v-model="queryParams.expire_time_range" type="daterange"
                        range-separator="至" start-placeholder="开始日期" end-placeholder="结束日期" format="YYYY-MM-DD"
                        value-format="YYYY-MM-DD" />
                </el-form-item>
                <el-form-item label="剩余天数">
                    <el-select class="w-[80px]" v-model="queryParams.remaining_days_operator" placeholder="条件">
                        <el-option label="<" value="lt" />
                        <el-option label="<=" value="lte" />
                        <el-option label="=" value="eq" />
                        <el-option label=">=" value="gte" />
                        <el-option label=">" value="gt" />
                    </el-select>
                    <el-input-number class="w-[100px] ml-2" v-model="queryParams.remaining_days" :min="0"
                        placeholder="天数" />
                </el-form-item>
                <el-form-item label="备注关键词">
                    <el-input class="w-[150px]" v-model="queryParams.remark_keyword" clearable placeholder="搜索备注"
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <template #header>
                <div class="flex justify-between items-center">
                    <span class="text-lg font-medium">套餐列表</span>
                    <div class="flex gap-2">
                        <el-button v-perms="['package/batch-renew']" type="success" @click="handleBatchRenew">
                            批量续费
                        </el-button>
                        <el-button v-perms="['package/statistics']" type="info" @click="showStatistics">
                            统计信息
                        </el-button>
                        <el-button v-perms="['package/handle-expired']" type="warning" @click="handleExpired">
                            处理过期
                        </el-button>
                    </div>
                </div>
            </template>
            <div class="mt-4" v-loading="pager.loading">
                <el-table :data="pager.lists" size="large" @selection-change="handleSelectionChange">
                    <el-table-column type="selection" width="55" />
                    <el-table-column label="ID" prop="id" width="80" />
                    <el-table-column label="代理商" prop="agent_name" min-width="120" />
                    <el-table-column label="租户" prop="tenant_name" min-width="120" />
                    <el-table-column label="端口总数" prop="port_total" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="primary">{{ row.port_total || row.port_count }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="已用端口" prop="port_used" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="warning">{{ row.port_used || 0 }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="空闲端口" prop="port_free" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="success">{{ row.port_free || (row.port_total || row.port_count) -
                                (row.port_used || 0) }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="使用率" min-width="100">
                        <template #default="{ row }">
                            <el-progress :percentage="getUsagePercentage(row)" :color="getProgressColor(row)"
                                :stroke-width="8" :show-text="true" />
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
                    <el-table-column label="操作" width="160" fixed="right">
                        <template #default="{ row }">
                            <el-button v-perms="['package/detail']" type="primary" link @click="handleDetail(row)">
                                详情
                            </el-button>
                            <el-button v-perms="['package/renew']" type="warning" link @click="handleRenew(row)">
                                续费
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 统计信息弹窗 -->
        <statistics-popup v-if="showStats" @close="showStats = false" />

        <!-- 详情弹窗 -->
        <detail-popup v-if="showDetail" ref="detailRef" @close="showDetail = false" />

        <!-- 续费弹窗 -->
        <renew-popup v-if="showRenew" ref="renewRef" @success="getLists" @close="showRenew = false" />

        <!-- 批量续费弹窗 -->
        <batch-renew-popup v-if="showBatchRenew" ref="batchRenewRef" @success="getLists"
            @close="showBatchRenew = false" />
    </div>
</template>

<script lang="ts" setup name="packageLists">
import { packageLists, packageHandleExpired } from '@/api/package'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

// import StatisticsPopup from './statistics.vue'
// import DetailPopup from './detail.vue'
import RenewPopup from './renew.vue'
import BatchRenewPopup from './batch-renew.vue'

const showStats = ref(false)
const showDetail = ref(false)
const showRenew = ref(false)
const showBatchRenew = ref(false)
const detailRef = shallowRef<InstanceType<typeof DetailPopup>>()
const renewRef = shallowRef<InstanceType<typeof RenewPopup>>()
const batchRenewRef = shallowRef<InstanceType<typeof BatchRenewPopup>>()
const selectedPackages = ref<any[]>([])

// 查询参数
const queryParams = reactive({
    agent_name: '',
    tenant_name: '',
    status: '',
    expire_status: '',
    port_count_min: '',
    port_count_max: '',
    assign_time_range: [],
    assign_start_time: '',
    assign_end_time: '',
    expire_time_range: [],
    expire_start_time: '',
    expire_end_time: '',
    remaining_days: '',
    remaining_days_operator: '',
    remark_keyword: ''
})

// 监听分配时间范围变化
watch(() => queryParams.assign_time_range, (val) => {
    if (val && val.length === 2) {
        queryParams.assign_start_time = val[0]
        queryParams.assign_end_time = val[1]
    } else {
        queryParams.assign_start_time = ''
        queryParams.assign_end_time = ''
    }
})

// 监听到期时间范围变化
watch(() => queryParams.expire_time_range, (val) => {
    if (val && val.length === 2) {
        queryParams.expire_start_time = val[0]
        queryParams.expire_end_time = val[1]
    } else {
        queryParams.expire_start_time = ''
        queryParams.expire_end_time = ''
    }
})

// 分页相关
const { pager, getLists, resetParams, resetPage } = usePaging({
    fetchFun: async (params: any) => {
        try {
            const response = await packageLists(params)
            // 转换数据格式以适配usePaging钩子
            return {
                count: response.total || 0,
                lists: response.data || []
            }
        } catch (error: any) {
            // 如果是取消的请求，不抛出错误
            if (error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
                return { count: 0, lists: [] }
            }
            throw error
        }
    },
    params: queryParams
})

// 显示统计信息
const showStatistics = () => {
    showStats.value = true
}

// 处理过期套餐
const handleExpired = async () => {
    await feedback.confirm('确定要处理过期套餐吗？此操作将释放过期套餐的小号。')
    try {
        const result = await packageHandleExpired({})
        feedback.msgSuccess(`处理完成：过期套餐${result.expired_packages}个，释放小号${result.released_accounts}个`)
        getLists()
    } catch (error) {
        console.error('处理过期套餐失败:', error)
    }
}

// 查看详情
const handleDetail = async (row: any) => {
    showDetail.value = true
    await nextTick()
    detailRef.value?.open(row.id)
}

// 续费套餐
const handleRenew = async (row: any) => {
    showRenew.value = true
    await nextTick()
    renewRef.value?.open(row)
}

// 处理选择变化
const handleSelectionChange = (selection: any[]) => {
    selectedPackages.value = selection
}

// 批量续费
const handleBatchRenew = async () => {
    if (selectedPackages.value.length === 0) {
        feedback.msgWarning('请先选择要续费的套餐')
        return
    }

    if (selectedPackages.value.length > 100) {
        feedback.msgWarning('单次最多只能续费100个套餐')
        return
    }

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
    getLists()
})
</script>
