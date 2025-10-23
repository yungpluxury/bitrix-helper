<?php

namespace YandexGPT\Bitrix;

class BitrixAPI
{
    private $webhookUrl;
    private $portalUrl;
    private $timeout = 30;

    public function __construct($webhookUrl)
    {
        $this->webhookUrl = rtrim($webhookUrl, '/') . '/';
        $this->portalUrl = $this->extractPortalUrl($webhookUrl);
    }

    private function extractPortalUrl($webhookUrl)
    {
        $parsed = parse_url($webhookUrl);
        return $parsed['scheme'] . '://' . $parsed['host'];
    }

    public function getContactUrl($contactId)
    {
        return $this->portalUrl . '/crm/contact/details/' . $contactId . '/';
    }

    public function getLeadUrl($leadId)
    {
        return $this->portalUrl . '/crm/lead/details/' . $leadId . '/';
    }

    public function getDealUrl($dealId)
    {
        return $this->portalUrl . '/crm/deal/details/' . $dealId . '/';
    }

    public function getCompanyUrl($companyId)
    {
        return $this->portalUrl . '/crm/company/details/' . $companyId . '/';
    }

    public function call($method, $params = [])
    {
        $url = $this->webhookUrl . $method;
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new BitrixAPIException("cURL Error: " . $error);
        }

        if ($httpCode !== 200) {
            throw new BitrixAPIException("HTTP Error: " . $httpCode);
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BitrixAPIException("JSON Decode Error: " . json_last_error_msg());
        }

        if (isset($data['error'])) {
            throw new BitrixAPIException(
                "Bitrix API Error: " . $data['error_description'], 
                $data['error']
            );
        }

        return $data;
    }


    public function getContacts($filter = [], $select = ['*'], $order = ['ID' => 'ASC'], $start = 0)
    {
        return $this->call('crm.contact.list', [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'start' => $start
        ]);
    }


    public function findContactByFio($fio)
    {
        $filter = [
            'LOGIC' => 'OR',
            ['NAME' => $fio],
            ['SECOND_NAME' => $fio],
            ['LAST_NAME' => $fio],
            ['FULL_NAME' => $fio]
        ];

        $words = explode(' ', trim($fio));
        if (count($words) > 1) {
            foreach ($words as $word) {
                if (strlen($word) > 2) {
                    $filter[] = ['NAME' => "%{$word}%"];
                    $filter[] = ['SECOND_NAME' => "%{$word}%"];
                    $filter[] = ['LAST_NAME' => "%{$word}%"];
                }
            }
        }

        return $this->getContacts($filter, [
            'ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'FULL_NAME',
            'PHONE', 'EMAIL', 'COMPANY_TITLE', 'POST'
        ]);
    }

    public function getContact($id)
    {
        return $this->call('crm.contact.get', ['id' => $id]);
    }

    public function createContact($fields)
    {
        return $this->call('crm.contact.add', ['fields' => $fields]);
    }

    public function updateContact($id, $fields)
    {
        return $this->call('crm.contact.update', [
            'id' => $id,
            'fields' => $fields
        ]);
    }

    public function getDeals($filter = [], $select = ['*'], $order = ['ID' => 'ASC'], $start = 0)
    {
        return $this->call('crm.deal.list', [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'start' => $start
        ]);
    }

    public function getCompanies($filter = [], $select = ['*'], $order = ['ID' => 'ASC'], $start = 0)
    {
        return $this->call('crm.company.list', [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'start' => $start
        ]);
    }

    public function __call($method, $args)
    {
        $params = isset($args[0]) ? $args[0] : [];
        return $this->call($method, $params);
    }
}
