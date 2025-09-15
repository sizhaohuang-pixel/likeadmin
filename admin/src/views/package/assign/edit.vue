<template>
    <div class="assign-popup">
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
                <el-form-item label="选择租户" prop="tenant_id">
                    <el-select
                        v-model="formData.tenant_id"
                        placeholder="请选择租户"
                        class="w-full"
                        filterable
                        :disabled="tenantDisabled"
                    >
                        <el-option
                            v-for="item in tenantOptions"
                            :key="item.id"
                            :label="`${item.name}(${item.account})`"
                            :value="item.id"
                        />
                    </el-select>
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

<script lang="ts" setup name="assignEdit">
import type { FormInstance } from 'element-plus'
import { packageAssign, packageTenantOptions } from '@/api/package'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const mode = ref('add')
const tenantDisabled = ref(false)
const tenantOptions = ref<any[]>([])

const popupTitle = computed(() => {
    return '分配套餐'
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
    tenant_id: [
        {
            required: true,
            message: '请选择租户',
            trigger: 'change'
        }
    ],
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

// 获取租户选项
const getTenantOptions = async () => {
    try {
        const data = await packageTenantOptions({})
        tenantOptions.value = data
    } catch (error) {
        console.error('获取租户选项失败:', error)
    }
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
    getTenantOptions()
}

// 设置租户数据（当从列表点击分配时）
const setTenantData = (tenantData: any) => {
    formData.tenant_id = tenantData.id
    tenantDisabled.value = true
}

// 重置表单
const resetForm = () => {
    formData.tenant_id = ''
    formData.port_count = 100
    formData.expire_days = 30
    formData.remark = ''
    tenantDisabled.value = false
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
