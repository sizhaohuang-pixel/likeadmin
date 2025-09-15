# 代理商套餐分配系统API接口文档

## 📋 接口概览

### **基础信息**
- **基础URL**: `http://your-domain/adminapi`
- **认证方式**: Bearer Token
- **请求格式**: JSON
- **响应格式**: JSON
- **字符编码**: UTF-8

### **统一响应格式**
```json
{
  "code": 1,           // 状态码：1-成功，0-失败，-1-登录失效
  "msg": "操作成功",    // 提示信息
  "data": {},          // 响应数据
  "show": 1            // 是否显示提示：1-显示，0-不显示
}
```

## 🔥 核心功能接口

### 1. 分配套餐

**接口地址**: `POST /package/assign`  
**权限标识**: `package/assign`  
**适用角色**: 代理商

#### 请求参数
```json
{
  "tenant_id": 123,        // 租户ID，必填，正整数
  "port_count": 100,       // 端口数量，必填，1-10000
  "expire_days": 30,       // 有效天数，必填，1-3650
  "remark": "测试分配"     // 备注，可选，最大255字符
}
```

#### 响应示例
```json
{
  "code": 1,
  "msg": "套餐分配成功",
  "data": {},
  "show": 1
}
```

#### 错误示例
```json
{
  "code": 0,
  "msg": "您只能为自己的下级租户分配套餐",
  "data": {},
  "show": 1
}
```

---

### 2. 查询租户套餐

**接口地址**: `GET /package/tenant-packages`  
**权限标识**: `package/tenant-packages`  
**适用角色**: 代理商、租户

#### 请求参数
```
tenant_id=123    // 租户ID，必填，正整数
```

#### 响应示例
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "port_pool_status": {
      "total_ports": 500,        // 总端口数
      "used_ports": 200,         // 已用端口数
      "available_ports": 300,    // 可用端口数
      "expiring_soon": 50        // 即将过期端口数（7天内）
    },
    "packages": [
      {
        "id": 1,
        "agent_name": "代理商A",
        "port_count": 200,
        "assign_time_text": "2025-08-27 10:00:00",
        "expire_time_text": "2025-09-26 10:00:00",
        "status_text": "有效",
        "remaining_days": 30,
        "remark": "测试套餐"
      }
    ],
    "assigned_accounts": [
      {
        "alt_account_id": 1001,
        "operator_name": "客服A",
        "assign_time_text": "2025-08-27 11:00:00"
      }
    ]
  },
  "show": 0
}
```

---

### 3. 分配小号

**接口地址**: `POST /package/assign-alt-account`  
**权限标识**: `package/assign-alt-account`  
**适用角色**: 租户

#### 请求参数
```json
{
  "alt_account_ids": [1001, 1002, 1003],  // 小号ID数组，必填，最多1000个
  "operator_id": 456                      // 客服ID，必填，正整数
}
```

#### 响应示例
```json
{
  "code": 1,
  "msg": "小号分配成功",
  "data": {},
  "show": 1
}
```

#### 错误示例
```json
{
  "code": 0,
  "msg": "端口不足，当前可用端口：2个，需要：3个",
  "data": {},
  "show": 1
}
```

---

### 4. 检查端口可用性

**接口地址**: `GET /package/check-port-availability`  
**权限标识**: `package/check-port-availability`  
**适用角色**: 租户

#### 请求参数
```
tenant_id=123      // 租户ID，必填，正整数
need_count=50      // 需要端口数，可选，默认0
```

#### 响应示例
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "available": true,           // 是否可用
    "total_ports": 500,          // 总端口数
    "used_ports": 200,           // 已用端口数
    "available_ports": 300,      // 可用端口数
    "need_ports": 50,            // 需要端口数
    "can_assign": true           // 是否可以分配
  },
  "show": 0
}
```

---

### 5. 分配历史查询

**接口地址**: `GET /package/assign-history`  
**权限标识**: `package/assign-history`  
**适用角色**: 代理商、管理员

#### 请求参数
```
tenant_id=123                    // 租户ID，可选
status=1                         // 状态，可选，0-过期，1-有效
time_range[]=2025-08-01         // 开始时间，可选
time_range[]=2025-08-31         // 结束时间，可选
page=1                          // 页码，可选，默认1
limit=20                        // 每页数量，可选，默认20
```

#### 响应示例
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "total": 100,
    "per_page": 20,
    "current_page": 1,
    "last_page": 5,
    "data": [
      {
        "id": 1,
        "agent_name": "代理商A",
        "tenant_name": "租户B",
        "port_count": 200,
        "assign_time_text": "2025-08-27 10:00:00",
        "expire_time_text": "2025-09-26 10:00:00",
        "status_text": "有效",
        "remaining_days": 30,
        "remark": "测试分配"
      }
    ]
  },
  "show": 0
}
```

## 📊 管理功能接口

### 6. 套餐列表

**接口地址**: `GET /package/lists`  
**权限标识**: `package/lists`  
**适用角色**: 代理商、管理员

#### 请求参数
```
agent_id=123                     // 代理商ID，可选
tenant_id=456                    // 租户ID，可选
status=1                         // 状态，可选
remark=测试                      // 备注关键词，可选
expire_status=valid              // 到期状态，可选：valid-有效，expired-已过期，expiring_soon-即将过期
port_count_min=100              // 最小端口数，可选
port_count_max=1000             // 最大端口数，可选
start_time=2025-08-01           // 开始时间，可选
end_time=2025-08-31             // 结束时间，可选
page=1                          // 页码，可选
limit=20                        // 每页数量，可选
sort_field=create_time          // 排序字段，可选
sort_order=desc                 // 排序方向，可选：asc-升序，desc-降序
```

#### 响应示例
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "total": 100,
    "per_page": 20,
    "current_page": 1,
    "last_page": 5,
    "data": [
      {
        "id": 1,
        "agent_name": "代理商A",
        "agent_account": "agent001",
        "tenant_name": "租户B",
        "tenant_account": "tenant001",
        "port_count": 200,
        "assign_time_text": "2025-08-27 10:00:00",
        "expire_time_text": "2025-09-26 10:00:00",
        "status_text": "有效",
        "remaining_days": 30,
        "is_expiring_soon": false,
        "remark": "测试分配"
      }
    ]
  },
  "show": 0
}
```

---

### 7. 统计信息

**接口地址**: `GET /package/statistics`  
**权限标识**: `package/statistics`  
**适用角色**: 代理商、管理员

#### 响应示例
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "total_count": 150,              // 总记录数
    "total_ports": 15000,            // 总端口数
    "valid_count": 120,              // 有效记录数
    "valid_ports": 12000,            // 有效端口数
    "expired_count": 30,             // 过期记录数
    "expired_ports": 3000,           // 过期端口数
    "expiring_soon_count": 10,       // 即将过期记录数
    "expiring_soon_ports": 1000      // 即将过期端口数
  },
  "show": 0
}
```

## ⚙️ 系统维护接口

### 10. 处理过期套餐

**接口地址**: `POST /package/handle-expired`
**权限标识**: `package/handle-expired`
**适用角色**: 管理员

#### 请求参数
无需参数

#### 响应示例
```json
{
  "code": 1,
  "msg": "处理完成",
  "data": {
    "expired_packages": 5,           // 过期套餐数量
    "released_accounts": 20,         // 释放的小号数量
    "affected_tenants": 3,           // 受影响的租户数量
    "release_details": [
      {
        "tenant_id": 123,
        "released_count": 10,
        "remaining_ports": 50
      }
    ]
  },
  "show": 1
}
```

---

### 11. 更新过期状态

**接口地址**: `POST /package/update-expired-status`
**权限标识**: `package/update-expired-status`
**适用角色**: 管理员

#### 请求参数
无需参数

#### 响应示例
```json
{
  "code": 1,
  "msg": "更新完成",
  "data": {
    "updated_count": 8,
    "update_time": "2025-08-27 15:30:00"
  },
  "show": 1
}
```

## 🔧 辅助功能接口

### 12. 租户选项

**接口地址**: `GET /package/tenant-options`
**权限标识**: `package/tenant-options`
**适用角色**: 代理商

#### 响应示例
```json
{
  "code": 1,
  "msg": "success",
  "data": [
    {
      "id": 123,
      "name": "租户A",
      "account": "tenant001"
    },
    {
      "id": 124,
      "name": "租户B",
      "account": "tenant002"
    }
  ],
  "show": 0
}
```

---

### 13. 客服选项

**接口地址**: `GET /package/operator-options`
**权限标识**: `package/operator-options`
**适用角色**: 租户

#### 响应示例
```json
{
  "code": 1,
  "msg": "success",
  "data": [
    {
      "id": 456,
      "name": "客服A",
      "account": "service001"
    },
    {
      "id": 457,
      "name": "客服B",
      "account": "service002"
    }
  ],
  "show": 0
}
```

---

### 14. 小号选项

**接口地址**: `GET /package/alt-account-options`
**权限标识**: `package/alt-account-options`
**适用角色**: 租户

#### 响应示例
```json
{
  "code": 1,
  "msg": "success",
  "data": [
    {
      "id": 1001,
      "nickname": "小号001",
      "phone": "13800138001"
    },
    {
      "id": 1002,
      "nickname": "小号002",
      "phone": "13800138002"
    }
  ],
  "show": 0
}
```

## 📝 错误码说明

### **通用错误码**
| 错误码 | 说明 | 解决方案 |
|-------|------|---------|
| -1 | 登录超时，请重新登录 | 重新获取token |
| 0 | 业务逻辑错误 | 根据msg提示处理 |
| 1 | 操作成功 | 正常响应 |

### **业务错误示例**
```json
// 权限不足
{
  "code": 0,
  "msg": "权限不足，无法访问或操作",
  "data": {},
  "show": 1
}

// 参数验证失败
{
  "code": 0,
  "msg": "端口数量必须在1-10000之间",
  "data": {},
  "show": 1
}

// 业务逻辑错误
{
  "code": 0,
  "msg": "您只能为自己的下级租户分配套餐",
  "data": {},
  "show": 1
}
```

## 🔐 认证说明

### **Token获取**
通过登录接口获取token：
```bash
curl -X POST "http://your-domain/adminapi/login/account" \
  -H "Content-Type: application/json" \
  -d '{
    "account": "your-account",
    "password": "your-password"
  }'
```

### **Token使用**
在请求头中添加Authorization：
```bash
curl -H "Authorization: Bearer your-token-here" \
  "http://your-domain/adminapi/package/lists"
```

### **Token刷新**
Token有效期8小时，临时过期前1小时自动续期。

## 📊 接口统计

| 接口类型 | 数量 | 说明 |
|---------|------|------|
| 核心功能 | 5个 | 套餐分配、查询、小号分配等 |
| 管理功能 | 4个 | 列表、统计、详情、状态查询 |
| 系统维护 | 2个 | 过期处理、状态更新 |
| 辅助功能 | 3个 | 选项列表接口 |
| **总计** | **14个** | **完整功能覆盖** |

## ⚠️ 注意事项

1. **时区**: 所有时间均为北京时间（UTC+8）
2. **编码**: 请求和响应均使用UTF-8编码
3. **限流**: 建议控制请求频率，避免过于频繁的调用
4. **缓存**: 部分查询接口支持缓存，可适当缓存结果
5. **日志**: 所有操作都会记录操作日志，便于审计
```

---

### 8. 套餐详情

**接口地址**: `GET /package/detail`  
**权限标识**: `package/detail`  
**适用角色**: 代理商、管理员

#### 请求参数
```
id=123    // 套餐记录ID，必填，正整数
```

#### 响应示例
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "id": 1,
    "agent_name": "代理商A",
    "tenant_name": "租户B",
    "port_count": 200,
    "assign_time_text": "2025-08-27 10:00:00",
    "expire_time_text": "2025-09-26 10:00:00",
    "status_text": "有效",
    "remaining_days": 30,
    "remark": "测试分配",
    "create_time": 1724734800,
    "update_time": 1724734800
  },
  "show": 0
}
```

---

### 9. 端口池状态

**接口地址**: `GET /package/port-pool-status`  
**权限标识**: `package/port-pool-status`  
**适用角色**: 租户、代理商

#### 请求参数
```
tenant_id=123    // 租户ID，必填，正整数
```

#### 响应示例
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "total_ports": 500,        // 总端口数
    "used_ports": 200,         // 已用端口数
    "available_ports": 300,    // 可用端口数
    "expiring_soon": 50        // 即将过期端口数
  },
  "show": 0
}
```

## 🔄 套餐续费功能

### 1. 单个套餐续费

**接口地址**: `POST /package/renew`
**权限标识**: `package/renew`
**适用角色**: 代理商

#### 请求参数
```json
{
  "package_id": 123,       // 套餐ID，必填，正整数
  "extend_days": 30        // 续费天数，必填，1-3650天
}
```

#### 响应示例
```json
{
  "code": 1,
  "msg": "套餐续费成功",
  "data": [],
  "show": 1
}
```

#### 错误示例
```json
{
  "code": 0,
  "msg": "套餐记录不存在",
  "data": [],
  "show": 1
}
```

### 2. 批量套餐续费

**接口地址**: `POST /package/batch-renew`
**权限标识**: `package/batchRenew`
**适用角色**: 代理商

#### 请求参数
```json
{
  "package_ids": [123, 124, 125],  // 套餐ID列表，必填，最多100个
  "extend_days": 30                // 续费天数，必填，1-3650天
}
```

#### 响应示例
```json
{
  "code": 1,
  "msg": "批量续费成功",
  "data": [],
  "show": 1
}
```

### 3. 获取可续费套餐列表

**接口地址**: `GET /package/renewable-packages`
**权限标识**: `package/renewablePackages`
**适用角色**: 代理商

#### 请求参数
```
tenant_id=456  // 租户ID，必填
```

#### 响应示例
```json
{
  "code": 1,
  "msg": "",
  "data": [
    {
      "id": 123,
      "tenant_id": 456,
      "port_count": 100,
      "assign_time": 1703664000,
      "expire_time": 1706256000,
      "status": 1,
      "remark": "测试套餐",
      "remaining_days": 5,
      "is_expired": false,
      "is_expiring_soon": true,
      "tenant": {
        "id": 456,
        "name": "测试租户",
        "account": "test_tenant"
      }
    }
  ],
  "show": 0
}
```

#### 续费业务逻辑
- **未过期套餐**: 在原到期时间基础上延长
- **已过期套餐**: 从当前时间开始计算新的到期时间
- **权限控制**: 只能续费自己分配的套餐
- **状态更新**: 续费后套餐状态自动设为有效

## 🔧 功能优化更新

### 小号删除优化
- **级联删除**: 删除小号时自动清理分配关系
- **端口释放**: 删除后端口立即可重新分配
- **事务保护**: 确保数据一致性

### 端口分配优化
- **过期检查**: 只能使用未过期套餐的端口
- **实时计算**: 动态计算可用端口数量
- **状态同步**: 套餐过期后立即停止分配

### 历史查询优化
- **全租户查询**: 支持不选择租户查看全部记录
- **状态筛选**: 支持按套餐状态（0:过期 1:有效）筛选
- **时间范围**: 支持按时间范围查询

---

## 📊 端口统计机制（v1.9.4更新）

### 统一统计标准
从 v1.9.4 版本开始，所有端口统计都使用基于 `package_id` 的新机制：

```php
// 统一的端口统计方式
$usedPorts = AltAccount::where('tenant_id', $tenantId)
    ->where('package_id', '>', 0)  // 基于package_id统计
    ->count();
```

### 统计原理
- **端口总数**: 租户所有有效套餐的端口数之和
- **已用端口**: 已分配给客服的小号数量（基于package_id统计）
- **可用端口**: 端口总数 - 已用端口
- **过期端口**: 已过期套餐的端口数

### 优先级分配策略
- **时间优先**: 最早分配的套餐优先使用
- **精确追踪**: 每个小号记录使用的具体套餐ID
- **智能分配**: 自动按优先级将小号分配到对应套餐

### 数据一致性
- 所有接口使用统一的 `PortStatisticsService` 服务类
- 确保 `auth.tenant/lists` 和 `package/lists` 数据一致
- 提供数据验证接口确保统计准确性

---

## 📝 更新日志

### v1.9.4 (2025-08-27)
- ✅ 新增套餐续费功能（单个续费、批量续费）
- ✅ 优化小号删除逻辑（级联删除分配关系）
- ✅ 修复端口可用性检查（只计算未过期套餐）
- ✅ 优化历史查询功能（支持全租户查询和状态筛选）
- ✅ 修复时间字段类型错误
- ✅ 优化接口返回数据格式
- ✅ 统一端口统计机制（基于package_id）
- ✅ 实现套餐优先级分配策略
- ✅ 新增端口统计服务类和数据验证接口
