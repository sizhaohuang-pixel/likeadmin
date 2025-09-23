<template>
    <div>
        <el-dialog 
            v-model="dialogVisible" 
            title="创建批量改昵称任务" 
            width="600px"
            :close-on-click-modal="false"
            @close="handleClose">
            
            <el-form 
                ref="formRef" 
                :model="formData" 
                :rules="formRules" 
                label-width="120px"
                class="create-task-form">
                
                <el-form-item label="任务名称" prop="task_name">
                    <el-input 
                        v-model="formData.task_name"
                        placeholder="请输入任务名称"
                        maxlength="100"
                        show-word-limit
                        clearable />
                </el-form-item>

                <el-form-item label="账号分组" prop="account_group_id">
                    <el-select 
                        v-model="formData.account_group_id"
                        placeholder="请选择账号分组"
                        style="width: 100%;"
                        @change="handleAccountGroupChange">
                        <el-option 
                            v-for="group in accountGroups" 
                            :key="group.id" 
                            :label="`${group.name} (${group.account_count}个账号)`"
                            :value="group.id" />
                    </el-select>
                </el-form-item>

                <el-form-item label="昵称分组" prop="nickname_group_name">
                    <el-select 
                        v-model="formData.nickname_group_name"
                        placeholder="请选择昵称分组"
                        style="width: 100%;"
                        @change="handleNicknameGroupChange">
                        <el-option 
                            v-for="group in nicknameGroups" 
                            :key="group.name" 
                            :label="`${group.name} (${group.available_count}个可用昵称)`"
                            :value="group.name" />
                    </el-select>
                </el-form-item>

                <!-- 任务预览信息 -->
                <el-card v-if="showPreview" class="task-preview" shadow="never">
                    <template #header>
                        <div class="preview-header">
                            <span>任务预览</span>
                        </div>
                    </template>
                    <div class="preview-content">
                        <div class="preview-item">
                            <span class="label">选择账号数量：</span>
                            <span class="value">{{ selectedAccountCount }} 个</span>
                        </div>
                        <div class="preview-item">
                            <span class="label">可用昵称数量：</span>
                            <span class="value">{{ selectedNicknameCount }} 个</span>
                        </div>
                        <div class="preview-item">
                            <span class="label">执行状态：</span>
                            <span 
                                :class="[
                                    'value',
                                    canExecute ? 'success' : 'danger'
                                ]">
                                {{ statusText }}
                            </span>
                        </div>
                    </div>
                </el-card>

                <!-- 注意事项 -->
                <el-alert
                    title="注意事项"
                    type="info"
                    show-icon
                    :closable="false"
                    class="mt-4">
                    <template #default>
                        <ul class="notice-list">
                            <li>昵称修改成功后，对应昵称将标记为已使用</li>
                            <li>任务创建后将自动分配昵称给对应账号</li>
                            <li>昵称不足时，部分账号可能无法分配昵称</li>
                            <li>任务执行期间请勿删除相关账号或昵称分组</li>
                        </ul>
                    </template>
                </el-alert>
            </el-form>

            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="handleClose">取消</el-button>
                    <el-button 
                        type="primary" 
                        :loading="loading"
                        :disabled="!canExecute"
                        @click="handleSubmit">
                        创建任务
                    </el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="CreateTaskDialog">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import type { ElForm } from 'element-plus'
import { apiBatchNicknameTaskCreate, apiGetAccountGroups, apiGetNicknameGroups } from '@/api/task-management'
import feedback from '@/utils/feedback'

// Props
interface Props {
    modelValue: boolean
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'refresh': []
}>()

// 表单引用
const formRef = ref<InstanceType<typeof ElForm>>()

// 弹窗显示状态
const dialogVisible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// 加载状态
const loading = ref(false)

// 表单数据
const formData = reactive({
    task_name: '',
    account_group_id: undefined as number | undefined,
    nickname_group_name: ''
})

// 表单验证规则
const formRules = {
    task_name: [
        { required: true, message: '请输入任务名称', trigger: 'blur' },
        { min: 1, max: 100, message: '任务名称长度在1到100个字符', trigger: 'blur' }
    ],
    account_group_id: [
        { required: true, message: '请选择账号分组', trigger: 'change' }
    ],
    nickname_group_name: [
        { required: true, message: '请选择昵称分组', trigger: 'change' }
    ]
}

// 账号分组列表
const accountGroups = ref<Array<{
    id: number
    name: string
    account_count: number
}>>([])

// 昵称分组列表
const nicknameGroups = ref<Array<{
    name: string
    available_count: number
    total_count: number
}>>([])

// 选择的账号数量
const selectedAccountCount = ref(0)

// 选择的昵称数量
const selectedNicknameCount = ref(0)

// 是否显示预览
const showPreview = computed(() => {
    return formData.account_group_id && formData.nickname_group_name
})

// 是否可以执行
const canExecute = computed(() => {
    return selectedAccountCount.value > 0 && 
           selectedNicknameCount.value > 0 && 
           selectedNicknameCount.value >= selectedAccountCount.value
})

// 状态文本
const statusText = computed(() => {
    if (!showPreview.value) {
        return '请选择账号分组和昵称分组'
    }
    
    if (selectedAccountCount.value === 0) {
        return '选择的账号分组中没有可用账号'
    }
    
    if (selectedNicknameCount.value === 0) {
        return '选择的昵称分组中没有可用昵称'
    }
    
    if (selectedNicknameCount.value < selectedAccountCount.value) {
        return `昵称不足，缺少 ${selectedAccountCount.value - selectedNicknameCount.value} 个昵称`
    }
    
    return '可以执行任务'
})

// 处理账号分组变化
const handleAccountGroupChange = (groupId: number) => {
    const group = accountGroups.value.find(g => g.id === groupId)
    selectedAccountCount.value = group ? group.account_count : 0
}

// 处理昵称分组变化
const handleNicknameGroupChange = (groupName: string) => {
    const group = nicknameGroups.value.find(g => g.name === groupName)
    selectedNicknameCount.value = group ? group.available_count : 0
}

// 获取账号分组列表
const getAccountGroups = async () => {
    console.log('开始获取账号分组数据...')
    try {
        const res = await apiGetAccountGroups()
        console.log('账号分组API响应:', res)
        
        if (res && typeof res === 'object') {
            if ('code' in res && res.code === 1) {
                accountGroups.value = res.data || []
                console.log('账号分组数据设置成功:', accountGroups.value)
            } else if (Array.isArray(res)) {
                accountGroups.value = res
                console.log('账号分组数据设置成功(数组):', accountGroups.value)
            } else {
                accountGroups.value = res as any
                console.log('账号分组数据设置成功(直接):', accountGroups.value)
            }
        }
    } catch (error: any) {
        console.error('获取账号分组失败:', error)
        feedback.msgError(error.message || '获取账号分组失败')
    }
}

// 获取昵称分组列表
const getNicknameGroups = async () => {
    console.log('开始获取昵称分组数据...')
    try {
        const res = await apiGetNicknameGroups()
        console.log('昵称分组API响应:', res)
        
        if (res && typeof res === 'object') {
            if ('code' in res && res.code === 1) {
                nicknameGroups.value = res.data || []
                console.log('昵称分组数据设置成功:', nicknameGroups.value)
            } else if (Array.isArray(res)) {
                nicknameGroups.value = res
                console.log('昵称分组数据设置成功(数组):', nicknameGroups.value)
            } else {
                nicknameGroups.value = res as any
                console.log('昵称分组数据设置成功(直接):', nicknameGroups.value)
            }
        }
    } catch (error: any) {
        console.error('获取昵称分组失败:', error)
        feedback.msgError(error.message || '获取昵称分组失败')
    }
}

// 提交表单
const handleSubmit = async () => {
    if (!formRef.value) return
    
    try {
        await formRef.value.validate()
        
        if (!canExecute.value) {
            feedback.msgError('当前条件不满足任务执行要求')
            return
        }
        
        loading.value = true
        
        const res = await apiBatchNicknameTaskCreate(formData)
        console.log('创建任务API响应:', res)
        
        if (res && typeof res === 'object') {
            if ('code' in res) {
                if (res.code === 1) {
                    feedback.msgSuccess('任务创建成功')
                    handleClose()
                    emit('refresh')
                } else {
                    feedback.msgError(res.msg || '创建任务失败')
                }
            } else {
                feedback.msgSuccess('任务创建成功')
                handleClose()
                emit('refresh')
            }
        } else {
            feedback.msgError('创建任务失败：响应数据格式错误')
        }
    } catch (error: any) {
        feedback.msgError(error.message || '创建任务失败')
    } finally {
        loading.value = false
    }
}

// 关闭弹窗
const handleClose = () => {
    formRef.value?.resetFields()
    Object.assign(formData, {
        task_name: '',
        account_group_id: undefined,
        nickname_group_name: ''
    })
    selectedAccountCount.value = 0
    selectedNicknameCount.value = 0
    emit('update:modelValue', false)
}

// 监听弹窗显示状态
watch(dialogVisible, (visible) => {
    console.log('弹窗状态变化:', visible)
    if (visible) {
        console.log('弹窗打开，开始加载数据...')
        getAccountGroups()
        getNicknameGroups()
        
        // 自动生成任务名称
        if (!formData.task_name) {
            formData.task_name = `批量改昵称任务_${new Date().toLocaleString('zh-CN', {
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }).replace(/[\/\s]/g, '')}`
        }
    } else {
        console.log('弹窗关闭')
    }
})

onMounted(() => {
    // 组件挂载时不需要加载数据，等弹窗打开时再加载
})
</script>

<style scoped>
.create-task-form {
    padding: 0 20px;
}

.task-preview {
    margin-top: 20px;
    border: 1px solid #e4e7ed;
}

.preview-header {
    font-weight: 600;
    color: #303133;
}

.preview-content {
    padding: 0;
}

.preview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f5f7fa;
}

.preview-item:last-child {
    border-bottom: none;
}

.preview-item .label {
    color: #606266;
    font-size: 14px;
}

.preview-item .value {
    font-weight: 600;
    font-size: 14px;
}

.preview-item .value.success {
    color: #67c23a;
}

.preview-item .value.danger {
    color: #f56c6c;
}

.notice-list {
    margin: 0;
    padding-left: 20px;
    color: #606266;
    font-size: 14px;
    line-height: 1.6;
}

.notice-list li {
    margin-bottom: 4px;
}

.dialog-footer {
    text-align: right;
}

.mt-4 {
    margin-top: 16px;
}
</style>