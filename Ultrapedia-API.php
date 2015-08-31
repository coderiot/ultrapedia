<?php 
/**
 * Ultrapedia-API
 * Provides line-highlighting functionality to the edit form and forwards updates to DBpedia
 * Christian Becker <http://beckr.org#chris>
 */

/**
 * Checks if the file is being executed within MediaWiki
 */
// if ( !defined( 'MEDIAWIKI' ) )
//         die();
//         
require_once("Ultrapedia-API.class.php");

/**
 * Property: Extension credits
 */
$wgExtensionCredits['parserhook'][] = array(
        'name'           => 'Ultrapedia-API',
        'author'         => array( 'Christian Becker' ),
/*        'url'            => '',*/
        'description'    => 'Provides line-highlighting functionality to the edit form and forwards updates to DBpedia',
        'descriptionmsg' => '',
);

$wgExtensionMessagesFiles['Ultrapedia-API'] = dirname( __FILE__ ) . '/Ultrapedia-API.i18n.php';

$api = new UltrapediaAPI();
