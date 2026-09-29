<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount;

use Qinii\WechatOfficialAccount\Exception\OfficialAccountException;
use Qinii\WechatOfficialAccount\Support\AbstractApi;

/**
 * 公众号永久素材上传能力。
 */
final class Material extends AbstractApi
{
    private const ADD_ENDPOINT = 'cgi-bin/material/add_material';
    private const SUPPORTED_TYPES = ['image', 'voice'];

    /**
     * 上传永久图片素材。
     *
     * @return array<string, mixed>
     */
    public function uploadImage(string $path): array
    {
        return $this->upload('image', $path);
    }

    /**
     * 上传永久语音素材。
     *
     * @return array<string, mixed>
     */
    public function uploadVoice(string $path): array
    {
        return $this->upload('voice', $path);
    }

    /**
     * 上传指定类型的永久素材。
     *
     * 当前只开放 CRMEB 实际使用的图片和语音类型。
     *
     * @return array<string, mixed>
     */
    public function upload(string $type, string $path): array
    {
        $type = strtolower(trim($type));

        if (! in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new OfficialAccountException(sprintf(
                '不支持的永久素材类型 [%s]。',
                $type,
            ));
        }

        if (! is_file($path) || ! is_readable($path)) {
            throw new OfficialAccountException(sprintf(
                '永久素材文件不存在或不可读：%s',
                $path,
            ));
        }

        $client = $this->client()->withFile(
            $path,
            'media',
            basename($path),
        );
        $response = $client->post(self::ADD_ENDPOINT, [
            'query' => ['type' => $type],
        ]);

        return $this->responseToArray($response);
    }
}
