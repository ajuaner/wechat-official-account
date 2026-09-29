<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount;

use Qinii\WechatOfficialAccount\Support\AbstractApi;

/**
 * 公众号用户信息能力。
 */
final class User extends AbstractApi
{
    private const INFO_ENDPOINT = 'cgi-bin/user/info';

    /**
     * 根据 openid 获取已关注公众号用户的信息。
     *
     * @return array<string, mixed>
     */
    public function get(
        string $openId,
        string $lang = 'zh_CN',
    ): array {
        $response = $this->client()->get(self::INFO_ENDPOINT, [
            'query' => [
                'openid' => $this->requireString($openId, 'openid'),
                'lang' => $this->requireString($lang, '语言'),
            ],
        ]);

        return $this->responseToArray($response);
    }
}
