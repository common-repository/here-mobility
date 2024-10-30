(function (w, i, d, g, e, t) {
	var h = 'HereMobilityWidget';
	w[h] = w[h] || [];
	w[h].push(e);
	if (!(e in w)) {
		w[e] = function (o) {
			w[e].q.push(o)
		};
		w[e].q = [{el: g}]
	}
	w[e].t = new Date() - 0;
	var s = i.createElement('script');
	s.async = 1;
	s.src = d + '?a=' + t + '&b=' + (w[e].t / 864e5 | 0);
	i.querySelector('#' + g) && i.querySelector('#' + g).appendChild(s);
})(window, document, hereLoaderData.widgetUrl, hereLoaderData.id, 'hmw', hereLoaderData.appKey);

jQuery('em.here-destination').on('click', function () {
	hmw({
			pickup: jQuery(this).data('pickup'),
			destination: jQuery(this).data('destination')
	});
});

setTimeout(function () {
	jQuery(document).ready(function(_) {

		var postData = {
			'action': hereLoaderData.auth_ajax_action,
			'application_key': hereLoaderData.appKey,
			'post_id': hereLoaderData.post_id,
		};

		jQuery.post(hereLoaderData.auth_ajax_url, postData, function(response) {
			if (response) {

				var d = new Date(Date.now());
				hmw({
					eventTime: d.getTime(),
					pickup: hereLoaderData.pickup,
					destination: hereLoaderData.destination,
					locale: hereLoaderData.locale || 'en-US',
					auth: JSON.parse(response)
				});
			}
		});
	});
}, 0);

