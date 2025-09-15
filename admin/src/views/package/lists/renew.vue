<template>
    <div class="renew-popup">
        <popup
            ref="popupRef"
            title="套餐续费"
            :async="true"
            width="500px"
            @confirm="handleSubmit"
            @close="handleClose"
        >
            <el-form
                ref="formRef"
                :model="formData"
                label-width="100px"
                :rules="rules"
            >
                <el-form-item label="套餐信息">
                    <div class="package-info">
                        <div class="info-row">
                            <span class="label">套餐ID：</span>
                            <span class="value">{{ packageData.id }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">租户：</span>
                            <span class="value">{{ packageData.tenant_name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">端口数量：</span>
                            <span class="value">{{ packageData.port_count }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">当前到期时间：</span>
                            <span class="value">{{ packageData.expire_time_text }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">剩余天数：</span>
                            <el-tag 
                                :type="packageData.remaining_days > 7 ? 'success' : packageData.remaining_days > 0 ? 'warning' : 'danger'"
                            >
                                {{ packageData.remaining_days }}天
                            </el-tag>
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
                    <div class="text-xs text-gray-500 mt-1">续费天数范围：1-3650天</div>
                </el-form-item>
                <el-form-item label="续费后到期时间">
                    <div class="new-expire-time">
                        <el-tag type="success">{{ newExpireTime }}</el-tag>
                    </div>
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup name="renewPopup">
import type { FormInstance } from 'element-plus'
import { packageRenew } from '@/api/package'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()

// 套餐数据
const packageData = reactive({
    id: '',
    tenant_name: '',
    port_count: 0,
    expire_time_text: '',
    remaining_days: 0
})

// 表单数据
const formData = reactive({
    package_id: '',
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

// 计算续费后的到期时间
const newExpireTime = computed(() => {
    if (!packageData.expire_time_text || !formData.extend_days) {
        return '请输入续费天数'
    }
    
    try {
        // 解析当前到期时间
        const currentExpireDate = new Date(packageData.expire_time_text)
        
        // 如果套餐已过期，从当前时间开始计算
        const baseDate = packageData.remaining_days <= 0 ? new Date() : currentExpireDate
        
        // 添加续费天数
        const newDate = new Date(baseDate.getTime() + formData.extend_days * 24 * 60 * 60 * 1000)
        
        return newDate.toLocaleString('zh-CN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        })
    } catch (error) {
        return '计算错误'
    }
})

// 提交表单
const handleSubmit = async () => {
    await formRef.value?.validate()
    
    // 续费天数超过365天时给出警告
    if (formData.extend_days > 365) {
        const confirmed = await feedback.confirm(
            `续费天数为${formData.extend_days}天，超过365天。建议分批续费或联系管理员。确定要继续吗？`,
            '续费提醒'
        )
        if (!confirmed) return
    }
    
    await packageRenew(formData)
    popupRef.value?.close()
    emit('success')
    feedback.msgSuccess('套餐续费成功')
}

// 关闭弹窗
const handleClose = () => {
    emit('close')
}

// 打开弹窗
const open = (data: any) => {
    // 设置套餐数据
    packageData.id = data.id
    packageData.tenant_name = data.tenant_name
    packageData.port_count = data.port_count
    packageData.expire_time_text = data.expire_time_text
    packageData.remaining_days = data.remaining_days
    
    // 设置表单数据
    formData.package_id = data.id
    formData.extend_days = 30
    
    popupRef.value?.open()
}

// 重置表单
const resetForm = () => {
    formData.package_id = ''
    formData.extend_days = 30
    
    packageData.id = ''
    packageData.tenant_name = ''
    packageData.port_count = 0
    packageData.expire_time_text = ''
    packageData.remaining_days = 0
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
.package-info {
    @apply p-4 bg-gray-50 rounded-lg space-y-2;
}

.info-row {
    @apply flex items-center;
}

.label {
    @apply text-gray-600 w-24 flex-shrink-0;
}

.value {
    @apply text-gray-900 font-medium;
}

.new-expire-time {
    @apply p-2 bg-green-50 rounded border border-green-200;
}
</style>
