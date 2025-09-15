# CLAUDE.md

本文件为 Claude Code (claude.ai/code) 在此仓库中工作时提供指导。

## 项目概述

这是 LikeAdmin - 一个多平台管理系统，具备账户管理功能。项目包含四个主要应用：

- **服务端 (Backend)**: ThinkPHP 6.0 API 服务器 (`E:\aicode\likeadmin\server\`)
- **管理后台 (Management)**: Vue.js 3 + Element Plus 管理面板 (`E:\aicode\likeadmin\admin\`)  
- **PC端 (Frontend)**: Nuxt.js 3 网页应用 (`E:\aicode\likeadmin\pc\`)
- **移动端 (Mobile)**: Uni-app 跨平台移动应用 (`E:\aicode\likeadmin\uniapp\`)

## 后端服务器通过 phpstudy 在 `http://www.lineuk.com` 运行（无需手动启动）。
## 如果需要操作数据库，可以直接允许mysql命令，数据库是：likeadmin, 账号是root，密码是123456

## 架构概览

### 后端结构 (ThinkPHP 6.0)
- **控制器 (Controllers)**: `server/app/adminapi/controller/` - API端点和请求处理
- **业务逻辑 (Logic)**: `server/app/adminapi/logic/` - 业务逻辑层
- **模型 (Models)**: `server/app/common/model/` - 数据模型和数据库交互
- **验证器 (Validation)**: `server/app/adminapi/validate/` - 请求参数验证
- **路由 (Routes)**: `server/app/adminapi/route/` - API路由定义
- **列表类 (Lists)**: `server/app/adminapi/lists/` - 数据列表和分页逻辑
- **服务类 (Services)**: `server/app/common/service/` - 可复用的服务类

### 前端结构 (Vue.js 3)
- **视图 (Views)**: `admin/src/views/` - 按功能组织的页面组件
- **组件 (Components)**: `admin/src/components/` - 可复用的UI组件
- **API服务 (API)**: `admin/src/api/` - 后端通信的API服务层
- **状态管理 (Stores)**: `admin/src/stores/` - Pinia状态管理
- **路由 (Router)**: `admin/src/router/` - Vue Router配置
- **类型定义 (Types)**: `admin/src/typings/` - TypeScript类型定义

### 核心功能
- **账户管理**: 小号(alt_account)管理，支持层级权限
- **用户管理**: 多角色用户系统，包含代理商和租户
- **套餐系统**: 资源分配和端口管理
- **权限控制**: 基于角色的访问控制，支持层级关系
- **文件上传**: 多存储后端支持（本地、云端）
- **导入导出**: 批量操作，支持Excel/CSV格式

## 全栈开发工作流程

**始终遵循后端优先的方法**：

1. **开发前准备**：
   - **必须先阅读 `DEVELOPMENT_LOG.md` 了解项目最新状态和历史变更**
   - **必须使用 TodoWrite 工具创建任务清单，最后一项必须是"更新DEVELOPMENT_LOG.md"**
   - 分析需求，规划开发步骤

2. **后端开发**：
   - 在 `server/app/common/model/` 中创建/修改模型
   - 在 `server/app/adminapi/logic/` 中添加业务逻辑
   - 在 `server/app/adminapi/validate/` 中创建验证器
   - 在 `server/app/adminapi/controller/` 中构建控制器
   - 在 `server/app/adminapi/route/` 中定义路由

3. **前端开发**：
   - 在 `admin/src/typings/` 中定义TypeScript类型
   - 在 `admin/src/api/` 中创建API服务
   - 在 `admin/src/views/` 或 `admin/src/components/` 中构建Vue组件
   - 如需要，在 `admin/src/router/` 中更新路由

4. **文档更新 [必须步骤]**：
   - **在任务完成前必须更新 `DEVELOPMENT_LOG.md`**
   - **记录所有文件变更、技术决策、问题解决过程**
   - **这是开发工作流程的必要组成部分，不可省略**

## 开发指南

### 代码风格
- **后端**: 遵循ThinkPHP 6.0约定和PSR标准
- **前端**: 使用Vue.js 3 Composition API配合TypeScript
- **用户界面**: 使用Element Plus组件保持一致性
- **状态管理**: 复杂状态管理使用Pinia

### 数据库约定
- 表名以 `la_` 为前缀 (如 `la_admin`, `la_alt_account`)
- 字段名使用 snake_case 命名
- 标准表包含 `create_time`, `update_time`, `delete_time` 字段

### API响应格式
所有API响应遵循以下结构：
```php
{
    "code": 1,      // 1 = 成功, 0 = 错误
    "msg": "操作成功", // 中文消息
    "data": {...}   // 响应数据
}
```

### 权限系统
- **超级管理员**: 拥有系统完整访问权限
- **代理商**: 可以管理租户和资源
- **租户**: 仅限访问分配的资源
- 使用 `AdminHierarchyService` 进行权限检查

### ⚠️ 关键提醒 - 绝对不能忘记 ⚠️

**🚨 开发日志管理关键流程 🚨**
- **开始任务前：必须先阅读 `DEVELOPMENT_LOG.md` 主索引了解项目历史和模块结构**
- **开发过程中：每次开发任务都必须更新对应功能模块的日志文件 - 这是强制性要求**
- **任务规划时：在TodoWrite创建任务清单时，必须将"更新开发日志"设为最后一个任务**
- **完成检查时：任务未完成报告给用户前，必须先检查相关模块日志是否已更新**
- **新记录位置：根据功能选择对应模块文件进行记录**
  - 任务管理功能 → `docs/development-logs/2025/09-task-management.md`
  - 小号管理功能 → `docs/development-logs/2025/09-alt-account.md`  
  - 端口管理功能 → `docs/development-logs/2025/09-user-port.md`
  - 基础架构功能 → `docs/development-logs/2025/09-infrastructure.md`
  - 数据库相关 → `docs/development-logs/2025/09-database.md`
- **新模块开发：创建新的 `YYYY-MM-模块名.md` 文件并更新主索引**
- **跨模块功能：在相关的多个模块文件中都添加记录，并相互引用**

### 其他重要提示
- 后端服务器在 `http://www.lineuk.com` 自动运行 - 无需手动启动
- 使用现有的 QWEN.md 指南进行全栈开发工作流程
- 遵循已建立的导入/导出模式进行批量操作
- 修改现有API时保持向后兼容性

## 测试和验证
- 后端: 通过 `http://www.lineuk.com/adminapi/` 访问API
- 管理面板: `http://www.lineuk.com/admin` (生产环境) 或 `http://localhost:5173/admin` (开发环境)
- 始终测试创建/编辑场景和列表/删除操作
- 验证权限控制在不同用户角色下正确工作