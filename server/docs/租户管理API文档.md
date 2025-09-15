# 租户管理API文档

## 概述

租户管理功能提供了完整的租户信息管理接口，包括租户的增删改查、列表筛选、搜索等功能。所有接口都需要管理员权限认证，并实现了层级权限控制。

**基础信息**
- 基础路径: `/adminapi`
- 认证方式: Bearer Token (管理员登录后获取)
- 数据格式: JSON
- 字符编码: UTF-8

**层级权限控制**
- 管理员只能查看和操作自己的下级租户
- root用户可以查看和操作所有租户
- 添加租户时，自动将添加者设置为新租户的上级
- 不支持修改租户的上级关系

**创建权限控制**
- 只有"代理商"角色才能创建租户
- root用户也可以创建租户
- 平台管理员无法创建租户

## 接口列表

### 1. 租户列表

#### GET /adminapi/auth.tenant/lists

获取租户列表，支持分页、搜索和排序。

**请求参数**

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| page | int | 否 | 页码，默认1 | 1 |
| limit | int | 否 | 每页数量，默认15 | 15 |
| name | string | 否 | 租户姓名搜索 | 张三 |
| account | string | 否 | 账号搜索 | tenant001 |

**响应示例**

```json
{
    "code": 1,
    "msg": "成功",
    "data": {
        "lists": [
            {
                "id": 38,
                "name": "张三租户",
                "account": "tenant_zhangsan",
                "create_time": "2024-08-24 10:30:00",
                "disable": 0,
                "disable_desc": "正常",
                "multipoint_login": 1,
                "role_name": "租户",
                "parent_name": "代理商A"
            }
        ],
        "count": 1,
        "page_no": 1,
        "page_size": 15
    }
}
```

**响应字段说明**

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 租户ID |
| name | string | 租户姓名 |
| account | string | 登录账号 |
| create_time | string | 创建时间 |
| disable | int | 状态 0-启用 1-禁用 |
| disable_desc | string | 状态描述 |
| multipoint_login | int | 多点登录 0-不支持 1-支持 |
| role_name | string | 角色名称，固定为"租户" |
| parent_name | string | 上级姓名 |

### 2. 添加租户

#### POST /adminapi/auth.tenant/add

创建新的租户账号。

- **接口描述**: 创建新的租户账号（仅限代理商）
- **权限要求**: 需要代理商角色或root用户

**请求参数**

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| name | string | 是 | 租户姓名，1-16字符 | 张三租户 |
| account | string | 是 | 登录账号，1-32字符，系统唯一 | tenant_zhangsan |
| password | string | 是 | 登录密码，6-32字符 | 123456 |
| password_confirm | string | 是 | 确认密码，必须与password一致 | 123456 |
| avatar | string | 否 | 头像URL | /uploads/avatar.jpg |
| multipoint_login | int | 否 | 多点登录 0-不支持 1-支持，默认1 | 1 |

**请求示例**

```json
{
    "name": "张三租户",
    "account": "tenant_zhangsan",
    "password": "123456",
    "password_confirm": "123456",
    "multipoint_login": 1
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

### 3. 编辑租户

#### POST /adminapi/auth.tenant/edit

编辑租户信息。

- **接口描述**: 编辑租户基本信息
- **权限要求**: 只能编辑自己的下级租户

**请求参数**

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| id | int | 是 | 租户ID | 38 |
| name | string | 是 | 租户姓名，1-16字符 | 李四租户 |
| account | string | 是 | 登录账号，1-32字符，系统唯一 | tenant_lisi |
| password | string | 否 | 登录密码，6-32字符，不填则不修改 | 654321 |
| password_confirm | string | 否 | 确认密码，修改密码时必填 | 654321 |
| avatar | string | 否 | 头像URL | /uploads/avatar2.jpg |
| disable | int | 是 | 状态 0-启用 1-禁用 | 0 |
| multipoint_login | int | 是 | 多点登录 0-不支持 1-支持 | 1 |

**请求示例**

```json
{
    "id": 38,
    "name": "李四租户",
    "account": "tenant_lisi",
    "disable": 0,
    "multipoint_login": 1
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

### 4. 删除租户

#### POST /adminapi/auth.tenant/delete

删除指定的租户（真实删除，不可恢复）。

- **接口描述**: 删除租户（真实删除，不可恢复）
- **权限要求**: 只能删除自己的下级租户

**请求参数**

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| id | int | 是 | 租户ID | 38 |

**请求示例**

```json
{
    "id": 38
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

### 5. 租户详情

#### GET /adminapi/auth.tenant/detail

获取租户详细信息。

- **接口描述**: 获取租户详细信息
- **权限要求**: 只能查看自己的下级租户

**请求参数**

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| id | int | 是 | 租户ID | 38 |

**响应示例**

```json
{
    "code": 1,
    "msg": "成功",
    "data": {
        "id": 38,
        "account": "tenant_zhangsan",
        "name": "张三租户",
        "avatar": "http://example.com/avatar.jpg",
        "disable": 0,
        "root": 0,
        "multipoint_login": 1,
        "parent_id": 28,
        "parent_name": "代理商A"
    }
}
```

**响应字段说明**

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 租户ID |
| account | string | 登录账号 |
| name | string | 租户姓名 |
| avatar | string | 头像URL |
| disable | int | 状态 0-启用 1-禁用 |
| root | int | 是否超级管理员 0-否 1-是 |
| multipoint_login | int | 多点登录 0-不支持 1-支持 |
| parent_id | int | 上级管理员ID |
| parent_name | string | 上级姓名 |

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 0 | 操作失败 |
| 1 | 操作成功 |

## 常见错误信息

| 错误信息 | 说明 | 解决方案 |
|----------|------|----------|
| 创建租户只能由代理商执行 | 非代理商尝试创建租户 | 确保操作者具有代理商角色 |
| 您没有权限编辑该租户 | 尝试编辑非下级租户 | 只能编辑自己的下级租户 |
| 您没有权限删除该租户 | 尝试删除非下级租户 | 只能删除自己的下级租户 |
| 账号已存在 | 账号重复 | 使用不同的账号 |
| 该租户还有下级，无法删除 | 租户有下级管理员 | 先删除下级管理员 |

## 业务规则

1. **角色限制**: 只有代理商角色才能创建租户
2. **层级关系**: 租户的上级自动设置为创建者
3. **权限边界**: 管理员只能操作自己的下级租户
4. **数据安全**: 删除操作为真实删除，数据不可恢复
5. **唯一性**: 账号在系统中必须唯一，姓名可以重复
6. **真实删除**: 删除租户时使用真实删除，确保数据彻底清理

## 注意事项

1. 所有接口都需要在请求头中携带有效的管理员Token
2. 创建租户时会自动分配租户角色，无需手动指定
3. 租户的上级关系一旦确定，无法通过编辑接口修改
4. 删除操作不可逆，请谨慎操作
5. 账号具有全局唯一性，不能与其他管理员账号重复
