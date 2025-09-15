<template>
    <div class="tenant">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="formData" inline>
                <el-form-item class="w-[280px]" label="租户账号">
                    <el-input v-model="formData.account" placeholder="请输入租户账号" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item class="w-[280px]" label="租户昵称">
                    <el-input v-model="formData.name" placeholder="请输入租户昵称" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                    <export-data class="ml-2.5" :fetch-fun="tenantLists" :params="formData" :page-size="pager.size" />
                </el-form-item>
            </el-form>
        </el-card>
        <el-card v-loading="pager.loading" class="mt-4 !border-none" shadow="never">
            <el-button v-perms="['auth.tenant/add']" type="primary" @click="handleAdd">
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
                    <el-table-column label="角色" prop="role_name" min-width="100" show-tooltip-when-overflow />
                    <el-table-column label="上级" prop="parent_name" min-width="120" show-tooltip-when-overflow />
                    <el-table-column label="在线状态" min-width="120">
                        <template #default="{ row }">
                            <OnlineStatusTag 
                                :online="row.online_status"
                                :status-text="row.online_status_text"
                            />
                        </template>
                    </el-table-column>
                    <el-table-column label="端口总数" prop="total_ports" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="primary">{{ row.total_ports || 0 }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="已用端口" prop="used_ports" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="warning">{{ row.used_ports || 0 }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="空闲端口" prop="available_ports" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="success">{{ row.available_ports || 0 }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="过期端口" prop="expired_ports" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="danger">{{ row.expired_ports || 0 }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="创建时间" prop="create_time" min-width="180" />
                    <el-table-column label="状态" min-width="100" v-perms="['auth.tenant/edit']">
                        <template #default="{ row }">
                            <el-switch v-model="row.disable" :active-value="0" :inactive-value="1"
                                @change="changeStatus(row)" />
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" :width="isMobile ? 120 : 280" fixed="right">
                        <template #default="{ row }">
                            <!-- 桌面端：显示所有按钮 -->
                            <div class="hidden md:flex md:gap-1 md:flex-wrap">
                                <el-button v-perms="['auth.tenant/edit']" type="primary" link @click="handleEdit(row)">
                                    编辑
                                </el-button>
                                <el-button v-perms="['package/assign']" type="success" link
                                    @click="handleAssignPackage(row)">
                                    分配套餐
                                </el-button>
                                <el-button v-perms="['package/lists']" type="warning" link
                                    @click="handleViewPackages(row)">
                                    查看套餐
                                </el-button>
                                <el-button v-perms="['auth.tenant/delete']" type="danger" link
                                    @click="handleDelete(row.id)">
                                    删除
                                </el-button>
                            </div>

                            <!-- 移动端：下拉菜单 -->
                            <div class="md:hidden">
                                <el-dropdown @command="(command) => handleDropdownCommand(command, row)">
                                    <el-button type="primary" size="small">
                                        操作
                                        <el-icon class="el-icon--right">
                                            <icon name="el-icon-ArrowDown" />
                                        </el-icon>
                                    </el-button>
                                    <template #dropdown>
                                        <el-dropdown-menu>
                                            <el-dropdown-item v-perms="['auth.tenant/edit']" command="edit">
                                                <el-icon>
                                                    <icon name="el-icon-Edit" />
                                                </el-icon>
                                                编辑
                                            </el-dropdown-item>
                                            <el-dropdown-item v-perms="['package/assign']" command="assign">
                                                <el-icon>
                                                    <icon name="el-icon-Plus" />
                                                </el-icon>
                                                分配套餐
                                            </el-dropdown-item>
                                            <el-dropdown-item v-perms="['package/lists']" command="view">
                                                <el-icon>
                                                    <icon name="el-icon-View" />
                                                </el-icon>
                                                查看套餐
                                            </el-dropdown-item>
                                            <el-dropdown-item v-perms="['auth.tenant/delete']" command="delete" divided>
                                                <el-icon>
                                                    <icon name="el-icon-Delete" />
                                                </el-icon>
                                                删除
                                            </el-dropdown-item>
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
        <edit-popup v-if="showEdit" ref="editRef" @success="getLists" @close="showEdit = false" />
        <assign-package-popup v-if="showAssignPackage" ref="assignPackageRef" @success="getLists"
            @close="showAssignPackage = false" />
    </div>
</template>

<script lang="ts" setup name="tenant">
import { tenantDelete, tenantEdit, tenantLists } from '@/api/tenant'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import { useRouter } from 'vue-router'

import EditPopup from './edit.vue'
import AssignPackagePopup from './assign-package.vue'
import OnlineStatusTag from '@/components/OnlineStatusTag.vue'

const router = useRouter()
const editRef = shallowRef<InstanceType<typeof EditPopup>>()
const assignPackageRef = shallowRef<InstanceType<typeof AssignPackagePopup>>()
// 表单数据
const formData = reactive({
    account: '',
    name: ''
})

const showEdit = ref(false)
const showAssignPackage = ref(false)

// 检测是否为移动端
const isMobile = ref(false)
const checkMobile = () => {
    isMobile.value = window.innerWidth < 768 // md断点是768px
}

const { pager, getLists, resetParams, resetPage } = usePaging({
    fetchFun: tenantLists,
    params: formData
})

const changeStatus = (data: any) => {
    tenantEdit({
        id: data.id,
        name: data.name,
        account: data.account,
        avatar: data.avatar,
        disable: data.disable
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

const handleAssignPackage = async (data: any) => {
    showAssignPackage.value = true
    await nextTick()
    assignPackageRef.value?.open('add')
    assignPackageRef.value?.setTenantData(data)
}

// 查看套餐
const handleViewPackages = (data: any) => {
    // 跳转到套餐分配页面，并传递租户ID作为查询参数
    router.push({
        path: '/package/assign',
        query: {
            tenant_id: data.id,
            tenant_name: data.name,
            auto_search: '1'  // 标记自动搜索
        }
    })
}

// 处理下拉菜单命令
const handleDropdownCommand = (command: string, row: any) => {
    switch (command) {
        case 'edit':
            handleEdit(row)
            break
        case 'assign':
            handleAssignPackage(row)
            break
        case 'view':
            handleViewPackages(row)
            break
        case 'delete':
            handleDelete(row.id)
            break
    }
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定要删除？此操作不可恢复！')
    await tenantDelete({ id })
    getLists()
}

onMounted(() => {
    getLists()
    checkMobile()
    window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile)
})
</script>
