<template>
    <div class="edit-popup">
        <popup ref="popupRef" :title="popupTitle" :async="true" width="550px" @confirm="handleSubmit"
            @close="handleClose">
            <el-form ref="formRef" :model="formData" label-width="84px" :rules="formRules">
                <!-- 账号输入框 -->
                <el-form-item label="账号" prop="account">
                    <el-input v-model="formData.account" placeholder="请输入账号" clearable />
                </el-form-item>

                <!-- 客服头像 -->
                <el-form-item label="头像">
                    <div>
                        <div>
                            <material-picker v-model="formData.avatar" :limit="1" />
                        </div>
                        <div class="form-tips">建议尺寸：100*100px，支持jpg，jpeg，png格式</div>
                    </div>
                </el-form-item>

                <!-- 昵称输入框 -->
                <el-form-item label="昵称" prop="name">
                    <el-input v-model="formData.name" placeholder="请输入昵称" clearable />
                </el-form-item>

                <!-- 密码输入框 -->
                <el-form-item label="密码" prop="password">
                    <el-input v-model="formData.password" type="password" placeholder="请输入密码" show-password clearable />
                </el-form-item>

                <!-- 确认密码输入框 -->
                <el-form-item label="确认密码" prop="password_confirm">
                    <el-input v-model="formData.password_confirm" type="password" placeholder="请再次输入密码" show-password
                        clearable />
                </el-form-item>

                <!-- 状态选择 -->
                <el-form-item label="状态" prop="disable" v-if="mode === 'edit'">
                    <el-radio-group v-model="formData.disable">
                        <el-radio :label="0">启用</el-radio>
                        <el-radio :label="1">禁用</el-radio>
                    </el-radio-group>
                </el-form-item>

                <!-- 多点登录选择 -->
                <el-form-item label="多点登录" prop="multipoint_login">
                    <el-radio-group v-model="formData.multipoint_login">
                        <el-radio :label="0">不支持</el-radio>
                        <el-radio :label="1">支持</el-radio>
                    </el-radio-group>
                </el-form-item>

                <!-- 分配上限设置 -->
                <el-form-item label="分配上限" prop="account_limit">
                    <div class="space-y-3">
                        <el-switch
                            v-model="isUnlimited"
                            active-text="无限制"
                            inactive-text="限制数量"
                            @change="handleUnlimitedChange"
                            style="--el-switch-on-color: #13ce66; --el-switch-off-color: #409eff;"
                        />
                        <div v-if="!isUnlimited" class="space-y-2">
                            <el-input-number
                                v-model="formData.account_limit"
                                :min="0"
                                :max="999999"
                                placeholder="请输入分配上限"
                                style="width: 200px"
                                :precision="0"
                            />
                            <div class="text-sm text-gray-500">
                                <div>• 0 = 禁止分配账号</div>
                                <div>• 正整数 = 具体的分配上限数量</div>
                            </div>
                        </div>
                    </div>
                </el-form-item>

            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup name="operatorEdit">
import type { FormInstance } from 'element-plus'
import { operatorAdd, operatorEdit, operatorDetail } from '@/api/operator'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const mode = ref('add')
const popupTitle = computed(() => {
    return mode.value == 'edit' ? '编辑客服' : '新增客服'
})

// 表单数据
const formData = reactive({
    id: '',
    account: '',
    name: '',
    password: '',
    password_confirm: '',
    avatar: '',
    disable: 0,
    multipoint_login: 0,
    account_limit: 0,
    parent_name: ''
})

// 无限制开关状态
const isUnlimited = ref(false)

// 表单验证
const formRules = reactive({
    account: [
        {
            required: true,
            message: '请输入账号',
            trigger: ['blur']
        },
        {
            min: 1,
            max: 32,
            message: '账号长度应为1-32个字符',
            trigger: ['blur']
        }
    ],
    name: [
        {
            required: true,
            message: '请输入昵称',
            trigger: ['blur']
        },
        {
            min: 1,
            max: 16,
            message: '昵称长度应为1-16个字符',
            trigger: ['blur']
        }
    ],
    password: [
        {
            required: true,
            message: '请输入密码',
            trigger: ['blur']
        },
        {
            min: 6,
            max: 32,
            message: '密码长度应为6-32个字符',
            trigger: ['blur']
        }
    ],
    password_confirm: [
        {
            required: true,
            message: '请再次输入密码',
            trigger: ['blur']
        },
        {
            validator: (rule: any, value: any, callback: any) => {
                // 编辑模式下，如果密码都为空，则通过验证
                if (mode.value === 'edit' && !formData.password && !value) {
                    callback()
                    return
                }
                
                if (value !== formData.password) {
                    callback(new Error('两次输入的密码不一致'))
                } else {
                    callback()
                }
            },
            trigger: ['blur']
        }
    ]
})

// 处理无限制切换
const handleUnlimitedChange = (value: boolean) => {
    if (value) {
        // 切换到无限制
        formData.account_limit = -1
    } else {
        // 切换到限制数量
        formData.account_limit = 0
    }
}

// 获取详情
const setFormData = async (data: Record<string, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key]
        }
    }

    // 根据account_limit值设置无限制开关状态
    isUnlimited.value = formData.account_limit === -1

    // 编辑模式下密码不是必填的
    if (mode.value === 'edit') {
        formRules.password[0].required = false
        formRules.password_confirm[0].required = false
    }
}

const handleSubmit = async () => {
    await formRef.value?.validate()
    const data = { ...formData }

    // 如果是编辑模式且密码为空，则不传递密码字段
    if (mode.value === 'edit' && !data.password) {
        delete data.password
        delete data.password_confirm
    }

    // 移除不需要提交的字段
    delete data.parent_name

    mode.value == 'edit' ? await operatorEdit(data) : await operatorAdd(data)
    popupRef.value?.close()
    feedback.msgSuccess('操作成功')
    emit('success')
}

const open = (type = 'add') => {
    mode.value = type

    // 重置表单数据
    Object.assign(formData, {
        id: '',
        account: '',
        name: '',
        password: '',
        password_confirm: '',
        avatar: '',
        disable: 0,
        multipoint_login: 0,
        account_limit: 0,
        parent_name: ''
    })

    // 重置无限制开关状态
    isUnlimited.value = false

    // 重置验证规则
    if (type === 'add') {
        formRules.password[0].required = true
        formRules.password_confirm[0].required = true
    } else if (type === 'edit') {
        formRules.password[0].required = false
        formRules.password_confirm[0].required = false
    }

    popupRef.value?.open()
}

const handleClose = () => {
    emit('close')
}

defineExpose({
    open,
    setFormData
})
</script>
