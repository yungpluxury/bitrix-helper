# YandexGPT Function Calling для Bitrix24

Библиотека для интеграции YandexGPT с Bitrix24 через function calling. Позволяет ИИ работать с данными CRM: искать контакты, лиды, сделки и компании.

## 🚀 Возможности

- **Function Calling** - YandexGPT может вызывать функции для работы с Bitrix24
- **Полный цикл работы с задачами** - создание, обновление, удаление, управление статусами
- **Поиск контактов** по ФИО
- **Поиск лидов** по названию
- **Поиск сделок** по названию  
- **Поиск компаний** по названию
- **Web-интерфейс** для тестирования
- **REST API** для интеграции

## 📦 Установка

```bash
# Клонируем репозиторий
git clone <repository-url>
cd yandex-function-calling

# Устанавливаем зависимости
composer install

# Создаём .env файл
composer run setup

# Делаем скрипт запуска исполняемым
chmod +x start_server.sh
```

## ⚙️ Настройка

1. **Создайте .env файл** (уже создан командой `composer run setup`)

2. **Получите API ключи Yandex Cloud:**
   - Зайдите в [Yandex Cloud Console](https://console.cloud.yandex.ru/)
   - Создайте сервисный аккаунт
   - Получите API ключ и Folder ID

3. **Настройте Bitrix24 webhook:**
   - В Bitrix24 перейдите в Приложения → Разработчикам → Другие → Вебхуки
   - Создайте входящий вебхук с правами: CRM, Контакты, Лиды, Сделки, Компании

4. **Заполните .env файл:**
```env
# Yandex Cloud настройки
YANDEX_API_KEY=your-yandex-api-key
YANDEX_FOLDER_ID=your-folder-id
YANDEX_MODEL_NAME=yandexgpt

# Bitrix24 настройки  
BITRIX_WEBHOOK_URL=https://your-domain.bitrix24.ru/rest/1/webhook-code/
```

## 🎯 Использование

### Через код

```php
<?php
require_once 'vendor/autoload.php';

use YandexGPT\YandexGPT;
use YandexGPT\Config;
use YandexGPT\Functions\ContactFunctions;
use YandexGPT\Functions\LeadFunctions;
use YandexGPT\Functions\DealFunctions;
use YandexGPT\Functions\CompanyFunctions;

// Загружаем конфигурацию
Config::load('.env');

$options = Config::getYandexOptions();
$bitrixWebhook = Config::getBitrixWebhookUrl();

// Настраиваем Bitrix API для всех функций
ContactFunctions::setBitrixAPI($bitrixWebhook);
LeadFunctions::setBitrixAPI($bitrixWebhook);
DealFunctions::setBitrixAPI($bitrixWebhook);
CompanyFunctions::setBitrixAPI($bitrixWebhook);

// Создаём GPT клиент
$gpt = new YandexGPT($options);

// Задаём вопрос
$response = $gpt->callCompletion('Найди контакт Иванов Иван Иванович');

// Обрабатываем ответ
if (is_array($response) && $response['type'] === 'function_calls') {
    foreach ($response['calls'] as $call) {
        if ($call['result']['success']) {
            echo "✅ Найден: " . $call['result']['message'];
        } else {
            echo "❌ Ошибка: " . $call['result']['error'];
        }
    }
} else {
    echo "Ответ GPT: " . $response;
}
```

### Через веб-интерфейс

1. Запустите локальный сервер:
```bash
# Вариант 1: через скрипт (рекомендуется)
./start_server.sh

# Вариант 2: напрямую
php -S localhost:8000
```

2. Откройте `http://localhost:8000` в браузере

3. Задавайте вопросы вроде:
   - "Найди контакт Петров Петр Петрович"
   - "Есть ли лид по проекту 'Разработка сайта'?"
   - "Покажи сделку 'Продажа оборудования'"
   - "Создай задачу 'Подготовить отчет' с дедлайном завтра"
   - "Покажи все мои задачи"
   - "Заверши задачу #123"

### Через API

```bash
curl -X POST http://localhost:8000/api.php \
  -d "query=Найди компанию ООО Рога и копыта"
```

## 🔧 Доступные функции

### CRM функции
| Функция | Описание | Параметр |
|---------|----------|----------|
| `getContactTool` | Поиск контакта по ФИО | `fio` |
| `findLeadTool` | Поиск лида по названию | `title` |
| `findDealTool` | Поиск сделки по названию | `title` |
| `findCompanyTool` | Поиск компании по названию | `title` |

### Функции работы с задачами
| Функция | Описание | Параметры |
|---------|----------|-----------|
| `createTaskTool` | Создание новой задачи | `title`, `description`, `deadline` |
| `findTaskTool` | Поиск задачи по названию | `title` |
| `getTaskTool` | Получение информации о задаче | `taskId` |
| `updateTaskTool` | Обновление задачи | `taskId`, `title`, `description`, `deadline`, `responsibleId` |
| `deleteTaskTool` | Удаление задачи | `taskId` |
| `startTaskTool` | Начало выполнения задачи | `taskId` |
| `completeTaskTool` | Завершение задачи | `taskId` |
| `deferTaskTool` | Отложение задачи | `taskId` |
| `getTasksListTool` | Получение списка задач | `status`, `responsibleId`, `createdBy`, `limit` |

## 📁 Структура проекта

```
├── src/YandexGPT/             # Основная библиотека
│   ├── YandexGPT.php          # Основной класс
│   ├── Config.php             # Конфигурация
│   ├── FunctionHandler.php    # Обработчик функций
│   ├── Bitrix/
│   │   ├── BitrixAPI.php      # API для Bitrix24
│   │   └── BitrixAPIException.php
│   └── Functions/
│       ├── ContactFunctions.php
│       ├── LeadFunctions.php
│       ├── DealFunctions.php
│       ├── CompanyFunctions.php
│       └── TaskFunctions.php
├── index.html                 # Веб-интерфейс
├── api.php                    # REST API endpoint
├── example.php                # Пример использования
├── test_tasks.php             # Базовые тесты задач
├── test_extended_tasks.php    # Расширенные тесты задач
├── task_examples.php          # Интерактивные примеры
├── TASK_FUNCTIONS.md          # Документация по задачам
├── start_server.sh            # Скрипт запуска сервера
├── env.example                # Пример конфигурации
└── composer.json              # Зависимости
```

## 📋 Примеры работы с задачами

### Создание и управление задачами

```php
// Создание задачи
$response = $gpt->callCompletion('Создай задачу "Подготовить отчет" с дедлайном завтра');

// Поиск задач
$response = $gpt->callCompletion('Найди задачу "отчет"');

// Получение списка задач
$response = $gpt->callCompletion('Покажи последние 5 задач');

// Обновление задачи
$response = $gpt->callCompletion('Обнови задачу #123: измени название на "Срочный отчет"');

// Управление статусами
$response = $gpt->callCompletion('Начни выполнение задачи #123');
$response = $gpt->callCompletion('Заверши задачу #123');
$response = $gpt->callCompletion('Отложи задачу #123');

// Дополнительные функции
$response = $gpt->callCompletion('Удали задачу #123');
```

### Тестирование

```bash
# Полный автоматический тест всех функций задач
php test_extended_tasks.php

# Интерактивные примеры
php task_examples.php

# Базовые тесты
php test_tasks.php
```

Подробная документация по функциям задач: [TASK_FUNCTIONS.md](TASK_FUNCTIONS.md)

## 🛠️ Расширение функциональности

Добавить новую функцию:

```php
$gpt->registerFunction(
    'myCustomFunction',
    [MyClass::class, 'myMethod'],
    'Описание функции',
    [
        'param1' => [
            'type' => 'string',
            'description' => 'Описание параметра'
        ]
    ]
);
```

## 🐛 Отладка

- Проверьте логи в консоли браузера
- Убедитесь, что все API ключи корректны
- Проверьте права вебхука в Bitrix24
- Используйте `example.php` для тестирования
