# HitCounters

Die Pflege dieses Forks der MediaWiki-Erweiterung [HitCounters](https://www.mediawiki.org/wiki/Extension:HitCounters/de) wird von WikiMANNia verwaltet.

The maintenance of this fork of the MediaWiki extension [HitCounters](https://www.mediawiki.org/wiki/Extension:HitCounters) is managed by WikiMANNia.

El mantenimiento de esta bifurcación de la extensión de MediaWiki [HitCounters](https://www.mediawiki.org/wiki/Extension:HitCounters/es) está gestionado por WikiMANNia.

## Configuration

Verfügbare [Parameter](https://www.mediawiki.org/wiki/Template:Extension#parameters/de) für LocalSettings.php. / Available [parameters](https://www.mediawiki.org/wiki/Template:Extension#parameters) for LocalSettings.php. / [Parámetros](https://www.mediawiki.org/wiki/Template:Extension#parameters/es) disponibles para LocalSettings.php.

* $wgDisableCounters = `false`;

When set to `true`, it disables the notice in the pages footer saying "This page has been accessed 256 times." as well as the [special page](https://www.mediawiki.org/wiki/Manual:Special_pages) `PopularPages`.

* $wgEnableCountersAtTheFooter = `false`;

When set to `true`, it enables the notice in the pages footer saying "This page has been accessed 256 times.", which is displayed assuming that `$wgDisableCounters` is set to `false`.

* $wgHitcounterUpdateFreq = `1`;

Sets how often page counters should be updated. Default value is `1`.

Benutzerdefinierte [Einstellungen](https://www.mediawiki.org/wiki/Help:Preferences/de). / Custom [settings](https://www.mediawiki.org/wiki/Help:Preferences). / [Configuración](https://www.mediawiki.org/wiki/Help:Preferences/es) personalizada.
* `Exempt`                             – Exclude your own page views from statistics (Default is `false`)
* `TextLength`                         – Adds the [PageId](https://www.mediawiki.org/wiki/Help:Page_ID) to the special page `PopularPages` (Default is `false`)
* `PageId`                             – Adds the [TextLength](https://www.mediawiki.org/wiki/Manual:Page_table#page_len) to the special page `PopularPages` (Default is `false`)
* `NumberOfMostViewedPages`            – Set the Number of Most Viewed Pages in [Statistics](https://www.mediawiki.org/wiki/Special:Statistics) (Default is `50`)

## Version history

v0.2.0

As found [here](https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/HitCounters/+/refs/heads/REL1_25) (07 Nov 2015)

v0.3.0.8

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

v0.3.3

- Add: Output of the text length in the special page `Popular Pages`
- Add: Variables for configuration in `LocalSettings.php`

v0.3.4

- Update - 28 Sep 2022: Replace Parser::getFunctionLang() with ::getTargetLanguage(). Parser::getFunctionLang() is being deprecated. [Bug: T318860](https://github.com/wikimedia/mediawiki-extensions-HitCounters/commit/9af63d30b535efd4bc181736adee53dc70e53a3a)

v0.3.5

Add global variable `$wgNumberOfMostViewedPages`

v0.4.0

Refactoring: Add file `HitCountersDBConnect.php` and class `DBConnect`

v0.4.1

Code Review.

v0.5.0

Benutzerdefinierte [Einstellungen](https://www.mediawiki.org/wiki/Help:Preferences/de). / Custom [settings](https://www.mediawiki.org/wiki/Help:Preferences). / [Configuración](https://www.mediawiki.org/wiki/Help:Preferences/es) personalizada.

v0.5.2

- Custom setting: "Exclude your own page views from statistics"

v0.5.3

- Hard setting: "Exclude admins page views from statistics"

v0.5.4

- Refactoring: Change namespace from `HitCounters` to `MediaWiki\Extension\HitCounters`
- Refactoring: Move sql files from `sql` to `sql\mysql`

v0.5.5

- Initial cleanup, renaming files, 28 Nov 2023, Mark A. Hershberger

v0.5.6

- Default values for custom settings.

v0.5.7

- Default values for custom settings with "DefaultUserOptions".

v0.6.0

- Add database type `sqlite`
- Refactoring: Rename file name `page_counter.sql` into `hit_counter.sql`
- Refactoring: Renaming in `i18n`

v0.6.1

- The extension crashed if namespaces were not defined correctly in ´LocalSettings.php´. This has been fixed.

## Compatibility

This extension works from REL1_25 and has been tested up to MediaWiki version `1.25.6`, `1.31.16`, and `1.35.14`.

## Background

In [MediaWiki 1.25](https://gerrit.wikimedia.org/r/150699/), hit counters have been removed.  The reason is given in the commit message:

: The hitcounter implementation in MediaWiki is flawed and needs removal. For proper metrics, it is suggested to use something like Piwik or Google Analytics.

More discussion can be found at [mediawiki.org](https://www.mediawiki.org/wiki/RFC/Removing_hit_counters_from_MediaWiki_core).

If you wish to continue using the HitCounter's despite the flawed implementation, this extension should help.

Note that some steps will be needed to maintain you current hit count.  When those steps are understood, they'll be documented.
