# QWEN 全栈开发高级指南: Server & Admin

## 核心目标

本文档旨在指导 AI 编程助手高效、规范地进行 **全栈开发**。当接到一个功能需求时，AI **必须** 将其视为一个完整的端到端任务，协同完成后端 (`server` 目录) 和前端 (`admin` 目录) 的开发，并确保交付的功能稳定、一致且高质量。

---

## 协作三大原则 (AI 必须遵守)

1.  **永远先规划，后行动**: 在编写任何代码之前，**必须** 首先提出一个清晰、分步的 **全栈开发计划** 并征得用户同意。计划应明确包含后端和前端的修改点。
2.  **小步快跑，持续验证**: 优先完成一个最小可用功能的闭环（例如，先完成列表展示，再做新增/编辑），而不是一次性实现所有功能。
3.  **尊重并模仿现有代码**: 在修改或新增代码前，**必须** 阅读相关目录下的现有代码，以理解并遵循项目已有的编码风格、命名约定和设计模式。

---

## 核心项目技术栈

*   **后端 (Server):** `E:/aicode/likeadmin/server/`
    *   **技术:** ThinkPHP 6.0, PHP 8.0+
    *   **运行环境:** 由 phpstudy 管理，通过本地域名 `http://www.lineuk.com` 访问。**AI 无需手动启动后端服务。**
    *   **职责:** API 接口、业务逻辑、数据持久化。
*   **前端 (Admin):** `E:/aicode/likeadmin/admin/`
    *   **技术:** Vue.js 3, Vite, Element Plus, TypeScript, Pinia
    *   **职责:** 用户界面、API 调用、前端状态管理。

---

## 标准全栈开发工作流程

### 第一步：后端 API 开发 (Backend First)

1.  **数据模型 (Model):**
    *   **位置:** `server/app/common/model/`
    *   **任务:** 如果涉及新数据，创建或修改对应的数据模型 `.php` 文件。同时，在 `server/sql/` 目录下提供相应的 `.sql` 文件以更新数据库结构。

2.  **业务逻辑 (Logic):**
    *   **位置:** `server/app/adminapi/logic/`
    *   **任务:** 创建或修改逻辑类。这里是处理核心业务逻辑的地方，例如数据处理、权限校验等。

3.  **验证器 (Validation):**
    *   **位置:** `server/app/adminapi/validate/`
    *   **任务:** 为接收的请求参数创建验证器，确保数据的合法性。

4.  **控制器 (Controller):**
    *   **位置:** `server/app/adminapi/controller/`
    *   **任务:** 作为 API 入口，调用 Logic 和 Validate，并构建统一格式的响应 (`{code, msg, data}`)。

5.  **路由 (Route):**
    *   **位置:** `server/app/adminapi/route/`
    *   **任务:** 在独立的路由文件中（如 `user.php`）定义 API 路由，并指向对应的控制器方法。

### 第二步：前端 UI & 交互开发 (Frontend Next)

1.  **类型定义 (Typing):**
    *   **位置:** `admin/src/typings/` 或相关 `api` 目录下。
    *   **任务:** 为从后端获取的数据和提交的参数创建 TypeScript `interface` 或 `type`，提供类型安全保障。

2.  **API 服务 (API Service):**
    *   **位置:** `admin/src/api/`
    *   **任务:** 创建或修改 `.ts` 文件，封装对后端 API 的请求函数。

3.  **状态管理 (Store):**
    *   **位置:** `admin/src/stores/`
    *   **任务:** (可选) 如果是复杂的全局状态，使用 Pinia 创建或更新 Store 来管理数据。

4.  **视图与组件 (Views & Components):**
    *   **位置:** `admin/src/views/` 和 `admin/src/components/`
    *   **任务:** 创建或修改 `.vue` 文件。**必须** 优先复用项目中已有的 Element Plus 组件和样式，保持 UI 风格一致。

5.  **前端路由 (Router):**
    *   **位置:** `admin/src/router/`
    *   **任务:** 如果增加了新页面，在此处注册新的路由。

### 第三步：联调与验证

*   AI 需要在完成编码后，说明如何启动前端项目 (`cd admin && npm run dev`) 并验证功能。
*   **后端服务已在 `http://www.lineuk.com` 上运行，无需操作。**
*   验证示例：“请您启动前端开发服务器，然后访问 `http://localhost:5173/admin/` 下的XX菜单，检查列表是否正常显示，并尝试新增一条数据。”

### 第四步：记录开发日志

*   每次开发任务（包括新增功能、修改代码、修复Bug等）完成后，**必须** 在 `DEVELOPMENT_LOG.md` 文件中记录本次变更的详细信息。
*   **日志文件位置:** `E:/aicode/likeadmin/DEVELOPMENT_LOG.md`
*   记录内容应包括：变更日期、任务描述、涉及的后端和前端文件、关键逻辑说明以及验证方法。

---

## 实战示例：”添加简单的文章管理功能“

> **AI 在行动前应展示如下计划：**

好的，我将为您添加一个简单的文章管理功能。根据全栈开发指南，我的计划如下：

**第一步：后端 (Server)**
1.  在 `server/sql/` 中提供 `article.sql` 用于创建文章表。
2.  创建 `server/app/common/model/Article.php` 数据模型。
3.  创建 `server/app/adminapi/logic/ArticleLogic.php` 用于处理文章的增删改查逻辑。
4.  创建 `server/app/adminapi/validate/ArticleValidate.php` 用于验证文章提交的数据。
5.  创建 `server/app/adminapi/controller/Article.php` 控制器，提供 `lists`, `detail`, `add`, `edit`, `del` 方法。
6.  在 `server/app/adminapi/route/article.php` 中定义相关路由。

**第二步：前端 (Admin)**
1.  在 `admin/src/api/` 下创建 `article.ts`，用于请求文章相关接口。
2.  在 `admin/src/views/article/` 目录下创建 `index.vue`，使用 Element Plus 的 `el-table` 展示文章列表，并包含新增/编辑/删除按钮。
3.  (可选) 创建 `edit.vue` 作为新增和编辑文章的表单页面。
4.  在 `admin/src/router/` 中添加 `/article` 路由，指向 `index.vue`。

**第三步：验证**
完成后，我会提示您启动前端服务，并访问文章管理菜单进行测试。后端服务无需您手动启动。

请问是否可以按此计划执行？