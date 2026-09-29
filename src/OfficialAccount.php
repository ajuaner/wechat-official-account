<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount;

use EasyWeChat\OfficialAccount\Application;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Qinii\WechatCore\Config\ArrayConfigProvider;
use Qinii\WechatCore\Config\ConfigResolver;
use Qinii\WechatOfficialAccount\Support\OfficialAccountContext;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * 微信公众号统一门面。
 */
final class OfficialAccount
{
    private OfficialAccountContext $context;

    /** 创建门面并绑定默认账号组。 */
    public function __construct(
        ConfigResolver $configResolver,
        string $accountName = 'default',
    ) {
        $this->context = new OfficialAccountContext(
            $configResolver,
            $accountName,
        );
    }

    /**
     * 使用普通数组配置快速创建门面。
     *
     * @param array<string, array<string, mixed>> $config
     */
    public static function create(
        array $config,
        string $defaultAccount = 'default',
    ): self {
        return new self(
            new ConfigResolver(new ArrayConfigProvider($config)),
            $defaultAccount,
        );
    }

    /** 设置所有公众号接口共用的 HTTP 客户端。 */
    public function setHttpClient(HttpClientInterface $httpClient): self
    {
        $this->context->setHttpClient($httpClient);

        return $this;
    }

    /** 设置 access_token 和 JS-SDK ticket 共用缓存。 */
    public function setCache(CacheInterface $cache): self
    {
        $this->context->setCache($cache);

        return $this;
    }

    /** 设置公众号回调使用的 PSR-7 请求。 */
    public function setRequest(ServerRequestInterface $request): self
    {
        $this->context->setRequest($request);

        return $this;
    }

    /** 切换账号组并返回独立的新门面。 */
    public function account(string $name): self
    {
        $officialAccount = clone $this;
        $officialAccount->context = $this->context->account($name);

        return $officialAccount;
    }

    /** 获取网页授权能力。 */
    public function oauth(): OAuth
    {
        return new OAuth($this->context);
    }

    /** 获取公众号用户信息能力。 */
    public function user(): User
    {
        return new User($this->context);
    }

    /** 获取 JS-SDK 签名能力。 */
    public function jsSdk(): JsSdk
    {
        return new JsSdk($this->context);
    }

    /** 获取公众号消息回调能力。 */
    public function server(): Server
    {
        return new Server($this->context);
    }

    /** 获取被动回复消息构造器。 */
    public function message(): Message
    {
        return new Message();
    }

    /** 获取自定义菜单能力。 */
    public function menu(): Menu
    {
        return new Menu($this->context);
    }

    /** 获取公众号二维码能力。 */
    public function qrCode(): QrCode
    {
        return new QrCode($this->context);
    }

    /** 获取永久素材能力。 */
    public function material(): Material
    {
        return new Material($this->context);
    }

    /** 获取公众号模板消息能力。 */
    public function template(): Template
    {
        return new Template($this->context);
    }

    /** 获取客服消息能力。 */
    public function customerService(): CustomerService
    {
        return new CustomerService($this->context);
    }

    /**
     * 获取底层 EasyWeChat 应用，供尚未封装的公众号接口使用。
     */
    public function application(): Application
    {
        return $this->context->application();
    }
}
