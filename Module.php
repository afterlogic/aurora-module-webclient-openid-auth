<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\OpenIDAuthWebclient;

require('classes/class.openid.v3.php');


/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractWebclientModule
{
	protected $sService = 'openID';
	
	/**
	 * Initializes FacebookAuthWebclient Module.
	 * 
	 * @ignore
	 */
	public function init()
	{
		$this->AddEntry('openid.login', 'onOpenidLoginEntry');
		$this->AddEntry('openid.mode', 'onOpenidModeEntry');
		$this->includeTemplate('StandardLoginFormWebclient_LoginView', 'Login-After', 'templates/SignInButtonsView.html', self::GetName());
	}
	
	/**
	 * Passes data to connect to service.
	 * 
	 * @ignore
	 */
	public function onOpenidLoginEntry()
	{
		$sOpenIdUrl = $this->getConfig('OpenIdServer', '');

		$openid = new \SimpleOpenID();
		$openid->SetIdentity($sOpenIdUrl);
		$openid->SetTrustRoot($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER["HTTP_HOST"]);

		$openid->SetRequiredFields(array('email','fullname'));
		$openid->SetOptionalFields(array('dob','gender','postcode','country','language','timezone'));
		if ($openid->GetOpenIDServer())
		{
			$openid->SetApprovedURL($_SERVER["HTTP_REFERER"]);  	// Send Response from OpenID server to this script
			$openid->Redirect(); 	// This will redirect user to OpenID Server
		}
		else
		{
			// ON THE WAY, WE GOT SOME ERROR
			$error = $openid->GetError();
			echo "ERROR CODE: " . $error['code'] . "<br>";
			echo "ERROR DESCRIPTION: " . $error['description'] . "<br>";
		}
	}

	/**
	 *
	 * @return void
	 */
	public function onOpenidModeEntry()
	{
		$sOpenidMode = $_GET['openid_mode'];

		if ($sOpenidMode === 'id_res')
		{
			$openid = new \SimpleOpenID();
			$sOpenidIdentity = $this->getConfig('OpenIdServer', '');
			$openid->SetIdentity($sOpenidIdentity);
			$openid_validation_result = $openid->ValidateWithServer();
			if ($openid_validation_result == true)
			{ 		
				// OK HERE KEY IS VALID
				if (isset($_GET['openid_sreg_email']))
				{
					$sEmail = $_GET['openid_sreg_email'];
					$sPassword = $this->getConfig('MasterPassword', '');

					if (empty($sPassword))
					{
						$oUser = \Aurora\Modules\Core\Module::Decorator()->GetUserByPublicId($sEmail);
						if (isset($oUser))
						{
							$oAccount = \Aurora\Modules\Mail\Module::getInstance()->getAccountsManager()->getAccountByEmail($sEmail);
							if ($oAccount)
							{
								$sPassword = $oAccount->GetPassword();
							}
						}
					}

					$aResult = \Aurora\Modules\Core\Module::Decorator()->Login($sEmail, $sPassword);

					if (is_array($aResult) && isset($aResult['AuthToken']))
					{
						@\setcookie(
							\Aurora\System\Application::AUTH_TOKEN_KEY, 
							$aResult['AuthToken'], 
							\strtotime('+30 days'), 
							\Aurora\System\Api::getCookiePath()
						);
					}

		
					\Aurora\System\Api::Location('./');					
				}

			}
			else if($openid->IsError() == true)
			{			
				// ON THE WAY, WE GOT SOME ERROR
				$error = $openid->GetError();
				echo "ERROR CODE: " . $error['code'] . "<br>";
				echo "ERROR DESCRIPTION: " . $error['description'] . "<br>";
			}
		}
		else if ($sOpenidMode === 'cancel')
		{ 
			// User Canceled your Request
			echo "USER CANCELED REQUEST";
		}		

	}
}
