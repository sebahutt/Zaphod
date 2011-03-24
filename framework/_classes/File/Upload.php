<?php
/**
 * The Upload class provides a wrapper for all uploaded files
 */
class Upload {
	/**
	 * Name of the file input
	 * @var string
	 */
	protected $_name;
	/**
	 * File object of the uploaded file
	 * @var File|boolean
	 */
	protected $_file;
	
	/**
	 * Constructor
	 * @param string $name the name of the file input
	 */
	public function __construct($name)
	{
		$this->_name = $name;
	}
	
	/**
	 * Test if the file has been uploaded
	 * @return boolean true if something has been uploaded (may not be valid), else false
	 */
	public function isUploaded()
	{
		return isset($_FILES[$this->_name]);
	}
	
	/**
	 * Test if the file has been successfully uploaded
	 * @return boolean true if the file has successfully been uploaded, else false
	 */
	public function isSuccessfullyUploaded()
	{
		return isset($_FILES[$this->_name]) and ($_FILES[$this->_name]['error'] === \UPLOAD_ERR_OK and is_uploaded_file($_FILES[$this->_name]['tmp_name']));
	}
	
	/**
	 * Return the File object of the uploaded file (if successfull)
	 * @return File|boolean the File object, or false if error
	 */
	public function getFile()
	{
		// Caching
		if (!isset($this->_file))
		{
			// If error
			if (!$this->isSuccessfullyUploaded())
			{
				$this->_file = false;
			}
			else
			{
				$this->_file = FileServer::get('upload')->getFile($_FILES[$input]['tmp_name']);
			}
		}
		
		return $this->_file;
	}
	
	/**
	 * Get the error relative to the uploaded file
	 * @return string the error messsage
	 */
	public function getUploadedError()
	{
		// If valid
		if ($this->isUploaded())
		{
			switch ($_FILES[$this->_name]['error'])
			{
				case UPLOAD_ERR_OK:
					return 'The file uploaded with success';
					break;
				
				case UPLOAD_ERR_INI_SIZE:
					return 'The uploaded file exceeds the max file size for this server';
					break;
				
				case UPLOAD_ERR_FORM_SIZE:
					return 'The uploaded file exceeds the max file size for this form';
					break;
				
				case UPLOAD_ERR_PARTIAL:
					return 'The uploaded file was only partially uploaded';
					break;
				
				case UPLOAD_ERR_NO_FILE:
					return 'No file was uploaded';
					break;
				
				case UPLOAD_ERR_NO_TMP_DIR:
					return 'Missing a temporary folder';
					break;
				
				case UPLOAD_ERR_CANT_WRITE:
					return 'Failed to write file to disk';
					break;
				
				case UPLOAD_ERR_EXTENSION:
					return 'A PHP extension stopped the file upload';
					break;
				
				default:
					return 'Unknown upload error';
					break;
			}
		}
		else
		{
			return 'No file sent';
		}
	}
}