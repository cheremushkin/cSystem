Utils = {
	/**
	 * Universal animation function
	 */
	
	animation: {
        // all animations
		queue: [],
		
		make: function(params) {
			/*
				params = {
					object: object,
					styles: [
						{
							name: string,
							from: number,
							to: number,
							prefix: string,
							suffix: string
						}
					],
            
					properties: [
						{
							duration: number,
							callback: function
						}
					]
				}
			*/
			
			// every style of object
            for (var i = 0, size = params.styles.length; i < size; i++) {
                var style = params.styles[i],
                    properties = params.properties[i];
                
                // editing prefixes and suffixes
                style.prefix = style.prefix || '';
                style.suffix = style.suffix || '';
                
                // new object in queue
                var index = this.queue.length;
                this.queue[index] = {
                    object: params.object,
                    from: style.from,
                    to: style.to,
                    start: new Date().getTime(),
                    timeout: setTimeout(function() {
                        var now = new Date().getTime() - Utils.animation.queue[index].start,
                            progress = now / properties.duration,
                            result = (style.to - style.from) * progress + style.from;
                        
                        params.object.style[style.name] = style.prefix + result + style.suffix;
                        
                        if (progress < 1) {
                            Utils.animation.queue[index].timeout = setTimeout(arguments.callee, 0);
                        } else {
                            // fix rounding curves
                            params.object.style[style.name] = style.prefix + style.to + style.suffix;
                        
                            // callback
                            if (properties.callback) {
                                properties.callback.function.apply(properties.callback.context ? properties.callback.context : window, properties.callback.arguments ? properties.callback.arguments : []);
                            };
                        };
                    }, 0)
                };
            };    
        }
    },
	
	
	
	/**
	 * AJAX object.
	 */
	
	ajax: {
		query: function(params) {
			/* params = {
				url: string
				data: object
				method: POST/GET
				timeout: number
				success: function
				error: function
			} */


			// cross browser request
			var factories = [
					function() {
						return new ActiveXObject("Msxml2.XMLHTTP");
					},
					function() {
						return new ActiveXObject("Msxml3.XMLHTTP");
					},
					function() {
						return new ActiveXObject("Microsoft.XMLHTTP");
					},
					function() {
						return new XMLHttpRequest();
					}
				],
				i = factories.length,
				request = false;
				
			while (i--) {
				try {
					request = factories[i]();
				} catch (e) {
					continue;
				};
				break;
			}
			if (!request) return false;
			
			
			// method
			params.method = params.method ? params.method.toUpperCase() : 'POST';
			if (params.method == 'GET'){
				params.url = params.url + (params.data ? '?' + params.data : '');
				params.data = null;
			} else {
				if (!params.data) params.data = null;
			};
			
			
			// data
			params.data = 'data=' + encodeURIComponent(JSON.stringify(params.data));
			
			
			// sending request
			request.open(params.method, params.url, true);
			request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			
			
			// timeout
			params.timeout = params.timeout || this.timeout;
			params.timeout = setTimeout(function() {
				request.abort();
				params.error && params.error('timeout');
			}, params.timeout);
			
			
			// server's response
			params.timeout = params.timeout || this.timeout;
			request.onreadystatechange = function() {
				if (request.readyState != 4) return false;
				if (request.status == 200) {
					params.timeout && clearTimeout(params.timeout);
					
					// parse the response and transmission to handler
                    var response = JSON.parse(request.responseText);
					params.success && params.success(response);
				} else {
					params.timeout && clearTimeout(params.timeout);
					params.error && params.error('error');
				}
			};
			
			request.send(params.data);
		},
		
		// AJAX handlers
		classes: {}
	},



    /**
     * Beautiful animation hints.
     */

    hints: {
        options: {
            max: 5, // максимальное кол-во открытых подсказок
            top: 10, // отступ для первой подсказки
            between: 5, // промежуток между подсказками

            timers: {
                fade: 250, // время на появление
                position: 250, // время на позиционирование
                life: 5000 // время существования
            }
        },

        queues:{all:[],open:[],close:[]},
        is:{opening:false,closing:false,recursion:false},
        open:function(message,name){var hint=document.createElement('div');this.customize(hint,message,name);this.queues.all.push(hint);if(this.queues.all.length>this.options.max||this.is.recursion){this.queues.open.push(hint);return false}else{this.view(hint)}},
        customize:function(hint,message,name){hint.className='cHints '+name;hint.innerHTML=message;hint.style.opacity=0;hint.style.position='absolute';hint.style.right=10+'px'},
        view:function(hint){hint.style.top=this.options.top+'px';document.body.appendChild(hint);this.options.top+=hint.offsetHeight+this.options.between;Utils.animation.make({object:hint,styles:[{name:'opacity',from:0,to:1}],properties:[{duration:this.options.timers.fade,callback:function(){setTimeout(function(){if(Utils.hints.is.recursion){Utils.hints.queues.close.push(hint);return false}else{Utils.hints.close(hint)}},Utils.hints.options.timers.life)}}]})},
        close:function(hint){this.is.recursion=true;Utils.animation.make({object:hint,styles:[{name:'opacity',from:1,to:0}],properties:[{duration:Utils.hints.options.timers.fade,callback:function(){var height=hint.offsetHeight,top=parseInt(hint.style.top,10)+height+Utils.hints.options.between;document.body.removeChild(hint);var size=Utils.hints.queues.all.length-Utils.hints.queues.open.length;for(var i=1;i<size;i++){Utils.animation.make({object:Utils.hints.queues.all[i],styles:[{name:'top',from:top,to:top-height-Utils.hints.options.between,suffix:'px'}],properties:[{duration:Utils.hints.options.timers.position,}]});top+=parseInt(Utils.hints.queues.all[i].offsetHeight,10)+Utils.hints.options.between};setTimeout(function(){Utils.hints.options.top-=(height+5);Utils.hints.queues.all.shift();if(!Utils.hints.queues.close.length)Utils.hints.is.recursion=false;if(Utils.hints.queues.open.length){Utils.hints.view(Utils.hints.queues.open.shift())};if(Utils.hints.queues.close.length){setTimeout(function(){Utils.hints.close(Utils.hints.queues.close.shift())},Utils.hints.options.timers.fade)}},Utils.hints.options.timers.position)}}]})}
    },



    /**
     * Object for controlling CAPTCHA images.
     */

    captcha: {
        // all security codes
        all: [],


        // finds a security code using its ID
        get: function(id) {
            return this.all[id] || false;
        },


        // create new instance
        init: function(id, image, field, parameters) {
            // check if ID is busy
            if (this.all[id]) return false;


            (captcha = function(image, field, parameters) {
                // find on the page and save
                this.image = document.getElementById(image);
                this.field = document.getElementById(field);


                // action's flag
                this.action = true;


                // create a URL and save in the object
                var url = [], key; this.parameters = {};
                for (key in parameters) {
                    url[url.length] = key + "=" + parameters[key];
                    this.parameters[key] = parameters[key];
                };
                this.src = "/captcha?" + Utils.implode("&", url);


                // set onclick handler
                this.image.onclick = (function(captcha) {
                    return function() {
                        captcha.refresh();
                    };
                })(this);
            }).prototype = {
                // create an image
                create: function() {
                    this.image.src = this.src;
                },


                // load new CAPTCHA
                refresh: function() {
                    // check if another action is going now
                    if (!captcha.action) return false;
                    captcha.action = false;


                    // reset CAPTCHA field
                    this.field.value = "";


                    var callback = function() {
                        // refresh
                        this.image.src = this.src;


                        var callback = function() {
                            this.action = true;
                        };


                        // and return on the page
                        Utils.animation.make({
                            object: this.image,
                            styles: [{name: "opacity", from: 0, to: 1}],
                            properties: [{duration: 250, callback: {function: callback, context: this}}]
                        });
                    };


                    // to opacity = 0
                    Utils.animation.make({
                        object: this.image,
                        styles: [{name: "opacity", from: 1, to: 0}],
                        properties: [{duration: 250, callback: {function: callback, context: this}}]
                    });
                }
            };


            // create an instance, save in the object and return
            captcha = new captcha(image, field, parameters);
            this.all[id] = captcha;
            return captcha;
        }
    },
	
	
	
	/**
	 * Equivalent of PHP's construction „echo“.
	 * Source code from http://phpjs.org/functions/echo/ site.
	 */
	
	echo: function() {
		var arg = '',
			argc = arguments.length,
			argv = arguments,
			i = 0,
			holder, win = window,
			d = win.document,
			ns_xhtml = 'http://www.w3.org/1999/xhtml',
			ns_xul = 'http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul';
		
		var stringToDOM = function(str, parent, ns, container) {
			var extraNSs = '';
			if (ns === ns_xul) {
			  extraNSs = ' xmlns:html="' + ns_xhtml + '"';
			};
			var stringContainer = '<' + container + ' xmlns="' + ns + '"' + extraNSs + '>' + str + '</' + container + '>';
			var dils = win.DOMImplementationLS,
				dp = win.DOMParser,
				ax = win.ActiveXObject;
			if (dils && dils.createLSInput && dils.createLSParser) {
				var lsInput = dils.createLSInput();
				lsInput.stringData = stringContainer;
				var lsParser = dils.createLSParser(1, null);
				return lsParser.parse(lsInput).firstChild;
			} else if (dp) {
				try {
					var fc = new dp().parseFromString(stringContainer, 'text/xml');
					if (fc && fc.documentElement && fc.documentElement.localName !== 'parsererror' && fc.documentElement.namespaceURI !== 'http://www.mozilla.org/newlayout/xml/parsererror.xml') {
						return fc.documentElement.firstChild;
					};
				} catch (e) {};
			} else if (ax) {
				var axo = new ax('MSXML2.DOMDocument');
				axo.loadXML(str);
				return axo.documentElement;
			};
			if (d.createElementNS && (d.documentElement.namespaceURI || d.documentElement.nodeName.toLowerCase() !== 'html' ||( d.contentType && d.contentType !== 'text/html'))) {
				holder = d.createElementNS(ns, container);
			} else {
				holder = d.createElement(container);
			};
			holder.innerHTML = str;
			while (holder.firstChild) {
				parent.appendChild(holder.firstChild);
			};
			return false;
		};


		var ieFix = function(node) {
			if (node.nodeType === 1) {
				var newNode = d.createElement(node.nodeName);
				var i, len;
				if (node.attributes && node.attributes.length > 0) {
					for (i = 0, len = node.attributes.length; i < len; i++) {
						newNode.setAttribute(node.attributes[i].nodeName, node.getAttribute(node.attributes[i].nodeName));
					};
				};
				if (node.childNodes && node.childNodes.length > 0) {
					for (i = 0, len = node.childNodes.length; i < len; i++) {
						newNode.appendChild(ieFix(node.childNodes[i]));
					};
				};
				return newNode;
			} else {
				return d.createTextNode(node.nodeValue);
			};
		};

		var replacer = function(s, m1, m2) {
			if (m1 !== '\\') {
				return m1 + eval(m2);
			} else {
				return s;
			};
		};
		
		var phpjs = {},
			ini = phpjs.ini,
			obs = phpjs.obs;
		for (i = 0; i < argc; i++) {
			arg = argv[i];
			if (ini && ini['phpjs.echo_embedded_vars']) {
				arg = arg.replace(/(.?)\{?\$(\w*?\}|\w*)/g, replacer);
			};

			if (!phpjs.flushing && obs && obs.length) {
				obs[obs.length - 1].buffer += arg;
				continue;
			};

			if (d.appendChild) {
				if (d.body) {
					if (win.navigator.appName === 'Microsoft Internet Explorer') {
						d.body.appendChild(stringToDOM(ieFix(arg)));
					} else {
						var unappendedLeft = stringToDOM(arg, d.body, ns_xhtml, 'div').cloneNode(true);
						if (unappendedLeft) {
							d.body.appendChild(unappendedLeft);
						};
					};
				} else {
					d.documentElement.appendChild(stringToDOM(arg, d.documentElement, ns_xul, 'description'));
				};
			} else if (d.write) {
				d.write(arg);
			};
		};
	},



    /**
     * Equivalent of PHP's function „trim“.
     */

    trim: (function() {
        var i,
            ws = {},
            chars = ' \n\r\t\v\f\u00a0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000',
            length = chars.length;

        for (i = 0; i < length; i++) {
            ws[chars.charAt(i)] = true;
        };

        return function(str) {
            var s = -1,
                e = str.length;

            while (ws[str.charAt(--e)]);
            while (s++ !== e && ws[str.charAt(s)]);
            return str.substring(s, e + 1);
        };
    })(),



    /**
     * Equivalent of PHP's function implode().
     */

    implode: function(glue, pieces) {
        var i = '', retVal = '', tGlue = '';
        if (arguments.length === 1) {
            pieces = glue;
            glue = '';
        };
        if (typeof(pieces) === 'object') {
            if (Object.prototype.toString.call(pieces) === '[object Array]') {
                return pieces.join(glue);
            }
            for (i in pieces) {
                retVal += tGlue + pieces[i];
                tGlue = glue;
            }
            return retVal;
        }
        return pieces;
    },
	
	
	
	/**
	 * Create ACE field with beautiful settings.
	 */
	
	ace: function(id, mode) {
		var editor = ace.edit(id);
		editor.setTheme("ace/theme/tomorrow");
		mode && editor.getSession().setMode("ace/mode/" + mode);
		editor.getSession().setUseWrapMode(true);
		
		return editor;
	}
};