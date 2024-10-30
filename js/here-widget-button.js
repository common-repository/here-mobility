(function () {
	tinymce.PluginManager.add('here_panel', function (editor, url) {
		var shTag = 'here_panel';

		//helper functions
		function getAttr(s, n) {
			n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
			return n ? window.decodeURIComponent(n[1]) : '';
		};

		function html(cls, data, con) {
			var placeholder = url + '/img/widget.png';
			data = window.encodeURIComponent(data);

			return '<img style="cursor: default; max-width: 650px" src="' + placeholder + '" class="mceItem ' + cls + '" ' + 'data-sh-attr="' + data + '" data-mce-resize="true" data-mce-placeholder="1" />';
		}

		function replaceShortcodes(content) {
			return content.replace(/\[here_panel([^\]]*)\]([^\]]*)\[\/here_panel\]/g, function (all, attr, con) {
				return html('wp-here_panel', attr, con);
			});
		}

		function restoreShortcodes(content) {
			return content.replace(/(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function (match, image) {
				var data = getAttr(image, 'data-sh-attr');

				if (data) {
					return '<p>[' + shTag + data + ']' + '[/' + shTag + ']</p>';
				}
				return match;
			});
		}

		function generateId() {
			return Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2, 10);
		}

		//add popup
		editor.addCommand('here_panel_popup', function (ui, params) {
			//setup defaults
			var content = editor.getContent();

			if (content.indexOf(shTag) !== -1 && !params.id) {
				return;
			}

			function showError(error) {
				jQuery('.dialog-warnings').html(error).show();
			}

			editor.windowManager.open({
				title: 'HERE widget',
				width: 600,
				classes: 'here-popup',
				body: [
					{
						type: 'container',
						name: 'settings',
						label: '',
						html:  '<div> \
								<h2>Widget settings</h2>\
								<input type="radio" name="settings" value="default" id="settings_default"> \
								<label for="settings_default">Use default settings</label> \
								<input type="radio" name="settings" value="specific" id="settings_specific"> \
								<label for="settings_specific">Set specific settings</label> \
						</div>'
					},
					{
						type: 'container',
						name: 'container',
						html: '<div class="dialog-warning"></div>'
					},
					{
						type: 'textbox',
						name: 'id',
						value: params.id,
						classes: 'attr-id',
						hidden: true
					},
					{
						type: 'textbox',
						name: 'pickup',
						label: 'Pickup',
						value: params.pickup,
						classes: 'pickup'
					},
					{
						type: 'textbox',
						name: 'destination',
						label: 'Destination',
						value: params.destination,
						classes: 'destination'
					},
					{
						type: 'textbox',
						name: 'appKey',
						label: 'App Key',
						value: params.appKey,
						classes: 'app-key'
					},
					{
						type: 'textbox',
						name: 'appSecret',
						label: 'App Secret',
						value: params.appSecret,
						classes: 'app-secret'
					},
          {
            type: 'container',
            name: 'display',
            label: '',
            html:  '<div> \
								<h2>Widget display</h2>\
								<p style="white-space: pre-line; font-style: italic; color: #ccc;">\
			            You can use the default size of the widget or set a custom size. \
		              Please note that HERE Mobility widget’s minimum size is <b>360x450</b> pixels\
		              and its maximum size is <b>1200x450</b> pixels.\
					        The HERE web widget is mobile responsive in portrait mode below the break point of <b>512x450</b> pixels.\
					      </p>\
								<input type="radio" name="display" value="default" id="display_default"> \
		            <label for="display_default">Use default size</label> \
		            <input type="radio" name="display" value="custom" id="display_custom"> \
		            <label for="display_custom">Use custom size</label> \
						</div>'
          },
		      {
		        type: 'textbox',
	          name: 'width',
		        label: 'Width',
		        value: params.width,
		        classes: 'width'
		      },
		      {
		        type: 'textbox',
	          name: 'height',
		        label: 'Height',
		        value: params.height,
		        classes: 'height'
		      },
		      {
		        type: 'listbox',
		        name: 'unit',
		        label: 'Unit',
            values : [
              { text: 'px', value: 'px' },
              { text: '%', value: '%' },
	            { text: 'rem', value: 'rem' }
            ],
            value: params.unit,
		        classes: 'unit'
		      },
		      {
		        type: 'listbox',
	          name: 'position',
		        label: 'Position',
            values : [
              { text: 'left', value: 'left' },
              { text: 'center', value: 'center' },
              { text: 'right', value: 'right' }
            ],
			      value: params.position,
		        classes: 'position'
		      }
				],
				onSubmit: function (event) {
					var data = event.data;

					jQuery('.dialog-warnings').text('').hide();

					if ((!data.appKey && data.appSecret) || (data.appKey && !data.appSecret)) {
						showError('App Key and App Secret are required.');
						return false;
					}

          if (data.unit === 'px' && ( ( data.width && parseInt(data.width, 10) < 360) || ( data.height && parseInt(data.height, 10) < 450 ) ) ) {
            showError('Please set correct widget size. Widget’s minimum size is <b>360x450</b> pixels.');
            return false;
          }

					var shortcode_str = '[' + shTag;
					var update = !!data.id;

					data.id  = data.id || generateId();
					data.settings = params.settings === 'default' ? 'default': 'specific';

					for(var prop in data) {
						if (data.hasOwnProperty(prop) && typeof data[prop] == 'string' && data[prop].trim().length) {
							shortcode_str += ' ' + prop + '="' + data[prop].trim() + '"';
						}
					}

					shortcode_str += ']' + '[/' + shTag + ']';
					//insert shortcode to tinymce
					if (update) {
						var content = editor.getContent();
						var re = new RegExp('\\[' + shTag + '[^\\]]+?id="' + data.id + '"[^\\]]*?\\]\\[\/' + shTag + '\\]');
						content = content.replace(re, shortcode_str);
						editor.setContent(content);
					} else {
						editor.insertContent(shortcode_str);
					}
				},
				onOpen: function () {

					setTimeout(function () {
						jQuery('.mce-pickup').autoComplete({
							minChars: 2
						});

						jQuery('.mce-destination').autoComplete({
							minChars: 2
						});

						jQuery('<div class="dialog-warnings"></div>').insertAfter( '.mce-window-head').hide();

						if (params.settings === 'default') {
							jQuery('#settings_default').prop('checked', true);

              [
                '.mce-destination',
                '.mce-pickup',
                '.mce-app-key',
                '.mce-app-secret'
              ].forEach(function (childClass) {
                jQuery(childClass).closest('.mce-abs-layout-item.mce-container').hide();
              });

              jQuery('#settings_default').prop('checked', true);

            } else {
							jQuery('#settings_specific').prop('checked', true);
						}

            if (params.display === 'default') {
              [
                '.mce-width',
                '.mce-height',
                '.mce-unit',
                '.mce-position'
              ].forEach(function (childClass) {
                jQuery(childClass).closest('.mce-abs-layout-item.mce-container').hide();
              });
              jQuery('#display_default').prop('checked', true);

            } else {
              jQuery('#display_custom').prop('checked', true);
            }

						jQuery('input[name="settings"]').change(function (event) {
							if (event.target.getAttribute('value') === 'default') {

								jQuery('.mce-rewrite-credentianls').prop('checked', false);
								jQuery('.mce-destination').val('');
								jQuery('.mce-pickup').val('');
								jQuery('.mce-app-key').val('');
								jQuery('.mce-app-secret').val('');

                [
                  '.mce-destination',
                  '.mce-pickup',
                  '.mce-app-key',
                  '.mce-app-secret'
                ].forEach(function (childClass) {
                  jQuery(childClass).closest('.mce-abs-layout-item.mce-container').hide();
                });

								params.settings = 'default';
							} else {
                [
                  '.mce-destination',
                  '.mce-pickup',
                  '.mce-app-key',
                  '.mce-app-secret'
                ].forEach(function (childClass) {
                  jQuery(childClass).closest('.mce-abs-layout-item.mce-container').show();
                });

								params.settings = 'specific';
							}
						});

            jQuery('input[name="display"]').change(function (event) {
              if (event.target.getAttribute('value') === 'default') {

                jQuery('.mce-width').val('');
                jQuery('.mce-height').val('');
                jQuery('.mce-unit').val('px');
                jQuery('.mce-position').val('center');

                [
                  '.mce-width',
                  '.mce-height',
                  '.mce-unit',
                  '.mce-position'
                ].forEach(function (childClass) {
                  jQuery(childClass).closest('.mce-abs-layout-item.mce-container').hide();
                });

                params.display = 'default';
              } else {

                [
                  '.mce-width',
                  '.mce-height',
                  '.mce-unit',
                  '.mce-position'
                ].forEach(function (childClass) {
                  jQuery(childClass).closest('.mce-abs-layout-item.mce-container').show();
                });

                params.settings = 'custom';
              }
            });
					});
				},
				onClose: function () {
					jQuery('.mce-pickup').autoComplete('destroy');
					jQuery('.mce-destination').autoComplete('destroy');
				}
			});
		});

		//add button
		editor.addButton('here_panel', {
			icon: 'here_panel',
			tooltip: 'HERE Mobility Panel',
			onclick: function () {
				editor.execCommand('here_panel_popup', '', {
					destination: '',
					pickup: '',
					appKey: '',
					appSecret: '',
					settings: 'default',
					display: 'default',
					width: '',
					height: '',
					unit: 'px',
					position: 'center'
				});
			}
		});

		//replace from shortcode to an image placeholder
		editor.on('BeforeSetcontent', function (event) {
			event.content = replaceShortcodes(event.content);
		});

		//replace from image placeholder to shortcode
		editor.on('GetContent', function (event) {
			event.content = restoreShortcodes(event.content);
		});

		//open popup on placeholder double click
		editor.on('DblClick', function (e) {
			if (e.target.nodeName == 'IMG' && e.target.className.indexOf('wp-here_panel') > -1) {
				var title = e.target.attributes['data-sh-attr'].value;
				title = window.decodeURIComponent(title);
				editor.execCommand('here_panel_popup', '', {
					id: getAttr(title, 'id'),
					destination: getAttr(title, 'destination'),
					pickup: getAttr(title, 'pickup'),
					appKey: getAttr(title, 'appKey'),
					appSecret: getAttr(title, 'appSecret'),
					settings: getAttr(title, 'settings'),
					width: getAttr(title, 'width'),
					height: getAttr(title, 'height'),
					position: getAttr(title, 'position'),
					unit: getAttr(title, 'unit'),
					display: getAttr(title, 'display'),
				});
			}
		});
	});
})();
