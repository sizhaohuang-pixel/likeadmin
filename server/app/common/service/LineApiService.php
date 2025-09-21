<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * LINE API服务类
 * 用于处理LINE账号相关的第三方API调用
 * Class LineApiService
 * @package app\common\service
 */
class LineApiService
{
    /**
     * 第三方API地址
     */
    const API_URL = 'http://103.205.208.78:8080/api';

    /**
     * 状态码定义
     */
    const STATUS_NORMAL = 1;        // 正常
    const STATUS_PROXY_ERROR = 2;   // 代理不可用
    const STATUS_OFFLINE = 3;       // 下线
    const STATUS_BANNED = 4;        // 封禁

    /**
     * 状态码对应的中文描述
     */
    const STATUS_MESSAGES = [
        self::STATUS_NORMAL => '����',
        self::STATUS_PROXY_ERROR => '����������',
        self::STATUS_OFFLINE => '����',
        self::STATUS_BANNED => '���'
    ];

    const UPDATE_NICKNAME_MESSAGES = [
        1 => 'Success',
        2 => 'Proxy cannot be used',
        3 => 'Access token refresh required',
        4 => 'Access blocked'
    ];

    const UPDATE_AVATAR_MESSAGES = [
        1 => 'Success',
        2 => 'Proxy cannot be used',
        3 => 'Access token refresh required',
        4 => 'Access blocked',
        5 => 'Failure'
    ];

    /**
     * @notes 账号验活（带重试机制）
     * @param string $mid 账号MID
     * @param string $accessToken 访问令牌
     * @param string $proxyUrl 代理地址
     * @param int $maxRetries 最大重试次数（状态2时使用，默认5次）
     * @return array
     * @author 段誉
     * @date 2025/09/08
     */
    public static function verifyAccount(string $mid, string $accessToken, string $proxyUrl, int $maxRetries = 5): array
    {
        $attempt = 0;
        $lastResult = null;

        while ($attempt <= $maxRetries) {
            try {
                // 构建请求参数
                $data = [
                    'type' => 'noop',
                    'proxy' => $proxyUrl,
                    'mid' => $mid,
                    'accessToken' => $accessToken
                ];

                // 发起HTTP请求
                $response = self::sendRequest(self::API_URL, $data);

                if ($response === false) {
                    $lastResult = [
                        'success' => false,
                        'message' => 'API请求失败',
                        'code' => 0,
                        'data' => []
                    ];
                } else {
                    // 解析返回结果
                    $result = json_decode($response, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $lastResult = [
                            'success' => false,
                            'message' => 'API返回数据格式错误',
                            'code' => 0,
                            'data' => []
                        ];
                    } else {
                        $code = $result['code'] ?? 0;
                        $status = $result['status'] ?? '';
                        $returnMid = $result['mid'] ?? '';

                        // 根据状态码处理结果
                        $message = self::STATUS_MESSAGES[$code] ?? '未知状态';
                        $success = $code === self::STATUS_NORMAL;

                        $lastResult = [
                            'success' => $success,
                            'message' => $message,
                            'code' => $code,
                            'status' => $status,
                            'data' => [
                                'mid' => $returnMid,
                                'original_mid' => $mid
                            ],
                            'retry_attempt' => $attempt
                        ];

                        // 只有状态码为2（代理不可用）且还有重试次数时才重试
                        if ($code !== self::STATUS_PROXY_ERROR || $attempt >= $maxRetries) {
                            // 如果不是代理错误，或者已达到最大重试次数，直接返回结果
                            if ($attempt > 0) {
                                $lastResult['retried'] = true;
                                $lastResult['total_attempts'] = $attempt + 1;
                                if ($code === self::STATUS_PROXY_ERROR) {
                                    $lastResult['message'] .= "（重试{$attempt}次后仍失败）";
                                } else {
                                    $lastResult['message'] .= "（第" . ($attempt + 1) . "次尝试成功）";
                                }
                            }
                            return $lastResult;
                        }
                    }
                }
            } catch (\Exception $e) {
                $lastResult = [
                    'success' => false,
                    'message' => '验活过程中发生异常：' . $e->getMessage(),
                    'code' => 0,
                    'data' => []
                ];
            }

            // 如果需要重试且还有重试次数
            if ($attempt < $maxRetries && isset($lastResult['code']) && $lastResult['code'] === self::STATUS_PROXY_ERROR) {
                \think\facade\Log::info("代理不可用，立即重试（第" . ($attempt + 1) . "次重试）", [
                    'mid' => $mid,
                    'attempt' => $attempt + 1,
                    'max_retries' => $maxRetries
                ]);
                // 不添加延迟，直接进入下次循环重试
            }

            $attempt++;
        }

        // 如果执行到这里，说明所有重试都失败了
        if ($lastResult) {
            $lastResult['retried'] = true;
            $lastResult['total_attempts'] = $attempt;
            $lastResult['message'] .= "（重试{$maxRetries}次后仍失败）";
        }

        return $lastResult ?? [
            'success' => false,
            'message' => '验活失败（未知错误）',
            'code' => 0,
            'data' => []
        ];
    }

    /**
     * @notes 发送HTTP请求
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @param int $timeout 超时时间（秒，默认60秒）
     * @return string|false
     * @author 段誉
     * @date 2025/09/08
     */
    private static function sendRequest(string $url, array $data, int $timeout = 60)
    {
        // 使用cURL发送POST请求
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($error)) {
            \think\facade\Log::error('LINE API请求失败', [
                'url' => $url,
                'data' => $data,
                'error' => $error,
                'http_code' => $httpCode
            ]);
            return false;
        }

        if ($httpCode !== 200) {
            \think\facade\Log::error('LINE API返回非200状态码', [
                'url' => $url,
                'data' => $data,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            return false;
        }

        return $response;
    }

    /**
     * @notes 获取状态码对应的中文描述
     * @param int $code 状态码
     * @return string
     * @author 段誉
     * @date 2025/09/08
     */
    public static function getStatusMessage(int $code): string
    {
        return self::STATUS_MESSAGES[$code] ?? '未知状态';
    }

    /**
     * @notes 判断状态是否正常
     * @param int $code 状态码
     * @return bool
     * @author 段誉
     * @date 2025/09/08
     */
    public static function isNormalStatus(int $code): bool
    {
        return $code === self::STATUS_NORMAL;
    }

    /**
     * @notes 更新昵称（带重试机制）
     * @param string $nickname 昵称原文
     * @param string $mid 账号MID
     * @param string $accessToken 访问Token
     * @param string $proxyUrl 代理地址
     * @param int $maxRetries 最大重试次数（状态2时使用，默认5次）
     * @return array
     */
    public static function updateNickname(string $nickname, string $mid, string $accessToken, string $proxyUrl, int $maxRetries = 5): array
    {
        $attempt = 0;
        $lastResult = null;

        while ($attempt <= $maxRetries) {
            try {
                $payload = [
                    'type' => 'UpdateNickName',
                    'NickName' => base64_encode($nickname),
                    'proxy' => $proxyUrl,
                    'mid' => $mid,
                    'accessToken' => $accessToken
                ];
                \think\facade\Log::info('LINE API昵称更新:' . json_encode($payload));
                $response = self::sendRequest(self::API_URL, $payload);

                if ($response === false) {
                    $lastResult = [
                        'success' => false,
                        'message' => '昵称更新请求失败',
                        'code' => 0,
                        'status' => '',
                        'mid' => ''
                    ];
                } else {
                    $result = json_decode($response, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $lastResult = [
                            'success' => false,
                            'message' => '昵称更新响应解析失败',
                            'code' => 0,
                            'status' => '',
                            'mid' => ''
                        ];
                    } else {
                        $code = (int)($result['code'] ?? 0);
                        $status = (string)($result['status'] ?? '');
                        $midResult = (string)($result['mid'] ?? '');
                        $message = self::UPDATE_NICKNAME_MESSAGES[$code] ?? '未知状态';

                        $lastResult = [
                            'success' => $code === 1,
                            'message' => $message,
                            'code' => $code,
                            'status' => $status,
                            'mid' => $midResult,
                            'retry_attempt' => $attempt
                        ];

                        // 只有状态码为2（代理不可用）且还有重试次数时才重试
                        if ($code !== 2 || $attempt >= $maxRetries) {
                            // 如果不是代理错误，或者已达到最大重试次数，直接返回结果
                            if ($attempt > 0) {
                                $lastResult['retried'] = true;
                                $lastResult['total_attempts'] = $attempt + 1;
                                if ($code === 2) {
                                    $lastResult['message'] .= "（重试{$attempt}次后仍失败）";
                                } else {
                                    $lastResult['message'] .= "（第" . ($attempt + 1) . "次尝试成功）";
                                }
                            }
                            return $lastResult;
                        }
                    }
                }
            } catch (\Exception $e) {
                \think\facade\Log::error('LINE API昵称更新异常', [
                    'mid' => $mid,
                    'nickname' => $nickname,
                    'proxy' => $proxyUrl,
                    'exception' => $e->getMessage()
                ]);
                $lastResult = [
                    'success' => false,
                    'message' => '昵称更新异常: ' . $e->getMessage(),
                    'code' => 0,
                    'status' => '',
                    'mid' => ''
                ];
            }

            // 如果需要重试且还有重试次数
            if ($attempt < $maxRetries && isset($lastResult['code']) && $lastResult['code'] === 2) {
                \think\facade\Log::info("昵称更新代理不可用，立即重试（第" . ($attempt + 1) . "次重试）", [
                    'mid' => $mid,
                    'nickname' => $nickname,
                    'attempt' => $attempt + 1,
                    'max_retries' => $maxRetries
                ]);
                // 不添加延迟，直接进入下次循环重试
            }

            $attempt++;
        }

        // 如果执行到这里，说明所有重试都失败了
        if ($lastResult) {
            $lastResult['retried'] = true;
            $lastResult['total_attempts'] = $attempt;
            $lastResult['message'] .= "（重试{$maxRetries}次后仍失败）";
        }

        return $lastResult ?? [
            'success' => false,
            'message' => '昵称更新失败（未知错误）',
            'code' => 0,
            'status' => '',
            'mid' => ''
        ];
    }

    /**
     * @notes 更新头像（带重试机制）
     * @param string $avatarBase64 图片Base64
     * @param string $mid 账号MID
     * @param string $accessToken 访问Token
     * @param string $proxyUrl 代理地址
     * @param int $maxRetries 最大重试次数（状态2时使用，默认5次）
     * @return array
     */
    public static function updateAvatar(string $avatarBase64, string $mid, string $accessToken, string $proxyUrl, int $maxRetries = 5): array
    {
        $attempt = 0;
        $lastResult = null;

        while ($attempt <= $maxRetries) {
            try {
                $payload = [
                    'type' => 'UpdateAvatar',
                    'Avatar' => $avatarBase64,
                    'proxy' => $proxyUrl,
                    'mid' => $mid,
                    'accessToken' => $accessToken
                ];

                $response = self::sendRequest(self::API_URL, $payload);

                if ($response === false) {
                    $lastResult = [
                        'success' => false,
                        'message' => '头像更新请求失败',
                        'code' => 0,
                        'status' => '',
                        'mid' => ''
                    ];
                } else {
                    $result = json_decode($response, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $lastResult = [
                            'success' => false,
                            'message' => '头像更新响应解析失败',
                            'code' => 0,
                            'status' => '',
                            'mid' => ''
                        ];
                    } else {
                        $code = (int)($result['code'] ?? 0);
                        $status = (string)($result['status'] ?? '');
                        $midResult = (string)($result['mid'] ?? '');
                        $message = self::UPDATE_AVATAR_MESSAGES[$code] ?? '未知状态';

                        $lastResult = [
                            'success' => $code === 1,
                            'message' => $message,
                            'code' => $code,
                            'status' => $status,
                            'mid' => $midResult,
                            'retry_attempt' => $attempt
                        ];

                        // 只有状态码为2（代理不可用）且还有重试次数时才重试
                        if ($code !== 2 || $attempt >= $maxRetries) {
                            // 如果不是代理错误，或者已达到最大重试次数，直接返回结果
                            if ($attempt > 0) {
                                $lastResult['retried'] = true;
                                $lastResult['total_attempts'] = $attempt + 1;
                                if ($code === 2) {
                                    $lastResult['message'] .= "（重试{$attempt}次后仍失败）";
                                } else {
                                    $lastResult['message'] .= "（第" . ($attempt + 1) . "次尝试成功）";
                                }
                            }
                            return $lastResult;
                        }
                    }
                }
            } catch (\Exception $e) {
                \think\facade\Log::error('LINE API头像更新异常', [
                    'mid' => $mid,
                    'proxy' => $proxyUrl,
                    'exception' => $e->getMessage()
                ]);
                $lastResult = [
                    'success' => false,
                    'message' => '头像更新异常: ' . $e->getMessage(),
                    'code' => 0,
                    'status' => '',
                    'mid' => ''
                ];
            }

            // 如果需要重试且还有重试次数
            if ($attempt < $maxRetries && isset($lastResult['code']) && $lastResult['code'] === 2) {
                \think\facade\Log::info("头像更新代理不可用，立即重试（第" . ($attempt + 1) . "次重试）", [
                    'mid' => $mid,
                    'attempt' => $attempt + 1,
                    'max_retries' => $maxRetries
                ]);
                // 不添加延迟，直接进入下次循环重试
            }

            $attempt++;
        }

        // 如果执行到这里，说明所有重试都失败了
        if ($lastResult) {
            $lastResult['retried'] = true;
            $lastResult['total_attempts'] = $attempt;
            $lastResult['message'] .= "（重试{$maxRetries}次后仍失败）";
        }

        return $lastResult ?? [
            'success' => false,
            'message' => '头像更新失败（未知错误）',
            'code' => 0,
            'status' => '',
            'mid' => ''
        ];
    }

    /**
     * @notes 刷新Token
     * @param string $mid 账号MID
     * @param string $accessToken 访问令牌
     * @param string $refreshToken 刷新令牌
     * @param string $proxyUrl 代理地址
     * @return array
     * @author 段誉
     * @date 2025/09/08
     */
    public static function refreshToken(string $mid, string $accessToken, string $refreshToken, string $proxyUrl): array
    {
        try {
            // 构建请求参数
            $data = [
                'type' => 'RefreshToken',
                'proxy' => $proxyUrl,
                'mid' => $mid,
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken
            ];

            // 发起HTTP请求
            $response = self::sendRequest(self::API_URL, $data);

            if ($response === false) {
                return [
                    'success' => false,
                    'message' => 'Token刷新API请求失败',
                    'code' => 0,
                    'data' => []
                ];
            }

            // 解析返回结果
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'Token刷新API返回数据格式错误',
                    'code' => 0,
                    'data' => []
                ];
            }

            $code = $result['code'] ?? 0;
            $status = $result['status'] ?? '';

            // 根据状态码处理结果
            if ($code === self::STATUS_NORMAL) {
                // 刷新成功，返回新的token信息
                return [
                    'success' => true,
                    'message' => 'Token刷新成功',
                    'code' => $code,
                    'status' => $status,
                    'data' => [
                        'accessToken' => $result['accessToken'] ?? '',
                        'refreshToken' => $result['refreshToken'] ?? '',
                        'mid' => $result['mid'] ?? $mid
                    ]
                ];
            } else {
                // 刷新失败
                $message = self::STATUS_MESSAGES[$code] ?? '未知状态';
                return [
                    'success' => false,
                    'message' => 'Token刷新失败：' . $message,
                    'code' => $code,
                    'status' => $status,
                    'data' => []
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Token刷新过程中发生异常：' . $e->getMessage(),
                'code' => 0,
                'data' => []
            ];
        }
    }
}
