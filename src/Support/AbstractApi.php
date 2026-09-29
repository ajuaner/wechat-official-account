<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount\Support;

use EasyWeChat\Kernel\HttpClient\AccessTokenAwareClient;
use EasyWeChat\OfficialAccount\Application;
use Qinii\WechatOfficialAccount\Exception\OfficialAccountException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * 公众号接口公共基类，仅提供应用、客户端和响应转换能力。
 */
abstract class AbstractApi
{
    public function __construct(
        protected OfficialAccountContext $context,
    ) {
    }

    /** 获取新的 EasyWeChat 公众号应用。 */
    protected function application(): Application
    {
        return $this->context->application();
    }

    /** 获取携带 access_token 的微信接口客户端。 */
    protected function client(): AccessTokenAwareClient
    {
        return $this->application()->getClient();
    }

    /** 获取已经应用公众号请求配置的底层 HTTP 客户端。 */
    protected function httpClient(): HttpClientInterface
    {
        return $this->application()->getHttpClient();
    }

    /**
     * 将 EasyWeChat 响应转换为数组。
     *
     * @return array<string, mixed>
     */
    protected function responseToArray(object $response): array
    {
        if (! method_exists($response, 'toArray')) {
            throw new OfficialAccountException('微信接口响应无法转换为数组。');
        }

        $data = $response->toArray();

        if (! is_array($data)) {
            throw new OfficialAccountException('微信接口响应格式不正确。');
        }

        return $data;
    }

    /** 校验字符串参数不为空。 */
    protected function requireString(string $value, string $name): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new OfficialAccountException(sprintf('%s 不能为空。', $name));
        }

        return $value;
    }
}
