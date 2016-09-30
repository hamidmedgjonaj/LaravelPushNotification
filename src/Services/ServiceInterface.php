<?php

namespace MedDev\PushNotification\Services;

use MedDev\PushNotification\Contracts\Payload;

interface ServiceInterface
{	
	public function getPlatform();
	public function send(Payload $payload, $tokens);
}