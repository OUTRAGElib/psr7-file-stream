<?php
/**
 *	Value object representing a file uploaded through an HTTP request.
 *
 *	Instances of this interface are considered immutable; all methods that
 *	might change state MUST be implemented such that they retain the internal
 *	state of the current instance and return an instance that contains the
 *	changed state.
 */

namespace OUTRAGElib\FileStream;

use \Exception;
use \Psr\Http\Message\StreamInterface as PsrStreamInterface;
use \Psr\Http\Message\UploadedFileInterface as PsrUploadedFileInterface;


interface FileInterface extends PsrUploadedFileInterface
{
	/**
	 *	Set the stream which this uploaded file refers to.
	 */
	public function setStream(PsrStreamInterface $stream);
	
	
	/**
	 *	What is the error, as reported by PHP's upload functionality?
	 */
	public function setError($errno);
	
	
	/**
	 *	Set the filename of the upload in question
	 */
    public function setClientFilename($filename);
    
    
    /**
     *	Set the MIME type of the upload in question
     */
    public function setClientMediaType($media_type);
}