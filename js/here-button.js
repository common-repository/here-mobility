(function () {
	tinymce.PluginManager.add('here_button', function (editor, url) {
		var shTag = 'here_button';

		//helper functions
		function getAttr(s, n) {
			n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
			return n ? window.decodeURIComponent(n[1]) : '';
		};

		function html(cls, data, con) {
      var dataEncoded = window.encodeURIComponent(data);
      var buttonType = getAttr( data, "buttonType");
      var buttonPosition = getAttr( data, "buttonPosition");
      var imgSrc;
      var imgClass;

      switch (buttonType) {
	      case 'round':
          imgSrc = url + '/img/round-btn.png';
          break;
        case 'text':
          imgSrc = url + '/img/text-btn.png';
          break;
        case 'rectangle':
          imgSrc = url + '/img/rectangle-btn.png';
          break;
      }

      switch (buttonPosition) {
        case 'left':
          imgClass = 'btn-left';
          break;
        case 'center':
          imgClass = 'btn-center';
          break;
        case 'right':
        default:
          imgClass = 'btn-right';
          break;
      }

      return '<img style="cursor: default;" src="' + imgSrc + '" class="mceItem mceItemButton ' + cls + ' ' + imgClass + '" ' + 'data-bsh-attr="' + dataEncoded + '" data-mce-resize="false" data-mce-placeholder="1" /><br style="clear:both" />';
		}

		function replaceShortcodes(content) {
			return content.replace(/\[here_button([^\]]*)\]([^\]]*)\[\/here_button\]/g, function (all, attr, con) {
				return html('wp-here_button', attr, con);
			});
		}

		function restoreShortcodes(content) {
			return content.replace(/(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function (match, tag) {
				var data = getAttr(tag, 'data-bsh-attr');

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
		editor.addCommand('here_button_popup', function (ui, params) {
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
            name: 'display',
            label: '',
            html:  '<div> \
								<h2>Widget button</h2>\
						</div>'
          },
          {
            type: 'listbox',
            name: 'buttonType',
            label: 'Button type',
            values : [
              { text: 'Rectangle', value: 'rectangle' },
              { text: 'Round', value: 'round' },
              { text: 'Text', value: 'text' }
            ],
            value: params.buttonType,
            classes: 'button-type',
            onSelect: function(e) {
              if ( e.target.state.data.value === 'round') {
              	jQuery('.mce-button-text').closest('.mce-container').hide();
              } else {
                jQuery('.mce-button-text').closest('.mce-container').show();
              }
            },
          },
          {
            type: 'listbox',
            name: 'buttonPosition',
            label: 'Button Position',
            values : [
              { text: 'left', value: 'left' },
              { text: 'center', value: 'center' },
              { text: 'right', value: 'right' }
            ],
            value: params.buttonPosition,
            classes: 'button-position'
          },
          {
            type: 'textbox',
            name: 'buttonText',
            label: 'Button Text',
            value: params.buttonText,
            classes: 'button-text'
          },
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
					}
				],
				onSubmit: function (event) {
					var data = event.data;

					jQuery('.dialog-warnings').text('').hide();

					if ((!data.appKey && data.appSecret) || (data.appKey && !data.appSecret)) {
						showError('App Key and App Secret are required.');
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

            if ( params.buttonType === 'round') {
              jQuery('.mce-button-text').closest('.mce-container').hide();
            } else {
              jQuery('.mce-button-text').closest('.mce-container').show();
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
					});
				},
				onClose: function () {
					jQuery('.mce-pickup').autoComplete('destroy');
					jQuery('.mce-destination').autoComplete('destroy');
				}
			});
		});

		//add button
		editor.addButton('here_button', {
			icon: 'here_button',
			tooltip: 'HERE Mobility Button',
			onclick: function () {
				editor.execCommand('here_button_popup', '', {
					destination: '',
					pickup: '',
					appKey: '',
					appSecret: '',
					settings: 'default',
					buttonPosition: 'right',
					buttonType: 'round',
					buttonText: 'Book a ride'
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
			if (e.target.className.indexOf('wp-here_button') > -1) {
				var container = jQuery(e.target);
        var title = title = window.decodeURIComponent(container.attr('data-bsh-attr'));

        editor.execCommand('here_button_popup', '', {
          id: getAttr(title, 'id'),
          destination: getAttr(title, 'destination'),
          pickup: getAttr(title, 'pickup'),
          appKey: getAttr(title, 'appKey'),
          appSecret: getAttr(title, 'appSecret'),
          settings: getAttr(title, 'settings'),
          buttonType: getAttr(title, 'buttonType'),
          buttonText: getAttr(title, 'buttonText'),
          buttonPosition: getAttr(title, 'buttonPosition'),
        });
      }
		});
	});
})();
