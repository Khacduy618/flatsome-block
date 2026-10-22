/**
 * Step Progress Bar Component
 * Two-way binding with Flatsome Flickity Slider
 *
 * @package FlatsomeBlockManager
 */

(function($) {
	'use strict';

	/**
	 * Initialize a single Step Progress element
	 *
	 * @param {HTMLElement} el
	 */
	function initStepProgress(el) {
		var $wrap = $(el);

		// Read fresh attributes directly from DOM to avoid stale cache
		var targetCls     = ($wrap.attr('data-target') || '').trim();
		var style         = $wrap.attr('data-style') || 'segmented';
		var rawLabelsAttr = $wrap.attr('data-labels');
		var rawLabels     = (typeof rawLabelsAttr === 'string' && rawLabelsAttr.trim() !== '')
			? rawLabelsAttr.split(',').map(function(s) { return s.trim(); }).filter(Boolean)
			: [];

		// Find adjacent or sibling slider
		function findSlider() {
			var $s = $wrap.prevAll('.slider, .ux-slider').first();
			if (!$s.length) {
				$s = $wrap.parent().find('.slider, .ux-slider').first();
			}
			if (!$s.length) {
				$s = $wrap.closest('.row, .section, .col, [class*="col-"]').find('.slider, .ux-slider').first();
			}
			return $s;
		}

		var $slider = findSlider();
		if (!$slider.length) {
			return;
		}

		var flkty = null;
		var totalSteps = 0;

		// Get current active slide index reliably across all modes
		function getActiveIndex() {
			flkty = $slider.data('flickity') || (window.Flickity ? window.Flickity.data($slider[0]) : null);
			if (flkty && typeof flkty.selectedIndex === 'number') {
				return flkty.selectedIndex;
			}
			var $cells = $slider.find('.flickity-slider > *');
			if (!$cells.length) {
				$cells = $slider.find('.banner, .col, .step-item');
			}
			var $sel = $cells.filter('.is-selected');
			if ($sel.length) {
				return $cells.index($sel);
			}
			return 0;
		}

		// Synchronize active and completed states on all step items
		function syncState(activeIndex) {
			activeIndex = (typeof activeIndex === 'number' && activeIndex >= 0) ? activeIndex : getActiveIndex();
			var $items = $wrap.find('.ux-sp-item');

			$items.each(function(idx) {
				var $item = $(this);
				$item.removeClass('is-active is-completed');
				if (idx < activeIndex) {
					$item.addClass('is-completed').attr('aria-selected', 'false');
				} else if (idx === activeIndex) {
					$item.addClass('is-active').attr('aria-selected', 'true');
				} else {
					$item.attr('aria-selected', 'false');
				}
			});

			// Update continuous track line for numbered and dot styles
			if ((style === 'numbered' || style === 'dot') && totalSteps > 1) {
				var percent = (activeIndex / (totalSteps - 1)) * 100;
				$wrap.find('.ux-sp-track-fill').css('width', percent + '%');
			}

			// Update continuous slider thumb position for slider style
			if (style === 'slider' && totalSteps > 0) {
				var thumbOffset = activeIndex * 100;
				$wrap.find('.ux-sp-slider-thumb').css('transform', 'translateX(' + thumbOffset + '%)');
			}

			// Update progressive fill-up width for fill_up style
			if (style === 'fill_up' && totalSteps > 0) {
				var fillPercent = ((activeIndex + 1) / totalSteps) * 100;
				$wrap.find('.ux-sp-fillup-fill').css('width', fillPercent + '%');
			}
		}

		// Build and render node buttons
		function renderNodes() {
			flkty = $slider.data('flickity') || (window.Flickity ? window.Flickity.data($slider[0]) : null);

			// Count total steps accurately
			if (targetCls && targetCls !== '.' && $slider.find(targetCls).length > 0) {
				totalSteps = $slider.find(targetCls).length;
			} else if (flkty && flkty.cells && flkty.cells.length > 0) {
				totalSteps = flkty.cells.length;
			} else {
				var $slides = $slider.find('.flickity-slider > *');
				if (!$slides.length) {
					$slides = $slider.find('.banner, .col');
				}
				totalSteps = $slides.length || $slider.children().length || (rawLabels.length > 0 ? rawLabels.length : 3);
			}

			if (totalSteps <= 0) {
				totalSteps = 3;
			}

			var $itemsWrap = $wrap.find('.ux-sp-items');
			$itemsWrap.empty();

			for (var i = 0; i < totalSteps; i++) {
				var labelText = (rawLabels && rawLabels[i]) ? rawLabels[i] : '';
				var labelHtml = labelText ? '<span class="ux-sp-label">' + labelText + '</span>' : '';
				var $btn = $('<button type="button" class="ux-sp-item" role="tab" data-index="' + i + '"></button>');

				if (style === 'segmented') {
					$btn.append(
						'<div class="ux-sp-bar"><span class="ux-sp-bar-fill"></span></div>' +
						labelHtml
					);
				} else if (style === 'slider' || style === 'fill_up') {
					$btn.append(labelHtml);
				} else if (style === 'numbered') {
					var iconCheck = '<svg class="ux-sp-check" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
					$btn.append(
						'<div class="ux-sp-node"><span class="ux-sp-num">' + (i + 1) + '</span>' + iconCheck + '</div>' +
						labelHtml
					);
				} else { // dot
					$btn.append(
						'<div class="ux-sp-dot"></div>' +
						labelHtml
					);
				}

				$itemsWrap.append($btn);
			}

			// For slider style, calculate thumb width
			if (style === 'slider' && totalSteps > 0) {
				var thumbWidth = (100 / totalSteps);
				$wrap.find('.ux-sp-slider-thumb').css('width', thumbWidth + '%');
			}

			syncState(getActiveIndex());
		}

		// Bind event listeners to Flickity slider
		function bindSliderEvents() {
			function onSliderChange() {
				syncState(getActiveIndex());
			}

			// Click directly on continuous slider track
			$wrap.off('click', '.ux-sp-slider-bar').on('click', '.ux-sp-slider-bar', function(e) {
				var rect = this.getBoundingClientRect();
				var clickX = e.clientX - rect.left;
				var ratio = clickX / rect.width;
				var targetIdx = Math.min(totalSteps - 1, Math.max(0, Math.floor(ratio * totalSteps)));
				syncState(targetIdx);

				flkty = $slider.data('flickity') || (window.Flickity ? window.Flickity.data($slider[0]) : null);
				if (flkty && typeof flkty.select === 'function') {
					flkty.select(targetIdx);
				} else {
					$slider.find('.flickity-page-dots .dot').eq(targetIdx).trigger('click');
				}
			});

			// Click directly on progressive fill-up track
			$wrap.off('click', '.ux-sp-fillup-track').on('click', '.ux-sp-fillup-track', function(e) {
				var rect = this.getBoundingClientRect();
				var clickX = e.clientX - rect.left;
				var ratio = clickX / rect.width;
				var targetIdx = Math.min(totalSteps - 1, Math.max(0, Math.floor(ratio * totalSteps)));
				syncState(targetIdx);

				flkty = $slider.data('flickity') || (window.Flickity ? window.Flickity.data($slider[0]) : null);
				if (flkty && typeof flkty.select === 'function') {
					flkty.select(targetIdx);
				} else {
					$slider.find('.flickity-page-dots .dot').eq(targetIdx).trigger('click');
				}
			});

			// 1. Direct Flickity instance listener
			flkty = $slider.data('flickity') || (window.Flickity ? window.Flickity.data($slider[0]) : null);
			if (flkty) {
				if (typeof flkty.off === 'function') {
					flkty.off('select', onSliderChange);
					flkty.off('change', onSliderChange);
				}
				flkty.on('select', onSliderChange);
				flkty.on('change', onSliderChange);
			} else {
				// Retry if Flickity initializes asynchronously
				var checkCount = 0;
				var timer = setInterval(function() {
					flkty = $slider.data('flickity') || (window.Flickity ? window.Flickity.data($slider[0]) : null);
					if (flkty || checkCount > 35) {
						clearInterval(timer);
						if (flkty) {
							renderNodes();
							flkty.on('select', onSliderChange);
							flkty.on('change', onSliderChange);
						}
					}
					checkCount++;
				}, 150);
			}

			// 2. jQuery event listener on slider element
			$slider.off('select.flickity_sp change.flickity_sp').on('select.flickity_sp change.flickity_sp', onSliderChange);

			// 3. MutationObserver fallback watching for .is-selected class on slides
			var sliderEl = $slider[0];
			if (window.MutationObserver && sliderEl && !sliderEl._uxSpObserved) {
				sliderEl._uxSpObserved = true;
				var observer = new MutationObserver(function() {
					onSliderChange();
				});
				observer.observe(sliderEl, { subtree: true, attributes: true, attributeFilter: ['class'] });
			}
		}

		// Click step button to navigate slider
		$wrap.off('click', '.ux-sp-item').on('click', '.ux-sp-item', function(e) {
			e.preventDefault();
			var targetIdx = parseInt($(this).attr('data-index'), 10);
			if (isNaN(targetIdx)) {
				return;
			}

			// Instant UI update for snappy feedback
			syncState(targetIdx);

			// Command slider to select target index
			flkty = $slider.data('flickity') || (window.Flickity ? window.Flickity.data($slider[0]) : null);
			if (flkty && typeof flkty.select === 'function') {
				flkty.select(targetIdx);
			} else {
				// Fallback to clicking standard Flatsome pagination dot
				$slider.find('.flickity-page-dots .dot').eq(targetIdx).trigger('click');
			}
		});

		renderNodes();
		bindSliderEvents();
	}

	function initAll() {
		$('.ux-step-progress').each(function() {
			initStepProgress(this);
		});
	}

	$(document).ready(initAll);
	$(window).on('load', initAll);

	// UX Builder live canvas support
	if (window.UxBuilder && typeof window.UxBuilder.on === 'function') {
		window.UxBuilder.on('shortcode-attached', function() {
			initAll();
		});
	}

})(jQuery);
