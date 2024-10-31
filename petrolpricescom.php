<?php

/*
Plugin Name: PetrolPrices.com
Plugin URI: http://www.petrolprices.com/feeds/#wordpress
Description: Displays the current minimum, maximum and average price of fuel in a specified area (UK only).  Data from PetrolPrices.com.
Version: 1.0
Author: Fubra Limited
Author URI: http://www.fubra.com/
*/

#
#  petrolprices.php
#
#  Created by Jonathon Wardman on 24-11-2009.
#  Copyright 2009, Fubra Limited. All rights reserved.
#
#  This program is free software: you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation, either version 3 of the License, or
#  (at your option) any later version.
#
#  You may obtain a copy of the License at:
#  http://www.gnu.org/licenses/gpl-3.0.txt
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.

$_currentFuelType = null;
$_currentPriceBand = null;
$_priceDataArray = array();

	 // General output...
function petrolprices_display_table($searchType = null, $searchValue = null) {

	$feedUrl = 'http://www.petrolprices.com/feeds/averages.xml';
	if (($searchType !== null) && ($searchValue !== null)) {
		$feedUrl .= '?search_type='.urlencode($searchType).'&search_value='.urlencode($searchValue);
	}

	$xmlParser = xml_parser_create();
	xml_set_element_handler($xmlParser, 'petrolprices_parse_start', 'petrolprices_parse_end');
	xml_set_character_data_handler($xmlParser, 'petrolprices_parse_data');
	if (xml_parse($xmlParser, file_get_contents($feedUrl), true)) {
		$highest = $average = $lowest = array();
		echo '<table id="petrolprices_averages">';
		echo '<tr><td></td>';
		foreach ($GLOBALS['_priceDataArray'] AS $fuelType => $fuelPrices) {
			$highest[] = $fuelPrices['HIGHEST'];
			$average[] = $fuelPrices['AVERAGE'];
			$lowest[] = $fuelPrices['LOWEST'];
			echo '<th>'.htmlentities($fuelType).'</th>';
		}
		echo '</tr><tr><th>Max</th>';
		foreach ($highest AS $price) {
			echo '<td>'.htmlentities($price).'p</td>';
		}
		echo '</tr>';
		echo '</tr><tr><th>Avg</th>';
		foreach ($average AS $price) {
			echo '<td>'.htmlentities($price).'p</td>';
		}
		echo '</tr>';
		echo '</tr><tr><th>Min</th>';
		foreach ($lowest AS $price) {
			echo '<td>'.htmlentities($price).'p</td>';
		}
		echo '</tr><tr><td colspan="'.(count($GLOBALS['_priceDataArray']) + 1).'">Data provided by <a href="http://www.petrolprices.com/">PetrolPrices.com</a></td></tr></table>';
	}

}

function petrolprices_parse_data($xmlParser, $priceData) {

	if ($GLOBALS['_currentPriceBand'] !== null && is_numeric($priceData)) {
		$GLOBALS['_priceDataArray'][$GLOBALS['_currentFuelType']][$GLOBALS['_currentPriceBand']] = $priceData;
	}

}

function petrolprices_parse_start($xmlParser, $elementName, $attributes) {

	switch ($elementName) {
		case 'FUEL':
			$GLOBALS['_currentFuelType'] = $attributes['TYPE'];
		break;
		default:
			if ($GLOBALS['_currentFuelType'] !== null) {
				$GLOBALS['_currentPriceBand'] = $elementName;
			}
		break;
	}

}

function petrolprices_parse_end($xmlParser, $elementName) {

	switch ($elementName) {
		case 'FUEL':
			$GLOBALS['_currentFuelType'] = null;
			$GLOBALS['_currentPriceBand'] = null;
		break;
	}

}

	 // Shortcode...
function shorttag_petrolprices($atts) {

	$searchDetails = (shortcode_atts(array('type' => null, 'value' => null), $atts));
	petrolprices_display_table($searchDetails['type'], $searchDetails['value']);

}
add_shortcode('petrolprices', 'shorttag_petrolprices');

	 // Widget...
if (function_exists('register_sidebar_widget')) {

	function widget_petrolprices($args) {

		extract($args);
		echo $before_widget.$before_title.'Petrol Prices'.$after_title;
		$settings = unserialize(get_option('widget_petrolprices'));
		petrolprices_display_table($settings['type'], $settings['value']);
		echo $after_widget;

	}
	
	function widget_control_petrolprices() {
	
		if (isset($_POST['petrolprices_search_type']) && isset($_POST['petrolprices_search_value'])) {
			update_option('widget_petrolprices', serialize(array('type' => $_POST['petrolprices_search_type'], 'value' => $_POST['petrolprices_search_value'])));
		}
		
		$settings = unserialize(get_option('widget_petrolprices'));
		
		echo '<p><label for="petrolprices_search_type">Search type:</label>';
		echo '<select class="widefat" id="petrolprices_search_type" name="petrolprices_search_type">';
		echo '<option value="postcode"'.(($settings['type'] == 'postcode') ? ' selected="selected"' : '').'>Postcode</option>';
		echo '<option value="town"'.(($settings['type'] == 'town') ? ' selected="selected"' : '').'>Town</option>';
		echo '<option value="county"'.(($settings['type'] == 'county') ? ' selected="selected"' : '').'>County</option>';
		echo '</select></p>';
		echo '<p><label for="petrolprices_search_value">Search value:</label><input class="widefat" id="petrolprices_search_value" name="petrolprices_search_value" value="'.htmlentities($settings['value']).'" type="text"></p>';
	
	}
	
	register_sidebar_widget('PetrolPrices.com', 'widget_petrolprices');
	register_widget_control('PetrolPrices.com', 'widget_control_petrolprices');

}