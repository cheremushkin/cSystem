Utils.ajax.classes.filemaj = {
	// delete a file/directory (realy it moves into basket)
	delete: function(path, number) {
		wrapper = document.getElementById('files-line-' + number);
		field = document.getElementById('files-line-' + number + '-field-2');
		
		Utils.ajax.query({
			url: '/ajax.php',
			data: {
				class: 'Filemaj',
				method: 'cd',
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
				class: 'Filemaj',
				method: 'cd',
				path: path
			},
			method: 'POST',
			timeout: 3000,
			success: function(response) {
				if (response.code == 200) {
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
					Utils.hints.open(response.message, 'fail');
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
				class: 'Filemaj',
				method: 'getEditTemplate',
				path: path
			},
			method: 'POST',
			timeout: 3000,
			success: function(response) {
				if (response.code == 200) {
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
								Utils.ace('ace-editor', mode); // create beautiful editor
							
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
					Utils.hints.open(response.message, 'fail');
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
				class: 'Filemaj',
				method: 'saveSourceCode',
				path: path,
				source: source
			},
			method: 'POST',
			timeout: 3000,
			success: function(response) {
				if (!response.code == 200) Utils.hints.open(response.text, 'fail');
				
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
				class: 'Admin',
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