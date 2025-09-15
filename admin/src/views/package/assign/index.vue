<template>
    <div class="package-assign">
        <el-card class="!border-none" shadow="never">
            <!-- 来源提示 -->


            <el-form class="mb-[-16px]" :model="queryParams" inline>
                <el-form-item label="选择租户">
                    <el-select v-model="queryParams.tenant_id" placeholder="请选择租户" style="width: 200px" filterable
                        clearable @change="resetPage">
                        <el-option v-for="item in tenantOptions" :key="item.id"
                            :label="item.name + '(' + item.account + ')'" :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="套餐状态">
                    <el-select v-model="queryParams.status" placeholder="请选择" style="width: 120px" clearable
                        @change="resetPage">
                        <el-option label="有效" :value="1" />
                        <el-option label="已过期" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="到期状态">
                    <el-select v-model="queryParams.expire_status" placeholder="请选择" style="width: 140px" clearable
                        @change="resetPage">
                        <el-option label="有效" value="valid" />
                        <el-option label="已过期" value="expired" />
                        <el-option label="即将过期" value="expiring_soon" />
                    </el-select>
                </el-form-item>
                <el-form-item label="端口数">
                    <el-input-number v-model="queryParams.port_count_min" :min="0" placeholder="最小值"
                        style="width: 120px" />
                    <span class="mx-2">-</span>
                    <el-input-number v-model="queryParams.port_count_max" :min="0" placeholder="最大值"
                        style="width: 120px" />
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
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-medium">套餐管理</span>
                        <span v-if="route.query.auto_search === '1' && route.query.tenant_name"
                            class="text-sm text-orange-600 bg-orange-50 px-3 py-1 rounded-full border border-orange-200">
                            正在查看租户 "{{ route.query.tenant_name }}" 的套餐
                        </span>
                    </div>
                    <el-button v-perms="['package/assign']" type="primary" @click="handleAssign">
                        <template #icon>
                            <icon name="el-icon-Plus" />
                        </template>
                        分配套餐
                    </el-button>
                </div>
            </template>
            <div class="mt-4" v-loading="pager.loading">
                <el-table :data="pager.lists" size="large">
                    <el-table-column label="套餐ID" prop="id" width="80" />
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
                    <el-table-column label="过期端口" prop="port_expired" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="danger">{{ row.port_expired || 0 }}</el-tag>
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
                    <el-table-column label="操作" width="120" fixed="right">
                        <template #default="{ row }">
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

        <!-- 分配套餐弹窗 -->
        <assign-popup v-if="showAssign" ref="assignRef" @success="getLists" @close="showAssign = false" />



        <!-- 续费弹窗 -->
        <renew-popup v-if="showRenew" ref="renewRef" @success="getLists" @close="showRenew = false" />
    </div>
</template>

<script lang="ts" setup name="packageAssign">
import { packageLists, packageTenantOptions } from '@/api/package'
import { usePaging } from '@/hooks/usePaging'
import { useRoute } from 'vue-router'

import AssignPopup from './edit.vue'
import RenewPopup from '../lists/renew.vue'

const route = useRoute()
const assignRef = shallowRef<InstanceType<typeof AssignPopup>>()
const renewRef = shallowRef<InstanceType<typeof RenewPopup>>()
const showAssign = ref(false)
const showRenew = ref(false)

// 租户选项
const tenantOptions = ref<any[]>([])

// 查询参数
const queryParams = reactive({
    tenant_id: '',
    status: '',
    expire_status: '',
    port_count_min: '',
    port_count_max: ''
})

// 分页相关
const { pager, getLists, resetParams, resetPage } = usePaging({
    fetchFun: async (params: any) => {
        try {
            const response = await packageLists(params)
            // 转换数据格式以适配usePaging钩子
            return {
                count: response.count || 0,
                lists: response.lists || []
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

// 分配套餐
const handleAssign = async () => {
    showAssign.value = true
    await nextTick()
    assignRef.value?.open('add')
}

// 续费套餐
const handleRenew = async (row: any) => {
    showRenew.value = true
    await nextTick()
    renewRef.value?.open(row)
}





// 获取租户选项
const getTenantOptions = async () => {
    try {
        const data = await packageTenantOptions({})
        tenantOptions.value = data
    } catch (error) {
        console.error('获取租户选项失败:', error)
    }
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

// 初始化URL参数
const initFromUrlParams = () => {
    const { tenant_id, auto_search } = route.query

    if (tenant_id && auto_search === '1') {
        // 设置查询参数
        queryParams.tenant_id = tenant_id as string

        // 等待租户选项加载完成后再执行搜索
        nextTick(() => {
            setTimeout(() => {
                getLists()
            }, 500) // 延迟500ms确保租户选项已加载
        })
    }
}

// 监听路由变化，当URL参数改变时重新初始化
watch(() => route.query, (newQuery, oldQuery) => {
    // 只有当auto_search参数存在且发生变化时才重新初始化
    if (newQuery.auto_search === '1' &&
        (newQuery.tenant_id !== oldQuery.tenant_id || newQuery.auto_search !== oldQuery.auto_search)) {
        initFromUrlParams()
    }
}, { deep: true })

onMounted(() => {
    getTenantOptions()
    initFromUrlParams()

    // 如果没有URL参数，正常加载列表
    if (!route.query.auto_search) {
        getLists()
    }
})
</script>
