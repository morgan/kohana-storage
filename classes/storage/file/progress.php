<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Storage File Progress
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011 Micheal Morgan
 * @license		MIT
 */
class Storage_File_Progress
{
	/**
	 * File size (in bytes)
	 * 
	 * @access	protected
	 * @var		int
	 */
	protected $_size = 0;
	
	/**
	 * Bytes transferred
	 * 
	 * @access	protected
	 * @var		int
	 */
	protected $_transferred = 0;
	
	/**
	 * Set or get size
	 * 
	 * @access	protected
	 * @param	mixed	NULL|int
	 * @return	mixed	int|$this
	 */
	public function size($size = NULL)
	{
		if ($size === NULL)
			return $this->_size;
			
		$this->_size = $size;
		
		return $this;
	}
	
	/**
	 * Set or get transferred
	 * 
	 * @access	protected
	 * @param	mixed	NULL|int
	 * @return	mixed	int|$this
	 */
	public function transferred($transferred = NULL)
	{
		if ($transferred === NULL)
			return $this->_transferred;
			
		$this->_transferred = $transferred;
		
		return $this;
	}	
}