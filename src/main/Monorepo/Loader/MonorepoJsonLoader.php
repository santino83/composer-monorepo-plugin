<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 1.17
 */

namespace Monorepo\Loader;


use Monorepo\Schema\SchemaValidator;
use Monorepo\Utils\FileUtils;

class MonorepoJsonLoader
{

    /**
     * @var SchemaValidator
     */
    private $validator;

    /**
     * MonorepoJsonLoader constructor.
     * @param SchemaValidator|null $validator
     */
    public function __construct($validator = null)
    {
        $this->validator = $validator ? $validator : new SchemaValidator();
    }

    /**
     * @param string $file full path to monorepo.json file
     * @return array
     * @throws \RuntimeException on errors
     */
    public function fromFile($file)
    {
        try{
            $content = FileUtils::read_file($file);
            return $this->fromJson($content);
        }catch (\Exception $ex){
            throw new \RuntimeException(sprintf("Unable to parse %s : \n%s", $file, $ex->getMessage()));
        }
    }

    /**
     * @param string $json the content of monorepo.json
     * @return array
     * @throws \RuntimeException on errors
     */
    public function fromJson($json)
    {
        $this->validator->validate($json);

        try{
            $monorepoJson = json_decode($json, true);
        }catch (\Exception $ex){
            $monorepoJson = NULL;
        }finally{
            if($monorepoJson === NULL){
                throw new \RuntimeException("Unable to parse given monorepo json");
            }

            return $monorepoJson;
        }
    }

    /**
     * @return SchemaValidator
     */
    public function getValidator()
    {
        return $this->validator;
    }

}