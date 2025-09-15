<template>
    <div class="assign-package-popup">
        <popup
            ref="popupRef"
            :title="popupTitle"
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
                <el-form-item label="租户信息">
                    <div class="tenant-info">
                        <div class="tenant-name">{{ tenantData.name }}</div>
                        <div class="tenant-account">账号：{{ tenantData.account }}</div>
                    </div>
                </el-form-item>
                <el-form-item label="端口数量" prop="port_count">
                    <el-input-number
                        v-model="formData.port_count"
                        :min="1"
                        :max="10000"
                        placeholder="请输入端口数量"
                        class="w-full"
                    />
                    <div class="text-xs text-gray-500 mt-1">端口数量范围：1-10000</div>
                </el-form-item>
                <el-form-item label="有效天数" prop="expire_days">
                    <el-input-number
                        v-model="formData.expire_days"
                        :min="1"
                        :max="3650"
                        placeholder="请输入有效天数"
                        class="w-full"
                    />
                    <div class="text-xs text-gray-500 mt-1">有效天数范围：1-3650天</div>
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input
                        v-model="formData.remark"
                        type="textarea"
                        :rows="3"
                        placeholder="请输入备注信息（可选）"
                        maxlength="255"
                        show-word-limit
                    />
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup name="assignPackageEdit">
import type { FormInstance } from 'element-plus'
import { packageAssign } from '@/api/package'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const mode = ref('add')

const popupTitle = computed(() => {
    return '分配套餐'
})

// 租户数据
const tenantData = reactive({
    id: '',
    name: '',
    account: ''
})

// 表单数据
const formData = reactive({
    tenant_id: '',
    port_count: 100,
    expire_days: 30,
    remark: ''
})

// 表单验证规则
const rules = {
    port_count: [
        {
            required: true,
            message: '请输入端口数量',
            trigger: 'blur'
        },
        {
            type: 'number',
            min: 1,
            max: 10000,
            message: '端口数量必须在1-10000之间',
            trigger: 'blur'
        }
    ],
    expire_days: [
        {
            required: true,
            message: '请输入有效天数',
            trigger: 'blur'
        },
        {
            type: 'number',
            min: 1,
            max: 3650,
            message: '有效天数必须在1-3650之间',
            trigger: 'blur'
        }
    ]
}

// 提交表单
const handleSubmit = async () => {
    await formRef.value?.validate()
    await packageAssign(formData)
    popupRef.value?.close()
    emit('success')
    feedback.msgSuccess('套餐分配成功')
}

// 关闭弹窗
const handleClose = () => {
    emit('close')
}

// 打开弹窗
const open = (type = 'add') => {
    mode.value = type
    popupRef.value?.open()
}

// 设置租户数据
const setTenantData = (data: any) => {
    tenantData.id = data.id
    tenantData.name = data.name
    tenantData.account = data.account
    formData.tenant_id = data.id
}

// 重置表单
const resetForm = () => {
    formData.tenant_id = ''
    formData.port_count = 100
    formData.expire_days = 30
    formData.remark = ''
    tenantData.id = ''
    tenantData.name = ''
    tenantData.account = ''
}

watch(() => popupRef.value?.visible, (visible) => {
    if (!visible) {
        resetForm()
    }
})

defineExpose({
    open,
    setTenantData
})
</script>

<style scoped>
.tenant-info {
    @apply p-3 bg-gray-50 rounded-lg;
}

.tenant-name {
    @apply text-lg font-medium text-gray-900;
}

.tenant-account {
    @apply text-sm text-gray-600 mt-1;
}
</style>
