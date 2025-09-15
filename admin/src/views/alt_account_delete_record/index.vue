<template>
    <div>
        <el-card class="!border-none mb-4" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" inline>
                <el-form-item label="操作人" prop="operator_name">
                    <el-input class="w-[180px]" v-model="queryParams.operator_name" clearable placeholder="请输入操作人姓名或账号" />
                </el-form-item>
                <el-form-item label="删除时间" prop="delete_time">
                    <el-date-picker
                        v-model="queryParams.delete_time"
                        type="datetimerange"
                        value-format="YYYY-MM-DD HH:mm:ss"
                        :default-time="[new Date(2000, 1, 1, 0, 0, 0), new Date(2000, 1, 1, 23, 59, 59)]"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        class="w-[280px]"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none" v-loading="pager.loading" shadow="never">
            <div class="mt-4">
                <el-table :data="pager.lists" style="width: 100%">
                    <el-table-column label="删除时间" prop="delete_time_text" min-width="180" show-overflow-tooltip />
                    <el-table-column label="操作人" prop="operator_name" min-width="150" show-overflow-tooltip />
                    <el-table-column label="删除数量" prop="delete_count" min-width="120" align="center">
                        <template #default="{ row }">
                            <el-tag type="warning" size="small">{{ row.delete_count }}个</el-tag>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
            <div class="flex mt-4 justify-end">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
</template>

<script lang="ts" setup name="altAccountDeleteRecordLists">
import { usePaging } from '@/hooks/usePaging'
import { apiAltAccountDeleteRecordLists } from '@/api/alt_account_delete_record'

const queryParams = reactive({
    operator_name: '',
    delete_time: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: apiAltAccountDeleteRecordLists,
    params: queryParams
})

getLists()
</script>