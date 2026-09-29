<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount;

use EasyWeChat\OfficialAccount\Application;
use EasyWeChat\OfficialAccount\Message as OfficialAccountMessage;
use EasyWeChat\OfficialAccount\Server as EasyWechatServer;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qinii\WechatOfficialAccount\Exception\OfficialAccountException;
use Qinii\WechatOfficialAccount\Support\OfficialAccountContext;

/**
 * 公众号消息回调、验签、解密和事件监听入口。
 */
final class Server
{
    /** @var array<int, callable|string> */
    private array $handlers = [];

    /** @var array<int, array{type: string, handler: callable|string}> */
    private array $messageListeners = [];

    /** @var array<int, array{event: string, handler: callable|string}> */
    private array $eventListeners = [];

    public function __construct(
        private OfficialAccountContext $context,
    ) {
    }

    /** 添加处理所有消息和事件的通用处理器。 */
    public function with(callable|string $handler): self
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /** 添加指定消息类型的监听器。 */
    public function onMessage(
        string $type,
        callable|string $handler,
    ): self {
        $this->messageListeners[] = [
            'type' => trim($type),
            'handler' => $handler,
        ];

        return $this;
    }

    /** 添加指定事件类型的监听器。 */
    public function onEvent(
        string $event,
        callable|string $handler,
    ): self {
        $this->eventListeners[] = [
            'event' => trim($event),
            'handler' => $handler,
        ];

        return $this;
    }

    /**
     * 校验并处理公众号回调。
     *
     * 明文模式额外补充 EasyWeChat 6 未执行的 signature 校验；
     * 安全模式继续复用 EasyWeChat 的 AES 验签、解密和加密回复。
     */
    public function serve(
        ?ServerRequestInterface $request = null,
    ): ResponseInterface {
        $application = $this->context->application($request);
        $request = $application->getRequest();
        $verificationResponse = $this->verifyRequest(
            $application,
            $request,
        );

        if ($verificationResponse !== null) {
            return $verificationResponse;
        }

        $server = $application->getServer();

        if (! $server instanceof EasyWechatServer) {
            throw new OfficialAccountException(
                '当前公众号 Server 不支持消息监听器。',
            );
        }

        foreach ($this->handlers as $handler) {
            $server->with($handler);
        }

        foreach ($this->messageListeners as $listener) {
            $server->addMessageListener(
                $listener['type'],
                $listener['handler'],
            );
        }

        foreach ($this->eventListeners as $listener) {
            $server->addEventListener(
                $listener['event'],
                $listener['handler'],
            );
        }

        return $server->serve();
    }

    /**
     * 校验并读取回调消息，但不执行监听器。
     */
    public function message(
        ?ServerRequestInterface $request = null,
    ): OfficialAccountMessage {
        $application = $this->context->application($request);
        $request = $application->getRequest();
        $verificationResponse = $this->verifyRequest(
            $application,
            $request,
        );

        if ($verificationResponse !== null) {
            throw new OfficialAccountException(
                'URL 校验请求不包含可处理的公众号消息。',
            );
        }

        $server = $application->getServer();

        if (! $server instanceof EasyWechatServer) {
            throw new OfficialAccountException(
                '当前公众号 Server 不支持读取回调消息。',
            );
        }

        $message = $server->getDecryptedMessage($request);

        if (! $message instanceof OfficialAccountMessage) {
            throw new OfficialAccountException('公众号回调消息格式不正确。');
        }

        return $message;
    }

    /**
     * 校验明文签名并处理微信服务器 URL 验证请求。
     */
    private function verifyRequest(
        Application $application,
        ServerRequestInterface $request,
    ): ?ResponseInterface {
        $query = $request->getQueryParams();
        $echoString = (string) ($query['echostr'] ?? '');
        $messageSignature = (string) (
            $query['msg_signature'] ?? ''
        );
        $timestamp = (string) ($query['timestamp'] ?? '');
        $nonce = (string) ($query['nonce'] ?? '');
        $config = $this->context->config();

        if ($messageSignature !== '' && $config->aesKey() !== '') {
            if ($echoString === '') {
                return null;
            }

            $decrypted = $application->getEncryptor()->decrypt(
                ciphertext: $echoString,
                msgSignature: $messageSignature,
                nonce: $nonce,
                timestamp: $timestamp,
            );

            return new Response(200, [], $decrypted);
        }

        $this->verifyPlainSignature(
            token: $config->token(),
            signature: (string) ($query['signature'] ?? ''),
            timestamp: $timestamp,
            nonce: $nonce,
        );

        return $echoString !== ''
            ? new Response(200, [], $echoString)
            : null;
    }

    /** 校验公众号明文模式 signature。 */
    private function verifyPlainSignature(
        string $token,
        string $signature,
        string $timestamp,
        string $nonce,
    ): void {
        if ($token === '') {
            throw new OfficialAccountException(
                '公众号回调配置 [token] 不能为空。',
            );
        }

        if ($signature === '' || $timestamp === '' || $nonce === '') {
            throw new OfficialAccountException('公众号回调签名参数不完整。');
        }

        $items = [$token, $timestamp, $nonce];
        sort($items, SORT_STRING);
        $expected = sha1(implode('', $items));

        if (! hash_equals($expected, $signature)) {
            throw new OfficialAccountException('公众号回调签名校验失败。');
        }
    }
}
