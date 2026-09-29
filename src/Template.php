<?php

declare(strict_types=1);

namespace Qinii\WechatOfficialAccount;

use Qinii\WechatOfficialAccount\Exception\OfficialAccountException;
use Qinii\WechatOfficialAccount\Support\AbstractApi;

/**
 * 公众号模板消息发送能力。
 */
final class Template extends AbstractApi
{
    private const SEND_ENDPOINT = 'cgi-bin/message/template/send';
    private const SET_INDUSTRY_ENDPOINT = 'cgi-bin/template/api_set_industry';
    private const ADD_TEMPLATE_ENDPOINT = 'cgi-bin/template/api_add_template';
    private const PRIVATE_TEMPLATES_ENDPOINT = 'cgi-bin/template/get_all_private_template';
    private const DELETE_TEMPLATE_ENDPOINT = 'cgi-bin/template/del_private_template';
    private const GET_INDUSTRY_ENDPOINT = 'cgi-bin/template/get_industry';

    /**
     * 设置公众号模板消息所属行业。
     *
     * @return array<string, mixed>
     */
    public function setIndustry(int|string $industryOne, int|string $industryTwo): array
    {
        return $this->responseToArray(
            $this->client()->postJson(self::SET_INDUSTRY_ENDPOINT, [
                'industry_id1' => $this->requireString(
                    (string) $industryOne,
                    '一级行业编号',
                ),
                'industry_id2' => $this->requireString(
                    (string) $industryTwo,
                    '二级行业编号',
                ),
            ]),
        );
    }

    /**
     * 根据模板短编号和关键词名称添加公众号模板。
     *
     * @param array<int, string|int> $keywordNameList
     * @return array<string, mixed>
     */
    public function addTemplate(
        string $templateIdShort,
        array $keywordNameList,
    ): array {
        $keywords = [];

        if ($keywordNameList === []) {
            throw new OfficialAccountException('模板关键词不能为空。');
        }

        foreach ($keywordNameList as $keyword) {
            if (! is_string($keyword) && ! is_int($keyword)) {
                throw new OfficialAccountException(
                    '模板关键词必须是字符串或整数。',
                );
            }

            $keyword = trim((string) $keyword);

            if ($keyword === '') {
                throw new OfficialAccountException('模板关键词不能为空。');
            }

            $keywords[] = $keyword;
        }

        return $this->responseToArray(
            $this->client()->postJson(self::ADD_TEMPLATE_ENDPOINT, [
                'template_id_short' => $this->requireString(
                    $templateIdShort,
                    '模板短编号',
                ),
                'keyword_name_list' => array_values($keywords),
            ]),
        );
    }

    /**
     * 获取当前公众号已经添加的模板列表。
     *
     * @return array<string, mixed>
     */
    public function getPrivateTemplates(): array
    {
        return $this->responseToArray(
            $this->client()->get(self::PRIVATE_TEMPLATES_ENDPOINT),
        );
    }

    /**
     * 删除当前公众号中的指定模板。
     *
     * @return array<string, mixed>
     */
    public function deletePrivateTemplate(string $templateId): array
    {
        return $this->responseToArray(
            $this->client()->postJson(self::DELETE_TEMPLATE_ENDPOINT, [
                'template_id' => $this->requireString($templateId, '模板 ID'),
            ]),
        );
    }

    /**
     * 获取当前公众号已经设置的模板消息行业。
     *
     * @return array<string, mixed>
     */
    public function getIndustry(): array
    {
        return $this->responseToArray(
            $this->client()->get(self::GET_INDUSTRY_ENDPOINT),
        );
    }

    /**
     * 发送公众号模板消息。
     *
     * @param array<string, mixed> $data 模板字段数据
     * @param array<string, string>|null $miniProgram 跳转小程序配置
     *
     * @return array<string, mixed>
     */
    public function send(
        string $openId,
        string $templateId,
        array $data,
        ?string $url = null,
        ?string $defaultColor = null,
        ?array $miniProgram = null,
        ?string $clientMessageId = null,
    ): array {
        $message = [
            'touser' => $this->requireString($openId, 'openid'),
            'template_id' => $this->requireString(
                $templateId,
                '模板 ID',
            ),
            'data' => $this->formatData($data, $defaultColor),
        ];

        if ($url !== null && trim($url) !== '') {
            $message['url'] = trim($url);
        }

        if ($miniProgram !== null && $miniProgram !== []) {
            $message['miniprogram'] = $miniProgram;
        }

        if ($clientMessageId !== null && trim($clientMessageId) !== '') {
            $message['client_msg_id'] = trim($clientMessageId);
        }

        return $this->sendRaw($message);
    }

    /**
     * 发送已组装完成的模板消息参数。
     *
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    public function sendRaw(array $message): array
    {
        foreach (['touser', 'template_id', 'data'] as $key) {
            if (! isset($message[$key]) || $message[$key] === '') {
                throw new OfficialAccountException(sprintf(
                    '模板消息参数 [%s] 不能为空。',
                    $key,
                ));
            }
        }

        return $this->responseToArray(
            $this->client()->postJson(self::SEND_ENDPOINT, $message),
        );
    }

    /**
     * 将简写数据转换为微信模板消息字段格式。
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, array<string, string>>
     */
    private function formatData(
        array $data,
        ?string $defaultColor,
    ): array {
        $formatted = [];

        foreach ($data as $key => $value) {
            if (is_array($value) && array_key_exists('value', $value)) {
                if (
                    ! is_scalar($value['value'])
                    && $value['value'] !== null
                ) {
                    throw new OfficialAccountException(sprintf(
                        '模板字段 [%s] 的 value 必须是标量或 null。',
                        (string) $key,
                    ));
                }

                $formatted[(string) $key] = $value;
                continue;
            }

            if (is_array($value) && $this->isList($value)) {
                $item = ['value' => (string) ($value[0] ?? '')];

                if (isset($value[1]) && (string) $value[1] !== '') {
                    $item['color'] = (string) $value[1];
                }

                $formatted[(string) $key] = $item;
                continue;
            }

            if (is_array($value)) {
                throw new OfficialAccountException(sprintf(
                    '模板字段 [%s] 必须是字符串、标量，或包含 value 的数组。',
                    (string) $key,
                ));
            }

            if (! is_scalar($value) && $value !== null) {
                throw new OfficialAccountException(sprintf(
                    '模板字段 [%s] 必须是标量或 null。',
                    (string) $key,
                ));
            }

            $item = ['value' => (string) $value];

            if ($defaultColor !== null && trim($defaultColor) !== '') {
                $item['color'] = trim($defaultColor);
            }

            $formatted[(string) $key] = $item;
        }

        return $formatted;
    }

    /**
     * 判断数组是否为从 0 开始的连续索引数组。
     *
     * 该实现用于兼容 PHP 8.0。
     *
     * @param array<mixed> $value
     */
    private function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
