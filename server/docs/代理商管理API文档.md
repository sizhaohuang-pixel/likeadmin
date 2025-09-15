# 代理商管理 API 文档

## 概述

代理商管理功能提供了完整的代理商信息管理接口，包括代理商的增删改查、列表筛选、搜索等功能。所有接口都需要管理员权限认证，并实现了层级权限控制。

**基础信息**
- 基础路径: `/adminapi`
- 认证方式: Bearer Token (管理员登录后获取)
- 数据格式: JSON
- 字符编码: UTF-8

**层级权限控制**
- 管理员只能查看和操作自己的下级代理商
- root用户可以查看和操作所有代理商
- 添加代理商时，自动将添加者设置为新代理商的上级
- 不支持修改代理商的上级关系

**创建权限控制**
- 只有"平台管理员"角色才能创建代理商
- root用户也可以创建代理商
- 普通代理商无法创建新的代理商

---

## 1. 代理商列表

### 接口信息
- **接口地址**: `GET /adminapi/auth.agent/lists`
- **接口描述**: 获取代理商列表，支持分页、搜索、排序
- **权限要求**: 需要管理员登录

### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| page | int | 否 | 页码，默认1 | 1 |
| limit | int | 否 | 每页数量，默认25 | 25 |
| name | string | 否 | 按姓名搜索 | 张三 |
| account | string | 否 | 按账号搜索 | agent001 |
| sort_field | string | 否 | 排序字段 | create_time |
| sort_order | string | 否 | 排序方向 asc/desc | desc |

### 请求示例

```bash
GET /adminapi/auth.agent/lists?page=1&limit=10&name=张三
Authorization: Bearer your_admin_token
```

### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| code | int | 状态码，1成功，0失败 |
| msg | string | 响应消息 |
| data | object | 响应数据 |
| data.lists | array | 代理商列表 |
| data.count | int | 总数量 |
| data.page_no | int | 当前页码 |
| data.page_size | int | 每页数量 |

#### 代理商信息字段

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 代理商ID |
| name | string | 代理商姓名 |
| account | string | 登录账号 |
| avatar | string | 头像URL |
| create_time | string | 创建时间 |
| login_time | string | 最后登录时间 |
| login_ip | string | 最后登录IP |
| disable | int | 状态 0-启用 1-禁用 |
| disable_desc | string | 状态描述 |
| multipoint_login | int | 多点登录 0-不支持 1-支持 |
| role_name | string | 角色名称，固定为"代理" |
| parent_name | string | 上级姓名 |

### 响应示例

```json
{
    "code": 1,
    "msg": "成功",
    "data": {
        "lists": [
            {
                "id": 2,
                "name": "张三代理商",
                "account": "agent_zhangsan",
                "avatar": "http://example.com/avatar.jpg",
                "create_time": "2024-08-24 15:30:00",
                "login_time": "2024-08-24 16:00:00",
                "login_ip": "192.168.1.100",
                "disable": 0,
                "disable_desc": "正常",
                "multipoint_login": 1,
                "role_name": "代理",
                "parent_name": "admin"
            }
        ],
        "count": 1,
        "page_no": 1,
        "page_size": 25
    }
}
```

---

## 2. 添加代理商

### 接口信息
- **接口地址**: `POST /adminapi/auth.agent/add`
- **接口描述**: 创建新的代理商账号（仅限平台管理员）
- **权限要求**: 需要平台管理员角色或root用户

### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| name | string | 是 | 代理商姓名，1-16字符 | 张三代理商 |
| account | string | 是 | 登录账号，1-32字符，系统唯一 | agent_zhangsan |
| password | string | 是 | 登录密码，6-32字符 | 123456 |
| password_confirm | string | 是 | 确认密码，必须与password一致 | 123456 |
| avatar | string | 否 | 头像文件路径 | /uploads/avatar.jpg |
| disable | int | 否 | 状态，0-启用 1-禁用，默认0 | 0 |
| multipoint_login | int | 否 | 多点登录，0-不支持 1-支持，默认1 | 1 |

### 请求示例

```bash
POST /adminapi/auth.agent/add
Authorization: Bearer your_admin_token
Content-Type: application/json

{
    "name": "张三代理商",
    "account": "agent_zhangsan",
    "password": "123456",
    "password_confirm": "123456",
    "disable": 0,
    "multipoint_login": 1
}
```

### 响应示例

```json
{
    "code": 1,
    "msg": "操作成功",
    "data": [],
    "count": 1,
    "show": 1
}
```

---

## 3. 编辑代理商

### 接口信息
- **接口地址**: `POST /adminapi/auth.agent/edit`
- **接口描述**: 修改代理商信息
- **权限要求**: 需要管理员登录

### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| id | int | 是 | 代理商ID | 2 |
| name | string | 是 | 代理商姓名，1-16字符 | 李四代理商 |
| account | string | 是 | 登录账号，1-32字符，系统唯一 | agent_lisi |
| password | string | 否 | 新密码，6-32字符，不填则不修改 | 654321 |
| password_confirm | string | 否 | 确认密码，填写password时必填 | 654321 |
| avatar | string | 否 | 头像文件路径 | /uploads/new_avatar.jpg |
| disable | int | 是 | 状态，0-启用 1-禁用 | 0 |
| multipoint_login | int | 是 | 多点登录，0-不支持 1-支持 | 1 |

### 请求示例

```bash
POST /adminapi/auth.agent/edit
Authorization: Bearer your_admin_token
Content-Type: application/json

{
    "id": 2,
    "name": "李四代理商",
    "account": "agent_lisi",
    "disable": 0,
    "multipoint_login": 1
}
```

### 响应示例

```json
{
    "code": 1,
    "msg": "操作成功",
    "data": [],
    "count": 1,
    "show": 1
}
```

---

## 4. 删除代理商

### 接口信息
- **接口地址**: `POST /adminapi/auth.agent/delete`
- **接口描述**: 删除代理商（真实删除，不可恢复）
- **权限要求**: 需要管理员登录

### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| id | int | 是 | 代理商ID | 2 |

### 请求示例

```bash
POST /adminapi/auth.agent/delete
Authorization: Bearer your_admin_token
Content-Type: application/json

{
    "id": 2
}
```

### 响应示例

```json
{
    "code": 1,
    "msg": "操作成功",
    "data": [],
    "count": 1,
    "show": 1
}
```

---

## 5. 代理商详情

### 接口信息
- **接口地址**: `GET /adminapi/auth.agent/detail`
- **接口描述**: 获取代理商详细信息
- **权限要求**: 需要管理员登录

### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| id | int | 是 | 代理商ID | 2 |

### 请求示例

```bash
GET /adminapi/auth.agent/detail?id=2
Authorization: Bearer your_admin_token
```

### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| code | int | 状态码，1成功，0失败 |
| msg | string | 响应消息 |
| data | object | 代理商详细信息 |

#### 代理商详情字段

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 代理商ID |
| name | string | 代理商姓名 |
| account | string | 登录账号 |
| avatar | string | 头像URL |
| disable | int | 状态 0-启用 1-禁用 |
| root | int | 是否超级管理员 0-否 1-是 |
| multipoint_login | int | 多点登录 0-不支持 1-支持 |
| parent_id | int | 上级管理员ID |
| parent_name | string | 上级姓名 |

### 响应示例

```json
{
    "code": 1,
    "msg": "成功",
    "data": {
        "id": 2,
        "name": "张三代理商",
        "account": "agent_zhangsan",
        "avatar": "http://example.com/avatar.jpg",
        "disable": 0,
        "root": 0,
        "multipoint_login": 1,
        "parent_id": 1,
        "parent_name": "admin"
    }
}
```

---

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 0 | 操作失败 |
| 1 | 操作成功 |
| -1 | 登录超时，请重新登录 |

## 常见错误响应

### 参数验证失败
```json
{
    "code": 0,
    "msg": "账号不能为空",
    "data": [],
    "count": 0,
    "show": 1
}
```

### 权限不足
```json
{
    "code": 0,
    "msg": "登录超时，请重新登录",
    "data": [],
    "count": -1,
    "show": 0
}
```

### 业务逻辑错误
```json
{
    "code": 0,
    "msg": "该管理员不是代理商",
    "data": [],
    "count": 0,
    "show": 1
}
```

---

## 注意事项

1. **权限控制**: 所有接口都需要管理员登录认证
2. **角色限制**: 代理商只能具有代理角色，系统会自动分配
3. **数据安全**: 删除操作为真实删除，数据不可恢复
4. **密码安全**: 密码会自动加密存储
5. **唯一性**: 账号在系统中必须唯一，姓名可以重复
6. **缓存清理**: 修改操作会自动清理相关缓存

## SDK示例

### JavaScript (Axios)

```javascript
// 获取代理商列表
const getAgentList = async (params = {}) => {
    try {
        const response = await axios.get('/adminapi/auth.agent/lists', {
            params,
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        return response.data;
    } catch (error) {
        console.error('获取代理商列表失败:', error);
    }
};

// 添加代理商
const addAgent = async (agentData) => {
    try {
        const response = await axios.post('/adminapi/auth.agent/add', agentData, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        return response.data;
    } catch (error) {
        console.error('添加代理商失败:', error);
    }
};
```

### PHP (cURL)

```php
// 获取代理商列表
function getAgentList($token, $params = []) {
    $url = '/adminapi/auth.agent/lists?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// 添加代理商
function addAgent($token, $agentData) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, '/adminapi/auth.agent/add');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($agentData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
```
