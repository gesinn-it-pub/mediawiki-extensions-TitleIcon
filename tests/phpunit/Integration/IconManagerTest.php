<?php

/*
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

namespace MediaWiki\Extension\TitleIcon\Test;

use MediaWiki\Extension\TitleIcon\Icon;
use MediaWiki\Page\PageReferenceValue;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 */
class IconManagerTest extends MediaWikiIntegrationTestCase {
	private function setPageContent(
		string $testPageContent,
		?string $testCategoryContent = null,
		?string $testNamespaceContent = null
	): array {
		$result = [];
		if ( $testNamespaceContent ) {
			$result['namespace'] = $this->insertPage( '(Main)', $testNamespaceContent, NS_PROJECT )['title'];
		}
		if ( $testCategoryContent ) {
			$result['category'] = $this->insertPage( 'Test Category', $testCategoryContent, NS_CATEGORY )['title'];
			$testPageContent .= '[[Category:Test Category]]';
		}
		$result['page'] = $this->insertPage( 'Test Page', $testPageContent, NS_MAIN )['title'];
		return $result;
	}

	public static function provideGetIcons() {
		$page = PageReferenceValue::localReference( NS_MAIN, 'Test Page' );
		$category = PageReferenceValue::localReference( NS_CATEGORY, 'Test Category' );
		$namespace = PageReferenceValue::localReference( NS_PROJECT, '(Main)' );
		$link = PageReferenceValue::localReference( NS_MAIN, 'Another Page' );
		yield [
			'{{#titleicon_file:Icon1.png}}',
			null,
			null,
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE )
			],
			'page icon'
		];
		yield [
			'',
			'{{#titleicon_file:Icon2.png}}',
			null,
			[
				new Icon( $category, 'Icon2.png', Icon::ICON_TYPE_FILE )
			],
			'category icon'
		];
		yield [
			'',
			null,
			'{{#titleicon_file:Icon3.png}}',
			[
				new Icon( $namespace, 'Icon3.png', Icon::ICON_TYPE_FILE )
			],
			'namespace icon'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}',
			'{{#titleicon_file:Icon2.png}}',
			null,
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE ),
				new Icon( $category, 'Icon2.png', Icon::ICON_TYPE_FILE )
			],
			'page and category icons'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}',
			null,
			'{{#titleicon_file:Icon3.png}}',
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE ),
				new Icon( $namespace, 'Icon3.png', Icon::ICON_TYPE_FILE )
			],
			'page and namespace icons'
		];
		yield [
			'',
			'{{#titleicon_file:Icon2.png}}',
			'{{#titleicon_file:Icon3.png}}',
			[
				new Icon( $category, 'Icon2.png', Icon::ICON_TYPE_FILE ),
				new Icon( $namespace, 'Icon3.png', Icon::ICON_TYPE_FILE )
			],
			'category and namespace icons'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}',
			'{{#titleicon_file:Icon2.png}}',
			'{{#titleicon_file:Icon3.png}}',
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE ),
				new Icon( $category, 'Icon2.png', Icon::ICON_TYPE_FILE ),
				new Icon( $namespace, 'Icon3.png', Icon::ICON_TYPE_FILE )
			],
			'page, category, and namespace icons'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}{{#hidetitleicon:page}}',
			'{{#titleicon_file:Icon2.png}}',
			'{{#titleicon_file:Icon3.png}}',
			[
				new Icon( $category, 'Icon2.png', Icon::ICON_TYPE_FILE ),
				new Icon( $namespace, 'Icon3.png', Icon::ICON_TYPE_FILE )
			],
			'hide page icon'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}{{#hidetitleicon:category}}',
			'{{#titleicon_file:Icon2.png}}',
			'{{#titleicon_file:Icon3.png}}',
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE ),
				new Icon( $namespace, 'Icon3.png', Icon::ICON_TYPE_FILE )
			],
			'hide category icon'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}{{#hidetitleicon:namespace}}',
			'{{#titleicon_file:Icon2.png}}',
			'{{#titleicon_file:Icon3.png}}',
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE ),
				new Icon( $category, 'Icon2.png', Icon::ICON_TYPE_FILE )
			],
			'hide namespace icon'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}{{#hidetitleicon:category}}{{#hidetitleicon:namespace}}',
			'{{#titleicon_file:Icon2.png}}',
			'{{#titleicon_file:Icon3.png}}',
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE )
			],
			'multiple hides'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}{{#hidetitleicon:all}}',
			'{{#titleicon_file:Icon2.png}}',
			'{{#titleicon_file:Icon3.png}}',
			[
			],
			'hide all icons'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}'
			. '{{#hidetitleicon:page}}{{#hidetitleicon:category}}{{#hidetitleicon:namespace}}',
			'{{#titleicon_file:Icon2.png}}',
			'{{#titleicon_file:Icon3.png}}',
			[
			],
			'hide all icons'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}{{#hidetitleicon:bogus}}',
			'{{#titleicon_file:Icon2.png}}',
			'{{#titleicon_file:Icon3.png}}',
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE ),
				new Icon( $category, 'Icon2.png', Icon::ICON_TYPE_FILE ),
				new Icon( $namespace, 'Icon3.png', Icon::ICON_TYPE_FILE )
			],
			'bogus hide value ignores hide'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}{{#hidetitleicon:page}}{{#hidetitleicon:bogus}}',
			'{{#titleicon_file:Icon2.png}}',
			'{{#titleicon_file:Icon3.png}}',
			[
				new Icon( $category, 'Icon2.png', Icon::ICON_TYPE_FILE ),
				new Icon( $namespace, 'Icon3.png', Icon::ICON_TYPE_FILE )
			],
			'bogus hide value ignores hide with valid hide'
		];
		yield [
			'{{#titleicon_file:File:Icon1.png}}',
			null,
			null,
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE )
			],
			'can add File: to filename for file icon in parser function'
		];
		yield [
			'{{#titleicon_file:Icon1.png}}',
			null,
			null,
			[
				new Icon( $page, 'File:Icon1.png', Icon::ICON_TYPE_FILE )
			],
			'can add File: to filename for file icon in Icon constructor'
		];
		yield [
			'{{#titleicon_unicode:&#x1F469;&#x1F3FE;&zwj;&#x1F4BB;}}',
			null,
			null,
			[
				new Icon( $page, '&#x1F469;&#x1F3FE;&zwj;&#x1F4BB;', Icon::ICON_TYPE_UNICODE )
			],
			'unicode icon'
		];
		yield [
			'{{#titleicon_ooui:wikiText.svg}}}',
			null,
			null,
			[
				new Icon( $page, 'wikiText.svg', Icon::ICON_TYPE_OOUI )
			],
			'OOUI icon'
		];
		yield [
			'{{#titleicon_file:Icon1.png|Another Page}}',
			null,
			null,
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE, $link )
			],
			'icon with page link'
		];
		yield [
			'{{#titleicon_file:Icon1.png|}}',
			null,
			null,
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE )
			],
			'icon with blank page link'
		];
		yield [
			'{{#titleicon_file:Icon1.png|::::}}',
			null,
			null,
			[
				new Icon( $page, 'Icon1.png', Icon::ICON_TYPE_FILE )
			],
			'icon with malformed page link'
		];
	}

	/**
	 * @covers       MediaWiki\Extension\TitleIcon\IconManager
	 * @dataProvider provideGetIcons
	 * @param string $testPageContent
	 * @param string|null $testCategoryContent
	 * @param string|null $testNamespaceContent
	 * @param array $expected
	 * @param string $message
	 */
	public function testGetIcons(
		string $testPageContent,
		?string $testCategoryContent,
		?string $testNamespaceContent,
		array $expected,
		string $message
	) {
		$jsonCodec = $this->getServiceContainer()->getJsonCodec();
		$title = $this->setPageContent( $testPageContent, $testCategoryContent, $testNamespaceContent )['page'];

		// A page view is usually served from the parser cache, so the manager that parsed the page is gone.
		$this->getServiceContainer()->resetServiceForTesting( 'TitleIcon:IconManager' );
		$manager = $this->getServiceContainer()->getService( 'TitleIcon:IconManager' );

		$actual = $manager->getIcons( $title );

		$this->assertEquals(
			$jsonCodec->serialize( $expected ),
			$jsonCodec->serialize( $actual ),
			$message
		);
	}

	/**
	 * @covers \MediaWiki\Extension\TitleIcon\IconManager
	 */
	public function testNamespaceIconDoesNotDependOnUserLanguage() {
		// the namespace page is named after the content language, whatever language the viewing user has
		$this->setUserLang( 'de' );
		$manager = $this->getServiceContainer()->getService( 'TitleIcon:IconManager' );
		$title = $this->setPageContent( '', null, '{{#titleicon_file:Icon3.png}}' )['page'];

		$icons = $manager->getIcons( $title );

		$this->assertCount( 1, $icons );
		$this->assertSame( 'File:Icon3.png', $icons[0]->getIcon() );
	}

	/**
	 * @covers \MediaWiki\Extension\TitleIcon\IconManager
	 */
	public function testHideTitleIconOnOnePageDoesNotAffectOtherPages() {
		$hidden = $this->insertPage( 'Hidden Page', '{{#titleicon_file:Icon1.png}}{{#hidetitleicon:all}}' )['title'];
		$other = $this->insertPage( 'Other Page', '{{#titleicon_file:Icon2.png}}' )['title'];
		$manager = $this->getServiceContainer()->getService( 'TitleIcon:IconManager' );

		$this->assertSame( [], $manager->getIcons( $hidden ), 'hidden page shows no icons' );
		$icons = $manager->getIcons( $other );

		$this->assertCount( 1, $icons, 'other page keeps its icon' );
		$this->assertSame( 'File:Icon2.png', $icons[0]->getIcon() );
	}

	/**
	 * @covers \MediaWiki\Extension\TitleIcon\IconManager
	 */
	public function testRemovedIconsAreNotKeptWhenAPageIsParsedAgain() {
		$title = $this->insertPage( 'Test Page', '{{#titleicon_file:Icon1.png}}' )['title'];
		$this->editPage( $title, '{{#titleicon_file:Icon2.png}}' );
		$manager = $this->getServiceContainer()->getService( 'TitleIcon:IconManager' );

		$icons = $manager->getIcons( $title );

		$this->assertCount( 1, $icons );
		$this->assertSame( 'File:Icon2.png', $icons[0]->getIcon() );
	}

	/**
	 * @covers \MediaWiki\Extension\TitleIcon\IconManager
	 */
	public function testIconsStoredInTheOldDoubleEncodedFormatAreRead() {
		$page = $this->insertPage( 'Legacy Page', 'text' );
		$codec = $this->getServiceContainer()->getJsonCodec();
		$source = PageReferenceValue::localReference( NS_MAIN, 'Legacy Page' );
		$icon = new Icon( $source, 'Old.png', Icon::ICON_TYPE_FILE );
		// before MediaWiki 1.40 every icon was stored as a JSON string inside the JSON list
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'page_props' )
			->row( [
				'pp_page' => $page['id'],
				'pp_propname' => Icon::ICON_PROPERTY_NAME,
				'pp_value' => json_encode( [ $codec->serialize( $icon ) ] ),
			] )
			->caller( __METHOD__ )
			->execute();
		$this->getServiceContainer()->resetServiceForTesting( 'PageProps' );
		$this->getServiceContainer()->resetServiceForTesting( 'TitleIcon:IconManager' );

		$icons = $this->getServiceContainer()->getService( 'TitleIcon:IconManager' )->getIcons( $page['title'] );

		$this->assertCount( 1, $icons );
		$this->assertSame( 'File:Old.png', $icons[0]->getIcon() );
	}
}
