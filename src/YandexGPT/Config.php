<?php

namespace YandexGPT;

class Config
{
    private static $config = [];

    public static function load($envFile = '.env')
    {
        if (!file_exists($envFile)) {
            throw new \Exception("Environment file {$envFile} not found");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                self::$config[trim($key)] = trim($value);
            }
        }
    }

    public static function get($key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }

    public static function getYandexOptions()
    {
        return [
            'apiKey' => self::get('YANDEX_API_KEY'),
            'folderId' => self::get('YANDEX_FOLDER_ID'),
            'modelName' => self::get('YANDEX_MODEL_NAME', 'yandexgpt'),
        ];
    }

    public static function getBitrixWebhookUrl()
    {
        return self::get('BITRIX_WEBHOOK_URL');
    }
}
