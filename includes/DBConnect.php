<?php

namespace MediaWiki\Extension\HitCounters;

use MediaWiki\MediaWikiServices;

/**
 * Settings is a singleton - used to get access to DB.
 */

class DBConnect {

	public static function getReadingConnect() {
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		return $lb->getMaintenanceConnectionRef( DB_REPLICA );
	}

	public static function getWritingConnect() {
		return wfGetDB( DB_PRIMARY );
	}

	public static function getWritingConnectFromLoadBalancer() {
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		return $lb->getConnection( DB_PRIMARY, [], false );
	}

	/**
	 * @return array
	 */
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