<?php
error_reporting(1);
use Restserver\Libraries\REST_Controller;
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class TelegramApi extends REST_Controller {

    function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $method = $_SERVER['REQUEST_METHOD'];
        if($method == "OPTIONS") {
            die();
        }
        parent::__construct();
        $this->methods['slip_post']['limit'] = 10000;
         $this->load->database();

    }

	private $bot_link = "https://t.me/KwikExchangeBot";
	private $public_full_path = "https://api.telegram.org/bot5620628494:AAGVmoUrdOYcrtcyXT0i1-_kz1yEM2tbefQ";
	//WebHok Configuration :: https://api.telegram.org/bot5620628494:AAGVmoUrdOYcrtcyXT0i1-_kz1yEM2tbefQ/setwebhook?url=https://wesabi.com/api/telegramApi/start



	public function start_post()
    {

   				$path = $this->public_full_path;
			
			$requestPlain = file_get_contents("php://input");
			$updates = json_decode($requestPlain, FALSE);
			
			$userInput = $updates->message->text;
			$chat_id = $updates->message->chat->id;
			$telegram_id = $updates->message->from->id;
			$name = $updates->message->from->username;
			$existing_user = $this->isUserNew($telegram_id);

			if($existing_user['is_ban'] == 1){
           
				if(time() > $existing_user['ban_expiration']){
					##### unbanned conditions
					$this->unbannedTemporary($existing_user['id']);
					file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
					$msg = 'Congratulation!!!'.chr(10).'Your account has been unbanned'.chr(10).'Click /start to chat with me.';
					file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
					
					
				}else{
					  ##### user has been banned 
					  file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
					  $msg = 'Oooops!!'.chr(10).'You are currently banned from chatting with me!'.chr(10).'Your account will be unlock at '.date('Y-m-d h:i:s', $existing_user["banned_expiration"])." (GMT+2)";
					  file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
				
				}
				
				return;
			}
		
           if(strtolower($userInput) == 'start' || strtolower($userInput) == '/start'){

				if(empty($existing_user)){
				   
					$telegramArray = array(
						'telegram_id'=>$telegram_id,
						'username'=>$name,
						'created_at'=>date('Y-m-d h:i:s')
					);
					$insert = $this->insertNew($telegramArray);
					
					if($insert){
					$this->update_stage("Registration", $existing_user['id']);
					$this->update_stagePosition(1, $existing_user['id']);
					  $msg = "Hi <b>$name</b>,".chr(10)."Welcome to KwikExchange.".chr(10)."".chr(10)."You're 7 steps to start sending funds to friends and family".chr(10)."".chr(10)."Step 1/7".chr(10).chr(10)."Kindly provide your email 📧 ";
					  file_get_contents($path."/sendmessage?chat_id=$chat_id&parse_mode=html&text=".urlencode($msg));
					return;
					}

				        $msg = "Network Issue, Kindly try again by pressing <b>START</b>";
    					file_get_contents($path."/sendmessage?chat_id=$chat_id&parse_mode=html&text=".urlencode($msg));
    					return;
    					
				}else{
				   //Check stages to determin response on clicking start
				   if($existing_user['stage'] == 'Registration'){

								if($existing_user['stage_position'] == 1){
									$msg = "Hi <b>$name</b>,".chr(10)."Welcome to KwikExchange.".chr(10)."".chr(10)."You're 7 steps to start sending funds to friends and family".chr(10)."".chr(10)."Step 1/7".chr(10).chr(10)."Kindly provide your email 📧 ";
									file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg));
									return;  
								}

								if($existing_user['stage_position'] == 2){
									$msg = "Welcome back <b>$name</b>,".chr(10)."".chr(10)."You're 6 steps to start sending funds to friends and family".chr(10)."".chr(10)."Step 2/7".chr(10).chr(10)."Kindly provide your fullname 🦒 ";
									file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg));
									return;  
								}

								if($existing_user['stage_position'] == 3){
									$msg = "Welcome back <b>$name</b>,".chr(10)."".chr(10)."You're 5 steps to start sending funds to friends and family".chr(10)."".chr(10)."Step 3/7".chr(10).chr(10)."Kindly provide your full address 🏠 ";
									file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg));
									return;  
								}

								if($existing_user['stage_position'] == 4){
									
									$keyboard =
										[[[
											'text' => 'Share my phone number',
											'request_contact' => true
										]]];
									$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
									$reply = json_encode($resp);

									$msg = "Welcome back $name,".chr(10)."".chr(10)."You're 4 steps to start sending funds to friends and family".chr(10)."".chr(10)."Step 4/7".chr(10).chr(10)."Kindly share your phone contant 📱 ";
									file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
									return;  
								}

								if($existing_user['stage_position'] == 5){
									$this->update_stage("Registration", $existing_user['id']);
								    $this->update_stagePosition(1, $existing_user['id']);
									$msg = "Welcome back <b>$name</b>,".chr(10)."".chr(10)."You're 5 steps to start sending funds to friends and family".chr(10)."".chr(10)."Step 3/7".chr(10).chr(10)."Kindly provide your full address 🏠 ";
									file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg));
									return;  
								}

				   }else{
					//Back to dashboard
					$keyboard = array(
						["Transfer Fund"], ["Transfer Status"], ["Support"]
					);
					$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
					$reply = json_encode($resp);
				
					file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
					$msg = 'Welcome to your Dashboard'.chr(10).chr(10).'Select one of the options to proceed';
					file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
				   return;

				   }
				  
				}



		   }

	}
	
	private function isUserNew($id){
		$row = $this->db->query("SELECT * FROM telegrams where telegram_id={$id}")->row_array();
		return $row;
	}
	
	private function insertNew($data){
		$this->db->insert('telegrams', $data); 
        if($this->db->affected_rows() > 0){
            return true;
        }else{
            return false;
        }
	}

	private function unbannedTemporary($id){
		$this->db->query("UPDATE telegrams SET is_ban =0,warning = 0, stage='start' where id = $id");
	}

	private function update_stage($stage, $id){
		$this->db->query("UPDATE telegrams SET stage =$stage where id = $id");
	}

	private function update_stagePosition($position,$id){
		$this->db->query("UPDATE telegrams SET stage_position =$position where id = $id");
	}

	private function attempt_increment($id){
		$this->db->query("UPDATE telegrams SET warning = (warning + 1) where id = $id");
	}


	private function getOTPEmailTemplate($name,$code){
		return '<div  style="display: none">tonoit Telegram Verification.</div>
			 <table border="0" cellpadding="0" cellspacing="0" >
	   <tbody><tr>
		 <td>&nbsp;</td>
		 <td >
			 <table style="width: 100%;">
			   <tbody><tr>
				 <td >
				   <table border="0" cellpadding="0" cellspacing="0" style="font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;    width: 100%;">
					 <tbody><tr>
					   <td>
						 <a href="https://www.tonoit.com" rel="noreferrer" target="_blank" ><img src="https://www.tonoit.com/assets/tonoit/logo.png"></a>
						 <hr>
						 <p style="padding:0;font-size:28px;color:#2672ec">Dear '.$name.' ,</p>
						 <p style="padding:0;padding-top:15px;font-size:15px;color:#2a2a2a">You attempted to link your telegram with tonoit.<br><br>Kindly use the verification code: <span style="font-weight:bold;color:#2672ec">'.$code.'</span></p>       
						
						 <hr>
						 
						 <p style="padding-top:15px;margin-bottom:0px;font-size:14px;color:#2a2a2a">Best Regards<br>tonoit Team,</p>
						 
					   </td>
					 </tr>
				   </tbody></table>
				 </td>
			   </tr>
	 
			 
			 </tbody>
			 
		 </table>
	 
			 
			   <table border="0" cellpadding="0" cellspacing="0">
				 <tbody><tr>
				   <td class="">
					 <br>
					 <br>
					   <div><font size="2" style="color: rgb(0, 0, 0); font-family:Roboto,RobotoDraft,Helvetica,Arial,sans-serif;"><b><i>tonoit Team<br></i></b></font><a href="mailto:support@tonoit.com" rel="noreferrer" target="_blank" style="color: rgb(17, 85, 204); font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;; font-size: 12px;"><span style="color: rgb(0, 0, 0);">support@</span><span style="color: rgb(0, 0, 0);">tonoit.com</span></a><br style="color: rgb(0, 0, 0); font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;; font-size: 12px;"><br style="color: rgb(0, 0, 0); font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;; font-size: 12px;"><br style="color: rgb(0, 0, 0); font-family: Helvetica; font-size: 12px;"><span style="color: rgb(0, 0, 0); font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif; font-size: 12px;">
					 <img style="width: 113px;" src="https://www.tonoit.com/assets/tonoit/logo.png">               
					 <div><br class="m_-3936905741563449462m_-716047227135624900Apple-interchange-newline"><font face="Helvetica-LightOblique"><span style="font-size: 11px;"><i><span style="font-weight: bold;font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;">Disclaimer:</span> Tonoit does not accept&nbsp;responsibility or liability for the&nbsp;unauthorized use of its e-mail facility&nbsp;and/or the use of its e-mail facility other&nbsp;than for its own authorized business&nbsp;purposes. Save for statements and/or&nbsp;opinions relating to bona fide company&nbsp;matters. Tonoit &nbsp;denies&nbsp;responsibility or liability for the contents&nbsp;of this communication. The contents of&nbsp;this e-mail and any accompanying&nbsp;documentation are confidential and any&nbsp;use thereof, in whatever form, by anyone&nbsp;other than the addressee for whom it is&nbsp;intended, is strictly prohibited. If you&nbsp;suspect the message may have been&nbsp;intercepted or amended please contact&nbsp;Tonoit at&nbsp;</i></span></font><a href="mailto:support@tonoit.com" rel="noreferrer" target="_blank" style="color: rgb(17, 85, 204);">support@tonoit.com</a></div><div><font face="Helvetica-LightOblique"><span style="font-size: 11px;"><i><br></i></span></font><div class="yj6qo"></div><div class="adL"><br></div></div><div class="adL"></div></span></div>
	 
				   </td>
				 </tr>
				 <tr>
				   <td style="font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;">
					 All Rights Reserved.
				   </td>
				 </tr>
			   </tbody></table>
	 
		 </td>
		 <td>&nbsp;</td>
	   </tr>
	 </tbody></table>';
	 
	 
	 }
	 
	  private function sendMailNow($email,$content,$subject){
	 
		 $apiUrl = "https://api.sendgrid.com/v3/mail/send";
	 
		 $requestHeaders = [
			 'Content-type: application/json',
			 'Authorization: Bearer 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'
		 ];
	 
	 
		 //$fromEmail = $settings['admin_email'];
		// $fromEmail = "donotreply@pythonsignals.com";
		 $fromEmail = "support@tonoit.com";
		 
			 $param = [ "personalizations"=>array(["to"=>[array("email"=>$email)]]),
				 "from"=>["email"=>$fromEmail,"name"=>"tonoit"],
				 "subject"=>$subject,
				 "content"=>[array(
					 "type"=>"text/html",
					 "value"=>$content
				 )]
			 ];
	 
	 
		 $ch = curl_init($apiUrl);
		 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		 curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		 curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
		 $response = curl_exec($ch);
	 
		 $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	 
		 $err = curl_error($ch);
	 
		 curl_close($ch);
	 
		 if ($err){
			 ### failed
			 return array("status"=>false,"message"=>json_encode(array("error"=>$err)));
		 }else{
			 switch ($httpcode){
				 case 202:  # OK
					 $res = json_decode($response);
					 return array("status"=>true,"message"=>$response,"httpcode"=>$httpcode);
					 break;
				 default:
					 return array("status"=>false,"message"=>$response,"httpcode"=>$httpcode);
			 }
		 }
	 
	 
	 }	
	 
	 function sendOTPEmail($name,$code,$email,$subject){
		$content = $this->getOTPEmailTemplate($name,$code);
	   return $this->sendMailNow($email,$content,$subject);
	}
}
