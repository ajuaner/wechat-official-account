<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount\Support;

use EasyWeChat\OfficialAccount\Application;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Qinii\WechatCore\Config\ConfigResolver;
use Qinii\WechatOfficialAccount\Config\OfficialAccountConfig;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * 公众号运行上下文，统一保存账号、HTTP 客户端、缓存和当前请求。
 */
final class OfficialAccountContext
{
    private const API_BASE_URI = 'https://api.weixin.qq.com/';

    private ?HttpClientInterface $httpClient = null;
    private ?CacheInterface $cache = null;
    private ?ServerRequestInterface $request = null;

    public function __construct(
        private ConfigResolver $configResolver,
        private string $accountName = 'default',
    ) {
    }

    /** 设置公众号接口使用的 HTTP 客户端。 */
    public function setHttpClient(HttpClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /** 设置 access_token 和 JS-SDK ticket 共用的缓存。 */
    public function setCache(CacheInterface $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /** 设置公众号回调使用的 PSR-7 请求。 */
    public function setRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    /** 克隆上下文并切换账号组，避免污染原实例。 */
    public function account(string $name): self
    {
        $context = clone $this;
        $context->accountName = $name;

        return $context;
    }

    /** 获取当前账号组的公众号配置。 */
    public function config(): OfficialAccountConfig
    {
        return OfficialAccountConfig::fromAccountConfig(
            $this->configResolver->require($this->accountName),
        );
    }

    /**
     * 创建 EasyWeChat 公众号应用。
     *
     * 每次调用均创建新应用，避免 Swoole 常驻进程复用上一次请求状态。
     */
    public function application(
        ?ServerRequestInterface $request = null,
    ): Application {
        $application = new Application($this->config()->toArray());

        if ($this->httpClient !== null) {
            $application->setHttpClient(
                $this->httpClient->withOptions([
                    'base_uri' => self::API_BASE_URI,
                ]),
            );
        }

        if ($this->cache !== null) {
            $application->setCache($this->cache);
        }

        $request ??= $this->request;

        if ($request !== null) {
            $application->setRequest($request);
        }

        return $application;
    }
}
