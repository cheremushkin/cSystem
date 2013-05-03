Utils.ajax.classes.control = {
	// authorization in control panel
	login: function(form) {
		Utils.ajax.query({
			url: '/ajax.php',
			data: {
				class: 'control',
				method: 'login',
				captcha: form.captcha && form.captcha.value ? form.captcha.value : null,
				email: form.email && form.email.value ? form.email.value : null,
				password: form.password && form.password.value ? form.password.value : null,
				backdoor: form.backdoor && form.backdoor.value ? form.backdoor.value : null
			},
			method: 'POST',
			timeout: 3000,
			success: function(response) {
				if (response.code == 200) window.location.reload(true);
                else {
                    Utils.captcha.refresh();
                    Utils.hints.open(response.message, 'fail');
                }
			},
			error: function(type) {
				switch (type) {
					case 'timeout':
						Utils.hints.open('Время выполнения запроса истекло, попробуйте позднее.', 'fail');
						break;
						
					case 'error':
						Utils.hints.open('Во время запроса произошла ошибка, попробуйте позднее.', 'fail');
						break;
				};
			}
		});
	},
	
	// log out from control panel
	logout: function() {
		Utils.ajax.query({
			url: '/ajax.php',
			data: {
				class: 'control',
				method: 'logout'
			},
			method: 'POST',
			timeout: 3000,
			success: function(response) {
				response.code == 200 ? window.location.reload(true) : Utils.hints.open(response.message, 'fail');
			},
			error: function(type) {
				switch (type) {
					case 'timeout':
						Utils.hints.open('Время выполнения запроса истекло, попробуйте позднее.', 'fail');
						break;
						
					case 'error':
						Utils.hints.open('Во время запроса произошла ошибка, попробуйте позднее.', 'fail');
						break;
				};
			}
		});
	},
	
	// delete a file/directory (realy it moves into basket)
	delete: function(path, number) {
		wrapper = document.getElementById('files-line-' + number);
		field = document.getElementById('files-line-' + number + '-field-2');
		
		Utils.ajax.query({
			url: '/ajax.php',
			data: {
				class: 'control',
				method: 'ajax',
				load: {
					class: 'Files',
					method: 'delete'
				},
				path: path
			},
			method: 'POST',
			timeout: 3000,
			success: function(response) {
				if (response.success) {
					Utils.animation.make({
						object: wrapper,
						styles: [{
							name: 'opacity',
							from: 1,
							to: 0
						}],
						
						properties: [{
							duration: 200,
							callback: function() {
								field.innerHTML = response.image;
								
								Utils.animation.make({
									object: wrapper,
									styles: [{
										name: 'opacity',
										from: 0,
										to: 1
									}],
									
									properties: [{
										duration: 200
									}]
								});
							}
						}]
					});
				} else {
					Utils.hints.open(response.text, 'fail');
				};
			},
			error: function(type) {
				switch (type) {
					case 'timeout':
						Utils.hints.open('Время выполнения запроса истекло, попробуйте позднее.', 'fail');
						break;
						
					case 'error':
						Utils.hints.open('Во время запроса произошла ошибка, попробуйте позднее.', 'fail');
						break;
				};
			}
		});
	},
	
	// change directory
	cd: function(path, wrapper) {
		wrapper = document.getElementById(wrapper);
		
		Utils.ajax.query({
			url: '/ajax.php',
			data: {
				class: 'control',
				method: 'ajax',
				load: {
					class: 'Files',
					method: 'cd'
				},
				path: path
			},
			method: 'POST',
			timeout: 3000,
			success: function(response) {
				if (response.success) {
					Utils.animation.make({
						object: wrapper,
						styles: [{
							name: 'opacity',
							from: 1,
							to: 0
						}],
						
						properties: [{
							duration: 200,
							callback: function() {
								wrapper.innerHTML = response.template;
								
								Utils.animation.make({
									object: wrapper,
									styles: [{
										name: 'opacity',
										from: 0,
										to: 1
									}],
									
									properties: [{
										duration: 200
									}]
								});
							}
						}]
					});
				} else {
					Utils.hints.open(response.text, 'fail');
				};
			},
			error: function(type) {
				switch (type) {
					case 'timeout':
						Utils.hints.open('Время выполнения запроса истекло, попробуйте позднее.', 'fail');
						break;
						
					case 'error':
						Utils.hints.open('Во время запроса произошла ошибка, попробуйте позднее.', 'fail');
						break;
				};
			}
		});
	},
	
	// get template with source code and information about file
	getEditTemplate: function(path, section, content, mode) {
		section = document.getElementById(section);
		content = document.getElementById(content);
		
		Utils.ajax.query({
			url: '/ajax.php',
			data: {
				class: 'control',
				method: 'ajax',
				load: {
					class: 'Files',
					method: 'getEditTemplate'
				},
				path: path
			},
			method: 'POST',
			timeout: 3000,
			success: function(response) {
				if (response.success) {
					Utils.animation.make({
						object: section,
						styles: [{
							name: 'opacity',
							from: 1,
							to: 0
						}],
						
						properties: [{
							duration: 200,
							callback: function() {
								content.innerHTML = response.template;
								
								// ACE settings ----
								var editor = ace.edit('ace-editor');
								editor.setTheme("ace/theme/tomorrow");
								mode && editor.getSession().setMode("ace/mode/" + mode);
								editor.getSession().setUseWrapMode(true);
								// -----------------
								
								Utils.animation.make({
									object: section,
									styles: [{
										name: 'opacity',
										from: 0,
										to: 1
									}],
									
									properties: [{
										duration: 200
									}]
								});
							}
						}]
					});
				} else {
					Utils.hints.open(response.text, 'fail');
				};
			},
			error: function(type) {
				switch (type) {
					case 'timeout':
						Utils.hints.open('Время выполнения запроса истекло, попробуйте позднее.', 'fail');
						break;
						
					case 'error':
						Utils.hints.open('Во время запроса произошла ошибка, попробуйте позднее.', 'fail');
						break;
				};
			}
		});
	},
	
	// save source code
	saveSourceCode: function(form, path) {
		// change button to disabled
		button = form.getElementsByTagName('button')[0];
		button.disabled = true;
		
		
		// get source from ACE
		var editor = ace.edit('ace-editor');
		var source = editor.getValue();
		
		Utils.ajax.query({
			url: '/ajax.php',
			data: {
				class: 'control',
				method: 'ajax',
				load: {
					class: 'Files',
					method: 'saveSourceCode'
				},
				path: path,
				source: source
			},
			method: 'POST',
			timeout: 3000,
			success: function(response) {
				if (!response.success) Utils.hints.open(response.text, 'fail');
				
				button.disabled = false;
			},
			error: function(type) {
				switch (type) {
					case 'timeout':
						Utils.hints.open('Время выполнения запроса истекло, попробуйте позднее.', 'fail');
						break;
						
					case 'error':
						Utils.hints.open('Во время запроса произошла ошибка, попробуйте позднее.', 'fail');
						break;
				};
				
				// change loader to default image
				image.src = src;
			}
		});
	},
	
	// get template with source code and information about file
	getBasketTemplate: function(section, content) {
		section = document.getElementById(section);
		content = document.getElementById(content);
		
		Utils.ajax.query({
			url: '/ajax.php',
			data: {
				class: 'control',
				method: 'ajax',
				load: {
					class: 'Files',
					method: 'getBasketTemplate'
				}
			},
			method: 'POST',
			timeout: 3000,
			success: function(response) {
				if (response.success) {
					Utils.animation.make({
						object: section,
						styles: [{
							name: 'opacity',
							from: 1,
							to: 0
						}],
						
						properties: [{
							duration: 200,
							callback: function() {
								content.innerHTML = response.template;
								
								Utils.animation.make({
									object: section,
									styles: [{
										name: 'opacity',
										from: 0,
										to: 1
									}],
									
									properties: [{
										duration: 200
									}]
								});
							}
						}]
					});
				} else {
					Utils.hints.open(response.text, 'fail');
				};
			},
			error: function(type) {
				switch (type) {
					case 'timeout':
						Utils.hints.open('Время выполнения запроса истекло, попробуйте позднее.', 'fail');
						break;
						
					case 'error':
						Utils.hints.open('Во время запроса произошла ошибка, попробуйте позднее.', 'fail');
						break;
				};
			}
		});
	}
};