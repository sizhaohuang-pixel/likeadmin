<template>
    <div class="nickname-repository">
        <!-- 统计卡片区域 -->
        <div class="stats-cards mb-4">
            <el-row :gutter="{ xs: 20, sm: 24, md: 30 }">
                <el-col :xs="12" :sm="6" :md="6" class="stats-col">
                    <el-card class="stats-card">
                        <div class="stats-content">
                            <div class="stats-icon">
                                <el-icon size="24" color="#409EFF"><folder /></el-icon>
                            </div>
                            <div class="stats-info">
                                <div class="stats-value">{{ statistics.total_groups }}</div>
                                <div class="stats-label">分组总数</div>
                            </div>
                        </div>
                    </el-card>
                </el-col>
                <el-col :xs="12" :sm="6" :md="6" class="stats-col">
                    <el-card class="stats-card">
                        <div class="stats-content">
                            <div class="stats-icon">
                                <el-icon size="24" color="#67C23A"><collection /></el-icon>
                            </div>
                            <div class="stats-info">
                                <div class="stats-value">{{ statistics.total_nicknames }}</div>
                                <div class="stats-label">昵称总数</div>
                            </div>
                        </div>
                    </el-card>
                </el-col>
                <el-col :xs="12" :sm="6" :md="6" class="stats-col">
                    <el-card class="stats-card">
                        <div class="stats-content">
                            <div class="stats-icon">
                                <el-icon size="24" color="#E6A23C"><star /></el-icon>
                            </div>
                            <div class="stats-info">
                                <div class="stats-value">{{ statistics.available_nicknames }}</div>
                                <div class="stats-label">可用昵称</div>
                            </div>
                        </div>
                    </el-card>
                </el-col>
                <el-col :xs="12" :sm="6" :md="6" class="stats-col">
                    <el-card class="stats-card">
                        <div class="stats-content">
                            <div class="stats-icon">
                                <el-icon size="24" color="#F56C6C"><pie-chart /></el-icon>
                            </div>
                            <div class="stats-info">
                                <div class="stats-value">{{ statistics.usage_rate }}%</div>
                                <div class="stats-label">使用率</div>
                            </div>
                        </div>
                    </el-card>
                </el-col>
            </el-row>
        </div>

        <!-- 搜索区域 -->
        <el-card class="!border-none mb-4" shadow="never">
            <el-form :model="searchForm" :inline="true" class="search-form">
                <el-form-item label="分组名称">
                    <el-input 
                        v-model="searchForm.groupName" 
                        placeholder="请输入分组名称" 
                        clearable
                        @keyup.enter="handleSearch"
                        @clear="handleSearch"
                        class="search-input"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">搜索</el-button>
                    <el-button @click="handleResetSearch">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 操作按钮区域 -->
        <el-card class="!border-none mb-4" shadow="never">
            <div class="action-area">
                <div class="action-buttons">
                    <el-button type="success" @click="handleRefresh">
                        <template #icon>
                            <icon name="el-icon-Refresh" />
                        </template>
                        刷新数据
                    </el-button>
                    <el-button type="primary" @click="handleAddGroup">
                        <template #icon>
                            <icon name="el-icon-Plus" />
                        </template>
                        新增分组
                    </el-button>
                </div>
                <div class="update-time">
                    最后更新：{{ lastUpdateTime }}
                </div>
            </div>
        </el-card>

        <!-- 分组列表卡片区域 -->
        <div class="groups-grid">
            <el-row :gutter="{ xs: 20, sm: 24, md: 30 }" v-loading="loading">
                <el-col :xs="24" :sm="12" :md="8" :lg="8" v-for="group in filteredGroupList" :key="group.group_name" class="group-col">
                    <el-card class="group-card">
                        <template #header>
                            <div class="group-header">
                                <div class="group-name">
                                    <el-icon><folder /></el-icon>
                                    <span>{{ group.group_name }}</span>
                                </div>
                                <div class="group-actions">
                                    <el-button type="primary" link size="small" @click="handleViewDetail(group.group_name)">
                                        查看明细
                                    </el-button>
                                    <el-button type="success" link size="small" @click="handleImportNickname(group.group_name)">
                                        导入昵称
                                    </el-button>
                                    <el-dropdown trigger="click" @command="handleGroupCommand">
                                        <el-button type="primary" link size="small">
                                            更多操作
                                            <el-icon class="ml-1"><arrow-down /></el-icon>
                                        </el-button>
                                        <template #dropdown>
                                            <el-dropdown-menu>
                                                <el-dropdown-item :command="`edit:${group.group_name}`">
                                                    修改分组
                                                </el-dropdown-item>
                                                <el-dropdown-item :command="`export:${group.group_name}`" :disabled="group.available_count === 0">
                                                    剩余数据导出
                                                </el-dropdown-item>
                                                <el-dropdown-item :command="`delete:${group.group_name}`" divided class="text-red-600">
                                                    删除分组
                                                </el-dropdown-item>
                                            </el-dropdown-menu>
                                        </template>
                                    </el-dropdown>
                                </div>
                            </div>
                        </template>

                        <div class="group-content">
                            <div class="group-stats">
                                <div class="stat-item">
                                    <span class="stat-label">剩余数量</span>
                                    <span class="stat-value available">{{ group.available_count }}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">总数量</span>
                                    <span class="stat-value total">{{ group.total_count }}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">使用率</span>
                                    <span class="stat-value usage">
                                        {{ group.total_count > 0 ? Math.round((group.total_count - group.available_count) / group.total_count * 100) : 0 }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </el-card>
                </el-col>

                <!-- 空状态 -->
                <el-col :span="24" v-if="!loading && filteredGroupList.length === 0">
                    <el-empty :description="searchForm.groupName ? '未找到匹配的分组' : '暂无分组数据'">
                        <el-button v-if="!searchForm.groupName" type="primary" @click="handleAddGroup">创建第一个分组</el-button>
                        <el-button v-else @click="handleResetSearch">清空搜索</el-button>
                    </el-empty>
                </el-col>
            </el-row>
        </div>

        <!-- 新增/编辑分组对话框 -->
        <el-dialog 
            v-model="groupDialogVisible" 
            :title="groupDialogTitle" 
            :width="dialogWidth"
            :close-on-click-modal="false"
        >
            <el-form :model="groupForm" :rules="groupRules" ref="groupFormRef" label-width="100px">
                <el-form-item label="分组名称" prop="group_name" v-if="groupDialogMode === 'add'">
                    <el-input v-model="groupForm.group_name" placeholder="请输入分组名称" maxlength="100" show-word-limit />
                </el-form-item>
                <el-form-item label="原分组名称" v-if="groupDialogMode === 'edit'">
                    <el-input v-model="groupForm.old_group_name" disabled />
                </el-form-item>
                <el-form-item label="新分组名称" prop="new_group_name" v-if="groupDialogMode === 'edit'">
                    <el-input v-model="groupForm.new_group_name" placeholder="请输入新的分组名称" maxlength="100" show-word-limit />
                </el-form-item>
            </el-form>
            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="groupDialogVisible = false">取消</el-button>
                    <el-button type="primary" @click="handleGroupSubmit" :loading="submitting">确定</el-button>
                </div>
            </template>
        </el-dialog>

        <!-- 导入昵称组件 -->
        <import-nickname-popup 
            v-model="importDialogVisible" 
            :group-name="currentGroupName"
            @success="handleImportSuccess" 
        />

        <!-- 查看明细弹窗 -->
        <nickname-detail-dialog 
            v-model="detailDialogVisible" 
            :group-name="currentGroupName"
        />
    </div>
</template>

<script lang="ts" setup name="nicknameRepository">
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
    Folder, 
    Collection, 
    Star, 
    PieChart,
    ArrowDown
} from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
import { 
    apiNicknameRepositoryGroups,
    apiNicknameRepositoryAdd,
    apiNicknameRepositoryEdit,
    apiNicknameRepositoryDelete,
    apiNicknameRepositoryExport,
    apiNicknameRepositoryStatistics
} from '@/api/nickname_repository'
import type { NicknameGroup, NicknameStatistics } from '@/typings/nickname-repository'
import feedback from '@/utils/feedback'
import { useRouter } from 'vue-router'
import ImportNicknamePopup from './import.vue'
import NicknameDetailDialog from './detail-dialog.vue'

const router = useRouter()

// 数据状态
const loading = ref(false)
const submitting = ref(false)
const groupList = ref<NicknameGroup[]>([])
const filteredGroupList = ref<NicknameGroup[]>([])  // 过滤后的分组列表
const statistics = ref<NicknameStatistics>({
    total_groups: 0,
    total_nicknames: 0,
    available_nicknames: 0,
    used_nicknames: 0,
    usage_rate: 0
})

// 对话框状态
const groupDialogVisible = ref(false)
const groupDialogMode = ref<'add' | 'edit'>('add')
const importDialogVisible = ref(false)
const detailDialogVisible = ref(false)
const currentGroupName = ref('')

// 表单相关
const groupFormRef = ref<FormInstance>()
const groupForm = reactive({
    group_name: '',
    old_group_name: '',
    new_group_name: ''
})

// 搜索表单
const searchForm = reactive({
    groupName: ''
})

const groupRules = reactive<FormRules>({
    group_name: [
        { required: true, message: '请输入分组名称', trigger: 'blur' },
        { min: 1, max: 100, message: '分组名称长度必须在1-100个字符之间', trigger: 'blur' }
    ],
    new_group_name: [
        { required: true, message: '请输入新的分组名称', trigger: 'blur' },
        { min: 1, max: 100, message: '分组名称长度必须在1-100个字符之间', trigger: 'blur' }
    ]
})

// 计算属性
const groupDialogTitle = computed(() => {
    return groupDialogMode.value === 'add' ? '新增分组' : '编辑分组'
})

const lastUpdateTime = computed(() => {
    return new Date().toLocaleString()
})

// 响应式对话框宽度
const dialogWidth = computed(() => {
    if (typeof window !== 'undefined') {
        return window.innerWidth < 768 ? '90%' : '500px'
    }
    return '500px'
})

// 方法
const getGroupList = async () => {
    try {
        loading.value = true
        const data = await apiNicknameRepositoryGroups()
        groupList.value = data.lists || []
        filterGroups() // 应用搜索过滤
    } catch (error: any) {
        ElMessage.error(error.message || '获取分组列表失败')
    } finally {
        loading.value = false
    }
}

const getStatistics = async () => {
    try {
        const data = await apiNicknameRepositoryStatistics()
        statistics.value = data
    } catch (error: any) {
        console.error('获取统计信息失败:', error)
    }
}

const handleRefresh = async () => {
    await Promise.all([getGroupList(), getStatistics()])
    ElMessage.success('数据刷新成功')
}

// 搜索相关方法
const filterGroups = () => {
    if (!searchForm.groupName) {
        filteredGroupList.value = groupList.value
    } else {
        filteredGroupList.value = groupList.value.filter(group => 
            group.group_name.toLowerCase().includes(searchForm.groupName.toLowerCase())
        )
    }
}

const handleSearch = () => {
    filterGroups()
}

const handleResetSearch = () => {
    searchForm.groupName = ''
    filterGroups()
}

const handleAddGroup = () => {
    groupDialogMode.value = 'add'
    groupForm.group_name = ''
    groupDialogVisible.value = true
}

const handleGroupCommand = (command: string) => {
    const [action, groupName] = command.split(':')
    
    switch (action) {
        case 'detail':
            handleViewDetail(groupName)
            break
        case 'edit':
            handleEditGroup(groupName)
            break
        case 'export':
            handleExportNickname(groupName)
            break
        case 'delete':
            handleDeleteGroup(groupName)
            break
    }
}

const handleViewDetail = (groupName: string) => {
    currentGroupName.value = groupName
    detailDialogVisible.value = true
}

const handleEditGroup = (groupName: string) => {
    groupDialogMode.value = 'edit'
    groupForm.old_group_name = groupName
    groupForm.new_group_name = groupName
    groupDialogVisible.value = true
}

const handleImportNickname = (groupName: string) => {
    currentGroupName.value = groupName
    importDialogVisible.value = true
}

const handleExportNickname = async (groupName: string) => {
    try {
        const response = await apiNicknameRepositoryExport({ group_name: groupName })
        
        // 检查响应是否为 Blob
        if (!(response instanceof Blob)) {
            throw new Error('导出数据格式错误')
        }
        
        const url = window.URL.createObjectURL(response)
        const link = document.createElement('a')
        link.href = url
        link.download = `${groupName}_昵称导出_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.txt`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
        ElMessage.success('导出成功')
    } catch (error: any) {
        console.error('导出错误:', error)
        ElMessage.error(error.message || '导出失败')
    }
}

const handleDeleteGroup = async (groupName: string) => {
    try {
        await ElMessageBox.confirm(
            `确定要删除分组"${groupName}"吗？删除后该分组下的所有昵称都将被删除，此操作不可恢复。`,
            '确认删除',
            {
                confirmButtonText: '确定删除',
                cancelButtonText: '取消',
                type: 'warning'
            }
        )
        
        await apiNicknameRepositoryDelete({ group_name: groupName })
        ElMessage.success('删除成功')
        await handleRefresh()
    } catch (error: any) {
        if (error !== 'cancel') {
            ElMessage.error(error.message || '删除失败')
        }
    }
}

const handleGroupSubmit = async () => {
    if (!groupFormRef.value) return
    
    try {
        await groupFormRef.value.validate()
        submitting.value = true
        
        if (groupDialogMode.value === 'add') {
            await apiNicknameRepositoryAdd({ group_name: groupForm.group_name })
            ElMessage.success('添加成功')
        } else {
            await apiNicknameRepositoryEdit({
                old_group_name: groupForm.old_group_name,
                new_group_name: groupForm.new_group_name
            })
            ElMessage.success('编辑成功')
        }
        
        groupDialogVisible.value = false
        await handleRefresh()
    } catch (error: any) {
        ElMessage.error(error.message || '操作失败')
    } finally {
        submitting.value = false
    }
}

const handleImportSuccess = () => {
    importDialogVisible.value = false
    handleRefresh()
}

onMounted(() => {
    handleRefresh()
})
</script>

<style scoped>
.nickname-repository {
    padding: 20px;
}

/* 移动端适配 */
@media (max-width: 768px) {
    .nickname-repository {
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .nickname-repository {
        padding: 10px;
    }
}

/* 统计卡片 */
.stats-col {
    margin-bottom: 20px;
    padding: 0 8px;
}

@media (max-width: 768px) {
    .stats-col {
        margin-bottom: 16px;
        padding: 0 6px;
    }
}

@media (max-width: 480px) {
    .stats-col {
        margin-bottom: 12px;
        padding: 0 4px;
    }
}

.stats-card {
    height: 100px;
    margin: 4px;
}

@media (max-width: 768px) {
    .stats-card {
        height: 90px;
    }
}

.stats-content {
    display: flex;
    align-items: center;
    height: 100%;
}

.stats-icon {
    margin-right: 15px;
}

@media (max-width: 480px) {
    .stats-icon {
        margin-right: 10px;
    }
}

.stats-info {
    flex: 1;
}

.stats-value {
    font-size: 24px;
    font-weight: bold;
    color: #303133;
    line-height: 1;
}

@media (max-width: 768px) {
    .stats-value {
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .stats-value {
        font-size: 18px;
    }
}

.stats-label {
    font-size: 14px;
    color: #909399;
    margin-top: 5px;
}

@media (max-width: 480px) {
    .stats-label {
        font-size: 12px;
    }
}

/* 搜索表单 */
.search-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

@media (max-width: 768px) {
    .search-form {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .search-form :deep(.el-form-item) {
        margin-bottom: 0;
        margin-right: 0;
    }
}

.search-input {
    min-width: 200px;
}

@media (max-width: 480px) {
    .search-input {
        min-width: auto;
    }
}

/* 操作按钮区域 */
.action-area {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

@media (max-width: 768px) {
    .action-area {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}

.action-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

@media (max-width: 480px) {
    .action-buttons {
        gap: 8px;
        width: 100%;
    }
    
    .action-buttons .el-button {
        flex: 1;
        min-width: auto;
    }
}

.update-time {
    font-size: 14px;
    color: #909399;
}

@media (max-width: 768px) {
    .update-time {
        font-size: 12px;
        align-self: flex-end;
    }
}

/* 分组卡片 */
.group-col {
    margin-bottom: 24px;
    padding: 0 8px;
}

@media (max-width: 768px) {
    .group-col {
        margin-bottom: 20px;
        padding: 0 6px;
    }
}

@media (max-width: 480px) {
    .group-col {
        margin-bottom: 16px;
        padding: 0 4px;
    }
}

.group-card {
    height: auto;
    min-height: 160px;
    border-radius: 8px;
    transition: box-shadow 0.3s;
    margin: 4px;
}

.group-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

@media (max-width: 480px) {
    .group-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}

.group-name {
    display: flex;
    align-items: center;
    font-weight: 500;
    color: #303133;
    flex: 1;
    min-width: 0;
}

.group-name .el-icon {
    margin-right: 8px;
    color: #409EFF;
    flex-shrink: 0;
}

.group-name span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.group-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

@media (max-width: 480px) {
    .group-actions {
        width: 100%;
        justify-content: flex-end;
    }
}

.group-content {
    padding-top: 8px;
}

.group-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
}

.stat-label {
    color: #909399;
    font-size: 14px;
}

@media (max-width: 480px) {
    .stat-label {
        font-size: 13px;
    }
}

.stat-value {
    font-weight: 500;
    font-size: 16px;
}

@media (max-width: 480px) {
    .stat-value {
        font-size: 15px;
    }
}

.stat-value.available {
    color: #67C23A;
}

.stat-value.total {
    color: #409EFF;
}

.stat-value.usage {
    color: #E6A23C;
}

/* 空状态和其他组件 */
.groups-grid {
    min-height: 200px;
}

@media (max-width: 768px) {
    .groups-grid {
        min-height: 150px;
    }
}

/* Element Plus 组件覆盖 */
:deep(.el-card__header) {
    padding: 16px 20px;
}

@media (max-width: 480px) {
    :deep(.el-card__header) {
        padding: 12px 16px;
    }
}

:deep(.el-card__body) {
    padding: 16px 20px;
}

@media (max-width: 480px) {
    :deep(.el-card__body) {
        padding: 12px 16px;
    }
}

/* 下拉菜单样式优化 */
:deep(.el-dropdown-menu__item) {
    padding: 8px 16px;
    font-size: 14px;
}

@media (max-width: 480px) {
    :deep(.el-dropdown-menu__item) {
        padding: 10px 16px;
        font-size: 15px;
    }
}
</style>