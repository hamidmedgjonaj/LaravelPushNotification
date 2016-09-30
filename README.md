#Install
Add follow line into "require" section in your composer.json:

```json
"meddev/laravel-push-notification": "1.0.0"
```

Update composer with command:

```json
"composer update"
```

###Provider and Facade
Like all providers, put this follow lines in your config/app.php

```php
'providers' => [
	...
	MedDev\PushNotification\PushNotificationProvider::class,
],
```

```php
'aliases' => [
	...
	'PushNotificationFacade' => MedDev\PushNotification\Facades\PushNotificationFacade::class,
],
```

###Configuration
Finally you need to generate a configuration file for this package.
Run follow composer command:

```php
php artisan vendor:publish --provider="MedDev\PushNotification\PushNotificationProvider"
```

This command will generate `pushnotification.php` file in your config directory.
```php
return [

    "aps" => [
        	/*
        	 * A valid PEM certificate generated from Apple Push Service certificate
        	 */
            "certificate" 	=> storage_path('app')."/aps.pem",

        	/*
        	 * Password used to generate a certificate
        	 */
            "passPhrase"  	=> "",

            /*
        	 * Server used to send push notifications
        	 */
            "server"  	=> "https://api.development.push.apple.com",

            /*
        	 * Set to TRUE if HTTP/2 Is Enabled for Your SSL application
        	 */
            "useApi"    => false
        ],

        "fcm" => [
        	/*
        	 * Google FCM api server key
        	 * You can retrieve your key in Firebase Console
        	 */
            "apiKey"      	=> "",

            /*
        	 * Server used to send push notifications
        	 */
            "server"  	=> "https://fcm.googleapis.com/fcm/send"
        ]

];
```
Remember to add your FCM api key and PEM certificate path.

#Tokens
You should have a model to store devices information into your database. To fit your model to be used directly from PushNotification Package you simply need to add `use TokenTrait`:

```php
use MedDev\PushNotification\TokenTrait;

class YourDevicesTable extends Model
{
    use TokenTrait;
}
```

To works automatically with PushNotification Package your table needs two columns, one that contains platform name, and the other one that contains device token.

By default this two columns names are considered `"platform"` and `"device_token"`

```
+--------------+
| Field        |
+--------------+
| id           |
| platform     | enum (android, ios, web)
| device_token |
| created_at   |
| updated_at   |
+--------------+
```

You can design your table as you want, only this two fields are mandatory to work with PushNotification Package. 

#Payload
You just create a class that implements `MedDev\PushNotification\Contracts\Payload` and overwrite `apsPayload` and `fcmPayload` properties with your payload content.

```php
namespace App\Payload;

use App\User;
use MedDev\PushNotification\Contracts\Payload;

class MessagePayload extends Payload
{
	/**
	 * Generate Notification Payload
	 *
	 * @param User $user
	 * @return void
	 */
	public function __construct(User user)
	{
		//IOS payload format	
		$this->apsPayload = [
				"alert" => [
					"title" => "Someone has sent you a message",
                    "body" 	=> "This is the notification body text",
				],
		];
		
		//Android payload format
		$this->fcmPayload = [
				"title" => "Someone has sent you a message",
				"body" 	=> "This is the notification body text",
		];
	}
}
```
You can proceed to create your payload collection for every event or message that you want send to your users.

#Send
Now you are able to get a list of devices tokens from your DB and you have a payload for your specific event.
To send a payload to a list of devices you can use `send` method inherited from Payload class.

###Regular send
```php
//Create payload
$payload = new MessagePayload(User::findOrFail(1));

//Retrieve devices list with your own criteria
$tokens = YourDevicesTable::all();

//send directly
$payload->send($tokens);
```

###Send by Queue 
Package use Queue to send notification natively to execute your send task in background. You need to set your Queue provider in config/queue.php and your payload will be sent on queue.

With second param of "send" method you can schedule job in a specific queue.

```php
//push in queue
$payload->send($payload, $tokens, "your-queue-name");
```


