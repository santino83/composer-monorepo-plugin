<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 1.41
 */

namespace Monorepo\Schema;



use Monorepo\Composer\Util\Filesystem;

class SchemaValidatorTest extends \PHPUnit_Framework_TestCase
{

    private $fixtureDir;

    /**
     * @var Filesystem
     */
    private $fs;

    protected function setUp()
    {
        $this->fs = new Filesystem();
        $this->fixtureDir = $this->fs->path(dirname(__DIR__),'..','_fixtures');
    }

    public function testValidateSimple()
    {
        $validator = new SchemaValidator();

        $c1 = file_get_contents($this->fs->path($this->fixtureDir,'example-simple','bar','monorepo.json'));
        $c2 = file_get_contents($this->fs->path($this->fixtureDir,'example-simple','foo','monorepo.json'));

        $this->assertTrue($validator->validate($c1));
        $this->assertTrue($validator->validate($c2));
    }

    public function testValidateNoDev()
    {
        $validator = new SchemaValidator();

        $c1 = file_get_contents($this->fs->path($this->fixtureDir,'example-nodev','bar','monorepo.json'));
        $c2 = file_get_contents($this->fs->path($this->fixtureDir,'example-nodev','foo','monorepo.json'));

        $this->assertTrue($validator->validate($c1));
        $this->assertTrue($validator->validate($c2));
    }

    public function testValidateFull()
    {
        $validator = new SchemaValidator();

        $c = file_get_contents($this->fs->path($this->fixtureDir,'resources','monorepo-test.json'));
        $this->assertTrue($validator->validate($c));
    }

    public function testValidateAnotherSchema()
    {
        $schema = json_decode(file_get_contents($this->fs->path($this->fixtureDir,'resources','another-schema.json')));
        $validator = new SchemaValidator($schema);

        $content = file_get_contents($this->fs->path($this->fixtureDir,'another-schema','another-schema.json'));
        $this->assertTrue($validator->validate($content));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testValidateInvalidSchema()
    {
        $validator = new SchemaValidator();

        $content = file_get_contents($this->fs->path($this->fixtureDir,'another-schema','another-schema.json'));
        $validator->validate($content);
    }

}