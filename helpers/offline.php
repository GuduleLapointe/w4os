<?php
/**
 * offline.php
 *
 * Handles Instant Messages sent to offline users and deliver them when the user
 * comes back online. If the user has enabled Send offline IM by email (in
 * viewer preferences), the messages are also forwarded immediately by email.
 *
 * @package     magicoli/opensim-helpers
 * @author      Gudule Lapointe <gudule@speculoos.world>
 * @link            https://github.com/magicoli/opensim-helpers
 * @license     AGPLv3
 *
 * Includes portions of code from
 *   http://opensimulator.org/wiki/Offline_Messaging
 *   http://www.weberdev.com/get_example-4372.html
 **/

require_once __DIR__ . '/bootstrap.php';
// require_once 'includes/config.php';
require_once 'includes/databases.php';

if ( ! isset( $OpenSimDB ) ) {
	die();
}

$request_xml = file_get_contents( 'php://input' );
if ( empty( $request_xml ) ) {
	osXmlResponse( false, 'Invalid request' );
	die();
}

$method = @$_SERVER['PATH_INFO)'];
if ( empty( $method ) ) {
	$method = '/' . basename( getenv( 'REDIRECT_URL' ) ) . '/';
}

$xml = new SimpleXMLElement( $request_xml );

switch ( $method ) {
	case '/SaveMessage/':
		if ( strpos( $request_xml, '?>' ) == -1 ) {
			osXmlResponse( false );
			die;
		}

		// Save for in-world delivery
		$saved = $OpenSimDB->prepareAndExecute(
			'INSERT INTO ' . OFFLINE_MESSAGE_TBL . ' (PrincipalID, FromID, Message)
		VALUES (:PrincipalID, :FromID, :Message)',
			array(
				'PrincipalID' => $xml->toAgentID,
				'FromID'      => $xml->fromAgentID,
				'Message'     => preg_replace( '/>\n/', '>', $xml->asXML() ),
			)
		);
		osXmlResponse( $saved );  // Output in-world save result
		dontWait(); // flush output, continue in background

		// Save to mail queue if mail forwarding is set
		$emailinfo = $OpenSimDB->prepareAndExecute(
			'SELECT imviaemail, email FROM usersettings WHERE useruuid = :useruuid',
			array(
				'useruuid' => $xml->toAgentID,
			)
		);

		if ( $emailinfo ) {
			list($sendmail, $email) = $emailinfo->fetch();
			if ( empty( $email ) || $sendmail == 'false' ) {
				die();
			}

			if ( $xml->fromAgentName == 'Server' ) {
				$xml->fromAgentName = OPENSIM_GRID_NAME;
			}

			$headers  = "From: $xml->fromAgentName <" . OPENSIM_MAIL_SENDER . ">\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-type: text/html; charset=UTF-8\r\n";
			$parts    = explode( '|', $xml->message );
			if ( count( $parts ) > 1 ) {
				$subject = $parts[0];
				$body    = "<h4>$subject</h4>" . $parts[1];
			} else {
				$body = $xml->message;
			}
			if ( ! empty( OPENSIM_GRID_NAME ) ) {
				$in = ' in ' . OPENSIM_GRID_NAME;
			}

			switch ( $xml->dialog ) {
				// To complete from http://wiki.secondlife.com/wiki/ImprovedInstantMessage
				case '32':
					$subject = "Group notice: $subject";
					$intro   = "$xml->fromAgentName sent a group notice$in:";
					break;

				case '3':
					$subject = "Group invitation from $xml->fromAgentName";
					$intro   = $body;
					$body    = 'Log in-world to accept or decline';
					break;

				case '4':
					$subject = "Inventory offer from $xml->fromAgentName";
					$intro   = "$xml->fromAgentName returned you " . $body;
					$body    = 'Log in-world to accept or decline';
					break;

				// case "19":
				// $subject = "Message from $xml->fromAgentName";
				// $intro = $body;
				// break;

				case '38':
					$subject = "Friendship offer from $xml->fromAgentName";
					$intro   = $body;
					$body    = 'Log in-world to accept or decline.';
					break;

				default:
					$subject = "Message from $xml->fromAgentName";
					$outro   = "Sent by $xml->fromAgentName $in";
			}
			$body    = htmlspecialchars( $body );
			$subject = htmlspecialchars( $subject );

			$body = str_replace( "\n", "\n<br>", $body );
						// $body = str_replace( "\n", "\r\n", $body );

			$body = '<html><body>'
			. ( empty( $intro ) ? '' : '<p></p>' )
			. $body
			. "\r\n"
			. "\r\n"
			. '<hr>'
			. ( empty( $outro ) ? '' : "<blockquote>$outro</blockquote>" )
			. '<p style="font-size:small"><b>' . OPENSIM_GRID_NAME . '</b> Instant Messages mail forwarding.'
			. '<br>Please log in-world to answer to this message. Emails to the sender address will not be processed.'
			. '<br>To disable mail notifications, uncheck option "Send IM to mail" in your viewer preferences (tab "Chat" or "Communications").'
			. '</p></body></html>';

			if ( defined( 'WPINC' ) ) {
				// We're inside WordPress, use wp_mail()
				add_action(
					'plugins_loaded',
					function () use ( $email, $subject, $body, $headers ) {
						$result = wp_mail( $email, $subject, $body, $headers );
						if ( ! $result ) {
							error_log( __FILE__ . "error $result sending IM notification to $email." );
						}
						die();
					},
					99
				);
			} else {
				// use standard PHP mail, might need a local smtp server
				$result = mail( $email, $subject, $body, $headers );
				if ( ! $result ) {
					error_log( __FILE__ . "error $result sending IM notification to $email." );
				}
				die();
			}
		}
		break;

	case '/RetrieveMessages/':
		if ( is_uuid( $xml->Guid ) ) {
			$pendingmessages = $OpenSimDB->prepareAndExecute(
				'SELECT ID, Message FROM ' . OFFLINE_MESSAGE_TBL . ' WHERE PrincipalID = :PrincipalID',
				array(
					'PrincipalID' => $xml->Guid,
				)
			);
			if ( $pendingmessages ) {
				$delivered = array();
				echo '<?xml version="1.0" encoding="utf-8"?>';
				echo '<ArrayOfGridInstantMessage xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';
				while ( list($id, $message) = $pendingmessages->fetch() ) {
					$start = strpos( $message, '?>' );
					if ( $start != -1 ) {
						$message = substr( $message, $start + 2 );
					}
					echo $message;
					$delivered[] = $id;
				}
				echo '</ArrayOfGridInstantMessage>';
				if ( ! empty( $delivered ) ) {
					$result = $OpenSimDB->prepareAndExecute(
						'DELETE FROM ' . OFFLINE_MESSAGE_TBL . ' WHERE ID=' . join( ' OR ID=', $delivered )
					);
				}
			} else {
				error_log( 'error retrieving pending messages' );
				osXmlResponse( false );
			}
		} else {
			error_log( $xml->Guid . ' is not a valid UUID' );
		}
		die();
	break;

	// '//': die(); // empty request

	default:
		error_log( "Offline messages: method $method not implemented, please configure OfflineMessageModule = OfflineMessageModule in OpenSim.ini" );
		osXmlResponse( false, "method $method not implemented" );
		die();
}
