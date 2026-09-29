# 功能清单

本文档列出当前扩展包已经实现和暂未实现的微信公众号能力。

## 已实现

### 门面和公共能力

- `OfficialAccount::create()`：使用数组配置创建门面。
- `account()`：切换账号组。
- `setHttpClient()`：注入 Symfony/Swoole HTTP 客户端。
- `setCache()`：注入 PSR-16 缓存。
- `setRequest()`：注入公众号回调 PSR-7 请求。
- `application()`：获取底层 EasyWeChat 公众号应用。

### 网页授权

- `OAuth::redirect()`：生成公众号网页授权地址。
- `OAuth::tokenFromCode()`：使用 code 换取网页授权凭证。
- `OAuth::userFromCode()`：使用 code 获取授权用户资料。
- `OAuth::userFromToken()`：使用网页授权 token 和 openid 获取用户资料。

### 用户信息

- `User::get()`：根据 openid 获取单个已关注用户信息。

### JS-SDK

- `JsSdk::build()`：生成 JS-SDK 签名配置。

### 自定义菜单

- `Menu::create()`：创建或覆盖自定义菜单。

### 带参数二维码

- `QrCode::temporary()`：创建临时二维码。
- `QrCode::forever()`：创建永久二维码。
- `QrCode::url()`：根据 ticket 生成二维码展示地址。

### 永久素材

- `Material::uploadImage()`：上传永久图片素材。
- `Material::uploadVoice()`：上传永久语音素材。
- `Material::upload()`：上传当前支持的指定类型素材。

### 模板消息

- `Template::send()`：格式化并发送公众号模板消息。
- `Template::sendRaw()`：发送完整的微信模板消息参数。
- `Template::setIndustry()`：设置模板消息所属行业。
- `Template::addTemplate()`：根据模板短编号添加模板。
- `Template::getPrivateTemplates()`：获取当前公众号已添加的模板。
- `Template::deletePrivateTemplate()`：删除指定模板。
- `Template::getIndustry()`：获取当前公众号的模板消息行业。

### 客服消息

- `CustomerService::sendText()`：发送文本消息。
- `CustomerService::sendImage()`：发送图片消息。
- `CustomerService::sendVoice()`：发送语音消息。
- `CustomerService::sendNews()`：发送图文消息。
- `CustomerService::send()`：发送完整的客服消息参数。

### 消息回调和自动回复

- `Server::with()`：添加通用处理器。
- `Server::onMessage()`：监听指定消息类型。
- `Server::onEvent()`：监听指定事件。
- `Server::serve()`：验签、解密、执行处理器并生成回复。
- `Server::message()`：只验签、解密并读取消息。
- `Message::text()`：构造文本回复。
- `Message::image()`：构造图片回复。
- `Message::voice()`：构造语音回复。
- `Message::video()`：构造视频回复。
- `Message::news()`：构造图文回复。
- `Message::transfer()`：构造转接客服回复。

## 暂未实现

以下能力在当前 CRMEB 项目中没有确认到真实业务调用，因此暂不纳入第一版：

- 微信用户标签和旧版用户分组；
- 批量获取用户资料和关注用户列表；
- 公众号订阅通知；
- 卡券；
- 临时素材；
- 永久视频素材和图文素材管理；
- 模板库批量同步编排（扩展包已提供底层增删查接口，项目按自身配置实现同步）；
- 菜单远程查询、删除和个性化菜单；
- 群发消息；
- 数据统计接口。

未封装的能力可以先通过 `OfficialAccount::application()` 获取 EasyWeChat 应用后自行调用。确认存在稳定业务需求后，再加入对应的公开方法。
