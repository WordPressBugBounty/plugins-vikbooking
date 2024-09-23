<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

class smtp_message_class extends email_message_class
{
	/* Private variables */

	var $smtp;
	var $line_break="\r\n";
	var $delivery = 0;

	/* Public variables */

/*
{metadocument}
	<variable>
		<name>localhost</name>
		<type>STRING</type>
		<value></value>
		<documentation>
			<purpose>Specify the domain name of the computer sending the
				message.</purpose>
			<usage>This value is used to identify the sending machine to the
				SMTP server. When using the direct delivery mode, if this variable
				is set to a non-empty string it used to generate the
				<tt>Recieved</tt> header to show that the message passed by the
				specified host address. To prevent confusing directly delivered
				messages with spam, it is strongly recommended that you set this
				variable to you server host name.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $localhost="";

/*
{metadocument}
	<variable>
		<name>smtp_host</name>
		<type>STRING</type>
		<value></value>
		<documentation>
			<purpose>Specify the address of the SMTP server.</purpose>
			<usage>Set to the address of the SMTP server that will relay the
				messages. This variable is not used in direct delivery mode.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_host="localhost";

/*
{metadocument}
	<variable>
		<name>smtp_port</name>
		<type>INTEGER</type>
		<value>25</value>
		<documentation>
			<purpose>Specify the TCP/IP port of SMTP server to connect.</purpose>
			<usage>Most servers work on port 25 . Certain e-mail services use
				alternative ports to avoid firewall blocking. Gmail uses port
				<integervalue>465</integervalue>.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_port=25;

/*
{metadocument}
	<variable>
		<name>smtp_ssl</name>
		<type>BOOLEAN</type>
		<value>0</value>
		<documentation>
			<purpose>Specify whether it should use secure connections with SSL
				to connect to the SMTP server.</purpose>
			<usage>Certain e-mail services like Gmail require SSL connections.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_ssl=0;

/*
{metadocument}
	<variable>
		<name>smtp_start_tls</name>
		<type>BOOLEAN</type>
		<value>0</value>
		<documentation>
			<purpose>Specify whether it should use secure connections starting
				TLS protocol after connecting to the SMTP server.</purpose>
			<usage>Certain e-mail services like Hotmail require starting TLS
				protocol after the connection to the SMTP server is already
				established.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_start_tls=0;

/*
{metadocument}
	<variable>
		<name>smtp_direct_delivery</name>
		<type>BOOLEAN</type>
		<value>0</value>
		<documentation>
			<purpose>Boolean flag that indicates whether the message should be
				sent in direct delivery mode.</purpose>
			<usage>Set this to <tt><booleanvalue>1</booleanvalue></tt> if you
				want to send urgent messages directly to the recipient domain SMTP
				server.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_direct_delivery=0;

/*
{metadocument}
	<variable>
		<name>smtp_getmxrr</name>
		<type>STRING</type>
		<value>getmxrr</value>
		<documentation>
			<purpose>Specify the name of the function that is called to determine
				the SMTP server address of a given domain.</purpose>
			<usage>Change this to a working replacement of the PHP
				<tt>getmxrr()</tt> function if this is not working in your system
					and you want to send messages in direct delivery mode.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_getmxrr="getmxrr";

/*
{metadocument}
	<variable>
		<name>smtp_exclude_address</name>
		<type>STRING</type>
		<value></value>
		<documentation>
			<purpose>Specify an address that should be considered invalid
				when resolving host name addresses.</purpose>
			<usage>In some networks any domain name that does not exist is
				resolved as a sub-domain of the default local domain. If the DNS is
				configured in such way that it always resolves any sub-domain of
				the default local domain to a given address, it is hard to
				determine whether a given domain does not exist.<paragraphbreak />
				If your network is configured this way, you may set this variable
				to the address that all sub-domains of the default local domain
				resolves, so the class can assume that such address is invalid.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_exclude_address="";

/*
{metadocument}
	<variable>
		<name>smtp_user</name>
		<type>STRING</type>
		<value></value>
		<documentation>
			<purpose>Specify the user name for authentication.</purpose>
			<usage>Set this variable if you need to authenticate before sending
				a message.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_user="";

/*
{metadocument}
	<variable>
		<name>smtp_realm</name>
		<type>STRING</type>
		<value></value>
		<documentation>
			<purpose>Specify the user authentication realm.</purpose>
			<usage>Set this variable if you need to authenticate before sending
				a message.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_realm="";

/*
{metadocument}
	<variable>
		<name>smtp_workstation</name>
		<type>STRING</type>
		<value></value>
		<documentation>
			<purpose>Specify the user authentication workstation needed when
				using the <tt>NTLM</tt> authentication (Windows or Samba).</purpose>
			<usage>Set this variable if you need to authenticate before sending
				a message.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_workstation="";

/*
{metadocument}
	<variable>
		<name>smtp_authentication_mechanism</name>
		<type>STRING</type>
		<value></value>
		<documentation>
			<purpose>Specify the user authentication mechanism that should be
				used when authenticating with the SMTP server.</purpose>
			<usage>Set this variable if you need to force the SMTP connection to
				authenticate with a specific authentication mechanism. Leave this
				variable with an empty string if you want the authentication
				mechanism be determined automatically from the list of mechanisms
				supported by the server.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_authentication_mechanism="";

/*
{metadocument}
	<variable>
		<name>smtp_password</name>
		<type>STRING</type>
		<value></value>
		<documentation>
			<purpose>Specify the user authentication password.</purpose>
			<usage>Set this variable if you need to authenticate before sending
				a message.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_password="";

/*
{metadocument}
	<variable>
		<name>smtp_pop3_auth_host</name>
		<type>STRING</type>
		<value></value>
		<documentation>
			<purpose>Specify the server address for POP3 based authentication.</purpose>
			<usage>Set this variable to the address of the POP3 server if the
				SMTP server requires POP3 based authentication.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_pop3_auth_host="";

/*
{metadocument}
	<variable>
		<name>smtp_debug</name>
		<type>BOOLEAN</type>
		<value>0</value>
		<documentation>
			<purpose>Specify whether it is necessary to output SMTP connection
				debug information.</purpose>
			<usage>Set this variable to
				<tt><booleanvalue>1</booleanvalue></tt> if you need to see
				the progress of the SMTP connection and protocol dialog when you
				need to understand the reason for delivery problems.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_debug=0;

/*
{metadocument}
	<variable>
		<name>smtp_html_debug</name>
		<type>BOOLEAN</type>
		<value>0</value>
		<documentation>
			<purpose>Specify whether the debug information should be outputted in
				HTML format.</purpose>
			<usage>Set this variable to
				<tt><booleanvalue>1</booleanvalue></tt> if you need to see
				the debug output in a Web page.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $smtp_html_debug=0;

/*
{metadocument}
	<variable>
		<name>esmtp</name>
		<type>BOOLEAN</type>
		<value>1</value>
		<documentation>
			<purpose>Specify whether the class should try to use Enhanced SMTP
				protocol features.</purpose>
			<usage>It is recommended to leave this variable set to
				<tt><booleanvalue>1</booleanvalue></tt> so the class can take
				advantage of Enhanced SMTP protocol features.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $esmtp=1;

/*
{metadocument}
	<variable>
		<name>timeout</name>
		<type>INTEGER</type>
		<value>25</value>
		<documentation>
			<purpose>Specify the connection timeout period in seconds.</purpose>
			<usage>Change this value if for some reason the timeout period seems
				insufficient or otherwise it seems too long.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $timeout=55;

/*
{metadocument}
	<variable>
		<name>invalid_recipients</name>
		<type>ARRAY</type>
		<value></value>
		<documentation>
			<purpose>Return the list of recipient addresses that were not
				accepted by the SMTP server.</purpose>
			<usage>Check this variable after attempting to send a message to
				figure whether there were any recipients that were rejected by the
				SMTP server.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $invalid_recipients=array();

/*
{metadocument}
	<variable>
		<name>mailer_delivery</name>
		<value>smtp $Revision: 1.34 $</value>
		<documentation>
			<purpose>Specify the text that is used to identify the mail
				delivery class or sub-class. This text is appended to the
				<tt>X-Mailer</tt> header text defined by the
				mailer variable.</purpose>
			<usage>Do not change this variable.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $mailer_delivery='smtp $Revision: 1.34 $';

/*
{metadocument}
	<variable>
		<name>maximum_bulk_deliveries</name>
		<type>INTEGER</type>
		<value>100</value>
		<documentation>
			<purpose>Specify the number of consecutive bulk mail deliveries
				without disconnecting.</purpose>
			<usage>Lower this value if you have enabled the bulk mail mode but
				the SMTP server does not accept sending more than a number of
				messages within the same SMTP connection.<paragraphbreak />
				Set this value to <integervalue>0</integervalue> to never
				disconnect during bulk mail mode unless an error occurs.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $maximum_bulk_deliveries=100;

	Function SetRecipients(&$recipients,&$valid_recipients)
	{
		for($valid_recipients=$recipient=0,Reset($recipients);$recipient<count($recipients);Next($recipients),$recipient++)
		{
			$address=Key($recipients);
			if($this->smtp->SetRecipient($address))
				$valid_recipients++;
			else
				$this->invalid_recipients[$address]=$this->smtp->error;
		}
		return(1);
	}

	Function ResetConnection($error)
	{
		if(IsSet($this->smtp))
		{
			if(!$this->smtp->Disconnect()
			&& strlen($error) == 0)
				$error = $this->smtp->error;
			UnSet($this->smtp);
		}
		if(strlen($error))
			$this->OutputError($error);
		return($error);
	}

	Function StartSendingMessage()
	{
		if(function_exists("class_exists")
		&& !class_exists("smtp_class"))
			return("the smtp_class class was not included");
		if(IsSet($this->smtp))
			return("");
		$this->smtp=new smtp_class;
		$this->smtp->localhost=$this->localhost;
		$this->smtp->host_name=$this->smtp_host;
		$this->smtp->host_port=$this->smtp_port;
		$this->smtp->ssl=$this->smtp_ssl;
		$this->smtp->start_tls=$this->smtp_start_tls;
		$this->smtp->timeout=$this->timeout;
		$this->smtp->debug=$this->smtp_debug;
		$this->smtp->html_debug=$this->smtp_html_debug;
		$this->smtp->direct_delivery=$this->smtp_direct_delivery;
		$this->smtp->getmxrr=$this->smtp_getmxrr;
		$this->smtp->exclude_address=$this->smtp_exclude_address;
		$this->smtp->pop3_auth_host=$this->smtp_pop3_auth_host;
		$this->smtp->user=$this->smtp_user;
		$this->smtp->realm=$this->smtp_realm;
		$this->smtp->workstation=$this->smtp_workstation;
		$this->smtp->authentication_mechanism=$this->smtp_authentication_mechanism;
		$this->smtp->password=$this->smtp_password;
		$this->smtp->esmtp=$this->esmtp;
		if($this->smtp->Connect())
		{
			$this->delivery = 0;
			return("");
		}
		return($this->ResetConnection($this->smtp->error));
	}

	Function SendMessageHeaders($headers)
	{
		$header_data="";
		$date=date("D, d M Y H:i:s T");
		if($this->smtp_direct_delivery
		&& strlen($this->localhost))
		{
			$local_ip=gethostbyname($this->localhost);
			$header_data.=$this->FormatHeader("Received","FROM ".$this->localhost." ([".$local_ip."]) BY ".$this->localhost." ([".$local_ip."]) WITH SMTP; ".$date)."\r\n";
		}
		for($message_id_set=$date_set=0,$header=0,$return_path=$from=$to=$recipients=array(),Reset($headers);$header<count($headers);$header++,Next($headers))
		{
			$header_name=Key($headers);
			switch(strtolower($header_name))
			{
				case "return-path":
					$return_path[$headers[$header_name]]=1;
					break;
				case "from":
					$error=$this->GetRFC822Addresses($headers[$header_name],$from);
					break;
				case "to":
					$error=$this->GetRFC822Addresses($headers[$header_name],$to);
					break;
				case "cc":
				case "bcc":
					$this->GetRFC822Addresses($headers[$header_name],$recipients);
					break;
				case "date":
					$date_set=1;
					break;
				case "message-id":
					$message_id_set=1;
					break;
			}
			if(strcmp($error,""))
				return($this->ResetConnection($error));
			if(strtolower($header_name)=="bcc")
				continue;
			$header_data.=$this->FormatHeader($header_name,$headers[$header_name])."\r\n";
		}
		if(count($from)==0)
			return($this->ResetConnection("it was not specified a valid From header"));
		Reset($return_path);
		Reset($from);
		$this->invalid_recipients=array();
		if(!$this->smtp->MailFrom(count($return_path) ? Key($return_path) : Key($from)))
			return($this->ResetConnection($this->smtp->error));
		$r = 0;
		if(count($to))
		{
			if(!$this->SetRecipients($to,$valid_recipients))
				return($this->ResetConnection($this->smtp->error));
			$r += $valid_recipients;
		}
		if(!$date_set)
			$header_data.="Date: ".$date."\r\n";
		if(!$message_id_set
		&& $this->auto_message_id)
		{
			$sender=(count($return_path) ? Key($return_path) : Key($from));
			$header_data.=$this->GenerateMessageID($sender)."\r\n";
		}
		if(count($recipients))
		{
			if(!$this->SetRecipients($recipients,$valid_recipients))
				return($this->ResetConnection($this->smtp->error));
			$r += $valid_recipients;
		}
		if($r==0)
			return($this->ResetConnection("it were not specified any valid recipients"));
		if(!$this->smtp->StartData()
		|| !$this->smtp->SendData($header_data."\r\n"))
			return($this->ResetConnection($this->smtp->error));
		return("");
	}

	Function SendMessageBody($data)
	{
		return($this->smtp->SendData($this->smtp->PrepareData($data)) ? "" : $this->ResetConnection($this->smtp->error));
	}

	Function EndSendingMessage()
	{
		return($this->smtp->EndSendingData() ? "" : $this->ResetConnection($this->smtp->error));
	}

	Function StopSendingMessage()
	{
		++$this->delivery;
		if($this->bulk_mail
		&& !$this->smtp_direct_delivery
		&& ($this->maximum_bulk_deliveries == 0
		|| $this->delivery < $this->maximum_bulk_deliveries))
			return("");
		return($this->ResetConnection(''));
	}

	Function ChangeBulkMail($on)
	{
		if($on
		|| !IsSet($this->smtp))
			return(1);
		return($this->smtp->Disconnect() ? "" : $this->ResetConnection($this->smtp->error));
	}
};

/*

{metadocument}
</class>
{/metadocument}

*/

?>