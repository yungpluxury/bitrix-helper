<?php

namespace YandexGPT\Functions;

use YandexGPT\Bitrix\BitrixAPI;
use YandexGPT\Bitrix\BitrixAPIException;

class TaskFunctions
{
    private static $bitrixApi = null;

    public static function setBitrixAPI($webhookUrl)
    {
        self::$bitrixApi = new BitrixAPI($webhookUrl);
    }

    public static function createTask($arguments)
    {
        $title = $arguments->title ?? '';
        $description = $arguments->description ?? '';
        $deadline = $arguments->deadline ?? '';
        
        if (empty($title)) {
            return [
                'success' => false,
                'error' => 'Не указано название задачи'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите TaskFunctions::setBitrixAPI()'
            ];
        }

        try {
            $fields = [
                'TITLE' => $title,
                'DESCRIPTION' => $description,
                'CREATED_BY' => 1,
                'RESPONSIBLE_ID' => 1
            ];

            if (!empty($deadline)) {
                $fields['DEADLINE'] = self::parseDeadline($deadline);
            }

            $result = self::$bitrixApi->call('tasks.task.add', [
                'fields' => $fields
            ]);
            
            if (empty($result['result']['task']['id'])) {
                return [
                    'success' => false,
                    'error' => 'Не удалось создать задачу'
                ];
            }

            $taskId = $result['result']['task']['id'];

            return [
                'success' => true,
                'data' => [
                    'id' => $taskId,
                    'title' => $title,
                    'description' => $description,
                    'deadline' => $deadline,
                    'url' => self::$bitrixApi->getTaskUrl($taskId)
                ],
                'message' => "Задача '{$title}' успешно создана"
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

    public static function findTask($arguments)
    {
        $title = $arguments->title ?? '';
        
        if (empty($title)) {
            return [
                'success' => false,
                'error' => 'Не указано название задачи для поиска'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите TaskFunctions::setBitrixAPI()'
            ];
        }

        try {
            $filter = [
                '%TITLE' => "{$title}"
            ];

            $result = self::$bitrixApi->call('tasks.task.list', [
                'filter' => $filter,
                'select' => ['ID', 'TITLE', 'DESCRIPTION', 'STATUS', 'CREATED_BY', 'RESPONSIBLE_ID', 'DEADLINE', 'CREATED_DATE']
            ]);
            
            if (empty($result['result']['tasks'])) {
                return [
                    'success' => false,
                    'message' => "Задача '{$title}' не найдена",
                    'data' => []
                ];
            }

            $tasks = $result['result']['tasks'];
            $task = $tasks[0];

            return [
                'success' => true,
                'data' => [
                    'id' => $task['id'],
                    'title' => $task['title'] ?? '',
                    'description' => $task['description'] ?? '',
                    'status' => $task['status'] ?? '',
                    'created_by' => $task['createdBy'] ?? '',
                    'responsible_id' => $task['responsibleId'] ?? '',
                    'deadline' => $task['deadline'] ?? '',
                    'created_date' => $task['createdDate'] ?? '',
                    'url' => self::$bitrixApi->getTaskUrl($task['id'])
                ],
                'message' => "Найдена задача: " . ($task['title'] ?? $title),
                'total_found' => count($tasks)
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

    public static function updateTask($arguments)
    {
        $taskId = $arguments->taskId ?? '';
        $title = $arguments->title ?? null;
        $description = $arguments->description ?? null;
        $deadline = $arguments->deadline ?? null;
        $responsibleId = $arguments->responsibleId ?? null;
        
        if (empty($taskId)) {
            return [
                'success' => false,
                'error' => 'Не указан ID задачи'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите TaskFunctions::setBitrixAPI()'
            ];
        }

        try {
            $fields = [];
            
            if ($title !== null) {
                $fields['TITLE'] = $title;
            }
            if ($description !== null) {
                $fields['DESCRIPTION'] = $description;
            }
            if ($deadline !== null) {
                $fields['DEADLINE'] = self::parseDeadline($deadline);
            }
            if ($responsibleId !== null) {
                $fields['RESPONSIBLE_ID'] = (int)$responsibleId;
            }

            if (empty($fields)) {
                return [
                    'success' => false,
                    'error' => 'Не указаны поля для обновления'
                ];
            }

            $result = self::$bitrixApi->call('tasks.task.update', [
                'taskId' => $taskId,
                'fields' => $fields
            ]);
            
            if (empty($result['result']['task'])) {
                return [
                    'success' => false,
                    'error' => 'Не удалось обновить задачу'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $taskId,
                    'updated_fields' => array_keys($fields)
                ],
                'message' => "Задача #{$taskId} успешно обновлена"
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

    public static function getTask($arguments)
    {
        $taskId = $arguments->taskId ?? '';
        
        if (empty($taskId)) {
            return [
                'success' => false,
                'error' => 'Не указан ID задачи'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите TaskFunctions::setBitrixAPI()'
            ];
        }

        try {
            $result = self::$bitrixApi->call('tasks.task.get', [
                'taskId' => $taskId,
                'select' => ['ID', 'TITLE', 'DESCRIPTION', 'STATUS', 'PRIORITY', 'CREATED_BY', 'RESPONSIBLE_ID', 'DEADLINE', 'CREATED_DATE', 'CHANGED_DATE', 'CLOSED_DATE']
            ]);
            
            if (empty($result['result']['task'])) {
                return [
                    'success' => false,
                    'error' => 'Задача не найдена'
                ];
            }

            $task = $result['result']['task'];

            return [
                'success' => true,
                'data' => [
                    'id' => $task['id'],
                    'title' => $task['title'] ?? '',
                    'description' => $task['description'] ?? '',
                    'status' => $task['status'] ?? '',
                    'priority' => $task['priority'] ?? '',
                    'created_by' => $task['createdBy'] ?? '',
                    'responsible_id' => $task['responsibleId'] ?? '',
                    'deadline' => $task['deadline'] ?? '',
                    'created_date' => $task['createdDate'] ?? '',
                    'changed_date' => $task['changedDate'] ?? '',
                    'closed_date' => $task['closedDate'] ?? '',
                    'url' => self::$bitrixApi->getTaskUrl($task['id'])
                ],
                'message' => "Информация о задаче #{$taskId}"
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

    public static function deleteTask($arguments)
    {
        $taskId = $arguments->taskId ?? '';
        
        if (empty($taskId)) {
            return [
                'success' => false,
                'error' => 'Не указан ID задачи'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите TaskFunctions::setBitrixAPI()'
            ];
        }

        try {
            $result = self::$bitrixApi->call('tasks.task.delete', [
                'taskId' => $taskId
            ]);
            
            if (empty($result['result']['task'])) {
                return [
                    'success' => false,
                    'error' => 'Не удалось удалить задачу'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $taskId
                ],
                'message' => "Задача #{$taskId} успешно удалена"
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

    public static function startTask($arguments)
    {
        $taskId = $arguments->taskId ?? '';
        
        if (empty($taskId)) {
            return [
                'success' => false,
                'error' => 'Не указан ID задачи'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите TaskFunctions::setBitrixAPI()'
            ];
        }

        try {
            $result = self::$bitrixApi->call('tasks.task.start', [
                'taskId' => $taskId
            ]);
            
            if (empty($result['result']['task'])) {
                return [
                    'success' => false,
                    'error' => 'Не удалось начать выполнение задачи'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $taskId,
                    'status' => 'in_progress'
                ],
                'message' => "Задача #{$taskId} переведена в статус 'Выполняется'"
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

    public static function completeTask($arguments)
    {
        $taskId = $arguments->taskId ?? '';
        
        if (empty($taskId)) {
            return [
                'success' => false,
                'error' => 'Не указан ID задачи'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите TaskFunctions::setBitrixAPI()'
            ];
        }

        try {
            $result = self::$bitrixApi->call('tasks.task.complete', [
                'taskId' => $taskId
            ]);
            
            if (empty($result['result']['task'])) {
                return [
                    'success' => false,
                    'error' => 'Не удалось завершить задачу'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $taskId,
                    'status' => 'completed'
                ],
                'message' => "Задача #{$taskId} успешно завершена"
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

    public static function deferTask($arguments)
    {
        $taskId = $arguments->taskId ?? '';
        
        if (empty($taskId)) {
            return [
                'success' => false,
                'error' => 'Не указан ID задачи'
            ];
        }

        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите TaskFunctions::setBitrixAPI()'
            ];
        }

        try {
            $result = self::$bitrixApi->call('tasks.task.defer', [
                'taskId' => $taskId
            ]);
            
            if (empty($result['result']['task'])) {
                return [
                    'success' => false,
                    'error' => 'Не удалось отложить задачу'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $taskId,
                    'status' => 'deferred'
                ],
                'message' => "Задача #{$taskId} отложена"
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

    public static function getTasksList($arguments)
    {
        $status = $arguments->status ?? null;
        $responsibleId = $arguments->responsibleId ?? null;
        $createdBy = $arguments->createdBy ?? null;
        $limit = $arguments->limit ?? 10;
        
        if (!self::$bitrixApi) {
            return [
                'success' => false,
                'error' => 'Bitrix API не настроен. Вызовите TaskFunctions::setBitrixAPI()'
            ];
        }

        try {
            $filter = [];
            
            if ($status !== null) {
                $filter['STATUS'] = $status;
            }
            if ($responsibleId !== null) {
                $filter['RESPONSIBLE_ID'] = (int)$responsibleId;
            }
            if ($createdBy !== null) {
                $filter['CREATED_BY'] = (int)$createdBy;
            }

            $result = self::$bitrixApi->call('tasks.task.list', [
                'filter' => $filter,
                'select' => ['ID', 'TITLE', 'DESCRIPTION', 'STATUS', 'PRIORITY', 'CREATED_BY', 'RESPONSIBLE_ID', 'DEADLINE', 'CREATED_DATE'],
                'order' => ['ID' => 'DESC'],
                'start' => 0
            ]);
            
            $tasks = $result['result']['tasks'] ?? [];
            $limitedTasks = array_slice($tasks, 0, (int)$limit);

            $formattedTasks = [];
            foreach ($limitedTasks as $task) {
                $formattedTasks[] = [
                    'id' => $task['id'],
                    'title' => $task['title'] ?? '',
                    'description' => $task['description'] ?? '',
                    'status' => $task['status'] ?? '',
                    'priority' => $task['priority'] ?? '',
                    'created_by' => $task['createdBy'] ?? '',
                    'responsible_id' => $task['responsibleId'] ?? '',
                    'deadline' => $task['deadline'] ?? '',
                    'created_date' => $task['createdDate'] ?? '',
                    'url' => self::$bitrixApi->getTaskUrl($task['id'])
                ];
            }

            return [
                'success' => true,
                'data' => $formattedTasks,
                'message' => "Найдено задач: " . count($formattedTasks),
                'total_found' => count($tasks),
                'limit' => (int)$limit
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


    private static function parseDeadline($deadline)
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) {
            return $deadline;
        }

        $currentYear = date('Y');
        $currentMonth = date('n');
        
        if (preg_match('/(\d{1,2})\s+(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)\s+(текущего\s+года|этого\s+года)/u', $deadline, $matches)) {
            $day = (int)$matches[1];
            $monthName = $matches[2];
            $year = $currentYear;
            
            $months = [
                'января' => 1, 'февраля' => 2, 'марта' => 3, 'апреля' => 4,
                'мая' => 5, 'июня' => 6, 'июля' => 7, 'августа' => 8,
                'сентября' => 9, 'октября' => 10, 'ноября' => 11, 'декабря' => 12
            ];
            
            $month = $months[$monthName] ?? null;
            if ($month) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }
        
        if (preg_match('/через\s+(\d+)\s+дн?[ея]?/u', $deadline, $matches)) {
            $days = (int)$matches[1];
            $date = date('Y-m-d', strtotime("+{$days} days"));
            return $date;
        }
        
        if (strpos($deadline, 'завтра') !== false) {
            return date('Y-m-d', strtotime('+1 day'));
        }
        
        if (strpos($deadline, 'послезавтра') !== false) {
            return date('Y-m-d', strtotime('+2 days'));
        }
        
        return $deadline;
    }
}
