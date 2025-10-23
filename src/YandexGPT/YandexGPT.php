<?php

namespace YandexGPT;

use YandexGPT\Functions\ContactFunctions;
use YandexGPT\Functions\LeadFunctions;
use YandexGPT\Functions\DealFunctions;
use YandexGPT\Functions\CompanyFunctions;

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