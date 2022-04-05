<?php

namespace Aphreton\Routes;

class Test extends \Aphreton\APIRoute {

    public function __construct($parent) {
        parent::__construct($parent);
        $this->setJSONSchemaForEndpoint(
            'default', [
                'type' => 'object',
                'properties' => [
                    'message' => [
                        'type' => 'string'
                    ]
                ],
                'required' => ['message']
            ]
        );
        $this->setRequiredUserLevelForEndpoint('default', 0);
    }

    public function default($params) {
        //$this->testError();
        //$this->testError($params);
        //$this->testException($params);
        return ["Hello, {$this->parent->getUser()->login}, from Test route default endpoint!"];
    }

    public function testException($params) {
        throw new \Aphreton\APIException(
            'Exception from Test route default endpoint!',
            \Aphreton\Models\LogEntry::LOG_LEVEL_WARNING,
            'Exception from Test route default endpoint!'
        );
    }

    public function testError($params) {
        callNonExistingMethod();
    }
}
