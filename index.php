<?php
/* Copyright (C) 2013		Charles-Fr BENKE	<charles.fr@benke.fr>
 *
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
 *    \file       htdocs/mylist/index.php
 *    \ingroup    Liste
 *    \brief      Page liste des listes personnalis�es
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory

dol_include_once('/mylist/class/mylist.class.php');;

$langs->load('mylist@mylist');

if (!$user->rights->mylist->lire) accessforbidden();

llxHeader("","",$langs->trans("Mylist"));

print_fiche_titre($langs->trans("MylistList"));

$object = new Mylist($db);
$lists = $object->get_all_mylist();

if ($lists != -1)
{
	print '<br><br>';
	print '<table id="listtable" class="noborder" width="100%">';
	print '<thead>';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("label").'</th>';
	print '<th>'.$langs->trans("menu").'</th>';
	print '</tr>';
	print '</thead>';
	print '<tbody>';
	$var=true;
	foreach ($lists as $list)
	{
		if($list->langs)
			foreach(explode(":", $list->langs) as $newlang)
				$langs->load($newlang);
		$var = ! $var;
		print "<tr ".$bc[$var].">\n";
		print "<td><a href='mylist.php?rowid=".$list['rowid']."'>".$list['label']."</a></td>\n";
		print "<td align='left'>".$langs->trans($list['mainmenu'])." / ".$langs->trans($list['leftmenu'])." / ".$list['titlemenu']."</td>\n";
		print "</tr>\n";
	}
	print '</tbody>';
	print "</table>";
}
else
{
	dol_print_error();
}

/*
 * Boutons actions
 */
print '<br><div class="tabsAction">';



print "</div>";

$db->close();
llxFooter();

if (!empty($conf->global->MAIN_USE_JQUERY_DATATABLES))
{
	print "\n";
	print '<script type="text/javascript">'."\n";
	print 'jQuery(document).ready(function() {'."\n";
	print 'jQuery("#listtable").dataTable( {'."\n";


	print '"bPaginate": true,'."\n";
	print '"bFilter": false,'."\n";
	print '"sPaginationType": "full_numbers",'."\n";
	print '"bJQueryUI": false,'."\n";
	print '"oLanguage": {"sUrl": "'.$langs->trans('datatabledict').'" },'."\n";
	print '"iDisplayLength": 25,'."\n";
	print '"aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],'."\n";
	print '"bSort": true,'."\n";

	print '} );'."\n";
	print '});'."\n";
	print '</script>'."\n";
}
?>
