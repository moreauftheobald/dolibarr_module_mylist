<?php
/* Copyright (C) 2014-2015     Charlie Benke <charlie@patas-monkey.com>
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
 *   	\file       htdocs/customlink/admin/customlink.php
 *		\ingroup    customlink
 *		\brief      Page to setup the module customlink (nothing to do)
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/mylist/core/lib/mylist.lib.php');

$langs->load("admin");
$langs->load("mylist@mylist");

if (! $user->admin) accessforbidden();


$type=array('yesno','texte','chaine');

$action = GETPOST('action','alpha');
$value = GETPOST('value','alpha');
$label = GETPOST('label','alpha');
$scandir = GETPOST('scandir','alpha');
$typedoc='mylist';

/*
 * Actions
 */

if ($action == 'setvalue')
{
	// save the setting
	dolibarr_set_const($db, "MYLIST_NB_ROWS", GETPOST('nbrows','int'),'chaine',0,'',$conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}
if ($action == 'setdefaultother')
{
	// save the setting
	dolibarr_set_const($db, "MAIN_USE_JQUERY_DATATABLES", GETPOST('value','int'),'chaine',0,'',$conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}
if ($action == 'setexportcsv')
{
	// save the setting
	dolibarr_set_const($db, "MYLIST_CSV_EXPORT", GETPOST('value','int'),'chaine',0,'',$conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}

// Activate a model
else if ($action == 'set')
{
	$ret = addDocumentModel($value, $typedoc, $label, $scandir);
}

else if ($action == 'del')
{
	$ret = delDocumentModel($value, $typedoc);
	if ($ret > 0)
	{
        if ($conf->global->MYLIST_ADDON_PDF == "$value") dolibarr_del_const($db, 'MYLIST_ADDON_PDF',$conf->entity);
	}
}
// Set default model
else if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "MYLIST_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->MYLIST_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $typedoc);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $typedoc, $label, $scandir);
	}
}

// Get setting
$nbrows=$conf->global->MYLIST_NB_ROWS;
if ($nbrows=="")
{
	$nbrows=25;
	dolibarr_set_const($db, "MYLIST_NB_ROWS", $nbrows,'chaine',0,'',$conf->entity);
}

if ($action == 'setdoc')
{
	$label = GETPOST('label','alpha');
	$scandir = GETPOST('scandir','alpha');

	$db->begin();

	if (dolibarr_set_const($db, "MYLIST_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		$conf->global->MYLIST_ADDON_PDF = $value;
	}

	// On active le modele
	$type='mylist';

	$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql_del.= " WHERE nom = '".$db->escape($value)."'";
	$sql_del.= " AND type = '".$type."'";
	$sql_del.= " AND entity = ".$conf->entity;
	dol_syslog("mylist/admin/admin.php ".$sql_del);
	$result1=$db->query($sql_del);

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
	$sql.= " VALUES ('".$value."', '".$type."', ".$conf->entity.", ";
	$sql.= ($label?"'".$db->escape($label)."'":'null').", ";
	$sql.= (! empty($scandir)?"'".$scandir."'":"null");
	$sql.= ")";
	dol_syslog("mylist/admin/admin.php ".$sql);
	$result2=$db->query($sql);
	if ($result1 && $result2)
	{
		$db->commit();
	}
	else
	{
		dol_syslog("mylist/admin/admin.php ".$db->lasterror(), LOG_ERR);
		$db->rollback();
	}
}

$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);

/*
 * View
 */
$form=new Form($db);

$help_url='EN:Module_mylist|FR:Module_mylist|ES:M&oacute;dulo_mylist';

llxHeader('',$langs->trans("MylistSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MylistSetup"),$linkback,'setup');


$head = mylist_admin_prepare_head();

dol_fiche_head($head, 'admin', $langs->trans("myList"), 0, 'mylist@mylist');


dol_htmloutput_mesg($mesg);

// la s�lection des status � suivre dans le process commercial
print '<br>';
print_titre($langs->trans("GeneralSetting"));
print '<br>';

$var=true;
print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td width="200px">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap >'.$langs->trans("Value").'</td>';
print '</tr>'."\n";

$var = !$var;
print '<tr '.$bc[$var].'>';
print '<td align=left>'.$langs->trans("NumberRowsInmyList").'</td>';
print '<td align=left>'.$langs->trans("InfoNumberRowsInmyList").'</td>';
print '<td  align=left>';
print '<form method="post" action="admin.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';
print '<input type=text value="'.$nbrows.'" name=nbrows>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td>';
print '</tr>'."\n";

$var = !$var;
print '<tr '.$bc[$var].'>';
print '<td align=left>'.$langs->trans("EnableDatatables").'</td>';
print '<td align=left>'.$langs->trans("InfoEnableDatatables").'</td>';
print '<td align=left >';
if ($conf->global->MAIN_USE_JQUERY_DATATABLES =="1")
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdefaultother&amp;value=0">'.img_picto($langs->trans("Activated"),'switch_on').'</a>';
else
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdefaultother&amp;value=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
print '</td>';
print '</tr>';
$var = !$var;
print '<tr '.$bc[$var].'>';
print '<td align=left>'.$langs->trans("EnableExportcsv").'</td>';
print '<td align=left>'.$langs->trans("InfoEnableExportcsv").'</td>';
print '<td align=left >';
if ($conf->global->MYLIST_CSV_EXPORT =="1")
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setexportcsv&amp;value=0">'.img_picto($langs->trans("Activated"),'switch_on').'</a>';
else
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setexportcsv&amp;value=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
print '</td>';
print '</tr>';
print '</table>';
// Boutons d'action

print '<br>';

/*
 *  Document templates generators
 */
print '<br>';
print_titre($langs->trans("MylistPDFModules"));

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$typedoc."'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql)
{
	$i = 0;
	$num_rows=$db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
}
else
{
	dol_print_error($db);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;
foreach ($dirmodels as $reldir)
{
	foreach (array('','/doc') as $valdir)
	{
		$dir = dol_buildpath($reldir."core/modules/mylist".$valdir);

		if (is_dir($dir))
		{
			$handle=opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle))!==false)
				{
					$filelist[]=$file;
				}
				closedir($handle);
				arsort($filelist);

				foreach($filelist as $file)
				{
					if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
					{
						if (file_exists($dir.'/'.$file))
						{
							$name = substr($file, 4, dol_strlen($file) -16);
							$classname = substr($file, 0, dol_strlen($file) -12);

							require_once($dir.'/'.$file);
							$module = new $classname($db);

							$modulequalified=1;
							if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;

							if ($modulequalified)
							{
								$var = !$var;
								print '<tr '.$bc[$var].'><td width="100">';
								print (empty($module->name)?$name:$module->name);
								print "</td><td>\n";
								if (method_exists($module,'info')) print $module->info($langs);
								else print $module->description;
								print '</td>';

								// Active
								if (in_array($name, $def))
								{
									print '<td align="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'">';
									print img_picto($langs->trans("Enabled"),'switch_on');
									print '</a>';
									print '</td>';
								}
								else
								{
									print "<td align=\"center\">\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&scandir='.$module->scandir.'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
									print "</td>";
								}

								// Defaut
								print "<td align=\"center\">";
								if ($conf->global->MYLIST_ADDON_PDF == "$name")
								{
									print img_picto($langs->trans("Default"),'on');
								}
								else
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name.'&scandir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip =	''.$langs->trans("Name").': '.$module->name;
								$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
								if ($module->type == 'pdf')
								{
									$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								print '<td align="center">';
								print $form->textwithpicto('',$htmltooltip,1,0);
								print '</td>';

								// Preview
								print '<td align="center">';
								if ($module->type == 'pdf')
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
								}
								else
								{
									print img_object($langs->trans("PreviewNotAvailable"),'generic');
								}
								print '</td>';

								print "</tr>\n";
							}
						}
					}
				}
			}
		}
	}
}
print '</table>';

dol_fiche_end();


llxFooter();

$db->close();
?>