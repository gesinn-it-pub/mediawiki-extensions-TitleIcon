# Changelog

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/) and
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [7.0.0] - 2026-11-28

Requires MediaWiki 1.43 or newer and is tested with MediaWiki 1.43 and 1.46. Icons hidden with `#hidetitleicon` now
stay hidden for pages served from the parser cache, and several problems in the search results and in the tooltips are
fixed. MediaWiki 1.40 to 1.42 remain supported on the `6.x` line.

### Breaking Changes
- Require MediaWiki 1.43 or newer; drop support for MediaWiki 1.40 to 1.42 [`0ddc33e`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/0ddc33e)
- Change the PHP interface of the icon manager: `getHTML()` takes the icons, `parseIcons()` and `hideTitleIcon()` take the parser, `saveIcons()` and the `ParserAfterParse` hook handler are removed [`6ea6bc0`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/6ea6bc0)

### Fixed
- Keep icons hidden with `#hidetitleicon` for pages served from the parser cache, stop it from hiding the icons of other pages in the same request, and stop removed icons from reappearing when a page is parsed again. Pages that use `#hidetitleicon` need to be parsed again once (purge, null edit or `refreshLinks`) [`6ea6bc0`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/6ea6bc0)
- Do not nest the icon links in search hit titles and keep the highlighted title of the search engine [`338b5ad`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/338b5ad)
- Find the icons of the main namespace whatever language the user has [`a266cce`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/a266cce)
- Show the file name without prefix and extension as tooltip, for example `Logo.v2` instead of `File:Logo` [`849e3cd`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/849e3cd)
- Show the name of the defining page as tooltip when `$wgTitleIcon_UseFileNameAsToolTip` is off, instead of a debug string [`e7c4917`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/e7c4917)
- Render OOUI icons only for plain `.svg` file names, so a name can no longer point outside the OOUI icon directory [`f0c39c1`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/f0c39c1)
- Handle invalid characters in the message that names the main namespace [`618340f`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/618340f)

### Changed
- Document installation, parser functions, hiding of icons and settings in the README [`947fe79`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/947fe79)
- Update translations [`06c83b1`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/06c83b1)

[Unreleased]: https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/compare/7.0.0...HEAD
[7.0.0]: https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/compare/6.3.0...7.0.0
[6.3.0]: https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/releases/tag/6.3.0
