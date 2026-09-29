<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount;

use Qinii\WechatOfficialAccount\Exception\OfficialAccountException;

/**
 * 公众号被动回复消息构造器。
 */
final class Message
{
    /** 构造文本回复，字符串会由 EasyWeChat 自动转换为文本消息。 */
    public function text(string $content): string
    {
        return str_replace(
            ['\\n', '\\r'],
            ["\n", "\r"],
            $content,
        );
    }

    /**
     * 构造图片回复。
     *
     * @return array<string, mixed>
     */
    public function image(string $mediaId): array
    {
        return [
            'MsgType' => 'image',
            'Image' => ['MediaId' => $this->mediaId($mediaId)],
        ];
    }

    /**
     * 构造语音回复。
     *
     * @return array<string, mixed>
     */
    public function voice(string $mediaId): array
    {
        return [
            'MsgType' => 'voice',
            'Voice' => ['MediaId' => $this->mediaId($mediaId)],
        ];
    }

    /**
     * 构造视频回复。
     *
     * @return array<string, mixed>
     */
    public function video(
        string $mediaId,
        string $title = '',
        string $description = '',
    ): array {
        return [
            'MsgType' => 'video',
            'Video' => [
                'MediaId' => $this->mediaId($mediaId),
                'Title' => $title,
                'Description' => $description,
            ],
        ];
    }

    /**
     * 构造图文回复。
     *
     * @param array<int, array<string, mixed>> $articles
     *
     * @return array<string, mixed>
     */
    public function news(array $articles): array
    {
        if ($articles === []) {
            throw new OfficialAccountException('被动回复图文消息不能为空。');
        }

        $items = [];

        foreach ($articles as $article) {
            $items[] = [
                'Title' => (string) ($article['title'] ?? ''),
                'Description' => (string) (
                    $article['description'] ?? ''
                ),
                'PicUrl' => (string) (
                    $article['picurl'] ?? $article['image'] ?? ''
                ),
                'Url' => (string) ($article['url'] ?? ''),
            ];
        }

        return [
            'MsgType' => 'news',
            'ArticleCount' => count($items),
            'Articles' => $items,
        ];
    }

    /**
     * 构造转接客服回复。
     *
     * @return array<string, mixed>
     */
    public function transfer(?string $account = null): array
    {
        $message = ['MsgType' => 'transfer_customer_service'];

        if ($account !== null && trim($account) !== '') {
            $message['TransInfo'] = [
                'KfAccount' => trim($account),
            ];
        }

        return $message;
    }

    /** 校验并返回素材 ID。 */
    private function mediaId(string $mediaId): string
    {
        $mediaId = trim($mediaId);

        if ($mediaId === '') {
            throw new OfficialAccountException('素材 ID 不能为空。');
        }

        return $mediaId;
    }
}
