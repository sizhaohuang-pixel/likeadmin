<template>
    <div class="agent">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="formData" inline>
                <el-form-item class="w-[280px]" label="代理商账号">
                    <el-input
                        v-model="formData.account"
                        placeholder="请输入代理商账号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[280px]" label="代理商昵称">
                    <el-input
                        v-model="formData.name"
                        placeholder="请输入代理商昵称"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                    <export-data
                        class="ml-2.5"
                        :fetch-fun="agentLists"
                        :params="formData"
                        :page-size="pager.size"
                    />
                </el-form-item>
            </el-form>
        </el-card>
        <el-card v-loading="pager.loading" class="mt-4 !border-none" shadow="never">
            <el-button v-perms="['auth.agent/add']" type="primary" @click="handleAdd">
                <template #icon>
                    <icon name="el-icon-Plus" />
                </template>
                新增
            </el-button>
            <div class="mt-4">
                <el-table :data="pager.lists" size="large">
                    <el-table-column label="ID" prop="id" min-width="60" />
                    <el-table-column label="头像" min-width="100">
                        <template #default="{ row }">
                            <el-avatar :size="50" :src="row.avatar"></el-avatar>
                        </template>
                    </el-table-column>
                    <el-table-column label="账号" prop="account" min-width="120" />
                    <el-table-column label="昵称" prop="name" min-width="120" />
                    <el-table-column
                        label="角色"
                        prop="role_name"
                        min-width="100"
                        show-tooltip-when-overflow
                    />
                    <el-table-column
                        label="上级"
                        prop="parent_name"
                        min-width="120"
                        show-tooltip-when-overflow
                    />
                    <el-table-column label="在线状态" min-width="120">
                        <template #default="{ row }">
                            <OnlineStatusTag 
                                :online="row.online_status"
                                :status-text="row.online_status_text"
                            />
                        </template>
                    </el-table-column>
                    <el-table-column label="创建时间" prop="create_time" min-width="180" />
                    <el-table-column label="最近登录时间" prop="login_time" min-width="180" />
                    <el-table-column label="最近登录IP" prop="login_ip" min-width="120" />
                    <el-table-column label="多点登录" min-width="100">
                        <template #default="{ row }">
                            <dict-value :options="multiLoginOptions" :value="row.multipoint_login" />
                        </template>
                    </el-table-column>
                    <el-table-column label="状态" min-width="100" v-perms="['auth.agent/edit']">
                        <template #default="{ row }">
                            <el-switch
                                v-model="row.disable"
                                :active-value="0"
                                :inactive-value="1"
                                @change="changeStatus(row)"
                            />
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="120" fixed="right">
                        <template #default="{ row }">
                            <el-button
                                v-perms="['auth.agent/edit']"
                                type="primary"
                                link
                                @click="handleEdit(row)"
                            >
                                编辑
                            </el-button>
                            <el-button
                                v-perms="['auth.agent/delete']"
                                type="danger"
                                link
                                @click="handleDelete(row.id)"
                            >
                                删除
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
            <div class="flex mt-4 justify-end">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
        <edit-popup v-if="showEdit" ref="editRef" @success="getLists" @close="showEdit = false" />
    </div>
</template>

<script lang="ts" setup name="agent">
import { agentDelete, agentEdit, agentLists } from '@/api/agent'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

import EditPopup from './edit.vue'
import OnlineStatusTag from '@/components/OnlineStatusTag.vue'

const editRef = shallowRef<InstanceType<typeof EditPopup>>()
// 表单数据
const formData = reactive({
    account: '',
    name: ''
})

// 多点登录选项
const multiLoginOptions = [
    { label: '不支持', value: 0 },
    { label: '支持', value: 1 }
]

const showEdit = ref(false)
const { pager, getLists, resetParams, resetPage } = usePaging({
    fetchFun: agentLists,
    params: formData
})

const changeStatus = (data: any) => {
    agentEdit({
        id: data.id,
        name: data.name,
        account: data.account,
        avatar: data.avatar,
        disable: data.disable,
        multipoint_login: data.multipoint_login
    }).finally(() => {
        getLists()
    })
}

const handleAdd = async () => {
    showEdit.value = true
    await nextTick()
    editRef.value?.open('add')
}

const handleEdit = async (data: any) => {
    showEdit.value = true
    await nextTick()
    editRef.value?.open('edit')
    editRef.value?.setFormData(data)
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定要删除？')
    await agentDelete({ id })
    getLists()
}

onMounted(() => {
    getLists()
})
</script>
