<?php


namespace OUTRAGElib\FileStream;

use \InvalidArgumentException;
use \Psr\Http\Message\StreamInterface as PsrStreamInterface;
use \RuntimeException;


class File implements FileInterface
{
	/**
	 *	Store a copy of the stream we're working with
	 */
	protected $stream = null;
	
	
	/**
	 *	What error/success flag does this file have?
	 */
	protected $error = UPLOAD_ERR_NO_FILE;
	
	
	/**
	 *	What is the filename, as reported by the client?
	 */
	protected $client_filename = null;
	
	
	/**
	 *	What is the MIME type, as reported by the client?
	 */
	protected $client_media_type = null;
	
	
	/**
	 *	Set the stream which this uploaded file refers to.
	 */
	public function setStream(PsrStreamInterface $stream)
	{
		$this->stream = $stream;
		$this->error = UPLOAD_ERR_OK;
		
		return true;
	}
	
	
	/**
	 *	Retrieve a stream representing the uploaded file.
	 *
	 *	This method MUST return a StreamInterface instance, representing the
	 *	uploaded file. The purpose of this method is to allow utilizing native PHP
	 *	stream functionality to manipulate the file upload, such as
	 *	stream_copy_to_stream() (though the result will need to be decorated in a
	 *	native PHP stream wrapper to work with such functions).
	 *
	 *	If the moveTo() method has been called previously, this method MUST raise
	 *	an exception.
	 *
	 *	@return StreamInterface Stream representation of the uploaded file.
	 *	@throws \RuntimeException in cases when no stream is available or can be
	 *	    created.
	 */
	public function getStream()
	{
		if(!$this->stream)
			throw new RuntimeException("No stream is available");
		
		return $this->stream;
	}
	
	
	/**
	 *	Move the uploaded file to a new location.
	 *
	 *	Use this method as an alternative to move_uploaded_file(). This method is
	 *	guaranteed to work in both SAPI and non-SAPI environments.
	 *	Implementations must determine which environment they are in, and use the
	 *	appropriate method (move_uploaded_file(), rename(), or a stream
	 *	operation) to perform the operation.
	 *
	 *	$target_path may be an absolute path, or a relative path. If it is a
	 *	relative path, resolution should be the same as used by PHP's rename()
	 *	function.
	 *
	 *	The original file or stream MUST be removed on completion.
	 *
	 *	If this method is called more than once, any subsequent calls MUST raise
	 *	an exception.
	 *
	 *	When used in an SAPI environment where $_FILES is populated, when writing
	 *	files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
	 *	used to ensure permissions and upload status are verified correctly.
	 *
	 *	If you wish to move to a stream, use getStream(), as SAPI operations
	 *	cannot guarantee writing to stream destinations.
	 *
	 *	@see http://php.net/is_uploaded_file
	 *	@see http://php.net/move_uploaded_file
	 *	@param string $target_path Path to which to move the uploaded file.
	 *	@throws \InvalidArgumentException if the $target_path specified is invalid.
	 *	@throws \RuntimeException on any error during the move operation, or on
	 *	    the second or subsequent call to the method.
	 */
	public function moveTo($target_path)
	{
		if(!$this->stream)
			throw new RuntimeException("No stream is available");
		
		# if the file hasn't been uploaded correctly, then we can't proceed with
		# this!
		# @todo: group this into errors based on the following error codes:
		# 	- UPLOAD_ERR_INI_SIZE
		# 	- UPLOAD_ERR_FORM_SIZE
		# 	- UPLOAD_ERR_PARTIAL
		# 	- UPLOAD_ERR_NO_FILE
		# 	- UPLOAD_ERR_NO_TMP_DIR
		# 	- UPLOAD_ERR_CANT_WRITE
		# 	- UPLOAD_ERR_EXTENSION
		if($this->error !== UPLOAD_ERR_OK)
			throw new RuntimeException("No file has been uploaded");
		
		# first thing we need to do is make sure that the path we're wanting to move
		# this file to actually exists
		if(!is_string($target_path) || !strlen($target_path))
			throw new InvalidArgumentException("Invalid path format supplied");
		
		$target_base_path = dirname($target_path);
		
		if(!is_dir($target_base_path))
			throw new InvalidArgumentException("The target directory '".$target_base_path.DIRECTORY_SEPARATOR."' does not exist");
		
		$uri = $this->stream->getMetadata("uri");
		
		if(is_uploaded_file($uri))
		{
			if(move_uploaded_file($uri, $target_path))
				return true;
			
			throw new RuntimeException("Unable to move uploaded file");
		}
		else
		{
			# this will be developed on later.
			# the issue that we have at the moment is that i'm wanting this to be
			# able to take streams, and move them into files if necessary.
			# things to think of:
			#	- HTTP streams
			#	- string streams (tmp?)
			#	- bog standard file pointers??
		}
		
		throw new RuntimeException("Unable to move uploaded file");
	}
	
	
	/**
	 *	Retrieve the file size.
	 *
	 *	Implementations SHOULD return the value stored in the "size" key of
	 *	the file in the $_FILES array if available, as PHP calculates this based
	 *	on the actual size transmitted.
	 *
	 *	@return int|null The file size in bytes or null if unknown.
	 */
	public function getSize()
	{
		if(!$this->stream)
			throw new RuntimeException("No stream is available");
		
		return $this->stream->getSize();
	}
	
	
	/**
	 *	What is the error, as reported by PHP's upload functionality?
	 */
	public function setError($error)
	{
		$this->error = $error;
		return true;
	}
	
	
	/**
	 *	Retrieve the error associated with the uploaded file.
	 *
	 *	The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
	 *
	 *	If the file was uploaded successfully, this method MUST return
	 *	UPLOAD_ERR_OK.
	 *
	 *	Implementations SHOULD return the value stored in the "error" key of
	 *	the file in the $_FILES array.
	 *
	 *	@see http://php.net/manual/en/features.file-upload.errors.php
	 *	@return int One of PHP's UPLOAD_ERR_XXX constants.
	 */
	public function getError()
	{
		return $this->error;
	}
	
	
	/**
	 *	Set the filename of the upload in question
	 */
	public function setClientFilename($filename)
	{
		$this->client_filename = $filename;
		return true;
	}
	
	
	/**
	 *	Retrieve the filename sent by the client.
	 *
	 *	Do not trust the value returned by this method. A client could send
	 *	a malicious filename with the intention to corrupt or hack your
	 *	application.
	 *
	 *	Implementations SHOULD return the value stored in the "name" key of
	 *	the file in the $_FILES array.
	 *
	 *	@return string|null The filename sent by the client or null if none
	 *	    was provided.
	 */
	public function getClientFilename()
	{
		return $this->client_filename;
	}
	
	
	/**
	 *	Set the MIME type of the upload in question
	 */
	public function setClientMediaType($media_type)
	{
		$this->client_media_type = $media_type;
		return true;
	}
	
	
	/**
	 *	Retrieve the media type sent by the client.
	 *
	 *	Do not trust the value returned by this method. A client could send
	 *	a malicious media type with the intention to corrupt or hack your
	 *	application.
	 *
	 *	Implementations SHOULD return the value stored in the "type" key of
	 *	the file in the $_FILES array.
	 *
	 *	@return string|null The media type sent by the client or null if none
	 *	    was provided.
	 */
	public function getClientMediaType()
	{
		return $this->client_media_type;
	}
}