# 运营管理API文档

## 概述

运营管理功能提供了完整的运营信息管理接口，包括运营的增删改查、列表筛选、搜索等功能。所有接口都需要管理员权限认证，并实现了层级权限控制。

**基础信息**
- 基础路径: `/adminapi`
- 认证方式: Bearer Token (管理员登录后获取)
- 数据格式: JSON
- 字符编码: UTF-8

**层级权限控制**
- 管理员只能查看和操作自己的下级运营
- root用户可以查看和操作所有运营
- 添加运营时，自动将添加者设置为新运营的上级
- 不支持修改运营的上级关系

**创建权限控制**
- 只有"租户"角色才能创建运营
- root用户也可以创建运营
- 代理商和平台管理员无法创建运营

## 接口列表

### 1. 运营列表

#### GET /adminapi/auth.operator/lists

获取运营列表，支持分页、搜索和排序。

**请求参数**

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| page | int | 否 | 页码，默认1 | 1 |
| limit | int | 否 | 每页数量，默认15 | 15 |
| name | string | 否 | 运营姓名搜索 | 张三 |
| account | string | 否 | 账号搜索 | operator001 |

**响应示例**

```json
{
    "code": 1,
    "msg": "成功",
    "data": {
        "lists": [
            {
                "id": 10,
                "name": "张三",
                "account": "operator001",
                "avatar": "http://example.com/avatar.jpg",
                "disable": 0,
                "disable_desc": "正常",
                "multipoint_login": 1,
                "create_time": "2024-08-24 10:00:00",
                "login_time": "2024-08-24 15:30:00",
                "login_ip": "192.168.1.100",
                "role_name": "运营",
                "parent_name": "李四(租户)"
            }
        ],
        "count": 1,
        "page_no": 1,
        "page_size": 15
    },
    "show": 1,
    "exit": 1
}
```

### 2. 添加运营

#### POST /adminapi/auth.operator/add

创建新的运营账号。只有租户才能执行此操作。

**请求参数**

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| account | string | 是 | 账号，1-32位字符 | operator001 |
| name | string | 是 | 姓名，1-16位字符 | 张三 |
| password | string | 是 | 密码，6-32位字符 | 123456 |
| password_confirm | string | 是 | 确认密码 | 123456 |
| multipoint_login | int | 是 | 是否支持多处登录，0=否，1=是 | 1 |
| avatar | string | 否 | 头像URL | /uploads/avatar.jpg |

**请求示例**

```json
{
    "account": "operator001",
    "name": "张三",
    "password": "123456",
    "password_confirm": "123456",
    "multipoint_login": 1,
    "avatar": "/uploads/avatar.jpg"
}
```

**响应示例**

```json
{
    "code": 1,
    "msg": "操作成功",
    "data": [],
    "show": 1,
    "exit": 1
}
```

### 3. 编辑运营

#### POST /adminapi/auth.operator/edit

修改运营信息。只能编辑自己的下级运营。

**请求参数**

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| id | int | 是 | 运营ID | 10 |
| account | string | 是 | 账号，1-32位字符 | operator001 |
| name | string | 是 | 姓名，1-16位字符 | 张三 |
| password | string | 否 | 密码，6-32位字符，不填则不修改 | 123456 |
| password_confirm | string | 否 | 确认密码 | 123456 |
| disable | int | 是 | 状态，0=正常，1=禁用 | 0 |
| multipoint_login | int | 是 | 是否支持多处登录，0=否，1=是 | 1 |
| avatar | string | 否 | 头像URL | /uploads/avatar.jpg |

**请求示例**

```json
{
    "id": 10,
    "account": "operator001",
    "name": "张三",
    "disable": 0,
    "multipoint_login": 1,
    "avatar": "/uploads/avatar.jpg"
}
```

**响应示例**

```json
{
    "code": 1,
    "msg": "操作成功",
    "data": [],
    "show": 1,
    "exit": 1
}
```

### 4. 删除运营

#### POST /adminapi/auth.operator/delete

删除运营账号。只能删除自己的下级运营，且该运营下不能有下级管理员。

**请求参数**

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| id | int | 是 | 运营ID | 10 |

**请求示例**

```json
{
    "id": 10
}
```

**响应示例**

```json
{
    "code": 1,
    "msg": "操作成功",
    "data": [],
    "show": 1,
    "exit": 1
}
```

### 5. 运营详情

#### GET /adminapi/auth.operator/detail

获取运营详细信息。只能查看自己的下级运营。

**请求参数**

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| id | int | 是 | 运营ID | 10 |

**响应示例**

```json
{
    "code": 1,
    "msg": "成功",
    "data": {
        "id": 10,
        "account": "operator001",
        "name": "张三",
        "avatar": "http://example.com/avatar.jpg",
        "disable": 0,
        "root": 0,
        "multipoint_login": 1,
        "parent_id": 5,
        "parent_name": "李四(租户)"
    },
    "show": 1,
    "exit": 1
}
```

## 错误码说明

### 通用错误码

| 错误码 | 说明 |
|--------|------|
| 0 | 操作失败 |
| 1 | 操作成功 |
| -1 | 登录超时，请重新登录 |

### 业务错误信息

| 错误信息 | 说明 | 解决方案 |
|----------|------|----------|
| 创建运营只能由租户执行 | 当前用户不是租户角色 | 使用租户账号登录 |
| 您没有权限编辑该运营 | 尝试编辑非下级运营 | 只能编辑自己的下级 |
| 您没有权限删除该运营 | 尝试删除非下级运营 | 只能删除自己的下级 |
| 您没有权限查看该运营详情 | 尝试查看非下级运营 | 只能查看自己的下级 |
| 该运营下还有下级管理员，无法删除 | 运营下有下级 | 先删除或转移下级 |
| 账号已存在 | 账号重复 | 使用其他账号 |
| 运营不存在 | 运营ID无效 | 检查运营ID |
| 该管理员不是运营 | 指定的管理员不是运营角色 | 确认目标是运营 |

## 权限说明

### 创建权限
- **租户权限**：只有租户（角色ID=2）才能创建运营
- **root权限**：超级管理员也可以创建运营
- **自动上级**：创建者自动成为新运营的上级

### 操作权限
- **层级控制**：只能操作自己的下级运营
- **root特权**：超级管理员可以操作所有运营
- **权限验证**：每个操作都会验证层级权限

### 查看权限
- **下级可见**：只能查看自己的下级运营
- **递归查询**：包含下级的下级（多层级）
- **权限缓存**：使用缓存提高查询效率

## 使用示例

### 1. 租户创建运营

```javascript
// 1. 租户登录获取token
const loginResponse = await fetch('/adminapi/login/account', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        account: 'tenant001',
        password: '123456'
    })
});

const { data: { token } } = await loginResponse.json();

// 2. 创建运营
const createResponse = await fetch('/adminapi/auth.operator/add', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
        account: 'operator001',
        name: '张三',
        password: '123456',
        password_confirm: '123456',
        multipoint_login: 1
    })
});
```

### 2. 查询运营列表

```javascript
const response = await fetch('/adminapi/auth.operator/lists?page=1&limit=10&name=张', {
    headers: {
        'Authorization': `Bearer ${token}`
    }
});

const { data } = await response.json();
console.log('运营列表:', data.lists);
```

### 3. 编辑运营信息

```javascript
const response = await fetch('/adminapi/auth.operator/edit', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
        id: 10,
        account: 'operator001',
        name: '张三(已修改)',
        disable: 0,
        multipoint_login: 1
    })
});
```

## 注意事项

1. **权限验证**：所有接口都需要登录认证，并验证层级权限
2. **数据安全**：删除操作是真实删除，无法恢复
3. **角色一致性**：系统会自动维护运营角色的一致性
4. **缓存更新**：操作后会自动清理相关权限缓存
5. **事务处理**：所有写操作都使用数据库事务确保一致性

运营管理API为系统提供了完整的运营管理能力，通过严格的权限控制确保了数据安全和操作规范。
