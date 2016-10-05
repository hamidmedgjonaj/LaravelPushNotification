<?php

namespace MedDev\PushNotification;

use MedDev\PushNotification\Contracts\Payload;
use MedDev\PushNotification\Services\ServiceInterface;

class PushNotification
{
	/**
	 * List of driver available
	 *
	 * @var array
	 */
	private $services = [
		Services\FCM\FCMService::class,
		Services\APNs\ApnService::class,
	];

	/**
	 * Instance of queue connection
	 *
	 * @var \Illuminate\Contracts\Queue\Queue
	 */
	protected $queue;

	/**
	 * Instance of laravel App
	 *
	 * @var App
	 */
	protected $app;

	/**
	 * Create a new Mailer instance.
	 *
	 * @param $app
	 */
	public function __construct($app)
	{
		$this->app = $app;
		$this->setBridgeDependencies();
	}

	/**
	 * Queue a new push notification for sending.
	 *
	 * @param $payload
	 * @param array $tokens
	 * @param string $queue
	 */
	public function queue(Payload $payload, $tokens, $queue = null)
	{
		//Serialize data
		$payload = serialize($payload);
		$tokens = serialize($tokens);

		//Push in queue
		return $this->queue->push('notification@handleQueuedSending', compact('payload', 'tokens'), $queue);
	}

	/**
	 * Handle a queued push notification message job.
	 *
	 * @param \Illuminate\Contracts\Queue\Job  $job
	 * @param array<DeveloperDynamo\PushNotification\Token> $tokens
	 * @return void
	 */
	public function handleQueuedSending($job, $data)
	{
		//Unserialize data
		$payload = unserialize($data['payload']);
		$tokens = unserialize($data['tokens']);

		//Execute task
		$this->send($payload, $tokens);

		//Delete job from the queue
		$job->delete();
	}

	/**
	 * Send notification to devices tokens
	 *
	 * @throws \Exception
	 * @param $payload
	 * @param array<Model> $tokens
	 */
	public function send(Payload $payload, $tokens)
	{
		//Retrieve drivers and cast to DriverInterface
		foreach($this->services as $service){
			//Create service instance
			$instance = new $service();

			//Check instance type to use our standard interface
			if(! $instance instanceof ServiceInterface){
				throw new \InvalidArgumentException("Service must be a ServiceInterface implementation");
			}

			//Retrieve tokens for specific driver's platform
			$platform_tokens = $this->dispatchDeviceToken($instance->getPlatform(), $tokens);

			//Send payload to tokens across driver's platform
			$instance->send($payload, $platform_tokens);
		}
	}

	/**
	 * @param $platform
	 * @param $tokens
	 * @return array
     */
	protected function dispatchDeviceToken($platform, $tokens)
	{
		$platform_tokens = [];

		foreach ($tokens as $tk) {

			//Check TokenTrait to use our standard interface
			if (!$this->hasDeviceTrait($tk)) {
				continue;
			}

			//Use trait method
			$tk = $tk->getTokenArray();

			//filtering tokens for platform
			if (is_string($platform)) {
				if ($tk['platform'] === $platform)
					$platform_tokens[] = $tk['device_token'];
			}

			if (is_array($platform)) {
				if (in_array($tk['platform'], $platform)) {
					$platform_tokens[] = $tk['device_token'];
				}
			}
		}

		return $platform_tokens;
	}

	/**
	 * Check if given class use TokenTrait
	 *
	 * @param string $tk
	 * @return boolean
	 */
	protected function hasDeviceTrait($tk)
	{
		foreach(class_uses($tk) as $t ) {
			if( $t === 'MedDev\PushNotification\DeviceTrait' ){
				return true;
			}
		}
		return false;
	}

	/**
	 * Set a few dependencies on the bridge instance.
	 *
	 * @return void
	 */
	protected function setBridgeDependencies()
	{
		//if you want to see if a class is bound to the container you can use BOUND method
		if ($this->app->bound('queue')) {
			$this->queue = $this->app['queue.connection'];
		}
	}
}