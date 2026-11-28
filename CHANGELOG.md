# Changelog

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/) and
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Fixed
- Hide icons with `#hidetitleicon` reliably: the setting is now stored as page property, so it also applies to pages served from the parser cache, and it no longer hides the icons of other pages handled in the same request, such as other search hits. Icons removed from a page no longer reappear when the page is parsed again. Pages that use `#hidetitleicon` have to be parsed again (purge, null edit or `refreshLinks`) once [`6ea6bc0`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/6ea6bc0)
- Do not nest the icon links in search hit titles and keep the highlighted title of the search engine [`338b5ad`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/338b5ad)
- Find the icons of the main namespace independently of the language of the user [`a266cce`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/a266cce)
- Show the file name without prefix and extension as tooltip, for example `Logo.v2` instead of `File:Logo` [`849e3cd`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/849e3cd)
- Render OOUI icons only for plain `.svg` file names, so a name can no longer point outside the OOUI icon directory [`f0c39c1`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/f0c39c1)
- Use the name of the defining page as tooltip when `$wgTitleIcon_UseFileNameAsToolTip` is off, instead of a debug string like `[0:Some_page]` [`e7c4917`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/e7c4917)

### Changed
- Test against MariaDB 12.3 and update `mediawiki-codesniffer` to 52.0.2 and `eslint-config-wikimedia` to 0.33.1 [`09f71db`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/09f71db), [`d41f8d3`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/d41f8d3), [`d6ba9b1`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/d6ba9b1)
- Use the namespaced MediaWiki classes instead of the deprecated global aliases [`0f17aa1`](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/commit/0f17aa1)
