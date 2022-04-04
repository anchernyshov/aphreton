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
        //TODO: fix PHP data leak in error messages:
        //$this->testError();
        //$this->testError($params);
        return ["Hello, {$this->parent->getUser()->login}, from Test route default endpoint!"];
    }

    public function testException($params) {
        throw new \Exception('Exception from Test route default endpoint!');
    }

    public function testError($params) {
        callNonExistingMethod();
    }
}
