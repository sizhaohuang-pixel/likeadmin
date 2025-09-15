<template>
    <div class="edit-popup">
        <popup ref="popupRef" :title="popupTitle" :async="true" width="550px" @confirm="handleSubmit"
            @close="handleClose">
            <el-form ref="formRef" :model="formData" label-width="90px" :rules="formRules">
                <template v-if="mode === 'add'">
                    <el-form-item label="区号" prop="area_code">
                        <el-input v-model="formData.area_code" clearable placeholder="请输入区号" />
                    </el-form-item>
                    <el-form-item label="电话" prop="phone">
                        <el-input v-model="formData.phone" clearable placeholder="请输入电话" />
                    </el-form-item>
                    <el-form-item label="密码" prop="password">
                        <el-input v-model="formData.password" clearable placeholder="请输入密码" />
                    </el-form-item>
                    <el-form-item label="系统ID" prop="mid">
                        <el-input v-model="formData.mid" clearable placeholder="请输入系统ID" />
                    </el-form-item>
                    <el-form-item label="自定义ID" prop="uid">
                        <el-input v-model="formData.uid" clearable placeholder="请输入自定义ID（可选）" />
                    </el-form-item>
                    <el-form-item label="访问令牌" prop="accesstoken">
                        <el-input class="flex-1" v-model="formData.accesstoken" type="textarea" :rows="4" clearable
                            placeholder="请输入访问令牌" />
                    </el-form-item>
                    <el-form-item label="刷新令牌" prop="refreshtoken">
                        <el-input class="flex-1" v-model="formData.refreshtoken" type="textarea" :rows="4" clearable
                            placeholder="请输入刷新令牌" />
                    </el-form-item>
                </template>
                
                <!-- 代理配置 -->
                <el-divider v-if="mode === 'edit'" content-position="left">代理配置</el-divider>
                <el-divider v-else content-position="left">代理配置（可选）</el-divider>
                
                <el-form-item label="代理URL" prop="proxy_url">
                    <el-input
                        v-model="formData.proxy_url"
                        type="textarea"
                        :rows="3"
                        placeholder="请输入代理URL，如：socks5://username:password@host:port"
                        clearable
                    />
                    <div class="text-gray-500 text-sm mt-1">
                        支持格式：http://host:port、https://host:port、socks4://host:port、socks5://username:password@host:port
                    </div>
                </el-form-item>

                <el-form-item v-if="proxyPreview" label="URL预览">
                    <el-input
                        :value="proxyPreview"
                        readonly
                        placeholder="代理URL预览将在此显示"
                    />
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup name="altAccountEdit">
import type { FormInstance } from 'element-plus'
import Popup from '@/components/popup/index.vue'
import { apiAltAccountAdd, apiAltAccountEdit, apiAltAccountDetail } from '@/api/alt_account'
import { timeFormat } from '@/utils/util'
import type { PropType } from 'vue'
defineProps({
    dictData: {
        type: Object as PropType<Record<string, any[]>>,
        default: () => ({})
    }
})
const emit = defineEmits(['success', 'close'])
const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const mode = ref('add')


// 弹窗标题
const popupTitle = computed(() => {
    return mode.value == 'edit' ? '代理配置' : '新增账号'
})

// 表单数据
const formData = reactive({
    id: '',
    area_code: '',
    phone: '',
    password: '',
    mid: '',
    uid: '',
    platform: '',
    accesstoken: '',
    refreshtoken: '',
    proxy_url: ''
})

// 代理URL预览（隐藏密码）
const proxyPreview = computed(() => {
    if (!formData.proxy_url) {
        return ''
    }
    
    try {
        // 解析代理URL，隐藏密码部分
        const url = new URL(formData.proxy_url)
        if (url.username && url.password) {
            return `${url.protocol}//${url.username}:***@${url.hostname}:${url.port}`
        } else {
            return formData.proxy_url
        }
    } catch (error) {
        // 如果解析失败，使用正则表达式隐藏密码
        return formData.proxy_url.replace(/:([^:@]+)@/, ':***@')
    }
})

// 验证代理URL格式
const validateProxyUrl = (url: string): boolean => {
    if (!url) return true // 空值允许（清除代理）
    
    try {
        const parsedUrl = new URL(url)
        const validProtocols = ['http:', 'https:', 'socks4:', 'socks5:']
        return validProtocols.includes(parsedUrl.protocol)
    } catch (error) {
        return false
    }
}

// 表单验证
const formRules = computed(() => {
    return {
        // 区号非必填
        area_code: [],
        // 电话非必填
        phone: [],
        // 密码非必填
        password: [],
        // 系统ID必填
        mid: [{
            required: true,
            message: '请输入系统ID',
            trigger: ['blur']
        }],
        // 自定义ID - 非必填
        uid: [],
        // 访问令牌 - 只在新增时必填
        accesstoken: mode.value === 'add' ? [{
            required: true,
            message: '请输入访问令牌',
            trigger: ['blur']
        }] : [],
        // 刷新令牌 - 只在新增时必填
        refreshtoken: mode.value === 'add' ? [{
            required: true,
            message: '请输入刷新令牌',
            trigger: ['blur']
        }] : [],
        // 代理URL验证
        proxy_url: [
            {
                validator: (rule: any, value: any, callback: any) => {
                    if (value && !validateProxyUrl(value)) {
                        callback(new Error('请输入有效的代理URL格式'))
                    } else {
                        callback()
                    }
                },
                trigger: ['blur']
            }
        ]
    }
})


// 获取详情
const setFormData = async (data: Record<any, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key]
        }
    }


}

const getDetail = async (row: Record<string, any>) => {
    const data = await apiAltAccountDetail({
        id: row.id
    })
    setFormData(data)
}


// 提交按钮
const handleSubmit = async () => {
    await formRef.value?.validate()
    const data = { ...formData, }
    mode.value == 'edit'
        ? await apiAltAccountEdit(data)
        : await apiAltAccountAdd(data)
    popupRef.value?.close()
    emit('success')
}

//打开弹窗
const open = (type = 'add') => {
    mode.value = type
    popupRef.value?.open()
}

// 关闭回调
const handleClose = () => {
    emit('close')
}



defineExpose({
    open,
    setFormData,
    getDetail
})
</script>

<style scoped>
.ml-2 {
    margin-left: 8px;
}

.text-gray-500 {
    color: #6b7280;
}
</style>
