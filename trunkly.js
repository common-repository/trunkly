function trunkly_embed( embedUrl ) {

	jQuery.ajax({
		url: '/wp-content/plugins/trunkly/embed.php?url='+embedUrl,
		success:	function( data ) {
			if( 'url'==data ) {
				window.open( embedUrl, '_blank' );
			} else {
				jQuery.colorbox({html: data, maxHeight: 800, maxWidth: 1200 });
			}
		}
	});
}