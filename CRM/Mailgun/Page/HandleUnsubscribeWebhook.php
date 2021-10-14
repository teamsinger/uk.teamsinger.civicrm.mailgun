<?php

class CRM_Mailgun_Page_HandleUnsubscribeWebhook extends CRM_Core_Page
{
	function run()
	{

		// Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
		CRM_Utils_System::setTitle(ts('HandleUnsubscribeWebhook'));

		// Example: Assign a variable for use in a template
		$this->assign('currentTime', date('Y-m-d H:i:s'));

		static $store = null;

		$timestamp = CRM_Utils_Request::retrieve('timestamp', 'String', $store, false, null, 'POST');
		$token = CRM_Utils_Request::retrieve('token', 'String', $store, false, null, 'POST');
		$signature = CRM_Utils_Request::retrieve('signature', 'String', $store, false, null, 'POST');

		CRM_Mailgun_Utils::checkSignature($timestamp, $token, $signature);

		$recipient = CRM_Utils_Request::retrieve('recipient', 'String', $store, false, null, 'POST');
		$reason = CRM_Utils_Request::retrieve('event', 'String', $store, false, null, 'POST');

		try {

			// get old contact -- necessary to prevent "Ambiguous match criteria" on create
			$origcontact = civicrm_api3('Contact', 'get', [
				'email' => $recipient,
				'match' => [
					'email',
				],
			]);
			if (!empty($origcontact['is_error']))
				throw new Exception($origcontact['error_message']);
			//~ print_r($origcontact); exit;

			// noone to unsubscribe if noone exists
			$contact_ids =[];
			$optouts =[];
			if ($origcontact['count'] > 0) {
				foreach ($origcontact['values'] as $val) {
					//~ print_r($val);
					$optouts[] = civicrm_api3('Contact', 'create', [
						'contact_id' => $val['contact_id'],
						'is_opt_out' => '1',
						'match' => [
							'contact_id',
						],
					]);
				}
				//~ print_r($optouts); exit;
			}
		} catch (Exception $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			return;
		}

		$query_params = [
			1 => [$recipient, 'String'],
			2 => [json_encode($optouts), 'String'],
			3 => [json_encode($_POST), 'String'],
			4 => [$reason, 'String'],
		];

		//~ var_dump($query_params); exit;

		CRM_Core_DAO::executeQuery("
			INSERT INTO mailgun_events
      	(recipient, email, post_data, reason)
			VALUES
				(%1, %2, %3, %4)
		", $query_params);

		echo json_encode([
			'type' => 'unsubscribe',
			'msg' => 'Post received',
		]);

		CRM_Utils_System::civiExit();
		//~ parent::run();
	}
}
