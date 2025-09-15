import axios, { type AxiosRequestConfig, type Canceler } from 'axios'

const cancelerMap = new Map<string, Canceler>()

// 获取一个唯一的请求键，它由请求的 URL 和参数组成
function getRequestKey(config: AxiosRequestConfig): string {
    const { url, method, params, data } = config
    const paramsStr = params ? JSON.stringify(params) : ''
    const dataStr = data ? JSON.stringify(data) : ''
    return `${method || 'GET'}_${url || ''}_${paramsStr}_${dataStr}`
}

export class AxiosCancel {
    private static instance?: AxiosCancel

    static createInstance() {
        return this.instance ?? (this.instance = new AxiosCancel())
    }
    add(config: AxiosRequestConfig) {
        const requestKey = getRequestKey(config)
        this.remove(requestKey)
        config.cancelToken = new axios.CancelToken((cancel) => {
            if (!cancelerMap.has(requestKey)) {
                cancelerMap.set(requestKey, cancel)
            }
        })
    }
    remove(requestKey: string) {
        if (cancelerMap.has(requestKey)) {
            const cancel = cancelerMap.get(requestKey)
            // 静默取消请求，不抛出错误
            cancel && cancel()
            cancelerMap.delete(requestKey)
        }
    }
}

const axiosCancel = AxiosCancel.createInstance()

export default axiosCancel
