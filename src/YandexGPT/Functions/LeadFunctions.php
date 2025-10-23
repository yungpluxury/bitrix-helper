<?php

namespace YandexGPT\Functions;

use YandexGPT\Bitrix\BitrixAPI;
use YandexGPT\Bitrix\BitrixAPIException;

class LeadFunctions
{
    private static $bitrixApi = null;

    public static function setBitrixAPI($webhookUrl)
    {
        self::$bitrixApi = new BitrixAPI($webhookUrl);
    }

    public static function findLead($arguments)
    {
        $title = $arguments->title ?? '';
        
        if (empty($title)) {
            return [
                'success' => false,
                'error' => 'Не указано название лида для поиска'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите LeadFunctions::setBitrixAPI()'
            ];
        }

        try {
            $filter = [
                'LOGIC' => 'OR',
                ['TITLE' => $title],
                ['TITLE' => "%{$title}%"]
            ];

            $result = self::$bitrixApi->call('crm.lead.list', [
                'filter' => $filter,
                'select' => ['ID', 'TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'PHONE', 'EMAIL', 'COMPANY_TITLE', 'STATUS_ID', 'SOURCE_ID', 'OPPORTUNITY', 'CURRENCY_ID']
            ]);
            
            if (empty($result['result'])) {
                return [
                    'success' => false,
                    'message' => "Лид '{$title}' не найден",
                    'data' => []
                ];
            }

            $leads = $result['result'];
            $lead = $leads[0];

            return [
                'success' => true,
                'data' => [
                    'id' => $lead['ID'],
                    'title' => $lead['TITLE'] ?? '',
                    'name' => $lead['NAME'] ?? '',
                    'last_name' => $lead['LAST_NAME'] ?? '',
                    'second_name' => $lead['SECOND_NAME'] ?? '',
                    'phone' => $lead['PHONE'][0]['VALUE'] ?? '',
                    'email' => $lead['EMAIL'][0]['VALUE'] ?? '',
                    'company' => $lead['COMPANY_TITLE'] ?? '',
                    'status' => $lead['STATUS_ID'] ?? '',
                    'source' => $lead['SOURCE_ID'] ?? '',
                    'opportunity' => $lead['OPPORTUNITY'] ?? '',
                    'currency' => $lead['CURRENCY_ID'] ?? '',
                    'url' => self::$bitrixApi->getLeadUrl($lead['ID'])
                ],
                'message' => "Найден лид: " . ($lead['TITLE'] ?? $title),
                'total_found' => count($leads)
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
