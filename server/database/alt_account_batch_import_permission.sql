-- 小号批量导入权限配置
-- 执行时间：2025-01-05

-- 查找小号管理的父级菜单ID
SET @parent_menu_id = (SELECT id FROM la_system_menu WHERE perms = 'alt_account/lists' LIMIT 1);

-- 如果找不到父级菜单，则查找小号管理模块的目录
SET @parent_menu_id = IFNULL(@parent_menu_id, (SELECT id FROM la_system_menu WHERE name = '小号管理' AND type = 'M' LIMIT 1));

-- 插入批量导入权限菜单
INSERT INTO `la_system_menu` (
    `pid`, 
    `type`, 
    `name`, 
    `icon`, 
    `sort`, 
    `perms`, 
    `paths`, 
    `component`, 
    `selected`, 
    `params`, 
    `is_cache`, 
    `is_show`, 
    `is_disable`, 
    `create_time`, 
    `update_time`
) VALUES (
    @parent_menu_id,
    'A',
    '批量导入',
    '',
    50,
    'alt_account/batchImport',
    '',
    '',
    '',
    '',
    0,
    0,
    0,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);

-- 获取新插入的菜单ID
SET @new_menu_id = LAST_INSERT_ID();

-- 为租户角色分配批量导入权限（假设租户角色ID为2）
-- 注意：请根据实际系统中的角色ID进行调整
INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`) 
VALUES (2, @new_menu_id);

-- 为管理员角色分配批量导入权限（假设管理员角色ID为1）
INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`) 
VALUES (1, @new_menu_id);

-- 查看插入结果
SELECT 
    m.id as menu_id,
    m.name as menu_name,
    m.perms as permission,
    m.pid as parent_id,
    pm.name as parent_name
FROM la_system_menu m
LEFT JOIN la_system_menu pm ON m.pid = pm.id
WHERE m.perms = 'alt_account/batchImport';

-- 查看角色权限分配结果
SELECT 
    r.id as role_id,
    r.name as role_name,
    m.name as menu_name,
    m.perms as permission
FROM la_system_role r
JOIN la_system_role_menu rm ON r.id = rm.role_id
JOIN la_system_menu m ON rm.menu_id = m.id
WHERE m.perms = 'alt_account/batchImport'
ORDER BY r.id;
