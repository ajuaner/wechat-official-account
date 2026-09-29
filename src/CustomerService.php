<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount;

use Qinii\WechatOfficialAccount\Exception\OfficialAccountException;
use Qinii\WechatOfficialAccount\Support\AbstractApi;

/**
 * 公众号客服消息发送能力。
 */
final class CustomerService extends AbstractApi
{
    private const SEND_ENDPOINT = 'cgi-bin/message/custom/send';

    /**
     * 发送文本客服消息。
     *
     * @return array<string, mixed>
     */
    public function sendText(
        string $openId,
        string $content,
        ?string $account = null,
    ): array {
        return $this->send($openId, [
            'msgtype' => 'text',
            'text' => [
                'content' => $this->requireString($content, '文本内容'),
            ],
        ], $account);
    }

    /**
     * 发送图片客服消息。
     *
     * @return array<string, mixed>
     */
    public function sendImage(
        string $openId,
        string $mediaId,
        ?string $account = null,
    ): array {
        return $this->send($openId, [
            'msgtype' => 'image',
            'image' => [
                'media_id' => $this->requireString($mediaId, '素材 ID'),
            ],
        ], $account);
    }

    /**
     * 发送语音客服消息。
     *
     * @return array<string, mixed>
     */
    public function sendVoice(
        string $openId,
        string $mediaId,
        ?string $account = null,
    ): array {
        return $this->send($openId, [
            'msgtype' => 'voice',
            'voice' => [
                'media_id' => $this->requireString($mediaId, '素材 ID'),
            ],
        ], $account);
    }

    /**
     * 发送图文客服消息。
     *
     * @param array<int, array<string, mixed>> $articles
     *
     * @return array<string, mixed>
     */
    public function sendNews(
        string $openId,
        array $articles,
        ?string $account = null,
    ): array {
        if ($articles === []) {
            throw new OfficialAccountException('客服图文消息不能为空。');
        }

        $items = [];

        foreach ($articles as $article) {
            $items[] = [
                'title' => (string) ($article['title'] ?? ''),
                'description' => (string) ($article['description'] ?? ''),
                'url' => (string) ($article['url'] ?? ''),
                'picurl' => (string) (
                    $article['picurl'] ?? $article['image'] ?? ''
                ),
            ];
        }

        return $this->send($openId, [
            'msgtype' => 'news',
            'news' => ['articles' => $items],
        ], $account);
    }

    /**
     * 发送已经组装完成的客服消息。
     *
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    public function send(
        string $openId,
        array $message,
        ?string $account = null,
    ): array {
        if (empty($message['msgtype'])) {
            throw new OfficialAccountException(
                '客服消息参数 [msgtype] 不能为空。',
            );
        }

        $payload = array_merge([
            'touser' => $this->requireString($openId, 'openid'),
        ], $message);

        if ($account !== null && trim($account) !== '') {
            $payload['customservice'] = [
                'kf_account' => trim($account),
            ];
        }

        return $this->responseToArray(
            $this->client()->postJson(self::SEND_ENDPOINT, $payload),
        );
    }
}
