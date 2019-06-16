<?php

add_action( 'rss2_item', 'ii_rss_insert' );
add_action( 'bbp_feed_item', 'ii_rss_insert' );
/* add media namespace if this option selected */
if ( isset( $ii_options[ 'rss_media' ] ) ) {
add_action( 'rss2_ns', 'ii_rss_ns_insert' );
add_action( 'bbp_feed', 'ii_rss_ns_insert' );
}

//reset RSS cache more often while debugging
if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
	/*
	 * try not to cache feed when debugging
	 */
	function ii_feed_cache_transient_lifetime( $lifetime, $filename ) {
		return 0;
	}

	add_filter( 'wp_feed_cache_transient_lifetime', 'ii_feed_cache_transient_lifetime', 10, 2 );

	/*
	 * try not to cache feed when debugging
	 */
	function ii_turn_off_feed_caching( $feed ) {
		$feed->enable_cache( false );
	}

	add_action( 'wp_feed_options', 'ii_turn_off_feed_caching' );
}
/*
 * add namespace for media tag
 */
function ii_rss_ns_insert() {
	echo 'xmlns:media="http://search.yahoo.com/mrss/"' . "\n";
}

/**
 * add media and/or enclosure tag to RSS entry according to selected options
 */
function ii_rss_insert() {
	global $post;
	if ( ! $post ) {
		return;
	}

	$ii_options	 = ii_get_options();
	$size		 = apply_filters( 'ii_rss_image_size', 'large' );

	//if the post has a thumbnail it should be possible to output full details from local file
	$post_thumbnail_id = get_post_thumbnail_id( $post );
	if ( $post_thumbnail_id ) {
		$image = wp_get_attachment_image_src( $post_thumbnail_id, $size, false );
		if ( $image ) {
			ii_rss_img_from_attachment( $image, $size );
			return;
		}
	}
	//fallback to first image from content (which could reference an external site)
	$image_url = inkston_catch_image();
	if ( ! empty( $image_url ) ) {
		$filepath	 = ii_file_from_url( $image_url );
		$mime		 = ($filepath) ? ii_mime_from_file( $filepath ) : ii_mime_from_name( $image_url );
		$mime = ii_mime_from_name( $image_url );
		$filesize	 = ($filepath) ? filesize( $filepath ) : 200000;
		//$width = get_option( "{$size}_size_w" );
		//enclosure tag
		if ( isset( $ii_options[ 'rss_enclosure' ] ) ) {
			$enclosuretag = sprintf( '<enclosure length="%s" type="%s" url="%s" />', $filesize, $mime, $image_url );
			echo $enclosuretag . "\n";
		}
		//media tag
		if ( isset( $ii_options[ 'rss_media' ] ) ) {
			echo "	" . '<media:content url="' . esc_url( $image_url ) . '" medium="image" type="' . $mime . '" />' . "\n";
		}
	}
}

/*
 * output rss image tags for an image attachment
 * (standard option where post featured image is set)
 *
 * @param array $image	 wordpress attachment image information
 * @param string $size	 wordpress image size string
 * @param string $defaultfilesize	 a default used for enclosure tag where a length is mandatory
 */
function ii_rss_img_from_attachment( $image, $size = 'large', $defaultfilesize = 200000 ) {
	$ii_options	 = ii_get_options();
	$image_url	 = $image[ 0 ];
	$filepath	 = ii_file_from_url( $image_url );
	$mime		 = ($filepath) ? ii_mime_from_file( $filepath ) : ii_mime_from_name( $image_url );
	$filesize	 = ($filepath) ? filesize( $filepath ) : 200000;
	$width		 = $image[ 1 ];
	if ( ! $width ) {
		$width = get_option( "{$size}_size_w" );
	}
	$height = $image[ 2 ];

	//TODO: is plain http needed for the rss reader?
	$image_url = str_replace( 'https', 'http', $image_url );

	//enclosure tag
	if ( isset( $ii_options[ 'rss_enclosure' ] ) ) {
		$enclosuretag = sprintf( '<enclosure length="%s" type="%s" url="%s" />', $filesize, $mime, $image_url );
		echo $enclosuretag . "\n";
	}
	//media tag
	if ( isset( $ii_options[ 'rss_media' ] ) ) {
		$mediatag	 = sprintf( '<media:content url="%s" type="%s" medium="image" width="%s" ', $image_url, $mime, $width );
		$mediatag	 .= ($height) ? 'height="' . $height . '" />' : ' />';
		echo $mediatag . "\n";
	}
}

/**
 * return the file path for a url (if possible)
 *
 * @param string $source_url	 source url
 * @return mixed               filepath or false
 */
function ii_file_from_url( $source_url ) {
	//split url components
	$url	 = parse_url( $source_url );
	$urlpath = $url[ 'path' ];

	//normally, we are looking for an attachment image (post featured image)
	//which will normally be in uploads directory
	$testurl = strtolower( $source_url );
	$testpos = strpos( $testurl, 'uploads' );
	if ( $testpos !== FALSE ) {
		//if it is in uploads start search with wp upload dir function since
		//uploads may have moved from a simple wp subdirectory
		$uploads = wp_upload_dir();
		$baseuploaddir	 = $uploads[ 'basedir' ];

		//basedir could include subsite upload directory eg:
		//  "/whateverpath/wp-content/uploads/sites/2"
		//this sites/2 is also included in the url path so will need to be trimmed out
		$trimmedUploadDir = stristr( $baseuploaddir, 'uploads', true );
		if ( $trimmedUploadDir ) {
			$baseuploaddir = $trimmedUploadDir . 'uploads';
		}
		$path = $baseuploaddir . preg_replace( '/.*uploads(.*)/', '${1}', $url[ 'path' ] );
		if ( file_exists( $path ) ) {
			return $path;
		}
	}

	//if not found in uploads directory test path, but is part of site,
	//try as literal subdirectory of site root
	$networksiteurl	 = strtolower( network_site_url() );
	$path			 = get_home_path();
	$testpos		 = strpos( $testurl, $networksiteurl );
	if ( $testpos !== FALSE ) {
		$siteurl	 = parse_url( $networksiteurl );
		$sitepath	 = $siteurl[ 'path' ];
		if ( $sitepath ) {
			$trimmedPath = stristr( $path, $sitepath, true );
			if ( $trimmedPath ) {
				$path = $trimmedPath;
			}
		}
		$path .= $url[ 'path' ];
		if ( file_exists( $path ) ) {
			return $path;
		}
	}
	return false;
}

/**
 * add media and/or enclosure tag to RSS entry
 *
 * @param string $filepath	  full file path which should already exist
 * @param string $default		default type changed from 'application/octet-stream' to 'image/jpg'
 *
 * @return string               mime type string default 'image/jpg'
 */
function ii_mime_from_file( $filepath, $default = 'image/jpg' ) {
	try {
		$type = mime_content_type( $filepath );
		if ( $type ) {
			return ($type);
		}
	} catch ( Exception $ex ) {
		error_log( 'rss mime_from_file exception caught: ' . $ex->getMessage() );
	}
	return ii_mime_from_name( $filepath, $default );
}

/*
 * fairly standard function, assuming file type from url or path string
 * without attempting to examine file or get headers
 *
 * @param string $filename	any string which might be url or file related
 * @param string $default		default type changed from 'application/octet-stream' to 'image/jpg'
 *
 * @return string             mime type string default 'image/jpg'
 */
function ii_mime_from_name( $filename, $default = 'image/jpg' ) {

	$mime_types = array(
		'txt'	 => 'text/plain',
		'htm'	 => 'text/html',
		'html'	 => 'text/html',
		'php'	 => 'text/html',
		'css'	 => 'text/css',
		'js'	 => 'application/javascript',
		'json'	 => 'application/json',
		'xml'	 => 'application/xml',
		'swf'	 => 'application/x-shockwave-flash',
		'flv'	 => 'video/x-flv',
		// images
		'png'	 => 'image/png',
		'jpe'	 => 'image/jpeg',
		'jpeg'	 => 'image/jpeg',
		'jpg'	 => 'image/jpeg',
		'gif'	 => 'image/gif',
		'bmp'	 => 'image/bmp',
		'ico'	 => 'image/vnd.microsoft.icon',
		'tiff'	 => 'image/tiff',
		'tif'	 => 'image/tiff',
		'svg'	 => 'image/svg+xml',
		'svgz'	 => 'image/svg+xml',
		// archives
		'zip'	 => 'application/zip',
		'rar'	 => 'application/x-rar-compressed',
		'exe'	 => 'application/x-msdownload',
		'msi'	 => 'application/x-msdownload',
		'cab'	 => 'application/vnd.ms-cab-compressed',
		// audio/video
		'mp3'	 => 'audio/mpeg',
		'qt'	 => 'video/quicktime',
		'mov'	 => 'video/quicktime',
		// adobe
		'pdf'	 => 'application/pdf',
		'psd'	 => 'image/vnd.adobe.photoshop',
		'ai'	 => 'application/postscript',
		'eps'	 => 'application/postscript',
		'ps'	 => 'application/postscript',
		// ms office
		'doc'	 => 'application/msword',
		'rtf'	 => 'application/rtf',
		'xls'	 => 'application/vnd.ms-excel',
		'ppt'	 => 'application/vnd.ms-powerpoint',
		// open office
		'odt'	 => 'application/vnd.oasis.opendocument.text',
		'ods'	 => 'application/vnd.oasis.opendocument.spreadsheet',
	);

	//get the extension
	$ext = strrchr( $filename, "." );
	if ( ! $ext ) {
		return $default;
	}
	//strip the . and make it lowercase to use as key in array
	$ext = strtolower( str_replace( ".", '', $ext ) );
	if ( array_key_exists( $ext, $mime_types ) ) {
		return $mime_types[ $ext ];
	} else {
		return $default;
	}
}
