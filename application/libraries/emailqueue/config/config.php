<?
	define("FRONTEND_USER", "user"); // The user name for the frontend
	define("FRONTEND_PASSWORD", "password"); // The password for the frontend

	define("QUEUED_MESSAGES", 40); // Number of last queued messages to show in frontend
	define("LATEST_DELIVERED_MESSAGES", 10); // Number of last delivered messages to show in frontend
	define("LATEST_CANCELLED_MESSAGES", 10); // Number of last cancelled messages to show in frontend
	define("MAXIMUM_DELIVERY_TIMEOUT", 30); // Maximum seconds Emailque must use to send queued emails
	define("DELIVERY_INTERVAL", 10); // Hundreds of a second between each email send
	define("MAX_DELIVERS_A_TIME", 1000); // Number of maximum messages to deliver every time delivery script is called
	define("SENDING_RETRY_MAX_ATTEMPTS", 3); // Maximum number of attemps to send a message
	define("PURGE_OLDER_THAN_DAYS", 30); // Purge messages older than this days from the database
	
	define("SMTP_SERVER", "127.0.0.1"); // The IP of the SMTP server
	
	define("SMTP_IS_AUTHENTICATION", false); // True to use SMTP server Authentication
	define("SMTP_AUTHENTICATION_USERNAME", "");
	define("SMTP_AUTHENTICATION_PASSWORD", "");
	
	define("PHPMAILER_LANGUAGE", "en");
	
	define("DEFAULT_TIMEZONE", "UTC");

	define("LOGS_DIR", "logs"); // The directory to store logs
	define("LOGS_FILENAME_DATEFORMAT", "Y-m-d"); // The file name format for log files, as a parameter for the PHP date() function
	define("LOGS_DATA_DATEFORMAT", "Y-m-d H:i:s"); // The format of the date as stored inside log files, as a parameter for the PHP date() function

    define("IS_DEVEL_ENVIRONMENT", false); // When set to true, only emails addressed to emails into $devel_emails array are sent

    $devel_emails = array(
        "me@email.com"
    );
?>