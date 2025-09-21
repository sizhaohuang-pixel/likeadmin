<template>
    <el-dialog 
        v-model="visible" 
        title="导入昵称" 
        width="600px" 
        :close-on-click-modal="false"
        :before-close="handleClose"
    >
        <div class="import-container">
            <!-- 使用说明 -->
            <el-alert
                title="导入说明"
                type="info"
                :closable="false"
                show-icon
                class="mb-4"
            >
                <template #default>
                    <ul class="list-disc pl-4 space-y-1 text-sm">
                        <li>只支持 .txt 格式文件</li>
                        <li>文件编码格式为 UTF-8</li>
                        <li>一行一条昵称数据</li>
                        <li>每行昵称长度最长20个字符</li>
                        <li>超过20字符的昵称将导入失败</li>
                        <li>重复昵称将被自动跳过</li>
                    </ul>
                </template>
            </el-alert>

            <!-- 文件上传区域 -->
            <div class="upload-section mb-4">
                <el-upload
                    ref="uploadRef"
                    class="upload-demo"
                    drag
                    :auto-upload="false"
                    :show-file-list="true"
                    :limit="1"
                    accept=".txt"
                    :on-change="handleFileChange"
                    :on-exceed="handleExceed"
                    :on-remove="handleFileRemove"
                >
                    <el-icon class="el-icon--upload"><upload-filled /></el-icon>
                    <div class="el-upload__text">
                        将文件拖到此处，或<em>点击上传</em>
                    </div>
                    <template #tip>
                        <div class="el-upload__tip">
                            只能上传 .txt 文件，且不超过 10MB
                        </div>
                    </template>
                </el-upload>
            </div>

            <!-- 文件内容预览 -->
            <div v-if="fileContent" class="preview-section mb-4">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-sm font-medium">文件内容预览 (前10行)</h4>
                    <span class="text-xs text-gray-500">总行数: {{ totalLines }}</span>
                </div>
                <el-input
                    type="textarea"
                    :model-value="previewContent"
                    :rows="6"
                    readonly
                    class="preview-textarea"
                />
            </div>

            <!-- 导入结果 -->
            <div v-if="importResult" class="result-section">
                <el-alert
                    :title="getResultTitle(importResult)"
                    :type="getResultType(importResult)"
                    :closable="false"
                    show-icon
                    class="mb-3"
                />
                
                <div class="result-stats grid grid-cols-2 gap-4 mb-4">
                    <div class="stat-item">
                        <div class="stat-label">总行数</div>
                        <div class="stat-value">{{ importResult.total_lines }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">有效行数</div>
                        <div class="stat-value">{{ importResult.valid_lines }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">成功导入</div>
                        <div class="stat-value text-green-600">{{ importResult.success_count }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">失败条数</div>
                        <div class="stat-value text-red-600">{{ importResult.failed_count }}</div>
                    </div>
                </div>

                <!-- 错误详情 -->
                <div v-if="importResult.errors && importResult.errors.length > 0" class="error-details">
                    <h5 class="text-sm font-medium mb-2">错误详情</h5>
                    <el-table :data="importResult.errors" size="small" max-height="200">
                        <el-table-column prop="line_number" label="行号" width="60" />
                        <el-table-column prop="nickname" label="昵称内容" width="150" />
                        <el-table-column prop="error_message" label="错误信息" />
                    </el-table>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="dialog-footer">
                <el-button @click="handleClose">取消</el-button>
                <el-button 
                    type="primary" 
                    @click="handleImport" 
                    :loading="importing"
                    :disabled="!fileContent || importing"
                >
                    {{ importing ? '导入中...' : '开始导入' }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup name="importNicknamePopup">
import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { UploadFilled } from '@element-plus/icons-vue'
import type { UploadInstance, UploadProps, UploadRawFile } from 'element-plus'
import { apiNicknameRepositoryBatchImport } from '@/api/nickname_repository'
import type { NicknameImportResult } from '@/typings/nickname-repository'

interface Props {
    modelValue: boolean
    groupName: string
}

interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// 数据状态
const uploadRef = ref<UploadInstance>()
const importing = ref(false)
const fileContent = ref('')
const totalLines = ref(0)
const importResult = ref<NicknameImportResult | null>(null)

// 计算属性
const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

const previewContent = computed(() => {
    if (!fileContent.value) return ''
    const lines = fileContent.value.split('\n')
    return lines.slice(0, 10).join('\n')
})

// 获取导入结果标题
const getResultTitle = (result: any) => {
    if (result.success_count > 0) {
        return '导入完成'
    } else if (result.skipped_lines > 0 && result.failed_count === 0) {
        return '导入完成 (全部重复)'
    } else {
        return '导入失败'
    }
}

// 获取导入结果类型
const getResultType = (result: any) => {
    if (result.success_count > 0) {
        return 'success'
    } else if (result.skipped_lines > 0 && result.failed_count === 0) {
        return 'warning'
    } else {
        return 'error'
    }
}

// 监听对话框显示状态
watch(visible, (newVal) => {
    if (newVal) {
        resetForm()
    }
})

// 重置表单
const resetForm = () => {
    fileContent.value = ''
    totalLines.value = 0
    importResult.value = null
    importing.value = false
    uploadRef.value?.clearFiles()
}

// 文件变化处理
const handleFileChange: UploadProps['onChange'] = (file) => {
    const rawFile = file.raw
    if (!rawFile) return

    // 验证文件类型
    if (!rawFile.name.toLowerCase().endsWith('.txt')) {
        ElMessage.error('只支持 .txt 格式文件')
        uploadRef.value?.clearFiles()
        return
    }

    // 验证文件大小 (10MB)
    if (rawFile.size > 10 * 1024 * 1024) {
        ElMessage.error('文件大小不能超过 10MB')
        uploadRef.value?.clearFiles()
        return
    }

    // 读取文件内容
    const reader = new FileReader()
    reader.onload = (e) => {
        const content = e.target?.result as string
        fileContent.value = content
        const lines = content.split('\n').filter(line => line.trim())
        totalLines.value = lines.length
    }
    reader.readAsText(rawFile, 'UTF-8')
}

// 文件超出限制
const handleExceed = () => {
    ElMessage.warning('只能选择一个文件')
}

// 移除文件
const handleFileRemove = () => {
    fileContent.value = ''
    totalLines.value = 0
    importResult.value = null
}

// 执行导入
const handleImport = async () => {
    if (!fileContent.value) {
        ElMessage.error('请先选择文件')
        return
    }

    if (!props.groupName) {
        ElMessage.error('缺少分组名称')
        return
    }

    try {
        importing.value = true
        
        const result = await apiNicknameRepositoryBatchImport({
            group_name: props.groupName,
            file_content: fileContent.value
        })
        
        importResult.value = result
        
        // 判断导入是否成功
        if (result.success_count > 0) {
            // 有成功导入的数据
            ElMessage.success(`成功导入 ${result.success_count} 条昵称${result.skipped_lines > 0 ? `，跳过 ${result.skipped_lines} 条重复昵称` : ''}`)
            emit('success')
        } else if (result.skipped_lines > 0 && result.failed_count === 0) {
            // 全部是重复数据，但没有错误
            ElMessage.warning(`所有昵称均已存在，跳过 ${result.skipped_lines} 条重复昵称`)
            emit('success')  // 仍然触发成功事件，因为没有真正的错误
        } else if (result.failed_count > 0) {
            // 有失败的数据
            ElMessage.error(`导入失败 ${result.failed_count} 条，成功 ${result.success_count} 条`)
        } else {
            // 其他情况
            ElMessage.error('导入失败，请检查文件格式')
        }
    } catch (error: any) {
        ElMessage.error(error.message || '导入失败')
    } finally {
        importing.value = false
    }
}

// 关闭对话框
const handleClose = () => {
    if (importing.value) {
        ElMessage.warning('正在导入中，请稍候...')
        return
    }
    visible.value = false
}
</script>

<style scoped>
.import-container {
    padding: 0;
}

.upload-demo {
    width: 100%;
}

.preview-textarea :deep(.el-textarea__inner) {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
}

.result-stats {
    background: #f9fafb;
    border-radius: 6px;
    padding: 16px;
}

.stat-item {
    text-align: center;
}

.stat-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 20px;
    font-weight: bold;
    color: #333;
}

.error-details {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 12px;
}
</style>