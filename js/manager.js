'use strict';

module.exports = function (oAppData) {
	var
		_ = require('underscore'),
		$ = require('jquery'),
		ko = require('knockout'),
		
		App = require('%PathToCoreWebclientModule%/js/App.js'),
		
		bAnonymUser = App.getUserRole() === Enums.UserRole.Anonymous
	;

	if (bAnonymUser)
	{
		return {
			start: function (ModulesManager) {
				
				var fInitialize = function (oParams) {
					if ('CLoginView' === oParams.Name)
					{
						oParams.View.authOpenIdClick = function () {
							$.cookie('openid-redirect', 'login');

							window.location.href = '?openid.login';
						};
					}
				};
				
				App.subscribeEvent('StandardLoginFormWebclient::ConstructView::after', fInitialize);
			}
		};
	}
	
	return null;
};
