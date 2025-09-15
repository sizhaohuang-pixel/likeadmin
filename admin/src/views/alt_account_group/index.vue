<template>
    <div class="alt-account-group">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" inline>
                <el-form-item class="w-[180px]" label="分组名称">
                    <el-input v-model="queryParams.name" placeholder="请输入分组名称" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item class="w-[180px]" label="分组描述">
                    <el-input v-model="queryParams.description" placeholder="请输入分组描述" clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card v-loading="pager.loading" class="mt-4 !border-none" shadow="never">
            <el-button v-perms="['alt_account_group/add']" type="primary" @click="handleAdd">
                <template #icon>
                    <icon name="el-icon-Plus" />
                </template>
                新增分组
            </el-button>
            <div class="mt-4">
                <el-table :data="pager.lists" size="large">
                    <el-table-column label="ID" prop="id" min-width="60" />
                    <el-table-column label="分组名称" prop="name" min-width="150" show-overflow-tooltip />
                    <el-table-column label="分组描述" prop="description" min-width="200" show-overflow-tooltip />
                    <el-table-column label="账号数量" prop="alt_account_count" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="info">{{ row.alt_account_count || 0 }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="创建时间" prop="create_time" min-width="180" />
                    <el-table-column label="操作" width="160" fixed="right">
                        <template #default="{ row }">
                            <el-button v-perms="['alt_account_group/edit']" type="primary" link
                                @click="handleEdit(row)">
                                编辑
                            </el-button>
                            <el-button v-perms="['alt_account_group/delete']" type="danger" link
                                @click="handleDelete(row.id, row.alt_account_count)">
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

<script lang="ts" setup name="altAccountGroupLists">
import { usePaging } from '@/hooks/usePaging'
import { apiAltAccountGroupLists, apiAltAccountGroupDelete } from '@/api/alt_account_group'
import feedback from '@/utils/feedback'

import EditPopup from './edit.vue'

const editRef = shallowRef<InstanceType<typeof EditPopup>>()
// 是否显示编辑框
const showEdit = ref(false)

// 查询条件
const queryParams = reactive({
    name: '',
    description: ''
})

// 分页相关
const { pager, getLists, resetParams, resetPage } = usePaging({
    fetchFun: apiAltAccountGroupLists,
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
const handleDelete = async (id: number, accountCount: number) => {
    let confirmMessage = '确定要删除该分组？'
    if (accountCount > 0) {
        confirmMessage = `该分组下有 ${accountCount} 个账号，删除后这些账号将变为"未分组"状态，确定要删除？`
    }

    await feedback.confirm(confirmMessage)
    await apiAltAccountGroupDelete({ id })
    feedback.msgSuccess('删除成功')
    getLists()
}

onMounted(() => {
    getLists()
})
</script>
