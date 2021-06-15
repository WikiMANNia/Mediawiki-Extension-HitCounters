# HitCounters

Die Pflege dieses Forks der MediaWiki-Erweiterung [HitCounters](https://www.mediawiki.org/wiki/Extension:HitCounters) wird von WikiMANNia verwaltet.

The maintenance of this fork of the MediaWiki extension [HitCounters](https://www.mediawiki.org/wiki/Extension:HitCounters) is managed by WikiMANNia.

El mantenimiento de esta bifurcación de la extensión de MediaWiki [HitCounters](https://www.mediawiki.org/wiki/Extension:HitCounters) está gestionado por WikiMANNia.

## Version history

v0.3.0

As found [here](https://github.com/wikimedia/mediawiki-extensions-HitCounters/releases/tag/0.3) (24 Nov 2015)

v0.3.0.1-0.3.0.8

- Fix: Several translation issues
- Fix - 23 Nov 2017: {{NUMBEROFVIEWS}} in MediaWiki 1.29 - [Bug: T142127](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/213b2c6e40b5ef332381c82655d3ce227ace5c71)
- Update - 14 Aug 2018: Updating mediawiki/mediawiki-codesniffer to 18.0.0 - [diff](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/822140f6d96974f5051449837e7f46a771d5f6a5#diff-df7ea4e51a49240fd52f0adb1b2ad9b2e2c8af3ee6a843defd40fd270e69595b)
- Add - 30 Jul 2018: Call AbuseFilter hooks for its page-views variable - [Bug: T159069](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/33adf8a130cb72e3c9c246bb0139adbc62527df7)
- Fix - 22 Aug 2018: Type hint against IContextSource instead of RequestContext [diff](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/c0afb68eb2704e55508f1d0771432e0400a50dbd)
- Fix - 25 Aug 2018: Use new syntax for AbuseFilter variables and deprecate the old ones - [Bug: T173889](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/a3fc5c057960d3229591dd8139d3d76cfd284604)
- Fix -  1 Sep 2018: Escaping order with Language::convert. [diff](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/3befcbb027f12017195bd1cea373d984bd171bd5)
- Fix - 31 May 2019: Fix cache key - [Bug: T163957](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/04c68575651b6899bf4029934a0a9017305be6a5)
- Fix -  8 Jul 2019: Remove SiteStatsUpdate update that does nothing [diff](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/c1634b1f32cce89b908c01e074673e72b356a033)
- Fix - 18 Nov 2019: Use main cache to avoid issues with UTF-8 keys - [diff](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/dcba24835d67d9260d11b7fb8d0a9a90de9eff16)
- Improve - 9 Jan 2021: Use IDatabase::selectField to get single value - [diff](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/839568dfdf3eb0d4a15f5f00e90a53ca91285639)

v0.3.1

- Add -  8 Feb 2020: Support for PostgreSQL to the HitCounters extension - [Bug: T110655](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/ac04330d4d416dab505f19b0766a0c8ec367034d)

v0.3.1.2

- Link to the special page in the documental message
- Localisation updates from https://translatewiki.net.
- Fix - 27 Mar 2020: Use the magic word value cache for magic word handlers - [Bug: T236813](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/564f55661b8a44a4cf5a681078d2c4f95d2a2426)
- 29 Mar 2021: Stop using $wgContLang global - [diff](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/35624f0b2d75f1896e38a81aeb77c696d87a2c0b)

v0.3.2

Version 0.3.2 is compatible with 1.25+ until 1.35+.

- Fixed a problem with backward compatibility to version 1.25

v0.3.2.1

Version 0.3.2.1 is compatible with 1.27+ until 1.35+.

- Fix -  7 Dec 2020: Replaced removed wfMemcKey (deprecated since 1.30) with makeKey (since 1.27) - [Bug: T266502](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/d31e0b8fe417bea31275c8be47b54a6adc6c75cc)
- Fix - 16 Mar 2021: Fix replacement for wfGetMainCache - [Bug: T277494](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/c4c98d3dea5887fd49b72a22ded7c54fade49a60)

v0.3.2.2

Version 0.3.2.2 is compatible with 1.32+ until 1.35+.

- Fix - 10 Dec 2020: Avoid calls to deprecated Database::onTransactionIdle() method (since 1.32) - [diff](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/ba48ca56e9a271eeb14dd55a83dce8cd5e4e52ac)

v0.3.2.3

Version 0.3.2.3 is compatible with 1.35+.

- Update - Use Hook [SkinAddFooterLinks](https://www.mediawiki.org/wiki/Manual:Hooks/SkinAddFooterLinks) (since 1.35) instead of [SkinTemplateOutputPageBeforeExec](https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateOutputPageBeforeExec). (deprecated since 1.35)

v0.3.3

- Add: Output of the text length in the special page "Popular Pages"
- Add: Variables for configuration in "LocalSettings.php"

v0.3.4
- Use: MediaWikiServices

# Default settings

* $wgDisableCounters = false;              Set to true to disable them completely.
* $wgEnableAddPageId = false;              Set to true to display the page id on [[Special:PopularPages]].
* $wgEnableAddTextLength = false;          Set to true to display the page length on [[Special:PopularPages]].
* $wgEnableCountersAtTheFooter = true;     Set to false to disable them at the footer.
* $wgHitcounterUpdateFreq = 1;

## Background

In [MediaWiki 1.25](https://gerrit.wikimedia.org/r/150699/), hit counters have been removed.  The reason is given in the commit message:

: The hitcounter implementation in MediaWiki is flawed and needs removal. For proper metrics, it is suggested to use something like Piwik or Google Analytics.

More discussion can be found at [mediawiki.org](https://www.mediawiki.org/wiki/RFC/Removing_hit_counters_from_MediaWiki_core).

If you wish to continue using the HitCounter's despite the flawed implementation, this extension should help.

Note that some steps will be needed to maintain you current hit count.  When those steps are understood, they'll be documented.
