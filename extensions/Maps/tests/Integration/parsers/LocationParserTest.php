<?php

namespace Maps\Test;

use DataValues\Geo\Values\LatLongValue;
use Maps\Elements\Location;
use Maps\LocationParser;
use Title;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;

/**
 * @covers Maps\LocationParser
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LocationParserTest extends \ValueParsers\Test\StringValueParserTest {

	/**
	 * @return string
	 */
	protected function getParserClass() {
		return LocationParser::class;
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	public function validInputProvider() {
		$argLists = [];

		$valid = [
			'55.7557860 N, 37.6176330 W' => [ 55.7557860, -37.6176330 ],
			'55.7557860, -37.6176330' => [ 55.7557860, -37.6176330 ],
			'55 S, 37.6176330 W' => [ -55, -37.6176330 ],
			'-55, -37.6176330' => [ -55, -37.6176330 ],
			'5.5S,37W ' => [ -5.5, -37 ],
			'-5.5,-37 ' => [ -5.5, -37 ],
			'4,2' => [ 4, 2 ],
		];

		foreach ( $valid as $value => $expected ) {
			$expected = new Location( new LatLongValue( $expected[0], $expected[1] ) );
			$argLists[] = [ (string)$value, $expected ];
		}

		$location = new Location( new LatLongValue( 4, 2 ) );
		$location->setTitle( 'Title' );
		$location->setText( 'some description' );
		$argLists[] = [ '4,2~Title~some description', $location ];

		return $argLists;
	}

	/**
	 * @see ValueParserTestBase::requireDataValue
	 *
	 * @since 3.0
	 *
	 * @return boolean
	 */
	protected function requireDataValue() {
		return false;
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testGivenTitleThatIsNotLink_titleIsSetAndLinkIsNot( $title ) {
		$parser = new LocationParser();
		$location = $parser->parse( '4,2~' . $title );

		$this->assertTitleAndLinkAre( $location, $title, '' );
	}

	public function titleProvider() {
		return [
			[ '' ],
			[ 'Title' ],
			[ 'Some title' ],
			[ 'link' ],
			[ 'links:foo' ],
		];
	}

	protected function assertTitleAndLinkAre( Location $location, $title, $link ) {
		$this->assertHasJsonKeyWithValue( $location, 'title', $title );
		$this->assertHasJsonKeyWithValue( $location, 'link', $link );
	}

	protected function assertHasJsonKeyWithValue( Location $polygon, $key, $value ) {
		$json = $polygon->getJSONObject();

		$this->assertArrayHasKey( $key, $json );
		$this->assertEquals(
			$value,
			$json[$key]
		);
	}

	/**
	 * @dataProvider linkProvider
	 */
	public function testGivenTitleThatIsLink_linkIsSetAndTitleIsNot( $link ) {
		$parser = new LocationParser();
		$location = $parser->parse( '4,2~link:' . $link );

		$this->assertTitleAndLinkAre( $location, '', $link );
	}

	public function linkProvider() {
		return [
			[ 'https://semantic-mediawiki.org' ],
			[ 'http://www.semantic-mediawiki.org' ],
			[ 'irc://freenode.net' ],
		];
	}

	/**
	 * @dataProvider titleLinkProvider
	 */
//	public function testGivenPageTitleAsLink_pageTitleIsTurnedIntoUrl( $link ) {
//		$parser = new LocationParser();
//		$location = $parser->parse( '4,2~link:' . $link );
//
//		$linkUrl = Title::newFromText( $link )->getFullURL();
//		$this->assertTitleAndLinkAre( $location, '', $linkUrl );
//	}
//
//	public function titleLinkProvider() {
//		return array(
//			array( 'Foo' ),
//			array( 'Some_Page' ),
//		);
//	}

	protected function getInstance() {
		return new LocationParser();
	}

	public function testGivenAddressAndNoTitle_addressIsSetAsTitle() {
		$options = new ParserOptions( ['useaddressastitle' => true] );
		$parser = new LocationParser( $options );
		$location = $parser->parse( 'Tempelhofer Ufer 42' );

		$this->assertSame( 'Tempelhofer Ufer 42', $location->getTitle() );
	}

	public function testGivenAddressAndTitle_addressIsNotUsedAsTitle() {
		$options = new ParserOptions( ['useaddressastitle' => true] );
		$parser = new LocationParser( $options );
		$location = $parser->parse( 'Tempelhofer Ufer 42~Great title of doom' );

		$this->assertSame( 'Great title of doom', $location->getTitle() );
	}

	public function testGivenCoordinatesAndNoTitle_noTitleIsSet() {
		$options = new ParserOptions( ['useaddressastitle' => true] );
		$parser = new LocationParser( $options );
		$location = $parser->parse( '4,2' );

		$this->assertFalse( $location->getOptions()->hasOption( 'title' ) );
	}

}
