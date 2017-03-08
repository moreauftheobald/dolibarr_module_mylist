<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2015	Charlie BENKE		<charlie@patas-monkey.com>

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/mylist/ajax/row.php
 *       \brief      File to return Ajax response on Row move
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory

dol_include_once('/mylist/class/mylist.class.php');

/*
 * View
 */

top_httphead();

	print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

	// order of the fields
	$roworder=GETPOST('roworder','alpha',2);
	// table of fields
	$table_element_line=json_decode(GETPOST('table_element_line'),true);
	// list to update
	$rowid=GETPOST('rowid','string');
//var_dump($table_element_line);
//print "==".$rowid;
//print "XXX==".$roworder;

	dol_syslog("AjaxRow roworder=".$roworder." fk_element=".$rowid, LOG_DEBUG);
	
	$object = new Mylist($db);
	$object->fetch($rowid);
	// get the fields order
	$rowordertab = explode(',',$roworder);
	// loop for order the table of fields
	$newArrays=array();
	foreach ($table_element_line as $key => $fields) 
	//foreach($rowordertab as $key)
	{
		//print "\nxx=".$fields['field'].'-'.($key-1).':'. array_search($key, $rowordertab)."\n";//;  $rowordertab[$key-1]."\n";
		// si on doit trier
		if (($key) != (array_search($key, $rowordertab)+1))
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."mylistdet";
			$sql.= " SET pos = ".(array_search($key, $rowordertab)+1);
			$sql.= " where rowid = ".$fields['rowid'];
			//print $sql;
			$resql = $db->query($sql);
		}
	}
?>
