<?php

namespace Deplink\Validators;

use Deplink\Validators\Exceptions\JsonDecodeException;
use Deplink\Validators\Exceptions\ValidationException;
use JsonSchema\Validator;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

/**
 * Check correctness of the json string
 * as well as compatibility with schema.
 */
class JsonValidator
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var JsonParser
     */
    private $parser;

    /**
     * @param Validator $validator
     * @param JsonParser $parser
     */
    public function __construct(Validator $validator, JsonParser $parser)
    {
        $this->validator = $validator;
        $this->parser = $parser;
    }

    /**
     * Ensure that json match given schema.
     *
     * @param string|object $json
     * @param string|object $schema
     * @throws JsonDecodeException
     * @throws ParsingException
     * @throws ValidationException
     */
    public function validate($json, $schema)
    {
        $json = $this->jsonDecode($json);
        $schema = $this->jsonDecode($schema);

        $this->validator->check($json, $schema);
        if (!$this->validator->isValid()) {
            $error = $this->validator->getErrors()[0];
            throw new ValidationException("Json not match given schema, the '{$error['property']}' property failed with message: '{$error['message']}'.");
        }
    }

    /**
     * @param string|object $json
     * @return object
     * @throws JsonDecodeException
     * @throws ParsingException
     */
    private function jsonDecode($json)
    {
        // Skip if already decoded.
        if (is_object($json)) {
            return $json;
        }

        $this->parser->parse($json);
        $result = json_decode($json);
        if (!is_object($result)) {
            throw new JsonDecodeException("Json cannot be decoded.");
        }

        return $result;
    }
}
