<?php
/* Copyright (C) 2013-2015	Charlie BENKE	<charlie@patas-monkey.com>
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
 *	\file       htdocs/mylist/card.php
 *	\ingroup    listes
 *	\brief      List card
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory
dol_include_once('/mylist/class/mylist.class.php')
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';


$langs->load("mylist@mylist");

$code=GETPOST('code','alpha');
$rowid=GETPOST('rowid','alpha');
$action=GETPOST('action','alpha');
$backtopage=GETPOST('backtopage','alpha');

if (!$user->rights->mylist->lire) accessforbidden();

$object = new Mylist($db);

/*
 * Actions
 */


if ($action == 'add' && $user->rights->mylist->creer)
{
	$mesg="";
	$error=0;
	if (empty($_POST["label"]))
	{
		$mesg.='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("label")).'</div>';
		$error++;
	}
	if (empty($_POST["querylist"]))
	{
		$mesg.='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("querylist")).'</div>';
		$error++;
	}

	if (! $error)
	{
		$object->code		= $_POST["code"];
		$object->label		= $_POST["label"];
		$object->titlemenu	= $_POST["titlemenu"];
		$object->mainmenu	= $_POST["mainmenu"];
		$object->leftmenu	= $_POST["leftmenu"];
		$object->elementtab	= $_POST["elementtab"];
		$object->perms		= $_POST["perms"];
		$object->langs		= $_POST["langs"];
		$object->author		= $_POST["author"];
		$object->active		= $_POST["active"];
		$object->export		= $_POST["export"];
		$object->model_pdf	= $_POST["model_pdf"];
		// to bypass the sqlinjection
		$object->querylist=str_replace("#SEL#", "SELECT", $_POST["querylist"]);
		$querydo=str_replace( "#SEL#", "SELECT", GETPOST("querydo"));
		$querydo=str_replace( "#UPD#", "UPDATE", $querydo);
		$querydo=str_replace( "#INS#", "INSERT", $querydo);
		$querydo=str_replace( "#DEL#", "DELETE", $querydo);
		$object->querydo=$querydo;

		$object->fieldinit	= $_POST["fieldinit"];
		
		$result = $object->create($user);
		if ($result == 0)
		{
			$langs->load("errors");
			$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
			$error++;
		}

		if (! $error)
		{
			header("Location:card.php?rowid=".$result);
			exit;
		}
		else
			$action = 'create';
	}
	else
		$action = 'create';
}
elseif ($action == 'validate' && $user->rights->mylist->creer)
{
	// met � jour la liste
	$object->code=		GETPOST("code");
	$object->rowid=		GETPOST("rowid");
	$object->label=		GETPOST("label");
	$object->titlemenu=	GETPOST("titlemenu");
	$object->mainmenu=	GETPOST("mainmenu");
	$object->leftmenu=	GETPOST("leftmenu");
	$object->elementtab=GETPOST("elementtab");
	$object->perms=		GETPOST("perms");
	$object->langs=		GETPOST("langs");
	$object->author=	GETPOST("author");
	$object->active=	GETPOST("active");
	$object->export=	GETPOST("export");
	$object->model_pdf= GETPOST("model_pdf");

	// to bypass the sqlinjection
	$querylist=str_replace( "#SEL#", "SELECT", GETPOST("querylist"));
	$object->querylist=	$querylist;
	$querydo=GETPOST("querydo");
	$querydo=str_replace( "#SEL#", "SELECT", GETPOST("querydo"));
	$querydo=str_replace( "#UPD#", "UPDATE", $querydo);
	$querydo=str_replace( "#INS#", "INSERT", $querydo);
	$querydo=str_replace( "#DEL#", "DELETE", $querydo);
	$object->querydo=	$querydo;
	$object->fieldinit=	GETPOST("fieldinit"); 

	$object->update();
}
elseif ($action == 'importation' && $user->rights->mylist->creer)
{
	if (GETPOST("importexport"))
	{
		
		$object->importlist(GETPOST("importexport"));
		$rowid=$object->rowid;
		if ($rowid == 0)
		{
			$langs->load("errors");
			$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
			$error++;
		}

		if (! $error)
		{
			header("Location:card.php?rowid=".$rowid);
			exit;
		}
		else
			$action = 'importexport';
		//$object->fetch($object->code);
	}
	else
	{
		header("Location:list.php");
		exit;
	}
}
elseif ($action == 'delete' && $user->rights->mylist->supprimer)
{
	$object->rowid=	GETPOST("rowid");
	$object->delete($user);
	header("Location:list.php");
	exit;
}

/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);


$help_url="EN:Module_mylist|FR:Module_mylist|ES:M&oacute;dulo_mylist";
llxHeader("",$langs->trans("Mylist"),$help_url);



if ($action == 'create' && $user->rights->mylist->creer)
{
	/*
	 * Create
	 */
	print_fiche_titre($langs->trans("NewMylist"));

	dol_htmloutput_mesg($mesg);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print '<table class="border" width="100%">';

	// label
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Label").'</span></td><td><input size="30" type="text" name="label" value="'.$_POST["label"].'"></td></tr>';

	// TitleMenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("MenuTitle").'</span></td><td><input size="30" type="text" name="titlemenu" value="'.$_POST["titlemenu"].'"></td></tr>';

	// Mainmenu
	print '<tr><td>'.$langs->trans("MainMenu").'</td><td><input size="30" type="text" name="mainmenu" value="'.$_POST["mainmenu"].'"></td></tr>';

	// Leftmenu
	print '<tr><td>'.$langs->trans("LeftMenu").'</td><td><input size="30" type="text" name="leftmenu" value="'.$_POST["leftmenu"].'"></td></tr>';

	// elementtab
	print '<tr><td>'.$langs->trans("ElementTab").'</td><td>'.$object->getSelectelementTab("").'</td></tr>';

	// perms
	print '<tr><td>'.$langs->trans("perms").'</td><td><input size="30" type="text" name="perms" value="'.$_POST["perms"].'"></td></tr>';

	// langs
	print '<tr><td>'.$langs->trans("langs").'</td><td><input size="30" type="text" name="langs" value="'.$_POST["langs"].'"></td></tr>';

	// author
	print '<tr><td>'.$langs->trans("author").'</td><td><input size="30" type="text" name="author" value="'.$_POST["author"].'"></td></tr>';

	if ($conf->global->MYLIST_CSV_EXPORT =="1")
	{	
		print '<tr><td>'.$langs->trans("ExportListCSV").'</td><td align=left >';
		print $form->selectyesno('export',"",1);
		print '</td></tr>';
	}
	
	if ($conf->global->MYLIST_ADDON_PDF)
	{
		// Load array def with activated templates
		$def = array();
		$sql = "SELECT nom";
		$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
		$sql.= " WHERE type = 'mylist'";
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
		if (count($def)	> 0 )
		{
			print '<tr><td>'.$langs->trans("DocumentModel").'</td><td align=left >';
			print $form->selectarray('model_pdf', $def, "", 1, 1, 1);
			print '</td></tr>';
		}
	}

	// querylist
	print '<tr><td valign=top><span class="fieldrequired">'.$langs->trans("QueryList").'</span><br>'.$langs->trans("explainbypassSQLinjection").'</td>';
	print '<td ><textarea name="querylist" cols=100 rows=10>'.$_POST["querylist"].'</textarea></td></tr>';

	// querydo
	print '<tr><td valign=top>'.$langs->trans("QueryDo").'<br>'.$langs->trans("explainbypassSQLinjectionDo").'</td>';
	print '<td ><textarea name="querydo" cols=100 rows=10>'.$_POST["querydo"].'</textarea></td></tr>';
	
	// fieldinit value
	print '<tr><td valign=top>'.$langs->trans("DefaultInitFields").'</td>';
	print '<td ><textarea name="fieldinit" cols=100 rows=5>'.$object->fieldinit.'</textarea></td></tr>';
	
	print '</table>';

	print '<br><center>';
	print '<input type="submit" class="button" value="'.$langs->trans("Create").'">';
	if (! empty($backtopage))
	{
	    print ' &nbsp; &nbsp; ';
	    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	}
	print '</center>';
	print '</form>';
}
elseif ($action == 'importexport' && $user->rights->mylist->creer)
{
	/*
	 * Import/export list data
	 */
	print_fiche_titre($langs->trans("ImportExport"));

	dol_htmloutput_mesg($mesg);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="importation">';
	print '<input type="hidden" name="code" value="'.GETPOST("code").'">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print '<table class="border" width="100%">';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("FillImportExportData").'</span></td></tr>';
	print '<td><textarea name=importexport cols=132 rows=20>';
	if($rowid)
		print $object->getexporttable($rowid);
	print '</textarea></td></tr>';	
	print '</table>';
	print '<br><center>';
	print '<input type="submit" class="button" value="'.$langs->trans("ImportMyList").'">';

	print '</center>';
	print '</form>';
}
elseif ($action == 'update' && $user->rights->mylist->creer)
{
	print_fiche_titre($langs->trans("UpdateMylist"));
	
	dol_htmloutput_mesg($mesg);

	$ret=$object->fetch( $rowid);
	
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="validate">';
	print '<input type="hidden" name="rowid" value="'.$rowid.'"></td></tr>';
	
	print '<table class="border" width="100%">';
	
	// label
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Label").'</span></td><td><input size="30" type="text" name="label" value="'.$object->label.'"></td></tr>';

	// TitleMenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("MenuTitle").'</span></td><td><input size="30" type="text" name="titlemenu" value="'.$object->titlemenu.'"></td></tr>';

	// Mainmenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("MainMenu").'</span></td><td><input size="30" type="text" name="mainmenu" value="'.$object->mainmenu.'"></td></tr>';

	// Leftmenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("LeftMenu").'</span></td><td><input size="30" type="text" name="leftmenu" value="'.$object->leftmenu.'"></td></tr>';

	// elementtab
	print '<tr><td>'.$langs->trans("ElementTab").'</td><td>'.$object->getSelectelementTab($object->elementtab).'</td></tr>';

	// perms
	print '<tr><td>'.$langs->trans("perms").'</td><td><input size="30" type="text" name="perms" value="'.$object->perms.'"></td></tr>';

	// langs
	print '<tr><td>'.$langs->trans("langs").'</td><td><input size="30" type="text" name="langs" value="'.$object->langs.'"></td></tr>';

	// author
	print '<tr><td>'.$langs->trans("author").'</td><td>';
	// non modifiable si il est renseign�
	if ($object->author)
		print '<input type="hidden" name="author" value="'.$object->author.'">'.$object->author;
	else
		print '<input size="30" type="text" name="author" value="'.$object->author.'">';
	print '</td></tr>';

	
	if ($conf->global->MYLIST_CSV_EXPORT =="1")
	{	
		print '<tr><td>'.$langs->trans("ExportListCSV").'</td><td align=left >';
		print $form->selectyesno('export',$object->export,1);
		print '</td></tr>';
	}
	
	if ($conf->global->MYLIST_ADDON_PDF)
	{
		// Load array def with activated templates
		$def = array();
		$sql = "SELECT nom";
		$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
		$sql.= " WHERE type = 'mylist'";
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
		if (count($def)	> 0 )
		{
			print '<tr><td>'.$langs->trans("DocumentModel").'</td><td align=left >';
			print $form->selectarray('model_pdf', $def, $object->model_pdf, 1,0,1);
			print '</td></tr>';
		}
	}
	print '<tr><td>'.$langs->trans("active").'</td><td align=left >';
	print $form->selectyesno('active',$object->active,1);
	print '</td></tr>';


	// querylist
	print '<tr><td valign=top><span class="fieldrequired">'.$langs->trans("QueryList").'</span><br>'.$langs->trans("explainbypassSQLinjection").'</td>';
	print '<td ><textarea name="querylist" cols=100 rows=10>'.$object->querylist.'</textarea></td></tr>';

	$querydo=str_replace( "SELECT", "#SEL#", $object->querydo);
	$querydo=str_replace( "UPDATE", "#UPD#", $querydo);
	$querydo=str_replace( "INSERT", "#INS#", $querydo);
	$querydo=str_replace( "DELETE", "#DEL#", $querydo);

	// querydo
	print '<tr><td valign=top>'.$langs->trans("QueryDo").'<br>'.$langs->trans("explainbypassSQLinjectionDo").'</td>';
	print '<td ><textarea name="querydo" cols=100 rows=10>'.$querydo.'</textarea></td></tr>';

	// fieldinit
	print '<tr><td valign=top>'.$langs->trans("DefaultInitFields").'</td>';
	print '<td ><textarea name="fieldinit" cols=100 rows=5>'.$object->fieldinit.'</textarea></td></tr>';

	print '</table>';

	print '<br><center>';
	print '<input type="submit" class="button" value="'.$langs->trans("Update").'">';
	if (! empty($backtopage))
	{
		print ' &nbsp; &nbsp; ';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	}
	print '</center>';

	print '</form>';

}
else
{
	$ret=$object->fetch($rowid);
	// charge les langues
	if ($object->langs)
		foreach(explode(":",$object->langs) as $newlang)
			$langs->load($newlang);
	/*
	 * Show
	 */
	print_fiche_titre($langs->trans("EditMylist"));

	dol_htmloutput_mesg($mesg);
	
	dol_fiche_head($head, 'list', $langs->trans("Mylist"),0,'list');

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/mylist/list.php">'.$langs->trans("BackToList").'</a>';

	// Label
	print '<tr><td width=25%>'.$langs->trans("Label").'</td><td >'.$object->label.'</td></tr>';

	// Menu
	print '<tr><td>'.$langs->trans("MenuTitle").'</td><td >'.$object->titlemenu.'</td></tr>';
	print '<tr><td>'.$langs->trans("MainMenu").'</td><td >'.$object->mainmenu.'</td></tr>';
	print '<tr><td>'.$langs->trans("LeftMenu").'</td><td >'.$object->leftmenu.'</td></tr>';
	print '<tr><td>'.$langs->trans("ElementTab").'</td><td >'.($object->elementtab?$langs->trans($object->elementtab):'').'</td></tr>';
	print '<tr><td>'.$langs->trans("perms").'</td><td >'.$object->perms.'</td></tr>';
	print '<tr><td>'.$langs->trans("langs").'</td><td >'.$object->langs.'</td></tr>';
	print '<tr><td>'.$langs->trans("author").'</td><td >'.$object->author.'</td></tr>';
	print '<tr><td>'.$langs->trans("querylist").'</td><td >'.$object->querylist.'</td></tr>';
	print '<tr><td>'.$langs->trans("querydo").'</td><td >'.$object->querydo.'</td></tr>';
	print '<tr><td>'.$langs->trans("DefaultInitFields").'</td><td >'.$object->fieldinit.'</td></tr>';
	if ($conf->global->MYLIST_CSV_EXPORT =="1")
		print '<tr><td>'.$langs->trans("ExportListCSV").'</td><td >'.yn($object->export).'</td></tr>';
	if ($conf->global->MYLIST_ADDON_PDF)
		print '<tr><td>'.$langs->trans("DocumentModel").'</td><td >'.$object->model_pdf.'</td></tr>';
	print '<tr><td>'.$langs->trans("active").'</td><td >'.yn($object->active).'</td></tr>';
	print '</table>';

	dol_fiche_end();

	/*
	 * Boutons actions de la liste
	 */
	print '<div class="tabsAction">';
	
	if ($user->rights->mylist->creer)
		print '<a class="butAction" href="card.php?rowid='.$object->rowid.'&action=update">'.$langs->trans('Update').'</a>';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Update').'</a>';

	if ($user->rights->mylist->creer)
		print '<a class="butAction" href="card.php?rowid='.$object->rowid.'&action=importexport">'.$langs->trans('ImportExport').'</a>';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('ImportExport').'</a>';

	// on autorise la suppression que sur les listes d�sactiv�s
	if ($user->rights->mylist->supprimer && !$object->active)
		print '<a class="butAction" href="card.php?rowid='.$object->rowid.'&action=delete">'.$langs->trans('Delete').'</a>';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';


	print "</div>";
	print "<br>\n";


	// display fields list

	$object->getChampsArray($rowid);

	if (! empty($conf->use_javascript_ajax))
	{
		include DOL_DOCUMENT_ROOT.'/mylist/tpl/ajaxrow.tpl.php';
	}

	if(is_array($object->listsUsed))
	{
		print '<table id="tablelines" class="noborder" >';
			print '<tr class="liste_titre">';
			print '<th width=150px>'.$langs->trans('field').'</td>' ;
			print '<th width=150px>'.$langs->trans('name').'</th>' ;
			print '<th width=100px>'.$langs->trans('namelangs').'</th>' ;
			print '<th width=75px>'.$langs->trans('alias').'</th>' ;
			print '<th width=75px>'.$langs->trans('type').'</th>' ;
			print '<th width=100px>'.$langs->trans('elementField').'</th>' ;
			print '<th width=75px>'.$langs->trans('width').'</th>' ;
			print '<th width=75px>'.$langs->trans('align').'</th>' ;
			print '<th width=50px>'.$langs->trans('enabled').'</th>' ;
			print '<th width=50px>'.$langs->trans('visible').'</th>' ;
			print '<th width=30px>'.$langs->trans('filter').'</th>' ;
			print '<th width=30px>'.$langs->trans('filterinit').'</th>' ;
			print '<th width=30px>&nbsp;</th>';
			print '</tr>';
		$var=true;

		foreach ($object->listsUsed as $key=> $value )
		{
			$var=!$var;
			print '<tr '.$bc[$var].' id="row-'.$key.'">'."\n";
			print '<td><a href=champ.php?mylistid='.$rowid.'&rowid='.$value['rowid'].'>'.($value['field']? $value['field']:$langs->trans('empty')).'</a></td>' ;
			print '<td>'.$value['name'].'</td>' ;
			print '<td>'.($value['name'] ? $langs->trans($value['name']) : '').'</td>' ;
			print '<td>'.$value['alias'].'</td>' ;
			print '<td>'.$value['type'].'</td>' ;
			print '<td>'.$value['param'].'</td>' ;
			print '<td>'.$value['width'].'</td>' ;
			print '<td>'.$langs->trans($value['align']).'</td>' ;
			print '<td>'.yn($value['enabled']).'</td>' ;
			print '<td>'.yn($value['visible']).'</td>' ;
			print '<td>'.yn($value['filter']).'</td>' ;
			print '<td>'.$value['filterinit'].'</td>' ;
			print '<td align="center" class="tdlineupdown">&nbsp;</td>';
			print '</tr>';
		}
		print "</table>";
	}

	/*
	 * Boutons actions des champs
	 */
	print '<div class="tabsAction">';

	if ($user->rights->mylist->creer)
		print '<a class="butAction" href="champ.php?mylistid='.$object->rowid.'">'.$langs->trans('AddField').'</a>';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('AddField').'</a>';

	print "</div>";
}

llxFooter();

$db->close();
?>