<?php

namespace MediaWiki\Extension\HitCounters;

use DatabaseUpdater;

/* hack to get at protected member */
class HCUpdater extends DatabaseUpdater {
	public static function getDBUpdates( DatabaseUpdater $updater ) {

		$type = $updater->getDB()->getType();
		if ( !in_array( $type, [ 'mysql', 'postgres' ] ) ) {
			throw new Exception( "HitCounters extension does not currently support $type database." );
		}

		// Use $sqlDirBase for DBMS-independent patches and $base for DBMS-dependent patches
		$sqlDirBase = __DIR__ . '/../../sql';
		$base = $sqlDirBase . '/' . $type;

		/* This is an ugly abuse to rename a table. */
		$updater->modifyExtensionField( 'hitcounter', 'hc_id', "$base/rename_table.sql" );
		$updater->addExtensionTable( 'hit_counter_extension', "$base/hit_counter_extension.sql" );
		$updater->addExtensionTable( 'hit_counter', "$base/page_counter.sql" );
		$updater->dropExtensionField( 'page', 'page_counter', "$sqlDirBase/drop_field.sql" );
	}

	public function clearExtensionUpdates() {
		$this->extensionUpdates = [];
	}

	public function getCoreUpdateList() {
		$updater = DatabaseUpdater::newForDb( $this->db, $this->shared, $this->maintenance );
		return $updater->getCoreUpdateList();
	}
}
