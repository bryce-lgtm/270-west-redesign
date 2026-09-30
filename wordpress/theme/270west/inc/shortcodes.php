<?php
/**
 * Shortcodes that expose the theme's resource logic to Elementor Pro templates.
 *
 * The Theme Builder versions of the resource singles, cards and archives are Elementor containers
 * and widgets that editors can rearrange; the pieces that are logic rather than layout (type label,
 * reading time, table of contents, checklist items, related resources, the story video band, news
 * meta) render through these shortcodes so they keep producing the same markup as the PHP template
 * parts, and the prototype stylesheet keeps styling them.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'w270_embed_url' ) ) {
	/** Normalises a pasted YouTube/Vimeo link to its embed URL (see template-parts/resource/story.php). */
	function w270_embed_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) { return ''; }
		if ( preg_match( '~youtube\.com/watch\?(?:.*&)?v=([\w-]+)~i', $url, $m ) || preg_match( '~youtu\.be/([\w-]+)~i', $url, $m ) ) {
			return 'https://www.youtube.com/embed/' . $m[1];
		}
		if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~i', $url, $m ) ) {
			return 'https://player.vimeo.com/video/' . $m[1];
		}
		return preg_match( '~^https://~i', $url ) ? $url : '';
	}
}

function w270_sc_id( $atts ) {
	$atts = (array) $atts;
	return ! empty( $atts['post'] ) ? (int) $atts['post'] : get_the_ID();
}

// [w270_field name=pull_quote wrap=blockquote class=story-card-quote default=""]
add_shortcode( 'w270_field', function ( $atts ) {
	$a  = shortcode_atts( [ 'name' => '', 'wrap' => 'span', 'class' => '', 'default' => '', 'post' => 0 ], (array) $atts );
	$v  = w270_field( $a['name'], w270_sc_id( $a ) );
	$v  = is_scalar( $v ) ? (string) $v : '';
	$v  = '' !== $v ? $v : $a['default'];
	if ( '' === $v ) { return ''; }
	$tag = preg_match( '/^[a-z0-9]+$/', $a['wrap'] ) ? $a['wrap'] : 'span';
	return sprintf( '<%1$s class="%2$s">%3$s</%1$s>', $tag, esc_attr( $a['class'] ), esc_html( $v ) );
} );

// [w270_type_label class=guide-type] → <span class="guide-type guide-type-checklist">Checklist</span>
add_shortcode( 'w270_type_label', function ( $atts ) {
	$a   = shortcode_atts( [ 'class' => 'guide-type', 'post' => 0 ], (array) $atts );
	$id  = w270_sc_id( $a );
	$sub = w270_subtype_slug( $id );
	$mod = $sub ? ' ' . $a['class'] . '-' . rtrim( $sub, 's' ) : '';
	return '<span class="' . esc_attr( $a['class'] . $mod ) . '">' . esc_html( w270_type_label( $id, (bool) w270_field( 'featured', $id ) ) ) . '</span>';
} );

// [w270_read_time class=guide-read suffix=" min read"]
add_shortcode( 'w270_read_time', function ( $atts ) {
	$a = shortcode_atts( [ 'class' => 'guide-read', 'suffix' => ' min read', 'post' => 0 ], (array) $atts );
	$m = w270_read_time( w270_sc_id( $a ) );
	return $m ? '<span class="' . esc_attr( $a['class'] ) . '">' . (int) $m . esc_html( $a['suffix'] ) . '</span>' : '';
} );

// SEO heading rule: the SEO H1 when the field is set (then the title is the marketing H2), else the title.
add_shortcode( 'w270_seo_h1', function ( $atts ) {
	$id = w270_sc_id( $atts );
	return esc_html( w270_field( 'seo_h1', $id ) ?: get_the_title( $id ) );
} );
add_shortcode( 'w270_marketing_h2', function ( $atts ) {
	$id  = w270_sc_id( $atts );
	$seo = trim( (string) w270_field( 'seo_h1', $id ) );
	// Only when the SEO H1 differs from the title; otherwise the H2 would repeat the H1.
	return ( $seo && 0 !== strcasecmp( $seo, get_the_title( $id ) ) ) ? esc_html( get_the_title( $id ) ) : '';
} );

// [w270_topic_crumb] → the "/ Topic" tail of the breadcrumb
add_shortcode( 'w270_topic_crumb', function ( $atts ) {
	$topic = w270_topic( w270_sc_id( $atts ) );
	if ( ! $topic ) { return ''; }
	return '<span>/</span><span class="res-crumb-current"><a href="' . esc_url( home_url( '/resources/#topic-' . $topic->slug ) ) . '">' . esc_html( $topic->name ) . '</a></span>';
} );

// [w270_hero_meta] → ● 7 min read · Updated September 2026 · By 270 West Consulting
add_shortcode( 'w270_hero_meta', function ( $atts ) {
	$id = w270_sc_id( $atts );
	$m  = w270_read_time( $id );
	return '<div class="res-meta">' . ( $m ? '<span>● ' . (int) $m . ' min read</span>' : '' )
		. '<span>Updated ' . esc_html( get_the_modified_date( 'F Y', $id ) ) . '</span><span>By 270 West Consulting</span></div>';
} );

// The guide-layout parts, each rendering only where the PHP single would.
add_shortcode( 'w270_toc', function () {
	// Every resource type gets a table of contents when the body has two or more headings; a
	// guide can switch it off with its Show TOC field. With nothing to list, nothing renders and
	// .article-layout drops the column (see styles.css) rather than leaving it blank.
	$show = w270_field( 'show_toc' );
	$show = null === $show || '' === $show ? true : (bool) $show;
	$toc  = $show ? w270_toc( apply_filters( 'the_content', get_the_content() ) ) : [];
	if ( ! $toc ) { return ''; }
	ob_start();
	get_template_part( 'template-parts/resource/toc', null, [ 'toc' => $toc ] );
	return ob_get_clean();
} );
add_shortcode( 'w270_callout', function () {
	if ( 'guides' !== w270_subtype_slug() ) { return ''; }
	ob_start();
	get_template_part( 'template-parts/resource/callout' );
	return ob_get_clean();
} );
add_shortcode( 'w270_checklist', function () {
	if ( 'checklists' !== w270_subtype_slug() ) { return ''; }
	ob_start();
	get_template_part( 'template-parts/resource/checklist-items' );
	return ob_get_clean();
} );
add_shortcode( 'w270_related', function () {
	ob_start();
	get_template_part( 'template-parts/resource/related' );
	return ob_get_clean();
} );

// [w270_story_media] → the 16:9 band: the video embed when a URL is set, else the featured photo.
add_shortcode( 'w270_story_media', function ( $atts ) {
	$id    = w270_sc_id( $atts );
	$video = w270_embed_url( w270_field( 'video_url', $id ) );
	if ( $video ) {
		return '<div class="story-single-video"><iframe src="' . esc_url( $video, [ 'https' ] ) . '" title="' . esc_attr( get_the_title( $id ) ) . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen></iframe></div>';
	}
	return has_post_thumbnail( $id ) ? '<div class="photo story-single-photo">' . get_the_post_thumbnail( $id, 'large' ) . '</div>' : '';
} );

// [w270_story_meta] → Story 01 · Video · 4 min
/** First given name from the veteran_name field, skipping rank abbreviations ("Capt. Sarah …" → "Sarah"). */
function w270_story_first_name( $id ) {
	$name  = trim( preg_replace( '/\s*\(.*$/', '', (string) w270_field( 'veteran_name', $id ) ) );
	$words = array_values( array_filter( explode( ' ', $name ), fn( $w ) => '' !== $w && ! str_ends_with( $w, '.' ) ) );
	return $words[0] ?? '';
}

/** True when the story has a video; otherwise it is a written story. */
function w270_story_has_video( $id ) {
	return (bool) w270_embed_url( w270_field( 'video_url', $id ) );
}

// [w270_story_meta] → "Story 01 · 5 min read" (written) or "Story 01 · Video · 4 min" (video).
add_shortcode( 'w270_story_meta', function ( $atts ) {
	$id  = w270_sc_id( $atts );
	$num = 'Story ' . str_pad( (string) (int) w270_field( 'story_number', $id ), 2, '0', STR_PAD_LEFT );
	if ( w270_story_has_video( $id ) ) {
		$mins = (int) w270_field( 'duration', $id );
		return '<div class="story-card-meta">' . esc_html( $num ) . ' · Video' . ( $mins ? ' · ' . $mins . ' min' : '' ) . '</div>';
	}
	return '<div class="story-card-meta">' . esc_html( $num ) . ' · ' . (int) w270_read_time( $id ) . ' min read</div>';
} );

// [w270_story_link] → "Read Robyn's story →" (or "Watch …" for a video story).
add_shortcode( 'w270_story_link', function ( $atts ) {
	$id    = w270_sc_id( $atts );
	$first = w270_story_first_name( $id );
	$verb  = w270_story_has_video( $id ) ? 'Watch' : 'Read';
	return '<span class="story-card-link">' . $verb . ( $first ? ' ' . esc_html( $first ) . "'s" : '' ) . ' story →</span>';
} );

// [w270_story_play] → the play badge, only for a story that has a video.
add_shortcode( 'w270_story_play', function ( $atts ) {
	return w270_story_has_video( w270_sc_id( $atts ) )
		? '<span class="story-card-play" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>'
		: '';
} );

/**
 * [w270_story_card slug="robyn-story" variant="tcard|hero" class="…" pos="center 30%"]
 * A testimonial card rendered from the story post, so the quote, name, rank and photo are edited once
 * (on the story) and every card links to its story page. The generator emits this for the prototype's
 * cards marked data-story. Renders nothing if the story is missing or unpublished.
 */
add_shortcode( 'w270_story_card', function ( $atts ) {
	$a    = shortcode_atts( [ 'slug' => '', 'variant' => 'tcard', 'class' => '', 'pos' => 'center 30%', 'priority' => '' ], (array) $atts );
	$post = $a['slug'] ? get_page_by_path( $a['slug'], OBJECT, 'resource' ) : null;
	if ( ! $post || 'publish' !== $post->post_status ) {
		if ( $a['slug'] ) { error_log( "w270: story card for missing or unpublished story '{$a['slug']}'" ); }
		return '';
	}
	$id    = $post->ID;
	$quote = (string) w270_field( 'pull_quote', $id );
	$name  = (string) w270_field( 'veteran_name', $id );
	$role  = (string) ( w270_field( 'veteran_role', $id ) ?: 'Canadian Armed Forces Veteran' );
	$first = w270_story_first_name( $id );
	$link  = ( w270_story_has_video( $id ) ? 'Watch ' : 'Read ' ) . ( $first ? $first . "'s" : 'the' ) . ' story →';
	$img   = get_the_post_thumbnail( $id, 'large', array_filter( [
		'style'         => 'object-position:' . preg_replace( '/[^a-z0-9%. ]/i', '', $a['pos'] ),
		'loading'       => $a['priority'] ? 'eager' : 'lazy',
		'fetchpriority' => $a['priority'] ? 'high' : '',
		'alt'           => trim( $name . ( $role ? ', ' . $role : '' ) ),
	] ) );
	$mark  = '<svg class="%s" width="%d" height="%d" viewBox="0 0 56 42" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 41V14C1 6.8 6.8 1 14 1h9v20h-9v20H1zM33 41V14c0-7.2 5.8-13 13-13h9v20h-9v20H33z"/></svg>';
	$cls   = trim( preg_replace( '/[^a-z0-9 _-]/i', '', $a['class'] ) );
	$href  = esc_url( get_permalink( $id ) );
	if ( 'hero' === $a['variant'] ) {
		return '<a class="' . esc_attr( $cls ?: 'hero-quote' ) . '" href="' . $href . '">' . $img
			. '<div class="hero-quote-scrim"></div><div class="hero-quote-body">' . sprintf( $mark, 'hero-quote-mark', 56, 42 )
			. '<blockquote class="hero-quote-q">' . esc_html( $quote ) . '</blockquote>'
			. '<div class="hero-quote-cite"><span class="hero-quote-name">' . esc_html( $name ) . '</span><span class="hero-quote-role">' . esc_html( $role ) . '</span></div>'
			. '<span class="story-quote-link">' . esc_html( $link ) . '</span></div></a>';
	}
	return '<a class="' . esc_attr( $cls ?: 'tcard' ) . '" href="' . $href . '"><div class="tcard-img">' . $img . '</div><div class="tcard-scrim"></div>'
		. '<div class="tcard-body">' . sprintf( $mark, 'tcard-quote-icon', 32, 24 )
		. '<blockquote class="tcard-quote">' . esc_html( $quote ) . '</blockquote>'
		. '<div class="tcard-cite"><span class="tcard-name">' . esc_html( $name ) . '</span><span class="tcard-handle">' . esc_html( $role ) . '</span></div>'
		. '<span class="story-quote-link">' . esc_html( $link ) . '</span></div></a>';
} );

// [w270_news_meta] → date + category chip(s)
add_shortcode( 'w270_news_meta', function ( $atts ) {
	$id    = w270_sc_id( $atts );
	$names = wp_get_object_terms( $id, 'news_category', [ 'fields' => 'names' ] );
	$out   = '<div class="news-entry-meta"><time datetime="' . esc_attr( get_the_date( 'Y-m-d', $id ) ) . '">' . esc_html( get_the_date( 'j F Y', $id ) ) . '</time>';
	foreach ( is_wp_error( $names ) ? [] : $names as $n ) { $out .= '<span class="news-cat">' . esc_html( $n ) . '</span>'; }
	return $out . '</div>';
} );

// [w270_news_link text="Read update →" class=news-entry-more] → honours the external_link field
add_shortcode( 'w270_news_link', function ( $atts ) {
	$a    = shortcode_atts( [ 'text' => 'Read update →', 'class' => 'news-entry-more', 'post' => 0 ], (array) $atts );
	$id   = w270_sc_id( $a );
	$href = w270_field( 'external_link', $id ) ?: get_permalink( $id );
	return '<a class="' . esc_attr( $a['class'] ) . '" href="' . esc_url( $href ) . '">' . esc_html( $a['text'] ) . '</a>';
} );

// [w270_archive_count types="guides,checklists,explainers" label="resources"] → "15 resources"
add_shortcode( 'w270_archive_count', function ( $atts ) {
	$a     = shortcode_atts( [ 'types' => '', 'label' => 'resources' ], (array) $atts );
	$types = array_filter( array_map( 'trim', explode( ',', $a['types'] ) ) );
	$n     = $types ? count( get_posts( [
		'post_type' => 'resource', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids',
		'tax_query' => [ [ 'taxonomy' => 'resource_type', 'field' => 'slug', 'terms' => $types ] ],
	] ) ) : 0;
	$label = $a['label'];
	if ( 1 === (int) $n && str_ends_with( $label, 's' ) ) { $label = substr( $label, 0, -1 ); } // "1 resource"
	return '<div class="archive-count">' . (int) $n . ' ' . esc_html( $label ) . '</div>';
} );

// [w270_card_tag main=1 class=resource-card-main-tag] → "Featured guide · 2 min read" / "Checklist · 1 min"
add_shortcode( 'w270_card_tag', function ( $atts ) {
	$a    = shortcode_atts( [ 'main' => '', 'class' => 'resource-card-sm-tag', 'post' => 0 ], (array) $atts );
	$id   = w270_sc_id( $a );
	$mins = w270_read_time( $id );
	$main = ! empty( $a['main'] );
	$tag  = w270_type_label( $id, $main ) . ( $mins ? ( $main ? " · {$mins} min read" : " · {$mins} min" ) : '' );
	return '<div class="' . esc_attr( $a['class'] ) . '">' . esc_html( $tag ) . '</div>';
} );

// [w270_date format="F Y" class=news-item-date]
add_shortcode( 'w270_date', function ( $atts ) {
	$a = shortcode_atts( [ 'format' => 'j F Y', 'class' => '', 'post' => 0 ], (array) $atts );
	return '<div class="' . esc_attr( $a['class'] ) . '">' . esc_html( get_the_date( $a['format'], w270_sc_id( $a ) ) ) . '</div>';
} );

// [w270_terms taxonomy=news_category class=news-item-tag] → the first term's name
add_shortcode( 'w270_terms', function ( $atts ) {
	$a     = shortcode_atts( [ 'taxonomy' => 'news_category', 'class' => '', 'post' => 0 ], (array) $atts );
	$names = wp_get_object_terms( w270_sc_id( $a ), $a['taxonomy'], [ 'fields' => 'names' ] );
	if ( is_wp_error( $names ) || ! $names ) { return ''; }
	return '<div class="' . esc_attr( $a['class'] ) . '">' . esc_html( $names[0] ) . '</div>';
} );

/**
 * Loop Grid queries the Query ID control names (Elementor Pro: elementor/query/{id}).
 * The Resources hub shows the featured guide, then the next two guides/checklists/explainers.
 */
$w270_library_terms = [ 'guides', 'checklists', 'explainers' ];
add_action( 'elementor/query/w270_hub_featured', function ( $query ) use ( $w270_library_terms ) {
	$query->set( 'post_type', 'resource' );
	$query->set( 'tax_query', [ [ 'taxonomy' => 'resource_type', 'field' => 'slug', 'terms' => $w270_library_terms ] ] );
	$query->set( 'meta_query', [ [ 'key' => 'featured', 'value' => '1' ] ] );
	$query->set( 'orderby', [ 'menu_order' => 'ASC', 'date' => 'DESC' ] );
	$query->set( 'posts_per_page', 1 );
} );
add_action( 'elementor/query/w270_hub_guides', function ( $query ) use ( $w270_library_terms ) {
	// The next two by menu order, skipping only the guide that took the featured slot (more than
	// one guide may be flagged featured; the others still belong here).
	// Pro fires this action from inside pre_get_posts, so the lookup below would re-enter it
	// without the guard and recurse until memory runs out.
	static $running = false;
	if ( $running ) { return; }
	$running  = true;
	$featured = get_posts( [
		'post_type' => 'resource', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids',
		'tax_query' => [ [ 'taxonomy' => 'resource_type', 'field' => 'slug', 'terms' => $w270_library_terms ] ],
		'meta_query' => [ [ 'key' => 'featured', 'value' => '1' ] ],
		'orderby' => [ 'menu_order' => 'ASC', 'date' => 'DESC' ],
	] );
	$query->set( 'post_type', 'resource' );
	$query->set( 'tax_query', [ [ 'taxonomy' => 'resource_type', 'field' => 'slug', 'terms' => $w270_library_terms ] ] );
	if ( $featured ) { $query->set( 'post__not_in', $featured ); }
	$query->set( 'orderby', [ 'menu_order' => 'ASC', 'date' => 'DESC' ] );
	$query->set( 'posts_per_page', 2 );
	$running = false;
} );


/**
 * [w270_language_links] → the footer's "English / Français (Canada)" links, each pointing at the
 * current page in that language (TranslatePress). data-no-translation keeps TranslatePress from
 * rewriting the English link to /fr/ on French pages. Without TranslatePress, plain labels.
 */
add_shortcode( 'w270_language_links', function () {
	$langs = [ 'en_US' => [ 'English', 'en' ], 'fr_CA' => [ 'Français (Canada)', 'fr-CA' ] ];
	if ( ! class_exists( 'TRP_Translate_Press' ) ) {
		return '<div class="footer-col-items">' . implode( '', array_map( fn( $l ) => '<span>' . esc_html( $l[0] ) . '</span>', $langs ) ) . '</div>';
	}
	global $TRP_LANGUAGE;
	$converter = TRP_Translate_Press::get_trp_instance()->get_component( 'url_converter' );
	$out = '';
	foreach ( $langs as $code => [ $label, $hreflang ] ) {
		$current = ( $TRP_LANGUAGE ?? 'en_US' ) === $code;
		$out    .= sprintf( '<a href="%s" hreflang="%s" lang="%s"%s>%s</a>', esc_url( $converter->get_url_for_language( $code, null, '' ) ),
			esc_attr( $hreflang ), esc_attr( $hreflang ), $current ? ' aria-current="true"' : '', esc_html( $label ) );
	}
	return '<div class="footer-col-items" data-no-translation>' . $out . '</div>';
} );
