<?php

namespace HitCounters;

use MediaWiki\MediaWikiServices;

/**
 * Settings is a singleton - used to get access to DB.
 */

class DBConnect {

	private static $instance;
	private static $services;

	private function __construct() { }

	private function __clone() { }

	/**
	 * @return self
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			// Erstelle eine neue Instanz, falls noch keine vorhanden ist.
			self::$instance = new self();
			self::$services = MediaWikiServices::getInstance();
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

	public static function getWritingConnectFromLoadBalancer() {
		$lb = self::$services->getDBLoadBalancer();
		return $lb->getConnection( DB_PRIMARY, [], false );
	}

	public static function getQueryInfo() {

		$namespaces = MediaWikiServices::getInstance()
					->getNamespaceInfo()
					->getContentNamespaces();

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
