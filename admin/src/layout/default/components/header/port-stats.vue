<template>
    <div v-if="isTenant && portStats" class="port-stats-container flex items-center mr-4">
        <el-tooltip
            class="box-item"
            effect="dark"
            :content="tooltipContent"
            placement="bottom"
        >
            <div class="port-stats flex items-center px-3 py-1 rounded bg-blue-50 text-blue-600 text-sm cursor-pointer">
                <icon name="el-icon-Connection" class="mr-1" />
                <span>{{ displayText }}</span>
            </div>
        </el-tooltip>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import useUserStore from '@/stores/modules/user'
import { tenantPortStats } from '@/api/tenant'
import { useBreakpoints, breakpointsTailwind } from '@vueuse/core'

// 定义端口统计信息类型
interface PortStats {
    total_ports: number
    used_ports: number
    available_ports: number
    expired_ports: number
    nearest_expire_time: string
}

const userStore = useUserStore()
const portStats = ref<PortStats | null>(null)
const isTenant = ref(false)

// 使用VueUse的breakpoints来检测屏幕尺寸
const breakpoints = useBreakpoints(breakpointsTailwind)
const isPC = breakpoints.greater('md') // md breakpoint is 768px

// 计算属性：根据屏幕尺寸决定显示的文本
const displayText = computed(() => {
    if (!portStats.value) return ''
    
    // PC模式下显示完整信息
    if (isPC.value) {
        return `端口总数: ${portStats.value.total_ports} | 空闲端口: ${portStats.value.available_ports} | 到期时间: ${portStats.value.nearest_expire_time}`
    } 
    // 移动端模式下显示简化信息
    else {
        return `端口: ${portStats.value.available_ports}/${portStats.value.total_ports}`
    }
})

// 计算属性：tooltip提示内容（始终显示完整信息）
const tooltipContent = computed(() => {
    if (!portStats.value) return ''
    return `端口总数: ${portStats.value.total_ports} | 空闲端口: ${portStats.value.available_ports} | 到期时间: ${portStats.value.nearest_expire_time}`
})

// 检查用户是否为租户
const checkIfTenant = () => {
    // 确保用户信息已加载
    if (!userStore.userInfo || Object.keys(userStore.userInfo).length === 0) {
        return false
    }
    
    // 检查用户角色中是否包含租户角色ID (根据后端代码，租户角色ID为2)
    const tenantRoleId = 2
    
    // 处理不同类型的role_id
    if (userStore.userInfo.role_id && Array.isArray(userStore.userInfo.role_id)) {
        // role_id是数组
        isTenant.value = userStore.userInfo.role_id.includes(tenantRoleId)
    } else if (typeof userStore.userInfo.role_id === 'number') {
        // role_id是数字
        isTenant.value = userStore.userInfo.role_id === tenantRoleId
    } else if (typeof userStore.userInfo.role_id === 'string') {
        // role_id是字符串，尝试转换为数字
        const roleIdNum = parseInt(userStore.userInfo.role_id, 10)
        isTenant.value = roleIdNum === tenantRoleId
    } else {
        isTenant.value = false
    }
    
    return isTenant.value
}

// 获取端口统计信息
const fetchPortStats = async () => {
    try {
        const res = await tenantPortStats()
        
        // 检查响应格式
        if (res && (res.data || (typeof res === 'object' && res.total_ports !== undefined))) {
            // 如果有res.data，使用res.data
            if (res.data) {
                portStats.value = res.data
            } else {
                // 否则直接使用res
                portStats.value = res
            }
        }
    } catch (error) {
        console.error('获取端口统计信息失败:', error)
    }
}

// 监听用户信息变化
watch(
    () => userStore.userInfo,
    (newUserInfo) => {
        if (newUserInfo && Object.keys(newUserInfo).length > 0) {
            const isTenantUser = checkIfTenant()
            if (isTenantUser) {
                fetchPortStats()
            }
        }
    },
    { deep: true }
)

onMounted(() => {
    // 初始检查
    const isTenantUser = checkIfTenant()
    if (isTenantUser) {
        fetchPortStats()
    }
})
</script>

<style scoped>
.port-stats-container {
    height: var(--navbar-height);
}

.port-stats {
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.port-stats:hover {
    background-color: #dbeafe; /* blue-100 */
}

/* 在PC模式下增加最大宽度 */
@media (min-width: 768px) {
    .port-stats {
        max-width: 600px;
    }
}
</style>