# laravel-push-notification
For introduction see this article: [Developer Dynamo PushNotification package](http://developerdynamo.it/2016/05/01/super-powerfull-laravel-pushnotification-package/)

There are many PHP library that help you to send push notifications but in my opinion nobody help you to easily make and manage a complete push notification service.

With this package you can create your Payload collection for any relevant event that could be happen for your users, filter devices list in your DB using Eloquent and send from one to millions messages with a single line of code.

###Platform actually supported (Desktop and Mobile)
- iOS (Only mobile App)
- Android
- Chrome, Firefox

#Install
Add follow line into "require" section in your composer.json:

```json
"developerdynamo/laravel-push-notification": "0.*"
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
	DeveloperDynamo\PushNotification\PushNotificationProvider::class,
],
```

```php
'aliases' => [
	...
	'NotificationBridge' => DeveloperDynamo\PushNotification\Facades\PushNotificationBridge::class,
],
```

###Configuration
Finally you need to generate a configuration file for this package.
Run follow composer command:

```php
php artisan vendor:publish --provider="DeveloperDynamo\PushNotification\PushNotificationProvider"
```

This command will generate `pushnotification.php` file in your config directory.
```php
return [

    "ios" => [
    	/*
    	 * A valid PEM certificate generated from Apple Push Service certificate
    	 */
        "certificate" 	=> storage_path('app')."/aps.pem",
    		
    	/*
    	 * Password used to generate a certificate
    	 */
        "passPhrase"  	=> ""
    ],
	
    "android" => [
    	/*
    	 * Google GCM api key
    	 * You can retrieve your key in Google Developer Console
    	 */
        "apiKey"      	=> "",
    ]

];
```
Remember to add your GCM api key and PEM certificate path.

#Tokens
You should have a model to store devices informations into your database. To fit your model to be used directly from PushNotification Package you simply need to add `use TokenTrait`:

```php
use DeveloperDynamo\PushNotification\TokenTrait;

class YourDeviceTokenTable extends Model
{
    use TokenTrait;
}
```

To works automatically with PushNotification Package your table needs two columns, one that contains platform name, and the other one that contains device token.

By default this two columns names are cosidered `"platform"` and `"device_token"`

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

If your table on DB are using differents names you can customize them according with your table's structure. 
For example, in your table you have platform column named "os" instead of "platform", and column to store device token is named "token" instead of "device_token":

```
+--------------+
| Field        |
+--------------+
| id           |
| os           | (instead of "platform")
| token        | (instead of "device_token")
| created_at   |
| updated_at   |
+--------------+
```

You can overwrite standard name used from package using `$columnName` property:

```php
class YourDeviceTokenTable extends Model
{
    use TokenTrait;
    
    /**
	 * Column name into DB table to store device informations
	 * 
	 * @var array
	 */
	protected $columnName = [
			"platform" => "os",
			"device_token" => "token",
	];
}
```
You can design your table as you want, only this two fields are mandatory to work with PushNotification Package. 

#Payload
You just create a class that implements `DeveloperDynamo\PushNotification\Contracts\Payload` and overwrite `apsPayload` and `gcmPayload` properties with your payload content.

```php
namespace App\Payload;

use App\User;
use DeveloperDynamo\PushNotification\Contracts\Payload;

class AddPhotoPayload extends Payload
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
					"title" => $user->first_name." posted a photo",
					"body" 	=> $user->first_name." added a new photo in her gallery",
				],
		];
		
		//Android payload format
		$this->gcmPayload = [
				"title" 	=> $user->first_name." posted a photo",
				"message" 	=> $user->first_name." added a new photo in her gallery",
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
$payload = new AddPhotoPayload(User::findOrFail(1));

//Retrieve devices list with your own criteria
$tokens = YourDeviceTokenTable::all();

//send directly
$payload->send($tokens);
```

###Send by Queue 
Package use Queue to send notification natively to execute your send task in backgroud. You need to set your Queue provider in config/queue.php and your payload will be sent on queue.

With second param of "send" method you can schedule job in a specific queue.

```php
//push in queue
$payload->send($payload, $tokens, "your-queue-name");
```


#Desktop notification
Thanks to GCM serivce you can send notifications to a Desktop browsers, Chrome and Firefox are supported, because both browsers implements a GCM client.


