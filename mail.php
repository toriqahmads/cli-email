<?php
	ini_set('max_execution_time', 300);
	require_once('vendor/autoload.php');

	use SSilence\ImapClient\ImapClientException;
	use SSilence\ImapClient\ImapConnect;
	use SSilence\ImapClient\ImapClient as Imap;

	//$encryption = Imap::ENCRYPT_SSL;

	function execution($server, $username, $password)
	{
		echo "Trying to connect to server and login..." . PHP_EOL;
		try
		{
		    $imap = new Imap([
						    'flags' => [
						        'service' => ImapConnect::SERVICE_IMAP,
						        'encrypt' => ImapConnect::ENCRYPT_SSL,
						        'validateCertificates' => ImapConnect::NOVALIDATE_CERT,
						    ],
						    'mailbox' => [
						        'remote_system_name' => $server,
						    ],
						    'connect' => [
						        'username' => $username,
						        'password' => $password
						    ]
						]);
		}catch (ImapClientException $error)
		{
		    echo $error->getMessage().PHP_EOL;
		    die();
		}


		$folderoptions = array();
		$folders = $imap->getFolders();
		$x = 1;

		echo "Fetching all mailbox which available..." . PHP_EOL;
		foreach($folders as $folder => $value) 
		{
		    echo $x . "." . $folder . PHP_EOL;
		    $folderoptions[] = $folder;
		    $x++;
		}

		echo "Select folder (Just type the number) : ";
		$select = rtrim(fgets(STDIN))-1;
		echo "Folder " . $folderoptions[$select] . " selected." . PHP_EOL;
		echo "Fetching all email from this mailbox..." . PHP_EOL;

		$imap->selectFolder($folderoptions[$select]);
		$emails = $imap->getMessages();

		if($emails)
		{
			foreach($emails as $email)
			{
				if($imap->setSeenMessage($email->header->uid))
				{
					echo "Success reading email with {$email->header->uid}".PHP_EOL;
				}
				else
				{
					echo "Failed reading email with {$email->header->uid}".PHP_EOL;
				}
				
				if($imap->moveMessage($email->header->uid, 'INBOX'))
				{
					echo "Success move email to INBOX with {$email->header->uid}".PHP_EOL;
				}
				else
				{
					echo "Failed move email to INBOX with {$email->header->uid}".PHP_EOL;
				}
			}
		}
		else
		{
			echo "Mailbox is empty!" . PHP_EOL;
		}
	}

	$servername = "localhost";
	$dbname = "email";
	$username = "root";
	$password = "";

	try 
	{
	    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
	    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	    $stmt = $conn->prepare("SELECT * FROM email");
	    $stmt->execute();
	    $result = $stmt->fetchAll();

	    $mailbox = "imap.gmail.com";
	    if($result)
	    {
	    	foreach ($result as $res) 
		    {
		    	execution($mailbox, $res['username'], $res['password']);
		    }
	    }
		else
		{
			exit("No data were found!");
		}
	}
	catch(PDOException $e)
	{
		echo $e->getMessages();
	}
?>