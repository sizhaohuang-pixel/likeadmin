<template>
    <div class="set-proxy">
        <popup ref="popupRef" :title="popupTitle" :async="true" width="600px" @confirm="handleSubmit" @close="handleClose">
            <el-form ref="formRef" :model="formData" label-width="120px" :rules="formRules">
                <el-form-item label="选中账号">
                    <div class="selected-accounts">
                        <el-tag v-if="selectedCount > 0" type="info">
                            已选中 {{ selectedCount }} 个账号
                        </el-tag>
                        <el-tag v-else type="warning">
                            请先选择要设置的账号
                        </el-tag>
                    </div>
                </el-form-item>

                <el-form-item label="代理URL" prop="proxy_url">
                    <el-input
                        v-model="formData.proxy_url"
                        type="textarea"
                        :rows="4"
                        placeholder="请输入代理URL，如：socks5://username:password@host:port"
                        clearable
                    />
                    <div class="text-gray-500 text-sm mt-1">
                        支持格式：http://host:port、https://host:port、socks4://host:port、socks5://username:password@host:port
                    </div>
                </el-form-item>

                <el-form-item v-if="proxyPreview" label="URL预览">
                    <div class="proxy-preview">
                        <el-input
                            :value="proxyPreview"
                            readonly
                            placeholder="代理URL预览将在此显示"
                        />
                    </div>
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup name="setProxy">
import type { FormInstance } from 'element-plus'
import { apiAltAccountSetProxy, apiAltAccountBatchSetProxy } from '@/api/alt_account'
import Popup from '@/components/popup/index.vue'
import feedback from '@/utils/feedback'

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()

// 表单数据
const formData = reactive({
    proxy_url: '',
    alt_account_ids: [] as number[],
    single_account_id: null as number | null,
    nickname: '' // 单个账号时显示昵称
})

// 是否为批量设置模式
const isBatchMode = computed(() => formData.alt_account_ids.length > 1)

// 选中的账号数量
const selectedCount = computed(() =>
    isBatchMode.value ? formData.alt_account_ids.length : (formData.single_account_id ? 1 : 0)
)

// 弹窗标题
const popupTitle = computed(() => {
    if (isBatchMode.value) {
        return `批量设置代理 (${selectedCount.value}个账号)`
    } else if (formData.nickname) {
        return `设置代理 - ${formData.nickname}`
    } else {
        return '设置代理'
    }
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

// 解析代理URL为后端需要的格式
const parseProxyUrl = (url: string) => {
    if (!url) {
        return {
            enabled: false,
            proxy_type: '',
            proxy_host: '',
            proxy_port: null,
            proxy_username: '',
            proxy_password: ''
        }
    }
    
    try {
        const parsedUrl = new URL(url)
        return {
            enabled: true,
            proxy_type: parsedUrl.protocol.replace(':', ''),
            proxy_host: parsedUrl.hostname,
            proxy_port: parseInt(parsedUrl.port) || (parsedUrl.protocol === 'https:' ? 443 : 80),
            proxy_username: parsedUrl.username || '',
            proxy_password: parsedUrl.password || ''
        }
    } catch (error) {
        throw new Error('代理URL格式不正确')
    }
}

// 表单验证规则
const formRules = computed(() => {
    return {
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

// 提交设置
const handleSubmit = async () => {
    if (selectedCount.value === 0) {
        feedback.msgError('请先选择要设置的账号')
        return
    }

    // 验证表单
    await formRef.value?.validate()

    try {
        const params = parseProxyUrl(formData.proxy_url)

        if (isBatchMode.value) {
            // 批量设置
            await apiAltAccountBatchSetProxy({
                ...params,
                alt_account_ids: formData.alt_account_ids
            })
        } else {
            // 单个设置
            await apiAltAccountSetProxy({
                ...params,
                id: formData.single_account_id
            })
        }

        feedback.msgSuccess(formData.proxy_url ? '代理设置成功' : '代理清除成功')
        popupRef.value?.close()
        emit('success')
    } catch (error) {
        console.error('设置代理失败:', error)
        if (error instanceof Error) {
            feedback.msgError(error.message)
        }
    }
}

// 打开弹窗 - 批量设置模式
const openBatch = (selectedIds: number[]) => {
    formData.alt_account_ids = [...selectedIds]
    formData.single_account_id = null
    formData.nickname = ''
    resetForm()
    popupRef.value?.open()
}

// 打开弹窗 - 单个设置模式
const openSingle = (accountId: number, nickname: string = '') => {
    formData.alt_account_ids = []
    formData.single_account_id = accountId
    formData.nickname = nickname
    resetForm()
    popupRef.value?.open()
}

// 重置表单
const resetForm = () => {
    formData.proxy_url = ''
}

// 关闭弹窗
const handleClose = () => {
    emit('close')
}

defineExpose({
    openBatch,
    openSingle
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

.proxy-preview {
    width: 100%;
}

.proxy-preview .el-input {
    font-family: 'Courier New', monospace;
}

.ml-2 {
    margin-left: 8px;
}

.text-gray-500 {
    color: #6b7280;
}

.text-sm {
    font-size: 14px;
}

.mt-1 {
    margin-top: 4px;
}
</style>