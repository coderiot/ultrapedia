<?php 
/**
 * Ultrapedia-API
 * Provides line-highlighting functionality to the edit form and forwards updates to DBpedia
 * Christian Becker <http://beckr.org#chris>
 */
 
$messages = array();

$messages['en'] = array(
	'dbpedia_update_error' => '<div class="errorbox"><h2>Error posting update to DBpedia</h2><p>The changes could not be posted to DBpedia and hence were not saved.<br>The URL used was (ULTRAPEDIA_DBPEDIA_ENDPOINT):<pre>$1</pre>The response was: <pre>$2</pre></p></div>',
	'no_endpoint' => '<div class="errorbox"><h2>Endpoint not defined</h2><p>Please define ULTRAPEDIA_DBPEDIA_ENDPOINT in LocalSettings.php.</p></div>'
);