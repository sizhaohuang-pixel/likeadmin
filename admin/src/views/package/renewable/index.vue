<template>
    <div class="renewable-packages">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" inline>
                <el-form-item label="选择租户" prop="tenant_id">
                    <el-select
                        v-model="queryParams.tenant_id"
                        placeholder="请选择租户"
                        class="w-[200px]"
                        filterable
                        @change="handleTenantChange"
                    >
                        <el-option
                            v-for="item in tenantOptions"
                            :key="item.id"
                            :label="`${item.name}(${item.account})`"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="getRenewablePackages" :disabled="!queryParams.tenant_id">
                        查询套餐
                    </el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never" v-if="queryParams.tenant_id">
            <template #header>
                <div class="flex justify-between items-center">
                    <span class="text-lg font-medium">可续费套餐列表</span>
                    <div class="flex gap-2">
                        <el-button
                            v-if="selectedPackages.length > 0"
                            type="success"
                            @click="handleBatchRenew"
                        >
                            批量续费 ({{ selectedPackages.length }})
                        </el-button>
                        <el-button @click="refreshList" :loading="loading">
                            刷新
                        </el-button>
                    </div>
                </div>
            </template>
            
            <div v-loading="loading">
                <el-table 
                    :data="renewablePackages" 
                    size="large"
                    @selection-change="handleSelectionChange"
                    v-if="renewablePackages.length > 0"
                >
                    <el-table-column type="selection" width="55" />
                    <el-table-column label="套餐ID" prop="id" width="80" />
                    <el-table-column label="端口数量" prop="port_count" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="primary">{{ row.port_count }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="分配时间" prop="assign_time" min-width="160" />
                    <el-table-column label="到期时间" prop="expire_time" min-width="160" />
                    <el-table-column label="剩余天数" min-width="100">
                        <template #default="{ row }">
                            <el-tag 
                                :type="row.remaining_days > 7 ? 'success' : row.remaining_days > 0 ? 'warning' : 'danger'"
                            >
                                {{ row.remaining_days }}天
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="状态" min-width="100">
                        <template #default="{ row }">
                            <el-tag v-if="row.is_expired" type="danger">已过期</el-tag>
                            <el-tag v-else-if="row.is_expiring_soon" type="warning">即将过期</el-tag>
                            <el-tag v-else type="success">正常</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="120" fixed="right">
                        <template #default="{ row }">
                            <el-button
                                type="warning"
                                link
                                @click="handleRenew(row)"
                            >
                                续费
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>
                
                <!-- 空状态 -->
                <div v-else class="empty-state">
                    <el-empty description="该租户暂无可续费的套餐" />
                </div>
            </div>
        </el-card>

        <!-- 提示信息 -->
        <el-card class="!border-none mt-4" shadow="never" v-else>
            <div class="text-center py-8">
                <el-icon class="text-4xl text-gray-400 mb-4"><Select /></el-icon>
                <div class="text-gray-500">请先选择租户查看可续费套餐</div>
            </div>
        </el-card>

        <!-- 续费弹窗 -->
        <renew-popup v-if="showRenew" ref="renewRef" @success="refreshList" @close="showRenew = false" />

        <!-- 批量续费弹窗 -->
        <batch-renew-popup v-if="showBatchRenew" ref="batchRenewRef" @success="refreshList" @close="showBatchRenew = false" />
    </div>
</template>

<script lang="ts" setup name="renewablePackages">
import { packageRenewablePackages, packageTenantOptions } from '@/api/package'
import { Select } from '@element-plus/icons-vue'
import feedback from '@/utils/feedback'

import RenewPopup from '../lists/renew.vue'
import BatchRenewPopup from '../lists/batch-renew.vue'

const loading = ref(false)
const showRenew = ref(false)
const showBatchRenew = ref(false)
const renewRef = shallowRef<InstanceType<typeof RenewPopup>>()
const batchRenewRef = shallowRef<InstanceType<typeof BatchRenewPopup>>()

// 查询参数
const queryParams = reactive({
    tenant_id: ''
})

// 租户选项
const tenantOptions = ref<any[]>([])

// 可续费套餐列表
const renewablePackages = ref<any[]>([])

// 选中的套餐
const selectedPackages = ref<any[]>([])

// 获取租户选项
const getTenantOptions = async () => {
    try {
        const data = await packageTenantOptions({})
        tenantOptions.value = data
    } catch (error) {
        console.error('获取租户选项失败:', error)
    }
}

// 获取可续费套餐列表
const getRenewablePackages = async () => {
    if (!queryParams.tenant_id) {
        feedback.msgWarning('请先选择租户')
        return
    }
    
    loading.value = true
    try {
        const data = await packageRenewablePackages({ tenant_id: queryParams.tenant_id })
        renewablePackages.value = data || []
        selectedPackages.value = []
    } catch (error) {
        console.error('获取可续费套餐失败:', error)
        renewablePackages.value = []
    } finally {
        loading.value = false
    }
}

// 租户变化处理
const handleTenantChange = () => {
    renewablePackages.value = []
    selectedPackages.value = []
}

// 刷新列表
const refreshList = () => {
    getRenewablePackages()
}

// 处理选择变化
const handleSelectionChange = (selection: any[]) => {
    selectedPackages.value = selection
}

// 单个续费
const handleRenew = async (row: any) => {
    showRenew.value = true
    await nextTick()
    renewRef.value?.open(row)
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

onMounted(() => {
    getTenantOptions()
})
</script>

<style scoped>
.empty-state {
    @apply py-8;
}
</style>
