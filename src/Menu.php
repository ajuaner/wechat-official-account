<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount;

use Qinii\WechatOfficialAccount\Exception\OfficialAccountException;
use Qinii\WechatOfficialAccount\Support\AbstractApi;

/**
 * 公众号自定义菜单能力。
 */
final class Menu extends AbstractApi
{
    private const CREATE_ENDPOINT = 'cgi-bin/menu/create';

    /**
     * 创建或覆盖公众号自定义菜单。
     *
     * @param array<int, array<string, mixed>> $buttons 一级菜单列表
     *
     * @return array<string, mixed>
     */
    public function create(array $buttons): array
    {
        if ($buttons === []) {
            throw new OfficialAccountException('公众号菜单不能为空。');
        }

        $response = $this->client()->postJson(self::CREATE_ENDPOINT, [
            'button' => array_values($buttons),
        ]);

        return $this->responseToArray($response);
    }
}
