<?php

/*
 * Copyright (c) 2013-2016 The MITRE Corporation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace MediaWiki\Extension\TitleIcon;

use Config;
use HtmlArmor;
use Language;
use MediaWiki\Json\JsonCodec;
use MediaWiki\Linker\Linker;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MainConfigNames;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\PageProps;
use MediaWiki\Page\PageReference;
use MediaWiki\Parser\ParserFactory;
use Mediawiki\Title\Title;
use Message;
use Parser;
use RepoGroup;
use Sanitizer;
use TitleParser;

class IconManager {
	/** @var Config */
	private $config;

	/** @var ParserFactory */
	private $parserFactory;

	/** @var TitleParser */
	private $titleParser;

	/** @var Language */
	private $contentLanguage;

	/** @var PageProps */
	private $pageProps;

	/** @var RepoGroup */
	private $repoGroup;

	/** @var LinkRenderer */
	private $linkRenderer;

	/** @var JsonCodec */
	private $jsonCodec;

	/** @var SMWInterface */
	private $smwInterface;

	/** Name of the page property that lists the icon sources hidden on a page, comma separated. */
	public const HIDE_PROPERTY_NAME = 'titleicons-hide';

	private const HIDE_PAGE = 'page';
	private const HIDE_CATEGORY = 'category';
	private const HIDE_NAMESPACE = 'namespace';
	private const HIDE_TARGETS = [ self::HIDE_PAGE, self::HIDE_CATEGORY, self::HIDE_NAMESPACE ];

	/**
	 * @param Config $config
	 * @param ParserFactory $parserFactory
	 * @param TitleParser $titleParser
	 * @param Language $contentLanguage
	 * @param PageProps $pageProps
	 * @param RepoGroup $repoGroup
	 * @param LinkRenderer $linkRenderer
	 * @param JsonCodec $jsonCodec
	 * @param SMWInterface $smwInterface
	 */
	public function __construct(
		Config $config,
		ParserFactory $parserFactory,
		TitleParser $titleParser,
		Language $contentLanguage,
		PageProps $pageProps,
		RepoGroup $repoGroup,
		LinkRenderer $linkRenderer,
		JsonCodec $jsonCodec,
		SMWInterface $smwInterface
	) {
		$this->config = $config;
		$this->parserFactory = $parserFactory;
		$this->titleParser = $titleParser;
		$this->contentLanguage = $contentLanguage;
		$this->pageProps = $pageProps;
		$this->repoGroup = $repoGroup;
		$this->linkRenderer = $linkRenderer;
		$this->jsonCodec = $jsonCodec;
		$this->smwInterface = $smwInterface;
	}

	/**
	 * @param PageReference $page
	 * @return PageIdentity[]
	 */
	private function getCategories( PageReference $page ): array {
		$result = [];
		$categories = Title::castFromPageReference( $page )->getParentCategories();
		foreach ( $categories as $category => $article ) {
			$result[] = Title::newFromLinkTarget( $this->titleParser->parseTitle( $category ) );
		}
		return $result;
	}

	/**
	 * Icons of a page: the ones defined on the page itself, on its categories and on its namespace, and the ones
	 * from the Semantic MediaWiki property. This only depends on the stored page data, so it gives the same result
	 * whether or not the page has just been parsed.
	 *
	 * @param PageIdentity $page
	 * @return Icon[]
	 */
	public function getIcons( PageIdentity $page ): array {
		$hidden = $this->getHiddenSources( $page );

		/** @var Icon[] $icons */
		$icons = [];

		/** @var PageReference[] $queryTargets */
		$queryTargets = [];

		if ( !in_array( self::HIDE_PAGE, $hidden, true ) ) {
			$queryTargets[] = $page;
			$this->addPagePropsIcons( $icons, $page );
		}

		if ( !in_array( self::HIDE_CATEGORY, $hidden, true ) ) {
			foreach ( $this->getCategories( $page ) as $category ) {
				$queryTargets[] = $category;
				$this->addPagePropsIcons( $icons, $category );
			}
		}

		if ( !in_array( self::HIDE_NAMESPACE, $hidden, true ) ) {
			$namespacePage = $this->getNamespacePage( $page );
			// if the Blanknamespace message contains invalid characters (e.g. in the x-xss language),
			// $namespacePage is null as the title is invalid
			if ( $namespacePage !== null ) {
				$queryTargets[] = $namespacePage;
				$this->addPagePropsIcons( $icons, $namespacePage );
			}
		}

		foreach ( $queryTargets as $queryTarget ) {
			$smwIcons = $this->smwInterface->getPropertyValues(
				$queryTarget,
				$this->config->get( 'TitleIcon_TitleIconPropertyName' )
			);
			foreach ( $smwIcons as $smwIcon ) {
				self::addIcon( $icons, new Icon( $queryTarget, $smwIcon, Icon::ICON_TYPE_FILE ) );
			}
		}

		return $icons;
	}

	/**
	 * The page that holds the icons of a namespace, named after the namespace in the content language.
	 *
	 * @param PageReference $page
	 * @return Title|null
	 */
	private function getNamespacePage( PageReference $page ): ?Title {
		$namespaceText = $this->contentLanguage->getNsText( $page->getNamespace() );
		if ( $namespaceText === '' ) {
			$namespaceText = Message::newFromKey( 'Blanknamespace' )->inContentLanguage()->plain();
		}
		return Title::newFromText( $namespaceText, NS_PROJECT );
	}

	/**
	 * @param Icon[] &$icons
	 * @param PageIdentity $page
	 */
	private function addPagePropsIcons( array &$icons, PageIdentity $page ): void {
		$stored = $this->pageProps->getProperties( $page, Icon::ICON_PROPERTY_NAME );
		$pageId = $page->getId();
		if ( isset( $stored[$pageId] ) ) {
			foreach ( $this->deserializeIcons( $stored[$pageId] ) as $icon ) {
				self::addIcon( $icons, $icon );
			}
		}
	}

	/**
	 * @param string|null $json
	 * @return Icon[]
	 */
	private function deserializeIcons( ?string $json ): array {
		$result = [];
		$icons = $json ? $this->jsonCodec->deserialize( $json ) : null;
		if ( $icons ) {
			foreach ( $icons as $icon ) {
				// TODO: Temporary compatibility for MediaWiki <1.40, see T312589
				if ( !( $icon instanceof Icon ) ) {
					$icon = $this->jsonCodec->deserialize( $icon );
				}
				if ( $icon ) {
					$result[] = $icon;
				}
			}
		}
		return $result;
	}

	/**
	 * Add an icon to the output of the page that is being parsed. It is stored as page property.
	 *
	 * @param Parser $parser
	 * @param string $type
	 * @param string|null $icon
	 * @param PageReference|null $link
	 */
	public function parseIcons( Parser $parser, string $type, ?string $icon, ?PageReference $link ): void {
		$source = $parser->getPage();
		if ( !$icon || !$source ) {
			return;
		}

		$output = $parser->getOutput();
		$stored = $output->getPageProperty( Icon::ICON_PROPERTY_NAME );
		$icons = $this->deserializeIcons( is_string( $stored ) ? $stored : null );
		$count = count( $icons );
		self::addIcon( $icons, new Icon( $source, $icon, $type, $link ) );
		if ( count( $icons ) !== $count ) {
			$output->setPageProperty( Icon::ICON_PROPERTY_NAME, $this->jsonCodec->serialize( $icons ) );
		}
	}

	/**
	 * @param Icon[] &$icons
	 * @param Icon $icon
	 */
	private static function addIcon( array &$icons, Icon $icon ): void {
		foreach ( $icons as $savedIcon ) {
			if ( $icon->getType() === $savedIcon->getType() && $icon->getIcon() === $savedIcon->getIcon() ) {
				return;
			}
		}
		$icons[] = $icon;
	}

	/**
	 * @param Icon[] $icons
	 * @return string
	 */
	public function getHTML( array $icons ): string {
		$iconhtml = '';
		foreach ( $icons as $icon ) {
			switch ( $icon->getType() ) {
				case Icon::ICON_TYPE_FILE:
					$iconhtml .= $this->getFileIconHTML( $icon );
					break;
				case Icon::ICON_TYPE_OOUI:
					$iconhtml .= $this->getOOUIIconHTML( $icon );
					break;
				case Icon::ICON_TYPE_UNICODE:
					$iconhtml .= $this->getUnicodeIconHTML( $icon );
			}
		}
		return $iconhtml;
	}

	/**
	 * Hide icons of the page that is being parsed. It is stored as page property, so it also applies when the
	 * page is later served from the parser cache.
	 *
	 * @param Parser $parser
	 * @param string|null $flag 'page', 'category', 'namespace', or 'all'
	 */
	public function hideTitleIcon( Parser $parser, ?string $flag ): void {
		$targets = self::parseHideFlag( $flag );
		if ( $targets === [] ) {
			return;
		}

		$output = $parser->getOutput();
		$stored = $output->getPageProperty( self::HIDE_PROPERTY_NAME );
		$current = is_string( $stored ) && $stored !== '' ? explode( ',', $stored ) : [];
		$hidden = array_values( array_intersect( self::HIDE_TARGETS, array_merge( $current, $targets ) ) );
		$output->setPageProperty( self::HIDE_PROPERTY_NAME, implode( ',', $hidden ) );
	}

	/**
	 * @param string|null $flag
	 * @return string[] the icon sources the flag hides
	 */
	private static function parseHideFlag( ?string $flag ): array {
		switch ( $flag ) {
			case self::HIDE_PAGE:
			case self::HIDE_CATEGORY:
			case self::HIDE_NAMESPACE:
				return [ $flag ];
			case 'all':
				return self::HIDE_TARGETS;
			default:
				return [];
		}
	}

	/**
	 * @param PageIdentity $page
	 * @return string[] the icon sources that are hidden on the page
	 */
	private function getHiddenSources( PageIdentity $page ): array {
		$flags = [];
		$stored = $this->pageProps->getProperties( $page, self::HIDE_PROPERTY_NAME );
		$pageId = $page->getId();
		if ( isset( $stored[$pageId] ) ) {
			$flags = explode( ',', (string)$stored[$pageId] );
		}
		$flags = array_merge( $flags, $this->smwInterface->getPropertyValues(
			$page,
			$this->config->get( 'TitleIcon_HideTitleIconPropertyName' )
		) );

		$hidden = [];
		foreach ( $flags as $flag ) {
			$hidden = array_merge( $hidden, self::parseHideFlag( $flag ) );
		}
		return array_values( array_unique( $hidden ) );
	}

	/**
	 * The tooltip of a file icon is the file name without namespace prefix and without file extension.
	 *
	 * @param LinkTarget $file
	 * @return string
	 */
	public static function getFileTooltip( LinkTarget $file ): string {
		return pathinfo( $file->getText(), PATHINFO_FILENAME );
	}

	/**
	 * @param Icon $icon
	 * @return string
	 */
	private function getFileIconHTML( Icon $icon ): string {
		$filename = $icon->getIcon();
		$filetitle = Title::newFromText( $filename, NS_FILE );
		$imagefile = $this->repoGroup->findFile( $filetitle );

		if ( $imagefile === false ) {
			return '';
		}

		if ( $this->config->get( 'TitleIcon_UseFileNameAsToolTip' ) ) {
			$tooltip = self::getFileTooltip( $filetitle );
		} else {
			$tooltip = $icon->getSource();
		}

		$linkTarget = $icon->getLink();
		if ( !$linkTarget ) {
			$linkTarget = $icon->getSource();
		}

		$frameParams = [
			'link-title' => $linkTarget,
			'alt' => $tooltip,
			'title' => $tooltip
		];

		$handlerParams = [
			'width' => '36',
			'height' => '36'
		];

		return Linker::makeImageLink(
			// resolved here, as the parser itself triggers the hooks that create this manager
			$this->parserFactory->getInstance(),
			$filetitle,
			$imagefile,
			$frameParams,
			$handlerParams
			) . "\u{00A0}";
	}

	/**
	 * @param Icon $icon
	 * @return string
	 */
	private function getOOUIIconHTML( Icon $icon ): string {
		$linkTitle = $icon->getLink();
		if ( !$linkTitle ) {
			$linkTitle = $icon->getSource();
		}
		$scriptPath = $this->config->has( MainConfigNames::ScriptPath )
			? $this->config->get( MainConfigNames::ScriptPath )
			: '';
		$url = $scriptPath . '/resources/lib/ooui/themes/wikimediaui/images/icons/' . $icon->getIcon();
		return $this->linkRenderer->makeLink(
			$linkTitle,
			new HtmlArmor( Linker::makeExternalImage( $url ) )
		) . "\u{00A0}";
	}

	/**
	 * @param Icon $icon
	 * @return string
	 */
	private function getUnicodeIconHTML( Icon $icon ): string {
		$linkTitle = $icon->getLink();
		if ( !$linkTitle ) {
			$linkTitle = $icon->getSource();
		}
		return $this->linkRenderer->makeLink(
			$linkTitle,
			Sanitizer::decodeCharReferences( $icon->getIcon() )
		) . "\u{00A0}";
	}
}
