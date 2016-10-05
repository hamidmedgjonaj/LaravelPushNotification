<?php

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
        "server"  	=> "ssl://gateway.push.apple.com:2195",
        //"server"  	=> "https://api.development.push.apple.com", // HTTP/2 protocol server

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