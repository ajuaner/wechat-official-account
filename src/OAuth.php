<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount;

use Overtrue\Socialite\Contracts\ProviderInterface;
use Overtrue\Socialite\Providers\WeChat;
use Qinii\WechatOfficialAccount\Exception\OfficialAccountException;
use Qinii\WechatOfficialAccount\Support\AbstractApi;

/**
 * 公众号网页授权能力。
 */
final class OAuth extends AbstractApi
{
    private const TOKEN_ENDPOINT = 'sns/oauth2/access_token';
    private const USER_ENDPOINT = 'sns/userinfo';

    /**
     * 生成微信公众号网页授权地址。
     *
     * @param array<int, string> $scopes 授权作用域，默认获取用户资料
     */
    public function redirect(
        ?string $redirectUrl = null,
        array $scopes = ['snsapi_userinfo'],
        ?string $state = null,
    ): string {
        $provider = $this->provider($scopes);

        if ($state !== null && $state !== '') {
            $provider->withState($state);
        }

        return $provider->redirect($redirectUrl);
    }

    /**
     * 使用授权 code 换取网页授权凭证。
     *
     * @return array<string, mixed>
     */
    public function tokenFromCode(string $code): array
    {
        $config = $this->context->config();
        $response = $this->httpClient()->request(
            'GET',
            self::TOKEN_ENDPOINT,
            [
                'query' => [
                    'appid' => $config->appId(),
                    'secret' => $config->secret(),
                    'code' => $this->requireString($code, '授权 code'),
                    'grant_type' => 'authorization_code',
                ],
            ],
        );

        return $this->oauthResponse(
            $response->toArray(false),
            '换取授权凭证',
            ['access_token', 'openid'],
        );
    }

    /**
     * 使用授权 code 获取授权用户资料。
     *
     * snsapi_base 只返回 openid，snsapi_userinfo 返回完整授权资料。
     *
     * @param array<int, string> $scopes
     */
    public function userFromCode(
        string $code,
        array $scopes = ['snsapi_userinfo'],
    ): array {
        $token = $this->tokenFromCode($code);

        if (in_array('snsapi_base', $scopes, true)) {
            return $token;
        }

        $user = $this->userFromToken(
            (string) ($token['access_token'] ?? ''),
            (string) ($token['openid'] ?? ''),
        );

        return array_merge($token, $user);
    }

    /**
     * 使用网页授权 access_token 和 openid 获取用户资料。
     */
    public function userFromToken(
        string $accessToken,
        string $openId,
    ): array {
        $response = $this->httpClient()->request(
            'GET',
            self::USER_ENDPOINT,
            [
                'query' => [
                    'access_token' => $this->requireString(
                        $accessToken,
                        '网页授权 access_token',
                    ),
                    'openid' => $this->requireString($openId, 'openid'),
                    'lang' => 'zh_CN',
                ],
            ],
        );

        return $this->oauthResponse(
            $response->toArray(false),
            '获取授权用户资料',
            ['openid'],
        );
    }

    /**
     * 获取 EasyWeChat 创建的微信网页授权驱动。
     *
     * @param array<int, string> $scopes
     */
    private function provider(
        array $scopes = ['snsapi_userinfo'],
    ): WeChat {
        $provider = $this->application()->getOAuth();

        if (! $provider instanceof WeChat) {
            throw new OfficialAccountException(
                sprintf(
                    '网页授权驱动必须实现为 %s，当前为 %s。',
                    WeChat::class,
                    $provider instanceof ProviderInterface
                        ? get_debug_type($provider)
                        : '未知类型',
                ),
            );
        }

        return $provider->scopes($scopes);
    }

    /**
     * 校验网页授权接口响应。
     *
     * @param array<string, mixed> $response
     *
     * @return array<string, mixed>
     */
    private function oauthResponse(
        array $response,
        string $action,
        array $requiredKeys = [],
    ): array
    {
        if (isset($response['errcode']) && (int) $response['errcode'] !== 0) {
            throw new OfficialAccountException(sprintf(
                '微信网页授权%s失败：%s（%s）',
                $action,
                (string) ($response['errmsg'] ?? '未知错误'),
                (string) $response['errcode'],
            ));
        }

        foreach ($requiredKeys as $key) {
            if (
                ! isset($response[$key])
                || ! is_string($response[$key])
                || trim($response[$key]) === ''
            ) {
                throw new OfficialAccountException(sprintf(
                    '微信网页授权%s响应缺少 [%s]。',
                    $action,
                    $key,
                ));
            }
        }

        return $response;
    }
}
