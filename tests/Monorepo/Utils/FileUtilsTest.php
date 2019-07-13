<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 13/07/19
 * Time: 23.18
 */

namespace Monorepo\Utils;

class FileUtilsTest extends \PHPUnit_Framework_TestCase
{

    private $tmpDir;

    protected function setUp()
    {
        $this->tmpDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'tmp';
        if(file_exists($this->tmpDir)){
            rmdir($this->tmpDir);
        }

        mkdir($this->tmpDir);
    }

    protected function tearDown()
    {
        rmdir($this->tmpDir);
    }

    public function testJoinPaths()
    {
        $exp1 = 'a/b';
        $exp2 = 'a/b/c';
        $exp3 = 'a/b/c/d';

        $this->assertEquals($exp1,FileUtils::join_paths('a','b'));
        $this->assertEquals($exp1,FileUtils::join_paths('a/','b'));
        $this->assertEquals($exp1,FileUtils::join_paths('a','/b'));
        $this->assertEquals($exp1,FileUtils::join_paths('a/','/b'));

        $this->assertEquals($exp2,FileUtils::join_paths('a','b','c'));
        $this->assertEquals($exp2,FileUtils::join_paths('a','b','/c'));
        $this->assertEquals($exp2,FileUtils::join_paths('a/b','c'));
        $this->assertEquals($exp2,FileUtils::join_paths('a/b/','c'));
        $this->assertEquals($exp2,FileUtils::join_paths('a/b/','/c'));

        $this->assertEquals($exp3,FileUtils::join_paths('a','b','c','d'));
        $this->assertEquals($exp3,FileUtils::join_paths('a/b/c','d'));
        $this->assertEquals($exp3,FileUtils::join_paths('a/b/c/','d'));
        $this->assertEquals($exp3,FileUtils::join_paths('a/b/c','/d'));
        $this->assertEquals($exp3,FileUtils::join_paths('a/b/c/','/d'));
    }

    public function testCreateDirectory()
    {
        $dir = FileUtils::join_paths($this->tmpDir, 'foo');
        $this->assertTrue(FileUtils::create_dir($dir));
        $this->assertFalse(FileUtils::create_dir($dir)); // dir allready exists
        rmdir($dir);
    }

    public function testReadFile()
    {
        $content = FileUtils::read_file($this->tmpDir,'..','_fixtures','.gitignore');
        $this->assertContains('vendor/composer',$content);
        $this->assertNull(FileUtils::read_file($this->tmpDir,'..','_fixtures','NOT_EXISTING_FILE'));
    }

    public function testIsWritable()
    {
        $this->assertTrue(FileUtils::is_writable($this->tmpDir));
        $this->assertFalse(FileUtils::is_writable($this->tmpDir,'NOT_EXISTING_DIRECTORY'));
    }

    public function testFileExists()
    {
        $this->assertTrue(FileUtils::file_exists($this->tmpDir,'..','_fixtures','.gitignore'));
        $this->assertFalse(FileUtils::file_exists($this->tmpDir,'..','_fixtures','NOT_EXISTING_FILE'));
    }

    public function testRemoveFile()
    {
        $file = FileUtils::join_paths($this->tmpDir,'TEST_FILE');
        file_put_contents($file,'hello world');

        $this->assertTrue(file_exists($file));
        $this->assertTrue(FileUtils::remove_file($file));
        $this->assertFalse(FileUtils::remove_file($file));
        $this->assertFalse(file_exists($file));
    }

    public function testRemoveEmptyDirectory()
    {
        $dir = FileUtils::join_paths($this->tmpDir,'TEST_DIR');

        $this->assertTrue(FileUtils::create_dir($dir));
        $this->assertTrue(FileUtils::file_exists($dir));
        $this->assertTrue(FileUtils::remove_directory($dir));
        $this->assertFalse(FileUtils::file_exists($dir));
    }

    public function testRemoveFullDirectory()
    {
        $dir = FileUtils::join_paths($this->tmpDir,'TEST_DIR');
        $dir2 = FileUtils::join_paths($dir, 'SUBTEST_DIR');
        $dir3 = FileUtils::join_paths($dir, 'SUBTEST2_DIR');

        $file1 = FileUtils::join_paths($dir,'test_file');
        $file2 = FileUtils::join_paths($dir2,'test_file');

        $this->assertTrue(FileUtils::create_dir($dir));
        $this->assertTrue(FileUtils::create_dir($dir2));
        $this->assertTrue(FileUtils::create_dir($dir3));

        file_put_contents($file1,'foo');
        $this->assertTrue(file_exists($file1));
        file_put_contents($file2,'bar');
        $this->assertTrue(file_exists($file2));

        $this->assertTrue(FileUtils::remove_directory($dir));
        $this->assertFalse(FileUtils::file_exists($dir));
    }
    
    public function testWrite()
    {
        $file = FileUtils::join_paths($this->tmpDir,'test_file');
        
        $this->assertFalse(FileUtils::file_exists($file));
        $this->assertTrue(FileUtils::write($file,'foo'));

        $content1 = FileUtils::read_file($file);
        $this->assertEquals('foo', $content1);

        $this->assertTrue(FileUtils::write($file,'zzz',true));

        $content2 = FileUtils::read_file($file);
        $this->assertEquals('foozzz', $content2);

        unlink($file);
    }

    public function testWriteFile()
    {
        $file = FileUtils::join_paths($this->tmpDir,'test_file');

        $this->assertFalse(FileUtils::file_exists($file));
        $this->assertTrue(FileUtils::write_file($this->tmpDir,'test_file','foo'));

        $content1 = FileUtils::read_file($file);
        $this->assertEquals('foo', $content1);

        $this->assertTrue(FileUtils::write_file($this->tmpDir, 'test_file','zzz',true));

        $content2 = FileUtils::read_file($file);
        $this->assertEquals('foozzz', $content2);

        unlink($file);
    }

    public function testRemoveFiles()
    {
        $file1 = FileUtils::join_paths($this->tmpDir,'test_file1.test');
        $file2 = FileUtils::join_paths($this->tmpDir,'test_file2.test');
        $file3 = FileUtils::join_paths($this->tmpDir,'test_file3.txt');
        $file4 = FileUtils::join_paths($this->tmpDir,'test_file4.txt');

        $this->assertTrue(FileUtils::write($file1,'1'));
        $this->assertTrue(FileUtils::write($file2,'2'));
        $this->assertTrue(FileUtils::write($file3,'3'));
        $this->assertTrue(FileUtils::write($file4,'4'));

        $this->assertTrue(FileUtils::remove_files([$file1,$file2]));
        $this->assertFalse(FileUtils::file_exists($file1));
        $this->assertFalse(FileUtils::file_exists($file2));

        $this->assertTrue(FileUtils::remove_files(glob(FileUtils::join_paths($this->tmpDir,'*.txt'))));
        $this->assertFalse(FileUtils::file_exists($file3));
        $this->assertFalse(FileUtils::file_exists($file4));
    }

}