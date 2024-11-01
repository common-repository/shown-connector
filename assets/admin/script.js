(function ($) {
	$( document ).on(
		'click',
		'#shown-connector .nav-tab-wrapper .nav-tab',
		function () {
			$( '#shown-connector .nav-tab-wrapper .nav-tab' ).removeClass( 'nav-tab-active' );
			$( this ).addClass( 'nav-tab-active' );

			var section = $( '.shown-tab-connect section' );
			section.hide();
			section.eq( $( this ).index() ).show();
			return false;
		}
	)
})( jQuery );
