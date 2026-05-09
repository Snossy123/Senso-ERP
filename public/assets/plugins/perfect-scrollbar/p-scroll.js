(function($) {
	"use strict";

	function initPs(selector, options) {
		if (typeof PerfectScrollbar === 'undefined') return null;
		var el = typeof selector === 'string' ? document.querySelector(selector) : selector;
		if (!el) return null;
		try {
			return new PerfectScrollbar(el, options || {});
		} catch (e) {
			console.warn('[PerfectScrollbar] init skipped for', selector, e);
			return null;
		}
	}

	initPs('.chat-scroll', {
		useBothWheelAxes:true,
		suppressScrollX:true,
	});
	initPs('.Notification-scroll', {
		useBothWheelAxes:true,
		suppressScrollX:true,
	});

})(jQuery);
