<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Function_class {
	function getXml($file) {
		$doc = new DOMDocument ();
		//$doc->load( $file );
		$xmlStr = file_get_contents($file);
                $doc->loadXML($xmlStr);
		return $doc;
	}
	
	function getModulesXml($node) {
		$m = $this->getXml ( $_SERVER ['DOCUMENT_ROOT'] . '/res/config/modules.xml' );
		$r = $m->getElementsByTagName ( "Menu" )->item ( 0 )->getElementsByTagName ( $node )->item ( 0 )->getElementsByTagName ( "name" );
		return $r;
	}

	function getModulesXml_admin($node) {
		$m = $this->getXml ( $_SERVER ['DOCUMENT_ROOT'] . '/res/config/modules_admin.xml' );
		$r = $m->getElementsByTagName ( "Menu" )->item ( 0 )->getElementsByTagName ( $node )->item ( 0 )->getElementsByTagName ( "name" );
		return $r;
	}
}
?>
