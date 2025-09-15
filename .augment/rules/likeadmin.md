---
type: "always_apply"
---

# Likeadmin项目 Augment Agent 全栈开发规则
# 官方文档: https://old-doc.likeadmin.cn/php/

## 项目概述
- **项目名称**: Likeadmin管理系统
- **架构**: 多端分离架构（Server + Admin + PC + UniApp）
- **开发模式**: 全栈开发（前后端配套实现）
- **版本**: 1.9.4

### 技术栈
**后端 (server)**:
- **框架**: ThinkPHP 8.0
- **PHP版本**: >=8.0
- **数据库**: MySQL >=5.7
- **架构模式**: 多应用分层架构

**前端 (admin)**:
- **框架**: Vue 3 + TypeScript
- **UI库**: Element Plus
- **构建工具**: Vite
- **状态管理**: Pinia
- **样式**: Tailwind CSS + SCSS
- **Node.js版本**: >=14.18.1

**其他端**:
- **PC端**: Nuxt.js (SSR/SPA)
- **移动端**: uni-app

## 1. 全栈开发规范

### 1.1 开发原则
- **前后端配套**: 除非特别说明，所有功能都需要前后端同步实现
- **API优先**: 先设计API接口，再实现前端页面
- **权限一致**: 前后端权限控制保持一致
- **数据同步**: 前端展示数据与后端API返回数据结构保持一致

### 1.2 新功能开发流程
1. **数据库设计**: 创建/修改数据表结构
2. **后端开发**:
   - 创建Model（继承BaseModel）
   - 实现Logic业务逻辑
   - 创建Controller（继承对应基类）
   - 创建Validate验证器
   - 实现Lists列表类
   - 配置路由和权限
3. **前端开发**:
   - 创建API接口调用函数
   - 实现页面组件和视图
   - 配置路由和权限
   - 集成后端API
4. **联调测试**: 前后端联调和功能测试

### 1.3 命名规范
**后端命名**:
- **控制器**: `XxxController`，继承 `BaseApiController` 或 `BaseAdminController`
- **模型**: 驼峰命名，继承 `BaseModel`
- **逻辑层**: `XxxLogic`，继承 `BaseLogic`，主要使用静态方法
- **验证器**: `XxxValidate`，继承 `BaseValidate`
- **列表类**: `XxxLists`，继承 `BaseDataLists`
- **服务类**: `XxxService`

**前端命名**:
- **页面组件**: 使用 PascalCase，如 `UserList.vue`
- **API函数**: 使用 camelCase，如 `getUserList`
- **Store模块**: 使用 camelCase，如 `useUserStore`
- **路由名称**: 使用 kebab-case，如 `user-list`

### 1.4 注释规范
**后端PHPDoc注释**:
```php
/**
 * @notes 方法描述
 * @param array $params 参数描述
 * @return array|bool
 * @throws \Exception
 * @author 作者名
 * @date 2024/01/01 10:00
 */
```

**前端JSDoc注释**:
```typescript
/**
 * 获取用户列表
 * @param params 查询参数
 * @returns 用户列表数据
 */
```

### 1.5 代码风格
**后端**:
- 使用严格类型声明: `declare (strict_types = 1);`
- 命名空间必须与目录结构一致
- 类属性和方法的可见性必须明确声明

**前端**:
- 使用 TypeScript 严格模式
- 组件使用 Composition API + `<script setup>`
- 统一使用 ESLint + Prettier 格式化

## 2. 架构模式识别与规则

### 2.1 多应用架构
项目采用ThinkPHP多应用架构，包含以下主要应用：
- `app/api/`: 用户端API (C端用户)
- `app/adminapi/`: 管理端API (后台管理)
- `app/index/`: 默认应用
- `app/common/`: 公共代码库

### 2.2 分层架构模式
严格遵循以下分层结构：
```
Controller (控制器层)
    ↓
Logic (业务逻辑层)
    ↓
Model (数据模型层)
    ↓
Database (数据库层)
```

### 2.3 数据处理规则
- 所有模型必须继承 `BaseModel`
- 优先使用软删除而非物理删除
- 数据库操作必须考虑事务处理

## 3. 文件组织规则

### 3.1 目录结构规范
每个应用模块必须包含以下目录：
```
app/{module}/
├── controller/     # 控制器
├── logic/         # 业务逻辑
├── validate/      # 验证器
├── lists/         # 列表类
├── service/       # 服务类
├── http/
│   └── middleware/ # 中间件
└── config/        # 配置文件
```

### 3.2 文件命名规则
- 控制器文件: `{Name}Controller.php`
- 逻辑文件: `{Name}Logic.php`
- 模型文件: `{Name}.php`
- 验证器文件: `{Name}Validate.php`
- 列表文件: `{Name}Lists.php`

### 3.3 子目录组织
按业务模块组织子目录，如：
- `controller/article/ArticleController.php`
- `logic/article/ArticleLogic.php`
- `model/article/Article.php`

## 4. 数据库操作规范

### 4.1 模型规范
```php
class Article extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
    protected $name = 'article'; // 可选，指定表名

    // 关联关系
    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'cid');
    }

    // 获取器
    public function getImageAttr($value): string
    {
        return trim($value) ? FileService::getFileUrl($value) : '';
    }

    // 搜索器
    public function searchKeywordAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('title', 'like', '%' . $value . '%');
        }
    }
}
```

### 4.2 数据库查询规范
- 优先使用模型方法而非原生SQL
- 使用搜索器处理复杂查询条件
- 软删除优于物理删除

### 4.3 事务处理
```php
use think\facade\Db;

Db::startTrans();
try {
    // 业务逻辑
    Db::commit();
    return true;
} catch (\Exception $e) {
    Db::rollback();
    self::setError($e->getMessage());
    return false;
}
```

## 5. API开发规则

### 5.1 统一响应格式
必须使用 `JsonService` 类处理API响应：
```php
// 成功响应
return $this->success('操作成功', $data, 1, 1);

// 失败响应
return $this->fail('操作失败', [], 0, 1);

// 数据响应
return $this->data($data);

// 列表响应
return $this->dataLists(new ArticleLists());
```

### 5.2 控制器规范
```php
class ArticleController extends BaseApiController
{
    // 免登录接口定义
    public array $notNeedLogin = ['lists', 'detail'];

    public function lists()
    {
        return $this->dataLists(new ArticleLists());
    }

    public function add()
    {
        $params = (new ArticleValidate())->post()->goCheck('add');
        $result = ArticleLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(ArticleLogic::getError());
    }
}
```

### 5.3 验证器使用
```php
// 场景验证
$params = (new ArticleValidate())->post()->goCheck('add');

// 验证器定义
class ArticleValidate extends BaseValidate
{
    protected $rule = [
        'title' => 'require|max:100',
        'content' => 'require',
    ];

    public function sceneAdd()
    {
        return $this->only(['title', 'content']);
    }
}
```

## 6. 前端开发规范

### 6.1 Vue 3 组件开发
```vue
<template>
  <div class="user-list">
    <el-table :data="pager.lists" v-loading="pager.loading">
      <el-table-column prop="name" label="用户名" />
      <el-table-column prop="account" label="账号" />
      <el-table-column label="操作">
        <template #default="{ row }">
          <el-button @click="handleEdit(row)">编辑</el-button>
          <el-button @click="handleDelete(row.id)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script lang="ts" setup name="UserList">
import { userList, userDelete } from '@/api/user'
import { usePaging } from '@/hooks/usePaging'

const { pager, getLists, resetPage } = usePaging({
  fetchFun: userList
})

const handleEdit = (row: any) => {
  // 编辑逻辑
}

const handleDelete = async (id: number) => {
  await userDelete({ id })
  getLists()
}
</script>
```

### 6.2 API 接口调用
```typescript
// api/user.ts
import request from '@/utils/request'

export interface UserListParams {
  page?: number
  limit?: number
  name?: string
}

export interface UserInfo {
  id: number
  name: string
  account: string
  avatar: string
}

/**
 * 获取用户列表
 */
export function userList(params: UserListParams) {
  return request.get({ url: '/user/lists', params })
}

/**
 * 添加用户
 */
export function userAdd(params: Partial<UserInfo>) {
  return request.post({ url: '/user/add', params })
}

/**
 * 编辑用户
 */
export function userEdit(params: UserInfo) {
  return request.post({ url: '/user/edit', params })
}

/**
 * 删除用户
 */
export function userDelete(params: { id: number }) {
  return request.post({ url: '/user/delete', params })
}
```

### 6.3 状态管理 (Pinia)
```typescript
// stores/user.ts
import { defineStore } from 'pinia'
import { getUserInfo } from '@/api/user'

interface UserState {
  userInfo: any
  token: string
  permissions: string[]
}

export const useUserStore = defineStore('user', {
  state: (): UserState => ({
    userInfo: {},
    token: '',
    permissions: []
  }),

  getters: {
    isLogin: (state) => !!state.token,
    hasPermission: (state) => (perm: string) => state.permissions.includes(perm)
  },

  actions: {
    async getUserInfo() {
      const data = await getUserInfo()
      this.userInfo = data
    },

    setToken(token: string) {
      this.token = token
    },

    logout() {
      this.token = ''
      this.userInfo = {}
      this.permissions = []
    }
  }
})
```

### 6.4 路由配置
```typescript
// router/modules/user.ts
export default {
  path: '/user',
  component: () => import('@/layout/index.vue'),
  meta: {
    title: '用户管理',
    icon: 'user'
  },
  children: [
    {
      path: 'list',
      name: 'UserList',
      component: () => import('@/views/user/list.vue'),
      meta: {
        title: '用户列表',
        perms: 'user:list'
      }
    },
    {
      path: 'add',
      name: 'UserAdd',
      component: () => import('@/views/user/edit.vue'),
      meta: {
        title: '添加用户',
        perms: 'user:add'
      }
    }
  ]
}
```

## 7. 错误处理规范

### 7.1 异常处理层级
1. **验证异常**: `ValidateException` - 参数验证失败
2. **业务异常**: Logic层使用 `setError()/getError()`
3. **系统异常**: `ExceptionHandle` 统一处理

### 7.2 错误信息规范
```php
// Logic层错误处理
public static function add($params)
{
    if (empty($params['title'])) {
        self::setError('标题不能为空');
        return false;
    }
    // 业务逻辑...
    return true;
}

// 控制器中获取错误
if (false === $result) {
    return $this->fail(ArticleLogic::getError());
}
```

### 7.3 中间件错误处理
```php
// 直接返回JSON响应
return JsonService::fail('登录超时，请重新登录', [], -1, 0);
```

## 8. 依赖管理规则

### 8.1 后端依赖管理 (Composer)
- 所有PHP依赖必须通过Composer管理
- 禁止手动修改 `composer.json`，使用命令行操作：
  ```bash
  composer require package/name
  composer remove package/name
  composer update
  ```

**核心依赖**:
- `topthink/framework`: ThinkPHP核心框架
- `topthink/think-orm`: ORM组件
- `topthink/think-multi-app`: 多应用支持
- `topthink/think-view`: 视图组件

**第三方服务集成**:
- 微信生态: `w7corp/easywechat`
- 支付: `alipaysdk/easysdk`
- 云存储: `qiniu/php-sdk`, `qcloud/cos-sdk-v5`, `aliyuncs/oss-sdk-php`
- 短信: `tencentcloud/sms`

### 8.2 前端依赖管理 (NPM)
- 所有前端依赖必须通过NPM管理
- 禁止手动修改 `package.json`，使用命令行操作：
  ```bash
  npm install package-name
  npm uninstall package-name
  npm update
  ```

**核心依赖**:
- `vue`: Vue 3 框架
- `element-plus`: UI组件库
- `pinia`: 状态管理
- `vue-router`: 路由管理
- `axios`: HTTP请求库
- `vite`: 构建工具

## 9. 测试和调试规范

### 9.1 代码生成器
- 使用项目内置的代码生成器生成基础代码
- 生成的代码包含: Controller、Model、Logic、Validate、Lists
- 生成路径: `runtime/generate/` 或直接生成到模块

### 9.2 演示模式（没什么用，尽量不使用）
- 开发时启用演示模式进行测试
- 使用 `CheckDemoMiddleware` 限制数据修改
- 使用 `EncryDemoDataMiddleware` 加密敏感数据

### 9.3 调试工具
**后端调试**:
- 使用 `symfony/var-dumper` 进行调试
- 启用 `think-trace` 进行性能分析
- 数据库查询日志通过 `trigger_sql` 配置

**前端调试**:
- 使用 Vue DevTools 进行组件调试
- 使用浏览器开发者工具进行网络请求调试
- 使用 ESLint 进行代码质量检查

## 10. 部署和环境配置

### 10.1 环境要求
**服务端**:
- PHP >= 8.0
- MySQL >= 5.7
- Nginx 或 Apache

**前端**:
- Node.js >= 14.18.1

### 10.2 部署方式
1. **宝塔面板部署** (推荐)
2. **PhpStudy部署** (开发环境)
3. **Docker部署** (容器化)
4. **通用部署** (手动配置)

### 10.3 项目配置
- 项目入口: `server/public/index.php`
- 管理后台访问: `http://域名/admin`
- API接口前缀: `adminapi` (管理端), `api` (用户端)

### 10.4 当前开发环境
- **部署方式**: PhpStudy部署
- **访问域名**: www.lineuk.com
- **数据库操作**: 可直接使用命令行操作（环境变量已配置）
- **管理后台**: http://www.lineuk.com/admin
- **API接口**: http://www.lineuk.com/adminapi/ (管理端), http://www.lineuk.com/api/ (用户端)

### 10.5 前端开发环境
- **前端启动**: 每次开发时，开发者会自行启动前端开发服务器
- **启动命令**: 在 `admin` 目录下执行 `npm run dev`
- **开发端口**: 通常运行在 `http://localhost:3000` 或类似端口
- **热重载**: 支持代码修改后自动刷新
- **代理配置**: 前端开发服务器会代理API请求到后端服务器

## 11. 开发日志管理规范

### 11.1 开发日志文件
- **文件位置**: 项目根目录下的 `DEVELOPMENT_LOG.md` 文件
- **作用**: 记录 likeadmin 项目的所有开发活动和历史记录
- **重要性**: 确保团队协作效率和项目可维护性

### 11.2 开发前准备
- **必读要求**: 在进行任何开发工作前，必须先阅读 `DEVELOPMENT_LOG.md` 文件
- **了解内容**:
  - 项目历史开发记录
  - 当前项目状态
  - 已实现的功能模块
  - 已知问题和解决方案
  - 技术决策记录

### 11.3 开发日志记录规范
每次完成开发任务后，必须及时更新 `DEVELOPMENT_LOG.md` 文件，记录以下内容：

**必记录项目**:
- **开发时间**: 具体的开发日期和时间
- **功能模块**: 开发的具体功能和业务模块
- **修改文件**: 详细的文件修改清单（新增、修改、删除）
- **技术决策**: 重要的架构选择和技术方案决策
- **问题解决**: 遇到的问题描述和具体解决方案
- **测试结果**: 功能测试、联调测试的结果
- **部署情况**: 部署状态和访问验证结果

**记录格式**:
```markdown
## YYYY-MM-DD HH:MM - 功能模块名称

### 开发内容
- 具体实现的功能描述

### 文件变更
- 新增文件:
- 修改文件:
- 删除文件:

### 技术决策
- 重要的技术选择和原因

### 问题与解决
- 问题描述:
- 解决方案:

### 测试与部署
- 测试结果:
- 部署状态:
- 访问验证:
```

### 11.4 文档维护要求
- **结构化**: 保持时间顺序和清晰的层级结构
- **及时性**: 开发完成后立即更新，不得延迟
- **完整性**: 确保记录信息的完整性和准确性
- **可读性**: 使用清晰的语言和格式，便于团队成员理解

## 12. 开发最佳实践

### 12.1 全栈开发流程
1. **开发前准备**: 阅读 `DEVELOPMENT_LOG.md` 了解项目状态
2. **需求分析**: 明确功能需求和业务逻辑
3. **数据库设计**: 创建/修改数据表结构
4. **后端开发**:
   - 创建Model（继承BaseModel）
   - 实现Logic业务逻辑
   - 创建Controller（继承对应基类）
   - 创建Validate验证器
   - 实现Lists列表类
   - 配置路由和权限
5. **前端开发**:
   - 创建API接口调用函数
   - 实现页面组件和视图
   - 配置路由和权限
   - 集成后端API
6. **联调测试**: 前后端联调和功能测试
7. **文档更新**: 更新 `DEVELOPMENT_LOG.md` 记录开发过程
8. **部署上线**: 测试环境验证后部署生产环境

### 12.2 代码复用策略
- 公共逻辑放在 `app/common/` 目录
- 使用Trait复用代码片段
- 服务类处理复杂业务逻辑
- 前端公共组件放在 `src/components/` 目录
- 使用Composables复用逻辑

### 12.3 性能优化
**后端优化**:
- 合理使用缓存机制
- 数据库查询优化
- 避免N+1查询问题
- 使用队列处理耗时任务

**前端优化**:
- 组件懒加载
- 图片懒加载
- 合理使用缓存
- 减少不必要的API请求

### 12.4 安全规范
**后端安全**:
- 所有用户输入必须经过验证
- 使用参数绑定防止SQL注入
- 敏感操作需要权限验证
- 文件上传需要类型和大小限制

**前端安全**:
- 输入数据验证和过滤
- XSS防护
- CSRF防护
- 敏感信息不在前端存储

---

**重要提醒**:
1. **全栈开发**: 默认情况下，所有功能都需要前后端配套实现
2. **架构一致性**: 始终遵循现有的架构模式和编码规范
3. **代码质量**: 所有新增功能都应该经过充分测试
4. **文档更新**: 重要功能变更需要更新相关文档
5. **版本控制**: 合理使用Git进行版本管理和协作开发