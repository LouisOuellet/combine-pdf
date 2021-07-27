<?php

// Import Librairies
require_once dirname(__FILE__,3) . '/vendor/PHPMailer/src/Exception.php';
require_once dirname(__FILE__,3) . '/vendor/PHPMailer/src/PHPMailer.php';
require_once dirname(__FILE__,3) . '/vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class apiSMTP{

	public $Mailer; // Contains the PHPMailer Class
	public $URL; // Contains the CSS Reference URL

	public function __construct($host,$port,$encryption,$username,$password){

		$this->URL = "https://dev.albcie.com/";

		// Setup PHPMailer
		$this->Mailer = new PHPMailer(true);
		$this->Mailer->isSMTP();
    $this->Mailer->Host = $host;
    $this->Mailer->SMTPAuth = true;
    $this->Mailer->Username = $username;
    $this->Mailer->Password = $password;
		if($encryption == 'SSL'){
			$this->Mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		}
		if($encryption == 'STARTTLS'){
			$this->Mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		}
    $this->Mailer->Port = $port;
	}

	public function login($username,$password,$host,$port,$encryption = null){
		// Setup PHPMailer
		$mail = new PHPMailer(true);
		$mail->isSMTP();
		$mail->Host = $host;
		$mail->SMTPAuth = true;
		$mail->Username = $username;
		$mail->Password = $password;
		if($encryption == 'SSL'){ $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; }
		if($encryption == 'STARTTLS'){ $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; }
		$mail->Port = $port;
		if($mail->SmtpConnect()){return true;}else{return false;}
	}

	public function send($email, $message, $extra = []){
		$this->Mailer->ClearAllRecipients();
		if(isset($extra['subject'])){ $this->Mailer->Subject = $extra['subject']; }
		else { $this->Mailer->Subject = 'ALB Connect'; }
		if(isset($extra['from'])){ $this->Mailer->setFrom($extra['from']); }
		else { $this->Mailer->setFrom($this->Mailer->Username, 'ALB Connect'); }
		if(isset($extra['replyto'])){ $this->Mailer->addReplyTo($extra['replyto']); }
		$this->Mailer->addAddress($email);
		if((isset($extra['attachments']))&&(is_array($extra['attachments']))){
			foreach($extra['attachments'] as $attachment){
				$this->Mailer->addAttachment($attachment);
			}
		}
		$this->Mailer->isHTML(true);
		if(isset($extra['subject'])){ $this->Mailer->Subject = $extra['subject']; }
		else { $this->Mailer->Subject = 'ALB Connect'; }
		$acceptReplies = false;
		if((isset($extra['acceptReplies']))&&(($extra['acceptReplies'] == false)||($extra['acceptReplies'] == 'false'))){$acceptReplies = true;}
		$this->Mailer->Body = '';
		$this->Mailer->Body .= '
		<meta http-equiv="Content-Type" content="text/html">
		<meta name="viewport" content="width=device-width">
		<style type="text/css">
			a { text-decoration: none; color: #0088CC; }
			a:hover { text-decoration: underline }
			body {
				font-size: 18px;
				width: 100% !important;
				background-color: white;
				margin: 0;
				padding: 0;
				font-family:\'Helvetica Neue\',\'Arial\',\'Helvetica\',\'Verdana\',sans-serif;
				color: #333333;
				line-height: 26px;
			}
			.arrow-right:after {
				content: "";
				background: url("'.$this->URL.'dist/img/arrow-right_1x.png") no-repeat;
				background-position: -2px 2px;
				background-size: 24px;
				display: inline-block;
				width: 24px;
				height: 30px;
				position: absolute;
			}
			.arrow-left:after {
				content: "";
				background: url("'.$this->URL.'dist/img/arrow-left_1x.png") no-repeat;
				background-position: -2px 2px;
				background-size: 24px;
				display: inline-block;
				width: 24px;
				height: 30px;
				position: absolute;
			}
		</style>
		<meta name="format-detection" content="telephone=no">
		<table style="border-collapse: collapse;" width="100%" height="100%" cellspacing="0" cellpadding="0" border="0" align="center">
			<tbody>
				<tr><td class="top-padding" style="line-height:120px;" width="100%">&nbsp;</td></tr>
				<tr>
					<td valign="top">
						<table style="border-collapse: collapse;" width="100%" cellspacing="0" cellpadding="0" border="0">
							<tbody>
								<tr style="width:100%!important;" align="center">
									<td>
										<table style="border-collapse: collapse;" width="692" cellspacing="0" cellpadding="0" border="0" align="center">
											<tbody>
												<tr width="100%" border="0" cellspacing="0" cellpadding="0">
													<td style="padding:0px 0px 0px 0px;" align="center">
														<span class="logo">
															<img src="'.$this->URL.'dist/img/logo-mail.png" alt="" moz-do-not-send="true" width="auto" height="auto" style="max-width: 250px;" border="0">
														</span>
													</td>
												</tr>';
												if(isset($extra['title'])){
													$this->Mailer->Body .= '
														<tr>
															<td style="padding:0px 0px 0px 0px;" valign="top" align="center">
																<table style="border-collapse: collapse;" width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
																	<tbody>
																		<tr align="center">
																			<td class="heading" style="font-family:\'Helvetica Neue\',\'Arial\',\'Helvetica\',\'Verdana\',sans-serif; font-size:52px; line-height:56px; font-weight: 200;padding:40px 0px 64px 0px; margin:0; border: 0; display:block; text-align:center;" width="90%" align="center">'.$extra['title'].'</td>
																		</tr>
																	</tbody>
																</table>
															</td>
														</tr>';
													}
		$this->Mailer->Body .= '
											</tbody>
										</table>
										<table style="border-collapse: collapse;" width="692px" cellspacing="0" cellpadding="0" border="0" align="center">
											<tbody>
												<tr>
													<td style="color:#333333; padding:0px 0px 64px 0px; margin:0px;" class="emailcontent" width="692px">
														<table style="border-collapse: collapse;" width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
															<tbody>
																<tr>
																	<td>
																		<table style="border-collapse: collapse;" width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
																			<tbody>
																				<tr>
																					<td style="padding:7px 0 19px; margin:0; font-family:\'Helvetica Neue\',\'Arial\',\'Helvetica\',\'Verdana\',sans-serif; color: #333333;font-size:18px; line-height: 26px; width:692px; text-align:justify">
																						'.$message.'
																					</td>
																				</tr>
																			</tbody>
																		</table>';
																		if(isset($extra['href'])){
																			$this->Mailer->Body .= '
																				<table style="border-collapse: collapse;" width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
																					<tbody>
																						<tr>
																							<td style="padding:7px 0 19px; margin:0; font-family:\'Helvetica Neue\',\'Arial\',\'Helvetica\',\'Verdana\',sans-serif; color: #333333;font-size:18px; line-height: 26px; width:692px">
																								Case ID: 101413965073<br>
																								<a href="'.$extra['href'].'" style="color:#0088cc" class="aapl-link arrow-right" moz-do-not-send="true">Open this case</a>
																							</td>
																						</tr>
																					</tbody>
																				</table>';
																		}
		$this->Mailer->Body .= '
																		<table style="border-collapse: collapse;" width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
																			<tbody>
																				<tr>
																					<td style="padding:7px 0 19px; margin:0; font-family:\'Helvetica Neue\',\'Arial\',\'Helvetica\',\'Verdana\',sans-serif; color: #333333;font-size:18px; line-height: 26px; width:692px">
																						Sincerely,<br>
																						ALB Team
																					</td>
																				</tr>
																			</tbody>
																		</table>
																	</td>
																</tr>
															</tbody>
														</table>
													</td>
												</tr>
											</tbody>
										</table>
									</td>
								</tr>';
							$this->Mailer->Body .= '
								<tr style="width:100%!important; background-color:#343A40;" align="center">
									<td class="footer" style="padding-top: 64px; padding-bottom: 64px">
										<table style="border-collapse: collapse;" width="692" cellspacing="0" cellpadding="0" border="0" align="center">
											<tbody>
												<tr width="100%" border="0" cellspacing="0" cellpadding="0">
													<td style="font-family:\'Helvetica Neue\',\'Arial\',\'Helvetica\',\'Verdana\',sans-serif;color:#999999; text-align:center; font-size:12px; line-height:16px; padding:4px;" align="center">
														TM and copyright &copy; '.date('Y').'
													</td>
												</tr>
												<tr width="100%" border="0" cellspacing="0" cellpadding="0">
													<td style="font-family:\'Helvetica Neue\',\'Arial\',\'Helvetica\',\'Verdana\',sans-serif;text-align:center; font-size:12px; line-height:16px; color:#999999" align="center">
														<a style="color:#ffffff;margin-right:4px;" href="'.$this->URL.'?p=legal" moz-do-not-send="true">All Rights Reserved</a>|
														<a style="margin-left:4px;margin-right:4px;color:#ffffff;" href="'.$this->URL.'?p=privacy-policy" moz-do-not-send="true">Privacy Policy</a>|
														<a style="margin-left:4px;color:#ffffff;" href="'.$this->URL.'?p=support" moz-do-not-send="true">Support</a>
													</td>
												</tr>';
												if($acceptReplies){
													$this->Mailer->Body .= '
														<tr width="100%" border="0" cellspacing="0" cellpadding="0">
															<td style="font-family:\'Helvetica Neue\',\'Arial\',\'Helvetica\',\'Verdana\',sans-serif;color:#999999; text-align:center; font-size:12px; line-height:16px; padding:4px;padding-top:32px; " align="center">
																This message was sent to you from an email address that does not accept incoming messages.<br>
																Any replies to this message will not be read. If you have questions, please visit <a href="'.$this->URL.'?p=contact" style="color: #ffffff" moz-do-not-send="true">'.$this->URL.'?p=contact</a>.
															</td>
														</tr>';
													}
		$this->Mailer->Body .= '
											</tbody>
										</table>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		';
		$status = $this->Mailer->send();
		$this->Mailer->clearAttachments();
		$this->Mailer->clearAllRecipients();
		$this->Mailer->clearAddresses();
		$this->Mailer->clearBCCs();
		$this->Mailer->clearCCs();
		$this->Mailer->clearCustomHeaders();
		$this->Mailer->clearReplyTos();
		return $status;
	}
}
