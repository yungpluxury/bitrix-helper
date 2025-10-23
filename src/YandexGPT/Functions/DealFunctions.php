<?php

namespace YandexGPT\Functions;

use YandexGPT\Bitrix\BitrixAPI;
use YandexGPT\Bitrix\BitrixAPIException;

class DealFunctions
{
    private static $bitrixApi = null;

    public static function setBitrixAPI($webhookUrl)
    {
        self::$bitrixApi = new BitrixAPI($webhookUrl);
    }

    public static function findDeal($arguments)
    {
        $title = $arguments->title ?? '';
        
        if (empty($title)) {
            return [
                'success' => false,
                'error' => 'Не указано название сделки для поиска'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите DealFunctions::setBitrixAPI()'
            ];
        }

        try {
            $filter = [
                'LOGIC' => 'OR',
                ['TITLE' => $title],
                ['TITLE' => "%{$title}%"]
            ];

            $result = self::$bitrixApi->call('crm.deal.list', [
                'filter' => $filter,
                'select' => ['ID', 'TITLE', 'STAGE_ID', 'OPPORTUNITY', 'CURRENCY_ID', 'COMPANY_TITLE', 'CONTACT_NAME', 'ASSIGNED_BY_ID', 'BEGINDATE', 'CLOSEDATE']
            ]);
            
            if (empty($result['result'])) {
                return [
                    'success' => false,
                    'message' => "Сделка '{$title}' не найдена",
                    'data' => []
                ];
            }

            $deals = $result['result'];
            $deal = $deals[0];

            return [
                'success' => true,
                'data' => [
                    'id' => $deal['ID'],
                    'title' => $deal['TITLE'] ?? '',
                    'stage' => $deal['STAGE_ID'] ?? '',
                    'opportunity' => $deal['OPPORTUNITY'] ?? '',
                    'currency' => $deal['CURRENCY_ID'] ?? '',
                    'company' => $deal['COMPANY_TITLE'] ?? '',
                    'contact' => $deal['CONTACT_NAME'] ?? '',
                    'assigned_by' => $deal['ASSIGNED_BY_ID'] ?? '',
                    'begin_date' => $deal['BEGINDATE'] ?? '',
                    'close_date' => $deal['CLOSEDATE'] ?? '',
                    'url' => self::$bitrixApi->getDealUrl($deal['ID'])
                ],
                'message' => "Найдена сделка: " . ($deal['TITLE'] ?? $title),
                'total_found' => count($deals)
            ];

        } catch (BitrixAPIException $e) {
            return [
                'success' => false,
                'error' => 'Ошибка Bitrix API: ' . $e->getMessage(),
                'bitrix_error_code' => $e->getBitrixErrorCode()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Общая ошибка: ' . $e->getMessage()
            ];
        }
    }
}
