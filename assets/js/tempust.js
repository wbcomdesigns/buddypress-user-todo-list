// tempust.js, Lean as shit jQuery calendar.
// 2017 (c) Semirix
var tempust = {
	dpm: [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
	npm: [
		"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
	],
	npd: [
		"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
	],
	sds: function (d) {
		return d.getFullYear() + "/" + (d.getMonth() + 1) + "/" + d.getDate();
	},
	di: function (y, m, o, e) {
		var lmd     = {},
				tmd = {},
				nmd = {},
				lm,
				nm;

		lm = new Date( y, m, 1 );
		lm.setMonth( m - 1 );
		nm = new Date( y, m, 1 );
		nm.setMonth( m + 1 );

		function offset(d, i, o) {
			return new Date( d.getFullYear(), d.getMonth(), (i - o) % 7 ).getDay();
		}

		for (var i = tempust.dpm[lm.getMonth()]; offset( lm, i, o ) !== 6; i--) {
			var date = new Date( lm.getFullYear(), lm.getMonth(), i );
			lmd[i]   = {
				date: date,
				e: e ? (e[tempust.sds( date )]) : undefined
			};
		}
		for (var i = 1; i <= tempust.dpm[m]; i++) {
			var date = new Date( y, m, i );
			tmd[i]   = {
				date: date,
				e: e ? e[tempust.sds( date )] : undefined
			};
		}
		for (var i = 1; offset( nm, i, o ); i++) {
			var date = new Date( nm.getFullYear(), nm.getMonth(), i );
			nmd[i]   = {
				date: date,
				e: e ? e[tempust.sds( date )] : undefined
			};
		}
		return {
			y: y,
			m: m,
			o: o,
			lmd: lmd,
			tmd: tmd,
			nmd: nmd
		};
	}
};

(function ($) {
	var def = function (self, data, defaultOption) {
		if ( ! self.data( data.toString() )) {
			self.data( data.toString(), defaultOption );
		}
	};

	var render = function (self) {
		var box            = self.children( ".tempust" ),
				date       = self.data().date,
				dateOffset = self.data().offset,
				dateInfo,
				dateBox    = box.children( ".dates" ),
				weekDay    = 1,
				dateInfo   = tempust.di(
					date.getFullYear(),
					date.getMonth(),
					dateOffset,
					self.data().events
				);

		dateBox.html( "" );

		loop = function (dates, dayClass) {
			var events = [];
			for (var day in dates) {
				var object = $( "<div>" )
						.append( day )
						.addClass( "day" )
						.addClass( dayClass ? dayClass : "" )
						.attr( "data-date", tempust.sds( dates[day].date ) )

				dateBox.append( object );
				if (dates[day].e) {
					object.attr( "data-event", true );
					object.addClass( "bptodo-day" );
					object.on(
						"click",
						function () {
							$( "[data-event-date='" + $( this ).attr( "data-date" ) + "']" ).slideToggle();
						}
					);
					events.push(
						{
							date: tempust.sds( dates[day].date ),
							details: dates[day].e
						}
					);
				}

				weekDay++;
				if (weekDay > 7) {
					weekDay = 1;
					events.forEach(
						function (event) {
							dateBox.append(
								$( "<div>" )
								.append( event.details )
								.addClass( "event" )
								.attr( "data-event-date", event.date )
							);
						}
					);
					events = [];
				}
			}
		}

		for (var i = 0; i < 7; i++) {
			dateBox.append(
				$( "<div class='weekday'>" )
					.append( tempust.npd[(i + dateInfo.o) % 7].slice( 0, 3 ) )
			);
		}

		loop( dateInfo.lmd, "inactive" );
		loop( dateInfo.tmd );
		loop( dateInfo.nmd, "inactive" );
	};

	var init = function (self) {
		var box,
				headerBox,
				date;

		def( self, "date", new Date() );

		date = self.data().date;
		self.data( "initialised", true );

		box = $( "<div class='tempust'>" )
				.append( $( "<div class='header'>" ) )
				.append( $( "<div class='dates'>" ) );

		self.append( box );

		headerBox = box.children( ".header" );

		headerBox.append(
			$( "<select>" )
				.addClass( "year" )
		).children( ".year" ).on(
			"change",
			function () {
						self.data().date.setFullYear( $( this ).val() );
						self.trigger(
							{
								type: "changeDate",
								date: self.data().date
							}
						);
						self.trigger(
							{
								type: "changeYear",
								date: self.data().date
							}
						);
						render( self );
			}
		);

		for (var i = 1900; i <= 2100; i++) {
			headerBox.children( ".year" ).append(
				$( "<option>" )
					.append( i )
					.attr( "value", i )
					.attr( "selected", i === date.getFullYear() )
			);
		}

		headerBox.append(
			$( "<select>" )
				.addClass( "month" )
		).children( ".month" ).on(
			"change",
			function () {
						self.data().date.setMonth( $( this ).val() );
						self.trigger(
							{
								type: "changeDate",
								date: self.data().date
							}
						);
						self.trigger(
							{
								type: "changeMonth",
								date: self.data().date
							}
						);
						render( self );
			}
		);

		for (var i = 0; i < 12; i++) {
			headerBox.children( ".month" ).append(
				$( "<option>" )
					.append( tempust.npm[i] )
					.attr( "value", i )
					.attr( "selected", i === date.getMonth() )
			);
		}
	};

	$.fn.tempust = function (action, data) {
		var self = this;

		if (Object.prototype.toString.call( action ) === "[object Date]") {
			self.data( action );
			init( self );
			render( self );
		}

		if (action !== null && typeof action === 'object') {
			self.data( action );
			init( self );
			render( self );
		}

		if (action === "changeDate") {
			self.data( "date", data );
			render( self );
		}

		if (action === "setEvents") {
			self.data( "events", data );
			render( self );
		}

		return self;
	};
}(jQuery));
