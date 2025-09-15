<template>
    <div class="set-group">
        <popup ref="popupRef" title="配置分组" :async="true" width="500px" @confirm="handleSubmit" @close="handleClose">
            <el-form ref="formRef" :model="formData" label-width="100px" :rules="formRules">
                <el-form-item label="选中账号">
                    <div class="selected-accounts">
                        <el-tag v-if="selectedCount > 0" type="info">
                            已选中 {{ selectedCount }} 个账号
                        </el-tag>
                        <el-tag v-else type="warning">
                            请先选择要配置的账号
                        </el-tag>
                    </div>
                </el-form-item>

                <el-form-item label="选择分组" prop="group_id">
                    <el-select v-model="formData.group_id" placeholder="请选择分组" style="width: 100%"
                        :loading="groupLoading">
                        <el-option v-for="group in allGroupOptions" :key="group.id" :label="group.name"
                            :value="group.id">
                            <span>{{ group.name }}</span>
                            <span style="float: right; color: #8492a6; font-size: 13px">
                                {{ group.description || (group.id === 0 ? '不属于任何分组' : '无描述') }}
                            </span>
                        </el-option>
                    </el-select>
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup name="setGroup">
import type { FormInstance } from 'element-plus'
import { apiAltAccountBatchSetGroup } from '@/api/alt_account'
import { apiAltAccountGroupGetGroupOptions } from '@/api/alt_account_group'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'
import { hasPermission } from '@/utils/perm'

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()

// 表单数据
const formData = reactive({
    group_id: '',
    alt_account_ids: [] as number[]
})

// 分组列表
const groupList = ref<any[]>([])
const groupLoading = ref(false)

// 选中的账号数量
const selectedCount = computed(() => formData.alt_account_ids.length)

// 所有分组选项（包含未分组选项）
const allGroupOptions = computed(() => {
    const options = [...groupList.value]

    // 检查是否已经包含未分组选项（id为0或name为"未分组"）
    const hasUngrouped = options.some(group => group.id === 0 || group.name === '未分组')

    // 如果没有未分组选项，则添加一个
    if (!hasUngrouped) {
        options.unshift({
            id: 0,
            name: '未分组',
            description: '不属于任何分组'
        })
    }

    return options
})

// 表单验证
const formRules = reactive({
    group_id: [
        {
            required: true,
            message: '请选择分组',
            trigger: ['blur', 'change']
        }
    ]
})

// 获取分组选项列表
const getGroupOptions = async () => {
    // 检查权限
    if (!hasPermission(['alt_account_group/getGroupOptions'])) {
        feedback.msgError('没有权限获取分组列表')
        return
    }

    try {
        groupLoading.value = true
        const data = await apiAltAccountGroupGetGroupOptions()
        groupList.value = data || []
    } catch (error) {
        console.error('获取分组列表失败:', error)
        feedback.msgError('获取分组列表失败')
    } finally {
        groupLoading.value = false
    }
}

// 提交配置
const handleSubmit = async () => {
    if (formData.alt_account_ids.length === 0) {
        feedback.msgError('请先选择要配置的账号')
        return
    }

    await formRef.value?.validate()

    try {
        await apiAltAccountBatchSetGroup({
            alt_account_ids: formData.alt_account_ids,
            group_id: formData.group_id
        })

        feedback.msgSuccess('配置分组成功')
        popupRef.value?.close()
        emit('success')
    } catch (error) {
        console.error('配置分组失败:', error)
    }
}

// 打开弹窗
const open = (selectedIds: number[]) => {
    formData.alt_account_ids = [...selectedIds]
    formData.group_id = ''

    // 获取分组列表
    getGroupOptions()

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
