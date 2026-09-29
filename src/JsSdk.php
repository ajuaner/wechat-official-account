<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount;

use Qinii\WechatOfficialAccount\Support\AbstractApi;

/**
 * 微信网页 JS-SDK 签名能力。
 */
final class JsSdk extends AbstractApi
{
    /**
     * 生成 JS-SDK 初始化配置。
     *
     * @param array<int, string> $apiList 需要调用的 JS 接口列表
     * @param array<int, string> $openTagList 需要使用的开放标签列表
     *
     * @return array<string, mixed>
     */
    public function build(
        string $url,
        array $apiList = [],
        array $openTagList = [],
        bool $debug = false,
    ): array {
        return $this->application()->getUtils()->buildJsSdkConfig(
            url: $this->requireString($url, '签名 URL'),
            jsApiList: $apiList,
            openTagList: $openTagList,
            debug: $debug,
        );
    }
}
