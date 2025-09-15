<template>
    <div class="import-result-dialog">
        <el-dialog
            v-model="showDialog"
            title="导入结果"
            :width="700"
            :close-on-click-modal="false"
            @close="handleClose"
        >
            <div class="result-content">
                <!-- 导入统计 -->
                <div class="result-summary">
                    <el-row :gutter="20">
                        <el-col :span="8">
                            <div class="summary-item success">
                                <div class="summary-value">{{ result.success_count || 0 }}</div>
                                <div class="summary-label">成功导入</div>
                            </div>
                        </el-col>
                        <el-col :span="8">
                            <div class="summary-item error">
                                <div class="summary-value">{{ result.failed_count || 0 }}</div>
                                <div class="summary-label">导入失败</div>
                            </div>
                        </el-col>
                        <el-col :span="8">
                            <div class="summary-item">
                                <div class="summary-value">{{ result.total_lines || 0 }}</div>
                                <div class="summary-label">总行数</div>
                            </div>
                        </el-col>
                    </el-row>
                </div>

                <!-- 导入状态 -->
                <div class="result-status">
                    <el-alert
                        :title="alertTitle"
                        :type="alertType"
                        :description="alertDescription"
                        show-icon
                        :closable="false"
                    />
                </div>

                <!-- 错误统计 -->
                <div v-if="hasErrors && result.error_stats" class="error-stats">
                    <div class="error-header">
                        <span>错误统计</span>
                    </div>
                    <div class="error-types">
                        <el-tag
                            v-for="(count, errorCode) in result.error_stats"
                            :key="errorCode"
                            type="danger"
                            size="small"
                            class="error-tag"
                        >
                            {{ getErrorMessage(errorCode) }}: {{ count }}条
                        </el-tag>
                    </div>
                </div>

                <!-- 导入时间 -->
                <div class="import-time">
                    <el-text type="info" size="small">
                        导入时间：{{ result.import_time }}
                    </el-text>
                </div>
            </div>

            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="handleClose">关闭</el-button>
                    <el-button v-if="isSuccess" type="primary" @click="handleClose">
                        确定
                    </el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="ImportResultDialog">
import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { CopyDocument, Download } from '@element-plus/icons-vue'

// 组件属性
const props = defineProps<{
    modelValue: boolean
    result: any
}>()

const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'close': []
}>()

// 响应式数据
const showDialog = ref(false)

// 计算属性
const isSuccess = computed(() => {
    return props.result.success_count > 0 && props.result.failed_count === 0
})

const hasErrors = computed(() => {
    return props.result.failed_count > 0
})

const alertTitle = computed(() => {
    if (isSuccess.value) {
        return '导入成功'
    } else if (hasErrors.value && props.result.success_count === 0) {
        return '导入失败'
    } else {
        return '部分成功'
    }
})

const alertType = computed(() => {
    if (isSuccess.value) {
        return 'success'
    } else if (hasErrors.value && props.result.success_count === 0) {
        return 'error'
    } else {
        return 'warning'
    }
})

const alertDescription = computed(() => {
    const successCount = props.result.success_count || 0
    const failedCount = props.result.failed_count || 0

    if (isSuccess.value) {
        return `成功导入 ${successCount} 个账号`
    } else if (hasErrors.value && successCount === 0) {
        return `导入失败，共 ${failedCount} 个错误`
    } else {
        return `成功导入 ${successCount} 个账号，${failedCount} 个失败`
    }
})

// 错误消息转换
const getErrorMessage = (errorCode: string) => {
    const errorMessages: Record<string, string> = {
        'DUPLICATE_MID': '账号重复',
        'DUPLICATE_MID_IN_IMPORT': '文件内重复',
        'EMPTY_MID': '账号为空',
        'EMPTY_ACCESSTOKEN': 'Token为空',
        'EMPTY_REFRESHTOKEN': '刷新Token为空',
        'INVALID_JWT': 'Token格式错误',
        'SYSTEM_ERROR': '系统错误'
    }
    return errorMessages[errorCode] || errorCode
}

// 监听显示状态
watch(() => props.modelValue, (val) => {
    showDialog.value = val
})

watch(showDialog, (val) => {
    emit('update:modelValue', val)
})



// 关闭对话框
const handleClose = () => {
    showDialog.value = false
    emit('close')
}
</script>

<style lang="scss" scoped>
.import-result-dialog {
    .result-content {
        .result-summary {
            margin-bottom: 20px;
            
            .summary-item {
                text-align: center;
                padding: 16px;
                border-radius: 8px;
                background-color: var(--el-fill-color-light);
                
                &.success {
                    background-color: var(--el-color-success-light-9);
                    
                    .summary-value {
                        color: var(--el-color-success);
                    }
                }
                
                &.error {
                    background-color: var(--el-color-error-light-9);
                    
                    .summary-value {
                        color: var(--el-color-error);
                    }
                }
                
                .summary-value {
                    font-size: 28px;
                    font-weight: bold;
                    color: var(--el-color-primary);
                    line-height: 1;
                }
                
                .summary-label {
                    font-size: 14px;
                    color: var(--el-text-color-regular);
                    margin-top: 8px;
                }
            }
        }
        
        .result-status {
            margin-bottom: 20px;
        }

        .error-stats {
            margin-bottom: 20px;

            .error-header {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 12px;
                color: var(--el-text-color-primary);
            }

            .error-types {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;

                .error-tag {
                    margin: 0;
                }
            }
        }
        
        .error-details {
            margin-bottom: 20px;
            
            .error-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 12px;
                
                > span {
                    font-weight: 500;
                    font-size: 16px;
                }
                
                .error-actions {
                    display: flex;
                    gap: 8px;
                }
            }
        }
        
        .import-time {
            text-align: center;
            margin-top: 16px;
        }
    }
}
</style>
