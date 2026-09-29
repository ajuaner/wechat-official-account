<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount;

use Qinii\WechatOfficialAccount\Exception\OfficialAccountException;
use Qinii\WechatOfficialAccount\Support\AbstractApi;

/**
 * 公众号带参数二维码能力。
 */
final class QrCode extends AbstractApi
{
    private const CREATE_ENDPOINT = 'cgi-bin/qrcode/create';
    private const SHOW_URL = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=%s';
    private const TEMPORARY_DEFAULT_SECONDS = 604800;
    private const TEMPORARY_MAX_SECONDS = 2592000;
    private const FOREVER_SCENE_ID_MAX = 100000;

    /**
     * 创建临时带参数二维码。
     *
     * @return array<string, mixed>
     */
    public function temporary(
        int|string $sceneValue,
        ?int $expireSeconds = null,
    ): array {
        $expireSeconds ??= self::TEMPORARY_DEFAULT_SECONDS;

        if ($expireSeconds < 1 || $expireSeconds > self::TEMPORARY_MAX_SECONDS) {
            throw new OfficialAccountException(sprintf(
                '临时二维码有效期必须在 1 到 %d 秒之间。',
                self::TEMPORARY_MAX_SECONDS,
            ));
        }

        [$actionName, $scene] = is_int($sceneValue) && $sceneValue > 0
            ? ['QR_SCENE', ['scene_id' => $sceneValue]]
            : ['QR_STR_SCENE', [
                'scene_str' => $this->sceneString($sceneValue),
            ]];

        return $this->create($actionName, $scene, $expireSeconds);
    }

    /**
     * 创建永久带参数二维码。
     *
     * @return array<string, mixed>
     */
    public function forever(int|string $sceneValue): array
    {
        if (
            is_int($sceneValue)
            && $sceneValue > 0
            && $sceneValue <= self::FOREVER_SCENE_ID_MAX
        ) {
            $actionName = 'QR_LIMIT_SCENE';
            $scene = ['scene_id' => $sceneValue];
        } else {
            $actionName = 'QR_LIMIT_STR_SCENE';
            $scene = ['scene_str' => $this->sceneString($sceneValue)];
        }

        return $this->create($actionName, $scene);
    }

    /** 根据二维码 ticket 生成可展示的二维码地址。 */
    public function url(string $ticket): string
    {
        return sprintf(
            self::SHOW_URL,
            rawurlencode($this->requireString($ticket, '二维码 ticket')),
        );
    }

    /**
     * 调用微信接口创建二维码。
     *
     * @param array<string, int|string> $scene
     *
     * @return array<string, mixed>
     */
    private function create(
        string $actionName,
        array $scene,
        ?int $expireSeconds = null,
    ): array {
        $payload = [
            'action_name' => $actionName,
            'action_info' => ['scene' => $scene],
        ];

        if ($expireSeconds !== null) {
            $payload['expire_seconds'] = $expireSeconds;
        }

        return $this->responseToArray(
            $this->client()->postJson(self::CREATE_ENDPOINT, $payload),
        );
    }

    /** 将场景值校验并转换为字符串。 */
    private function sceneString(int|string $sceneValue): string
    {
        $scene = trim((string) $sceneValue);

        if ($scene === '') {
            throw new OfficialAccountException('二维码场景值不能为空。');
        }

        if (strlen($scene) > 64) {
            throw new OfficialAccountException(
                '二维码字符串场景值不能超过 64 个字节。',
            );
        }

        return $scene;
    }
}
