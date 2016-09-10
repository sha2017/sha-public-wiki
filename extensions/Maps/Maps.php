<?php
/**
 * Initialization file for the Maps extension.
 *
 * @links https://github.com/JeroenDeDauw/Maps/blob/master/README.md#maps Documentation
 * @links https://github.com/JeroenDeDauw/Maps/issues Support
 * @links https://github.com/JeroenDeDauw/Maps Source code
 *
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

use DataValues\Geo\Parsers\GeoCoordinateParser;
use Maps\CircleParser;
use Maps\DistanceParser;
use Maps\ImageOverlayParser;
use Maps\LineParser;
use Maps\LocationParser;
use Maps\PolygonParser;
use Maps\RectangleParser;
use Maps\ServiceParam;
use Maps\WmsOverlayParser;

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( defined( 'Maps_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

define( 'Maps_VERSION' , '3.8.1' );

// Include the composer autoloader if it is present.
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	include_once( __DIR__ . '/vendor/autoload.php' );
}

// Only initialize the extension when all dependencies are present.
if ( !defined( 'Validator_VERSION' ) ) {
	throw new Exception( 'You need to have Validator installed in order to use Maps' );
}

if ( version_compare( $GLOBALS['wgVersion'], '1.23c' , '<' ) ) {
	throw new Exception(
		'This version of Maps requires MediaWiki 1.23 or above; use Maps 3.5.x for older versions.'
		. ' More information at https://github.com/JeroenDeDauw/Maps/blob/master/INSTALL.md'
	);
}

call_user_func( function() {
	$GLOBALS['wgExtensionCredits']['parserhook'][] = [
		'path' => __FILE__ ,
		'name' => 'Maps' ,
		'version' => Maps_VERSION ,
		'author' => [
			'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
			'...'
		] ,
		'url' => 'https://github.com/JeroenDeDauw/Maps/blob/master/README.md#maps' ,
		'descriptionmsg' => 'maps-desc',
		'license-name' => 'GPL-2.0+'
	];

	// The different coordinate notations.
	define( 'Maps_COORDS_FLOAT' , 'float' );
	define( 'Maps_COORDS_DMS' , 'dms' );
	define( 'Maps_COORDS_DM' , 'dm' );
	define( 'Maps_COORDS_DD' , 'dd' );

	$GLOBALS['egMapsStyleVersion'] = $GLOBALS['wgStyleVersion'] . '-' . Maps_VERSION;

	// Internationalization
	$GLOBALS['wgMessagesDirs']['Maps'] = __DIR__ . '/i18n';
	$GLOBALS['wgExtensionMessagesFiles']['MapsMagic'] = __DIR__ . '/Maps.i18n.magic.php';
	$GLOBALS['wgExtensionMessagesFiles']['MapsAlias'] = __DIR__ . '/Maps.i18n.alias.php';

	$GLOBALS['wgResourceModules'] = array_merge( $GLOBALS['wgResourceModules'], include 'Maps.resources.php' );

	$GLOBALS['wgAPIModules']['geocode'] = 'Maps\Api\Geocode';

	// Register the initialization function of Maps.
	$GLOBALS['wgExtensionFunctions'][] = function () {

		if ( $GLOBALS['egMapsGMaps3Language'] === '' ) {
			$GLOBALS['egMapsGMaps3Language'] = $GLOBALS['wgLang'];
		}

		Hooks::run( 'MappingServiceLoad' );
		Hooks::run( 'MappingFeatureLoad' );

		if ( in_array( 'googlemaps3', $GLOBALS['egMapsAvailableServices'] ) ) {
			$GLOBALS['wgSpecialPages']['MapEditor'] = 'SpecialMapEditor';
			$GLOBALS['wgSpecialPageGroups']['MapEditor'] = 'maps';
		}

		return true;
	};

	$GLOBALS['wgHooks']['AdminLinks'][]                = 'MapsHooks::addToAdminLinks';
	$GLOBALS['wgHooks']['MakeGlobalVariablesScript'][] = 'MapsHooks::onMakeGlobalVariablesScript';

	// Parser hooks

	// Required for #coordinates.
	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = function( Parser &$parser ) {
		$instance = new MapsCoordinates();
		return $instance->init( $parser );
	};

	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = function( Parser &$parser ) {
		$instance = new MapsDisplayMap();
		return $instance->init( $parser );
	};

	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = function( Parser &$parser ) {
		$instance = new MapsDistance();
		return $instance->init( $parser );
	};

	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = function( Parser &$parser ) {
		$instance = new MapsFinddestination();
		return $instance->init( $parser );
	};

	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = function( Parser &$parser ) {
		$instance = new MapsGeocode();
		return $instance->init( $parser );
	};

	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = function( Parser &$parser ) {
		$instance = new MapsGeodistance();
		return $instance->init( $parser );
	};

	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = function( Parser &$parser ) {
		$instance = new MapsMapsDoc();
		return $instance->init( $parser );
	};

	// Geocoders

	// Registration of the GeoNames service geocoder.
	$GLOBALS['wgHooks']['GeocoderFirstCallInit'][] = 'MapsGeonamesGeocoder::register';

	// Registration of the Google Geocoding (v2) service geocoder.
	$GLOBALS['wgHooks']['GeocoderFirstCallInit'][] = 'MapsGoogleGeocoder::register';

	// Registration of the geocoder.us service geocoder.
	$GLOBALS['wgHooks']['GeocoderFirstCallInit'][] = 'MapsGeocoderusGeocoder::register';

	// Registration of the OSM Nominatim service geocoder.
	$GLOBALS['wgHooks']['GeocoderFirstCallInit'][] = 'MapsNominatimGeocoder::register';

	// Mapping services

	// Include the mapping services that should be loaded into Maps.
	// Commenting or removing a mapping service will make Maps completely ignore it, and so improve performance.

	// Google Maps API v3
	// TODO: improve loading mechanism
	include_once __DIR__ . '/includes/services/GoogleMaps3/GoogleMaps3.php';

	// OpenLayers API
	// TODO: improve loading mechanism
	include_once __DIR__ . '/includes/services/OpenLayers/OpenLayers.php';

	// Leaflet API
	// TODO: improve loading mechanism
	include_once __DIR__ . '/includes/services/Leaflet/Leaflet.php';


	require_once __DIR__ . '/Maps_Settings.php';

	$GLOBALS['wgAvailableRights'][] = 'geocode';

	// Users that can geocode. By default the same as those that can edit.
	foreach ( $GLOBALS['wgGroupPermissions'] as $group => $rights ) {
		if ( array_key_exists( 'edit' , $rights ) ) {
			$GLOBALS['wgGroupPermissions'][$group]['geocode'] = $GLOBALS['wgGroupPermissions'][$group]['edit'];
		}
	}

	$GLOBALS['wgParamDefinitions']['coordinate'] = [
		'string-parser' => GeoCoordinateParser::class,
	];

	$GLOBALS['wgParamDefinitions']['mappingservice'] = [
		'definition'=> ServiceParam::class,
	];

	$GLOBALS['wgParamDefinitions']['mapslocation'] = [
		'string-parser' => LocationParser::class,
	];

	$GLOBALS['wgParamDefinitions']['mapsline'] = [
		'string-parser' => LineParser::class,
	];

	$GLOBALS['wgParamDefinitions']['mapscircle'] = [
		'string-parser' => CircleParser::class,
	];

	$GLOBALS['wgParamDefinitions']['mapsrectangle'] = [
		'string-parser' => RectangleParser::class,
	];

	$GLOBALS['wgParamDefinitions']['mapspolygon'] = [
		'string-parser' => PolygonParser::class,
	];

	$GLOBALS['wgParamDefinitions']['distance'] = [
		'string-parser' => DistanceParser::class,
	];

	$GLOBALS['wgParamDefinitions']['wmsoverlay'] = [
		'string-parser' => WmsOverlayParser::class,
	];

	$GLOBALS['wgParamDefinitions']['mapsimageoverlay'] = [
		'string-parser' => ImageOverlayParser::class,
	];
} );