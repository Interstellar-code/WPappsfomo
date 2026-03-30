//--------------------------------------------------------------------//
// Script related to statistics page
//--------------------------------------------------------------------//

;(function($) {

	var SocialSnapStatistics = {

		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function() {
			// Document ready
			$(document).ready( SocialSnapStatistics.ready );

			// Window load
			$(window).on( 'load', SocialSnapStatistics.load );

			// Window resize
			$(window).ss_smartresize( SocialSnapStatistics.resize );
		},

		//--------------------------------------------------------------------//
		// Events
		//--------------------------------------------------------------------//

		/**
		 * Document ready.
		 *
		 * @since 1.0.0
		 */
		ready: function() {
			SocialSnapStatistics.topPerformingHeight();
			SocialSnapStatistics.bindActions();
			SocialSnapStatistics.initChart();
			SocialSnapStatistics.initTopPerforming();
		},

		/**
		 * Window load.
		 *
		 * @since 1.0.0
		 */
		load: function() {

		},

		/**
		 * Window resize.
		 *
		 * @since 1.0.0
		 */
		resize: function() {
			SocialSnapStatistics.topPerformingHeight();
		},

		//--------------------------------------------------------------------//
		// Functions
		//--------------------------------------------------------------------//

		topPerformingHeight: function() {
			if ( $('#ss-analytics-stats-wrap').length ) {
				$('.ss-top-performing').css( 'height', $('#ss-analytics-stats-wrap').outerHeight() );
			}
		},

		/**
		 * Element bindings.
		 *
		 * @since 1.0.0
		 */
		 bindActions: function() {

		 	// Inner function to hide the dropdown field.
		 	var hide_dropdown = function(e) {
		 		if ( e === undefined ) {
		 			$('.ss-dropdown-filter').removeClass('ss-open');
		 		} else {
		 			if ( ! $( e.target ).hasClass('ss-dropdown-filter') && ! $( e.target ).offsetParent().hasClass('ss-dropdown-filter') ) {
		 				$('.ss-dropdown-filter').removeClass('ss-open');
		 			}
		 		}
		 	};

		 	// Inner function to show the dropdown field.
		 	var show_dropdown = function( $dropdown ) {
		 		hide_dropdown();
		 		$dropdown.addClass('ss-open');
		 	};

		 	// Show dropdown or hide if already visible
		 	$('.ss-dropdown-filter').click(function(e) {
		 		e.preventDefault();
		 		
		 		if ( $(this).hasClass('ss-open') ) {
		 			hide_dropdown();
		 		} else {
		 			show_dropdown( $(this) );	
		 		}
		 	});

		 	// Close dropdowns on outside click.
		 	$( document ).on( 'click', hide_dropdown );

		 	$('.ss-dropdown-filter li').click(function(e) {
		 		e.preventDefault();

		 		var $this 	= $(this);
		 		var $data 	= $this.data( 'value' );
		 		var $string = $this.html();

		 		$this.parent().find( 'li' ).removeClass( 'ss-current' );
		 		$this.addClass( 'ss-current' );
		 		$this.closest( '.ss-dropdown-filter' ).find( 'span' ).html( $string );

		 		// Reload the chart

		 	});
		},

		/**
		 * Init top performing posts
		 *
		 * @since 1.0.0
		 */
		initTopPerforming: function() {

			if ( ! $( '#ss-stats-top-performing' ).length ) {
				return false;
			}

			// Update post list
			var updateList = function( list_data ) {
				
				var data = {
					action  		: 'socialsnap_top_performing',
					ss_top_data  	: JSON.stringify(list_data),
					security		: $('#ss-stats-top-performing').data('nonce')
				};

		 		$.post(socialsnap_admin.ajaxurl, data, function(response) {
		 			if ( response.success ) {
		 				$('#ss-stats-top-performing').html(response.data.html);
		 			} else {
		 				// console.log(response);
		 			}
		 		});
			};

			// Default data
			var data = {
				'filter' 	: 'share_api',
				'post_type'	: 'post',
			};

			updateList( data );

			// Update filters 
			$('#ss-statistics').on('click', '#ss-top-performing-filter ul li', function(){
				
				var filter = $('#ss-top-performing-filter ul li.ss-current').data('value');

				if ( filter != data.filter ) {
					data.filter = filter;
					updateList( data );
				}
			});

			$('#ss-statistics').on('click', '#ss-top-post-type-filter ul li', function(){
				
				var filter = $('#ss-top-post-type-filter ul li.ss-current').data('value');

				if ( filter != data.post_type ) {
					data.post_type = filter;
					updateList( data );
				}
			});
		},


		/**
		 * Init analytics chart 
		 *
		 * @since 1.0.0
		 */
		initChart: function() {

			if ( ! $( '#ss-chart' ).length ) {
				return false;
			}

			var chart  = document.getElementById('ss-chart').getContext('2d');
			var chartInstance;

			Chart.defaults.global.tooltips.backgroundColor  = '#384654';
			Chart.defaults.global.tooltips.displayColors = false;
			Chart.defaults.global.tooltips.xPadding = 10;
			Chart.defaults.global.tooltips.yPadding = 10;

			var updateChart = function( chart_data ) {

				$('.ss-analytics-selects .ss-dropdown-filter').removeClass('disabled');

				if ( 'ctt' == chart_data.type ) {

					$('#ss-locations-filter').addClass('disabled');
					$('#ss-total-filter').addClass('disabled');

					chart_data.location = 'all';
					chart_data.display = 'total';

					$('#ss-locations-filter [data-value="' + chart_data.location + '"]').click();
					$('#ss-total-filter [data-value="' + chart_data.display + '"]').click();

				} else if ( 'like' == chart_data.type ) {

					$('#ss-total-filter').addClass('disabled');

					chart_data.display = 'total';
					
					$('#ss-total-filter [data-value="' + chart_data.display + '"]').click();
				} else if ( 'share_api' == chart_data.type ) {
					
					$('#ss-locations-filter').addClass('disabled');

					chart_data.location = 'all';
					
					$('#ss-locations-filter [data-value="' + chart_data.location + '"]').click();
				}

		 		var data = {
		 			action  		: 'socialsnap_statistics_chart',
		 			ss_stats_data   : JSON.stringify(chart_data),
		 			security		: $('#ss-analytics-stats-wrap').data('nonce')
		 		};

		 		$.post(socialsnap_admin.ajaxurl, data, function(response) {
		 			if ( response.success ) {
		 				
		 				if ( 'undefined' !== typeof chartInstance ) {
		 					chartInstance.destroy();
		 				}

		 				// Create chart
	 					chartInstance = new Chart(chart, {
							type: 		response.data.type,
							data: 		JSON.parse(response.data.data),
							options: 	JSON.parse(response.data.options),
						});		 					

	 					var chartMax;
	 					var newStepSize;

	 					// if ( chartInstance.config.type == 'bar' || chartInstance.config.type == 'line' ) {
	 						// Calculate new step size depending on max value
	 						chartMax = chartInstance.scales['y-axis-0'].max;
	 						newStepSize = Math.ceil( chartMax / 10 );

	 						// Update new step size
	 						chartInstance.config.options.scales.yAxes[0].ticks.stepSize = newStepSize;
	 					// } else {
	 					// 	// Calculate new step size depending on max value
	 					// 	chartMax = chartInstance.scales['x-axis-0'].max;
	 					// 	newStepSize = Math.ceil( chartMax / 10 );

	 					// 	// Update new step size
	 					// 	chartInstance.config.options.scales.xAxes[0].ticks.stepSize = newStepSize;
	 					// }

	 					chartInstance.update();

		 			} else {
		 				// console.log(response);
		 			}
		 		});
			};

			var data = {
				'location'	: 'all',
				'time'		: 'past-week',
				'display'	: 'individual-networks',
				'type'		: 'share_api'
			};

			updateChart( data );

			$('#ss-statistics').on('click', '#ss-time-filter ul li', function(){
				var time_filter = $('#ss-time-filter ul li.ss-current').data('value');

				if ( time_filter != data.time ) {
					data.time = time_filter;
					updateChart( data );
				}
			});

			$('#ss-statistics').on('click', '#ss-locations-filter ul li', function(){
				var filter = $('#ss-locations-filter ul li.ss-current').data('value');

				if ( filter != data.location ) {
					data.location = filter;
					updateChart( data );
				}
			});

			$('#ss-statistics').on('click', '#ss-total-filter ul li', function(){
				var filter = $('#ss-total-filter ul li.ss-current').data('value');

				if ( filter != data.display ) {
					data.display = filter;
					updateChart( data );
				}
			});

			$('#ss-statistics').on('click', '#ss-type-filter ul li', function(){
				var filter = $('#ss-type-filter ul li.ss-current').data('value');

				if ( filter != data.type ) {
					data.type = filter;
					updateChart( data );
				}
			});
		},
	};

	SocialSnapStatistics.init();
	window.socialsnapstatistics = SocialSnapStatistics;

})(jQuery);