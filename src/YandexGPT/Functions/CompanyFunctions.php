<?php

namespace YandexGPT\Functions;

use YandexGPT\Bitrix\BitrixAPI;
use YandexGPT\Bitrix\BitrixAPIException;

class CompanyFunctions
{
    private static $bitrixApi = null;

    public static function setBitrixAPI($webhookUrl)
    {
        self::$bitrixApi = new BitrixAPI($webhookUrl);
    }

    public static function findCompany($arguments)
    {
        $title = $arguments->title ?? '';
        
        if (empty($title)) {
            return [
                'success' => false,
                'error' => 'Не указано название компании для поиска'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите CompanyFunctions::setBitrixAPI()'
            ];
        }

        try {
            $filter = [
                'LOGIC' => 'OR',
                ['TITLE' => $title],
                ['TITLE' => "%{$title}%"]
            ];

            $result = self::$bitrixApi->call('crm.company.list', [
                'filter' => $filter,
                'select' => ['ID', 'TITLE', 'PHONE', 'EMAIL', 'WEB', 'ADDRESS', 'INDUSTRY', 'EMPLOYEES', 'REVENUE', 'CURRENCY_ID', 'ASSIGNED_BY_ID']
            ]);
            
            if (empty($result['result'])) {
                return [
                    'success' => false,
                    'message' => "Компания '{$title}' не найдена",
                    'data' => []
                ];
            }

            $companies = $result['result'];
            $company = $companies[0];

            return [
                'success' => true,
                'data' => [
                    'id' => $company['ID'],
                    'title' => $company['TITLE'] ?? '',
                    'phone' => $company['PHONE'][0]['VALUE'] ?? '',
                    'email' => $company['EMAIL'][0]['VALUE'] ?? '',
                    'website' => $company['WEB'][0]['VALUE'] ?? '',
                    'address' => $company['ADDRESS'] ?? '',
                    'industry' => $company['INDUSTRY'] ?? '',
                    'employees' => $company['EMPLOYEES'] ?? '',
                    'revenue' => $company['REVENUE'] ?? '',
                    'currency' => $company['CURRENCY_ID'] ?? '',
                    'assigned_by' => $company['ASSIGNED_BY_ID'] ?? '',
                    'url' => self::$bitrixApi->getCompanyUrl($company['ID'])
                ],
                'message' => "Найдена компания: " . ($company['TITLE'] ?? $title),
                'total_found' => count($companies)
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
