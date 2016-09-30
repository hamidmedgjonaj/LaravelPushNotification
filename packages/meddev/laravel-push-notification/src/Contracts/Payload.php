<?php

namespace MedDev\PushNotification\Contracts;

use MedDev\PushNotification\Facades\PushNotificationFacade;

abstract class Payload
{
	/**
	 * IOS payload structure
	 * 
	 * @var array
	 */
    protected $apsPayload = [];

    /**
     * Android payload structure
     *
     * @var array
     */
    protected $fcmPayload = [];

	/**
	 * Android notification type
	 *
	 * @var string
	 */
	protected $fcmPayloadType = 'notification';

	/**
	 * Basic mandatory attributes for ios
	 *
	 * @var array
	 */
	private $apsMandatoryFields = ['title', 'body'];

	/**
	 * Basic mandatory attributes for android
	 *
	 * @var array
	 */
	private $fcmMandatoryFields = ['title', 'body'];

	/**
	 * Generate payload for ios plaform
	 * 
	 * @return array
	 */
	final public function getApsFormat()
	{
		$this->checkApsMandatoryFields();
		
		return ["aps" => $this->rawFilter($this->apsPayload)];
	}

	final public function getFcmType()
	{
		return $this->fcmPayloadType;
	}

	/**
	 * Generate payload for android plaform
	 * 
	 * @return array
	 */
	final public function getFcmFormat()
	{
		$this->checkFcmMandatoryFields();
		return $this->rawFilter($this->fcmPayload);
	}
	
	/**
	 * Send Payload to devices list
	 * 
	 * @param Collection $tokens
	 * @param string $queue
	 * @return void
	 */
	protected function send($tokens, $queue = null)
	{
		PushNotificationFacade::queue($this, $tokens, $queue);
	}
	
	/**
	 * Check if exists mandatory field to compose essential notification payload
	 * 
	 * @throws \Exception
	 * @return boolean
	 */
	public function checkApsMandatoryFields()
	{
		foreach ($this->apsMandatoryFields as $field){
			if(! array_key_exists($field, $this->apsPayload) )
				return false;
		}
		
		return true;
	}
	
	/**
	 * Check if exists mandatory field to compose essential notification payload
	 * 
	 * @throws \Exception
	 * @return boolean
	 */
	public function checkFcmMandatoryFields()
	{
		foreach ($this->fcmMandatoryFields as $field){
			if(!array_key_exists($field, $this->fcmPayload) )
				return false;
		}
		
		return true;
	}
	
	/**
	 * Recursive methods to strip html tags from payload attributes
	 * 
	 * @param array $arr
	 * @return array
	 */
	public function rawFilter($arr)
	{
		foreach ($arr as $key => $value){
			if(is_array($value))
				$arr[$key] = $this->rawFilter($value);
			else
				$arr[$key] = strip_tags($value);
		}
		
		return $arr;
	}
}