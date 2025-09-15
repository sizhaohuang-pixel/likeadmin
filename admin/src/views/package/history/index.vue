<template>
    <div class="package-history">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" inline>
                <el-form-item label="租户">
                    <el-select style="width: 200px" v-model="queryParams.tenant_id" clearable placeholder="请选择租户"
                        filterable>
                        <el-option v-for="item in tenantOptions" :key="item.id" :label="`${item.name}(${item.account})`"
                            :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select style="width: 120px" v-model="queryParams.status" clearable placeholder="请选择">
                        <el-option label="有效" :value="1" />
                        <el-option label="过期" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="分配时间">
                    <el-date-picker style="width: 240px" v-model="queryParams.time_range" type="daterange"
                        range-separator="至" start-placeholder="开始日期" end-placeholder="结束日期" format="YYYY-MM-DD"
                        value-format="YYYY-MM-DD" />
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
                    <span class="text-lg font-medium">分配历史</span>
                    <div class="flex gap-2">
                        <el-button type="success" @click="exportHistory">
                            导出数据
                        </el-button>
                    </div>
                </div>
            </template>
            <div class="mt-4" v-loading="pager.loading">
                <el-table :data="pager.lists" size="large">
                    <el-table-column label="ID" prop="id" width="80" />
                    <el-table-column label="代理商" prop="agent_name" min-width="120" />
                    <el-table-column label="租户" prop="tenant_name" min-width="120" />
                    <el-table-column label="端口数量" prop="port_count" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="primary">{{ row.port_count }}</el-tag>
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
                </el-table>
            </div>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>


    </div>
</template>

<script lang="ts" setup name="packageHistory">
import { packageAssignHistory, packageTenantOptions } from '@/api/package'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'


const tenantOptions = ref<any[]>([])

// 查询参数
const queryParams = reactive({
    tenant_id: '',
    status: '',
    time_range: [],
    start_time: '',
    end_time: ''
})

// 监听时间范围变化
watch(() => queryParams.time_range, (val) => {
    if (val && val.length === 2) {
        queryParams.start_time = val[0]
        queryParams.end_time = val[1]
    } else {
        queryParams.start_time = ''
        queryParams.end_time = ''
    }
})

// 分页相关
const { pager, getLists, resetParams, resetPage } = usePaging({
    fetchFun: async (params: any) => {
        try {
            const response = await packageAssignHistory(params)
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

// 获取租户选项
const getTenantOptions = async () => {
    try {
        const data = await packageTenantOptions({})
        tenantOptions.value = data
    } catch (error) {
        console.error('获取租户选项失败:', error)
    }
}

// 导出历史数据
const exportHistory = async () => {
    try {
        // 这里可以调用导出API或者前端导出
        feedback.msgSuccess('导出功能开发中...')
    } catch (error) {
        console.error('导出失败:', error)
    }
}

// 获取状态类型
const getStatusType = (status: number) => {
    return status === 1 ? 'success' : 'danger'
}





onMounted(() => {
    getTenantOptions()
    getLists()
})
</script>
