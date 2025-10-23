<?php

namespace YandexGPT;

class FunctionHandler
{
    private $functions = [];

    public function register($name, $callback, $description = '', $parameters = [])
    {
        $this->functions[$name] = [
            'callback' => $callback,
            'description' => $description,
            'parameters' => $parameters
        ];
    }

    public function call($functionName, $arguments)
    {
        if (!isset($this->functions[$functionName])) {
            throw new \Exception("Function {$functionName} not found");
        }

        $function = $this->functions[$functionName];
        return call_user_func($function['callback'], $arguments);
    }

    public function getFunctionList()
    {
        $list = [];
        foreach ($this->functions as $name => $function) {
            $list[] = [
                "type" => "function",
                "function" => [
                    "name" => $name,
                    "description" => $function['description'],
                    "parameters" => [
                        "type" => "object",
                        "properties" => $function['parameters'],
                        "required" => array_keys($function['parameters'])
                    ]
                ]
            ];
        }
        return $list;
    }

    public function hasFunction($name)
    {
        return isset($this->functions[$name]);
    }
}
