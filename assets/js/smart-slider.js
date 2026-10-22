/**
 * Smart Product Slider: On-Demand DOM Tab Switching, AJAX Lazy Load, Native Flatsome Slider (Flickity)
 *
 * @package FlatsomeBlockManager
 */

(function($) {
	'use strict';

	/**
	 * Activate a tab panel using Flatsome native animation attributes & transitions
	 *
	 * @param {jQuery} $panel
	 * @param {string} animType
	 * @param {number} [speed]
	 */
	function activateTabPanel($panel, animType, speed) {
		animType = (animType && animType !== 'none') ? animType : '';

		if (animType) {
			$panel.attr('data-animate', animType);
			$panel.removeAttr('data-animate-transition');
			$panel.removeAttr('data-animated');
			if (speed) {
				$panel.css('--uxb-anim-speed', speed + 's');
			}
		} else {
			$panel.removeAttr('data-animate');
		}

		$panel.addClass('active').show();

		// Trigger Flatsome's native animation sequence via requestAnimationFrame
		if (animType) {
			window.requestAnimationFrame(function() {
				$panel.attr('data-animate-transform', 'true');
				window.requestAnimationFrame(function() {
					$panel.attr('data-animate-transition', 'true');
					setTimeout(function() {
						$panel.attr('data-animated', 'true');
					}, 50);
				});
			});
		} else {
			$panel.attr('data-animated', 'true');
		}

		// Initialize and layout Flickity slider inside
		initFlatsomeSlider($panel);
	}

	/**
	 * Attach or resize Flatsome Flickity slider inside target container
	 *
	 * @param {jQuery} $context Container element containing .slider
	 */
	function initFlatsomeSlider($context) {
		if (!$context || !$context.length) {
			return;
		}

		var $sliders = $context.find('.slider');
		if (!$sliders.length && $context.hasClass('slider')) {
			$sliders = $context;
		}

		$sliders.each(function() {
			var $slider = $(this);

			// If Flickity instance already exists, trigger resize and reposition
			if ($slider.data('flickity')) {
				$slider.flickity('resize');
				$slider.flickity('reposition');
				return;
			}

			// Attach via Flatsome's native behavior runner
			if (window.Flatsome && typeof window.Flatsome.attach === 'function') {
				window.Flatsome.attach($context);
			} else if (typeof $.fn.flickity === 'function') {
				var options = $slider.data('flickity-options') || {};
				$slider.flickity(options);
			}

			// Ensure layout is recalculated after animation/render
			setTimeout(function() {
				if ($slider.data('flickity')) {
					$slider.flickity('resize');
					$slider.flickity('reposition');
				}
			}, 60);
		});

		// Trigger Flatsome image lazyload and WooCommerce hooks
		$(document).trigger('flatsome-lazyload');
		$(document.body).trigger('post-load');
	}

	$(document).ready(function() {
		// Initialize pre-loaded first slider on DOM ready
		$('.uxb-smart-slider-wrapper').each(function() {
			var $wrapper = $(this);
			var $activePanel = $wrapper.find('.uxb-tab-panel.active[data-loaded="true"]');
			if ($activePanel.length) {
				var anim = $activePanel.attr('data-animate');
				if (anim && anim !== 'none') {
					$activePanel.removeAttr('data-animate-transition');
					$activePanel.removeAttr('data-animated');
					window.requestAnimationFrame(function() {
						$activePanel.attr('data-animate-transform', 'true');
						window.requestAnimationFrame(function() {
							$activePanel.attr('data-animate-transition', 'true');
							setTimeout(function() {
								$activePanel.attr('data-animated', 'true');
							}, 50);
						});
					});
				}
				initFlatsomeSlider($activePanel);
			}
		});

		// Tab click handler (delegated to support UX Builder live canvas)
		$(document).on('click', '.uxb-tab-btn', function(e) {
			e.preventDefault();

			var $btn         = $(this);
			var $wrapper     = $btn.closest('.uxb-smart-slider-wrapper');
			var targetSel    = $btn.data('target');
			var targetId     = targetSel ? targetSel.replace('#', '') : '';
			var $panelsWrap  = $wrapper.find('.uxb-tab-panels');
			var $targetPanel = $wrapper.find(targetSel);

			// Prevent duplicate clicks if already active or currently loading
			if ($btn.hasClass('active') || $btn.hasClass('is-loading')) {
				return;
			}

			// Update tab button active states
			$wrapper.find('.uxb-tab-btn').removeClass('active').attr('aria-selected', 'false');
			$btn.addClass('active').attr('aria-selected', 'true');

			// Hide all currently displayed panels
			$panelsWrap.find('.uxb-tab-panel').hide().removeClass('active');

			// Case 1: Panel already exists in DOM and was loaded previously (Client Cache)
			if ($targetPanel.length && $targetPanel.attr('data-loaded') === 'true') {
				var cachedParams = $btn.data('tab-params') || {};
				var cachedAnim   = (cachedParams.animate && cachedParams.animate !== 'none') ? cachedParams.animate : '';
				var cachedSpeed  = cachedParams.animate_speed ? parseFloat(cachedParams.animate_speed) : 0.7;
				activateTabPanel($targetPanel, cachedAnim, cachedSpeed);
				return;
			}

			// Case 2: On-demand DOM creation (Initial HTML has 0 DOM for lazy tabs)
			var tabParams = $btn.data('tab-params') || {};
			var tabAnim   = (tabParams && tabParams.animate && tabParams.animate !== 'none') ? tabParams.animate : '';
			var tabSpeed  = (tabParams && tabParams.animate_speed) ? parseFloat(tabParams.animate_speed) : 0.7;
			var cols      = (tabParams && tabParams.columns) ? Math.min(4, parseInt(tabParams.columns, 10)) : 4;

			if (!$targetPanel.length) {
				$targetPanel = $('<div id="' + targetId + '" class="uxb-tab-panel active" data-loaded="false" role="tabpanel"></div>');
				$panelsWrap.append($targetPanel);
			} else {
				$targetPanel.show().addClass('active');
			}

			// Render dynamic shimmer skeleton while waiting for AJAX
			var skeletonHtml = '<div class="uxb-slider-skeleton" aria-hidden="true"><div class="uxb-skeleton-grid uxb-cols-' + cols + '">';
			for (var s = 0; s < cols; s++) {
				skeletonHtml += '<div class="uxb-skeleton-card">' +
					'<div class="uxb-skeleton-thumb"></div>' +
					'<div class="uxb-skeleton-line category"></div>' +
					'<div class="uxb-skeleton-line title"></div>' +
					'<div class="uxb-skeleton-line price"></div>' +
				'</div>';
			}
			skeletonHtml += '</div></div>';
			$targetPanel.html(skeletonHtml);

			$btn.addClass('is-loading');

			var postData = $.extend({}, tabParams, {
				action: 'uxb_load_tab_products',
				nonce: uxb_smart_slider_params.nonce
			});

			$.ajax({
				url: uxb_smart_slider_params.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: postData,
				success: function(res) {
					if (res && res.success && res.data && res.data.html) {
						// Insert returned native slider markup into panel
						$targetPanel.empty().append(res.data.html);
						$targetPanel.attr('data-loaded', 'true');
						activateTabPanel($targetPanel, tabAnim, tabSpeed);
					} else {
						var errMsg = (res && res.data && res.data.message) ? res.data.message : 'Unable to load products.';
						$targetPanel.html('<div class="uxb-no-products"><p>' + errMsg + '</p></div>');
					}
				},
				error: function() {
					$targetPanel.html('<div class="uxb-no-products error"><p>Server connection error while loading products. Please try again later.</p></div>');
				},
				complete: function() {
					$btn.removeClass('is-loading');
				}
			});
		});
	});

	// Support UX Builder live canvas re-attachments
	if (window.UxBuilder && typeof window.UxBuilder.on === 'function') {
		window.UxBuilder.on('shortcode-attached', function(e) {
			if (e && e.$element && e.$element.find('.uxb-smart-slider-wrapper').length) {
				var $active = e.$element.find('.uxb-tab-panel.active[data-loaded="true"]');
				if ($active.length) {
					initFlatsomeSlider($active);
				}
			}
		});
	}

})(jQuery);
