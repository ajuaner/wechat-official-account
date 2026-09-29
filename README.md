# Qinii WeChat Official Account

基于 EasyWeChat 6 的微信公众号扩展包，提供 CRMEB 当前真实使用的公众号能力，并复用 `qinii/wechat-core` 的多账号配置和 HTTP 客户端。

## 环境要求

- PHP 8.0 及以上
- EasyWeChat 6.19.1 及以上
- `qinii/wechat-core`

## 安装

```bash
composer require qinii/wechat-official-account
```

## 配置

配置按账号组组织，公众号配置固定放在 `official_account` 节点。`secret`、`appsecret` 和 `app_secret` 均可使用，内部统一转换为 EasyWeChat 需要的 `secret`。

```php
use Qinii\WechatCore\Enum\ApplicationType;
use Qinii\WechatOfficialAccount\OfficialAccount;

$wechat = OfficialAccount::create([
    'default' => [
        ApplicationType::OFFICIAL_ACCOUNT => [
            'app_id' => 'wx1234567890',
            'appsecret' => 'your-app-secret',
            'token' => 'your-callback-token',
            'aes_key' => 'your-encoding-aes-key',
            'use_stable_access_token' => true,
            'oauth' => [
                'redirect_url' => 'https://example.com/wechat/oauth',
                'scopes' => ['snsapi_userinfo'],
            ],
            'http' => [
                'timeout' => 5,
            ],
        ],
    ],
]);
```

使用依赖注入时，可以直接注入 `ConfigResolver`：

```php
use Qinii\WechatCore\Config\ConfigResolver;
use Qinii\WechatOfficialAccount\OfficialAccount;

$wechat = new OfficialAccount(
    configResolver: $configResolver,
    accountName: 'default',
);
```

## 公共能力

```php
// 切换账号，不修改原门面
$other = $wechat->account('other');

// 注入 PSR-16 缓存，生产环境建议使用 Redis 等共享缓存
$wechat->setCache($cache);

// 注入 Symfony HttpClientInterface 实例
$wechat->setHttpClient($httpClient);

// 获取底层 EasyWeChat 应用，调用尚未封装的接口
$application = $wechat->application();
```

Swoole 环境可以直接注入 `wechat-core` 提供的客户端：

```php
use Qinii\WechatCore\Http\SwooleHttpClient;

$wechat->setHttpClient(new SwooleHttpClient([
    'http_version' => '1.1',
    'timeout' => 5,
]));
```

扩展包会为注入的客户端补充微信 API 基础地址。OAuth 换取 token 和获取授权用户资料同样使用该客户端，不会绕回 Guzzle/cURL。

## 网页授权

```php
$url = $wechat->oauth()->redirect(
    redirectUrl: 'https://example.com/wechat/oauth',
    scopes: ['snsapi_userinfo'],
    state: 'your-state',
);

$token = $wechat->oauth()->tokenFromCode($code);
$user = $wechat->oauth()->userFromCode($code);
$user = $wechat->oauth()->userFromToken($accessToken, $openid);
```

## 用户和 JS-SDK

```php
$user = $wechat->user()->get($openid);

$config = $wechat->jsSdk()->build(
    url: $url,
    apiList: ['updateAppMessageShareData', 'chooseWXPay'],
);
```

## 菜单和二维码

```php
$wechat->menu()->create([
    [
        'type' => 'view',
        'name' => '商城',
        'url' => 'https://example.com',
    ],
]);

$temporary = $wechat->qrCode()->temporary('login:123', 600);
$forever = $wechat->qrCode()->forever('spread:123');
$imageUrl = $wechat->qrCode()->url($forever['ticket']);
```

## 永久素材

```php
$image = $wechat->material()->uploadImage('/absolute/path/image.jpg');
$voice = $wechat->material()->uploadVoice('/absolute/path/voice.mp3');
```

## 模板消息

```php
$result = $wechat->template()->send(
    openId: $openid,
    templateId: $templateId,
    data: [
        'first' => '订单支付成功',
        'amount' => ['99.00 元', '#ff0000'],
        'remark' => ['value' => '感谢您的购买'],
    ],
    url: 'https://example.com/orders/123',
);
```

已经完整组装微信参数时，可以使用 `sendRaw()`。

模板库管理接口可以用于项目自己的模板同步流程：

```php
$wechat->template()->setIndustry(1, 4);
$templates = $wechat->template()->getPrivateTemplates();
$result = $wechat->template()->addTemplate(
    'TM00015',
    ['订单编号', '订单金额', '订单状态'],
);
$wechat->template()->deletePrivateTemplate($templateId);
$industry = $wechat->template()->getIndustry();
```

扩展包只负责调用微信接口，不绑定项目数据库或模板配置。项目中的模板同步接口可以先读取自己的模板配置，再调用上述接口完成删除、添加和保存模板 ID。

## 客服消息

```php
$wechat->customerService()->sendText($openid, '您好');
$wechat->customerService()->sendImage($openid, $mediaId);
$wechat->customerService()->sendVoice($openid, $mediaId);
$wechat->customerService()->sendNews($openid, [
    [
        'title' => '订单提醒',
        'description' => '您有一条新订单消息',
        'url' => 'https://example.com/orders',
        'image' => 'https://example.com/order.jpg',
    ],
]);
```

## 消息回调和自动回复

`Server` 接收 PSR-7 `ServerRequestInterface`。普通 PHP 环境可以直接传入请求，Swoole 环境必须传入当前协程对应的请求，避免复用其他请求状态。

```php
$message = $wechat->message();

$response = $wechat->server()
    ->onEvent('subscribe', function ($incoming) use ($message) {
        return $message->text('感谢关注');
    })
    ->onMessage('text', function ($incoming) use ($message) {
        return $message->text('收到：' . $incoming->Content);
    })
    ->serve($request);
```

支持的被动回复构造方法：

```php
$message->text('文本');
$message->image($mediaId);
$message->voice($mediaId);
$message->video($mediaId, '标题', '说明');
$message->news($articles);
$message->transfer('account@wechat');
```

回调处理会执行：

- 明文模式 `signature` 校验；
- 安全模式 URL 校验字符串解密；
- 安全模式消息验签、AES 解密和加密回复；
- 微信服务器 URL 验证响应。

AES 能力直接复用 EasyWeChat，不在扩展包中自行实现签名或加解密算法。

## 功能范围

完整的方法列表和暂未实现功能见 [功能清单](docs/features.md)。
