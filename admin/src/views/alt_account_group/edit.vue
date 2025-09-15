<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            width="500px"
            @confirm="handleSubmit"
            @close="handleClose"
        >
            <el-form ref="formRef" :model="formData" label-width="100px" :rules="formRules">
                <!-- 分组名称 -->
                <el-form-item label="分组名称" prop="name">
                    <el-input
                        v-model="formData.name"
                        placeholder="请输入分组名称"
                        clearable
                        maxlength="50"
                        show-word-limit
                    />
                </el-form-item>

                <!-- 分组描述 -->
                <el-form-item label="分组描述" prop="description">
                    <el-input
                        v-model="formData.description"
                        type="textarea"
                        placeholder="请输入分组描述"
                        :rows="4"
                        maxlength="200"
                        show-word-limit
                    />
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup name="altAccountGroupEdit">
import type { FormInstance } from 'element-plus'
import { apiAltAccountGroupAdd, apiAltAccountGroupEdit } from '@/api/alt_account_group'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const mode = ref('add')
const popupTitle = computed(() => {
    return mode.value == 'edit' ? '编辑分组' : '新增分组'
})

// 表单数据
const formData = reactive({
    id: '',
    name: '',
    description: ''
})

// 表单验证
const formRules = reactive({
    name: [
        {
            required: true,
            message: '请输入分组名称',
            trigger: ['blur']
        },
        {
            min: 1,
            max: 50,
            message: '分组名称长度应为1-50个字符',
            trigger: ['blur']
        }
    ],
    description: [
        {
            max: 200,
            message: '分组描述长度不能超过200个字符',
            trigger: ['blur']
        }
    ]
})

// 设置表单数据
const setFormData = async (data: Record<string, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key]
        }
    }
}

// 提交表单
const handleSubmit = async () => {
    await formRef.value?.validate()
    const data = { ...formData }
    
    mode.value == 'edit' ? await apiAltAccountGroupEdit(data) : await apiAltAccountGroupAdd(data)
    popupRef.value?.close()
    feedback.msgSuccess('操作成功')
    emit('success')
}

// 打开弹窗
const open = (type = 'add') => {
    mode.value = type
    
    // 重置表单数据
    Object.assign(formData, {
        id: '',
        name: '',
        description: ''
    })
    
    popupRef.value?.open()
}

// 关闭弹窗
const handleClose = () => {
    emit('close')
}

defineExpose({
    open,
    setFormData
})
</script>
