<?php
/*
 * @package Joomla 1.5
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 * @component Phoca Component
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocamaps'.DS.'helpers'.DS.'phocamapsicon.php' );
/* Google Maps Version 3 */
class PhocaMapsMap
{
	/*
	 * Map Name (id of element in html)
	 */
	var $_name			= 'phocaMap';
	
	/*
	 * Map ID - it is important e.g. for plugin when more instances are created
	 */
	var $_id			= '';
	var $_map			= 'mapPhocaMap';
	var $_latlng		= 'phocaLatLng';
	var $_options		= 'phocaOptions';
	var $_tst			= 'tstPhocaMap';
	var $_tstint		= 'tstIntPhocaMap';
	
	/*
	 * If you want to work only with one marker (administration), set TRUE for global marker so only with one marker id will be worked in the map
	 * You need to set:
	 * In createMap() method set TRUE for $globalMarker - global var will be created: var markerPhocaMarkerGlobal
	 * In setMarker() or exportMarker() set the id as "Global" - so the marker get the name markerPhocaMarkerGlobal
	 * If there is one global marker then there is one global window
	 */
	var $_marker		= FALSE;
	var $_window		= FALSE;
	var $_dirdisplay	= FALSE;
	var $_dirservice	= FALSE;
	var $_geocoder		= FALSE;
	
	function __construct($id = '') {
		$this->_id	= $id;
	}
	
	function startJScData() {
		return '<script type="text/javascript">//<![CDATA['."\n";
	}
	
	function endJScData($noScriptText = 'GOOGLE MAP ENABLE JAVASCRIPT') {
		return '//]]></script>'."\n"
			. '<noscript><p class="p-noscript">'.JText::_($noScriptText).'</p><p>&nbsp;</p></noscript>'."\n\n";
	}
	
	/*
	 * Loaded only one time per site (addScript)
	 */
	function loadAPI( $src = 'jsapi') {
		$document	= & JFactory::getDocument();
		$scriptLink	= 'http://www.google.com/'.$src;
		$document->addScript($scriptLink);

	}
	
	function loadCoordinatesJS() {
		$document	= & JFactory::getDocument();
		$document->addScript(JURI::base(true).'/components/com_phocamaps/assets/js/coordinates.js');
	}
	
	function loadGeoXMLJS() {
		$document	= & JFactory::getDocument();
		$document->addScript(JURI::base(true).'/components/com_phocamaps/assets/js/geoXML3.js');
		//$document->addScript(JURI::base(true).'/components/com_phocamaps/assets/js/ProjectedOverlay.js');
	}
	function loadBase64JS() {
		$document	= & JFactory::getDocument();
		$document->addScript(JURI::base(true).'/components/com_phocamaps/assets/js/base64.js');
	}
	
	function addAjaxAPI($type = 'maps', $version = '3.x', $params = '') {
		
		if ($params == '') {
			return ' google.load("'.$type.'", "'.$version.'");'."\n";
		} else {
			return ' google.load("'.$type.'", "'.$version.'", '.$params.');'."\n";
		}
	}

	/*
	 * Create whole map (e.g. Map View)
	 */
	function createMap($name, $map, $latlng, $options, $tst, $tstint, $geocoder = FALSE, $globalMarker = FALSE, $direction = FALSE) {
		$this->_name	= $name . $this->_id;
		$this->_map 	= $map . $this->_id;
		$this->_latlng 	= $latlng . $this->_id;
		$this->_options = $options . $this->_id;
		$this->_tst 	= $tst . $this->_id;
		$this->_tstint 	= $tstint . $this->_id;
		
		$js = "\n" . ' var '.$this->_tst .' = document.getElementById(\''.$this->_name .'\');'."\n";

		$js .=' var '.$this->_tstint.';'."\n"
			 .' var '.$this->_map.';'."\n";
		
		if ($geocoder) {
			$this->_geocoder	= 'phocaGeoCoder'. $this->_id;
			$js .=	 ' var '.$this->_geocoder.';'."\n";
		}
		
		if ($globalMarker) {
			$this->_marker	= 'markerPhocaMarkerGlobal'. $this->_id;
			$this->_window	= 'infoPhocaWindowGlobal'. $this->_id;
			$js .= ' var '.$this->_marker.';'."\n";
			$js .= ' var '.$this->_window.';'."\n";
			
		}
		
		if ($direction) {
			$this->_dirdisplay = 'phocaDirDisplay'. $this->_id;
			$this->_dirservice = 'phocaDirService'. $this->_id;
			$js .= ' var '.$this->_dirdisplay.';'."\n";
			$js .= ' var '.$this->_dirservice.';'."\n";
		}	
		return $js . "\n\n";
	}
	
	/*
	 * Create only direction (e.g. Route View)
	 */
	 function createDirection($name) {
		$this->_name		= $name. $this->_id;
		$js = '';
		$this->_dirdisplay = 'phocaDirDisplay'. $this->_id;
		$this->_dirservice = 'phocaDirService'. $this->_id;
		$js .= ' var '.$this->_dirdisplay.';'."\n";
		$js .= ' var '.$this->_dirservice.';'."\n";	
		return $js . "\n\n";
	}
	
	function setMap() {
		// Not var as the map is global variable so not disable the global effect
		return $this->_map.' = new google.maps.Map(document.getElementById(\''.$this->_name.'\'), '.$this->_options.');'."\n";
		
	}
	
	function setDirectionDisplayService($directionPanel = 'PhocaDir') {
		$js = '';
		if ($this->_dirdisplay && $this->_dirservice) {
			$js .= ' '.$this->_dirservice.' = new google.maps.DirectionsService();'."\n";
			$js .= ' '.$this->_dirdisplay.' = new google.maps.DirectionsRenderer();'."\n";
			$js .= ' '.$this->_dirdisplay.'.setMap('.$this->_map.');'."\n";
			$js .= ' '.$this->_dirdisplay.'.setPanel(document.getElementById("'.$directionPanel.$this->_id.'"));'."\n";
		}
		return $js;
	}

	
	function setLatLng($latitude, $longitude) {
		return ' var '.$this->_latlng.' = new google.maps.LatLng('.$latitude .', '. $longitude .');'."\n";
	}
	
	
	function startMapOptions() {
		return ' var '.$this->_options.' = {'."\n";
	}
	
	function endMapOptions (){
		return ' };'."\n\n";
	}
	
	// Options
	function setMapOption($option, $value, $trueOrFalse = FALSE) {
		$js = '';
		if (!$trueOrFalse) {
			$js .= '   '.$option.': '.$value;
		} else {
			if ($value == 0) {
				$js .= '   '.$option.': false';
			} else {
				$js .= '   '.$option.': true';
			}
		}
		return $js;
	}

	
	function setCenterOpt($comma = FALSE) {
		return '   center: '.$this->_latlng;
	}
	
	function setTypeControlOpt( $typeControl = 1, $typeControlPosition = 3 ) {
		$output = '';
		if ($typeControl == 0) {
			$output = 'mapTypeControl: false';
		} else {
			switch($typeControl) {
				case 2:
					$type = 'HORIZONTAL_BAR';
				break;
				case 3:
					$type = 'DROPDOWN_MENU';
				break;
				default:
				case 1:
					$type = 'DEFAULT';
				break;
			}
		
			$output = '   mapTypeControl: true,'."\n"
					 .'   mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.'.$type;
					 
			if ((int)$typeControlPosition > 0) {
				$typePosition = $this->_setTypeControlPositionOpt($typeControlPosition);
				$output .= ', ' . "\n" . '   position: google.maps.ControlPosition.'.$typePosition;
			}			
			$output	 .= ' }';
		
		}
		return $output;
	}
	
	
	function _setTypeControlPositionOpt( $typeControlPosition = 3 ) {
		$output = '';
		switch($typeControlPosition) {
			case 1:
				$output = 'TOP';
			break;
			case 2:
				$output = 'TOP_LEFT';
			break;
			case 4:
				$output = 'BOTTOM';
			break;
			case 5:
				$output = 'BOTTOM_LEFT';
			break;
			case 6:
				$output = 'BOTTOM_RIGHT';
			break;
			case 7:
				$output = 'LEFT';
			break;
			case 8:
				$output = 'RIGHT';
			break;
			
			default:
			case 3:
				$output = 'TOP_RIGHT';
			break;
		}
		return $output;
	}
	
	function setNavigationControlOpt( $navControl = 1) {
		$output = '';
		if ($navControl == 0) {
			$output = '   navigationControl: false';
		} else {
			switch($navControl) {
				case 2:
					$type = 'SMALL';
				break;
				case 3:
					$type = 'ZOOM_PAN';
				break;
				case 4:
					$type = 'ANDROID';
				break;
				default:
				case 1:
					$type = 'DEFAULT';
				break;
			}
		
			$output = '   navigationControl: true,'."\n"
					 .'   navigationControlOptions: {style: google.maps.NavigationControlStyle.'.$type.'}';
		}
		return $output;
	}

	
	function setMapTypeOpt( $mapType = 0 ) {
		$output = '';
		
		switch((int)$mapType) {
			case 1:
				$type = 'SATELLITE';
			break;
			case 2:
				$type = 'HYBRID';
			break;
			case 3:
				$type = 'TERRAIN';
			break;
			default:
			case 0:
				$type = 'ROADMAP';
			break;
		}
		
		$output = '   mapTypeId: google.maps.MapTypeId.'.$type;
		return $output;
	}
	

	function setMarker($name, $title, $description, $latitude, $longitude, $icon = 0, $iconId = 0, $text = '', $width = '', $height = '', $open = 0) {
		jimport('joomla.filter.output');
		//phocagalleryimport('phocagallery.text.text');
		
		$style = '';
		if ($width != '') {
			$style .= 'width: '.(int)$width.'px;';
		}
		if ($height != '') {
			$style .= 'height: '.(int)$height.'px;';
		}
		
		
		$output = '';
		if ($text == '') {
			if ($title != ''){
				$text .=  '<h1>' . addslashes($title) . '</h1>';
			}
			if ($description != '') {
				$text .= '<div>'. PhocaMapsHelper::strTrimAll(addslashes($description)).'</div>';
			}
		}
		
		if ($style != '') {
			$text = '<div style="'.$style.'">' . $text . '</div>';
		}
	
		$output .= ' var phocaPoint'.$name.$this->_id.' = new google.maps.LatLng('. $latitude.', ' .$longitude.');'."\n";
		
		// Global Marker is defined, don't define var here - the marker markerPhocaMarkerGlobal is defined in the beginning
		if ($name == 'Global') {
			$output .= ' markerPhocaMarker'.$name.$this->_id.' = new google.maps.Marker({title:"'.$title.'"'."\n";        
		} else {
			$output .= ' var markerPhocaMarker'.$name.$this->_id.' = new google.maps.Marker({' ."\n" . ' title:"'.$title.'"';
		}
		
		if ($icon == 1) {
			$output .= ', '."\n".'   icon:phocaImage'.$iconId.$this->_id;
			$output .= ', '."\n".'   shadow:phocaImageShadow'.$iconId.$this->_id;
			$output .= ', '."\n".'   shape:phocaImageShape'.$iconId.$this->_id;
		}
		
		$output .= ', '."\n".'   position: phocaPoint'.$name . $this->_id;
		$output .= ', '."\n".'   map: '.$this->_map."\n";
		$output .= ' });'."\n";		

		
		if ($name == 'Global') {
			$output .= ' infoPhocaWindow'.$name.$this->_id.' = new google.maps.InfoWindow({'."\n";
		} else {
			$output .= ' var infoPhocaWindow'.$name.$this->_id.' = new google.maps.InfoWindow({'."\n";
		}		
		$output .= '   content: \''.$text.'\''."\n"
				  .' });'."\n";
	
		$output .= ' google.maps.event.addListener(markerPhocaMarker'.$name.$this->_id.', \'click\', function() {'."\n"
			.'   infoPhocaWindow'.$name.$this->_id.'.open('.$this->_map.', markerPhocaMarker'.$name.$this->_id.' );'."\n"
			.' });'."\n";
		if ($open) {
			$output .= '   google.maps.event.trigger(markerPhocaMarker'.$name.$this->_id.', \'click\');'."\n";
		}
		return $output;
	}
	
	/*
	 * Icon has no this->_id as this will be set in Marker
	 */
	
	function setMarkerIcon($icon) {
		
		$output['icon']	= 0;
		$output['js'] 	= '';
		if((int)$icon > 0) {
			$i = PhocaMapsIcon::getIconData($icon);
			if ($i) {
				$imagePath = JURI::base(true).'/components/com_phocamaps/assets/images/'.$i['name'].'/';
				$js =' var phocaImage'.$icon.$this->_id.' = new google.maps.MarkerImage(\''.$imagePath.'image.png\','."\n";
				$js.=' new google.maps.Size('.$i['size'].'),'."\n";
				$js.=' new google.maps.Point('.$i['point1'].'),'."\n";
				$js.=' new google.maps.Point('.$i['point2'].'));'."\n";
				
				$js.=' var phocaImageShadow'.$icon.$this->_id.' = new google.maps.MarkerImage(\''.$imagePath.'shadow.png\','."\n";
				$js.=' new google.maps.Size('.$i['sizes'].'),'."\n";
				$js.=' new google.maps.Point('.$i['point1s'].'),'."\n";
				$js.=' new google.maps.Point('.$i['point2s'].'));'."\n";
				
				$js.=' var phocaImageShape'.$icon.$this->_id.' = {'."\n";
				$js.='   coord: '.$i['cord'].','."\n";
				$js.='   type: \''.$i['type'].'\''."\n";
				$js.=' };'."\n";
				
				$output['icon']		= 1;
				$output['js'] 		= $js;
			} else {
				$output['icon']		= 0;
				$output['js'] 		= '';
			}
			$output['iconid'] 	= $icon; // Make the icon ID so if e.g. more markers are using the same icon, 
										// don't create for every marker instance ($this->_id is not used as this info goes back)
										
		} else {
			$output['icon']		= 0;
			$output['js'] 		= '';// if default Icon should be displayed, no Icon should be created
			$output['iconid'] 	= $icon;
		}
		return $output;	
	}
	
	function setInitializeFunction() {
	
		$js = ' function initialize'.$this->_id.'() {'."\n"
			 .'   '.$this->_tst.'.setAttribute("oldValue'.$this->_id.'",0);'."\n"
		     .'   '.$this->_tst.'.setAttribute("refreshMap'.$this->_id.'",0);'."\n"
		     .'   '.$this->_tstint.' = setInterval("CheckPhocaMap'.$this->_id.'()",500);'."\n"
			 .' }'."\n\n"
			 .' google.setOnLoadCallback(initialize'.$this->_id.');'."\n";
		return $js;
	}
	
	function setListener() {
		$js = ' google.maps.event.addDomListener('.$this->_tst.', \'DOMMouseScroll\', CancelEventPhocaMap'.$this->_id.');'."\n"
		     .' google.maps.event.addDomListener('.$this->_tst.', \'mousewheel\', CancelEventPhocaMap'.$this->_id.');';
		return $js;
	}
	
	function checkMapFunction() {
		$js =' function CheckPhocaMap'.$this->_id.'() {'."\n"
			.'   if ('.$this->_tst.') {'."\n"
			.'      if ('.$this->_tst.'.offsetWidth != '.$this->_tst.'.getAttribute("oldValue'.$this->_id.'")) {'."\n"	
			.'         '.$this->_tst.'.setAttribute("oldValue'.$this->_id.'",'.$this->_tst.'.offsetWidth);'."\n"
			.'             if ('.$this->_tst.'.getAttribute("refreshMap'.$this->_id.'")==0) {'."\n"
			.'                if ('.$this->_tst.'.offsetWidth > 0) {'."\n"
			.'                   clearInterval('.$this->_tstint.');'."\n"
			.'                   getPhocaMap'.$this->_id.'();'."\n"
			.'                  '.$this->_tst.'.setAttribute("refreshMap'.$this->_id.'", 1);'."\n"
			.'                } '."\n"
			.'             }'."\n"
			.'         }'."\n"
			.'     }'."\n"
			.' }'."\n\n";
		return $js;
	}

	
	function cancelEventFunction() {
		$js =' function CancelEventPhocaMap'.$this->_id.'(event) { '."\n"
			.'   var e = event; '."\n"
			.'   if (typeof e.preventDefault == \'function\') e.preventDefault(); '."\n"
			.'   if (typeof e.stopPropagation == \'function\') e.stopPropagation(); '."\n"
			.'   if (window.event) { '."\n"
			.'      window.event.cancelBubble = true; /* for IE */'."\n"
			.'      window.event.returnValue = false; /* for IE */'."\n"
			.'   } '."\n"
			.' }'."\n\n";
		return $js;
	}
	
	function startMapFunction() {
		$js = ' function getPhocaMap'.$this->_id.'(){'."\n"
			 .'   if ('.$this->_tst.'.offsetWidth > 0) {'."\n\n";
		return $js;
	}
	
	function endMapFunction() {
		$js = '   }'."\n"
			 .' }'."\n\n";
		return $js;
	}
	
	function setGeoCoder() {
		$js = $this->_geocoder .' = new google.maps.Geocoder();'."\n";
		return $js;
	}
	
	function exportZoom($zoom, $value) {
		$js =' var phocaStartZoom'.$this->_id.' = '.$zoom.';'."\n"
			.' var phocaZoom'.$this->_id.' = null;'."\n"
			.' google.maps.event.addListener('.$this->_map.', "zoom_changed", function(phocaStartZoom'.$this->_id.', phocaZoom'.$this->_id.') {'."\n"
			.'   phocaZoom'.$this->_id.' = '.$this->_map.'.getZoom();'."\n"
			.'   '.$value.'.value = phocaZoom'.$this->_id.';'."\n" // value has no id (used in admin)
			.' });'."\n\n";
		return $js;
	}
	
	
	function exportMarker($name, $type, $latitude, $longitude, $valueLat, $valueLng) {
		
		$js = ' var phocaPoint'.$name.$this->_id.' = new google.maps.LatLng('. $latitude.', ' .$longitude.');'."\n";
		
		if ($name == 'Global') {
			$js .= ' markerPhocaMarker'.$name.$this->_id.' = new google.maps.Marker({'."\n";
		} else {
			$js .= ' var markerPhocaMarker'.$name.$this->_id.' = new google.maps.Marker({'."\n";
		}
		$js	.= '   position: phocaPoint'.$name.$this->_id.','."\n"
			  .'   map: '.$this->_map.','."\n"
			  .'   draggable: true'."\n"
		      .' });'."\n\n";
		
		if ($name == 'Global') {
			$js .= ' infoPhocaWindow'.$name.$this->_id.' = new google.maps.InfoWindow({'."\n";
		} else {
			$js .= ' var infoPhocaWindow'.$name.$this->_id.' = new google.maps.InfoWindow({'."\n";
		}
		
		$js .='   content: markerPhocaMarker'.$name.$this->_id.'.getPosition().toUrlValue(6)'."\n"
			  .' });'."\n\n";
		
		// Events
		$js .= ' google.maps.event.addListener(markerPhocaMarker'.$name.$this->_id.', \'dragend\', function() {'."\n"
			.'   var phocaPointTmp'.$this->_id.' = markerPhocaMarker'.$name.$this->_id.'.getPosition();'."\n"
			.'   markerPhocaMarker'.$name.$this->_id.'.setPosition(phocaPointTmp'.$this->_id.');'."\n"
			.'   closeMarkerInfo'.$name.$this->_id.'();'."\n"
			.'   exportPoint'.$name.$this->_id.'(phocaPointTmp'.$this->_id.');'."\n"
			.' });'."\n\n";
		
		// The only one place which needs to be edited to work with more markers
		// Comment it for working with more markers
		// Or add new behaviour to work with adding new marker to the map
		$js .= ' google.maps.event.addListener('.$this->_map.', \'click\', function(event) {'."\n"
			.'   var phocaPointTmp2'.$this->_id.' = event.latLng;'."\n"
			.'   markerPhocaMarker'.$name.$this->_id.'.setPosition(phocaPointTmp2'.$this->_id.');'."\n"
			.'   closeMarkerInfo'.$name.$this->_id.'();'."\n"
			.'   exportPoint'.$name.$this->_id.'(phocaPointTmp2'.$this->_id.');'."\n"
		   .' });'."\n\n";
		   
		$js .= ' google.maps.event.addListener(markerPhocaMarker'.$name.$this->_id.', \'click\', function(event) {'."\n"
				.'   openMarkerInfo'.$name.$this->_id.'();'."\n"
				.' });'."\n\n";
			
		$js .= ' function openMarkerInfo'.$name.$this->_id.'() {'."\n"
				.'   infoPhocaWindow'.$name.$this->_id.'.content = markerPhocaMarker'.$name.$this->_id.'.getPosition().toUrlValue(6);'."\n"
				.'   infoPhocaWindow'.$name.$this->_id.'.open('.$this->_map.', markerPhocaMarker'.$name.$this->_id.' );'."\n"
				.' }'."\n\n";
		 $js .= ' function closeMarkerInfo'.$name.$this->_id.'() {'."\n"
				.'   infoPhocaWindow'.$name.$this->_id.'.close('.$this->_map.', markerPhocaMarker'.$name.$this->_id.' );'."\n"
				.' }'."\n\n";
		  
		$js .= ' function exportPoint'.$name.$this->_id.'(phocaPointTmp3'.$this->_id.') {'."\n"
				.'   '.$valueLat.'.value = phocaPointTmp3'.$this->_id.'.lat();'."\n"  // valueLat has no id (used in admin)
				.'   '.$valueLng.'.value = phocaPointTmp3'.$this->_id.'.lng();'."\n"; // valueLng has no id (used in admin)
		if ($type == 'marker') {
			$js .='   setPMGPSLatitude(phocaPointTmp3'.$this->_id.'.lat());'."\n"// no id - global function
				 .'   setPMGPSLongitude(phocaPointTmp3'.$this->_id.'.lng());'."\n";// no id - global function
		}		
		$js.=' }'."\n\n";
		
		return $js;
	}
	
	function addAddressToMapFunction($name, $elementId = 'phocaAddressEl', $type = '', $valueLat, $valueLng ) {
		$js ='function addAddressToMap'.$this->_id.'() {'."\n"
		.'   var phocaAddress'.$this->_id.' = document.getElementById("'.$elementId.$this->_id.'").value;'."\n"
		.'   if ('.$this->_geocoder.') {'."\n"
		.'      '.$this->_geocoder.'.geocode( { \'address\': phocaAddress'.$this->_id.'}, function(results'.$this->_id.', status'.$this->_id.') {'."\n"
		.'         if (status'.$this->_id.' == google.maps.GeocoderStatus.OK) {'."\n"
		.'            var phocaLocation'.$this->_id.' = results'.$this->_id.'[0].geometry.location;'."\n"
		.'            var phocaLocationAddress'.$this->_id.' = results'.$this->_id.'[0].formatted_address'."\n"
		.'            '.$this->_map.'.setCenter(phocaLocation'.$this->_id.');'."\n"
		.'            markerPhocaMarker'.$name.$this->_id.'.setPosition(phocaLocation'.$this->_id.');'."\n"
		.'            infoPhocaWindow'.$name.$this->_id.'.content = \'<div>\'+ phocaLocationAddress'.$this->_id.' +\'</div><div>&nbsp;</div><div>\'+ phocaLocation'.$this->_id.' +\'</div>\';'."\n"
		.'            infoPhocaWindow'.$name.$this->_id.'.open('.$this->_map.', markerPhocaMarker'.$name.$this->_id.' );'."\n"
		.'            '.$valueLat.'.value = phocaLocation'.$this->_id.'.lat();'."\n"// valueLat has no id (used in admin)
		.'            '.$valueLng.'.value = phocaLocation'.$this->_id.'.lng();'."\n";// valueLng has no id (used in admin)
		
		if ($type == 'marker') {
			$js .='            setPMGPSLatitude(phocaLocation'.$this->_id.'.lat());'."\n";// no id - global function
			$js .='            setPMGPSLongitude(phocaLocation'.$this->_id.'.lng());'."\n";// no id - global function
		}

		$js .='         } else {'."\n"
		.'            alert("'.JText::_('Geocode not found').' (" + status'.$this->_id.' + ")");'."\n"
		.'         }'."\n"
		.'      });'."\n"
		.'   }'."\n"
		.'}'."\n\n";
		
		return $js;
	}
	
	function setDirectionFunction($printIcon = 0, $mapId = '', $mapAlias = '', $lang = '') {
		$js ='function setPhocaDir'.$this->_id.'(fromPMAddress'.$this->_id.', toPMAddress'.$this->_id.') {'."\n"
		.'   var request'.$this->_id.' = {'."\n"
		.'      origin:		fromPMAddress'.$this->_id.', '."\n"
		.'      destination:	toPMAddress'.$this->_id.','."\n"
		.'      travelMode: 	google.maps.DirectionsTravelMode.DRIVING'."\n"
		.'   };'."\n\n";
		
		$js .='   '.$this->_dirservice.'.route(request'.$this->_id.', function(response'.$this->_id.', status'.$this->_id.') {'."\n"
		.'   '."\n"
		.'    if (status'.$this->_id.' == google.maps.DirectionsStatus.OK) {'."\n";
		
		// In route view we don't need to create link to itself - to route view and we don't need the mapId
		// this is why $mapId = '' is as default in this function
		if($printIcon) {
			$js .='      pPI'.$this->_id.' = document.getElementById(\'phocaMapsPrintIcon'.$this->_id.'\');'. "\n"
				.'      pPI'.$this->_id.'.style.display=\'block\';'. "\n"
				.'      var from64'.$this->_id.' = Base64.encode(fromPMAddress'.$this->_id.').toString();'. "\n"
				.'      var to64'.$this->_id.'   = Base64.encode(toPMAddress'.$this->_id.').toString();'. "\n"
				.'      pPI'.$this->_id.'.innerHTML = \''.$this->getIconPrint($mapId, $mapAlias, $lang).'\';'. "\n\n";
		}
		
		$js .='      '.$this->_dirdisplay.'.setDirections(response'.$this->_id.');'."\n"
		.'   } else if (google.maps.DirectionsStatus.NOT_FOND) {'."\n"
		.'      alert("'. JText::_('NOT_FOUND').'");'."\n"
		.'   } else if (google.maps.DirectionsStatus.ZERO_RESULTS) {'."\n"
		.'      alert("'. JText::_('ZERO_RESULTS').'");'."\n"
		.'   } else if (google.maps.DirectionsStatus.MAX_WAYPOINTS_EXCEEDED) {'."\n"
		.'      alert("'. JText::_('MAX_WAYPOINTS_EXCEEDED').'");'."\n"
		.'   } else if (google.maps.DirectionsStatus.OVER_QUERY_LIMIT) {'."\n"
		.'      alert("'. JText::_('OVER_QUERY_LIMIT').'");'."\n"
		.'   } else if (google.maps.DirectionsStatus.INVALID_REQUEST) {'."\n"
		.'      alert("'. JText::_('INVALID_REQUEST').'");'."\n"
		.'   } else if (google.maps.DirectionsStatus.REQUEST_DENIED) {'."\n"
		.'      alert("'. JText::_('REQUEST_DENIED').'");'."\n"
		.'   } else if (google.maps.DirectionsStatus.UNKNOWN_ERROR) {'."\n"
		.'      alert("'. JText::_('UNKNOWN_ERROR').'");'."\n"
		.'   } else {'."\n"
		.'      alert("'. JText::_('UNKNOWN_ERROR').'");'."\n"
		.'   } '."\n"
		.'  });'."\n"
		.'}'."\n\n";
		
		return $js;
	}
	
	function directionInitializeFunction($from, $to){
		$js ='function initialize'.$this->_id.'(fromPMAddress'.$this->_id.', toPMAddress'.$this->_id.') {'."\n"
		.'     '.$this->_dirdisplay.' = new google.maps.DirectionsRenderer();'."\n"
		.'     '.$this->_dirservice.' = new google.maps.DirectionsService();'."\n"
		.'     '.$this->_dirdisplay.'.setPanel(document.getElementById("directionsPanel'.$this->_id.'"));'."\n"
		.'     setPhocaDir'.$this->_id.'(\''.base64_decode($from).'\', \''.base64_decode($to).'\');'."\n"
		.'}'."\n\n"
		.'google.setOnLoadCallback(initialize'.$this->_id.');'."\n";
		return $js;
	}

	
	function getIconPrint($idMap, $idMapAlias = '', $lang = '') {
		
		$suffix	= 'tmpl=component&print=1';
		//$status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';
		$status = 'width=640,height=480,menubar=yes,resizable=yes,scrollbars=yes,resizable=yes';
		
		$link 		= PhocaMapsHelperRoute::getPrintRouteRoute( $idMap, $idMapAlias, $suffix);
		$link		= JRoute::_( $link );
		$isThereQM 	= false;
		$isThereQM 	= preg_match("/\?/i", $link);

		if ($isThereQM) {
			$amp = '&amp;';
		} else {
			$amp = '?';
		}
		$link	= $link . $amp . 'from=\'+from64'.$this->_id.'+\'&amp;to=\'+to64'.$this->_id.'+\'';
		
		if ($lang != '') {
			$link = $link . '&amp;lang='.$lang.'';
		}
		
		$output = '<div class="pmprintroutelink">'
		.'<a href=\\u0022'.$link.'\\u0022 rel=\\u0022nofollow\\u0022 onclick=\\u0022window.open(this.href,\\\'phocaMapRoute\\\',\\\''.$status.'\\\'); return false;\\u0022 >'.JText::_('Print Route').'</a>'
		.'</div>'
		.'<div style="clear:both"></div>';
		
		return $output;
		
	}
	
	function getIconPrintScreen() {
		$output = '<div class="pmprintscreen"><a class="pmprintscreena" href="javascript: void()" onclick="window.print();return false;">'.JText::_('Print').'</a>'
		.'&nbsp; <a class="pmprintscreena" href="javascript: void window.close()">'.JText::_( 'Close Window' ). '</a></div><div style="clear:both;"></div>';
		return $output;
	}
	
	function setKMLFile($kmlFile) {
		$js =' var phocaGeoXml'.$this->_id.' = new geoXML3.parser({map: '.$this->_map.'});'."\n"
			.' phocaGeoXml'.$this->_id.'.parse(\''.$kmlFile.'\');'."\n"; // File is checked in View (after loading from Model)
		return $js;
	}
}
?>