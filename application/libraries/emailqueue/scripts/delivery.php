<?php

class delivery {

    private $_link;
    private $_memcache;

    public function __construct() {

        $fp = fopen("/etc/smcryp/.smsk", "r");
        fscanf($fp, "%s", $this->securekey);
        fclose($fp);
        $this->iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $this->securekey = mhash(MHASH_MD5, $this->securekey);

        /* ======== Memcached Server Connection ========= */
        $this->_memcache = new Memcached();
        $this->_memcache->addServer('localhost', 11211) or die("Could not connect");
        /* ======== Memcached Server Connection ========= */

        /* ======== Database Connection ========= */
        $this->_link = @mysqli_connect("127.0.0.1", "smh_ppv", "*A095E628F563DE69BDE25AB08F6625B3B63654EF", "smh_ppv", 3307) or die('Unable to establish a DB connection');
        /* ======== Database Connection ========= */
    }

    public function deliverEmail() {
        define("LIB_DIR", "lib");
        define("APP_DIR", "/var/www/vhosts/ppv/application/libraries/emailqueue/");

        include APP_DIR . "common.inc.php";
        include APP_DIR . "classes/phpmailer/PHPMailerAutoload.php";

        echo "Emailqueue · Delivery\n";
        if (IS_DEVEL_ENVIRONMENT) {
            echo "Reminder: Running in development environment, ";
            if (!$devel_emails)
                echo "no emails will be sent.\n";
            else
                echo "only emails to " . implode(", ", $devel_emails) . " will be sent.\n";
        }
        echo "Maximum delivery timeout: " . (MAXIMUM_DELIVERY_TIMEOUT ? $utils->secondstohumantime(MAXIMUM_DELIVERY_TIMEOUT) : "unlimited") . "\n";
        echo "Delivery interval: " . (DELIVERY_INTERVAL ? number_format((DELIVERY_INTERVAL / 100), 2, ",", "") . " seconds" : "none") . "\n";
        echo "Maximum emails to deliver: " . (MAX_DELIVERS_A_TIME ? MAX_DELIVERS_A_TIME : "no limit") . "\n";
        echo "Process started on: " . date("j/n/Y H:i.s") . "\n";

        $blacklisted_emails = array();
        // Get blacklisted emails
        $db->query("select * from blacklist");
        if ($db->isanyresult()) {
            while ($row = $db->fetchrow())
                $blacklisted_emails[] = $row["email"];
            $db->free();
        }

        // Query emails to be sent
        $db->query
                ("
		select  *
		from    emails
		where   is_sent = 0
		and     is_cancelled = 0
		and	is_blocked = 0
		and
		(
                    date_queued is null
                    or
                    (date_queued is not null and date_queued <= '" . date("Y-n-j H:i:s", $now) . "')
		)
		order by    is_inmediate desc, priority asc, date_queued asc
		" . (MAX_DELIVERS_A_TIME ? " LIMIT 0, " . MAX_DELIVERS_A_TIME : "") . "
	");

        if (!$db->isanyresult()) {
            echo "No emails on queue.\n";
        } else {
            while ($row = $db->fetchrow())
                $emails[] = $row;

            $timecontrol_start = mktime();
            foreach ($emails as $email) {
                $email_config = '';
                $TTL = 60; //Seconds
                $query = "SELECT * FROM `email_config` WHERE from_email = '" . $email["from"] . "'";
                $setKey = md5("config_" . $query);
                $getCache = $this->_memcache->get($setKey);

                if ($getCache) {
                    $email_config = $getCache;
                    echo "data comes from cache\n";
                    //print_r($email_config);
                } else {
                    $result = mysqli_query($this->_link, $query) or die('Query failed: ' . mysqli_error());
                    $email_config = mysqli_fetch_assoc($result);
                    $this->_memcache->set($setKey, $email_config, $TTL);
                    echo "data comes from database\n";
                    //print_r($email_config);
                }

                echo $email["id"] . ": Sending email to " . $email["to"] . " ... ";

                setsendingnow($email["id"]);

                if ($email["is_sendingnow"]) {
                    echo "already being sent.";
                    add_incidence($email["id"], "Try to send an email that is already being sent");
                    $logger->add_log_incidence
                            (
                            array
                                (
                                $email["id"],
                                $email["to"],
                                "Email skipped",
                                "Try to send an email that is already being sent"
                            )
                    );
                }

                if (!checkemail($email["to"])) {
                    echo "bad recipient email address.";
                    add_incidence($email["id"], "Incorrect recipient email address: " . $email["to"]);
                    cancel($email["id"]);
                    $logger->add_log_incidence
                            (
                            array
                                (
                                $email["id"],
                                $email["to"],
                                "Email cancelled",
                                "Incorrect recipient email address"
                            )
                    );
                }

                if (!checkemail($email["from"])) {
                    echo "bad addressee email address.";
                    add_incidence($email["id"], "Incorrect addressee email address: " . $email["from"]);
                    cancel($email["id"]);
                    $logger->add_log_incidence
                            (
                            array
                                (
                                $email["id"],
                                $email["to"],
                                "Email cancelled",
                                "Incorrect addressee email address"
                            )
                    );
                }

                // Check black list
                if (is_array($blacklisted_emails) and in_array(strtolower(trim($email["to"])), $blacklisted_emails)) {
                    echo "recipient is on the black list.";
                    add_incidence($email["id"], "Recipient is on the black list: " . $email["to"]);
                    cancel($email["id"]);
                    $logger->add_log_incidence
                            (
                            array
                                (
                                $email["id"],
                                $email["to"],
                                "Email cancelled",
                                "Recipient is on the black list"
                            )
                    );
                }

                if ($email_config) {
                    if ($email_config['use_default']) {
                        if (!IS_DEVEL_ENVIRONMENT || (IS_DEVEL_ENVIRONMENT && in_array($email["to"], $devel_emails))) {

                            // PHPMailer send
                            // Create a phpmailer object
                            $mail = new PHPMailer(true); // WARNING! This object is created here again and again inside the loop in order to clear the recipient's email addresses. Otherwise, when doing $mail->AddAddress, the emails will be sent to hundreds of users accumulated in the loop. (Learnt the hard way on 28/11/2013, lots of users had received hundreds of unwanted messages exposing the email addresses of other users on the CC field)

                            try {
                                $mail->CharSet = "UTF-8";

                                $mail->IsSendmail();

                                if ($email["replyto"] != "") {
                                    if ($email["replyto_name"] != "")
                                        $mail->AddReplyTo($email["replyto"], $email["replyto_name"]);
                                    else
                                        $mail->AddReplyTo($email["replyto"]);
                                }
                                else {
                                    $mail->AddReplyTo($email["from"]);
                                }

                                $mail->From = $email["from"];
                                if ($email["from_name"] != "")
                                    $mail->FromName = $email["from_name"];

                                $to = $email["to"];

                                $mail->AddAddress($to);

                                $mail->Subject = $email["subject"];

                                $mail->WordWrap = 80;

                                $body = $email["content"];
                                $body = preg_replace('/\\\\/', '', $body);

                                if ($email["is_html"]) {
                                    $mail->IsHTML(true);
                                }

                                $mail->MsgHTML($body);

                                if ($email["content_nonhtml"] != "")
                                    $mail->AltBody = $email["content_nonhtml"];
                                /*
                                  else
                                  $mail->AltBody = "Para ver el mensaje, utiliza un programa de email compatible con HTML! / To view the message, please use an HTML compatible email viewer!";
                                 */

                                if ($email["list_unsubscribe_url"] != "")
                                    $mail->AddCustomHeader("List-Unsubscribe: <" . $email["list_unsubscribe_url"] . ">");

                                $email_send_result = $mail->Send();
                            } catch (phpmailerException $e) {
                                echo "PHPMailer error: " . $e->errorMessage() . ", ";
                                $email_send_result = false;
                            }
                        } else {
                            echo "Running in devel environment, the recipient email isn't on the allowed devel emails. ";
                            $email_send_result = true;
                        }
                    } else {
                        if (!IS_DEVEL_ENVIRONMENT || (IS_DEVEL_ENVIRONMENT && in_array($email["to"], $devel_emails))) {

                            // PHPMailer send
                            // Create a phpmailer object
                            $mail = new PHPMailer(true); // WARNING! This object is created here again and again inside the loop in order to clear the recipient's email addresses. Otherwise, when doing $mail->AddAddress, the emails will be sent to hundreds of users accumulated in the loop. (Learnt the hard way on 28/11/2013, lots of users had received hundreds of unwanted messages exposing the email addresses of other users on the CC field)

                            try {
                                $mail->CharSet = "UTF-8";

                                $mail->IsSMTP();
                                $mail->Port = $email_config['port'];
                                $mail->Host = $email_config['server'];
                                if ($email_config['auth'] == 1) {
                                    $mail->SMTPAuth = true;
                                    $mail->Username = $email_config['from_email'];
                                    $mail->Password = $this->decrypt($email_config['pass']);
                                } else {
                                    $mail->SMTPAuth = false;
                                }
                                if ($email_config['secure']) {
                                    $mail->SMTPSecure = ($email_config['secure'] == 1) ? 'tls' : 'ssl';
                                }
                                $mail->SMTPOptions = array(
                                    'ssl' => array(
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        'allow_self_signed' => true
                                    )
                                );

                                $mail->SMTPDebug = 2;
                                $mail->Debugoutput = 'variable';

                                if ($email["replyto"] != "") {
                                    if ($email["replyto_name"] != "")
                                        $mail->AddReplyTo($email["replyto"], $email["replyto_name"]);
                                    else
                                        $mail->AddReplyTo($email["replyto"]);
                                }
                                else {
                                    $mail->AddReplyTo($email["from"]);
                                }

                                $mail->From = $email["from"];
                                if ($email["from_name"] != "")
                                    $mail->FromName = $email["from_name"];

                                $to = $email["to"];

                                $mail->AddAddress($to);

                                $mail->Subject = $email["subject"];

                                $mail->WordWrap = 80;

                                $body = $email["content"];
                                $body = preg_replace('/\\\\/', '', $body);

                                if ($email["is_html"]) {
                                    $mail->IsHTML(true);
                                }

                                $mail->MsgHTML($body);

                                if ($email["content_nonhtml"] != "")
                                    $mail->AltBody = $email["content_nonhtml"];
                                /*
                                  else
                                  $mail->AltBody = "Para ver el mensaje, utiliza un programa de email compatible con HTML! / To view the message, please use an HTML compatible email viewer!";
                                 */

                                if ($email["list_unsubscribe_url"] != "")
                                    $mail->AddCustomHeader("List-Unsubscribe: <" . $email["list_unsubscribe_url"] . ">");

                                //print_r($mail);
                                $email_send_result = $mail->Send();
                                $logger->add_log_smtp($mail->smtp->smtp_errors);
                            } catch (phpmailerException $e) {
                                echo "PHPMailer error: " . $e->errorMessage() . ", ";
                                $email_send_result = false;
                            }
                        } else {
                            echo "Running in devel environment, the recipient email isn't on the allowed devel emails. ";
                            $email_send_result = true;
                        }
                    }
                } else {
                    if (!IS_DEVEL_ENVIRONMENT || (IS_DEVEL_ENVIRONMENT && in_array($email["to"], $devel_emails))) {

                        // PHPMailer send
                        // Create a phpmailer object
                        $mail = new PHPMailer(true); // WARNING! This object is created here again and again inside the loop in order to clear the recipient's email addresses. Otherwise, when doing $mail->AddAddress, the emails will be sent to hundreds of users accumulated in the loop. (Learnt the hard way on 28/11/2013, lots of users had received hundreds of unwanted messages exposing the email addresses of other users on the CC field)

                        try {
                            $mail->CharSet = "UTF-8";

                            $mail->IsSendmail();

                            if ($email["replyto"] != "") {
                                if ($email["replyto_name"] != "")
                                    $mail->AddReplyTo($email["replyto"], $email["replyto_name"]);
                                else
                                    $mail->AddReplyTo($email["replyto"]);
                            }
                            else {
                                $mail->AddReplyTo($email["from"]);
                            }

                            $mail->From = $email["from"];
                            if ($email["from_name"] != "")
                                $mail->FromName = $email["from_name"];

                            $to = $email["to"];

                            $mail->AddAddress($to);

                            $mail->Subject = $email["subject"];

                            $mail->WordWrap = 80;

                            $body = $email["content"];
                            $body = preg_replace('/\\\\/', '', $body);

                            if ($email["is_html"]) {
                                $mail->IsHTML(true);
                            }

                            $mail->MsgHTML($body);

                            if ($email["content_nonhtml"] != "")
                                $mail->AltBody = $email["content_nonhtml"];
                            /*
                              else
                              $mail->AltBody = "Para ver el mensaje, utiliza un programa de email compatible con HTML! / To view the message, please use an HTML compatible email viewer!";
                             */

                            if ($email["list_unsubscribe_url"] != "")
                                $mail->AddCustomHeader("List-Unsubscribe: <" . $email["list_unsubscribe_url"] . ">");

                            $email_send_result = $mail->Send();
                        } catch (phpmailerException $e) {
                            echo "PHPMailer error: " . $e->errorMessage() . ", ";
                            $email_send_result = false;
                        }
                    } else {
                        echo "Running in devel environment, the recipient email isn't on the allowed devel emails. ";
                        $email_send_result = true;
                    }
                }

                if (!$email_send_result) {
                    echo "Error while sending email: " . $mail->ErrorInfo . ", ";

                    if ($email["error_count"] == SENDING_RETRY_MAX_ATTEMPTS - 1) {
                        update_error_count($email["id"], $email["error_count"] + 1);
                        $incidence_text = "Error while sending email: [" . $mail->ErrorInfo . "] Cancelled: No more sending attempts allowed";
                        add_incidence($email["id"], $incidence_text);
                        cancel($email["id"]);
                        $logger->add_log_incidence
                                (
                                array
                                    (
                                    $email["id"],
                                    $email["to"],
                                    "Email cancelled",
                                    "No more sending attempts allowed"
                                )
                        );
                        echo "No more attempts allowed, cancelled";
                    } else {
                        update_error_count($email["id"], $email["error_count"] + 1);
                        $incidence_text = "Error while sending email: [" . $mail->ErrorInfo . "] Scheduled for one more try";
                        add_incidence($email["id"], $incidence_text);
                        $logger->add_log_incidence
                                (
                                array
                                    (
                                    $email["id"],
                                    $email["to"],
                                    "Email rescheduled",
                                    $incidence_text
                                )
                        );
                        echo "Scheduled for one more try";
                    }
                } else {
                    mark_as_sent($email["id"]);
                    update_send_count($email["id"], $email["send_count"] + 1);
                    update_sentdate($email["id"], $now);
                    $logger->add_log_delivery
                            (
                            array
                                (
                                $email["id"],
                                "Email delivered",
                                $email["from"],
                                $email["to"],
                                $email["subject"]
                            )
                    );
                    echo "Email delivered";

                    // Sleeping
                    usleep((DELIVERY_INTERVAL / 100));
                }

                echo "\n";

                unsetsendingnow($email["id"]);

                // Check if maximum delivery timeout have been reached
                if ((mktime() - $timecontrol_start) > MAXIMUM_DELIVERY_TIMEOUT) {
                    echo "Delivery proccess automatically stopped before it finished because of too many time spent on delivering. Time spent: " . (mktime() - $timecontrol_start) . " seconds. Maximum time allowed: " . MAXIMUM_DELIVERY_TIMEOUT . " seconds\n";
                    $logger->add_log_incidence
                            (
                            array
                                (
                                0,
                                "",
                                "Maximum delivery timeout reached",
                                "The delivery proccess have been automatically stopped before it finishes because of too many time spent on delivering. Time spent: " . (mktime() - $timecontrol_start) . " seconds. Maximum time allowed: " . MAXIMUM_DELIVERY_TIMEOUT . " seconds"
                            )
                    );
                    break;
                }
            }
        }
    }

    function decrypt($input) {
        $input = base64_decode($input);
        $iv = substr($input, 0, $this->iv_size);
        $cipher = substr($input, $this->iv_size);
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->securekey, $cipher, MCRYPT_MODE_CBC, $iv));
    }

}

$delivery = new delivery();
$delivery->deliverEmail();
?>