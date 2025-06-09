/**
 * VikBooking - DatesRangePicker v1.2.3.
 * Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * https://vikwp.com | https://e4j.com | https://e4jconnect.com
 * 
 * Forked from MultiDatesPicker v1.6.6
 * https://dubrox.github.io/Multiple-Dates-Picker-for-jQuery-UI
 */
(function(factory) {
	if (typeof define === 'function' && define.amd) {
		define(['jquery', 'jquery-ui-dist'], factory);
	} else {
		factory(jQuery);
	}
}(function($) {
	$.extend($.ui, { vboMultiDatesPicker: { version: '1.6.6' } });

	$.fn.vboMultiDatesPicker = function(method) {
		var mdp_arguments = arguments;
		var ret = this;
		var today_date = new Date;
		var day_zero = new Date(0);
		var mdp_events = {};

		function removeDate(date, type) {
			if (!type) {
				type = 'picked';
			}
			date = dateConvert.call(this, date);
			for (var i = 0; i < this.vboMultiDatesPicker.dates[type].length; i++)
				if (!methods.compareDates(this.vboMultiDatesPicker.dates[type][i], date)) {
					return this.vboMultiDatesPicker.dates[type].splice(i, 1).pop();
				}
		}
		function removeIndex(index, type) {
			if (!type) {
				type = 'picked';
			}
			return this.vboMultiDatesPicker.dates[type].splice(index, 1).pop();
		}
		function addDate(date, type, no_sort) {
			if (!type) {
				type = 'picked';
			}
			date = dateConvert.call(this, date);

			date.setHours(0);
			date.setMinutes(0);
			date.setSeconds(0);
			date.setMilliseconds(0);
			
			if (methods.gotDate.call(this, date, type) === false) {
				this.vboMultiDatesPicker.dates[type].push(date);
				if (!no_sort) {
					this.vboMultiDatesPicker.dates[type].sort(methods.compareDates);
				}
			}
		}
		function sortDates(type) {
			if (!type) {
				type = 'picked';
			}
			this.vboMultiDatesPicker.dates[type].sort(methods.compareDates);
		}
		function dateConvert(date, desired_type, date_format) {
			if (!desired_type) {
				desired_type = 'object';
			}
			return methods.dateConvert.call(this, date, desired_type, date_format);
		}
		
		var methods = {
			init: function(options) {
				var $this = $(this);
				this.vboMultiDatesPicker.changed = false;
				
				var mdp_events = {
					beforeShow: function(input, inst) {
						this.vboMultiDatesPicker.changed = false;
						if (this.vboMultiDatesPicker.originalBeforeShow) {
							this.vboMultiDatesPicker.originalBeforeShow.call(this, input, inst);
						}
					},
					onSelect: function(dateText, inst) {
						var $this = $(this);
						this.vboMultiDatesPicker.changed = true;
						
						if (dateText) {
							$this.vboMultiDatesPicker('toggleDate', dateText);
							this.vboMultiDatesPicker.changed = true;
						}
						
						if (this.vboMultiDatesPicker.mode == 'normal' && this.vboMultiDatesPicker.pickableRange) {
							if (this.vboMultiDatesPicker.dates.picked.length > 0) {
								var min_date = this.vboMultiDatesPicker.dates.picked[0],
									max_date = new Date(min_date.getTime());

								methods.sumDays(max_date, this.vboMultiDatesPicker.pickableRange-1);

								// counts the number of disabled dates in the range
								if (this.vboMultiDatesPicker.adjustRangeToDisabled) {
									var c_disabled, 
										disabled = this.vboMultiDatesPicker.dates.disabled.slice(0);
									do {
										c_disabled = 0;
										for (var i = 0; i < disabled.length; i++) {
											if (disabled[i].getTime() <= max_date.getTime()) {
												if ((min_date.getTime() <= disabled[i].getTime()) && (disabled[i].getTime() <= max_date.getTime()) ) {
													c_disabled++;
												}
												disabled.splice(i, 1);
												i--;
											}
										}
										max_date.setDate(max_date.getDate() + c_disabled);
									} while(c_disabled != 0);
								}
								
								if (this.vboMultiDatesPicker.maxDate && (max_date > this.vboMultiDatesPicker.maxDate)) {
									max_date = this.vboMultiDatesPicker.maxDate;
								}

								$this.datepicker('option', 'minDate', min_date).datepicker('option', 'maxDate', max_date);
							} else {
								$this.datepicker('option', 'minDate', this.vboMultiDatesPicker.minDate).datepicker('option', 'maxDate', this.vboMultiDatesPicker.maxDate);
							}
						}

						if (this.vboMultiDatesPicker.originalOnSelect && dateText) {
							this.vboMultiDatesPicker.originalOnSelect.call(this, dateText, inst);
						}
					},
					beforeShowDay: function(date) {
						var $this = $(this),
							gotThisDate = $this.vboMultiDatesPicker('gotDate', date) !== false,
							isDisabledCalendar = $this.datepicker('option', 'disabled'),
							isDisabledDate = $this.vboMultiDatesPicker('gotDate', date, 'disabled') !== false,
							areAllSelected = this.vboMultiDatesPicker.maxPicks <= this.vboMultiDatesPicker.dates.picked.length;

						var bsdReturn = [true, '', null];
						if (this.vboMultiDatesPicker.originalBeforeShowDay) {
							bsdReturn = this.vboMultiDatesPicker.originalBeforeShowDay.call(this, date);
						}

						bsdReturn[1] = gotThisDate ? 'ui-state-highlight ' + bsdReturn[1] : bsdReturn[1];
						bsdReturn[0] = bsdReturn[0] && !(isDisabledCalendar || isDisabledDate || (areAllSelected && !bsdReturn[1]));
						return bsdReturn;
					}
				};

				// value needs to be extracted before datepicker is initiated
				if ($this.val()) {
					var inputDates = $this.val();
				}

				if (options) {
					// value needs to be extracted before datepicker is initiated
					if (options.separator) {
						this.vboMultiDatesPicker.separator = options.separator;
					}
					if (!this.vboMultiDatesPicker.separator) {
						this.vboMultiDatesPicker.separator = ', ';
					}

					this.vboMultiDatesPicker.originalBeforeShow = options.beforeShow;
					this.vboMultiDatesPicker.originalOnSelect = options.onSelect;
					this.vboMultiDatesPicker.originalBeforeShowDay = options.beforeShowDay;
					this.vboMultiDatesPicker.originalOnClose = options.onClose;

					// datepicker init
					$this.datepicker(options);

					this.vboMultiDatesPicker.minDate = $.datepicker._determineDate(this, options.minDate, null);
					this.vboMultiDatesPicker.maxDate = $.datepicker._determineDate(this, options.maxDate, null);
					if (options.addDates) {
						methods.addDates.call(this, options.addDates);
					}

					if (options.addDisabledDates) {
						methods.addDates.call(this, options.addDisabledDates, 'disabled');
					}

					methods.setMode.call(this, options);
				} else {
					$this.datepicker();
				}
				$this.datepicker('option', mdp_events);

				// adds any dates found in the input or alt field
				if (inputDates) $this.vboMultiDatesPicker('value', inputDates);

				// generates the new string of added dates
				var inputs_values = $this.vboMultiDatesPicker('value');

				// fills the input field back with all the dates in the calendar
				$this.val(inputs_values);

				// Fixes the altField filled with defaultDate by default
				var altFieldOption = $this.datepicker('option', 'altField');
				if (altFieldOption) $(altFieldOption).val(inputs_values);

				// Updates the calendar view
				$this.datepicker('refresh');
			},
			compareDates: function(date1, date2) {
				date1 = dateConvert.call(this, date1);
				date2 = dateConvert.call(this, date2);
				// return > 0 means date1 is later than date2
				// return == 0 means date1 is the same day as date2
				// return < 0 means date1 is earlier than date2
				var diff = date1.getFullYear() - date2.getFullYear();
				if (!diff) {
					diff = date1.getMonth() - date2.getMonth();
					if (!diff) {
						diff = date1.getDate() - date2.getDate();
					}
				}
				return diff;
			},
			sumDays: function(date, n_days) {
				var origDateType = typeof date;
				obj_date = dateConvert.call(this, date);
				obj_date.setDate(obj_date.getDate() + n_days);
				return dateConvert.call(this, obj_date, origDateType);
			},
			dateConvert: function(date, desired_format, dateFormat) {
				var from_format = typeof date;
				var $this = $(this);

				if (from_format == desired_format) {
					if (from_format == 'object') {
						try {
							date.getTime();
						} catch (e) {
							$.error('Received date is in a non supported format!');
							return false;
						}
					}
					return date;
				}

				if (typeof date == 'undefined') {
					date = new Date(0);
				}

				if (desired_format != 'string' && desired_format != 'object' && desired_format != 'number')
					$.error('Date format "'+ desired_format +'" not supported!');
				
				if (!dateFormat) {
					var dp_dateFormat = $this.datepicker('option', 'dateFormat');
					if (dp_dateFormat) {
						dateFormat = dp_dateFormat;
					} else {
						dateFormat = $.datepicker._defaults.dateFormat;
					}
				}
				
				// converts to object as a neutral format
				switch (from_format) {
					case 'object': break;
					case 'string': date = $.datepicker.parseDate(dateFormat, date); break;
					case 'number': date = new Date(date); break;
					default: $.error('Conversion from "'+ from_format +'" format not allowed on jQuery.vboMultiDatesPicker');
				}
				// then converts to the desired format
				switch (desired_format) {
					case 'object': return date;
					case 'string': return $.datepicker.formatDate(dateFormat, date);
					case 'number': return date.getTime();
					default: $.error('Conversion to "'+ desired_format +'" format not allowed on jQuery.vboMultiDatesPicker');
				}
				return false;
			},
			gotDate: function(date, type) {
				if (!type) {
					type = 'picked';
				}
				for (var i = 0; i < this.vboMultiDatesPicker.dates[type].length; i++) {
					if (methods.compareDates.call(this, this.vboMultiDatesPicker.dates[type][i], date) === 0) {
						return i;
					}
				}
				return false;
			},
			value: function(value) {
				if (value && typeof value == 'string') {
					methods.addDates.call(this, value.split(this.vboMultiDatesPicker.separator));
				} else {
					var dates = methods.getDates.call(this, 'string');
					return dates.length
						? dates.join(this.vboMultiDatesPicker.separator)
						: '';
				}
			},
			getDates: function(format, type) {
				if (!format) {
					format = 'string';
				}
				if (!type) {
					type = 'picked';
				}
				switch (format) {
					case 'object':
						return this.vboMultiDatesPicker.dates[type];
					case 'string':
					case 'number':
						var o_dates = [];
						for (var i = 0; i < this.vboMultiDatesPicker.dates[type].length; i++)
							o_dates.push(
								dateConvert.call(
									this, 
									this.vboMultiDatesPicker.dates[type][i], 
									format
								)
							);
						return o_dates;
					
					default: $.error('Format "'+format+'" not supported!');
				}
			},
			addDates: function(dates, type) {
				if (dates.length > 0) {
					if (!type) {
						type = 'picked';
					}
					switch (typeof dates) {
						case 'object':
						case 'array':
							if (dates.length) {
								for (var i = 0; i < dates.length; i++)
									addDate.call(this, dates[i], type, true);
								sortDates.call(this, type);
								break;
							} // else does the same as 'string'
						case 'string':
						case 'number':
							addDate.call(this, dates, type);
							break;
						default: 
							$.error('Date format "'+ typeof dates +'" not allowed on jQuery.vboMultiDatesPicker');
					}
				} else {
					$.error('Empty array of dates received.');
				}
			},
			removeDates: function(dates, type) {
				if (!type) {
					type = 'picked';
				}
				var removed = [];
				if (Object.prototype.toString.call(dates) === '[object Array]') {
					dates.sort(function(a,b) {return b-a});
					for (var i = 0; i < dates.length; i++) {
						removed.push(removeDate.call(this, dates[i], type));
					}
				} else {
					removed.push(removeDate.call(this, dates, type));
				}
				return removed;
			},
			removeIndexes: function(indexes, type) {
				if (!type) {
					type = 'picked';
				}
				var removed = [];
				if (Object.prototype.toString.call(indexes) === '[object Array]') {
					indexes.sort(function(a,b) {return b-a});
					for (var i = 0; i < indexes.length; i++) {
						removed.push(removeIndex.call(this, indexes[i], type));
					}
				} else {
					removed.push(removeIndex.call(this, indexes, type));
				}
				return removed;
			},
			resetDates: function (type) {
				if (!type) {
					type = 'picked';
				}
				this.vboMultiDatesPicker.dates[type] = [];
			},
			toggleDate: function(date, type) {
				if (!type) {
					type = 'picked';
				}
				switch (this.vboMultiDatesPicker.mode) {
					case 'daysRange':
						this.vboMultiDatesPicker.dates[type] = []; // deletes all picked/disabled dates
						var end = this.vboMultiDatesPicker.autoselectRange[1];
						var begin = this.vboMultiDatesPicker.autoselectRange[0];
						if (end < begin) {
							end = this.vboMultiDatesPicker.autoselectRange[0];
							begin = this.vboMultiDatesPicker.autoselectRange[1];
						}
						for (var i = begin; i < end; i++) {
							methods.addDates.call(this, methods.sumDays.call(this,date, i), type);
						}
						break;
					default:
						if (methods.gotDate.call(this, date) === false) {
							methods.addDates.call(this, date, type);
						} else {
							methods.removeDates.call(this, date, type);
						}
						break;
				}
			},
			setMode: function(options) {
				var $this = $(this);
				if (options.mode) {
					this.vboMultiDatesPicker.mode = options.mode;
				}

				switch (this.vboMultiDatesPicker.mode) {
					case 'normal':
						for (var option in options) {
							switch (option) {
								case 'maxPicks':
								case 'minPicks':
								case 'pickableRange':
								case 'adjustRangeToDisabled':
									this.vboMultiDatesPicker[option] = options[option];
									break;
								// default: $.error('Option ' + option + ' ignored for mode "'.options.mode.'".');
							}
						}
					break;
					case 'daysRange':
					case 'weeksRange':
						var mandatory = 1;
						for (option in options) {
							switch (option) {
								case 'autoselectRange':
									mandatory--;
								case 'pickableRange':
								case 'adjustRangeToDisabled':
									this.vboMultiDatesPicker[option] = options[option];
									break;
								// default: $.error('Option ' + option + ' does not exist for setMode on jQuery.vboMultiDatesPicker');
							}
						}
						if (mandatory > 0) {
							$.error('Some mandatory options not specified!');
						}
					break;
				}

				if (mdp_events.onSelect) {
					mdp_events.onSelect();
				}
			},
			destroy: function() {
				this.vboMultiDatesPicker = null;
				$(this).datepicker('destroy');
			}
		};

		this.each(function() {
			var $this = $(this);
			if (!this.vboMultiDatesPicker) {
				this.vboMultiDatesPicker = {
					dates: {
						picked: [],
						disabled: []
					},
					mode: 'normal',
					adjustRangeToDisabled: true
				};
			}

			if (methods[method]) {
				var exec_result = methods[method].apply(this, Array.prototype.slice.call(mdp_arguments, 1));
				switch (method) {
					case 'removeDates':
					case 'removeIndexes':
					case 'resetDates':
					case 'toggleDate':
					case 'addDates':
						var altField = $this.datepicker('option', 'altField');
						var dates_string = methods.value.call(this);
						if (altField !== undefined && altField != '') {
							$(altField).val(dates_string);
						}
						$this.val(dates_string);

						$.datepicker._refreshDatepicker(this);
				}
				switch (method) {
					case 'removeDates':
					case 'getDates':
					case 'gotDate':
					case 'sumDays':
					case 'compareDates':
					case 'dateConvert':
					case 'value':
						ret = exec_result;
				}
				return exec_result;
			} else if (typeof method === 'object' || !method) {
				return methods.init.apply(this, mdp_arguments);
			} else {
				$.error('Method ' +  method + ' does not exist on jQuery.vboMultiDatesPicker');
			}
			return false;
		}); 

		return ret;
	};

	$.vboMultiDatesPicker = {version: false};
	$.vboMultiDatesPicker.initialized = false;
	$.vboMultiDatesPicker.uuid = new Date().getTime();
	$.vboMultiDatesPicker.version = $.ui.vboMultiDatesPicker.version;
	
	/**
	 * Allows MDP not to hide everytime a date is picked.
	 */
	$(function() {
		/**
		 * Use a constant instead of a property object to break the loop caused
		 * my modal (AJAX) duplicate rendering.
		 */
		// $.vboMultiDatesPicker._hideDatepicker = $.datepicker._hideDatepicker;
		const vboMultiDatesPicker_hideDatepicker = $.datepicker._hideDatepicker;
		$.datepicker._hideDatepicker = function() {
			/**
			 * Prevent errors with inline datepickers when _curInst is null.
			 * 
			 * @see 	modified from original source code
			 */
			if (!this._curInst) {
				return;
			}

			var target = this._curInst.input[0];
			var mdp = target.vboMultiDatesPicker;
			if (!mdp || (this._curInst.inline === false && !mdp.changed)) {
				return vboMultiDatesPicker_hideDatepicker.apply(this, arguments);
			} else {
				mdp.changed = false;
				$.datepicker._refreshDatepicker(target);
				return;
			}
		};
	});

	/**
	 * VikBooking - DatesRangePicker declaration.
	 * 
	 * @param 	string|object 	checkin 	Check-in input field selector or element.
	 * @param 	string|object 	checkout 	Check-out input field selector or element.
	 * @param 	object			options 	Datepicker options.
	 */
	$.vboDatesRangePicker = function(checkin, checkout, options) {
		if (typeof checkin === 'string') {
			checkin = $(checkin);
		}
		if (typeof checkout === 'string') {
			checkout = $(checkout);
		}

		if (!checkin || !checkout || !checkin.length || !checkout.length) {
			throw new Error('Invalid vboDatesRangePicker check-in/check-out selectors.');
		}

		// ensure the current jQuery version is supported (v1.x is NOT supported)
		let jq_version = $.fn.jquery || '3';
		if (typeof jq_version === 'string' && jq_version.substring(0, 1) === '1') {
			// fallback to regular datepicker
			$(checkin).datepicker(options);
			$(checkout).datepicker(options);
			// abort
			throw new Error('Unsupported jQuery version');
		}

		if (typeof options !== 'object') {
			options = {};
		}

		// get the input fields for check-in and check-out
		const inputCheckin = options?.altFields?.checkin ? $(options.altFields.checkin) : checkin;
		const inputCheckout = options?.altFields?.checkout ? $(options.altFields.checkout) : checkout;

		// get currently populated dates, if any
		let prevCheckinDate = inputCheckin.val();
		let prevCheckoutDate = inputCheckout.val();
		const dates = [prevCheckinDate, prevCheckoutDate].filter(d => d);

		// date range picker configuration
		// 3 picks needed to rectify the completed selection
		options.maxPicks = 3;
		options.addDates = dates.length ? dates : null;

		if (typeof options.altFormat !== 'undefined') {
			// option not supported
			delete options.altFormat;
		}

		// default configuration arguments
		if (typeof options.numberOfMonths === 'undefined') {
			options.numberOfMonths = 2;
		}

		if (typeof options.minDate === 'undefined') {
			options.minDate = new Date;
		} else {
			// ensure minDate is an object
			options.minDate = $(this).vboDatesRangePicker('convertPeriod', options.minDate);
		}

		const _onSelect = options.onSelect ?? null;
		options.onSelect = function(date, instance) {
			let pickedDates = $(this).vboMultiDatesPicker('getDates');

			setTimeout(() => {
				// clear the active status to prevent conflicts in case of deselection
				let dateObj = $.datepicker.parseDate($(this).datepicker('option', 'dateFormat'), date);
				const dayCell = $('.ui-datepicker').find('td.date-' + dateObj.getFullYear() + '-' + dateObj.getMonth() + '-' + dateObj.getDate());
				dayCell.find('.ui-state-active').removeClass('ui-state-active');
				dayCell.removeClass('ui-datepicker-current-day');
				// resolve possible conflicts with tooltip
				dayCell.trigger('mouseenter');
			}, 10);

			if (pickedDates.length > 2) {
				// remove all the dates from the selection, except for the last picked one
				$(this).vboMultiDatesPicker('removeDates', pickedDates.filter(d => d != date));

				// adjust the picked dates
				pickedDates = [date];
			}

			// populate checkin and checkout fields
			if (pickedDates.length == 1) {
				inputCheckin.val(pickedDates[0]);
				inputCheckout.val('');
			} else if (pickedDates.length == 2) {
				inputCheckin.val(pickedDates[0]);
				inputCheckout.val(pickedDates[1]);
			} else {
				inputCheckin.val('');
				inputCheckout.val('');
			}

			/**
			 * @see 	Natively inline datepickers not rendered on input fields cannot be hid!
			 */

			// prevent the datepicker auto-hiding after selecting a date
			instance.inline = true;

			setTimeout(() => {
				// remove the inline state after completing the selection process,
				// this aims to re-allow the datepicker closure when clicking outside
				instance.inline = false;
			});

			if (_onSelect) {
				// propagate selection behavior
				_onSelect(date, instance);
			}

			if (pickedDates.length == 2) {
				setTimeout(() => {
					// prepare the very next click outside to hide the datepicker rather than 2 next clicks
					checkin.datepicker('hide');
				});

				if (options?.environment?.autoHide) {
					// do not allow to rectify the selection, but rather hide the DRP on selection completed
					setTimeout(() => {
						checkin.datepicker('hide');
						checkin.trigger('blur');
					}, (options?.environment?.autoHideDelay || 100));
				}
			}
		};

		const _beforeShowDay = options.beforeShowDay ?? null;
		options.beforeShowDay = function(date) {
			// get the DRP configuration
			let config = $(this).vboDatesRangePicker('drpoption');

			// current dates
			const pickedDates = $(this).vboMultiDatesPicker('getDates');
			const isPicked = pickedDates.some(dt => dt == date);

			// in case the date has been selected, use it as min date
			// in case the date has been deselected, use the first date in the array (config min date if empty selection)
			let minDate = isPicked ? date : (pickedDates[0] || config.minDate);
			// in case we have 0 or 2 selected dates, ignore the minimum date
			// in case we have 1 or 3 selected dates, use the new date
			minDate = pickedDates.length % 2 ? minDate : config.minDate;

			// class identifier to easily select the table cell from a date object
			const dateIdClass = 'date-' + date.getFullYear() + '-' + date.getMonth() + '-' + date.getDate();

			// in case of past date, disable cell
			if ($(this).vboDatesRangePicker('compareDates', date, minDate) < 0) {
				return [false, 'date-past ' + dateIdClass, null];
			}

			// build date return values
			let returnVal = [true, '', null];

			// whether the date is known
			let knownDate = false;

			if (pickedDates.length > 0) {
				// date format
				let dateFormat = $(this).datepicker('option', 'dateFormat');

				// get check-in date
				let checkinDate = $.datepicker.parseDate(dateFormat, pickedDates[0]);

				if ($(this).vboDatesRangePicker('compareDates', checkinDate, date) == 0) {
					// parsing the check-in date
					let checkinDayTitle = config?.labels?.checkin || null;
					if (pickedDates.length == 1 && parseInt((config?.checkoutConstraints?.minStayNights || 0)) > 1) {
						if (typeof config?.labels?.minStayNights === 'function') {
							checkinDayTitle = config.labels.minStayNights.call(null, config.checkoutConstraints.minStayNights);
						} else if (typeof config?.labels?.minStayNights === 'string') {
							checkinDayTitle = config.labels.minStayNights;
						}
					}
					returnVal = [true, 'checkin-date' + (pickedDates.length == 1 ? ' without-checkout-date' : ''), checkinDayTitle];
					knownDate = true;
				} else if (pickedDates.length == 2) {
					// get check-out date
					let checkoutDate = $.datepicker.parseDate(dateFormat, pickedDates[1]);
			  
					if ($(this).vboDatesRangePicker('compareDates', checkoutDate, date) == 0) {
						// parsing the check-out date
						let checkoutDayTitle = config?.labels?.checkout || null;
						returnVal = [true, 'checkout-date', checkoutDayTitle];
						knownDate = true;
					} else if (checkinDate < date && date < checkoutDate) {
						// parsing a date between the check-in and the check-out
						returnVal = [true, 'checkin-checkout-inner', null];
						knownDate = true;
					}
				}
			}

			if (_beforeShowDay && !knownDate) {
				// call the registered methods for validating the current date
				returnVal = _beforeShowDay(date);
			}

			if (returnVal[2]) {
				// in case of tooltip title text, append a specific class
				returnVal[1] += ' date-tooltip';
				if (returnVal[2].length > 20) {
					// this is a large tooltip text
					returnVal[1] += ' date-tooltip-large';
				}
				// determine the tooltip position
				let firstwday = $(this).vboDatesRangePicker('option', 'firstDay');
				if (date.getDay() == firstwday) {
					// first day of week
					returnVal[1] += ' date-tooltip-firstwday';
				} else {
					// calculate last week-day index
					let lastwday = firstwday - 1;
					lastwday = lastwday < 0 ? 6 : lastwday;
					if (date.getDay() == lastwday) {
						// last day of week
						returnVal[1] += ' date-tooltip-lastwday';
					}
				}
			}

			// append the class for this exact day and trim
			returnVal[1] += ' ' + dateIdClass;
			returnVal[1] = returnVal[1].trim();

			return returnVal;
		}

		/**
		 * The beforeShow function will NOT be called for inline datepickers.
		 */
		const _beforeShow = options.beforeShow ?? null;
		const _mouseEnter = (inlineElement) => {
			// determine the proper datepicker container
			let dpContainer = typeof inlineElement === 'undefined' ? $('.ui-datepicker').not('.ui-datepicker-inline') : $(inlineElement).find('.ui-datepicker');

			dpContainer.on('mouseenter', 'td', function() {
				let pickedDates = [];

				try {
					pickedDates = $(checkin).vboMultiDatesPicker('getDates');
				} catch (e) {
					// silently abort in case of elements not being controlled through DRP
					return;
				}

				if ($(this).hasClass('date-tooltip')) {
					let title = $(this).attr('title');
					if (title) {
						// replace native title attribute with data attribute for tooltip
						$(this).attr('title', '');
						$(this).attr('data-title', title);
					}
				}

				if (pickedDates.length == 1) {
					// get the proper date format in case it differs from regional values
					let dateFormat = $(checkin).datepicker('option', 'dateFormat') || options.dateFormat;
					const checkinDate = $.datepicker.parseDate(dateFormat, pickedDates[0]);

					const checkoutDate = new Date;
					// ensure to set the date to 1 first, in case the month to set does not have this day (i.e Jan 30, Feb 30 not existing)
					// without doing so, the Date object constructed would get an extra month, and so the date would not be the desired one
					checkoutDate.setDate(1);
					checkoutDate.setFullYear($(this).data('year'));
					checkoutDate.setMonth($(this).data('month'));
					checkoutDate.setDate($(this).text());

					$('.checkout-date').removeClass('checkout-date date-will');
					$('.checkin-checkout-inner').removeClass('checkin-checkout-inner');

					if ($(checkin).vboDatesRangePicker('compareDates', checkoutDate, checkinDate) == 0) {
						return;
					}

					$('.date-' + checkoutDate.getFullYear() + '-' + checkoutDate.getMonth() + '-' + checkoutDate.getDate()).addClass('checkout-date date-will');
					checkoutDate.setDate(checkoutDate.getDate() - 1);

					while ($(checkin).vboDatesRangePicker('compareDates', checkoutDate, checkinDate) > 0) {
						$('.date-' + checkoutDate.getFullYear() + '-' + checkoutDate.getMonth() + '-' + checkoutDate.getDate()).addClass('checkin-checkout-inner');
						checkoutDate.setDate(checkoutDate.getDate() - 1);
					}
				}
			});
		}
		options.beforeShow = function(input, instance) {
			// register mouseenter event on date cells
			_mouseEnter();

			if (_beforeShow) {
				// propagate show behavior
				_beforeShow(input, instance);
			}
		};

		/**
		 * The onUpdateDatepicker function will NOT be called for inline datepickers.
		 */
		const _onUpdateDatepicker = options.onUpdateDatepicker ?? null;
		options.onUpdateDatepicker = function(instance) {
			// get the DRP configuration
			let config = $(this).vboDatesRangePicker('drpoption');

			if (typeof config?.bottomCommands === 'object') {
				if ($('.vbo-drp-commands-bottom').length) {
					return;
				}

				// build bottom commands
				let btmCommands = $('<div></div>').addClass('vbo-drp-commands-bottom');

				// clear dates
				let clearCommand = $('<div></div>')
					.addClass('vbo-drp-command vbo-drp-command-clear')
					.append(
						$('<a></a>')
							.attr('href', 'JavaScript: void(0);')
							.text((config.bottomCommands?.clear || 'Clear dates'))
							.on('click', () => {
								$(this).vboDatesRangePicker('setDates', []);
								$('#ui-datepicker-div').find('.ui-state-active').removeClass('ui-state-active');
								$('#ui-datepicker-div').find('.ui-datepicker-current-day').removeClass('ui-datepicker-current-day');
								if (typeof config.bottomCommands?.onClear === 'function') {
									// invoke the provided method in case something needs to be cleared from the UI
									config.bottomCommands.onClear.call(this);
								}
							})
					);
				btmCommands.append(clearCommand);

				// close DRP
				let closeCommand = $('<div></div>')
					.addClass('vbo-drp-command vbo-drp-command-close')
					.append(
						$('<button></button>')
							.addClass('btn btn-small ' + (config?.environment?.section === 'admin' ? 'vbo-dark-btn' : 'vbo-pref-color-btn'))
							.attr('type', 'button')
							.text((config.bottomCommands?.close || 'Close'))
							.on('click', () => {
								$(this).datepicker('hide');
							})
					);
				btmCommands.append(closeCommand);

				// append bottom commands to DRP
				$('#ui-datepicker-div').append(btmCommands);
			}

			if (_onUpdateDatepicker) {
				// propagate onUpdateDatepicker behavior
				_onUpdateDatepicker(instance);
			}
		};

		/**
		 * Define the onClose behavior.
		 */
		const _onClose = options.onClose ?? null;
		options.onClose = function(dateText, instance) {
			// unregister mouseenter event from every non-inline datepicker
			$('.ui-datepicker').not('.ui-datepicker-inline').off('mouseenter', 'td');

			if (_onClose) {
				// propagate close behavior
				_onClose(dateText, instance);
			}
		};

		// check for regional default settings
		if (options?.environment?.section === 'admin') {
			let regionalDefaults = $.datepicker.regional['vikbooking'];
			if (typeof regionalDefaults === 'object') {
				// merge regional settings with DRP options
				options = Object.assign(regionalDefaults, options);
			}
		}

		// determine whether the datepicker is inline
		let isInline = !$(checkin).is('input[type="text"]');

		// instantiate multi-dates picker for a single range of dates
		$(checkin).vboMultiDatesPicker(options);

		// trigger datepicker opening when focusing the check-out field too
		$(checkout).on('focus', () => {
			$(checkin).datepicker('show');
		});

		// restore the initial check-in value
		inputCheckin.val(prevCheckinDate);

		if (isInline) {
			// manually register the mouseenter event since the beforeShow won't run
			setTimeout(() => {
				_mouseEnter(checkin);
			}, 100);
		} else {
			// add class to the check-out field to identify the trigger
			$(checkout).addClass('vbo-drp-inp-trigger');
		}
	}

	/**
	 * VikBooking - DatesRangePicker jQuery plugin.
	 * 
	 * @param 	any 	method 		Either string for getter/setter, or object to start the DRP.
	 * @param 	any 	options 	Initialization object options, or mixed value for setter.
	 * @param 	any 	setvalue 	The value to set within the datepicker in case of "option" setter.
	 */
	$.fn.vboDatesRangePicker = function(method, options, setvalue) {
		if (!method) {
			// initialize the DRP
			method = {};
		}

		// immediately exit in case of no elements found
		if ($(this).length == 0) {
			return this;
		}

		/**
		 * Initializes the DRP calendar.
		 */
		const init = (options) => {
			if (!options.dateFormat) {
				options.dateFormat = 'yy-mm-dd';
			}

			// set DRP cloned configuration data
			drpConfig(Object.assign({}, options));

			// element selector
			let that = $(this);

			// handle native datepicker method beforeShowDay
			if (options.beforeShowDay) {
				// get registered callbacks
				let beforeShowDayCheckin = options.beforeShowDay.checkin;
				let beforeShowDayCheckout = options.beforeShowDay.checkout;

				// delete native property
				delete options.beforeShowDay;

				// register native property
				options.beforeShowDay = (date) => {
					// get currently selected dates
					let pickedDates = that.vboMultiDatesPicker('getDates');

					// default date selectable state
					let isSelectable = true;
					let className = '';
					let tooltipText = null;

					// invoke callbacks by injecting the proper arguments
					if ((!pickedDates.length || pickedDates.length == 2) && typeof beforeShowDayCheckin === 'function') {
						// validate cell for check-in selection
						let validation = beforeShowDayCheckin.call(that, date);
						// update cell states
						isSelectable = validation[0];
						className    = validation[1] || className;
						tooltipText  = validation[2] || tooltipText;
					} else if (pickedDates.length == 1 && typeof beforeShowDayCheckout === 'function') {
						// validate cell for check-out selection
						let validation = beforeShowDayCheckout.call(that, date);
						// update cell states
						isSelectable = validation[0];
						className    = validation[1] || className;
						tooltipText  = validation[2] || tooltipText;
					}

					if (!pickedDates.length) {
						// unset any previously set checkout constraints when no dates selected
						unsetCheckoutConstraints(date);
					} else if (pickedDates.length == 1) {
						// before showing the check-out day, validate the constraints, if any
						let constrainData = validateCheckoutConstraints(date, [isSelectable, className, tooltipText], pickedDates[0]);
						// update cell states
						isSelectable = constrainData[0];
						className    = constrainData[1];
						tooltipText  = constrainData[2];
					}

					return [
						// whether it's selectable
						(isSelectable ? true : false),
						// CSS class name to add
						className,
						// tooltip text
						tooltipText,
					];
				};
			}

			// handle native datepicker method onSelect
			if (options.onSelect) {
				// get registered callbacks
				let onSelectCheckin = options.onSelect.checkin;
				let onSelectCheckout = options.onSelect.checkout;

				// delete native property
				delete options.onSelect;

				// register native property
				options.onSelect = (selectedDate) => {
					// get currently selected dates
					let pickedDates = that.vboMultiDatesPicker('getDates');

					// invoke callbacks by injecting the proper arguments
					if (pickedDates.length == 1 && typeof onSelectCheckin === 'function') {
						// call the registered check-in function
						onSelectCheckin.call(that, selectedDate);
					} else if (pickedDates.length == 2 && typeof onSelectCheckout === 'function') {
						// call the registered check-out function
						onSelectCheckout.call(that, selectedDate);
					}
				};
			}

			// handle datepicker alternate field
			if (options?.altFields?.checkin) {
				// set native alternate field
				options.altField = options.altFields.checkin;
			}

			// render DRP
			$.vboDatesRangePicker(that, $(options.checkout), options);

			// threshold for responsiveness
			let thresholdWidth = options?.responsiveNumMonths?.threshold || 860;

			// handle responsive number of months
			if (options?.responsiveNumMonths && (options?.numberOfMonths || 1) > 1) {
				// configure responsive number of months
				options._onResizeWindow = () => {
					let windowWidth = window.innerWidth;
					if (windowWidth && windowWidth <= thresholdWidth) {
						// just one month
						that.datepicker('option', 'numberOfMonths', 1);
					} else {
						// use the number of months configured
						that.datepicker('option', 'numberOfMonths', options.numberOfMonths);
					}
				};

				// remove duplicate event listeners
				window.removeEventListener('resize', options._onResizeWindow);

				// register event listener
				window.addEventListener('resize', options._onResizeWindow);

				// call method on init
				options._onResizeWindow();
			}

			// handle input fields focus/blur on mobile devices
			if (options?.environment?.section === 'admin' && that.is('input[type="text"]')) {
				// disable input fields focus on small screen resolutions
				let windowWidth = window.innerWidth;
				if (windowWidth && windowWidth <= thresholdWidth) {
					// disable focus on check-in input field
					that.on('focus', function() {
						$(this).blur();
					});

					if ($(options.checkout).is('input[type="text"]')) {
						// disable focus on check-out input field
						$(options.checkout).on('focus', function() {
							$(this).blur();
						});
					}
				}
			}

			return this;
		}

		/**
		 * Setter or getter for the DRP calendar.
		 */
		const drpConfig = (options) => {
			if (typeof options === 'undefined') {
				// GETTER: return DRP configuration.
				// Clone the object in order to prevent manual edits to
				// the configuration properties.
				return Object.assign({}, $(this).data('vboDrpConfig'));
			}

			// SETTER: update DRP configuration
			return $(this).data('vboDrpConfig', options);
		}

		/**
		 * Converts a period string like "+1d" into a Date object.
		 * 
		 * @param 	string 	period 		The date period string.
		 * @param 	Date 	fromDate 	Optional Date object from.
		 * 
		 * @return 	Date
		 */
		const convertPeriod = (period, fromDate) => {
			if (!fromDate || !(fromDate instanceof Date)) {
				fromDate = new Date;
			}

			if (typeof period !== 'string') {
				// period must be a string
				return fromDate;
			}

			let instructions = period.match(/^[\+\-]?([0-9]+)(d|w|m|y)$/i);

			if (!instructions || instructions.length != 3) {
				// period not matched
				return fromDate;
			}

			// period number of days/weeks/months/year
			let num = parseInt(instructions[1]);

			if (num === 0) {
				// same day period ("0d")
				return fromDate;
			}

			// period modifier
			if (period.substring(0, 1) == '-') {
				// turn number into negative
				num = num - (num * 2);
			}

			switch ((instructions[2] + '').toLowerCase()) {
				case 'd':
					fromDate.setDate(fromDate.getDate() + num);
					break;
				case 'w':
					fromDate.setDate(fromDate.getDate() + (7 * num));
					break;
				case 'm':
					fromDate.setMonth(fromDate.getMonth() + num);
					break;
				case 'y':
					fromDate.setFullYear(fromDate.getFullYear() + num);
					break;
			}

			return fromDate;
		}

		/**
		 * Compares two date objects against each other with year, month, day.
		 * 
		 * @param 	string|Date 	date1 	The first date.
		 * @param 	string|Date 	date2 	The second date.
		 * 
		 * @return 	int 			Greater than 0 means date1 is after date2.
		 * 							Equal to 0 means date1 is the same as date2.
		 * 							Less than 0 means date1 is before date2.
		 */
		const compareDates = (date1, date2) => {
			if (!date1) {
				// no empty dates allowed, default to today
				date1 = new Date;
			}

			if (!date2) {
				// no empty dates allowed, default to today
				date2 = new Date;
			}

			// get the DRP configuration
			let config = drpConfig();

			if (typeof date1 === 'string') {
				try {
					// convert date string to date object
					date1 = $.datepicker.parseDate(config.dateFormat, date1);
				} catch (e) {
					// attempt to convert a period string into a date object
					date1 = convertPeriod(date1);
				}
			}

			if (typeof date2 === 'string') {
				try {
					// convert date string to date object
					date2 = $.datepicker.parseDate(config.dateFormat, date2);
				} catch (e) {
					// attempt to convert a period string into a date object
					date2 = convertPeriod(date2);
				}
			}

			// year check
			let diff = date1.getFullYear() - date2.getFullYear();

			if (!diff) {
				// month check
				diff = date1.getMonth() - date2.getMonth();

				if (!diff) {
					// day check
					diff = date1.getDate() - date2.getDate();
				}
			}

			return diff;
		}

		/**
		 * Sets new dates in the DRP calendar.
		 * 
		 * @param 	array 	dates 	List of dates to set.
		 * 
		 * @return 	self
		 */
		const setDates = (dates) => {
			if (!Array.isArray(dates)) {
				throw new Error('Invalid dates argument');
			}

			// filter out empty dates
			dates = dates.filter(d => d);

			// get the DRP configuration
			let config = drpConfig();

			// map first the given dates to date strings in the desired format
			dates = dates.map((dt) => {
				if (typeof dt === 'object') {
					// convert date object into date string
					dt = $.datepicker.formatDate(config.dateFormat, dt);
				}

				return dt;
			});

			// remove all dates from the current selection
			let pickedDates = $(this).vboMultiDatesPicker('getDates');
			$(this).vboMultiDatesPicker('removeDates', pickedDates);

			if (dates.length) {
				// set new date(s)
				$(this).vboMultiDatesPicker('addDates', dates);
			} else {
				// remove any active cell state
				$('.ui-state-active').removeClass('ui-state-active');
				$('.ui-datepicker-current-day').removeClass('ui-datepicker-current-day');
			}

			// get the input fields for check-in and check-out
			let inputCheckin = config?.altFields?.checkin ? $(config.altFields.checkin) : $(this);
			let inputCheckout = config?.altFields?.checkout ? $(config.altFields.checkout) : $(config.checkout);

			// populate checkin and checkout fields
			if (dates.length == 1) {
				inputCheckin.val(dates[0]);
				inputCheckout.val('');
			} else if (dates.length == 2) {
				inputCheckin.val(dates[0]);
				inputCheckout.val(dates[1]);
			} else {
				inputCheckin.val('');
				inputCheckout.val('');
			}

			return this;
		}

		/**
		 * Handles the registration of the check-out update upon choosing the check-in.
		 * Expected actions to perform are: minDate, maxDate, setCheckoutDate.
		 * 
		 * @param 	string 	action 	The action to perform.
		 * @param 	any 	value 	The value for the action to perform.
		 * 
		 * @return 	self
		 */
		const handleCheckoutConstraints = (action, value) => {
			if (typeof action !== 'string') {
				throw new Error('Invalid arguments for handleCheckoutConstraints');
			}

			// access DRP configuration
			let config = drpConfig();

			if (action.match(/^setcheckoutdate$/i)) {
				// do NOT update the check-out date unless running in legacy mode
				// with two native datepicker calendars, of if unsetting the date.
				if (config?.legacy || !value) {
					return $(this).vboDatesRangePicker('setCheckoutDate', value);
				}

				// abort
				return this;
			}

			if (config?.legacy) {
				// apply the requested option to the check-out field
				return $(config.checkout).datepicker('option', action, value);
			}

			// build new settings
			let newConfig = Object.assign({}, config);

			// inject action value
			newConfig.checkoutConstraints = newConfig.checkoutConstraints || {};
			newConfig.checkoutConstraints[action] = value;

			// update DRP configuration
			drpConfig(newConfig)

			return this;
		}

		/**
		 * Validates the current check-out constraints against the given date.
		 * 
		 * @param 	Date 			date 		The date object to validate.
		 * @param 	Array 			validation 	The default date validation array of values for "beforeShowDay".
		 * @param 	string|Date 	checkin 	Optional check-in date.
		 * 
		 * @return 	Array 			The new validation array of values (selectable, class-name, tooltip-text).
		 */
		const validateCheckoutConstraints = (date, validation, checkin) => {
			// access DRP configuration
			let config = drpConfig();

			if (!Array.isArray(validation) || !validation.length) {
				// set default date validation array
				validation = [true, '', null];
			}

			if (config?.legacy || !(date instanceof Date)) {
				return validation;
			}

			if (typeof config?.checkoutConstraints !== 'object') {
				return validation;
			}

			if ((validation[1] + '').match(/checkin\-checkout\-inner/i)) {
				// no need to validate a date between the selected range of dates
				return validation;
			}

			// check if the current date is actually the check-in date
			let isCheckin = false;
			if (checkin) {
				isCheckin = !compareDates(date, checkin);
			}

			// get a list of both action and value constraints
			let actions = Object.keys(config.checkoutConstraints);
			let values  = Object.values(config.checkoutConstraints);

			// scan all actions and values
			actions.forEach((action, index) => {
				if (typeof action !== 'string') {
					return;
				}

				// validate minDate
				if (action.match(/^mindate$/i) && (values[index] instanceof Date)) {
					let isPast = compareDates(date, values[index]) < 0;
					if (isPast && !isCheckin) {
						// date should not be selectable
						validation[0] = false;
					}
				}

				// validate maxDate
				if (action.match(/^maxdate$/i) && (values[index] instanceof Date)) {
					let isBeyond = compareDates(date, values[index]) > 0;
					if (isBeyond && !isCheckin) {
						// date should not be selectable
						validation[0] = false;
					}
				}
			});

			return validation;
		}

		/**
		 * Unsets any previously set check-out constraint.
		 * 
		 * @param 	Date 	date 	The date object being validated.
		 * 
		 * @return 	void
		 */
		const unsetCheckoutConstraints = (date) => {
			// access DRP configuration
			let config = drpConfig();

			if (config.hasOwnProperty('checkoutConstraints')) {
				// delete configuration object property
				delete config.checkoutConstraints;

				// update DRP configuration
				drpConfig(config);
			}
		}

		/**
		 * Initializes the DRP calendar.
		 */
		if (typeof method === 'object') {
			return init(method);
		}

		/**
		 * Compares two dates.
		 */
		if (typeof method === 'string' && method.match(/^comparedates$/i)) {
			return compareDates(options, setvalue);
		}

		/**
		 * Converts a period string into a date object.
		 */
		if (typeof method === 'string' && method.match(/^convertperiod$/i)) {
			return convertPeriod(options, setvalue);
		}

		/**
		 * Aliasing dates retrieval.
		 */
		if (typeof method === 'string' && method.match(/^getdates$/i)) {
			return $(this).vboMultiDatesPicker('getDates');
		}

		/**
		 * Aliasing check-in date retrieval.
		 */
		if (typeof method === 'string' && method.match(/^getcheckindate$/i)) {
			let pickedDates = $(this).vboMultiDatesPicker('getDates', 'object');
			return pickedDates[0] || null;
		}

		/**
		 * Aliasing check-out date retrieval.
		 */
		if (typeof method === 'string' && method.match(/^getcheckoutdate$/i)) {
			let pickedDates = $(this).vboMultiDatesPicker('getDates', 'object');
			return pickedDates[1] || null;
		}

		/**
		 * Sets new dates in the DRP.
		 */
		if (typeof method === 'string' && method.match(/^setdates$/i)) {
			return setDates(options);
		}

		/**
		 * Sets the check-in date in the DRP.
		 */
		if (typeof method === 'string' && method.match(/^setcheckindate$/i)) {
			// get current dates
			let pickedDates = $(this).vboMultiDatesPicker('getDates');

			// build new dates
			let newDates = Array.isArray(options) ? options : [options];

			if (!newDates[0]) {
				// we are actually unsetting the check-in date
				newDates = [];
				pickedDates = [];
			}

			if (pickedDates[1]) {
				// push existing check-out date
				newDates.push(pickedDates[1]);
			}

			return setDates(newDates);
		}

		/**
		 * Sets the check-out date in the DRP.
		 */
		if (typeof method === 'string' && method.match(/^setcheckoutdate$/i)) {
			// get current dates
			let pickedDates = $(this).vboMultiDatesPicker('getDates');
			if (!pickedDates.length) {
				// abort if no dates currently selected, or if multiple dates given
				return this;
			}

			// build new dates
			let newDates = [pickedDates[0], Array.isArray(options) ? options[0] : options];

			return setDates(newDates);
		}

		/**
		 * Hides the DRP calendar, as long as it is not rendered inline.
		 */
		if (typeof method === 'string' && method.match(/^hide$/i)) {
			return $(this).datepicker('hide');
		}

		/**
		 * Setter or getter for the native datepicker calendar.
		 */
		if (method === 'option') {
			// native datepicker option getter or setter
			if (typeof setvalue === 'undefined') {
				// native datepicker option getter
				return $(this).datepicker('option', options);
			}

			// native datepicker option setter
			return $(this).datepicker('option', options, setvalue);
		}

		/**
		 * Gets one or all DRP options, or invokes a DRP function.
		 */
		if (method === 'drpoption') {
			// access DRP configuration
			let config = drpConfig();

			if (!options) {
				// return the whole DRP configuration object
				return config;
			}

			if (typeof options === 'string' && options.match(/^.+\..+$/i)) {
				// check for nested configuration object properties (i.e. "beforeShowDay.checkin")
				let macroOption = options;
				macroOption.split('.').forEach((prop) => {
					if (typeof config === 'object' && config.hasOwnProperty(prop) && typeof config[prop] === 'object') {
						config = config[prop];
					} else {
						options = prop;
					}
				});
			}

			if (typeof setvalue === 'undefined') {
				// DRP option getter
				return config[options] || null;
			}

			if (typeof config[options] !== 'function') {
				throw new Error('Invalid DRP function invoked (' + options + ')');
			}

			// call the requested method
			return Array.isArray(setvalue) ? config[options].apply(this, setvalue) : config[options].call(this, setvalue);
		}

		/**
		 * Handles check-out update operations upon selecting check-in.
		 */
		if (method === 'checkout') {
			return handleCheckoutConstraints(options, setvalue);
		}
	}
}));