<template>
    <div class="batch-renew-popup">
        <popup
            ref="popupRef"
            title="批量续费"
            :async="true"
            width="700px"
            @confirm="handleSubmit"
            @close="handleClose"
        >
            <el-form
                ref="formRef"
                :model="formData"
                label-width="100px"
                :rules="rules"
            >
                <el-form-item label="选中套餐">
                    <div class="selected-packages">
                        <div class="package-count">
                            已选择 <el-tag type="primary">{{ selectedPackages.length }}</el-tag> 个套餐
                        </div>
                        <div class="package-list">
                            <div 
                                v-for="pkg in selectedPackages" 
                                :key="pkg.id"
                                class="package-item"
                            >
                                <div class="package-info">
                                    <span class="package-id">ID: {{ pkg.id }}</span>
                                    <span class="package-tenant">{{ pkg.tenant_name }}</span>
                                    <span class="package-ports">{{ pkg.port_count }}端口</span>
                                    <el-tag 
                                        :type="pkg.remaining_days > 7 ? 'success' : pkg.remaining_days > 0 ? 'warning' : 'danger'"
                                        size="small"
                                    >
                                        剩余{{ pkg.remaining_days }}天
                                    </el-tag>
                                </div>
                            </div>
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="续费天数" prop="extend_days">
                    <el-input-number
                        v-model="formData.extend_days"
                        :min="1"
                        :max="3650"
                        placeholder="请输入续费天数"
                        class="w-full"
                    />
                    <div class="text-xs text-gray-500 mt-1">续费天数范围：1-3650天，将应用到所有选中的套餐</div>
                </el-form-item>
                <el-form-item label="续费说明">
                    <div class="renew-description">
                        <div class="desc-item">
                            <el-icon class="text-green-500"><SuccessFilled /></el-icon>
                            <span>未过期套餐：在原到期时间基础上延长</span>
                        </div>
                        <div class="desc-item">
                            <el-icon class="text-orange-500"><WarningFilled /></el-icon>
                            <span>已过期套餐：从当前时间开始计算</span>
                        </div>
                        <div class="desc-item">
                            <el-icon class="text-blue-500"><InfoFilled /></el-icon>
                            <span>续费成功后，套餐状态自动设为有效</span>
                        </div>
                    </div>
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup name="batchRenewPopup">
import type { FormInstance } from 'element-plus'
import { packageBatchRenew } from '@/api/package'
import { SuccessFilled, WarningFilled, InfoFilled } from '@element-plus/icons-vue'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()

// 选中的套餐
const selectedPackages = ref<any[]>([])

// 表单数据
const formData = reactive({
    package_ids: [] as number[],
    extend_days: 30
})

// 表单验证规则
const rules = {
    extend_days: [
        {
            required: true,
            message: '请输入续费天数',
            trigger: 'blur'
        },
        {
            type: 'number',
            min: 1,
            max: 3650,
            message: '续费天数必须在1-3650之间',
            trigger: 'blur'
        }
    ]
}

// 提交表单
const handleSubmit = async () => {
    await formRef.value?.validate()
    
    if (selectedPackages.value.length === 0) {
        feedback.msgError('没有选中的套餐')
        return
    }
    
    // 续费天数超过365天时给出警告
    if (formData.extend_days > 365) {
        const confirmed = await feedback.confirm(
            `续费天数为${formData.extend_days}天，超过365天。建议分批续费或联系管理员。确定要继续吗？`,
            '批量续费提醒'
        )
        if (!confirmed) return
    }
    
    // 确认批量续费
    const confirmed = await feedback.confirm(
        `确定要为${selectedPackages.value.length}个套餐续费${formData.extend_days}天吗？`,
        '批量续费确认'
    )
    if (!confirmed) return
    
    try {
        await packageBatchRenew(formData)
        popupRef.value?.close()
        emit('success')
        feedback.msgSuccess(`批量续费成功，共续费${selectedPackages.value.length}个套餐`)
    } catch (error: any) {
        if (error.message === '没有套餐续费成功') {
            feedback.msgError('所有套餐续费失败，请检查套餐权限和状态')
        } else {
            throw error
        }
    }
}

// 关闭弹窗
const handleClose = () => {
    emit('close')
}

// 打开弹窗
const open = (packages: any[]) => {
    selectedPackages.value = packages
    formData.package_ids = packages.map(pkg => pkg.id)
    formData.extend_days = 30
    
    popupRef.value?.open()
}

// 重置表单
const resetForm = () => {
    selectedPackages.value = []
    formData.package_ids = []
    formData.extend_days = 30
}

watch(() => popupRef.value?.visible, (visible) => {
    if (!visible) {
        resetForm()
    }
})

defineExpose({
    open
})
</script>

<style scoped>
.selected-packages {
    @apply border border-gray-200 rounded-lg p-4;
}

.package-count {
    @apply mb-3 text-sm font-medium;
}

.package-list {
    @apply max-h-60 overflow-y-auto space-y-2;
}

.package-item {
    @apply p-3 bg-gray-50 rounded border;
}

.package-info {
    @apply flex items-center gap-3 text-sm;
}

.package-id {
    @apply font-mono text-blue-600;
}

.package-tenant {
    @apply font-medium text-gray-900;
}

.package-ports {
    @apply text-gray-600;
}

.renew-description {
    @apply space-y-2 p-3 bg-blue-50 rounded border border-blue-200;
}

.desc-item {
    @apply flex items-center gap-2 text-sm;
}
</style>
