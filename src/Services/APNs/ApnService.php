<?php

namespace MedDev\PushNotification\Services\APNs;

use Illuminate\Support\Facades\Config;
use MedDev\PushNotification\Services\ServiceInterface;
use MedDev\PushNotification\Contracts\Payload;

class ApnService implements ServiceInterface
{
	/**
	 * Name of platform
	 *
	 * @var string
	 */
	protected $platform = ['ios'];

	/**
	 * Is Connected
	 *
	 * @var boolean
	 */
	protected $isConnected = false;

	/**
	 * Stream Socket
	 *
	 * @var Resource
	 */
	protected $socket;


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

		$apn_message = json_encode($payload->getApsFormat());

		$headers = [
			"apns-expiration: 86400",
			"apns-priority: 10"
		];

		if(Config::get('pushnotification.aps.useApi')) {
			$result = "";
			foreach($tokens as $token)
			{
				$url = Config::get('pushnotification.aps.server') . "/3/device/".$token;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $apn_message);
				curl_setopt($ch, CURLOPT_SSLCERT, Config::get('pushnotification.aps.certificate'));
				curl_setopt($ch, CURLOPT_SSLCERTPASSWD, Config::get('pushnotification.aps.passPhrase'));
				curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

				$result = curl_exec($ch);
				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

				if ($result === false) {
					throw new \Exception("Curl failed: " . curl_error($ch));
				}
				$result .= $result;
				curl_close($ch);
			}
			return $result;
		}
		else
		{
			//Open connection
			$this->connect();

			// Build the binary notification to each token
			$data = '';
			foreach($tokens as $tk){
				$data .= chr(0) . pack('n', 32) . pack('H*', str_replace(' ', '', $tk)) . pack('n', strlen($apn_message)) . $apn_message;
			}

			// Send data to the server
			return $this->write($data);
		}

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

	protected function connect()
	{
		/*
		 * Socket content creation
		 */
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', Config::get('pushnotification.aps.certificate'));
		stream_context_set_option($ctx, 'ssl', 'passphrase', Config::get('pushnotification.aps.passPhrase'));

		/*
		 * Open connection with apns server
		 */
		$this->socket = stream_socket_client(
			Config::get('pushnotification.aps.server'),
			$err,
			$errstr,
			60,
			STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT,
			$ctx
		);

		if (!$this->socket) {
			throw new \Exception('Unable to connect with APN service: '.Config::get('pushnotification.aps.server').' - '.$err);
		}

		/*
         * Sign it how to connected
         */
		$this->isConnected = true;
	}

	/**
	 * Close Connection
	 */
	protected function close()
	{
		if ($this->isConnected && is_resource($this->socket)) {
			fclose($this->socket);
		}

		$this->isConnected = false;
	}

	protected function write($payload)
	{
		if (!$this->isConnected) {
			throw new \Exception('You must open the connection prior to writing data for APNS server');
		}

		return fwrite($this->socket, $payload, strlen($payload));
	}

	/**
	 * Destructor
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->close();
	}
}
?>