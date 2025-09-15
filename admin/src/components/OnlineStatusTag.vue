<template>
    <div class="online-status-tag">
        <!-- 在线状态 -->
        <el-tag
            v-if="isOnline"
            type="success"
            size="small"
            class="online-tag"
        >
            <div class="status-content">
                <span class="status-dot online-dot"></span>
                <span class="status-text">在线</span>
            </div>
        </el-tag>
        
        <!-- 离线状态 -->
        <el-tag
            v-else
            type="info"
            size="small"
            class="offline-tag"
        >
            <div class="status-content">
                <span class="status-dot offline-dot"></span>
                <span class="status-text">{{ statusText || '离线' }}</span>
            </div>
        </el-tag>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    // 是否在线
    online?: boolean
    // 状态文本（如"5分钟前活跃"）
    statusText?: string
    // 简化模式，只显示圆点
    simple?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    online: false,
    statusText: '',
    simple: false
})

// 计算是否在线
const isOnline = computed(() => props.online)
</script>

<style scoped>
.online-status-tag {
    display: inline-flex;
    align-items: center;
}

.online-tag, .offline-tag {
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: 400;
}

.status-content {
    display: flex;
    align-items: center;
    gap: 4px;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}

.online-dot {
    background-color: #67c23a;
    box-shadow: 0 0 4px rgba(103, 194, 58, 0.4);
}

.offline-dot {
    background-color: #909399;
}

.status-text {
    line-height: 1;
    color: inherit;
}

/* 简化模式样式 */
.simple .status-text {
    display: none;
}

.simple .online-tag,
.simple .offline-tag {
    padding: 4px;
    min-width: 16px;
    justify-content: center;
}

/* 动画效果 */
.online-dot {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.7;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* 鼠标悬停效果 */
.online-tag:hover,
.offline-tag:hover {
    transform: translateY(-1px);
    transition: all 0.2s ease;
}
</style>