<?php

require_once 'src/YandexGPT/Config.php';
require_once 'src/YandexGPT/FunctionHandler.php';
require_once 'src/YandexGPT/Bitrix/BitrixAPI.php';
require_once 'src/YandexGPT/Bitrix/BitrixAPIException.php';
require_once 'src/YandexGPT/Functions/ContactFunctions.php';
require_once 'src/YandexGPT/Functions/LeadFunctions.php';
require_once 'src/YandexGPT/Functions/DealFunctions.php';
require_once 'src/YandexGPT/Functions/CompanyFunctions.php';
require_once 'src/YandexGPT/YandexGPT.php';

use YandexGPT\YandexGPT;
use YandexGPT\Config;
use YandexGPT\Functions\ContactFunctions;
use YandexGPT\Functions\LeadFunctions;
use YandexGPT\Functions\DealFunctions;
use YandexGPT\Functions\CompanyFunctions;

Config::load('.env');

$options = Config::getYandexOptions();
$bitrixWebhook = Config::getBitrixWebhookUrl();

if (empty($options['apiKey']) || empty($options['folderId'])) {
    die("Ошибка: Не заданы YANDEX_API_KEY или YANDEX_FOLDER_ID в .env файле\n");
}

if (empty($bitrixWebhook)) {
    die("Ошибка: Не задан BITRIX_WEBHOOK_URL в .env файле\n");
}

ContactFunctions::setBitrixAPI($bitrixWebhook);
LeadFunctions::setBitrixAPI($bitrixWebhook);
DealFunctions::setBitrixAPI($bitrixWebhook);
CompanyFunctions::setBitrixAPI($bitrixWebhook);

try {
    $gpt = new YandexGPT($options);
    $response = $gpt->callCompletion('А мы работаем с Довлет ООО?');
    
    if (is_array($response) && $response['type'] === 'function_calls') {
        echo "Вызваны функции:\n";
        foreach ($response['calls'] as $call) {
            echo "Функция: " . $call['function'] . "\n";
            if (isset($call['result'])) {
                $result = $call['result'];
                if ($result['success']) {
                    echo "✅ Успешно: " . $result['message'] . "\n";
                    if (!empty($result['data'])) {
                        echo "Данные контакта:\n";
                        foreach ($result['data'] as $key => $value) {
                            if (!empty($value)) {
                                if ($key === 'url') {
                                    echo "  {$key}: {$value} (ссылка в Bitrix24)\n";
                                } else {
                                    echo "  {$key}: {$value}\n";
                                }
                            }
                        }
                    }
                } else {
                    echo "❌ Ошибка: " . ($result['error'] ?? $result['message'] ?? 'Неизвестная ошибка') . "\n";
                }
            }
            if (isset($call['error'])) {
                echo "❌ Системная ошибка: " . $call['error'] . "\n";
            }
            echo "---\n";
        }
    } else {
        echo "Ответ от Yandex GPT: " . $response . "\n";
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
