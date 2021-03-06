jQuery( document ).ready( function($) {
	// Select website
	$( '#peta-websites' ).on( 'change', function( e ) {
		var website = e.target.value;
		$.post( ajaxurl, {
			action: 'peta_filter_websites',
			website: website,
		},
		function( response ) {
			if ( response.has_posts ) {
				$('.peta-dashboard-posts').html( response.posts );
			}
		},
		'json'
	);
	} );

	$( '.peta-dashboard-posts' ).on( 'click', function( e ) {
		e.preventDefault();
		$target = $(e.target);
		var post_id = $target.data('id');
		var website = $target.data('website');
		$target.parent().remove();
		$.post( ajaxurl, {
			action: 'peta_approve_website',
			post_id: post_id,
			website: website,
		},
		function( response ) {
		},
		'json'
	);
	} );
} );