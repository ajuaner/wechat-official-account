<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount\Config;

use Qinii\WechatCore\Enum\ApplicationType;
use Qinii\WechatOfficialAccount\Exception\OfficialAccountException;

/**
 * 公众号配置对象，负责从账号组中提取并规范 EasyWeChat 所需配置。
 */
final class OfficialAccountConfig
{
    /**
     * @param array<string, mixed> $items
     */
    private function __construct(
        private array $items,
    ) {
    }

    /**
     * 从完整账号组配置创建公众号配置。
     *
     * @param array<string, mixed> $accountConfig
     */
    public static function fromAccountConfig(array $accountConfig): self
    {
        $config = $accountConfig[ApplicationType::OFFICIAL_ACCOUNT] ?? null;

        if (! is_array($config)) {
            throw new OfficialAccountException(sprintf(
                '微信账号配置缺少 [%s] 节点。',
                ApplicationType::OFFICIAL_ACCOUNT,
            ));
        }

        $appId = self::firstString($config, ['app_id', 'appid']);
        $secret = self::firstString($config, [
            'secret',
            'appsecret',
            'app_secret',
        ]);

        if ($appId === '') {
            throw new OfficialAccountException(
                '微信公众号配置 [app_id] 不能为空。',
            );
        }

        if ($secret === '') {
            throw new OfficialAccountException(
                '微信公众号配置 [secret/appsecret] 不能为空。',
            );
        }

        $items = [
            'app_id' => $appId,
            'secret' => $secret,
            'token' => self::firstString($config, ['token']),
            'aes_key' => self::firstString($config, [
                'aes_key',
                'encoding_aes_key',
            ]),
            'use_stable_access_token' => (bool) (
                $config['use_stable_access_token'] ?? true
            ),
        ];

        foreach (['oauth', 'http'] as $key) {
            if (isset($config[$key])) {
                if (! is_array($config[$key])) {
                    throw new OfficialAccountException(sprintf(
                        '微信公众号配置 [%s] 必须是数组。',
                        $key,
                    ));
                }

                $items[$key] = $config[$key];
            }
        }

        return new self($items);
    }

    /**
     * 返回 EasyWeChat 公众号应用配置。
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /** 获取公众号接口校验 Token。 */
    public function token(): string
    {
        return (string) ($this->items['token'] ?? '');
    }

    /** 获取公众号 AppID。 */
    public function appId(): string
    {
        return (string) $this->items['app_id'];
    }

    /** 获取公众号 AppSecret。 */
    public function secret(): string
    {
        return (string) $this->items['secret'];
    }

    /** 获取公众号消息加解密密钥。 */
    public function aesKey(): string
    {
        return (string) ($this->items['aes_key'] ?? '');
    }

    /**
     * 按顺序读取第一个非空字符串配置。
     *
     * @param array<string, mixed> $config
     * @param array<int, string> $keys
     */
    private static function firstString(array $config, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $config[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }
}
