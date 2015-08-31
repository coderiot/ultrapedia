<?php 
/**
 * 
 */
 
class UltrapediaAPI {
	
	public function __construct() {
		global $wgHooks;
		//$wgHooks['EditPageGetPreviewText'][] = array($this, 'onPreviewValidate');
		$wgHooks['EditPage::showEditForm:initial'][] = array($this, 'onEditPageShowEditFormInitial');
		$wgHooks['EditPage::showEditForm:fields'][] = array($this, 'onEditPageShowEditFormFields');
		$wgHooks[defined("Article::hasRevisionSavePatch") ? 'RevisionSave' : 'ArticleSave'][] = array($this, 'onArticleSave');
		$wgHooks['ArticleUpdateBeforeRedirect'][] = array($this, 'onArticleUpdateBeforeRedirect');
		$wgHooks['GetFullURL'][] = array($this, 'onGetFullURL');
		$wgHooks['ArticleDeleteComplete'][] = array($this, 'onDeletePage');
		$wgHooks['SpecialMovepageAfterMove'][] = array($this, 'onMovePage');
	}
	
	function onMovePage( &$form, &$ot , &$nt ) {
		$old_title_encoded = $ot->prefix($ot->getPartialURL());
		$title_encoded = $nt->prefix($nt->getPartialURL());
		$title_decoded = $nt->getText();
		$ns = str_replace( '_', ' ', $nt->getNsText());

		// Note: until 2012-02-24, the next line used $nt->getNamespace(),
		// which seemed wrong to me, so I changed it. jc@sahnwaldt.de
		$path = $this->getPath($ot->getNamespace());
        
		// Note: until 2012-05-06, the following check ($path != null) did not exist.
		// I introduced it because it seemed necessary. jc@sahnwaldt.de
		if ($path != null) {
            $options = array(
                CURLOPT_RETURNTRANSFER => true,     // return web page
                CURLOPT_HEADER         => false,    // don't return headers
                CURLOPT_FOLLOWLOCATION => true,     // follow redirects
                CURLOPT_ENCODING       => "",       // handle all encodings
                CURLOPT_AUTOREFERER    => true,     // set referer on redirect
                CURLOPT_CONNECTTIMEOUT => 2,      // timeout on connect
                CURLOPT_TIMEOUT        => 2,      // timeout on response
                CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
                CURLOPT_URL            => ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."pages/".$old_title_encoded,
                CURLOPT_PORT           => 80
            );
        
            $cURL = curl_init();
            curl_setopt_array( $cURL, $options );
            $response = curl_exec($cURL);

            if (curl_errno($cURL) == 0) {
                $path = $this->getPath($nt->getNamespace());
                if ($path != null) {
                    $cURL = curl_init();
                    curl_setopt($cURL, CURLOPT_URL, ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."pages/".$title_encoded);	
                    curl_setopt($cURL, CURLOPT_PORT, '80' ); 
                    curl_setopt($cURL, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($cURL, CURLOPT_POSTFIELDS, $response);
                    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($cURL, CURLOPT_HTTPHEADER, array('Expect: ', 'Content-Type: application/xml', 'Transfer-Encoding: chunked')); 
                    curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 2);
                    // curl_setopt ($cURL, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
                    $response = curl_exec($cURL);
                }
            }
        }
		return true;
	}

	
	function onDeletePage ( &$article, &$user, $reason, $id ) {
		$path = $this->getPath($article->getTitle()->getNamespace());
		if ($path != null) {
			$title_encoded = $article->getTitle()->prefix($article->getTitle()->getPartialURL());
			$title_encoded = $article->getTitle()->getPrefixedText($article->getTitle()->getPartialURL());
			$cURL = curl_init();
			curl_setopt($cURL, CURLOPT_URL, ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."pages/".$title_encoded);	
			curl_setopt($cURL, CURLOPT_PORT, '80' ); 
			curl_setopt($cURL, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($cURL, CURLOPT_HTTPHEADER, array('Expect: ')); 
			curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 2);
            // curl_setopt ($cURL, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
			curl_exec($cURL);	
		}
		return true;
	}

	/**
	 * Sets up form for highlighting, if a line number is provided
	 * Shows error messages from "save" operation
	 */
	public function onEditPageShowEditFormInitial($editPage) {
		global $wgOut, $wgRequest, $wgJsMimeType, $wgScriptPath, $wgStyleVersion, $ultrapediaError/*, $article*/;

		if ($editPage->formtype == 'preview') {
			$article = $editPage->getArticle();
			
			// $title_encoded = $article->getTitle()->prefix($article->getTitle()->getPartialURL());
			$title_encoded = $article->getTitle()->getPrefixedText($article->getTitle()->getPartialURL());
            // $title_encoded = urlencode($title_encoded);
			$title_decoded = $article->getTitle()->getText();
			$ns = str_replace( '_', ' ', $article->getTitle()->getNsText());
			$xmlData = '
			<mediawiki xmlns="http://www.mediawiki.org/xml/export-0.4/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.mediawiki.org/xml/export-0.4/ http://www.mediawiki.org/xml/export-0.4.xsd" version="0.4" xml:lang="en">
				<page>
					<title>'.$ns.':'.$title_decoded.'</title>
					<id>0</id>
            		<revision>
						<id>0</id>
						<text xml:space="preserve">'.$this->xmlentities($editPage->textbox1).'</text>
					</revision>
				</page>
			</mediawiki>';

			
			$path = $this->getPath($article->getTitle()->getNamespace());
            // var_dump(ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."validate/".$title_encoded);
			if ($path != null) {
				$cURL = curl_init();
				curl_setopt($cURL, CURLOPT_URL, ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."validate/".$title_encoded);
				curl_setopt($cURL, CURLOPT_PORT, '9999' );
				curl_setopt($cURL, CURLOPT_POST, true);
				curl_setopt($cURL, CURLOPT_POSTFIELDS, $xmlData);
				curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($cURL, CURLOPT_HTTPHEADER, array('Expect: ', 'Content-Type: application/xml', 'Transfer-Encoding: chunked')); 
                // curl_setopt($cURL, CURLOPT_NOSIGNAL, 0);
				curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 2);

				$error_file_name = "/var/www/mappingswiki/extensions/Ultrapedia-API/stderr.log";
				// $error_file_name = "stderr.log";
				$error_file=fopen($error_file_name,'a+');
				curl_setopt($cURL, CURLOPT_VERBOSE, true);
				curl_setopt($cURL, CURLOPT_STDERR, $error_file);
                // curl_setopt ($cURL, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');

				$response = curl_exec($cURL);

                // var_dump($response);
                $errno = curl_errno($cURL);
                // var_dump($errno);
                // var_dump(curl_error($cURL));
				
				$file = "/var/www/mappingswiki/extensions/Ultrapedia-API/error.log";
				// $file = "error.log";
				if ($errno) {
					$f=fopen($file,'a+');
					fwrite($f, date("y-m-d G:i:s") . " Curl error #$errno: " . curl_error($cURL) . "\n           URL: " . ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."validate/".$title_encoded . "\n        Content: " . $xmlData . "\n\n");
					fclose($f); 
					
					/*
					$formattedResponse = str_replace("<", "&lt;", $response);
					$formattedResponse = str_replace(">", "&gt;", $formattedResponse);
					
					$editPage->editFormPageTop .= '<h2>Validation Results '.$errno.'</h2><p><pre>';
					$editPage->editFormPageTop .= $formattedResponse;
					$editPage->editFormPageTop .= '</pre></p>';
					*/
					/*
					$formattedXML = str_replace("<", "&lt;", $xmlData);
					$formattedXML = str_replace(">", "&gt;", $formattedXML);
					
					
					$editPage->editFormPageTop .= '<h2>Server Error</h2><p><pre>';
					$editPage->editFormPageTop .= $formattedXML;
					$editPage->editFormPageTop .= '</pre></p>';
					*/
				} else {
					$f=fopen($file,'a');
					fwrite($f, date("y-m-d G:i:s") . " Curl OK\n           URL: " . ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."validate/".$title_encoded . "\n        Content: " . $xmlData . "\n\n");
					fclose($f); 
					
					$formattedResponse = str_replace("<", "&lt;", $response);
					$formattedResponse = str_replace(">", "&gt;", $formattedResponse);
					
					$editPage->editFormPageTop .= '<p style="font-weight: bold; color:#CC0000;">Remember that this is only a preview; your changes have not yet been saved!</p>
					<h2>Validation Results</h2><p>';
					
					
					$xslDom = new DOMDocument; 
					$xslDom->load('/var/www/mappingswiki/extensions/Ultrapedia-API/validate.xsl');
					
					$xmlDom = new DOMDocument;
					echo $response;
					$xmlDom->loadXML($response); 
					 
					$xsl = new XsltProcessor; // XSLT Prozessor Objekt erzeugen 
					$xsl->importStylesheet($xslDom); // Stylesheet laden 
					
					$validationResult = $xsl->transformToXML($xmlDom); // Transformation - return XHTML 
					
					if (strpos($validationResult, ">Valid mapping.<") !== false) {
						switch ($article->getTitle()->getNamespace()) {
							case NS_DBPEDIA_CLASS:
								$validationResult = str_replace("Valid mapping", "Valid ontology class definition", $validationResult);
								break;
							case NS_DBPEDIA_PROPERTY:
								$validationResult = str_replace("Valid mapping", "Valid ontology property definition", $validationResult);
								break;
						}
					}
					
					$editPage->editFormPageTop .= $validationResult;
					
					//$editPage->editFormPageTop .= $formattedResponse;
					$editPage->editFormPageTop .= '</p>';
				}	
			}
			
			
			/*$editPage->getArticle()->getTitle()->getText()*/
			
			/*
			$title_encoded = $article->getTitle()->prefix($article->getTitle()->getPartialURL());
			$title_decoded = $article->getTitle()->getText();
			$ns = $article->getTitle()->getNsText();
			$xmlData = '
			<mediawiki xmlns="http://www.mediawiki.org/xml/export-0.4/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.mediawiki.org/xml/export-0.4/ http://www.mediawiki.org/xml/export-0.4.xsd" version="0.4" xml:lang="en">
				<page>
					<title>'.$ns.':'.$title_decoded.'</title>
					<id>0</id>
            		<revision>
						<id>0</id>
						<text xml:space="preserve">'.$this->xmlentities($text).'</text>
					</revision>
				</page>
			</mediawiki>';
			
			$path = $this->getPath($article->getTitle()->getNamespace());
			if ($path != null) {
				
				//$cURL = curl_init(ULTRAPEDIA_DBPEDIA_ENDPOINT);
				$cURL = curl_init();
				curl_setopt($cURL, CURLOPT_URL, ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."pages/".$title_encoded);	
				curl_setopt($cURL, CURLOPT_PORT, '80' ); 
				curl_setopt($cURL, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($cURL, CURLOPT_POSTFIELDS, $xmlData);
				curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($cURL, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($xmlData), 'Content-type: application/xml')); 
				curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 2);
				$response = curl_exec($cURL);
				
				$errno = curl_errno($cURL);
				$file = "error.log";
				if ($errno) {
					$f=fopen($file,'a');
					fwrite($f, date("y-m-d G:i:s") . " Curl error #$errno: " . curl_error($cURL) . "\n           URL: " . ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."pages/".$title_encoded . "\n        Content: " . $xmlData . "\n\n");
					fclose($f); 
				} else {
					$f=fopen($file,'a');
					fwrite($f, date("y-m-d G:i:s") . " Curl OK\n           URL: " . ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."pages/".$title_encoded . "\n        Content: " . $xmlData . "\n\n");
					fclose($f); 
				}	
			}
			*/
		}
		$lineToBeHighlighted = $wgRequest->getIntOrNull('line');
		
		if (!is_null($lineToBeHighlighted)) {
			$wgOut->addLink(array('rel' => 'stylesheet', 'type' => 'text/css', 'href' => $wgScriptPath . '/extensions/Ultrapedia-API/Ultrapedia-API.css'));
			
			$script = <<<END
	<script type="$wgJsMimeType" src="$wgScriptPath/extensions/Ultrapedia-API/fade.js?{$wgStyleVersion}"></script>
	<script type="$wgJsMimeType" src="$wgScriptPath/extensions/Ultrapedia-API/Ultrapedia-API.js?{$wgStyleVersion}"></script>
	<script type="$wgJsMimeType">ultrapediaAPI = new ultrapediaAPI_highlight($lineToBeHighlighted);</script>
END;
	
			$wgOut->addScript($script);
			
			$editPage->editFormTextBeforeContent .= '<div id="ultrapedia-anchor">';
			$editPage->editFormTextAfterWarn .= '<div id="ultrapedia-highlight"><!-- ie6 space --></div></div>';
		}
		
		/* Show Ultrapedia error message */
		if (isset($ultrapediaError)) {
			$editPage->editFormPageTop .= $ultrapediaError;
			$editPage->isConflict = false;
		}
				
		return true;
	}
	
	/**
	 * Pass on redirect parameter as a hidden form field
	 */
	public function onEditPageShowEditFormFields(&$editPage, &$output) {
		global $wgRequest;
		
		if ($wgRequest->getVal('redirect-after-edit') !== null) {
			$output->addHTML(Xml::hidden('redirect-after-edit', $wgRequest->getVal('redirect-after-edit')));
		}		
		return true;
	}	
	
	// XML Entity Mandatory Escape Characters
	public function xmlentities ( $string )
	{
		return str_replace ( array ( '&', '"', "'", '<', '>' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;' ), $string );
	}

	/**
	 * Posts saved articles to DBpedia; preventing saves whenever posting fails
	 */
	public function onArticleSave(&$article, &$user, &$text, &$summary, $minor, $watch, $sectionanchor, &$flags, &$status, $revisionId = undefined) {
 		global $ultrapediaError, $wgRequest, $wgOut;
 		
		wfLoadExtensionMessages('Ultrapedia-API');
       
		if (!defined("ULTRAPEDIA_DBPEDIA_ENDPOINT")) {
			$ultrapediaError = wfMsg("no_endpoint");
		} else {
		    // TODO: getTitle()->getPrefixedText()? getPrefixedURL()?
			// $data = array("title" => $article->getTitle()->prefix($article->getTitle()->getPartialURL()),
			$data = array("title" => $article->getTitle()->getPrefixedText($article->getTitle()->getPartialURL()),
							"newarticle" => $flags & EDIT_UPDATE ? "false" : "true",
							"source" => $text);
							
			if ($revisionId) {
				$data["revision"] = $revisionId;
			}
			
			// $title_encoded = $article->getTitle()->prefix($article->getTitle()->getPartialURL());
			$title_encoded = $article->getTitle()->getPrefixedText($article->getTitle()->getPartialURL());
			$title_decoded = $article->getTitle()->getText();
			$ns = str_replace( '_', ' ', $article->getTitle()->getNsText());
			$xmlData = '
			<mediawiki xmlns="http://www.mediawiki.org/xml/export-0.4/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.mediawiki.org/xml/export-0.4.xsd" version="0.4" xml:lang="en">
				<page>
					<title>'.$ns.':'.$title_decoded.'</title>
					<id>0</id>
            		<revision>
						<id>0</id>
						<text xml:space="preserve">'.$this->xmlentities($text).'</text>
					</revision>
				</page>
			</mediawiki>';
			
			$path = $this->getPath($article->getTitle()->getNamespace());
			if ($path != null) {
				//$cURL = curl_init(ULTRAPEDIA_DBPEDIA_ENDPOINT);
				$cURL = curl_init();
				curl_setopt($cURL, CURLOPT_URL, ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."pages/".$title_encoded);	
				curl_setopt($cURL, CURLOPT_PORT, '80' ); 
				curl_setopt($cURL, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($cURL, CURLOPT_POSTFIELDS, $xmlData);
				curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($cURL, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($xmlData), 'Content-type: application/xml')); 
				/*
				curl_setopt($cURL, CURLOPT_POST, true);
				curl_setopt($cURL, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($cURL, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($cURL, CURLOPT_HEADER, true);
				curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
				*/
				curl_setopt($cURL, CURLOPT_TIMEOUT, 30);
				curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 2);                
                // curl_setopt ($cURL, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');


                echo "Sorry. If you can read this, saving the mapping was not successful. ";
                echo "Please hit the 'Back' button in your browser and try again! ";
                echo "If this happens repeatedly, please contact the DBepdia developers. ";
                echo "(curl_exec hung itself in extensions/Ultrapedia-API/Ultrapedia-API.class.php->onArticleSave; ";
                echo "probably DBpedia server took to long to answer the PUT) ";

				$response = curl_exec($cURL);
				$errno = curl_errno($cURL);


				$file = "error.log";
				if ($errno) {
					$f=fopen($file,'a');
					fwrite($f, date("y-m-d G:i:s") . " Curl error #$errno: " . curl_error($cURL) . "\n           URL: " . ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."pages/".$title_encoded . "\n        Content: " . $xmlData . "\n\n");
					fclose($f); 
				} else {
					$f=fopen($file,'a');
					fwrite($f, date("y-m-d G:i:s") . " Curl OK\n           URL: " . ULTRAPEDIA_DBPEDIA_ENDPOINT.$path."pages/".$title_encoded . "\n        Content: " . $xmlData . "\n\n");
					fclose($f); 
				}	
				
				/*
				if ($errno) {
					$ultrapediaError = wfMsg("dbpedia_update_error", ULTRAPEDIA_DBPEDIA_ENDPOINT, "Curl error #$errno: " . curl_error($cURL));
				} else {
					$code = curl_getinfo($cURL, CURLINFO_HTTP_CODE);
					if ($code != 200) {
						$ultrapediaError = wfMsg("dbpedia_update_error", ULTRAPEDIA_DBPEDIA_ENDPOINT, htmlspecialchars($response));
					}
				}
				*/
			}
		}
        
		return true;
		
 		/*
 		if (!isset($ultrapediaError)) {
 			return true;
 		} else {
			$status->fatal('Ultrapedia error'); 
			return false;
 		}
		*/
	}
	
	/**
	 * We can't modify the redirect here, but we can pass our parameter internally
	 */
	public function onArticleUpdateBeforeRedirect($article, &$sectionanchor, &$extraq) {
		global $wgRequest, $wgOut;
		
		/* Modify redirect for Ultrapedia */
		if ($wgRequest->getVal('redirect-after-edit') !== null) {
			$extraq .= "redirect-after-edit-internal=" . urlencode($wgRequest->getVal('redirect-after-edit'));
		}
		
		return true;
	}
	
	/**
	 * Translate URLs with redirect-after-edit-internal into full redirects
	 */
	public function onGetFullURL($title, $url, $query) {
		global $wgRequest, $wgOut;
		
		@parse_str($query, $vars);
		
		if (array_key_exists('redirect-after-edit-internal', $vars)) {
			$url = $vars['redirect-after-edit-internal'];
		}
		
		return true;
	}
    
    private function getPath($namespace) {
        global $dbpediaServerPath;
        // echo "<!-- XXXX " . $namespace . " XXXX " . $dbpediaServerPath[$namespace] . " XXXX -->";
        return isset($dbpediaServerPath[$namespace]) ? $dbpediaServerPath[$namespace] : null;
    }
}
