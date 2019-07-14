<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 1.23
 */

namespace Monorepo\Schema;


use JsonSchema\Validator;

class SchemaValidator
{

    private $schema;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * SchemaValidator constructor.
     * @param $schema
     */
    public function __construct($schema = null)
    {
        $schemaPath = [__DIR__,'..','..','..','resources','monorepo-schema.json'];
        $this->schema = $schema ? $schema : json_decode(file_get_contents(implode(DIRECTORY_SEPARATOR, $schemaPath)));
        $this->validator = new Validator();
    }

    /**
     * Validates the given configuration json
     *
     * @param $rawContent
     * @return bool
     * @throws \RuntimeException on validation errors
     */
    public function validate($rawContent)
    {
        $data = json_decode($rawContent);

        $this->validator->check($data, $this->schema);

        if (!$this->validator->isValid()) {

            $errors = array();

            foreach ($this->validator->getErrors() as $error) {
                $errors[] = sprintf("[%s] %s\n", $error['property'], $error['message']);
            }

            throw new \RuntimeException(sprintf("JSON is not valid \n%s", implode("\n", $errors)));
        }

        return true;
    }

}