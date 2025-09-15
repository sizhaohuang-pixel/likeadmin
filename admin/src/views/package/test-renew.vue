<template>
    <div class="test-renew">
        <el-card class="!border-none" shadow="never">
            <template #header>
                <span class="text-lg font-medium">续费API测试</span>
            </template>
            
            <!-- 单个续费测试 -->
            <div class="mb-6">
                <h3 class="text-lg font-medium mb-4">单个套餐续费测试</h3>
                <el-form :model="singleRenewForm" inline>
                    <el-form-item label="套餐ID">
                        <el-input v-model="singleRenewForm.package_id" placeholder="请输入套餐ID" />
                    </el-form-item>
                    <el-form-item label="续费天数">
                        <el-input-number v-model="singleRenewForm.extend_days" :min="1" :max="3650" />
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="testSingleRenew" :loading="singleLoading">
                            测试单个续费
                        </el-button>
                    </el-form-item>
                </el-form>
                <div v-if="singleResult" class="mt-4">
                    <h4>响应结果：</h4>
                    <pre class="bg-gray-100 p-4 rounded">{{ JSON.stringify(singleResult, null, 2) }}</pre>
                </div>
            </div>

            <!-- 批量续费测试 -->
            <div class="mb-6">
                <h3 class="text-lg font-medium mb-4">批量套餐续费测试</h3>
                <el-form :model="batchRenewForm" inline>
                    <el-form-item label="套餐ID列表">
                        <el-input 
                            v-model="batchRenewForm.package_ids_text" 
                            placeholder="请输入套餐ID，用逗号分隔"
                            style="width: 300px"
                        />
                    </el-form-item>
                    <el-form-item label="续费天数">
                        <el-input-number v-model="batchRenewForm.extend_days" :min="1" :max="3650" />
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="testBatchRenew" :loading="batchLoading">
                            测试批量续费
                        </el-button>
                    </el-form-item>
                </el-form>
                <div v-if="batchResult" class="mt-4">
                    <h4>响应结果：</h4>
                    <pre class="bg-gray-100 p-4 rounded">{{ JSON.stringify(batchResult, null, 2) }}</pre>
                </div>
            </div>

            <!-- 可续费套餐查询测试 -->
            <div class="mb-6">
                <h3 class="text-lg font-medium mb-4">可续费套餐查询测试</h3>
                <el-form :model="renewableForm" inline>
                    <el-form-item label="租户ID">
                        <el-input v-model="renewableForm.tenant_id" placeholder="请输入租户ID" />
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="testRenewablePackages" :loading="renewableLoading">
                            查询可续费套餐
                        </el-button>
                    </el-form-item>
                </el-form>
                <div v-if="renewableResult" class="mt-4">
                    <h4>响应结果：</h4>
                    <pre class="bg-gray-100 p-4 rounded">{{ JSON.stringify(renewableResult, null, 2) }}</pre>
                </div>
            </div>
        </el-card>
    </div>
</template>

<script lang="ts" setup name="testRenew">
import { packageRenew, packageBatchRenew, packageRenewablePackages } from '@/api/package'
import feedback from '@/utils/feedback'

// 单个续费表单
const singleRenewForm = reactive({
    package_id: '',
    extend_days: 30
})
const singleLoading = ref(false)
const singleResult = ref(null)

// 批量续费表单
const batchRenewForm = reactive({
    package_ids_text: '',
    extend_days: 30
})
const batchLoading = ref(false)
const batchResult = ref(null)

// 可续费套餐查询表单
const renewableForm = reactive({
    tenant_id: ''
})
const renewableLoading = ref(false)
const renewableResult = ref(null)

// 测试单个续费
const testSingleRenew = async () => {
    if (!singleRenewForm.package_id) {
        feedback.msgWarning('请输入套餐ID')
        return
    }
    
    singleLoading.value = true
    try {
        const result = await packageRenew({
            package_id: parseInt(singleRenewForm.package_id),
            extend_days: singleRenewForm.extend_days
        })
        singleResult.value = result
        feedback.msgSuccess('单个续费测试成功')
    } catch (error: any) {
        singleResult.value = error.response?.data || error
        feedback.msgError('单个续费测试失败: ' + (error.message || '未知错误'))
    } finally {
        singleLoading.value = false
    }
}

// 测试批量续费
const testBatchRenew = async () => {
    if (!batchRenewForm.package_ids_text) {
        feedback.msgWarning('请输入套餐ID列表')
        return
    }
    
    const packageIds = batchRenewForm.package_ids_text
        .split(',')
        .map(id => parseInt(id.trim()))
        .filter(id => !isNaN(id))
    
    if (packageIds.length === 0) {
        feedback.msgWarning('请输入有效的套餐ID')
        return
    }
    
    batchLoading.value = true
    try {
        const result = await packageBatchRenew({
            package_ids: packageIds,
            extend_days: batchRenewForm.extend_days
        })
        batchResult.value = result
        feedback.msgSuccess('批量续费测试成功')
    } catch (error: any) {
        batchResult.value = error.response?.data || error
        feedback.msgError('批量续费测试失败: ' + (error.message || '未知错误'))
    } finally {
        batchLoading.value = false
    }
}

// 测试可续费套餐查询
const testRenewablePackages = async () => {
    if (!renewableForm.tenant_id) {
        feedback.msgWarning('请输入租户ID')
        return
    }
    
    renewableLoading.value = true
    try {
        const result = await packageRenewablePackages({
            tenant_id: parseInt(renewableForm.tenant_id)
        })
        renewableResult.value = result
        feedback.msgSuccess('可续费套餐查询测试成功')
    } catch (error: any) {
        renewableResult.value = error.response?.data || error
        feedback.msgError('可续费套餐查询测试失败: ' + (error.message || '未知错误'))
    } finally {
        renewableLoading.value = false
    }
}
</script>

<style scoped>
pre {
    max-height: 300px;
    overflow-y: auto;
}
</style>
