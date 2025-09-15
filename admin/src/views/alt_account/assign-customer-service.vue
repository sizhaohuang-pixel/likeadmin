<template>
    <div class="assign-customer-service">
        <popup ref="popupRef" title="分配客服" :async="true" width="500px" @confirm="handleSubmit" @close="handleClose">
            <el-form ref="formRef" :model="formData" label-width="100px" :rules="formRules">
                <el-form-item label="选中账号">
                    <div class="selected-accounts">
                        <el-tag v-if="selectedCount > 0" type="info">
                            已选中 {{ selectedCount }} 个账号
                        </el-tag>
                        <el-tag v-else type="warning">
                            请先选择要分配的账号
                        </el-tag>
                    </div>
                </el-form-item>

                <el-form-item label="选择客服" prop="operator_id">
                    <el-select v-model="formData.operator_id" placeholder="请选择客服" style="width: 100%"
                        :loading="operatorLoading">
                        <el-option v-for="operator in operatorList" :key="operator.id" :label="operator.name"
                            :value="operator.id">
                            <span>{{ operator.name }}</span>
                            <span style="float: right; color: #8492a6; font-size: 13px">
                                {{ operator.account }}
                            </span>
                        </el-option>
                    </el-select>
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup name="assignCustomerService">
import type { FormInstance } from 'element-plus'
import { apiAltAccountAssignCustomerService, apiAltAccountGetAvailableOperators } from '@/api/alt_account'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'
import { hasPermission } from '@/utils/perm'

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()

// 表单数据
const formData = reactive({
    operator_id: '',
    alt_account_ids: [] as number[]
})

// 运营列表
const operatorList = ref<any[]>([])
const operatorLoading = ref(false)

// 选中的账号数量
const selectedCount = computed(() => formData.alt_account_ids.length)

// 表单验证
const formRules = reactive({
    operator_id: [
        {
            required: true,
            message: '请选择客服',
            trigger: ['blur', 'change']
        }
    ]
})

// 获取可分配运营列表
const getAvailableOperators = async () => {
    // 检查权限
    if (!hasPermission(['alt_account/getAvailableOperators'])) {
        feedback.msgError('没有权限获取客服列表')
        return
    }

    try {
        operatorLoading.value = true
        const data = await apiAltAccountGetAvailableOperators()
        operatorList.value = data || []
    } catch (error) {
        console.error('获取运营列表失败:', error)
        feedback.msgError('获取客服列表失败')
    } finally {
        operatorLoading.value = false
    }
}

// 提交分配
const handleSubmit = async () => {
    if (formData.alt_account_ids.length === 0) {
        feedback.msgError('请先选择要分配的账号')
        return
    }

    await formRef.value?.validate()

    try {
        await apiAltAccountAssignCustomerService({
            alt_account_ids: formData.alt_account_ids,
            operator_id: formData.operator_id
        })

        feedback.msgSuccess('分配客服成功')
        popupRef.value?.close()
        emit('success')
    } catch (error) {
        console.error('分配客服失败:', error)
    }
}

// 打开弹窗
const open = (selectedIds: number[]) => {
    formData.alt_account_ids = [...selectedIds]
    formData.operator_id = ''

    // 获取运营列表
    getAvailableOperators()

    popupRef.value?.open()
}

// 关闭弹窗
const handleClose = () => {
    emit('close')
}

defineExpose({
    open
})
</script>

<style scoped>
.selected-accounts {
    display: flex;
    align-items: center;
    min-height: 32px;
}

.el-tag {
    margin-right: 8px;
}
</style>
