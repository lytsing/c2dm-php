<?php 
/**
 *
 * @package c2dm
 * @version $Id$
 * @copyright (c) 2011 lytsing.org & 2012 thebub.net
 * Description: C2DM implementation PHP code
 * refer to: http://stackoverflow.com/questions/4121508/c2dm-implementation-php-code
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

class c2dm {
	
	private static $instance = null;
	
	
	private function __construct() {
	}
	
	public static function getInstance() {
		if(!c2dm::$instance) {
			c2dm::$instance = new c2dm();
		}
		
		return c2dm::$instance;
	}
	
	private $authString = "";
	
	/**
	 * Get Google login auth token
	 * @see http://code.google.com/apis/accounts/docs/AuthForInstalledApps.html
	 */
	public function getAuthToken($username, $password, $source, $account_type) {

		if (empty($username) || empty($password)) {
			throw new Exception("Username and/or password not set.");
		}

		// Initialize the curl object
		$curl = curl_init();
		if (!$curl) {
			throw new Exception("cURL could not be initialized.");
		}

		curl_setopt($curl, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		$data = array(
			'Email'         => $username,
			'Passwd'        => $password,
			'accountType'   => $account_type,
			'source'        => $source,
			'service'       => 'ac2dm');

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

		$response_data = curl_exec($curl);
		$response_info = curl_getinfo($curl);
		
		curl_close($curl);

		if ($response_info['http_code'] == 403) {
			return false;
		}
		
		// Filter Auth-Token from within the response
		if(preg_match("/Auth=([a-z0-9_\-]+)/i", $response_data, $matches) == 1) {
			//Store Auth-Token
			$this->authString = $matches[1];
			
			return true;
		}

		return false;
	}

	/**
	 * Send C2DM message to specified device
	 * @see https://developers.google.com/android/c2dm/#server
	 */
	public function sendMessage($deviceRegistrationId, $message, $msgType) {
		
		// Check whether service is allready authenticated
		if(strlen($this->authString) == 0) {
			throw new Exception("Not authenticated");
		}
		
		$headers[] = 'Authorization: GoogleLogin auth='.$this->authString;
		
		$data = array(
			'registration_id' => $deviceRegistrationId,
			'collapse_key'    => $msgType,
			'data.message'    => $message
		);

		$curl = curl_init();
		  
		curl_setopt($curl, CURLOPT_URL, "https://android.apis.google.com/c2dm/send");
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

		$response_data = curl_exec($curl);
		$response_info = curl_getinfo($curl);
		
		curl_close($curl);
		
		if ($response_info['http_code'] == 200) {
			// Filter message-id or error from response
			if(preg_match("/id=([a-z0-9_\-]+)/i", $response_data, $matches) == 1) {
				
				return $matches[1];
				
			} else if(preg_match("/Error=([a-z0-9_\-]+)/i", $response_data, $matches) == 1) {
				
				throw new Exception($matches[1]);
				
			}
		} else if ($response_info['http_code'] == 401) {
			
			throw new Exception("Not authenticated");
			
		} else if ($response_info['http_code'] == 503) {
			
			throw new Exception("Service Unavailable");
			
		}

		return false;
	}
}
