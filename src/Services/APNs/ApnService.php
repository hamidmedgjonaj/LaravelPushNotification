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
			// Build the binary notification to each token
			foreach($tokens as $token)
			{
				//Open connection
				$this->connect();

				$innerData =
					chr(1)
					. pack('n', 32)
					. pack('H*', $token)

					. chr(2)
					. pack('n', strlen($apn_message))
					. $apn_message

					. chr(3)
					. pack('n', 4)
					. chr(1).chr(1).chr(1).chr(1)

					. chr(4)
					. pack('n', 4)
					. pack('N', time() + 86400)

					. chr(5)
					. pack('n', 1)
					. chr(10);

				$data =
					chr(2)
					. pack('N', strlen($innerData))
					. $innerData;

				$this->write($data);

				// Close connection
				$this->close();
			}
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
		$url = Config::get('pushnotification.aps.server');

		/*
		 * Socket content creation
		 */
		$ctx = stream_context_create([
			'ssl' => [
				'local_cert' => Config::get('pushnotification.aps.certificate'),
				'passphrase' => Config::get('pushnotification.aps.passPhrase')
			]
		]);

		/*
		 * Open connection with apns server
		 */
		$this->socket = @stream_socket_client($url, $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);

		stream_set_blocking($this->socket, 0);
		stream_set_write_buffer($this->socket, 0);

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

		return @fwrite($this->socket, $payload, strlen($payload));
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