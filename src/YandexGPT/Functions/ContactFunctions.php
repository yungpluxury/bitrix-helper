<?php

namespace YandexGPT\Functions;

use YandexGPT\Bitrix\BitrixAPI;
use YandexGPT\Bitrix\BitrixAPIException;

class ContactFunctions
{
    private static $bitrixApi = null;

    public static function setBitrixAPI($webhookUrl)
    {
        self::$bitrixApi = new BitrixAPI($webhookUrl);
    }

    public static function getContact($arguments)
    {
        $fio = $arguments->fio ?? '';
        
        if (empty($fio)) {
            return [
                'success' => false,
                'error' => 'Не указано ФИО для поиска'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите ContactFunctions::setBitrixAPI()'
            ];
        }

        try {
            $result = self::$bitrixApi->findContactByFio($fio);
            
            if (empty($result['result'])) {
                return [
                    'success' => false,
                    'message' => "Контакт '{$fio}' не найден",
                    'data' => []
                ];
            }

            $contacts = $result['result'];
            $contact = $contacts[0];
            
            return [
                'success' => true,
                'data' => [
                    'id' => $contact['ID'],
                    'name' => $contact['NAME'] ?? '',
                    'second_name' => $contact['SECOND_NAME'] ?? '',
                    'last_name' => $contact['LAST_NAME'] ?? '',
                    'full_name' => $contact['FULL_NAME'] ?? '',
                    'phone' => $contact['PHONE'][0]['VALUE'] ?? '',
                    'email' => $contact['EMAIL'][0]['VALUE'] ?? '',
                    'company' => $contact['COMPANY_TITLE'] ?? '',
                    'position' => $contact['POST'] ?? '',
                    'url' => self::$bitrixApi->getContactUrl($contact['ID'])
                ],
                'message' => "Найден контакт: " . ($contact['FULL_NAME'] ?? $fio),
                'total_found' => count($contacts)
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

    public static function createContact($arguments)
    {
        $fio = $arguments->fio ?? '';
        $phone = $arguments->phone ?? '';
        $email = $arguments->email ?? '';
        $company = $arguments->company ?? '';

        if (empty($fio)) {
            return [
                'success' => false,
                'error' => 'Не указано ФИО для создания контакта'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен'
            ];
        }

        try {
            $fields = [
                'NAME' => $fio,
                'TYPE_ID' => 'CLIENT'
            ];

            if ($phone) {
                $fields['PHONE'] = [['VALUE' => $phone, 'VALUE_TYPE' => 'WORK']];
            }

            if ($email) {
                $fields['EMAIL'] = [['VALUE' => $email, 'VALUE_TYPE' => 'WORK']];
            }

            if ($company) {
                $fields['COMPANY_TITLE'] = $company;
            }

            $result = self::$bitrixApi->createContact($fields);

            return [
                'success' => true,
                'data' => [
                    'id' => $result['result'],
                    'fio' => $fio
                ],
                'message' => "Создан новый контакт: {$fio}"
            ];

        } catch (BitrixAPIException $e) {
            return [
                'success' => false,
                'error' => 'Ошибка Bitrix API: ' . $e->getMessage()
            ];
        }
    }
}
