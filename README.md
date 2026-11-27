# TitleIcon

[![CI](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/actions/workflows/ci.yml/badge.svg)](https://github.com/gesinn-it-pub/mediawiki-extensions-TitleIcon/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/gesinn-it-pub/mediawiki-extensions-TitleIcon/branch/master/graph/badge.svg)](https://app.codecov.io/gh/gesinn-it-pub/mediawiki-extensions-TitleIcon)

TitleIcon displays icons to the left of the page title and in front of the page titles in the search results.
Icons are defined on the page itself, on one of its categories or on its namespace.

Homepage: <https://www.mediawiki.org/wiki/Extension:Title_Icon>

## Requirements

- MediaWiki 1.43 or newer
- Optional: [Semantic MediaWiki](https://www.semantic-mediawiki.org/) to define icons with a property

## Installation

1. Place the `TitleIcon` directory in the `extensions/` directory of your wiki.
2. Add the following to `LocalSettings.php`:

   ```php
   wfLoadExtension( 'TitleIcon' );
   ```

3. Check `Special:Version` to verify the installation.

## Usage

### Define icons

Use these parser functions on the page, category page or namespace page that should carry the icon. The optional
second parameter is the page the icon links to; without it the icon links to the page that defines it.

| Parser function | Icon |
|---|---|
| `{{#titleicon_file:Logo.png}}` | an uploaded file, with or without the `File:` prefix |
| `{{#titleicon_ooui:wikiText.svg}}` | an [OOUI icon](https://doc.wikimedia.org/oojs-ui/master/demos/?page=icons) by file name |
| `{{#titleicon_unicode:&#x1F4BB;}}` | a unicode character or character reference |

Example with a link target: `{{#titleicon_file:Logo.png|Help:Icons}}`.

Where an icon is picked up from:

- **Page:** the page itself.
- **Category:** the category pages of the page, so every page of the category shows the icon.
- **Namespace:** the page named after the namespace in the `Project:` namespace, for example `Project:Help`. For the
  main namespace this is the page named by the `Blanknamespace` message of the content language, by default
  `Project:(Main)`.

With Semantic MediaWiki installed, the icon can also be given as a text property (default `Title Icon`) holding a
file name on the page, category page or namespace page.

### Hide icons

`{{#hidetitleicon:page}}`, `{{#hidetitleicon:category}}`, `{{#hidetitleicon:namespace}}` and
`{{#hidetitleicon:all}}` hide the icons of that source on the page that contains the function. The semantic property
`Hide Title Icon` takes the same values.

The hidden sources are stored when the page is parsed, so pages that use `#hidetitleicon` have to be parsed again
(purge, null edit or `refreshLinks`) after updating from a version before this was stored.

### API

`action=query&prop=titleicons&titles=Main%20Page` returns the icons of the given pages.

## Configuration

| Setting | Default | Meaning |
|---|---|---|
| `$wgTitleIcon_EnableIconInPageTitle` | `true` | show icons in front of the page title |
| `$wgTitleIcon_EnableIconInSearchTitle` | `true` | show icons in the search results |
| `$wgTitleIcon_CSSSelector` | `"#firstHeading"` | element the icons are inserted into |
| `$wgTitleIcon_UseFileNameAsToolTip` | `true` | use the file name as tooltip of file icons |
| `$wgTitleIcon_TitleIconPropertyName` | `"Title Icon"` | semantic property that holds an icon file |
| `$wgTitleIcon_HideTitleIconPropertyName` | `"Hide Title Icon"` | semantic property that hides icons |

## Development

The tests run in a container with [docker-compose-ci](https://github.com/gesinn-it-pub/docker-compose-ci), which is
included as the `build` submodule:

```shell
git submodule update --init
make ci            # lint, static analysis, PHPUnit and the JavaScript checks
make ci-coverage   # the same, with a coverage report
```

## License

MIT, see [COPYING](COPYING).
