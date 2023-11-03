<?php

namespace MediaWiki\Extension\HitCounters;

use MWNamespace;

/**
 * Settings is a singleton - used to get access to DB.
 */

/**
* backward compatibility
* fix an issue that was introduced here:
* https://github.com/WikiMANNia/mediawiki-extensions-HitCounters/commit/822140f6d96974f5051449837e7f46a771d5f6a5#diff-1b6cef982bd7ace2232d91536185b83a
* @since 1.31.15
* @since 1.35.3
* define( 'DB_PRIMARY', ILoadBalancer::DB_PRIMARY )
* DB_PRIMARY remains undefined in MediaWiki before v1.31.15/v1.35.3
* @since 1.28.0
* define( 'DB_REPLICA', ILoadBalancer::DB_REPLICA )
* DB_REPLICA remains undefined in MediaWiki before v1.28
*/
defined('DB_PRIMARY') or define('DB_PRIMARY', DB_MASTER);
defined('DB_REPLICA') or define('DB_REPLICA', DB_SLAVE);


class DBConnect {

	private static $instance;

	private function __construct() { }

	private function __clone() { }

	/**
	 * @return self
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			// Erstelle eine neue Instanz, falls noch keine vorhanden ist.
			self::$instance = new self();
		}

		// Liefere immer die selbe Instanz.
		return self::$instance;
	}

	public static function getReadingConnect() {
		return wfGetDB( DB_REPLICA );
	}

	public static function getWritingConnect() {
		return wfGetDB( DB_PRIMARY );
	}

	public static function getQueryInfo() {

		$namespaces = MWNamespace::getContentNamespaces();

		return [
			'tables' => [
				'p' => 'page',
				'h' => 'hit_counter'
			],
			'fields' => [
				'namespace' => 'p.page_namespace',
				'title'  => 'p.page_title',
				'value'  => 'h.page_counter',
				'length' => 'p.page_len'
			],
			'conds' => [
				'p.page_is_redirect' => 0,
				'p.page_namespace' => $namespaces
			],
			'join_conds' => [
				'p' => [
					'INNER JOIN', [
						'p.page_id = h.page_id'
					]
				]
			]
		];
	}
}
