<template>
    <div class="import-account-popup">
        <el-dialog
            v-model="showEdit"
            title="批量导入账号"
            :width="700"
            :close-on-click-modal="false"
            :close-on-press-escape="false"
            @close="handleClose"
        >
            <div class="import-content">
                <!-- 文件上传区域 -->
                <div class="upload-section">
                    <el-upload
                        ref="uploadRef"
                        class="upload-demo compact"
                        drag
                        :auto-upload="false"
                        :show-file-list="false"
                        accept=".txt"
                        :before-upload="handleBeforeUpload"
                        :on-change="handleFileChange"
                        :limit="1"
                        :key="uploadKey"
                    >
                        <el-icon class="el-icon--upload"><upload-filled /></el-icon>
                        <div class="el-upload__text">
                            将文件拖到此处，或<em>点击上传</em>
                        </div>
                        <template #tip>
                            <div class="el-upload__tip">
                                仅支持.txt格式文件，最大500KB
                            </div>
                        </template>
                    </el-upload>
                </div>

                <!-- 文件信息显示 -->
                <div v-if="fileInfo.name" class="file-info">
                    <el-card>
                        <div class="file-details">
                            <div class="file-name">
                                <el-icon><document /></el-icon>
                                {{ fileInfo.name }} ({{ formatFileSize(fileInfo.size) }})
                            </div>
                            <el-button 
                                type="danger" 
                                size="small" 
                                @click="clearFile"
                            >
                                移除文件
                            </el-button>
                        </div>
                    </el-card>
                </div>

                <!-- 数据预览区域 -->
                <div v-if="previewData.totalLines > 0" class="preview-section">
                    <el-card>
                        <template #header>
                            <div class="card-header">
                                <span>数据预览</span>
                            </div>
                        </template>
                        
                        <!-- 统计信息 -->
                        <div class="preview-stats">
                            <el-row :gutter="20">
                                <el-col :span="6">
                                    <div class="stat-item">
                                        <div class="stat-value">{{ previewData.totalLines }}</div>
                                        <div class="stat-label">总行数</div>
                                    </div>
                                </el-col>
                                <el-col :span="6">
                                    <div class="stat-item">
                                        <div class="stat-value">{{ previewData.validLines }}</div>
                                        <div class="stat-label">有效数据</div>
                                    </div>
                                </el-col>
                                <el-col :span="6">
                                    <div class="stat-item">
                                        <div class="stat-value">{{ previewData.skippedLines }}</div>
                                        <div class="stat-label">跳过行数</div>
                                    </div>
                                </el-col>
                                <el-col :span="6">
                                    <div class="stat-item">
                                        <div class="stat-value text-red-500">{{ previewData.errorLines }}</div>
                                        <div class="stat-label">格式错误</div>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>

                        <!-- 预览表格 -->
                        <div v-if="previewData.sampleData.length > 0" class="preview-table">
                            <el-table :data="previewData.sampleData" size="small" max-height="120">
                                <el-table-column prop="lineNumber" label="行号" width="80" />
                                <el-table-column prop="mid" label="MID" width="200" show-overflow-tooltip />
                                <el-table-column prop="platform" label="平台" width="80">
                                    <template #default="{ row }">
                                        <el-tag v-if="row.platform" :type="row.platform === 'ANDROID' ? 'success' : 'primary'" size="small">
                                            {{ row.platform }}
                                        </el-tag>
                                        <el-tag v-else type="danger" size="small">解析失败</el-tag>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="status" label="状态" width="100">
                                    <template #default="{ row }">
                                        <el-tag v-if="row.isValid" type="success" size="small">有效</el-tag>
                                        <el-tag v-else type="danger" size="small">错误</el-tag>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="error" label="错误信息" show-overflow-tooltip />
                            </el-table>
                            <div v-if="previewData.validLines > 10" class="preview-tip">
                                仅显示前10行数据预览
                            </div>
                        </div>
                    </el-card>
                </div>

                <!-- 分组选择 -->
                <div class="group-section">
                    <el-card>
                        <template #header>
                            <div class="card-header">
                                <span>目标分组</span>
                                <el-button type="primary" size="small" @click="handleAddGroup">
                                    新增分组
                                </el-button>
                            </div>
                        </template>
                        
                        <el-select
                            v-model="selectedGroupId"
                            placeholder="请选择目标分组（必选）"
                            filterable
                            style="width: 100%"
                        >
                            <el-option
                                v-for="group in groupList"
                                :key="group.id"
                                :label="group.name"
                                :value="group.id"
                            />
                        </el-select>
                    </el-card>
                </div>

                <!-- 导入进度 -->
                <div v-if="importing" class="progress-section">
                    <el-card>
                        <div class="progress-content">
                            <div class="progress-text">正在导入账号数据...</div>
                            <el-progress :percentage="importProgress" :stroke-width="8" />
                        </div>
                    </el-card>
                </div>
            </div>

            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="handleClose" @dblclick="forceClose" :disabled="false">
                        {{ importing ? '取消(双击强制关闭)' : '取消' }}
                    </el-button>
                    <el-button 
                        type="primary" 
                        @click="handleImport" 
                        :disabled="!canImport || importing"
                        :loading="importing"
                    >
                        {{ importing ? '导入中...' : '开始导入' }}
                    </el-button>
                </div>
            </template>
        </el-dialog>

        <!-- 分组编辑弹窗 -->
        <AltAccountGroupEdit
            v-if="showGroupEdit"
            ref="groupEditRef"
            @success="handleGroupSuccess"
            @close="showGroupEdit = false"
        />

        <!-- 导入结果弹窗 -->
        <ImportResultDialog
            v-model="showResult"
            :result="importResult"
            @close="handleResultClose"
        />
    </div>
</template>

<script lang="ts" setup name="ImportAccountPopup">
import { ref, reactive, computed, watch, shallowRef, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import { UploadFilled, Document } from '@element-plus/icons-vue'
import { apiAltAccountBatchImport } from '@/api/alt_account'
import { apiAltAccountGroupLists } from '@/api/alt_account_group'
import AltAccountGroupEdit from '../alt_account_group/edit.vue'
import ImportResultDialog from './ImportResultDialog.vue'

// 组件属性
const props = defineProps<{
    modelValue: boolean
}>()

const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'success': []
}>()

// 响应式数据
const showEdit = ref(false)
const showGroupEdit = ref(false)
const showResult = ref(false)
const uploadRef = ref()
const groupEditRef = shallowRef()
const importing = ref(false)
const importProgress = ref(0)
const selectedGroupId = ref<number | null>(null)

// 文件信息
const fileInfo = reactive({
    name: '',
    size: 0,
    content: ''
})

// 预览数据
const previewData = reactive({
    totalLines: 0,
    validLines: 0,
    skippedLines: 0,
    errorLines: 0,
    sampleData: [] as any[]
})

// 分组列表
const groupList = ref<any[]>([])

// 导入结果
const importResult = ref<any>({})

// 上传组件key，用于强制重新渲染
const uploadKey = ref(0)

// 计算属性
const canImport = computed(() => {
    const result = fileInfo.content &&
           previewData.validLines > 0 &&
           previewData.errorLines === 0 &&
           selectedGroupId.value &&
           selectedGroupId.value > 0

    console.log('canImport 检查:', {
        hasContent: !!fileInfo.content,
        validLines: previewData.validLines,
        errorLines: previewData.errorLines,
        groupId: selectedGroupId.value,
        result
    })

    return result
})

// 监听显示状态
watch(() => props.modelValue, (val) => {
    console.log('ImportAccountPopup modelValue changed:', val)
    showEdit.value = val
    if (val) {
        console.log('开始获取分组列表和重置数据')
        getGroupList()
        resetData()
    }
})

watch(showEdit, (val) => {
    emit('update:modelValue', val)
})

// 重置数据
const resetData = () => {
    console.log('重置数据开始')
    fileInfo.name = ''
    fileInfo.size = 0
    fileInfo.content = ''
    previewData.totalLines = 0
    previewData.validLines = 0
    previewData.skippedLines = 0
    previewData.errorLines = 0
    previewData.sampleData = []
    selectedGroupId.value = null
    importing.value = false
    importProgress.value = 0
    showResult.value = false
    importResult.value = {}

    // 强制重新渲染上传组件
    uploadKey.value++

    console.log('重置数据完成')
}

// 获取分组列表
const getGroupList = async () => {
    try {
        const data = await apiAltAccountGroupLists({})
        groupList.value = data.lists || []
    } catch (error) {
        console.error('获取分组列表失败:', error)
    }
}

// 文件上传前验证
const handleBeforeUpload = (file: File) => {
    // 验证文件类型
    if (!file.name.toLowerCase().endsWith('.txt')) {
        ElMessage.error('仅支持.txt格式文件')
        return false
    }

    // 验证文件大小 (500KB)
    if (file.size > 500 * 1024) {
        ElMessage.error('文件大小不能超过500KB')
        return false
    }

    return true
}

// 文件选择处理
const handleFileChange = (file: any) => {
    console.log('文件选择处理:', file)
    if (!file.raw) {
        console.log('没有文件内容')
        return
    }

    fileInfo.name = file.name
    fileInfo.size = file.raw.size
    console.log('文件信息:', fileInfo)

    // 读取文件内容
    const reader = new FileReader()
    reader.onload = (e) => {
        fileInfo.content = e.target?.result as string
        console.log('文件内容读取完成，长度:', fileInfo.content.length)
        parseFileContent()
    }
    reader.readAsText(file.raw, 'UTF-8')
}

// 解析文件内容
const parseFileContent = () => {
    if (!fileInfo.content) return
    
    const lines = fileInfo.content.split('\n')
    previewData.totalLines = lines.length
    previewData.validLines = 0
    previewData.skippedLines = 0
    previewData.errorLines = 0
    previewData.sampleData = []
    
    let sampleCount = 0
    
    lines.forEach((line, index) => {
        const lineNumber = index + 1
        const trimmedLine = line.trim()
        
        // 跳过空行和注释行
        if (!trimmedLine || trimmedLine.startsWith('#')) {
            previewData.skippedLines++
            return
        }
        
        // 解析行数据
        const parts = trimmedLine.split('----')
        let isValid = true
        let error = ''
        let platform = ''
        
        if (parts.length !== 3) {
            isValid = false
            error = '格式错误：应为 mid----accesstoken----refreshtoken'
            previewData.errorLines++
        } else {
            const [mid, accesstoken, refreshtoken] = parts.map(p => p.trim())
            
            if (!mid) {
                isValid = false
                error = 'mid不能为空'
                previewData.errorLines++
            } else if (!accesstoken) {
                isValid = false
                error = 'accesstoken不能为空'
                previewData.errorLines++
            } else if (!refreshtoken) {
                isValid = false
                error = 'refreshtoken不能为空'
                previewData.errorLines++
            } else {
                // 尝试解析JWT获取平台信息
                platform = parseJWTPlatform(accesstoken)
                if (!platform) {
                    isValid = false
                    error = 'accesstoken格式无效'
                    previewData.errorLines++
                } else {
                    previewData.validLines++
                }
            }
        }
        
        // 添加到预览数据（仅前10行）
        if (sampleCount < 10) {
            previewData.sampleData.push({
                lineNumber,
                mid: parts[0]?.trim() || '',
                platform,
                isValid,
                error
            })
            sampleCount++
        }
    })
}

// 解析JWT获取平台信息
const parseJWTPlatform = (token: string): string => {
    try {
        const parts = token.split('.')
        if (parts.length !== 3) return ''
        
        const payload = parts[1]
        const decoded = atob(payload.replace(/-/g, '+').replace(/_/g, '/'))
        const data = JSON.parse(decoded)
        
        return data.ctype || ''
    } catch {
        return ''
    }
}

// 格式化文件大小
const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// 清除文件
const clearFile = () => {
    fileInfo.name = ''
    fileInfo.size = 0
    fileInfo.content = ''
    previewData.totalLines = 0
    previewData.validLines = 0
    previewData.skippedLines = 0
    previewData.errorLines = 0
    previewData.sampleData = []
    uploadRef.value?.clearFiles()
}

// 新增分组
const handleAddGroup = async () => {
    showGroupEdit.value = true
    await nextTick()
    groupEditRef.value?.open('add')
}

// 分组创建成功
const handleGroupSuccess = () => {
    getGroupList()
    showGroupEdit.value = false
}

// 开始导入
const handleImport = async () => {
    if (!fileInfo.content) {
        ElMessage.error('请先选择文件')
        return
    }

    if (previewData.errorLines > 0) {
        ElMessage.error('文件中存在格式错误，请修正后重新上传')
        return
    }

    if (previewData.validLines === 0) {
        ElMessage.error('文件中没有有效数据')
        return
    }

    if (!selectedGroupId.value || selectedGroupId.value <= 0) {
        ElMessage.error('请选择目标分组')
        return
    }
    
    importing.value = true
    importProgress.value = 0

    // 声明在外部，确保在任何情况下都能清理
    let progressTimer: any = null

    try {
        // 模拟进度
        progressTimer = setInterval(() => {
            if (importProgress.value < 90) {
                importProgress.value += 10
            }
        }, 200)

        const params = {
            file_content: fileInfo.content,
            group_id: selectedGroupId.value
        }

        const response = await apiAltAccountBatchImport(params)

        // 清理进度定时器
        if (progressTimer) {
            clearInterval(progressTimer)
            progressTimer = null
        }
        importProgress.value = 100

        // 手动处理响应
        const result = response.data

        setTimeout(() => {
            importing.value = false
            importResult.value = result
            showResult.value = true
        }, 500)

    } catch (error: any) {
        // 清理进度定时器
        if (progressTimer) {
            clearInterval(progressTimer)
            progressTimer = null
        }

        // 立即重置状态
        importing.value = false
        importProgress.value = 0

        // 显示简单错误消息
        ElMessage.error(error.message || '导入失败')
    }
}

// 导入结果关闭
const handleResultClose = () => {
    showResult.value = false
    if (importResult.value.success_count > 0) {
        emit('success')
        handleClose()
    }
}

// 强制重置导入状态
const forceResetImportState = () => {
    importing.value = false
    importProgress.value = 0
}

// 关闭弹窗
const handleClose = () => {
    if (importing.value) {
        ElMessage.warning('导入进行中，请稍候...')
        return
    }

    // 重置所有状态
    resetData()
    showEdit.value = false
}

// 强制关闭弹窗（用于调试或紧急情况）
const forceClose = () => {
    forceResetImportState()
    resetData()
    showEdit.value = false
}
</script>

<style lang="scss" scoped>
.import-account-popup {
    .import-content {
        .upload-section {
            margin-bottom: 20px;

            :deep(.upload-demo.compact) {
                .el-upload-dragger {
                    height: 120px !important;
                    padding: 20px !important;
                }

                .el-icon--upload {
                    font-size: 48px !important;
                    margin-bottom: 8px !important;
                }

                .el-upload__text {
                    font-size: 14px !important;
                    margin-bottom: 8px !important;
                }

                .el-upload__tip {
                    font-size: 12px !important;
                }
            }
        }
        
        .file-info {
            margin-bottom: 20px;
            
            .file-details {
                display: flex;
                justify-content: space-between;
                align-items: center;
                
                .file-name {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-weight: 500;
                }
            }
        }
        
        .preview-section {
            margin-bottom: 20px;
            
            .preview-stats {
                margin-bottom: 16px;
                
                .stat-item {
                    text-align: center;
                    
                    .stat-value {
                        font-size: 24px;
                        font-weight: bold;
                        color: var(--el-color-primary);
                    }
                    
                    .stat-label {
                        font-size: 12px;
                        color: var(--el-text-color-regular);
                        margin-top: 4px;
                    }
                }
            }
            
            .preview-table {
                .preview-tip {
                    text-align: center;
                    color: var(--el-text-color-regular);
                    font-size: 12px;
                    margin-top: 8px;
                }
            }
        }
        
        .group-section {
            margin-bottom: 20px;
        }
        
        .progress-section {
            .progress-content {
                .progress-text {
                    margin-bottom: 12px;
                    text-align: center;
                    font-weight: 500;
                }
            }
        }
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
}
</style>
