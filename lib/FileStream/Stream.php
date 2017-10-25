<?php


namespace OUTRAGElib\FileStream;

use \InvalidArgumentException;
use \LogicException;
use \RuntimeException;


class Stream implements StreamInterface
{
	/**
	 *	Store the file pointer to the stream in question
	 */
	protected $pointer = null;
	
	
	/**
	 *	What is the size of the stream?
	 */
	protected $size = null;
	
	
	/**
	 *	Is this stream readable?
	 */
	protected $is_readable = false;
	
	
	/**
	 *	Is this stream writable?
	 */
	protected $is_writable = false;
	
	
	/**
	 *	Is this stream seekable?
	 */
	protected $is_seekable = false;
	
	
	/**
	 *	Set the file pointer
	 */
	public function setFilePointer($pointer)
	{
		if(!is_resource($pointer))
			throw new InvalidArgumentException("Invalid type - not a file resource");
		
		$this->pointer = $pointer;
		
		# and now to set everything up...
		$metadata = stream_get_meta_data($this->pointer);
		
		# can this stream be read from?
		if(isset($metadata["mode"]))
		{
			$mode = rtrim($metadata["mode"], "bt");
			
			$super = (boolean) preg_match("/\+$/", $mode);
			$mode = rtrim($mode, "+");
			
			switch($mode)
			{
				case "r":
					$this->is_readable = true;
				break;
				
				case "w":
				case "a":
				case "x":
				case "c":
					$this->is_readable = $super;
				break;
				
				default:
					throw new LogicException("Invalid file access type: '".$metadata["mode"]."'");
				break;
			}
		}
		
		# can this stream be written to?
		if(isset($metadata["mode"]))
		{
			$mode = rtrim($metadata["mode"], "bt");
			
			$super = (boolean) preg_match("/\+$/", $mode);
			$mode = rtrim($mode, "+");
			
			switch($mode)
			{
				case "r":
					$this->is_writable = $super;
				break;
				
				case "w":
				case "a":
				case "x":
				case "c":
					$this->is_writable = true;
				break;
				
				default:
					throw new LogicException("Invalid file access type: '".$metadata["mode"]."'");
				break;
			}
		}
		
		# does this stream support seek functionality?
		if(isset($metadata["seekable"]))
			$this->is_seekable = (boolean) $metadata["seekable"];
		
		return null;
	}
	
	
	/**
	 *	Retrieve the file pointer
	 */
	public function getFilePointer()
	{
		return $this->pointer;
	}
	
	
	/**
	 *	Reads all data from the stream into a string, from the beginning to end.
	 *
	 *	This method MUST attempt to seek to the beginning of the stream before
	 *	reading data and read the stream until the end is reached.
	 *
	 *	Warning: This could attempt to load a large amount of data into memory.
	 *
	 *	This method MUST NOT raise an exception in order to conform with PHP's
	 *	string casting operations.
	 *
	 *	@see http://php.net/manual/en/language.oop5.magic.php#object.tostring
	 *	@return string
	 */
	public function __toString()
	{
		$output = "";
		
		if(is_resource($this->pointer))
		{
			# the specification does not mention what should happen when we're messing
			# about with pointers and the like - as such, the way I've chosen to deal
			# with this is it will grab the position and then once it has rewound it, it
			# will simply just place it back to where it was originally
			$position = ftell($this->pointer);
			
			if($this->is_seekable)
				rewind($this->pointer);
			
			$output = stream_get_contents($this->pointer);
			
			if($this->is_seekable)
				fseek($this->pointer, $position, SEEK_SET);
		}
		
		return $output;
	}
	
	
	/**
	 *	Closes the stream and any underlying resources.
	 *
	 *	@return void
	 */
	public function close()
	{
		if(is_resource($this->pointer))
			fclose($this->pointer);
		
		return null;
	}
	
	
	/**
	 *	Separates any underlying resources from the stream.
	 *
	 *	After the stream has been detached, the stream is in an unusable state.
	 *
	 *	@return resource|null Underlying PHP stream, if any
	 */
	public function detach()
	{
		$pointer = null;
		
		if(is_resource($this->pointer))
			$pointer = $this->pointer;
		
		# apparently... this will kill the stream! why?? who knows.
		# what a silly spec.
		$this->pointer = null;
		
		return $pointer;
	}
	
	
	/**
	 *	Get the size of the stream if known.
	 *
	 *	@return int|null Returns the size in bytes if known, or null if unknown.
	 */
	public function getSize()
	{
		return $this->size;
	}
	
	
	/**
	 *	Returns the current position of the file read/write pointer
	 *
	 *	@return int Position of the file pointer
	 *	@throws \RuntimeException on error.
	 */
	public function tell()
	{
		if(!is_resource($this->pointer))
			throw new RuntimeException("Unable to retrieve position of file pointer");
		
		return ftell($this->pointer);
	}
	
	
	/**
	 *	Returns true if the stream is at the end of the stream.
	 *
	 *	@return bool
	 */
	public function eof()
	{
		if(!is_resource($this->pointer))
			return false;
		
		return feof($this->pointer);
	}
	
	
	/**
	 *	Returns whether or not the stream is seekable.
	 *	
	 *	@return bool
	 */
	public function isSeekable()
	{
		return $this->is_seekable;
	}
	
	
	/**
	 *	Seek to a position in the stream.
	 *
	 *	@link http://www.php.net/manual/en/function.fseek.php
	 *	@param int $offset Stream offset
	 *	@param int $whence Specifies how the cursor position will be calculated
	 *             based on the seek offset. Valid values are identical to the built-in
	 *             PHP $whence values for `fseek()`:
	 * 			    - SEEK_SET: Set position equal to offset bytes
	 *				- SEEK_CUR: Set position to current location plus offset
	 *              - SEEK_END: Set position to end-of-stream plus offset.
	 *	@throws \RuntimeException on failure.
	 */
	public function seek($offset, $whence = SEEK_SET)
	{
		if($this->is_seekable)
		{
			if(fseek($this->pointer, $offset, $whence) !== -1)
				return true;
		}
		
		throw new RuntimeException("Unable to seek on stream");
	}
	
	
	/**
	 *	Seek to the beginning of the stream.
	 *
	 *	If the stream is not seekable, this method will raise an exception;
	 *	otherwise, it will perform a seek(0).
	 *
	 *	@see seek()
	 *	@link http://www.php.net/manual/en/function.fseek.php
	 *	@throws \RuntimeException on failure.
	 */
	public function rewind()
	{
		if($this->is_seekable)
		{
			if(rewind($this->pointer))
				return true;
		}
		
		throw new RuntimeException("Unable to seek on stream");
	}
	
	
	/**
	 *	Returns whether or not the stream is writable.
	 *	
	 *	@return bool
	 */
	public function isWritable()
	{
		return $this->is_writable;
	}
	
	
	/**
	 *	Write data to the stream.
	 *
	 *	@param string $string The string that is to be written.
	 *	@return int Returns the number of bytes written to the stream.
	 *	@throws \RuntimeException on failure.
	 */
	public function write($string)
	{
		if(!$this->is_writable)
			throw new RuntimeException("Unable to write to stream");
		
		return fwrite($this->pointer, $string);
	}
	
	
	/**
	 *	Returns whether or not the stream is readable.
	 *
	 *	@return bool
	 */
	public function isReadable()
	{
		return $this->is_readable;
	}
	
	
	/**
	 *	Read data from the stream.
	 *
	 *	@param int $length Read up to $length bytes from the object and return
	 *	    them. Fewer than $length bytes may be returned if underlying stream
	 *	    call returns fewer bytes.
	 *	@return string Returns the data read from the stream, or an empty string
	 *	    if no bytes are available.
	 *	@throws \RuntimeException if an error occurs.
	 */
	public function read($length)
	{
		if(!$this->is_readable)
			throw new RuntimeException("Unable to read from stream");
		
		return fread($this->pointer, $length);
	}
	
	
	/**
	 *	Returns the remaining contents in a string
	 *
	 *	@return string
	 *	@throws \RuntimeException if unable to read or an error occurs while
	 *  		reading.
	 */
	public function getContents()
	{
		if(!$this->is_readable)
			throw new RuntimeException("Unable to read from stream");
		
		return stream_get_contents($this->pointer);
	}
	
	
	/**
	 *	Get stream metadata as an associative array or retrieve a specific key.
	 *	
	 *	The keys returned are identical to the keys returned from PHP's
	 *	stream_get_meta_data() function.
	 *	
	 *	@link http://php.net/manual/en/function.stream-get-meta-data.php
	 *	@param string $key Specific metadata to retrieve.
	 *	@return array|mixed|null Returns an associative array if no key is
	 *	   provided. Returns a specific key value if a key is provided and the
	 *     value is found, or null if the key is not found.
	 */
	public function getMetadata($key = null)
	{
		$metadata = stream_get_meta_data($this->pointer);
		
		if($key === null)
			return $metadata;
		elseif(array_key_exists($key, $metadata))
			return $metadata[$key];
		
		return null;
	}
}