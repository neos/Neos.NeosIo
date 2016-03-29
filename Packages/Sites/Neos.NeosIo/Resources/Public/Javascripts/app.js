// Only initialize slider if we are not in the content module (we need a public API for that)
if ($('#neos-application').length == 0) {
	$(document).foundation('orbit', {
		timer_speed: 4000,
		navigation_arrows: false,
		slide_number: false,
		pause_on_hover: true,
		resume_on_mouseout: true
	}).foundation();
} else {
	// Orbit could be applied on clone in preview mode and destroyed again after disabling preview?
	// document.addEventListener('Neos.EnablePreview', function() {console.log('Preview enabled')});
	// document.addEventListener('Neos.DisablePreview', function() {console.log('Preview disabled')});
}

$(document).ready(function(){

	// Hack to align hovers correctly
	if ($(window).width() > 939) {
		$('.top-bar.alternative li.has-dropdown > a').each(function(){
			var width = $(this).width()/2;
			//$(this).parent().find('ul').css({'right':width});
		});
		//$('.top-bar.alternative li:last-child.has-dropdown > ul').css({'right':0}).addClass('sites');
	}

	// Init tiles plugins
	imagesLoaded(document.querySelectorAll('.tiles'), function () {
		$('.tiles--loader').hide();

		$('.tiles').each(function () {
			var $that = $(this),
				$filters = $that.find('.tiles--filters').show(),
				$tilesWrap = $that.find('.tiles--inner-wrap');

			var wookmark = new Wookmark($tilesWrap[0], {
				itemWidth: 308,
				offset: 10,
				ignoreInactiveItems: false,
				comparator: function (a, b) {
					var aInactive = $(a).hasClass('wookmark-inactive'),
						bInactive = $(b).hasClass('wookmark-inactive');

					if (aInactive == bInactive) {
						return $(a).data('name') < $(b).data('name') ? -1 : 1;
					}
					return aInactive && !bInactive ? 1 : -1;
				}
			});

			$filters.on('click.wookmark-filter', 'li', function (e) {
				var $item = $(e.currentTarget), itemActive = $item.hasClass('active');

				if (!itemActive) {
					$filters.children().removeClass('active');
				}
				itemActive = !itemActive;
				$item.toggleClass('active');

				// Filter by the currently selected filter
				wookmark.filter(itemActive ? [$item.data('filter')] : []);
			});
		});
	});

	// Init table sorting if needed
	$('table.sortable-stupidtable').stupidtable();
});

