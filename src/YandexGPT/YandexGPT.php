<?php

namespace YandexGPT;

use YandexGPT\Functions\ContactFunctions;
use YandexGPT\Functions\LeadFunctions;
use YandexGPT\Functions\DealFunctions;
use YandexGPT\Functions\CompanyFunctions;
use YandexGPT\Functions\TaskFunctions;

class YandexGPT
{
    public $apiUri = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion';
    public $apiKey;
    public $modelUrl;
    public $folderId;
    private $functionHandler;

    public function __construct($options)
    {
        $this->apiKey = $options['apiKey'];
        $this->folderId = $options['folderId'];

        $this->modelUrl = 'gpt://' . $options['folderId'] . '/' . $options['modelName'];
        
        $this->functionHandler = new FunctionHandler();
        $this->registerDefaultFunctions();
    }

    private function registerDefaultFunctions()
    {
        $this->functionHandler->register(
            'getContactTool',
            [ContactFunctions::class, 'getContact'],
            'Получает информацию о контакте по ФИО.',
            [
                'fio' => [
                    'type' => 'string',
                    'description' => 'ФИО контакта'
                ]
            ]
        );

        $this->functionHandler->register(
            'findLeadTool',
            [LeadFunctions::class, 'findLead'],
            'Ищет лид по названию.',
            [
                'title' => [
                    'type' => 'string',
                    'description' => 'Название лида'
                ]
            ]
        );

        $this->functionHandler->register(
            'findDealTool',
            [DealFunctions::class, 'findDeal'],
            'Ищет сделку по названию.',
            [
                'title' => [
                    'type' => 'string',
                    'description' => 'Название сделки'
                ]
            ]
        );

        $this->functionHandler->register(
            'findCompanyTool',
            [CompanyFunctions::class, 'findCompany'],
            'Ищет компанию по названию.',
            [
                'title' => [
                    'type' => 'string',
                    'description' => 'Название компании'
                ]
            ]
        );

        $this->functionHandler->register(
            'createTaskTool',
            [TaskFunctions::class, 'createTask'],
            'Создает новую задачу с указанным названием, описанием и крайним сроком.',
            [
                'title' => [
                    'type' => 'string',
                    'description' => 'Название задачи'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Описание задачи'
                ],
                'deadline' => [
                    'type' => 'string',
                    'description' => 'Крайний срок выполнения задачи (YYYY-MM-DD, "14 ноября текущего года", "через 3 дня", "завтра" и т.д.)'
                ]
            ]
        );

        $this->functionHandler->register(
            'findTaskTool',
            [TaskFunctions::class, 'findTask'],
            'Ищет задачу по названию.',
            [
                'title' => [
                    'type' => 'string',
                    'description' => 'Название задачи'
                ]
            ]
        );

        $this->functionHandler->register(
            'updateTaskTool',
            [TaskFunctions::class, 'updateTask'],
            'Обновляет существующую задачу. Можно изменить название, описание, крайний срок или исполнителя.',
            [
                'taskId' => [
                    'type' => 'string',
                    'description' => 'ID задачи для обновления'
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Новое название задачи (опционально)'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Новое описание задачи (опционально)'
                ],
                'deadline' => [
                    'type' => 'string',
                    'description' => 'Новый крайний срок (опционально)'
                ],
                'responsibleId' => [
                    'type' => 'string',
                    'description' => 'ID нового исполнителя (опционально)'
                ]
            ]
        );

        $this->functionHandler->register(
            'getTaskTool',
            [TaskFunctions::class, 'getTask'],
            'Получает детальную информацию о задаче по ID.',
            [
                'taskId' => [
                    'type' => 'string',
                    'description' => 'ID задачи'
                ]
            ]
        );

        $this->functionHandler->register(
            'deleteTaskTool',
            [TaskFunctions::class, 'deleteTask'],
            'Удаляет задачу по ID.',
            [
                'taskId' => [
                    'type' => 'string',
                    'description' => 'ID задачи для удаления'
                ]
            ]
        );

        $this->functionHandler->register(
            'startTaskTool',
            [TaskFunctions::class, 'startTask'],
            'Переводит задачу в статус "Выполняется".',
            [
                'taskId' => [
                    'type' => 'string',
                    'description' => 'ID задачи'
                ]
            ]
        );

        $this->functionHandler->register(
            'completeTaskTool',
            [TaskFunctions::class, 'completeTask'],
            'Завершает задачу.',
            [
                'taskId' => [
                    'type' => 'string',
                    'description' => 'ID задачи'
                ]
            ]
        );

        $this->functionHandler->register(
            'deferTaskTool',
            [TaskFunctions::class, 'deferTask'],
            'Откладывает задачу.',
            [
                'taskId' => [
                    'type' => 'string',
                    'description' => 'ID задачи'
                ]
            ]
        );

        $this->functionHandler->register(
            'getTasksListTool',
            [TaskFunctions::class, 'getTasksList'],
            'Получает список задач с возможностью фильтрации по статусу, исполнителю или создателю.',
            [
                'status' => [
                    'type' => 'string',
                    'description' => 'Статус задач для фильтрации (опционально)'
                ],
                'responsibleId' => [
                    'type' => 'string',
                    'description' => 'ID исполнителя для фильтрации (опционально)'
                ],
                'createdBy' => [
                    'type' => 'string',
                    'description' => 'ID создателя для фильтрации (опционально)'
                ],
                'limit' => [
                    'type' => 'string',
                    'description' => 'Максимальное количество задач (по умолчанию 10)'
                ]
            ]
        );

    }

    public function callCompletion($message)
    {
        $messages = [
            (object) [
                'role' => 'user',
                'text' => $message
            ]
        ];
        
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $this->apiUri,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    "modelUri" => $this->modelUrl,
                    "tools" => $this->functionHandler->getFunctionList(),
                    "messages" => $messages,
                ], 1),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Api-Key ' . $this->apiKey,
                    'x-folder-id: ' . $this->folderId
                ],
            ]
        );

        $response = curl_exec($curl);
        $res = json_decode($response);
        curl_close($curl);

        return $this->processResponse($res);
    }

    private function processResponse($response)
    {
        if (isset($response->error)) {
            return $response;
        }

        $message = $response->result->alternatives[0]->message;
        
        if (isset($message->toolCallList->toolCalls)) {
            $functionResults = [];
            
            foreach ($message->toolCallList->toolCalls as $toolCall) {
                $functionName = $toolCall->functionCall->name;
                $arguments = $toolCall->functionCall->arguments;
                
                if ($this->functionHandler->hasFunction($functionName)) {
                    try {
                        $result = $this->functionHandler->call($functionName, $arguments);
                        $functionResults[] = [
                            'function' => $functionName,
                            'result' => $result
                        ];
                    } catch (\Exception $e) {
                        $functionResults[] = [
                            'function' => $functionName,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }
            
            return [
                'type' => 'function_calls',
                'calls' => $functionResults,
                'raw_response' => $response
            ];
        }
        
        return $message->text ?? $response;
    }

    public function registerFunction($name, $callback, $description = '', $parameters = [])
    {
        $this->functionHandler->register($name, $callback, $description, $parameters);
    }
}