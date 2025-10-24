<?php

header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Метод не поддерживается');
}

if (!isset($_POST['query']) || empty(trim($_POST['query']))) {
    http_response_code(400);
    die('❌ Ошибка: Запрос не может быть пустым');
}

require_once 'src/YandexGPT/Config.php';
require_once 'src/YandexGPT/FunctionHandler.php';
require_once 'src/YandexGPT/Bitrix/BitrixAPI.php';
require_once 'src/YandexGPT/Bitrix/BitrixAPIException.php';
require_once 'src/YandexGPT/Functions/ContactFunctions.php';
require_once 'src/YandexGPT/Functions/LeadFunctions.php';
require_once 'src/YandexGPT/Functions/DealFunctions.php';
require_once 'src/YandexGPT/Functions/CompanyFunctions.php';
require_once 'src/YandexGPT/Functions/TaskFunctions.php';
require_once 'src/YandexGPT/YandexGPT.php';

use YandexGPT\YandexGPT;
use YandexGPT\Config;
use YandexGPT\Functions\ContactFunctions;
use YandexGPT\Functions\LeadFunctions;
use YandexGPT\Functions\DealFunctions;
use YandexGPT\Functions\CompanyFunctions;
use YandexGPT\Functions\TaskFunctions;

try {
    Config::load('.env');
    
    $options = Config::getYandexOptions();
    $bitrixWebhook = Config::getBitrixWebhookUrl();
    
    if (empty($options['apiKey']) || empty($options['folderId'])) {
        die("❌ Ошибка: Не заданы YANDEX_API_KEY или YANDEX_FOLDER_ID в .env файле\n");
    }
    
    if (empty($bitrixWebhook)) {
        die("❌ Ошибка: Не задан BITRIX_WEBHOOK_URL в .env файле\n");
    }
    
    ContactFunctions::setBitrixAPI($bitrixWebhook);
    LeadFunctions::setBitrixAPI($bitrixWebhook);
    DealFunctions::setBitrixAPI($bitrixWebhook);
    CompanyFunctions::setBitrixAPI($bitrixWebhook);
    TaskFunctions::setBitrixAPI($bitrixWebhook);
    
    $gpt = new YandexGPT($options);
    $query = trim($_POST['query']);
    
    echo "🤖 Запрос: {$query}\n\n";
    
    $response = $gpt->callCompletion($query);
    
    if (is_array($response) && $response['type'] === 'function_calls') {
        echo "🔧 Вызваны функции:\n";
        echo str_repeat("=", 50) . "\n\n";
        
        foreach ($response['calls'] as $index => $call) {
            echo "Функция #" . ($index + 1) . ": " . $call['function'] . "\n";
            echo str_repeat("-", 30) . "\n";
            
            if (isset($call['result'])) {
                $result = $call['result'];
                if ($result['success']) {
                    echo "✅ Успешно: " . $result['message'] . "\n";
                    if (!empty($result['data'])) {
                        echo "\n📊 Данные:\n";
                        foreach ($result['data'] as $key => $value) {
                            if (!empty($value)) {
                                if ($key === 'url') {
                                    echo "  🔗 {$key}: {$value} (ссылка в Bitrix24)\n";
                                } else {
                                    echo "  📝 {$key}: {$value}\n";
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
            
            echo "\n";
        }
        
        echo str_repeat("=", 50) . "\n";
        echo "✅ Обработка завершена\n";
        
    } else {
        echo "💬 Ответ от YandexGPT:\n";
        echo str_repeat("-", 30) . "\n";
        echo $response . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Критическая ошибка: " . $e->getMessage() . "\n";
    echo "\nПроверь:\n";
    echo "- Настроен ли .env файл\n";
    echo "- Правильные ли API ключи\n";
    echo "- Доступен ли Bitrix24 webhook\n";
}
