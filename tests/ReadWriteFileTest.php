<?php


namespace OUTRAGElib\FileStream\Tests;

require __DIR__."/../vendor/autoload.php";

use \OUTRAGElib\FileStream\File;
use \OUTRAGElib\FileStream\FileInterface;
use \OUTRAGElib\FileStream\Stream;
use \OUTRAGElib\FileStream\StreamInterface;
use \PHPUnit\Framework\TestCase;
use \Psr\Http\Message\StreamInterface as PsrStreamInterface;


class ReadWriteFileTest extends TestCase
{
	/**
	 *	The new way of doing things on here is to make sure everything
	 *	uses PSR 7's uploaded file interface
	 */
	public function testFileConstruction()
	{
		$fp = fopen("php://temp", "w+");
		
		$stream = new Stream();
		$stream->setFilePointer($fp);
		
		$file = new File();
		$file->setStream($stream);
		
		$this->assertEquals($stream, $file->getStream());
		
		return $file;
	}
	
	
	/**
	 *	Test setting PHP upload error
	 *
	 *	@depends testFileConstruction
	 */
	public function testSetFileError(FileInterface $file)
	{
		$input = UPLOAD_ERR_CANT_WRITE;
		
		$file->setError($input);
		
		$this->assertEquals($input, $file->getError());
	}
	
	
	/**
	 *	Test setting the name of the file we're 'uploading'
	 *
	 *	@depends testFileConstruction
	 */
	public function testSetFilename(FileInterface $file)
	{
		$input = "hello.jpg";
		
		$file->setClientFilename($input);
		
		$this->assertEquals($input, $file->getClientFilename());
	}
	
	
	/**
	 *	Test setting the MIME type of the file we're 'uploading'
	 *
	 *	@depends testFileConstruction
	 */
	public function testSetMediaType(FileInterface $file)
	{
		$input = "image/jpg";
		
		$file->setClientMediaType($input);
		
		$this->assertEquals($input, $file->getClientMediaType());
	}
}