<?php
/* Copyright (C) 2013-2015	Charlie BENKE		<charlie@patas-monkey.com>
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
 *	\file       htdocs/mylist/champ.php
 *	\ingroup    mylist
 *	\brief      Page of a list fields
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory
dol_include_once('/mylist/class/mylist.class.php')
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

$langs->load("mylist@mylist");

$mylistid = GETPOST("mylistid",'int');
$rowid = GETPOST("rowid",'int');
$action = GETPOST("action",'string');


// Security check
$socid=0;
if (! $user->rights->mylist->lire) accessforbidden();

$object = new MyList($db);
$ret=$object->fetch($mylistid);
$object->getChampsArray();

/*
 * Actions
 */

if ($action == 'edit' && $user->rights->mylist->creer)
{
	$error=0;

	if (empty($_POST["nameField"]))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Name")).'</div>';
	}
	
	if (! $error)
	{
		$object->idfield	= $rowid;
		$object->name		= GETPOST("nameField");
		$object->field		= GETPOST("field");
		$object->alias		= GETPOST("alias");
		$object->type		= GETPOST("type");
		$object->param		= GETPOST("param");
		$object->alias		= GETPOST("alias");
		$object->align		= GETPOST("align");
		$object->enabled	= GETPOST("enabled");
		$object->visible	= GETPOST("visible");
		$object->filter		= GETPOST("filter");
		$object->width		= GETPOST("width");
		$object->sumreport	= GETPOST("sumreport");
		$object->avgreport	= GETPOST("avgreport");
		$object->filterinit	= GETPOST("filterinit");

		if ($object->updateField($user, $rowid ) == 1)
		{
			$mesg = '<div class="ok">'.$langs->trans('UpdateSucceeded').'</div>';
		}
		else
		{
			$error++;
			$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
		}
	}
	$action='';
}

if ($action == 'add' && ! $_POST["cancel"] && $user->rights->mylist->creer)
{
	$error=0;

	if (empty($_POST["field"]))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("fieldName")).'</div>';
	}
	
	if (empty($_POST["nameField"]))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Name")).'</div>';
	}
	if (! $error)
	{
		$object->idfield	= $mylistid;
		$object->name		= GETPOST("nameField");
		$object->field		= GETPOST("field");
		$object->alias		= GETPOST("alias");
		$object->type		= GETPOST("type");
		$object->param		= GETPOST("param");
		$object->alias		= GETPOST("alias");
		$object->align		= GETPOST("align");
		$object->enabled	= GETPOST("enabled");
		$object->visible	= GETPOST("visible");
		$object->filter		= GETPOST("filter");
		$object->width		= GETPOST("width");
		$object->filterinit	= GETPOST("filterinit");
		
		// r�cup�ration de la position du dernier champ
		$object->pos		= $object->getlastpos($mylistid);
		
		$rowid = $object->addField($user, $mylistid);
		if ($rowid >0)
		{
			header("Location: ".DOL_URL_ROOT.'/mylist/champ.php?mylistid='.$mylistid.'&rowid='.$rowid);
			exit;
		}
		else
		{
			$error++;
			$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
			$action='';
		}
	}
	else
	{
		$action='';
	}
}

if ($action == 'confirm_delete' && GETPOST('confirm')== "yes" && $user->rights->mylist->supprimer)
{
	if ($object->deleteField($user, $rowid) == 1)
	{
		header("Location: ".DOL_URL_ROOT.'/mylist/card.php?rowid='.$mylistid);
		exit;
	}
	else
	{
		$langs->load("errors");
		$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
		$action='';
	}
}

$help_url="EN:Module_mylist|FR:Module_mylist|ES:M&oacute;dulo_mylist";
llxHeader("",$langs->trans("ListFields"),$help_url);


dol_htmloutput_mesg($mesg);


if (! empty($rowid))
{
	print_fiche_titre($langs->trans("EditField"));
}
else
{
	print_fiche_titre($langs->trans("AddField"));
}

$form = new Form($db);
if ($action == 'delete')
{
	$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?mylistid=".$mylistid.'&rowid='.$rowid, 
		$langs->trans("DeleteAField"),$langs->trans("ConfirmDeleteAField"),"confirm_delete");
	if ($ret == 'html') print '<br>';
}
/*
 * View
 */


dol_fiche_head($head, 'list', $langs->trans("Mylist"),0,'list');

print '<table class="border" width="100%">';

$linkback = '<a href="'.DOL_URL_ROOT.'/mylist/card.php?rowid='.$mylistid.'">'.$langs->trans("BackTomyList").'</a>';


// Label
print '<tr><td width=25%>'.$langs->trans("Label").'</td><td>'.$object->label.'</td><td width=10% >'.$linkback.'</td></tr>';
print '<tr><td>'.$langs->trans("MenuTitle").'</td><td colspan=2>'.$object->menutitle.'</td></tr>';
print '<tr><td>'.$langs->trans("MainMenu").'</td><td colspan=2>'.$object->mainmenu.'</td></tr>';
print '<tr><td>'.$langs->trans("LeftMenu").'</td><td colspan=2>'.$object->leftmenu.'</td></tr>';
print '<tr><td>'.$langs->trans("ElementTab").'</td><td colspan=2>'.($object->elementtab?$langs->trans($object->elementtab):'').'</td></tr>';


print '</table>';

dol_fiche_end();



$form = new Form($db);

print '<form action="'.$_SERVER["PHP_SELF"].'?mylistid='.$mylistid.'&rowid='.$rowid.'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

if (! empty($rowid))
{
	$ArrayFields=$object->FetchChampArray($rowid);
	/*
	 * Fiche champ en mode edit
	 */
	 
	print '<input type="hidden" name="action" value="edit">';
	print '<input type="hidden" name="mylistid" value="'.$mylistid.'">';
	
	print '<table class="border" width="50%">';
	
	// database fieldname = key
	print '<tr><td class="fieldrequired">'.$langs->trans("fieldName").'</td>';
	print '<td><input type=text name="field" value="'.$ArrayFields['field'].'"></td></tr>'."\n";

	// database alias
	print '<tr><td >'.$langs->trans("Alias").'</td>';
	print '<td><input type=text name="alias" value="'.$ArrayFields['alias'].'"></td></tr>'."\n";

	// FieldName
	print '<tr><td >'.$langs->trans("Name").'</td>';
	print '<td><input type=text name="nameField" value="'.$ArrayFields['name'].'"></td></tr>'."\n";

	// type of Fields
	print '<tr><td >'.$langs->trans("Type").'</td>';
	print '<td>'.$object->getSelectTypeFields($ArrayFields['type']).'</td></tr>'."\n";
	
	// element of Fields
	print '<tr><td >'.$langs->trans("ElementField").'</td>';
	print '<td><input type=text size=60 name="param" value="'.$ArrayFields['param'].'"></td></tr>'."\n";

	// width cols
	print '<tr><td >'.$langs->trans("Width").'</td>';
	print '<td><input type=text size=6 name="width" value="'.$ArrayFields['width'].'"></td></tr>'."\n";

	// align fields
	print '<tr><td >'.$langs->trans("align").'</td><td>';
	print $form->selectarray('align', array('left'=>'gauche','center'=>'milieu','right'=>'droite'), $ArrayFields['align']);
	print '</td></tr>'."\n";
	
	print '<tr><td >'.$langs->trans("enabled").'</td>';
	print '<td>';
	print $form->selectyesno('enabled', ($ArrayFields['enabled']=='1'?'yes':'no'), 1);

	print '</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("visible").'</td>';
	print '<td>';
	print $form->selectyesno('visible', ($ArrayFields['visible']=='1'?'yes':'no'), 1);
	print '</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("filter").'</td>';
	print '<td>';
	print $form->selectyesno('filter', ($ArrayFields['filter']=='1'?'yes':'no'), 1);
	print '</td></tr>'."\n";

	// filter init
	print '<tr><td >'.$langs->trans("FilterInit").'</td>';
	print '<td><input type=text size=15 name="filterinit" value="'.$ArrayFields['filterinit'].'"></td></tr>'."\n";

	// le champs suppl�mentaire pour la suite
	if($conf->global->MAIN_MODULE_MYLISTMORE)
	{
		// sum
		print '<tr><td >'.$langs->trans("sumreport").'</td>';
		print '<td>';
		print $form->selectyesno('sumreport', ($ArrayFields['sumreport']=='1'?'yes':'no'), 1);
		print '</td></tr>'."\n";
		
		// average
		print '<tr><td >'.$langs->trans("avgreport").'</td>';
		print '<td>';
		print $form->selectyesno('avgreport', ($ArrayFields['avgreport']=='1'?'yes':'no'), 1);
		print '</td></tr>'."\n";

		
	}

}
else
{
	print '<input type="hidden" name="action" value="add">';
	print '<table class="border" width="50%">';
	
	// database fieldname = key
	print '<tr><td class="fieldrequired">'.$langs->trans("fieldName").'</td>';
	print '<td><input type=text name="field" value=""></td></tr>'."\n";

	// database alias
	print '<tr><td >'.$langs->trans("Alias").'</td>';
	print '<td><input type=text name="alias" value=""></td></tr>'."\n";

	// FieldName
	print '<tr><td class="fieldrequired">'.$langs->trans("Name").'</td>';
	print '<td><input type=text name="nameField" value=""></td></tr>'."\n";

	// type of Fields
	print '<tr><td >'.$langs->trans("Type").'</td>';
	print '<td>'.$object->getSelectTypeFields("").'</td></tr>'."\n";
	
	// element of Fields
	print '<tr><td >'.$langs->trans("ElementField").'</td>';
	print '<td><input type=text name="param" value=""></td></tr>'."\n";

	// width cols
	print '<tr><td >'.$langs->trans("Width").'</td>';
	print '<td><input type=text size=6 name="width" value="100"></td></tr>'."\n";

	// align fields
	print '<tr><td >'.$langs->trans("align").'</td><td>';
	print $form->selectarray('align', array('left'=>$langs->trans("left"),'center'=>$langs->trans("center"),'right'=>$langs->trans("right")), 'left');
	print '</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("enabled").'</td>';
	print '<td>';
	print $form->selectyesno('enabled', 'yes', 1);
	print '</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("visible").'</td>';
	print '<td>';
	print $form->selectyesno('visible', 'yes', 1);
	print '</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("filter").'</td>';
	print '<td>';
	print $form->selectyesno('filter', 'yes', 1);
	print '</td></tr>'."\n";

	// filter init
	print '<tr><td >'.$langs->trans("FilterInit").'</td>';
	print '<td><input type=text size=15 name="filterinit" value=""></td></tr>'."\n";

}
print '</table>';

dol_fiche_end();


/*
 * Actions
 */
print '<div class="tabsAction">';

print '<a class="butAction" href="card.php?rowid='.$mylistid.'">'.$langs->trans('Cancel').'</a>';

// Modify
if (! empty($rowid))
{
	if ($user->rights->mylist->creer )
		print '<input type="submit" class="butAction" name="save" value="'.$langs->trans("Modify").'">';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Modify').'</a>';

	// Delete
	if ($user->rights->mylist->supprimer )
		print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?rowid='.$rowid.'&amp;action=delete&amp;mylistid='.$mylistid.'">'.$langs->trans('Delete').'</a>';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
}
else
{
	if ($user->rights->mylist->creer )
		print '<input type="submit" class="button" value="'.$langs->trans('Save').'">';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Add').'</a>';
}
print '</div>';

print '</form>';

llxFooter();
$db->close();
?>