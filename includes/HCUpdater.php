<?php

namespace MediaWiki\Extension\HitCounters;

use DatabaseUpdater;

/* hack to get at protected member */
class HCUpdater extends DatabaseUpdater {

	public static function getDBUpdates( DatabaseUpdater $updater ): void {

		$type = $updater->getDB()->getType();

		// Use $sqlDirBase for DBMS-independent patches and $base for
		// DBMS-dependent patches
		$base = $sqlDirBase = __DIR__ . '/../sql/' . $type;

		$updater->addExtensionTable( 'hit_counter_extension', "$base/hit_counter_extension.sql" );
		$updater->addExtensionTable( 'hit_counter', "$base/page_counter.sql" );
	}

	public function clearExtensionUpdates(): void {
		$this->extensionUpdates = [];
	}

	/**
	 * @return array[]
	 */
	public function getCoreUpdateList() {
		$updater = DatabaseUpdater::newForDb(
			$this->db, $this->shared, $this->maintenance
		);
		return $updater->getCoreUpdateList();
	}
}
