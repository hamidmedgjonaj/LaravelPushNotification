<?php

namespace MedDev\PushNotification\Services\FCM;

use Illuminate\Support\Facades\Config;
use MedDev\PushNotification\Services\ServiceInterface;
use MedDev\PushNotification\Contracts\Payload;

class FCMService implements ServiceInterface
{
	/**
	 * Name of platform
	 * 
	 * @var string
	 */
	protected $platform = ['android', 'web'];

	/**
	 * @param Payload $payload
	 * @param $tokens
	 * @return bool|mixed
	 * @throws \Exception
     */
	public function send(Payload $payload, $tokens)
	{
		if(!is_array($tokens)){
			throw new \InvalidArgumentException('Tokens must be an array');
		}

    	if(!count($tokens)>0){
    		return true;
    	}

		$gcm_message = [
				"to" 			=> 	$tokens[0],
				"priority" 		=> 	"high",
				"time_to_live"	=>	86400,
				$payload->getFcmType() => $payload->getFcmFormat(),
		];

		$headers = [
				"Authorization: key=".Config::get('pushnotification.fcm.apiKey'),
				"Content-Type: application/json"
		];

		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL, Config::get('pushnotification.fcm.server'));
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($gcm_message));
		$result=curl_exec($ch);
		
		if($result === false){
			throw new \Exception("Curl failed: ".curl_error($ch));
		}
		
		curl_close($ch);
		
		return $result;
	}
	/**
	 * Accessor for platform name
	 * 
	 * @return string
	 */
	public function getPlatform() 
	{
		return $this->platform;
	}
}
?>