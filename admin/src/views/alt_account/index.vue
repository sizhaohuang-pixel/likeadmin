<template>
    <div>
        <el-card class="!border-none mb-4" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" inline>
                <el-form-item label="昵称" prop="nickname">
                    <el-input class="w-[180px]" v-model="queryParams.nickname" clearable placeholder="请输入昵称" />
                </el-form-item>
                <el-form-item label="区号" prop="area_code">
                    <el-input class="w-[100px]" v-model="queryParams.area_code" clearable placeholder="请输入区号" />
                </el-form-item>
                <el-form-item label="电话" prop="phone">
                    <el-input class="w-[180px]" v-model="queryParams.phone" clearable placeholder="请输入电话" />
                </el-form-item>
                <el-form-item label="密码" prop="password">
                    <el-input class="w-[180px]" v-model="queryParams.password" clearable placeholder="请输入密码" />
                </el-form-item>
                <el-form-item label="是否存在昵称" prop="has_nickname">
                    <el-select class="w-[220px]" v-model="queryParams.has_nickname" clearable placeholder="请选择是否存在昵称"
                        style="width: 220px;">
                        <el-option label="全部" value=""></el-option>
                        <el-option label="存在" value="yes"></el-option>
                        <el-option label="不存在" value="no"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="是否存在自定义ID" prop="has_uid">
                    <el-select class="w-[220px]" v-model="queryParams.has_uid" clearable placeholder="请选择是否存在自定义ID"
                        style="width: 220px;">
                        <el-option label="全部" value=""></el-option>
                        <el-option label="存在" value="yes"></el-option>
                        <el-option label="不存在" value="no"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="状态" prop="status">
                    <el-select class="w-[220px] status-select" v-model="queryParams.status" clearable
                        placeholder="请选择状态" style="width: 220px;">
                        <el-option label="全部" value=""></el-option>
                        <el-option v-for="(item, index) in dictData.alt_status" :key="index" :label="item.name"
                            :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="分组" prop="group_id">
                    <el-select class="w-[220px]" v-model="queryParams.group_id" clearable placeholder="请选择分组"
                        style="width: 220px;">
                        <el-option label="全部" value=""></el-option>
                        <el-option v-for="group in allGroupOptions" :key="group.id" :label="group.name"
                            :value="group.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="客服" prop="operator_id">
                    <el-select class="w-[220px]" v-model="queryParams.operator_id" clearable placeholder="请选择客服"
                        :loading="operatorLoading" style="width: 220px;">
                        <el-option label="全部" value=""></el-option>
                        <el-option v-for="operator in allOperatorOptions" :key="operator.id" :label="operator.name"
                            :value="operator.id">
                            <span>{{ operator.name }}</span>
                            <span v-if="operator.account && operator.account !== operator.name"
                                style="float: right; color: #8492a6; font-size: 13px">
                                {{ operator.account }}
                            </span>
                        </el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="代理状态" prop="proxy_status">
                    <el-select class="w-[220px]" v-model="queryParams.proxy_status" clearable placeholder="请选择代理状态"
                        style="width: 220px;">
                        <el-option label="全部" value=""></el-option>
                        <el-option label="未设置" value="none"></el-option>
                        <el-option label="已设置" value="set"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none" v-loading="pager.loading" shadow="never">
            <el-button v-perms="['alt_account/add']" type="primary" @click="handleAdd">
                <template #icon>
                    <icon name="el-icon-Plus" />
                </template>
                新增
            </el-button>
            <el-button v-perms="['alt_account/batchImport']" type="success" @click="handleImport">
                <template #icon>
                    <icon name="el-icon-Upload" />
                </template>
                批量导入
            </el-button>
            <el-button v-perms="['alt_account/delete']" :disabled="!selectData.length"
                @click="handleDelete(selectData)">
                删除
            </el-button>
            <el-button v-perms="['alt_account/assignCustomerService']" :disabled="!selectData.length" type="success"
                @click="handleAssignCustomerService">
                分配客服
            </el-button>
            <el-button v-perms="['alt_account/batchSetGroup']" :disabled="!selectData.length" type="warning"
                @click="handleSetGroup">
                配置分组
            </el-button>
            <el-button v-perms="['alt_account/batchSetProxy']" :disabled="!selectData.length" type="info"
                @click="handleBatchSetProxy">
                批量设置代理
            </el-button>
            <el-button v-perms="['alt_account/clearProxy']" :disabled="!selectData.length" type="danger"
                @click="handleClearProxy">
                清除代理
            </el-button>
            <el-button v-perms="['alt_account/batch_verify']" :disabled="isCreatingTask" type="warning"
                :loading="isCreatingTask" @click="handleBatchVerify">
                <template #icon v-if="!isCreatingTask">
                    <icon name="el-icon-Refresh" />
                </template>
                一键验活 (根据搜索条件)
            </el-button>
            <div class="mt-4">
                <el-table :data="pager.lists" @selection-change="handleSelectionChange">
                    <el-table-column type="selection" width="55" />
                    <el-table-column label="头像" prop="avatar">
                        <template #default="{ row }">
                            <el-image style="width:50px;height:50px;" :src="row.avatar" />
                        </template>
                    </el-table-column>
                    <el-table-column label="昵称" prop="nickname" show-overflow-tooltip>
                        <template #default="{ row }">
                            {{ row.nickname || '暂无' }}
                        </template>
                    </el-table-column>
                    <el-table-column label="区号" prop="area_code" show-overflow-tooltip />
                    <el-table-column label="电话" prop="phone" show-overflow-tooltip />
                    <el-table-column label="密码" prop="password" show-overflow-tooltip />
                    <el-table-column label="自定义ID" prop="uid" show-overflow-tooltip>
                        <template #default="{ row }">
                            {{ row.uid || '暂无' }}
                        </template>
                    </el-table-column>
                    <el-table-column label="系统平台" prop="platform" width="100" show-overflow-tooltip>
                        <template #default="{ row }">
                            <el-tag v-if="row.platform === 'ANDROID'" type="success" size="small">
                                Android
                            </el-tag>
                            <el-tag v-else-if="row.platform === 'IOS'" type="primary" size="small">
                                iOS
                            </el-tag>
                            <el-tag v-else type="info" size="small">
                                未设置
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="分组" prop="group_name" min-width="120" show-overflow-tooltip>
                        <template #default="{ row }">
                            <el-tag v-if="row.group_name" type="success" size="small">
                                {{ row.group_name }}
                            </el-tag>
                            <el-tag v-else type="info" size="small">
                                未分组
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="所属客服" prop="operator_name" min-width="120" show-overflow-tooltip>
                        <template #default="{ row }">
                            <el-tag v-if="row.operator_name" type="primary" size="small">
                                {{ row.operator_name }}
                            </el-tag>
                            <el-tag v-else type="warning" size="small">
                                未分配
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="代理状态" prop="proxy_status" min-width="80" show-overflow-tooltip>
                        <template #default="{ row }">
                            <el-tag v-if="!row.proxy_url" type="info" size="small">
                                未设置
                            </el-tag>
                            <el-tag v-else type="success" size="small">
                                已设置
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="状态" prop="status">
                        <template #default="{ row }">
                            <dict-value :options="dictData.alt_status" :value="row.status" />
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="140" fixed="right">
                        <template #default="{ row }">
                            <div class="flex items-center gap-2">
                                <el-button v-perms="['alt_account/edit']" type="primary" link @click="handleEdit(row)">
                                    设置代理
                                </el-button>
                                <el-dropdown trigger="click">
                                    <el-button type="primary" link>
                                        更多
                                        <el-icon class="el-icon--right">
                                            <icon name="el-icon-ArrowDown" />
                                        </el-icon>
                                    </el-button>
                                    <template #dropdown>
                                        <el-dropdown-menu>
                                            <template v-if="checkPerms(['alt_account/edit'])">
                                                <el-dropdown-item @click="handleVerifyAccount(row)">
                                                    账号验活
                                                </el-dropdown-item>
                                            </template>
                                            <el-dropdown-item divided @click="handleEditCustomId(row)">
                                                修改自定义ID
                                            </el-dropdown-item>
                                            <el-dropdown-item @click="handleEditNickname(row)">
                                                修改昵称
                                            </el-dropdown-item>
                                            <el-dropdown-item @click="handleEditAvatar(row)">
                                                修改头像
                                            </el-dropdown-item>
                                            <template v-if="checkPerms(['alt_account/delete'])">
                                                <el-dropdown-item divided @click="handleDelete(row.id)" class="text-red-600">
                                                    删除
                                                </el-dropdown-item>
                                            </template>
                                        </el-dropdown-menu>
                                    </template>
                                </el-dropdown>
                            </div>
                        </template>
                                    </el-table-column>
                </el-table>
            </div>
            <div class="flex mt-4 justify-end">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
        <edit-popup v-show="showEdit" ref="editRef" :dict-data="dictData" @success="getLists" @close="showEdit = false" />
        <assign-customer-service-popup v-show="showAssignCustomerService" ref="assignCustomerServiceRef"
            @success="handleAssignSuccess" @close="showAssignCustomerService = false" />
        <set-group-popup v-show="showSetGroup" ref="setGroupRef" @success="handleSetGroupSuccess"
            @close="showSetGroup = false" />
        <set-proxy-popup v-show="showSetProxy" ref="setProxyRef" @success="handleSetProxySuccess"
            @close="showSetProxy = false" />
        <!-- 批量导入弹窗 -->
        <import-account-popup v-model="showImport" @success="handleImportSuccess" />
        
        <!-- 头像更新弹窗 -->
        <el-dialog 
            v-model="avatarDialogVisible" 
            title="修改头像" 
            width="500px"
            :close-on-click-modal="false"
        >
            <div class="text-center">
                <div class="mb-4">
                    <p class="text-gray-600 mb-2">当前头像：</p>
                    <el-image 
                        v-if="currentAvatarAccount?.avatar" 
                        :src="currentAvatarAccount.avatar" 
                        style="width: 120px; height: 120px; border-radius: 8px;"
                        fit="cover"
                    />
                    <div v-else class="w-30 h-30 border-2 border-dashed border-gray-300 flex items-center justify-center rounded-lg">
                        <span class="text-gray-500">暂无头像</span>
                    </div>
                </div>
                
                <!-- 预览新头像 -->
                <div v-if="previewAvatarUrl" class="mb-4">
                    <p class="text-gray-600 mb-2">新头像预览：</p>
                    <el-image 
                        :src="previewAvatarUrl" 
                        style="width: 120px; height: 120px; border-radius: 8px;"
                        fit="cover"
                    />
                </div>
                
                <div class="mb-4">
                    <el-button 
                        type="primary" 
                        @click="handleAvatarFileSelect"
                        :loading="isUpdatingAvatar"
                        v-if="!previewAvatarUrl"
                    >
                        <template #icon>
                            <icon name="el-icon-Upload" />
                        </template>
                        选择新头像
                    </el-button>
                    
                    <!-- 预览状态下的操作按钮 -->
                    <div v-else class="space-x-2">
                        <el-button 
                            type="success" 
                            @click="handleConfirmAvatar"
                            :loading="isUpdatingAvatar"
                        >
                            <template #icon>
                                <icon name="el-icon-Check" />
                            </template>
                            确认更新
                        </el-button>
                        <el-button 
                            @click="handleAvatarFileSelect"
                            :disabled="isUpdatingAvatar"
                        >
                            重新选择
                        </el-button>
                    </div>
                </div>
                
                <div class="text-xs text-gray-500">
                    <p>支持 JPG、PNG、JPEG 格式</p>
                    <p>文件大小不超过 1.5MB</p>
                </div>
            </div>
            
            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="avatarDialogVisible = false" :disabled="isUpdatingAvatar">
                        取消
                    </el-button>
                </div>
            </template>
        </el-dialog>
        
        <!-- 隐藏的文件输入 -->
        <input 
            ref="avatarFileInput"
            type="file" 
            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
            style="display: none;"
            @change="handleAvatarFileChange"
        />
    </div>
</template>

<script lang="ts" setup name="altAccountLists">
import { usePaging } from '@/hooks/usePaging'
import { useDictData } from '@/hooks/useDictOptions'
import { apiAltAccountLists, apiAltAccountDelete, apiAltAccountGetAvailableOperators, apiAltAccountBatchSetProxy, apiAltAccountClearProxy, apiAltAccountUpdateNickname, apiAltAccountUpdateAvatar, apiAltAccountVerify } from '@/api/alt_account'
import { apiBatchTaskCreate, apiBatchTaskCheck } from '@/api/task-management'
import feedback from '@/utils/feedback'
import { useRouter } from 'vue-router'
import EditPopup from './edit.vue'
import AssignCustomerServicePopup from './assign-customer-service.vue'
import SetGroupPopup from './set-group.vue'
import SetProxyPopup from './set-proxy.vue'
import ImportAccountPopup from './ImportAccountPopup.vue'
import { apiAltAccountGroupGetGroupOptions } from '@/api/alt_account_group'
import useUserStore from '@/stores/modules/user'

const editRef = shallowRef<InstanceType<typeof EditPopup>>()
const assignCustomerServiceRef = shallowRef<InstanceType<typeof AssignCustomerServicePopup>>()
const setGroupRef = shallowRef<InstanceType<typeof SetGroupPopup>>()
const setProxyRef = shallowRef<InstanceType<typeof SetProxyPopup>>()

// 权限检查函数
const userStore = useUserStore()
const checkPerms = (perms: string[]): boolean => {
    const permissions = userStore.perms
    const all_permission = '*'
    if (Array.isArray(perms) && perms.length > 0) {
        return permissions.some((key: string) => {
            return all_permission === key || perms.includes(key)
        })
    }
    return false
}
// 是否显示编辑框
const showEdit = ref(false)
// 是否显示分配客服弹窗
const showAssignCustomerService = ref(false)
// 是否显示配置分组弹窗
const showSetGroup = ref(false)
// 是否显示设置代理弹窗
const showSetProxy = ref(false)
// 是否显示批量导入弹窗
const showImport = ref(false)


// 查询条件
const queryParams = reactive({
    nickname: '',
    area_code: '',
    phone: '',
    password: '',
    has_nickname: '',
    has_uid: '',
    status: '',
    group_id: '',
    operator_id: '',
    proxy_status: ''
})

// 分组选项
const groupOptions = ref<any[]>([])
// 客服选项
const operatorOptions = ref<any[]>([])
const operatorLoading = ref(false)

// 所有分组选项（包含未分组选项，去重）
const allGroupOptions = computed(() => {
    const options = [...groupOptions.value]

    // 检查是否已经包含未分组选项（id为0或name为"未分组"）
    const hasUngrouped = options.some(group => group.id === 0 || group.name === '未分组')

    // 如果没有未分组选项，则添加一个
    if (!hasUngrouped) {
        options.unshift({
            id: 0,
            name: '未分组'
        })
    }

    return options
})

// 所有客服选项（包含未分配选项，去重）
const allOperatorOptions = computed(() => {
    // 先对原始数据进行去重和清理
    const cleanedOptions = operatorOptions.value
        .filter(operator => operator && operator.id !== undefined) // 过滤无效数据
        .map(operator => ({
            id: operator.id,
            name: operator.name || '未知客服',
            account: operator.account || ''
        }))
        .filter((operator, index, self) =>
            index === self.findIndex(o => o.id === operator.id) // 根据id去重
        )

    // 检查是否已经包含未分配选项
    const hasUnassigned = cleanedOptions.some(operator => operator.id === 0)

    // 如果没有未分配选项，则添加一个
    if (!hasUnassigned) {
        cleanedOptions.unshift({
            id: 0,
            name: '未分配',
            account: ''
        })
    }

    return cleanedOptions
})

// 选中数据
const selectData = ref<any[]>([])

// 表格选择后回调事件
const handleSelectionChange = (val: any[]) => {
    selectData.value = val.map(({ id }) => id)
}

// 获取字典数据
const { dictData } = useDictData('alt_status')

// 分页相关
const { pager, getLists, resetParams, resetPage } = usePaging({
    fetchFun: apiAltAccountLists,
    params: queryParams
})

// 添加
const handleAdd = async () => {
    showEdit.value = true
    await nextTick()
    editRef.value?.open('add')
}

// 编辑
const handleEdit = async (data: any) => {
    showEdit.value = true
    await nextTick()
    editRef.value?.open('edit')
    editRef.value?.setFormData(data)
}

// 删除
const handleDelete = async (id: number | any[]) => {
    await feedback.confirm('确定要删除？')

    // 根据参数类型传递不同的字段名
    const params = Array.isArray(id) ? { ids: id } : { id }

    await apiAltAccountDelete(params)
    getLists()
}

// 分配客服
const handleAssignCustomerService = async () => {
    if (selectData.value.length === 0) {
        feedback.msgError('请先选择要分配的账号')
        return
    }

    showAssignCustomerService.value = true
    await nextTick()
    assignCustomerServiceRef.value?.open(selectData.value)
}

// 分配客服成功回调
const handleAssignSuccess = () => {
    // 清空选中状态
    selectData.value = []
    // 刷新列表
    getLists()
}

// 配置分组
const handleSetGroup = async () => {
    if (selectData.value.length === 0) {
        feedback.msgError('请先选择要配置的账号')
        return
    }

    showSetGroup.value = true
    await nextTick()
    setGroupRef.value?.open(selectData.value)
}

// 配置分组成功回调
const handleSetGroupSuccess = () => {
    // 清空选中状态
    selectData.value = []
    // 刷新列表
    getLists()
}

// 获取分组选项
const getGroupOptions = async () => {
    try {
        const data = await apiAltAccountGroupGetGroupOptions()
        groupOptions.value = data || []
    } catch (error) {
        console.error('获取分组选项失败:', error)
    }
}

// 获取客服选项
const getOperatorOptions = async () => {
    try {
        operatorLoading.value = true
        const data = await apiAltAccountGetAvailableOperators()
        operatorOptions.value = data || []
    } catch (error) {
        console.error('获取客服选项失败:', error)
    } finally {
        operatorLoading.value = false
    }
}

// 批量导入
const handleImport = () => {
    console.log('批量导入按钮被点击')
    console.log('设置 showImport 为 true')
    showImport.value = true
    console.log('showImport 当前值:', showImport.value)
}

// 导入成功回调
const handleImportSuccess = () => {
    showImport.value = false
    getLists()
    feedback.msgSuccess('导入成功')
}

// 设置代理
const handleSetProxy = async (row: any) => {
    showSetProxy.value = true
    await nextTick()
    setProxyRef.value?.openSingle(row.id, row.nickname)
}

// 批量设置代理
const handleBatchSetProxy = async () => {
    if (selectData.value.length === 0) {
        feedback.msgError('请先选择要设置的账号')
        return
    }

    showSetProxy.value = true
    await nextTick()
    setProxyRef.value?.openBatch(selectData.value)
}

// 清除代理
const handleClearProxy = async () => {
    if (selectData.value.length === 0) {
        feedback.msgError('请先选择要清除代理的账号')
        return
    }

    await feedback.confirm('确定要清除选中账号的代理设置？')

    try {
        await apiAltAccountClearProxy({ alt_account_ids: selectData.value })
        feedback.msgSuccess('清除代理成功')
        // 清空选中状态
        selectData.value = []
        // 刷新列表
        getLists()
    } catch (error) {
        console.error('清除代理失败:', error)
    }
}

// 设置代理成功回调
const handleSetProxySuccess = () => {
    // 清空选中状态
    selectData.value = []
    // 刷新列表
    getLists()
}

// 格式化代理显示
const formatProxyDisplay = (proxyUrl: string) => {
    if (!proxyUrl) return ''
    
    try {
        // 解析代理URL，隐藏密码部分
        const url = new URL(proxyUrl)
        if (url.username && url.password) {
            return `${url.protocol}//${url.username}:***@${url.hostname}:${url.port}`
        } else {
            return `${url.protocol}//${url.hostname}:${url.port}`
        }
    } catch (error) {
        // 如果解析失败，直接返回原始字符串（但隐藏可能的密码）
        return proxyUrl.replace(/:([^:@]+)@/, ':***@')
    }
}

// 账号验活
const handleVerifyAccount = async (row: any) => {
    try {
        console.log('验活账号数据:', {
            id: row.id,
            nickname: row.nickname,
            mid: row.mid,
            hasAccessToken: !!row.accesstoken,
            hasProxyUrl: !!row.proxy_url
        })
        
        // 检查必要字段
        if (!row.mid || row.mid.trim() === '') {
            feedback.msgError(`账号 ${row.nickname || row.phone || 'ID:' + row.id} 的MID不能为空，请先完善账号信息`)
            return
        }
        if (!row.accesstoken || row.accesstoken.trim() === '') {
            feedback.msgError(`账号 ${row.nickname || row.phone || 'ID:' + row.id} 的访问令牌不能为空，请先完善账号信息`)
            return
        }
        if (!row.proxy_url || row.proxy_url.trim() === '') {
            feedback.msgError(`账号 ${row.nickname || row.phone || 'ID:' + row.id} 的代理地址不能为空，请先设置代理`)
            return
        }

        // 显示验活进度
        feedback.loading('正在验活中...')
        
        try {
            const res = await apiAltAccountVerify({ id: row.id })
            
            console.log('=== 验活API调试信息 ===')
            console.log('完整响应对象:', res)
            console.log('响应数据类型:', typeof res)
            console.log('=== 调试信息结束 ===')
            
            // res 本身就是验活结果数据
            console.log('响应数据:', res)
            
            // 检查响应结构 - 直接从 res 中获取数据
            if (res && res.success !== undefined) {
                const { success, code, message, account_info } = res
                
                console.log('解析出的数据:', { success, code, message, account_info })
                
                if (success) {
                    // 根据状态码显示不同颜色的消息
                    const accountName = account_info?.nickname || account_info?.phone || '账号'
                    
                    console.log('准备显示成功消息, code:', code, 'message:', message, 'accountName:', accountName)
                    
                    switch (code) {
                        case 1: // 正常
                            feedback.msgSuccess(`✅ ${message} - ${accountName}`)
                            break
                        case 2: // 代理不可用
                            feedback.msgWarning(`⚠️ ${message} - ${accountName}`)
                            break
                        case 3: // 下线
                            feedback.msgWarning(`📴 ${message} - ${accountName}`)
                            break
                        case 4: // 封禁
                            feedback.msgError(`❌ ${message} - ${accountName}`)
                            break
                        default:
                            feedback.msgInfo(`${message} - ${accountName}`)
                    }
                    
                    // 验活完成后刷新列表以显示最新状态
                    getLists()
                } else {
                    // 验活失败（业务层面失败）
                    console.log('验活失败 - success为false, message:', message)
                    feedback.msgError(message || '验活失败')
                }
            } else {
                // API调用失败
                console.log('API调用失败 - 响应结构不正确')
                console.log('res:', res)
                feedback.msgError('验活失败')
            }
        } catch (apiError) {
            feedback.msgError('验活过程中发生网络错误')
            console.error('验活API错误:', apiError)
        } finally {
            // 确保loading一定会被关闭
            feedback.closeLoading()
        }
    } catch (error) {
        feedback.msgError('验活过程中发生错误')
        console.error('验活错误:', error)
    }
}

// 修改自定义ID（占位功能）
const handleEditCustomId = (row: any) => {
    feedback.msgInfo('修改自定义ID功能正在开发中...')
}

// 修改昵称
const handleEditNickname = async (row: any) => {
    const accountLabel = row.nickname || row.phone || `ID:${row.id}`

    if (!row.mid || row.mid.trim() === '') {
        feedback.msgError(`账号 ${accountLabel} 的MID不能为空，请先完善账号信息`)
        return
    }
    if (!row.accesstoken || row.accesstoken.trim() === '') {
        feedback.msgError(`账号 ${accountLabel} 的访问令牌不能为空，请先完善账号信息`)
        return
    }
    if (!row.proxy_url || row.proxy_url.trim() === '') {
        feedback.msgError(`账号 ${accountLabel} 的代理地址不能为空，请先设置代理`)
        return
    }

    try {
        const { value } = await feedback.prompt("请输入新的昵称（最多32个字符）", "修改昵称", {
            inputValue: row.nickname || "",
            inputPlaceholder: "请输入昵称",
            confirmButtonText: "保存",
            inputValidator: (val: string) => {
                const trimmed = val.trim()
                if (!trimmed) {
                    return "昵称不能为空"
                }
                if (trimmed.length > 32) {
                    return "昵称长度不能超过32个字符"
                }
                return true
            }
        })

        const nickname = (value || "").trim()
        const originalNickname = (row.nickname || "").trim()

        if (!nickname) {
            feedback.msgError("昵称不能为空")
            return
        }
        if (nickname === originalNickname) {
            feedback.msgInfo("昵称未发生变化")
            return
        }

        feedback.loading("昵称更新中...")
        try {
            const res = await apiAltAccountUpdateNickname({ id: row.id, nickname })
            if (res?.success) {
                feedback.msgSuccess(res.message || "昵称更新成功")
                row.nickname = nickname
            } else {
                feedback.msgError(res?.message || "昵称更新失败")
            }
        } catch (error: any) {
            feedback.msgError(error?.message || "昵称更新失败")
        } finally {
            feedback.closeLoading()
        }
    } catch (error: any) {
        const action = typeof error === 'object' ? error?.action : error
        if (action === 'cancel' || action === 'close') {
            return
        }
        feedback.msgError(error?.message || '修改昵称操作被中断')
    }
}


// 修改头像
const avatarDialogVisible = ref(false)
const currentAvatarAccount = ref<any>(null)
const avatarFileInput = ref<HTMLInputElement | null>(null)
const isUpdatingAvatar = ref(false)
const previewAvatarUrl = ref<string>('')
const selectedFile = ref<File | null>(null)

const handleEditAvatar = (row: any) => {
    currentAvatarAccount.value = row
    previewAvatarUrl.value = ''
    selectedFile.value = null
    avatarDialogVisible.value = true
}

const handleAvatarFileSelect = () => {
    avatarFileInput.value?.click()
}

const handleAvatarFileChange = async (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    
    if (!file) return
    
    // 验证文件类型
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png']
    if (!allowedTypes.includes(file.type)) {
        feedback.msgError('只支持 JPG、PNG、JPEG 格式的图片')
        return
    }
    
    // 验证文件大小（限制在1.5MB以内）
    if (file.size > 1.5 * 1024 * 1024) {
        feedback.msgError('图片大小不能超过1.5MB')
        return
    }
    
    try {
        // 保存选中的文件
        selectedFile.value = file
        
        // 创建预览URL
        previewAvatarUrl.value = URL.createObjectURL(file)
    } catch (error: any) {
        feedback.msgError(error?.message || '文件处理失败')
    } finally {
        // 清空文件输入
        if (target) {
            target.value = ''
        }
    }
}

const fileToBase64 = (file: File): Promise<string> => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader()
        reader.onload = () => {
            const result = reader.result as string
            // 返回完整的base64格式，包含data:image/xxx;base64,前缀
            resolve(result)
        }
        reader.onerror = () => reject(new Error('文件读取失败'))
        reader.readAsDataURL(file)
    })
}

// 确认提交头像
const handleConfirmAvatar = async () => {
    if (!selectedFile.value) {
        feedback.msgError('请先选择头像文件')
        return
    }
    
    try {
        // 转换为base64
        const base64 = await fileToBase64(selectedFile.value)
        
        // 提交头像更新
        await updateAccountAvatar(base64)
    } catch (error: any) {
        feedback.msgError(error?.message || '头像处理失败')
    }
}

const updateAccountAvatar = async (avatarBase64: string) => {
    if (!currentAvatarAccount.value) return
    
    isUpdatingAvatar.value = true
    
    try {
        const { data } = await apiAltAccountUpdateAvatar({
            id: currentAvatarAccount.value.id,
            avatar: avatarBase64
        })
        
        if (data.code === 1) {
            feedback.msgSuccess(data.msg || '头像修改成功')
            
            // 更新本地数据
            const accountIndex = pager.lists.findIndex((item: any) => item.id === currentAvatarAccount.value.id)
            if (accountIndex !== -1) {
                pager.lists[accountIndex].avatar = data.data?.avatar_url || pager.lists[accountIndex].avatar
            }
            
            // 清理预览状态
            if (previewAvatarUrl.value) {
                URL.revokeObjectURL(previewAvatarUrl.value)
                previewAvatarUrl.value = ''
            }
            selectedFile.value = null
            
            avatarDialogVisible.value = false
        } else {
            feedback.msgError(data.msg || '头像修改失败')
        }
    } catch (error: any) {
        feedback.msgError(error?.message || '头像修改失败')
    } finally {
        isUpdatingAvatar.value = false
    }
}

// 监听对话框关闭，清理URL对象
watch(avatarDialogVisible, (newVal) => {
    if (!newVal && previewAvatarUrl.value) {
        URL.revokeObjectURL(previewAvatarUrl.value)
        previewAvatarUrl.value = ''
        selectedFile.value = null
    }
})

// 批量验活处理
const router = useRouter()
const isCreatingTask = ref(false)
const handleBatchVerify = async () => {
    if (isCreatingTask.value) return // 防止重复点击
    
    // 生成搜索条件描述
    const conditions = []
    if (queryParams.nickname) conditions.push(`昵称包含"${queryParams.nickname}"`)
    if (queryParams.area_code) conditions.push(`区号为"${queryParams.area_code}"`)
    if (queryParams.phone) conditions.push(`电话包含"${queryParams.phone}"`)
    if (queryParams.password) conditions.push(`密码包含"${queryParams.password}"`)
    if (queryParams.has_nickname) {
        const nicknameDesc = queryParams.has_nickname === 'yes' ? '存在' : '不存在'
        conditions.push(`昵称${nicknameDesc}`)
    }
    if (queryParams.has_uid) {
        const uidDesc = queryParams.has_uid === 'yes' ? '存在' : '不存在'
        conditions.push(`自定义ID${uidDesc}`)
    }
    if (queryParams.status) {
        const statusItem = dictData.alt_status?.find(item => item.value === queryParams.status)
        conditions.push(`状态为"${statusItem?.name || queryParams.status}"`)
    }
    if (queryParams.group_id) {
        const groupItem = allGroupOptions.value.find(item => item.id.toString() === queryParams.group_id.toString())
        conditions.push(`分组为"${groupItem?.name || queryParams.group_id}"`)
    }
    if (queryParams.operator_id) {
        const operatorItem = allOperatorOptions.value.find(item => item.id.toString() === queryParams.operator_id.toString())
        conditions.push(`客服为"${operatorItem?.name || queryParams.operator_id}"`)
    }
    if (queryParams.proxy_status) {
        const proxyStatusDesc = queryParams.proxy_status === 'none' ? '未设置' : '已设置'
        conditions.push(`代理状态为"${proxyStatusDesc}"`)
    }
    
    const conditionText = conditions.length > 0 ? conditions.join('、') : '所有账号'
    
    // 显示确认对话框
    await feedback.confirm(`即将批量验活账号：\n\n匹配条件：${conditionText}\n\n确认继续？`)

    isCreatingTask.value = true
    
    try {
        // 检查当前是否有运行中的任务
        const checkRes = await apiBatchTaskCheck({
            task_type: 'batch_verify'
        })
        
        console.log('任务检查响应:', checkRes)
        
        // 验证响应数据结构
        if (!checkRes) {
            feedback.msgError('请检查网络连接')
            return
        }
        
        // 检查是否有运行中的任务（API响应直接包含数据，不在data字段中）
        if (checkRes.has_running_task) {
            const runningTask = checkRes.running_task
            const taskInfo = runningTask ? `任务"${runningTask.task_name}"正在执行中（${runningTask.progress_percent}%）` : ''
            feedback.msgWarning(`您已有正在执行的批量验活任务，请前往任务管理页面查看。${taskInfo}`)
            return
        }
        
        // 基于搜索条件创建任务
        const searchParams = { ...queryParams }
        
        console.log('创建任务参数:', {
            task_type: 'batch_verify',
            search_params: searchParams
        })
        
        const result = await apiBatchTaskCreate({
            task_type: 'batch_verify',
            search_params: searchParams
            // 不传递account_ids，让后端根据搜索条件查找匹配的账号
        })
        
        feedback.msgSuccess('批量验活任务创建成功，正在根据搜索条件处理匹配的账号')
        
        // 跳转到任务管理页面
        await router.push('/task-management/batch-verify')
        
    } catch (error: any) {
        feedback.msgError(error.message || '创建任务失败')
    } finally {
        isCreatingTask.value = false
    }
}

onMounted(() => {
    getLists()
    getGroupOptions()
    getOperatorOptions()
})
</script>

<style scoped>
.status-select :deep(.el-select__wrapper) {
    width: 220px !important;
    min-width: 220px !important;
}

.status-select :deep(.el-select__selection) {
    width: 180px !important;
    min-width: 180px !important;
    overflow: visible !important;
}

.status-select :deep(.el-select__selected-item) {
    width: 100% !important;
    min-width: 150px !important;
    overflow: visible !important;
    text-overflow: unset !important;
    white-space: nowrap !important;
}

.status-select :deep(.el-input__wrapper) {
    width: 220px !important;
    min-width: 220px !important;
}

.status-select :deep(.el-input__inner) {
    width: 100% !important;
    min-width: 180px !important;
}
</style>
