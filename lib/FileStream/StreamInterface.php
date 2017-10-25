<?php
/**
 *	Describes a data stream.
 *	
 *	Typically, an instance will wrap a PHP stream; this interface provides
 *	a wrapper around the most common operations, including serialization of
 *	the entire stream to a string.
 */

namespace OUTRAGElib\FileStream;

use \Exception;
use \Psr\Http\Message\StreamInterface as PsrStreamInterface;


interface StreamInterface extends PsrStreamInterface
{
	/**
	 *	Set the file pointer
	 */
	public function setFilePointer($pointer);
	
	
	/**
	 *	Retrieve the file pointer
	 */
	public function getFilePointer();
}