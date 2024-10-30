(function () {
	tinymce.PluginManager.add('here_link', function (editor, url) {
		var shTag = 'here_link';

		//helper functions
		function getAttr(s, n) {
			n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
			return n ? window.decodeURIComponent(n[1]) : '';
		};

		function html(cls, data, con) {
			var placeholder = url + '/img/widget.js.png';
			data = window.encodeURIComponent(data);

			return '<span style="background-color: #61ddd4; cursor: default; display: inline-block; padding: 10px;" class="mceItem ' + cls + '" ' + 'data-shl-attr="' + data + '" data-mce-placeholder="1">' + con + '</span>';
		}

		function replaceShortcodes(content) {
			return content.replace(/\[here_link([^\]]*)\]([^\[]*)\[\/here_link\]/g, function (all, attr, con) {
				return html('wp-here_link', attr, con);
			});
		}

		function restoreShortcodes(content) {
			return content.replace(/(?:<p(?: [^>]+)?>)*(<span[^>]+?data-shl-att[^>]+?>(.*?)<\/span>)(?:<\/p>)*/g, function (match, span, content) {
				var data = getAttr(span, 'data-shl-attr');

				if (data) {
					return '<p>[' + shTag + data + ']' + content + '[/' + shTag + ']</p>';
				}
				return match;
			});
		}

		function generateId() {
			return Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2, 10);
		}

		//add popup
		editor.addCommand('here_link_popup', function (ui, params) {
			//setup defaults
			var pickup = params.pickup || '';
			var destination = params.destination || '';
			var id = params.id || '';

			var buttons = [{
				text: 'OK',
				onclick: 'submit',
				classes: 'primary',
				id: 'saveButton'
			}, {
				text: 'Close',
				onclick: 'close',
				id: 'closeButton'
			}];

			if (id) {
				buttons.push({
					text: 'Delete',
					onclick: function () {
						if (id) {
							var content = editor.getContent();
							var re = new RegExp('\\[' + shTag + '[^\\]]+?id="' + id + '"[^\\]]*?\\]([^\\[]*)\\[\/' + shTag + '\\]');

							content = content.replace(re, "$1");
							editor.setContent(content);
						}

						tinymce.activeEditor.windowManager.close();
					},
					id: 'deleteButton'
				});
			}

			function showError(error) {
				jQuery('.dialog-warnings').text(error).show();
			}

			editor.windowManager.open({
				title: 'HERE pickup/destination link',
				width: 600,
				classes: 'here-popup',
				buttons: buttons,
				body: [
					{
						type: 'textbox',
						name: 'pickup',
						label: 'Pickup',
						value: pickup,
						classes: 'pickup'
					},
					{
						type: 'textbox',
						name: 'destination',
						label: 'Destination',
						value: destination,
						classes: 'destination'
					},
					{
						type: 'textbox',
						name: 'id',
						value: id,
						hidden: true
					}
				],
				onSubmit: function (event) {
					jQuery('.dialog-warnings').text('').hide();

					var data = event.data;

					if (!data.destination && !data.pickup) {
						showError('Pickup or Destination should be filled.');
						return false;
					}

					var shortcode_str = '[' + shTag;
					var update = !!data.id;
					var node = editor.selection.getNode();
					var sectionContent = node.nodeName.toLowerCase() !== 'p'  ? editor.selection.getNode().outerHTML : editor.selection.getContent();

					data.id = data.id || generateId();

					for(var prop in data) {
						if (data.hasOwnProperty(prop) && typeof data[prop] == 'string' && data[prop].trim().length) {
							shortcode_str += ' ' + prop + '="' + data[prop].trim() + '"';
						}
					}

					shortcode_str += ']';
					if (update) {
						var content = editor.getContent();
						var re = new RegExp('\\[' + shTag + '[^\\]]+?id="' + data.id + '"[^\\]]*?\\]');
						content = content.replace(re, shortcode_str);
						editor.setContent(content);
					} else {
						//insert shortcode to tinymce
						shortcode_str  += sectionContent + '[/' + shTag + ']';
						tinymce.execCommand('mceInsertContent', false, shortcode_str);
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
					});
				},
				onClose: function () {
					jQuery('.mce-destination').autoComplete('destroy');
					jQuery('.mce-pickup').autoComplete('destroy');
				}
			});
		});

		//add button
		editor.addButton('here_link', {
			icon: 'here_link',
			tooltip: 'HERE Mobility Link',
			onclick: function () {
				editor.execCommand('here_link_popup', '', {
					pickup: '',
					destination: ''
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
			if (e.target.nodeName == 'SPAN' && e.target.className.indexOf('wp-here_link') > -1) {
				var title = e.target.attributes['data-shl-attr'].value;
				title = window.decodeURIComponent(title);
				editor.execCommand('here_link_popup', '', {
					id: getAttr(title, 'id'),
					pickup: getAttr(title, 'pickup'),
					destination: getAttr(title, 'destination')
				});
			}
		});
	});
})();
