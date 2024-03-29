define(['mage/url', 'Magento_Ui/js/model/messageList'], function (url, globalMessageList) {'use strict'; return {
	process: function (response, messageContainer) {
		messageContainer = messageContainer || globalMessageList;
		if (response.status == 401) {
			window.location.replace(url.build('customer/account/login/'));
		}
		else {
			var error = JSON.parse(response.responseText);
			alert(error.message);
			messageContainer.addErrorMessage(error);
		}
	}
};});