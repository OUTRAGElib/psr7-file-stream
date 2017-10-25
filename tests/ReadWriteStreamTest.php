<?php


namespace OUTRAGElib\FileStream\Tests;

require __DIR__."/../vendor/autoload.php";

use \OUTRAGElib\FileStream\Stream;
use \OUTRAGElib\FileStream\StreamInterface;
use \PHPUnit\Framework\TestCase;
use \Psr\Http\Message\StreamInterface as PsrStreamInterface;


class ReadWriteStreamTest extends TestCase
{
	/**
	 *	The new way of doing things on here is to make sure everything
	 *	uses PSR 7's uploaded file interface
	 */
	public function testStreamConstruction()
	{
		$fp = fopen("php://temp", "w+");
		
		$stream = new Stream();
		$stream->setFilePointer($fp);
		
		$this->assertInstanceOf(Stream::class, $stream);
		$this->assertInstanceOf(StreamInterface::class, $stream);
		$this->assertInstanceOf(PsrStreamInterface::class, $stream);
		
		return $stream;
	}
	
	
	/**
	 *	Write some content to the stream
	 *
	 *	@depends testStreamConstruction
	 */
	public function testStreamWrite(StreamInterface $stream)
	{
		$this->assertTrue($stream->isWritable());
		
		$input = "test 123";
		$bytes = $stream->write($input);
		
		$this->assertEquals(strlen($input), $bytes);
	}
	
	
	/**
	 *	Read from the test stream
	 *
	 *	@depends testStreamConstruction
	 */
	public function testStreamRead(StreamInterface $stream)
	{
		$this->assertTrue($stream->isReadable());
		
		$input = "test 123";
		$output = (string) $stream;
		
		$this->assertEquals($input, $output);
	}
}