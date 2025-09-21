<template>
    <div class="image-cropper">
        <div class="cropper-container" v-if="imageSrc">
            <div ref="cropperRef" class="cropper-wrapper"></div>
            
            <div class="cropper-controls mt-4">
                <el-button @click="rotateLeft">向左旋转</el-button>
                <el-button @click="rotateRight">向右旋转</el-button>
                <el-button @click="reset">重置</el-button>
                <el-button type="primary" @click="getCroppedImage">确认裁剪</el-button>
            </div>
        </div>
        
        <div v-else class="upload-area">
            <el-upload
                class="avatar-uploader"
                action="#"
                :show-file-list="false"
                :before-upload="beforeUpload"
                :on-change="handleFileChange"
                :auto-upload="false"
                accept="image/jpeg,image/jpg,image/png"
            >
                <el-icon class="avatar-uploader-icon"><Plus /></el-icon>
                <div class="upload-text">点击选择图片</div>
            </el-upload>
            <div class="upload-tips">
                <p>支持 JPG、PNG、JPEG 格式</p>
                <p>建议尺寸：280x280像素</p>
                <p>文件大小不超过 1.5MB</p>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.css'
import feedback from '@/utils/feedback'

const emit = defineEmits<{
    cropped: [file: File]
    cancel: []
}>()

const cropperRef = ref<HTMLElement>()
const imageSrc = ref('')
const cropper = ref<Cropper | null>(null)

const handleFileChange = (file: any, fileList: any[]) => {
    console.log('handleFileChange 被调用，文件信息:', {
        file: file,
        fileList: fileList,
        rawFile: file.raw
    })
    
    if (file.raw) {
        // 处理原始文件
        processFile(file.raw)
    }
}

const processFile = (file: File) => {
    console.log('processFile 被调用，文件信息:', {
        name: file.name,
        type: file.type,
        size: file.size
    })
    
    // 检查文件类型
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png']
    if (!allowedTypes.includes(file.type)) {
        feedback.msgError('只支持 JPG、PNG、JPEG 格式的图片')
        return
    }
    
    // 检查文件大小（1.5MB）
    if (file.size > 1.5 * 1024 * 1024) {
        feedback.msgError('图片大小不能超过1.5MB')
        return
    }
    
    console.log('文件验证通过，开始读取文件')
    
    // 创建图片预览
    const reader = new FileReader()
    reader.onload = (e) => {
        console.log('文件读取完成，准备初始化裁剪器')
        imageSrc.value = e.target?.result as string
        nextTick(() => {
            console.log('nextTick 执行，开始初始化裁剪器')
            initCropper()
        })
    }
    reader.onerror = () => {
        console.error('文件读取失败')
        feedback.msgError('文件读取失败')
    }
    reader.readAsDataURL(file)
}

const beforeUpload = (file: File) => {
    console.log('beforeUpload 被调用，文件信息:', {
        name: file.name,
        type: file.type,
        size: file.size
    })
    
    // 检查文件类型
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png']
    if (!allowedTypes.includes(file.type)) {
        feedback.msgError('只支持 JPG、PNG、JPEG 格式的图片')
        return false
    }
    
    // 检查文件大小（1.5MB）
    if (file.size > 1.5 * 1024 * 1024) {
        feedback.msgError('图片大小不能超过1.5MB')
        return false
    }
    
    console.log('beforeUpload 文件验证通过')
    
    // 阻止自动上传，我们在 handleFileChange 中处理文件
    return false
}

const initCropper = () => {
    console.log('initCropper 被调用，检查条件:', {
        cropperRef: !!cropperRef.value,
        imageSrc: !!imageSrc.value
    })
    
    if (!cropperRef.value || !imageSrc.value) {
        console.warn('裁剪器初始化失败：缺少必要的元素或图片源')
        return
    }
    
    console.log('条件满足，开始初始化裁剪器')
    
    // 销毁之前的cropper实例
    if (cropper.value) {
        console.log('销毁之前的裁剪器实例')
        cropper.value.destroy()
    }
    
    // 创建img元素
    const img = document.createElement('img')
    img.src = imageSrc.value
    img.style.maxWidth = '100%'
    
    console.log('创建图片元素，准备添加到容器')
    
    // 清空容器并添加图片
    cropperRef.value.innerHTML = ''
    cropperRef.value.appendChild(img)
    
    console.log('图片已添加到容器，开始初始化Cropper')
    
    // 初始化cropper
    try {
        cropper.value = new Cropper(img, {
            aspectRatio: 1, // 1:1 比例，280x280
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            cropBoxResizable: false, // 禁止调整裁剪框大小
            cropBoxMovable: true,
            toggleDragModeOnDblclick: false,
            ready() {
                console.log('Cropper ready 回调被触发')
                // 设置裁剪框为280x280（如果图片足够大）
                if (cropper.value) {
                    const containerData = cropper.value.getContainerData()
                    const size = Math.min(containerData.width, containerData.height, 280)
                    
                    console.log('设置裁剪框大小:', size)
                    cropper.value.setCropBoxData({
                        width: size,
                        height: size
                    })
                }
            }
        })
        console.log('Cropper 实例创建成功')
    } catch (error) {
        console.error('Cropper 初始化失败:', error)
        feedback.msgError('图片裁剪器初始化失败')
    }
}

const rotateLeft = () => {
    cropper.value?.rotate(-90)
}

const rotateRight = () => {
    cropper.value?.rotate(90)
}

const reset = () => {
    cropper.value?.reset()
}

const getCroppedImage = () => {
    if (!cropper.value) return
    
    // 获取裁剪后的canvas
    const canvas = cropper.value.getCroppedCanvas({
        width: 280,
        height: 280,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high' as ImageSmoothingQuality
    })
    
    // 转换为JPG格式的Blob
    canvas.toBlob((blob) => {
        if (blob) {
            // 创建File对象
            const file = new File([blob], 'avatar.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now()
            })
            emit('cropped', file)
        }
    }, 'image/jpeg', 0.9) // 90%质量的JPG
}

const cancel = () => {
    emit('cancel')
}

// 清理资源
onUnmounted(() => {
    if (cropper.value) {
        cropper.value.destroy()
    }
})

// 暴露方法给父组件
defineExpose({
    cancel
})
</script>

<style scoped>
.image-cropper {
    width: 100%;
}

.cropper-container {
    width: 100%;
}

.cropper-wrapper {
    width: 100%;
    height: 400px;
    border: 1px solid #dcdfe6;
    border-radius: 4px;
    overflow: hidden;
}

.cropper-controls {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.upload-area {
    text-align: center;
    padding: 40px 20px;
}

.avatar-uploader {
    display: inline-block;
}

.avatar-uploader :deep(.el-upload) {
    border: 2px dashed #d9d9d9;
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: 0.2s;
    width: 200px;
    height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.avatar-uploader :deep(.el-upload:hover) {
    border-color: #409eff;
}

.avatar-uploader-icon {
    font-size: 28px;
    color: #8c939d;
    width: 28px;
    height: 28px;
    text-align: center;
    margin-bottom: 8px;
}

.upload-text {
    color: #8c939d;
    font-size: 14px;
}

.upload-tips {
    margin-top: 16px;
    color: #909399;
    font-size: 12px;
    line-height: 1.5;
}

.upload-tips p {
    margin: 2px 0;
}
</style>