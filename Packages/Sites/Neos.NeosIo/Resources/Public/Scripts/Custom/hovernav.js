$(document).ready(function() {
	/*
	 "Hovernav" navbar dropdown on hover
	 Uses jQuery Media Query - see http://www.sitepoint.com/javascript-media-queries/
	 */
	var mq = window.matchMedia('(min-width: 768px)');
	if (mq.matches) {
		$('ul.navbar-nav > li').addClass('hovernav');
	} else {
		$('ul.navbar-nav > li').removeClass('hovernav');
	}
	/*
	 The addClass/removeClass also needs to be triggered
	 on page resize <=> 768px
	 */
	if (matchMedia) {
		mq = window.matchMedia('(min-width: 768px)');
		mq.addListener(WidthChange);
		WidthChange(mq);
	}
	function WidthChange(mq) {
		if (mq.matches) {
			$('ul.navbar-nav > li').addClass('hovernav');
			// Restore "clickable parent links" in navbar
			$('.hovernav a').click(function () {
				window.location = this.href;
			});
		} else {
			$('.hovernav a').unbind('click');
			$('ul.navbar-nav > li').removeClass('hovernav');
		}
	}
	// Restore "clickable parent links" in navbar
	$('.hovernav a').click(function () {
		window.location = this.href;
	});
});