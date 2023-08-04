function validateEnquiryForm() {
	var valid = true;
	var e = EnquiryFormValidator;
	for (var el in e) {
		var elItem = document.getElementById('Form_EnquiryForm_' + el);
		elItem.className = elItem.className.replace(/\binvalid\b/, '');
		if (e[el] == 'Email') { // Email
			var elValue = elItem.value.replace(/^\s+|\s+$/g, ''); // trim
			validRegExp = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			if (elValue == '' || !validRegExp.test(elValue)) {
				elValue = false;
			}
		}
		else if (e[el] == 'Text') { // Input / TextArea
			var elValue = elItem.value.replace(/^\s+|\s+$/g, ''); // trim
			if (elValue == '') {
				elValue = false;
			}
		}
		else if (e[el] == 'Select') { // Select
			var elValue = elItem.options[elItem.selectedIndex].value;
			if (elValue == '') {
				elValue = false;
			}
		}
		else if (e[el] == 'Radio') { // Radio
			var elValue = false;
			var rdios = document.getElementsByName(el);
			for (var i = 0; i < rdios.length; i++) {
				if (rdios[i].checked == true) {
					elValue = true;
				}
			}
		}
		if (elValue === false) {
			valid = false;
			elItem.className = elItem.className + " invalid";
		}
	}
	if (valid === false) {
		alert('Please make sure all the required fields are filled in correctly.');
		return false;
	}
	document.getElementById('Form_EnquiryForm_action_SendEnquiryForm').disabled = true;
	return true;
}

function initEnquiryFormValidator() {
	if (EnquiryFormValidator && document.getElementById('Form_EnquiryForm')) {
		var f = document.getElementById('Form_EnquiryForm');
		f.onsubmit = validateEnquiryForm;
		if (f.querySelector && f.querySelector('.message.required') && f.scrollIntoView) {
			f.scrollIntoView();
		}
	}
}

function addEnquiryFormLoadEvent(func) {
	var origOnload = window.onload;
	if (typeof window.onload != 'function') {
		window.onload = func;
	} else {
		window.onload = function () {
			origOnload();
			func();
		}
	}

}

addEnquiryFormLoadEvent(initEnquiryFormValidator);
