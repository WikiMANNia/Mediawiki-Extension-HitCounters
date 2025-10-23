<?php

namespace MediaWiki\Extension\HitCounters;

use DatabaseUpdater;

/* hack to get at protected member */
class HCUpdater extends DatabaseUpdater {

	public static function getDBUpdates( DatabaseUpdater $updater ): void {

		// Use $sqlDirBase for DBMS-independent patches and $base for
		// DBMS-dependent patches
		$dbType = $updater->getDB()->getType();
		$sqlDirBase = dirname( __DIR__ ) . '/sql';
		$base = "$sqlDirBase/$dbType";

		$updater->addExtensionTable( 'hit_counter_extension', "$base/hit_counter_extension.sql" );
		$updater->addExtensionTable( 'hit_counter', "$base/hit_counter.sql" );
	}

	public function clearExtensionUpdates(): void {
		$this->extensionUpdates = [];
	}

	/**
	 * @return array[]
	 */
	public function getCoreUpdateList() {
		$updater = DatabaseUpdater::newForDb(
			$this->db, (bool)$this->shared, $this->maintenance
		);
		return $updater->getCoreUpdateList();
	}

	/**
	 * @inheritDoc
	 * @return array[]
	 */
	protected function getInitialUpdateKeys() {
		return [];
	}
}
