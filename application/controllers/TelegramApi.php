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
	//WebHok Configuration :: https://api.telegram.org/bot5620628494:AAGVmoUrdOYcrtcyXT0i1-_kz1yEM2tbefQ/setwebhook?url=https://kwikxchangebot.herokuapp.com/telegramApi/start

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
					return;
				}else{
					  ##### user has been banned 
					  file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
					  $msg = 'Oooops!!'.chr(10).'You are currently banned from chatting with me!'.chr(10).'Your account will be unlock at '.date('Y-m-d h:i:s', $existing_user["banned_expiration"])." (GMT+2)";
					  file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
					  return;
				
				}
				
			}
		
			// If User clicked START
           if(strtolower($userInput) == 'start' || strtolower($userInput) == '/start'){

				if(empty($existing_user)){
				   
					$telegramArray = array(
						'telegram_id'=>$telegram_id,
						'username'=>$name,
						'created_at'=>date('Y-m-d h:i:s')
					);

					
					$telegramArray2 = array(
						'telegram_id'=>$telegram_id,
						'tier_stage'=>0,
						'status'=> 0,
						'created_at'=>date('Y-m-d h:i:s')
					);
					$insert = $this->insertNew($telegramArray);
					
					if($insert){
					$this->insertUsers($telegramArray2);
					$ar = array('stage'=>'Registration', 'stage_position'=>1);
					$this->update_stage($ar, $telegram_id);
					  $msg = "Hi <b>$name</b>,".chr(10)."Welcome to KwikExchange.".chr(10)."".chr(10)."You're 5 steps to start sending funds to friends and family in Nigeria".chr(10)."".chr(10)."Step 1/5".chr(10).chr(10)."Kindly provide your email 📧 ";
					  file_get_contents($path."/sendmessage?chat_id=$chat_id&parse_mode=html&text=".urlencode($msg));
					return;
					}

				        $msg = "Network Issue, Kindly try again by pressing <b>START</b>";
    					file_get_contents($path."/sendmessage?chat_id=$chat_id&parse_mode=html&text=".urlencode($msg));
    					return;
    					
				}else{
					$this->unbannedTemporary($existing_user['id']);
				   //Check stages to determin response on clicking start
				   if($existing_user['stage'] == 'Registration'){

								if($existing_user['stage_position'] == 1){
									$msg = "Hi <b>$name</b>,".chr(10)."Welcome to KwikExchange.".chr(10)."".chr(10)."You're 5 steps to start sending funds to friends and family in Nigeria".chr(10)."".chr(10)."Step 1/5".chr(10).chr(10)."Kindly provide your email 📧 ";
									file_get_contents($path."/sendmessage?chat_id=$chat_id&parse_mode=html&text=".urlencode($msg));
									return;  
								}

								if($existing_user['stage_position'] == 2){
									$msg = "Welcome back <b>$name</b>,".chr(10)."".chr(10)."You're 4 steps to start sending funds to friends and family in Nigeria".chr(10)."".chr(10)."Step 2/5".chr(10).chr(10)."Kindly provide your fullname 🦒 ";
									file_get_contents($path."/sendmessage?chat_id=$chat_id&parse_mode=html&text=".urlencode($msg));
									return;  
								}

								if($existing_user['stage_position'] == 3){
									$msg = "Welcome back <b>$name</b>,".chr(10)."".chr(10)."You're 3 steps to start sending funds to friends and family in Nigeria".chr(10)."".chr(10)."Step 3/5".chr(10).chr(10)."Kindly provide your full address 🏠 ";
									file_get_contents($path."/sendmessage?chat_id=$chat_id&parse_mode=html&text=".urlencode($msg));
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

									$msg = "Welcome back $name,".chr(10)."".chr(10)."You're 2 steps to start sending funds to friends and family in Nigeria".chr(10)."".chr(10)."Step 4/5".chr(10).chr(10)."Kindly share your phone contant 📱 ";
									file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
									return;  
								}

								if($existing_user['stage_position'] == 5){
									$keyboard =
										[[[
											'text' => 'Share location',
											'request_location' => true
										]]];
									$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
									$reply = json_encode($resp);

									$msg = "Welcome back $name,".chr(10)."".chr(10)."You're final step to start sending funds to friends and family in Nigeria".chr(10)."".chr(10)."Step 5/5".chr(10).chr(10)."Kindly share your location 🗺 ";
									file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
									return;  
								}

				   }elseif($existing_user['stage'] == 'Registered' || $existing_user['stage'] == 'Transfer Fund' || $existing_user['stage'] == 'Transfer Status' || $existing_user['stage'] == 'Contact Support'){
					$ar = array('stage'=>'Registered','stage_position'=>0);
					$this->update_stage($ar, $telegram_id);
					//Back to dashboard
					$keyboard = array(
						["Transfer Fund"], ["Transfer Status"], ["Contact Support"]
					);
					$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
					$reply = json_encode($resp);
				
					file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
					$msg = 'Welcome to your Dashboard'.chr(10).chr(10).'Select one of the options to proceed';
					file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
				   return;

				   }else{

					$keyboard = array(
						["START"]
					);
					$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
					$reply = json_encode($resp);
				
					file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
					$msg = 'Something went wrong'.chr(10).chr(10).'Kindly press START to continue';
					file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
				   return;

				   }
				  
				}
		   }else{
			$userID= $existing_user['id'];
			$banned_expiration = Date("Y-m-d H:i:s",strtotime("+ 10mins"));

			################# REGISTRATION STAGE #########################
			if($existing_user['stage'] == 'Registration'){

				####################### EMAIL SUBMISSION ######################################
				if($existing_user['stage_position'] == 1){

					if(filter_var($userInput, FILTER_VALIDATE_EMAIL)){
						$array=array('email'=>$userInput);
						$email_exist = $this->userExist($userInput);

						if(empty($email_exist)){

							####################### UPDATE USER EMAIL AND SATGE POSITION FOR REGISTRATION ######################################
							$this->update_telegramUsers($array, $telegram_id);
							$ar = array('stage_position'=>2);
							$this->update_stage($ar, $telegram_id);
							####################### END OF UPDATE USER EMAIL AND SATGE POSITION FOR REGISTRATION ######################################

							####################### RESPONSE FOR FULLNAME ######################################
							$msg = "You're 4 steps to start sending funds to friends and family in Nigeria".chr(10)."".chr(10)."Step 2/5".chr(10).chr(10)."Kindly provide your fullname 🦒 ";
							file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg));
							return; 
							####################### END OF RESPONSE FOR FULLNAME ###################################### 
						}else{
							$returnValue = $this->attempt_increment($userID);
						if($returnValue >= 5){
							$this->banTemporary($userID);
							file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
							$msg = 'Oooops!!'.chr(10).chr(10).'You are currently banned from chatting with me!'.chr(10).chr(10).'Your account will be unlock at '.date('Y-m-d h:i:s', $banned_expiration)." (GMT+2)";
							file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
							return;
						}else{
							file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
							$msg = 'Email already exist!'.chr(10).'('.$returnValue.'/5) attempts'.chr(10).chr(10);
							 $msg .= "Kindly provide your valid email 📧 ";
							 file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
						}
						}
						 
					}else{
						####################### BAN AT EMAIL SESSION ######################################
						$returnValue = $this->attempt_increment($userID);
						if($returnValue >= 5){
							$this->banTemporary($userID);
							file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
							$msg = 'Oooops!!'.chr(10).chr(10).'You are currently banned from chatting with me!'.chr(10).chr(10).'Your account will be unlock at '.date('Y-m-d h:i:s', $banned_expiration)." (GMT+2)";
							file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
							return;
						}else{
							file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
							$msg = 'Invalid email supplied!'.chr(10).'('.$returnValue.'/5) attempts'.chr(10).chr(10);
							 $msg .= "Kindly provide your valid email 📧 ";
							 file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
						}
						####################### END OF BAN AT EMAIL SESSION ######################################
					}
				}
				####################### END EMAIL SUBMISSION ######################################

				####################### FULLNAME SUBMISSION ######################################
				if($existing_user['stage_position'] == 2){

					if(preg_match('/^[\pL\p{Mc} \'-]+$/u', $userInput)){
						$array=array('fullname'=>$userInput);

							####################### UPDATE USER FULLNAME AND SATGE POSITION FOR REGISTRATION ######################################
							$this->update_telegramUsers($array, $telegram_id);
							$ar = array('stage_position'=>3);
							$this->update_stage($ar, $telegram_id);
							####################### END OF UPDATE USER EMAIL AND SATGE POSITION FOR REGISTRATION ######################################

							####################### RESPONSE FOR ADDRESS ######################################
							$msg = "You're 3 steps to start sending funds to friends and family in Nigeria".chr(10)."".chr(10)."Step 3/5".chr(10).chr(10)."Kindly provide your full residential address 🏠 ";
							file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg));
							return; 
							####################### END OF RESPONSE FOR ADDRESS ###################################### 
						
						 
					}else{
						####################### BAN AT EMAIL SESSION ######################################
						$returnValue = $this->attempt_increment($userID);
						if($returnValue >= 5){
							$this->banTemporary($userID);
							file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
							$msg = 'Oooops!!'.chr(10).chr(10).'You are currently banned from chatting with me!'.chr(10).chr(10).'Your account will be unlock at '.date('Y-m-d h:i:s', $banned_expiration)." (GMT+2)";
							file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
							return;
						}else{
							file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
							$msg = 'Invalid name supplied!'.chr(10).'('.$returnValue.'/5) attempts'.chr(10).chr(10);
							 $msg .= "Kindly provide your valid fullname 🦒 ";
							 file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
						}
						####################### END OF BAN AT FULLNAME SESSION ######################################
					}
				}
				####################### END FULLNAME SUBMISSION ######################################

				####################### ADDRESS SUBMISSION ######################################
				if($existing_user['stage_position'] == 3){

					if(strlen($userInput) >= 20){
						$array=array('address'=>$userInput);

							####################### UPDATE USER ADDRESS AND SATGE POSITION FOR REGISTRATION ######################################
							$this->update_telegramUsers($array, $telegram_id);
							$ar = array('stage_position'=>4);
							$this->update_stage($ar, $telegram_id);
							####################### END OF UPDATE USER EMAIL AND SATGE POSITION FOR REGISTRATION ######################################

							####################### RESPONSE FOR CONTACT ######################################
							$keyboard =
										[[[
											'text' => 'Share my phone number',
											'request_contact' => true
										]]];
									$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
									$reply = json_encode($resp);

							$msg = "You're 2 steps to start sending funds to friends and family in Nigeria".chr(10)."".chr(10)."Step 4/5".chr(10).chr(10)."Kindly share your phone contant 📱 ";
							file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
							return;  
							####################### END OF RESPONSE FOR CONTACT ###################################### 
						
						 
					}else{
						####################### BAN AT ADDRESS SESSION ######################################
						$returnValue = $this->attempt_increment($userID);
						if($returnValue >= 5){
							$this->banTemporary($userID);
							file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
							$msg = 'Oooops!!'.chr(10).chr(10).'You are currently banned from chatting with me!'.chr(10).chr(10).'Your account will be unlock at '.date('Y-m-d h:i:s', $banned_expiration)." (GMT+2)";
							file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
							return;
						}else{
							file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
							$msg = 'Invalid descriptive address supplied!'.chr(10).'('.$returnValue.'/5) attempts'.chr(10).chr(10);
							 $msg .= "Kindly provide your full residential address 🏠 (<i>At least 20 character length</i>)";
							 file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
						}
						####################### END OF BAN AT FULLNAME SESSION ######################################
					}
				}
				####################### END ADDRESS SUBMISSION ######################################
			
				####################### CONTACT SUBMISSION ######################################
				if($existing_user['stage_position'] == 4){
					$contact = $updates->message->contact->phone_number;
					if(!empty($contact)) {
						$array=array('mobile'=>$contact);

							####################### UPDATE USER ADDRESS AND SATGE POSITION FOR REGISTRATION ######################################
							$this->update_telegramUsers($array, $telegram_id);
							$ar = array('stage_position'=>5);
							$this->update_stage($ar, $telegram_id);
							####################### END OF UPDATE USER EMAIL AND SATGE POSITION FOR REGISTRATION ######################################

							####################### RESPONSE FOR LOCATION ######################################
							$keyboard =
							[[[
								'text' => 'Share location',
								'request_location' => true
							]]];
									$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
									$reply = json_encode($resp);

							$msg = "You're on final step to start sending funds to friends and family in Nigeria".chr(10)."".chr(10)."Step 5/5".chr(10).chr(10)."Kindly share your location 🗺 ";
							file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
							return;  
							####################### END OF RESPONSE FOR LOCATION ###################################### 
						
						 
					}else{
						####################### BAN AT ADDRESS SESSION ######################################
						$returnValue = $this->attempt_increment($userID);
						if($returnValue >= 5){
							$this->banTemporary($userID);
							file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
							$msg = 'Oooops!!'.chr(10).chr(10).'You are currently banned from chatting with me!'.chr(10).chr(10).'Your account will be unlock at '.date('Y-m-d h:i:s', $banned_expiration)." (GMT+2)";
							file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
							return;
						}else{
							$keyboard =
										[[[
											'text' => 'Share my phone number',
											'request_contact' => true
										]]];
									$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
									$reply = json_encode($resp);
							file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
							$msg = 'Invalid phone contact supplied!'.chr(10).'('.$returnValue.'/5) attempts'.chr(10).chr(10);
							 $msg .= "Kindly share your phone contant 📱 ";
							 file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
						}
						####################### END OF BAN AT CONTACT SESSION ######################################
					}
				}
				####################### END CONTACT SUBMISSION ######################################

				####################### LOCATION SUBMISSION ######################################
				if($existing_user['stage_position'] == 5){
						$long = $updates->message->location->longitude;
						$lat = $updates->message->location->latitude;
						if(!empty($long) && !empty($lat)) {
							$array=array('location'=>($long.':'.$lat));
	
								####################### UPDATE USER ADDRESS AND SATGE POSITION FOR REGISTRATION ######################################
								$this->update_telegramUsers($array, $telegram_id);
								$ar = array('stage'=>'Registered','stage_position'=>0);
							    $this->update_stage($ar, $telegram_id);
								####################### END OF UPDATE USER EMAIL AND SATGE POSITION FOR REGISTRATION ######################################
	
								####################### RESPONSE FOR DASHBOARD ######################################

								$keyboard = array(
									["Transfer Fund"], ["Transfer Status"], ["Support"]
								);
								$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
								$reply = json_encode($resp);
							
								file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
								$msg = 'Congrats 🎉 '.chr(10).chr(10).'Welcome to your Dashboard'.chr(10).chr(10).'Select one of the options to proceed';
								file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
								####################### END OF RESPONSE FOR DASHBOARD ###################################### 
							
							 
						}else{
							####################### BAN AT ADDRESS SESSION ######################################
							$returnValue = $this->attempt_increment($userID);
							if($returnValue >= 5){
								$this->banTemporary($userID);
								file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
								$msg = 'Oooops!!'.chr(10).chr(10).'You are currently banned from chatting with me!'.chr(10).chr(10).'Your account will be unlock at '.date('Y-m-d h:i:s', $banned_expiration)." (GMT+2)";
								file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
								return;
							}else{
								$keyboard =
								[[[
									'text' => 'Share location',
									'request_location' => true
								]]];
								$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
								$reply = json_encode($resp);
								file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
								$msg = 'Invalid phone contact supplied!'.chr(10).'('.$returnValue.'/5) attempts'.chr(10).chr(10);
								 $msg .= "Kindly share your location 🗺 ";
								 file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
							}
							####################### END OF BAN AT CONTACT SESSION ######################################
						}
					}
				####################### END LOCATION SUBMISSION ######################################

			}

			if($existing_user['stage'] == 'Registered'){

				if($userInput == "Transfer Fund"){
					$get_rate = $this->getRate();
					$ar = array('stage'=>'Transfer Fund','stage_position'=>0);
					$this->update_stage($ar, $telegram_id);
					$msg="We make direct deposit into recipient local banks and mobile money. We supports all major banks and payment merchants in Nigeria at minimal cost".chr(10).chr(10)."HOW IT WORKS:".chr(10).chr(10)."1).  Send dollars (USDT/ BUSD) to our unique generated wallet address.".chr(10)."2). Provide name, phone, emails and account details of recipient.".chr(10)."3). On receiving the money in our wallet, sender and recipients receives SMS/email notification.".chr(10)."4). Within 24hrs the fund is disbursed. Sender receives disbursed Email/SMS notification.".chr(10).chr(10)."OUR CHARGES:".chr(10).chr(10)."All our Charges are transparent.".chr(10).chr(10)."$100 - $499.99 == $2.5".chr(10)."$500 - $5000 == 0.75%".chr(10).chr(10)."RULES:".chr(10).chr(10)."$5,000 Transaction per day".chr(10).chr(10)."OUR EXCHNANGE RATE AS AT NOW::".chr(10)."1$ == ₦".number_format($get_rate['value'],2);

					$keyboard = array(
						["Agree"], ["Disagree"]
					);
								$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
								$reply = json_encode($resp);
								file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
								file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
								return;

				}
				elseif($userInput == "Transfer Status"){
					
					$ar = array('stage'=>'Transfer Status','stage_position'=>0);
							    $this->update_stage($ar, $telegram_id);
					file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
					$msg="Track your transactions".chr(10).chr(10)."Type the <em>TRANSACTION CODE</em>";
					file_get_contents($path."/sendmessage?chat_id=$chat_id&parse_mode=html&text=".urlencode($msg) ."&reply_markup=".$reply);
					return;

				}
				elseif($userInput == "Support"){
					
					file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
					$msg="Our supports and Customer Care are on ground to attend to your questions".chr(10).chr(10)."Kindly click https://t.me/Gbemitey to chat supports team";
					file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
					return;
					
				}else{

					$returnValue = $this->attempt_increment($userID);
						if($returnValue >= 5){
							$this->banTemporary($userID);
							file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
							$msg = 'Oooops!!'.chr(10).chr(10).'You are currently banned from chatting with me!'.chr(10).chr(10).'Your account will be unlock at '.date('Y-m-d h:i:s', $banned_expiration)." (GMT+2)";
							file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
							return;
						}else{
							file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
							$msg = 'Option Picked does not exist!'.chr(10).chr(10).'('.$returnValue.'/5) attempts'.chr(10).chr(10);
							 $msg .= "Click /START to go to dashboard";
							 file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
							 return;
						}

				}
			}

			if($existing_user['stage'] == 'Transfer Fund'){

				if($userInput == "Agree"){

					$ar = array('stage_position'=>1);
					$this->update_stage($ar, $telegram_id);
					$balance = $this->dailySum($telegram_id);
					$spending = number_format(5000,2);
					if(!empty($balance)){
						$spending = number_format((5000 - $balance['sum_amount']), 2);
					}
					
					$msg="You have transfer limit of ${$spending} for today.".chr(10).chr(10)."Kindly select or enter amount to send 💰 ".chr(10).chr(10)."(Maximum amount is $5,000)";
					$keyboard = array(
						["100"], ["200"],["500"],['1000'],['2000'],['3000'],['5000']
					);
								$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
								$reply = json_encode($resp);
								file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
								file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
								return;
				}

				if($userInput == "Disagree"){
					$ar = array('stage'=>'Registered','stage_position'=>0);
					$this->update_stage($ar, $telegram_id);
					file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
							$msg = 'Request for transfer has been declined by you!'.chr(10).chr(10);
							 $msg .= "Click /START to go to Dashboard";
							 file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
							 return;

				}

				if($existing_user['stage_position'] == 1){

					$digital_value = preg_replace("/[^0-9\.]/", '', $userInput);
					$digital_value = number_format((float)$digital_value,2);
					if($digital_value < 500){
						$charges = 2.5;
					}else{
						$charges = (0.75/100) * $digital_value;
					}
					if($digital_value >= 100 && $digital_value <= 5000){

						$time = time().$telegram_id;
						$code = sha1($time);
						$array= array('telegram_id'=>$telegram_id, 'amount'=>$digital_value, 'charges'=>$charges, 'transaction_code'=>strtoupper($code), 'created_at'=>date('Y-m-d h:i:s'));
						$transaction_id = $this->insertTransactions($array);
						$ar = array('stage_position'=>2, 'last_transaction_id'=>$transaction_id);
					    $this->update_stage($ar, $telegram_id);
						$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
						$msg="You entered $".$digital_value."".chr(10).chr(10)."Kindly provide <b>recipients'</b> fullname 📛 ";
						file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
						return;
					}else{
						file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
							$msg = 'Amount out of our transfer range/options!'.chr(10).chr(10);
							 $msg .= "Kindly select or enter amount to send 💰 ".chr(10).chr(10)."(Maximum amount is $5,000)";
							 $keyboard = array(
								["100"], ["200"],["500"],['1000'],['2000'],['3000'],['5000']
							);
										$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
										$reply = json_encode($resp);
										file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
										file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
										return;
					}

				}

				if($existing_user['stage_position'] == 2){

					if(preg_match('/^[\pL\p{Mc} \'-]+$/u', $userInput)){
						$array=array('recipient_name'=>$userInput);

							####################### UPDATE USER FULLNAME AND SATGE POSITION FOR REGISTRATION ######################################
							$this->update_telegramTransactions($array, $existing_user['last_transaction_id']);
							$ar = array('stage_position'=>3);
							$this->update_stage($ar, $telegram_id);
							####################### END OF UPDATE USER EMAIL AND SATGE POSITION FOR REGISTRATION ######################################

							####################### RESPONSE FOR ADDRESS ######################################
							file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
							$msg = "Kindly provide recipients' mobile number 📱  <i>(11 digits number)</i>";
							file_get_contents($path."/sendmessage?chat_id=$chat_id&parse_mode=html&text=".urlencode($msg));
							return; 
							####################### END OF RESPONSE FOR ADDRESS ###################################### 
						
						 
					}else{
						####################### BAN AT EMAIL SESSION ######################################
						
							file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
							$msg = 'Invalid name supplied!'.chr(10).chr(10);
							 $msg .= "Kindly provide recipients' valid fullname 🦒 ";
							 file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
						
						####################### END OF BAN AT FULLNAME SESSION ######################################
					}
				}

				if($existing_user['stage_position'] == 3){

					if (preg_match("/^[0-9]+$/", $userInput) && preg_match("/^[0]{1}[1-9]{1}[0-9]{9}$/", $userInput) && strlen($userInput) == 11) {

						$array=array('recipient_mobile'=>$userInput);

							####################### UPDATE USER FULLNAME AND SATGE POSITION FOR REGISTRATION ######################################
							$this->update_telegramTransactions($array, $existing_user['last_transaction_id']);
							$ar = array('stage_position'=>4);
							$this->update_stage($ar, $telegram_id);

							 $msg = "Kindly select a local bank from options 🏦 ";
							 $keyboard = array();
							$bnk = $this->getBanks();
							foreach($bnk as $b){
								$keyboard[]=[$b['name']];
							}
										$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
										$reply = json_encode($resp);
										file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
										file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
										return;
					}else{

						##################### ERRROR ########################
						
							file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
							$msg = 'Invalid mobile phone supplied!'.chr(10).chr(10);
							 $msg .= "Kindly provide valid recipients' mobile number 📱  <i>(11 digits number)</i>";
							 file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
						
					}

				}

			if($existing_user['stage_position'] == 4){

				$get_bank_code = $this->getBankCode($userInput);

				if (!empty($get_bank_code)) {

					$array=array('recipient_bank'=>$get_bank_code['name'], 'bank_code'=>$get_bank_code['code']);

						####################### UPDATE USER FULLNAME AND SATGE POSITION FOR REGISTRATION ######################################
						$this->update_telegramTransactions($array, $existing_user['last_transaction_id']);
						$ar = array('stage_position'=>5);
						$this->update_stage($ar, $telegram_id);

						 file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
							$msg = "Kindly provide recipients' nuban/account number 💳 ";
							file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg));
							return; 
						
				}else{

					##################### ERRROR ########################
						file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
						$msg = 'Invalid selected bank option!'.chr(10).chr(10);
						 $msg .= "Kindly select a local bank from options below 🏦 ";
						 $keyboard = array();
						 $bnk = $this->getBanks();
						 foreach($bnk as $b){
							 $keyboard[]=[$b['name']];
						 }
									 $resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
									 $reply = json_encode($resp);
									 file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
									 file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
									 return;
				}

			}

			if($existing_user['stage_position'] == 5){

				$transaction_details = $this->getTransactions($existing_user['last_transaction_id']);

				$link='https://app.nuban.com.ng/api/NUBAN-IVASLCIQ763?acc_no='.$userInput;

				$check_bank_details=$this->verifyAccount2($link);
                      
                        if ($check_bank_details == 0) {
                           
							$link='https://api.paystack.co/bank/resolve?account_number='.$userInput.'&bank_code='.$transaction_details['bank_code'];
							$check_bank_details=$this->verifyBank($link);

                        }
					
				if($check_bank_details != 0){
					$account_name=$check_bank_details['account_name'];
					$account_no= $check_bank_details['account_number'];
					$get_rate = $this->getRate();
					
					$array=array('recipient_account_no'=>$userInput);

						####################### UPDATE USER FULLNAME AND SATGE POSITION FOR REGISTRATION ######################################
						$this->update_telegramTransactions($array, $existing_user['last_transaction_id']);
						$ar = array('stage_position'=> 6);
						$this->update_stage($ar, $telegram_id);
						####################### END OF UPDATE USER EMAIL AND SATGE POSITION FOR REGISTRATION ######################################

						####################### RESPONSE FOR ADDRESS ######################################

						$keyboard = array(
							["Agree"], ["Disagree"]
						);
									$resp = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
									$reply = json_encode($resp);
								
						file_get_contents($path.'/sendChatAction?chat_id='.$chat_id.'&action=typing');
						$msg = 'ACCOUNT VERIFICATION::'.chr(10).chr(10).'Account Holder:: '.$account_name.chr(10).chr(10).'Account Number:: '.$account_no.chr(10).chr(10).chr(10).'TRANSACTION DETAILS:: '.chr(10).chr(10).'Recipient Name:: '.$transaction_details['recipient_name'].chr(10).chr(10).'Recipient Mobile:: '.$transaction_details['recipient_mobile'].chr(10).chr(10).'Recipient Bank:: '.$transaction_details['recipient_bank'].chr(10).chr(10).'Transfer Amount:: $'.$transaction_details['amount'].chr(10).chr(10).'Transfer Charges:: $'.$transaction_details['charges'].chr(10).chr(10).'Total Payment:: $'.number_format(($transaction_details['charges'] + $transaction_details['amount']), 2).chr(10).chr(10).'Dollar => Naira:: ₦'.number_format($get_rate['value'], 2).chr(10).chr(10).'Recipent Receives:: ₦'.number_format(($get_rate['value'] * ($transaction_details['charges'] + $transaction_details['amount'])), 2) ;
						file_get_contents($path."/sendmessage?chat_id=$chat_id&text=".urlencode($msg) ."&reply_markup=".$reply);
						return; 
						####################### END OF RESPONSE FOR ADDRESS ######################################  
				}else{
					####################### BAN AT EMAIL SESSION ######################################
					
						file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
						$msg = 'Account Verification failed!'.chr(10).chr(10);
						 $msg .= "Kindly provide recipients' nuban/account number 💳 ";
						 file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
						 return;
					
					####################### END OF BAN AT FULLNAME SESSION ######################################
				}
			}

			if($existing_user['stage_position'] == 6){

				if($userInput == "Agree"){

				$transaction_details = $this->getTransactions($existing_user['last_transaction_id']);

				$array=array('ongoing_status'=>1);
						$this->update_telegramTransactions($array, $existing_user['last_transaction_id']);

				$msg = 'Kindly deposit the sum of '.number_format(($transaction_details['chrges'] + $transaction_details['amount']), 2).'USDT asset into the wallet address below:'.chr(10).chr(10).'<code>Wallet Address:: 0x9d8290f731D38CF2feF112Ac74F1dDa69509cf18</code>'.chr(10).chr(10).chr(10).'On successful transfer of fund to KwikExchage wallet, Kindly provide TRANSACTIONID of the transfer as proof of fund on Transfer Status Menu'.chr(10).chr(10).chr(10).'Your KwikExchange Transaction code is <code>'.$transaction_details['transaction_code'].'</code> Copy it!!'.chr(10).chr(10).chr(10).'Click /START to go to Dashboard';
				$url    = "https://wesabi.com/api/assets/images/wallet.png";
				file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
				file_get_contents($path."/sendphoto?chat_id=$chat_id&photo=".$url."&caption=".urlencode($msg)."&parse_mode=HTML");
				return;
				}

				if($userInput == "Disagree"){
					$ar = array('stage'=>'Registered','stage_position'=>0);
					$this->update_stage($ar, $telegram_id);
					file_get_contents($path.'/sendChatAction?chat_id='.$updates->message->chat->id.'&action=typing');
							$msg = 'Request for transfer has been declined by you!'.chr(10).chr(10);
							 $msg .= "Click /START to go to Dashboard";
							 file_get_contents($path.'/sendmessage?chat_id='.$chat_id.'&parse_mode=html&text='.urlencode($msg));
							 return;

				}
				

			}

			}


		   }

	}
	
	private function isUserNew($id){
		$row = $this->db->query("SELECT * FROM telegrams where telegram_id='".$id."'")->row_array();
		return $row;
	}

	private function userExist($id){
		$row = $this->db->query("SELECT * FROM telegram_users where email='".$id."' OR mobile='".$id."'")->row_array();
		return $row;
	}

	private function getBanks(){
		$row = $this->db->query("SELECT A.name FROM telegram_banks as A")->result_array();
		return $row;
	}

	private function getRate(){
		$row = $this->db->query("SELECT A.value FROM telegram_rate as A")->row_array();
		return $row;
	}

	private function getBankCode($name){
		$row = $this->db->query("SELECT * FROM telegram_banks where name='".$name."'")->row_array();
		return $row;
	}
	
	private function insertNew($data){
		$this->db->insert('telegrams', $data); 
        return $this->db->insert_id();
       
	}

	private function insertUsers($data){
		$this->db->insert('telegram_users', $data); 
        if($this->db->affected_rows() > 0){
            return true;
        }else{
            return false;
        }
	}

	private function insertTransactions($data){
		$this->db->insert('telegram_transactions', $data); 
        return $this->db->insert_id();
	}

	private function getTransactions($id){
		$row = $this->db->query("SELECT * FROM telegram_transactions where id='".$id."'")->row_array();
		return $row;
	}

	private function dailySum($id){
		$row = $this->db->query("SELECT SUM(amount) as sum_amount FROM telegram_transactions where telegram_id='".$id."' AND (ongoing_status=1 OR payment_status=1) AND created_at > now() - interval 23 hour")->row_array();
		return $row;
	}

	private function unbannedTemporary($id){
		$this->db->query("UPDATE telegrams SET is_ban =0,warning = 0 where id = '".$id."'");
	}

	private function banTemporary($id){
		$banned_expiration = Date("Y-m-d H:i:s",strtotime("+ 10mins"));
		$time = strtotime($banned_expiration);
		$this->db->query("UPDATE telegrams SET is_ban =1,warning = 0, ban_expiration='".$time."' where id = '".$id."'");
	}

	private function update_stage($array, $id){
		$this->db->where('telegram_id', $id);
        $this->db->update('telegrams', $array);
	}

	private function update_telegramUsers($array, $id){
		$this->db->where('telegram_id', $id);
        $this->db->update('telegram_users', $array);
	}

	private function update_telegramTransactions($array, $id){
		$this->db->where('id', $id);
        $this->db->update('telegram_transactions', $array);
	}

	private function attempt_increment($id){
		$this->db->query("UPDATE telegrams SET warning = (warning + 1) where id = '".$id."'");
		$get_attempt = $this->db->query("select warning from telegrams where id = '".$id."'")->row_array();
		return $get_attempt['warning'];
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

	private function verifyBank($link){
		
		$curl = curl_init();
  
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $link,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_SSL_VERIFYHOST=> 0,
        CURLOPT_SSL_VERIFYPEER=> 0,
		  CURLOPT_HTTPHEADER => array(
			"Authorization: Bearer sk_live_434b22c585b33f3500ab2a38c20c24e0f4327e38",
			"Cache-Control: no-cache",
		  ),
		));
		
		$response = curl_exec($curl);
        curl_close($curl);
        $responseArray=json_decode($response, true); 
        if($responseArray['status'] == false){
             return 0;
        }else{
            return $responseArray['data'];
        }
	}

	private function verifyAccount2($link){
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $link,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_SSL_VERIFYHOST=> 0,
        CURLOPT_SSL_VERIFYPEER=> 0,
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $responseArray=json_decode($response, true); 
        if($responseArray['error']){
             return 0;
        }else{
            return $responseArray[0];
        }
    }
}
