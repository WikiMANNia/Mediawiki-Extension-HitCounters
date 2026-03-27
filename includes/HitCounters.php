<?php

namespace MediaWiki\Extension\HitCounters;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Title\Title;
use ObjectCache;
use Wikimedia\ObjectCache\BagOStuff;

// Class aliases for multi-version compatibility.
// These need to be in global scope so phan can pick up on them,
// and before any use statements that make use of the namespaced names.
if ( version_compare( MW_VERSION, '1.40', '<' ) ) {
	if ( !class_exists('MediaWiki\Title\Title') )  class_alias( '\Title', '\MediaWiki\Title\Title' );
}
if ( version_compare( MW_VERSION, '1.42', '<' ) ) {
	if ( !class_exists('MediaWiki\Parser\Parser') )  class_alias( '\Parser', '\MediaWiki\Parser\Parser' );
}
if ( version_compare( MW_VERSION, '1.43', '<' ) ) {
	if ( !class_exists('MediaWiki\Parser\PPFrame') )  class_alias( '\PPFrame', '\MediaWiki\Parser\PPFrame' );
	if ( !class_exists('Wikimedia\ObjectCache\BagOStuff') )  class_alias( '\BagOStuff', '\Wikimedia\ObjectCache\BagOStuff' );
}

class HitCounters {

	/** @var int|null */
	protected static ?int $mViews;

	protected static function cacheStore( BagOStuff $cache, string $key, ?int $views ): void {
		if ( $views < 100 ) {
			// Only cache for a minute
			$cache->set( $key, $views, 60 );
		} else {
			/* update only once a day */
			$cache->set( $key, $views, 24 * 3600 );
		}
	}

	/**
	 * @return The view count for the page
	 */
	public static function getCount( Title $title ): ?int {
		if ( $title->isSpecialPage() ) {
			return null;
		}

		/*
		 * Use the cache to avoid hitting the DB if available since
		 * page views are pretty common and this is a tiny bit of
		 * information.
		 */
		$cache = ObjectCache::getLocalClusterInstance();
		$key = $cache->makeKey( 'viewcount', $title->getPrefixedDBkey() );
		$views = $cache->get( $key );

		if ( $views === false || $views <= 1 ) {
			$dbr = DBConnect::getReadingConnect();
			$hits = $dbr->selectField(
				[ 'hit_counter' ],
				[ 'hits' => 'page_counter' ],
				[ 'page_id' => $title->getArticleID() ],
				__METHOD__
			);

			if ( $hits !== false ) {
				$views = (int)$hits;
				self::cacheStore( $cache, $key, $views );
			} else {
				$views = 0;
			}
		}

		return (int)$views;
	}

	public static function views(): ?int {
		# Should check for MiserMode here
		$cache = ObjectCache::getInstance( CACHE_ANYTHING );
		$key = $cache->makeKey( 'sitestats', 'activeusers-updated' );
		// Re-calculate the count if the last tally is old...
		if ( !isset( self::$mViews ) ) {
			self::$mViews = $cache->get( $key );
			wfDebugLog( "HitCounters", __METHOD__
				. ": got " . var_export( self::$mViews, true ) .
				" from cache." );
			if ( !self::$mViews || self::$mViews == 1 ) {
				$dbr = DBConnect::getReadingConnect();
				self::$mViews = $dbr->selectField(
					'hit_counter', 'SUM(page_counter)', '', __METHOD__
				);
				wfDebugLog( "HitCounters", __METHOD__ . ": got " .
					var_export( self::$mViews, true ) .
					" from select." );
				self::cacheStore( $cache, $key, self::$mViews );
			}
		}
		return self::$mViews;
	}

	/**
	 * {{NUMBEROFVIEWS}} - number of total views of the site
	 *
	 * We can't choose our parameters since this is a hook and we
	 * don't really need to use the $parser and $cache parameters.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public static function numberOfViews(
		Parser $parser, PPFrame $frame, $args
	): ?int {
		return self::views();
	}

	/**
	 * {{NUMBEROFPAGEVIEWS}} - number of total views of the page
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public static function numberOfPageViews(
		Parser $parser, PPFrame $frame, $args
	): ?int {
		return self::getCount( $frame->getTitle() );
	}
}
