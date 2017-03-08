<?php
/* Copyright (C) 2013-2015	charlie BENKE		<charlie@patas-monkey.com>
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
 *	\file       htdocs/mylist/class/mylist.class.php
 *	\ingroup    base
 *	\brief      File of class to manage personnalised lists
 */


/**
 *	Class to manage categories
 */
class Mylist extends CommonObject
{
	public $element='mylist';
	public $table_element='mylist';

	// champs des listes
	var $rowid;
	// var $code;
	var $label;
	var $titlemenu;
	var $mainmenu;
	var $leftmenu;
	var $posmenu;
	var $elementtab;
	var $idmenu;
	var $description;
	var $listsUsed=array();			// Tableau des colonnes paramétrés de la liste
	var $OLDlistsUsed=array();		// Tableau des colonnes paramétrés de la liste ancien mode

	var $fieldinit;					// permet de gérer les paramètres supplémentaires
	var $perms;
	var $langs;
	var $author;
	var $active;
	var $export;
	var $model_pdf;
	var $querylist;
	var $querydo;

	// champs des champs de la liste
	var $idfield;		// clé numérique associé au champ
	var $name;			// libelle du champs dans la base
	var $field;			// nom du champs dans la base
	var $alias;
	var $param;		 	// permet de gérer les liste et les clées
	var $type;
	var $pos;
	var	$align;
	var $enabled;
	var	$visible;
	var $filter;
	var $sumreport;
	var $avgreport;
	var $width;			// la taille de la colonne
	var $filterinit;	// une valeur de filtrage par défaut
	var $updatekey;		// pour la mise à jour


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db     Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	function GenFilterFieldsDataTables	($arrayOfFields)
	{
		global $langs, $form, $conf;
		print "Passe dans GenFilterFieldsDataTables (obsolet)<br>";
		return GenFilterFieldsTables ($arrayOfFields);
	}

	function GenFilterInitFieldsTables ()
	{
		global $langs, $form, $conf;
		// datatables mode or not
		$bdatatablesON= (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES));

		$tblInitFields=explode(":",$this->fieldinit);
		foreach ($tblInitFields as $initfields )
		{
			$tblInitField=explode("=",$initfields);
			$fieldinit =$tblInitField[0];
			$valueinit = GETPOST($fieldinit);

			// on prend la valeur par défaut si la valeur n'est pas saisie...
			if (!$valueinit)
				$valueinit = $tblInitField[1];
			if ($bdatatablesON)
			{
				$tmp.= '<div STYLE="float:left;"><table width=100%>';
				$tmp.= '<tr class="liste_titre">';

				$tmp.= '<td>'.$langs->trans($fieldinit). ' : '.'</td>';
				$tmp.= '<td align="left">';
				$tmp.='<input type="text" name='.$tblInitField[0]." value='".$valueinit."'>";
				$tmp.= '</td>';

				$tmp.= '</tr></table></div>';

			}
			else
			{
				$tmp.= '<td>'.$langs->trans($fieldinit). ': '.'</td>';
				$tmp.= '<td align="'.$fields['align'].'">';
				$tmp.='<input type="text" name='.$tblInitField[0]." value='".$valueinit."'>";
				$tmp.= '</td>';
			}
		}
		return $tmp;
	}

	function GenParamFilterInitFields ()
	{
		global $langs, $form, $conf;
		$tblInitFields=explode(":",$this->fieldinit);
		foreach ($tblInitFields as $initfields )
		{
			$tblInitField=explode("=",$initfields);
			$fieldinit =$tblInitField[0];
			$valueinit = GETPOST($fieldinit);

			// on prend la valeur par défaut si la valeur n'est pas saisie...
			if (!$valueinit)
				$valueinit = $tblInitField[1];
			$tmp.='&'.$tblInitField[0]."=".$valueinit;
		}
		return $tmp;
	}

	// gère le format et la taille des champs
	function gen_aoColumns($arrayOfFields, $bckecked)
	{
		$tmp='"aoColumns": [';
		// boucle sur les champs pour en définir le type pour le trie
		foreach ($arrayOfFields as $key => $fields)
		{
			// selon le type de données
			switch($fields['type'])
			{
				case "Number":
				case "Price":
				case "Percent":
					$tmp.= '{ "sType": "numeric-comma" ';
					if ($fields['width'] >0 )
						$tmp.= ', "sWidth": "'.$fields['width'].'"' ;
					else	// longueur par défaut pour le champs date
						$tmp.= ', "sWidth": "100px"' ;
					$tmp.= ' },';
					break;

				case "Date":
					$tmp.= '{ "sType": "date-euro"';
					if ($fields['width'] >0 )
						$tmp.= ', "sWidth": "'.$fields['width'].'"' ;
					else	// longueur par défaut pour le champs date
						$tmp.= ', "sWidth": "80px"' ;
					$tmp.= ' },';
					break;

				default:
					if ($fields['width'] >0 )
						$tmp.= '{ "sWidth": "'.$fields['width'].'"},' ;
					else
						$tmp.= 'null,';
					break;
			}
		}
		// si on peu cocher les lignes on ajoute une colonne
		if ($bckecked)
			$tmp.= 'null,';

		// on vire la derniere virgule et on ajoute le crochet et le saut de lignes
		$tmp= substr($tmp,0,-1).'],'."\n";
		return $tmp;
	}

	function gen_aasorting($sortfield, $sortorder, $arrayOfFields, $bckecked)
	{	// si il y a un trie par défaut
		$posOrderby=strpos(strtoupper($this->querylist), 'ORDER BY');
		$tmp="";
		if ($sortfield ==1 && $posOrderby > 0 )
		{	// un petit espace après l'accolade pour gérer la suppression si rien à trier
			$tmp='"aaSorting":[ ';
			$stringorderby=substr($this->querylist, strpos(strtoupper($this->querylist), 'ORDER BY')+8);
			// on fabrique la ligne de trie par défaut
			if (strpos($stringorderby, ',') > 0)
				$tblorderby = explode(",", $stringorderby);
			else
				$tblorderby[0] = $stringorderby;

			// boucle sur les champs du order by
			foreach ($tblorderby as $orderfield)
			{
				$tblorderbyfield = explode(" ", trim($orderfield));
				$poscol=0;
				// boucle sur les champs de la liste
				foreach ($arrayOfFields as $key => $fields)
				{
					if ($tblorderbyfield[0] == $fields["field"])
						$tmp.= '['.$poscol.",".(strtoupper($tblorderbyfield[1])=="ASC"?"'asc'":"'desc'")."],";

					$poscol++;
				}
				// si le champs à trier n'est pas dans la liste, il est ignoré
			}

			// si on peu cocher les ligne on ajoute une colonne
			if ($bckecked)
				$tmp.= 'null,';
			// on vire la derniere virgule et on ajoute le crochet final
			$tmp= substr($tmp,0,-1)."],\n";
		}
		return $tmp;
	}

	function GenParamFilterFields($arrayOfFields)
	{
		// pour savoir si il s'agit d'une seconde recherche
		$tmp="&filterinit=1";
		// boucle sur les champs filtrables
		foreach ($arrayOfFields as $key => $fields)
			if ($fields['filter']=='1')
				$tmp.= "&".$fields['name']."=".GETPOST($fields['name']);
		return $tmp;
	}

	function GenFilterFieldsTables ($arrayOfFields)
	{
		global $langs,$form,$conf;
		// datatables mode or not
		$bdatatablesON= (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES));

		$tmp="";
		// boucle sur les champs filtrables
		foreach ($arrayOfFields as $key => $fields) {

			if ($fields['filter']=='1') {
				if ($bdatatablesON)
				{
					$tmp.= '<div STYLE="float:left;"><table width=100%>';
					$tmp.= '<tr class="liste_titre"><td>'.$langs->trans($fields['name']). ': '.'</td>';
					$tmp.= '<td>';
				}
				else
					$tmp.= '<td align="'.$fields['align'].'">';
				$namefield=str_replace(array('.', '-'),'_',$fields['field']);

				// récupération du filtrage saisie
				$filtervalue=GETPOST($namefield);
				// gestion du filtrage par défaut (si il y en a un et que l'on est pas au premier appel
				if ($fields['filterinit'] != "" && GETPOST("filterinit") != 1 )
					$filtervalue=$fields['filterinit'];

				$tmp.= $form->textwithpicto(
							$this->build_filterField($fields['type'],
													$namefield,
													$filtervalue,
													$fields['param'])
							, $this->genDocFilter($fields['type']));
				if ($bdatatablesON)
				{
					$tmp.= '</td></tr>';
					$tmp.= '</table></div>';
				}
				else
					$tmp.= '</td>';
			}
			else
				if ($fields['visible']=='1') $tmp.= '<td>&nbsp;</td>';
		}
		return $tmp;
	}

	/**
	 *      Build an input field used to filter the query
	 *
	 *      @param		string	$TypeField		Type of Field to filter
	 *      @param		string	$NameField		Name of the field to filter
	 *      @param		string	$ValueField		Initial value of the field to filter
	 *      @return		string					html string of the input field ex : "<input type=text name=... value=...>"
	 */
	function build_filterField($TypeField, $NameField, $ValueField, $ElementField)
	{
		$szFilterField='';
		$InfoFieldList = explode(":", $ElementField);

		// build the input field on depend of the type of file
		switch ($TypeField)
		{
			case 'Text':
			case 'Date':
			case 'Duree':
			case 'Number':
			case 'Price':
			case 'Percent':
			case 'Sum':
				$szFilterField='<input type="text" name="'.$NameField.'" value="'.$ValueField.'">';
				break;

			case 'Boolean':
				$szFilterField='<select name="'.$NameField.'" class="flat">';
				$szFilterField.='<option ';
				if ($ValueField=='') $szFilterField.=' selected ';
				$szFilterField.=' value="">&nbsp;</option>';

				$szFilterField.='<option ';
				if ($ValueField=='yes') $szFilterField.=' selected ';
				$szFilterField.=' value="yes">'.yn(1).'</option>';

				$szFilterField.='<option ';
				if ($ValueField=='no') $szFilterField.=' selected ';
				$szFilterField.=' value="no">'.yn(0).'</option>';
				$szFilterField.="</select>";
				break;

			case 'List':

				switch(count($InfoFieldList))
				{
					case 5 :
						$sql = 'SELECT  rowid, '.$InfoFieldList[3].' as label';
						$sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[2];
						$sql.= ' WHERE '.$InfoFieldList[4];
						$sql.= ' ORDER BY label';
						break;
					case 4 :		// cas des clés primaires (Class:fichier:table:label)
						// cas de la class action commerciale avec id au lieu de rowid
						if ($InfoFieldList[0]=='Actioncomm')
							$sql = 'SELECT id as rowid , '.$InfoFieldList[3].' as label';
						else
							$sql = 'SELECT rowid, '.$InfoFieldList[3].' as label';
						$sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[2];
						$sql.= ' ORDER BY label';
						break;
					case 3 :
						$sql = 'SELECT '.$InfoFieldList[1].' as rowid, '.$InfoFieldList[2].' as label';
						$sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[0];
						$sql.= ' ORDER BY label';
						break;
					default :
						$sql = 'SELECT rowid, '.$InfoFieldList[1].' as label';
						$sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[0];
						$sql.= ' ORDER BY label';
						break;
				}

				$resql = $this->db->query($sql);
				if ($resql)
				{
					$szFilterField='<select class="flat" name="'.$NameField.'">';
					$szFilterField.='<option value="">&nbsp;</option>';
					$num = $this->db->num_rows($resql);

					$i = 0;
					if ($num)
					{
						while ($i < $num)
						{
							$obj = $this->db->fetch_object($resql);
							if ($obj->label == '-')
							{
								// Discard entry '-'
								$i++;
								continue;
							}
							$labeltoshow=dol_trunc($obj->label,18);
							if (!empty($ValueField) && $ValueField == $obj->rowid)
								$szFilterField.='<option value="'.$obj->rowid.'" selected="selected">'.$labeltoshow.'</option>';
							else
								$szFilterField.='<option value="'.$obj->rowid.'" >'.$labeltoshow.'</option>';
							$i++;
						}
					}
					$szFilterField.="</select>";
					$this->db->free();
				}
				break;

			case 'Statut':
				$tblselectedstatut=explode("#",$InfoFieldList[2]);
				$szFilterField='<select class="flat" name="'.$NameField.'">';
				$szFilterField.='<option value="" ></option>';
				if ($InfoFieldList[1]!="")
					require_once DOL_DOCUMENT_ROOT.$InfoFieldList[1];
				$objectstatic = new $InfoFieldList[0]($db);

				foreach ($tblselectedstatut as $key )
				{
					// pour cette daube d'état 'paye' dans les factures
					if ($key !='P')
					{
						$objectstatic->statut= $key;
						$labeltoshow=$objectstatic->getLibStatut(1);
					}
					else
					{
						$objectstatic->statut= 3;
						$objectstatic->paye= 1;
						$labeltoshow=$objectstatic->getLibStatut(1);
					}

					$labeltoshow=$objectstatic->getLibStatut(1);
					if (!$ValueField && $ValueField == $key)
						$szFilterField.='<option value="'.$key.'" selected="selected">'.$labeltoshow.'</option>';
					else
						$szFilterField.='<option value="'.$key.'" >'.$labeltoshow.'</option>';
				}
				$szFilterField.="</select>";
				break;
		}
		return $szFilterField;
	}

	function GetSqlFilterQuery	($arrayOfFields)
	{
		$tmp="";
		if (is_array($arrayOfFields))
		{
			foreach ($arrayOfFields as $key => $fields)
				if ($fields['filter']=='1')
				{
					$namefield=str_replace(array('.', '-'),'_',$fields['field']);
					$tmp.= $this->build_filterQuery($fields['type'], $fields['field'], GETPOST($namefield), $fields['filterinit'] );
				}
		}
		return $tmp;
	}

	/**
	 *      Build the conditionnal string from filter the query
	 *
	 *      @param		string	$TypeField		Type of Field to filter
	 *      @param		string	$NameField		Name of the field to filter
	 *      @param		string	$ValueField		Initial value of the field to filter
	 *      @return		string					sql string of then field ex : "field='xxx'>"
	 */
	function build_filterQuery($TypeField, $NameField, $ValueField, $DefaultFilterValue)
	{
	//print $TypeField."=".$NameField."=".$ValueField.'/'.$DefaultFilterValue.'<br>';

	if ($ValueField != "" || $DefaultFilterValue != "")
	{
		// récupération du filtrage saisie
		$filtervalue=$ValueField;
		// gestion du filtrage par défaut (si il y en a un et que l'on est pas au premier appel
		if ($DefaultFilterValue !="" && GETPOST("filterinit") != 1 )
			$filtervalue=$DefaultFilterValue;
		// build the input field on depend of the type of file
		switch ($TypeField) {
			case 'Text':
				if (! (strpos($ValueField, '%') == false))
					$szFilterQuery.=" and ".$NameField." LIKE '".$filtervalue."'";
				else
					$szFilterQuery.=" and ".$NameField." LIKE '%".$filtervalue."%'";
				break;
			case 'Date':
				if (strpos($ValueField, "+") > 0)
				{
					// mode plage
					$ValueArray = explode("+", $filtervalue);
					$szFilterQuery =" and (".$this->conditionDate($NameField,$ValueArray[0],">=");
					$szFilterQuery.=" AND ".$this->conditionDate($NameField,$ValueArray[1],"<=").")";
				}
				else
				{
					if (is_numeric(substr($filtervalue,0,1)))
						$szFilterQuery=" and ".$this->conditionDate($NameField,$filtervalue,"=");
					else
						$szFilterQuery=" and ".$this->conditionDate($NameField,substr($filtervalue,1),substr($filtervalue,0,1));
				}
				break;
			case 'Duree':
			case 'Number':
			case 'Price':
			case 'Percent':
			case 'Sum':
				// si le signe -
				if (strpos($filtervalue, "+") > 0)
				{
					// mode plage
					$ValueArray = explode("+", $filtervalue);
					if ($TypeField == "Percent")
					{
						$ValueArray[0] = $ValueArray[0]/100;
						$ValueArray[1] = $ValueArray[1]/100;
					}
					$szFilterQuery =" AND (".$NameField.">=".$ValueArray[0];
					$szFilterQuery.=" AND ".$NameField."<=".$ValueArray[1].")";
				}
				else
				{
					if (is_numeric(substr($filtervalue,0,1)))
						if ($TypeField == "Percent")
							$szFilterQuery=" and ".$NameField."=".($filtervalue/100);
						else
							$szFilterQuery=" and ".$NameField."=".$filtervalue;
					else
						$szFilterQuery=" and ".$NameField.substr($filtervalue,0,1).substr($filtervalue,1);
				}
				break;

			case 'Boolean':
				$szFilterQuery=" and ".$NameField."=".(is_numeric($filtervalue) ? $filtervalue : ($filtervalue =='yes' ? 1: 0) );
				break;
			case 'Statut':
				// pour gérer la merde des statut de facturation
				if ($filtervalue !='P')
					$szFilterQuery=" and ".$NameField."=".$filtervalue;
				else
					$szFilterQuery=" and ".$NameField."=2 and paye=1";
				break;
			case 'List':
				if (is_numeric($filtervalue))
					$szFilterQuery=" and ".$NameField."=".$filtervalue;
				else
					$szFilterQuery=" and ".$NameField."='".$filtervalue."'";
				break;
		}
	}
	return $szFilterQuery;
	}

	function get_infolist($rowid, $ElementField)
	{
		$InfoFieldList = explode(":", $ElementField);
		if (count($InfoFieldList)==3)
			$keyList=$InfoFieldList[2];
		else
			$keyList='rowid';

		$sql = 'SELECT '.$InfoFieldList[1];
		$sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[0];
		$sql.= ' where '.$keyList.' = '. $rowid;
//print $sql;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$labeltoshow=dol_trunc($obj->$InfoFieldList[1],18);
			$this->db->free();
		}
		return $labeltoshow;
	}

	/**
	 *	conditionDate
	 *
	 *  @param 	string	$Field		Field operand 1
	 *  @param 	string	$Value		Value operand 2
	 *  @param 	string	$Sens		Comparison operator
	 *  @return string
	 */
	function conditionDate($Field, $Value, $Sens)
	{
		// FIXME date_format is forbidden, not performant and no portable. Use instead BETWEEN
		if (strlen($Value)==4) $Condition=" date_format(".$Field.",'%Y') ".$Sens." ".$Value;
		elseif (strlen($Value)==6) $Condition=" date_format(".$Field.",'%Y%m') ".$Sens." '".$Value."'";
		else  $Condition=" date_format(".$Field.",'%Y%m%d') ".$Sens." ".$Value;
		return $Condition;
	}

	/**
	 *      Build the fields list for the SQL query
	 *
	 *      @param		array	$arrayOfFields	definition array fields of the list
	 *      @return		string					sql string of fields
	 */
	function GetSqlFields($arrayOfFields)
	{
		if (is_array($arrayOfFields))
		{
			foreach ($arrayOfFields as $key => $fields) {
				$tmp.=$fields['field']." as ";
				if (! empty($fields['alias']))
					$tmp.=$fields['alias'];
				else
					// pour gérer les . des définitions de champs
					$tmp.=str_replace(array('.', '-'),'_',$fields['field']);
				$tmp.=", ";
			}
			// on enlève la dernière virgule et l'espace en fin de ligne
			return substr($tmp,0,-2);
		}

	}

	/**
	 *      Build the group by fields list for the SQL query
	 *
	 *      @param		array	$arrayOfFields	definition array fields of the list
	 *      @return		string					sql string of group by fields
	 */
	function GetGroupBy($arrayOfFields)
	{
		$btopGroupBy = false;
		$tmp=" GROUP BY ";
		// on boucle sur les champs
		if (is_array($arrayOfFields))
		{
			foreach ($arrayOfFields as $key => $fields) {
				if (substr(strtoupper($key), 0, 4) == "SUM(")
					$btopGroupBy = true;
				elseif (substr(strtoupper($key), 0, 6) == "COUNT(")
					$btopGroupBy = true;
				else
					$tmp.=$fields['field'].", ";
			}
		}
		// on enlève la dernière virgule et l'espace en fin de ligne
		if ($btopGroupBy)
			return substr($tmp,0,-2);
		else
			return "";
	}

	function genDefaultTD($keyName, $Arrayfields, $objvalue)
	{
		global $langs, $conf;

		$tmp= "<td align=".$Arrayfields['align'].">";
		// pour gérer l'aliassing des champs
		if (!empty($Arrayfields['alias']))
			$codFields=$Arrayfields['alias'];
		else
			$codFields=str_replace(array('.', '-'),"_",$Arrayfields['field']);

		// selon le type de données
		switch($Arrayfields['type'])
		{
			case "Price":
				$tmp.= price($objvalue->$codFields);
				if ($conf->global->MYLIST_DISPLAY_CURRENCY_PRICE)
					$tmp.= " ".$langs->trans("Currency" . $conf->currency);
				break;

			case "Number":
				$tmp.= price($objvalue->$codFields);
				break;

			case "Percent":
				$tmp.= price($objvalue->$codFields * 100 )." %";
				break;

			case "Date":
				$tmp.= dol_print_date($this->db->jdate($objvalue->$codFields),'day');
				break;

			case "Boolean":
				$tmp.= yn($objvalue->$codFields);
				break;

			default:
				$tmp.= $objvalue->$codFields;
				break;
		}
		$tmp.= '</td>';
		return $tmp;
	}

	function genHideFields($Arrayfields)
	{
		//boucle sur les champs à afficher
		$tmp="<script>"."\n"."jQuery(document).ready(function() {"."\n";

		$i=0;
		foreach ($Arrayfields as $key => $fields)
		{
			// si le champs n'est pas visible on le cache
			if ($fields['visible']=='0')
				$tmp.= 'jQuery("#listtable").dataTable().fnSetColumnVis('. $i.', false );'."\n";
			$i++;
		}
		$tmp.= "});"."\n"."</script>"."\n";
		return $tmp;
	}

	/**
	 *      Build an input field used to filter the query
	 *
	 *      @param		string	$TypeField		Type of Field to filter
	 *      @return		string					html string of the input field ex : "<input type=text name=... value=...>"
	 *      TODO replace by translation
	 */
	function genDocFilter($TypeField)
	{
		$szMsg='';
		$InfoFieldList = explode(":", $TypeField);
		// build the input field on depend of the type of file
		switch ($InfoFieldList[0]) {
			case 'Text':
				$szMsg="% permet de remplacer un ou plusieurs caract&egrave;res dans la chaine";
				break;
			case 'Date':
				$szMsg ="'AAAA' 'AAAAMM' 'AAAAMMJJ' : filtre sur une ann&eacute;e/mois/jour <br>";
				$szMsg.="'AAAA+AAAA' 'AAAAMM+AAAAMM' 'AAAAMMJJ+AAAAMMJJ': filtre sur une plage d'ann&eacute;e/mois/jour <br>";
				$szMsg.="'&gt;AAAA' '&gt;AAAAMM' '&gt;AAAAMMJJ' filtre sur les ann&eacute;e/mois/jour suivants <br>";
				$szMsg.="'&lsaquo;AAAA' '&lsaquo;AAAAMM' '&lsaquo;AAAAMMJJ' filtre sur les ann&eacute;e/mois/jour pr&eacute;c&eacute;dent <br>";
				break;
			case 'Duree':
				break;
			case 'Number':
				$szMsg ="'NNNNN' filtre sur une valeur <br>";
				$szMsg.="'NNNNN+NNNNN' filtre sur une plage de valeur<br>";
				$szMsg.="'&lsaquo;NNNNN' filtre sur les valeurs inf&eacute;rieurs<br>";
				$szMsg.="'&gt;NNNNN' filtre sur les valeurs sup&eacute;rieurs<br>";
				break;

			case 'Boolean':
				break;
			case 'List':
				break;
		}
		return $szMsg;
	}

	/**
	 * 	Load Listables into memory from database
	 *
	 * 	@param		int		$code		code of listable
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		global $conf;

		$sql = "SELECT rowid, label, fieldinit, fieldused, mainmenu, leftmenu, elementtab, perms,";
		$sql.= " querylist, querydo, titlemenu, langs, author, export, model_pdf, active";
		$sql.= " FROM ".MAIN_DB_PREFIX."mylist";
		$sql.= " WHERE rowid = ".$rowid;

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);

				$this->rowid		= $res['rowid'];
				$this->label		= $res['label'];
				$this->mainmenu		= $res['mainmenu'];
				$this->leftmenu		= $res['leftmenu'];
				$this->titlemenu	= $res['titlemenu'];
				$this->elementtab	= $res['elementtab'];
				$this->perms		= $res['perms'];
				$this->langs		= $res['langs'];
				$this->author		= $res['author'];
				$this->active		= $res['active'];
				$this->querylist	= $res['querylist'];
				$this->querydo		= $res['querydo'];
				$this->fieldinit	= $res['fieldinit'];
				$this->export		= $res['export'];
				$this->model_pdf	= $res['model_pdf'];
				$this->OLDlistsUsed	= json_decode($res['fieldused'],true);
				$this->db->free($resql);

				// pour gérer les anciennes versions
				$this->fillmylistdet();
				return 1;
			}
			else
				return 0;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/*  Get the right position menu value for new */
	function getposmenu($titlemenu, $mainmenu, $leftmenu)
	{
		// gestion de la position du menu
		$sql="SELECT max(position) as posmenu FROM ".MAIN_DB_PREFIX."menu";
		$sql.=" WHERE fk_mainmenu ='".trim($mainmenu)."'";
		$sql.=" AND fk_leftmenu ='".trim($leftmenu)."'";
		$sql.=" AND titre <> '".trim($titlemenu)."'";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);
				// on rajoute 1 à la derniere liste présente
				if ($res['posmenu'] >= 100)
					return $res['posmenu']+1;
			}
		}
		// on renvoie la valeur par défaut dans tous les autres cas
		return 100;
	}

	/**
	 * 	Add mylist into database
	 *
	 * 	@param	User	$user		Object user
	 * 	@return	int 				-1 : erreur SQL

	 */
	function create($user='')
	{
		global $conf, $langs, $user;
		$langs->load('mylist@mylist');

		$error=0;

		$this->code = trim($this->code);
		$this->label=(!is_array($this->label)?trim($this->label):'');
		$this->perms=(!is_array($this->perms)?trim($this->perms):'');
		$this->langs=(!is_array($this->langs)?trim($this->langs):'');
		$this->titlemenu = trim($this->titlemenu);
		$this->mainmenu = trim($this->mainmenu);
		$this->leftmenu = trim($this->leftmenu);
		$this->elementtab = (!is_array($this->elementtab)?trim($this->elementtab):'');
		$this->author=(!is_array($this->author)?trim($this->author):'');
		$this->fieldinit=(!is_array($this->fieldinit)?trim($this->fieldinit):'');
		$this->querydo=(!is_array($this->querydo)?trim($this->querydo):'');

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mylist (";
		$sql.= " label,";
		$sql.= " titlemenu,";
		$sql.= " perms,";
		$sql.= " langs,";
		$sql.= " mainmenu,";
		$sql.= " leftmenu,";
		$sql.= " elementtab,";
		$sql.= " author,";
		$sql.= " active,";
		$sql.= " querylist,";
		$sql.= " querydo,";
		$sql.= " fieldinit";
		$sql.= ") VALUES (";
		$sql.= " '".$this->db->escape($this->label)."'";
		$sql.= ", '".$this->db->escape($this->titlemenu)."'";
		$sql.= ", '".$this->db->escape($this->perms)."'";
		$sql.= ", '".$this->db->escape($this->langs)."'";
		$sql.= ", '".$this->db->escape($this->mainmenu)."'";
		$sql.= ", '".$this->db->escape($this->leftmenu)."'";
		$sql.= ", '".$this->db->escape($this->elementtab)."'";
		$sql.= ", '".$this->db->escape($this->author)."'";
		$sql.= ", 0";  // by default the new list is not active
		$sql.= ", '".$this->db->escape($this->querylist)."'";
		$sql.= ", '".$this->db->escape($this->querydo)."'";
		$sql.= ", '".$this->db->escape($this->fieldinit)."'";
		$sql.= ")";
//print $sql;
		dol_syslog(get_class($this).'::create sql='.$sql);
		if ($this->db->query($sql))
		{
			$rowid=$this->db->last_insert_id(MAIN_DB_PREFIX."mylist");
			$this->db->commit();
			$this->rowid = $rowid;

			return $rowid;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::create error ".$this->error." sql=".$sql, LOG_ERR);
			$this->db->rollback();
			return 0;
		}
	}

	/**
	 * 	Delete fields
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *          					-1 : SQL error
	 *          					-2 : invalid fields
	 */
	function deleteField($user='',$keychange)
	{

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."mylistdet";
		$sql.= " where rowid=".$keychange;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->fillmylistdet();
			return 1;
		}
		else
			return -1;
	}

	/**
	 * 	Update fields
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *          					-1 : SQL error
	 *          					-2 : invalid fields
	 */
	function updateField($user='',$keychange)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."mylistdet";
		$sql.= " SET name= '".$this->name."'";
		$sql.= " , fieldname= '".$this->field."'";
		$sql.= " , alias = '".$this->alias."'";
		$sql.= " , type = '".$this->type."'";
		$sql.= " , param = '".$this->param."'";
		$sql.= " , filterinit = '".$this->filterinit."'";
		$sql.= " , enabled = ".$this->db->escape($this->enabled);
		$sql.= " , visible = ".$this->db->escape($this->visible);
		$sql.= " , filter = ".$this->db->escape($this->filter);
		$sql.= " , sumreport = ".$this->db->escape($this->sumreport);
		$sql.= " , avgreport = ".$this->db->escape($this->avgreport);

		$sql.= " where rowid=".$keychange;

		$resql = $this->db->query($sql);
		return $resql;
	}

	/**
	 * 	Add fields
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *          					-1 : SQL error
	 *          					-2 : invalid fields
	 */
	function addField($user='', $idfield)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mylistdet";
		$sql.= "(fk_mylist, fieldname, name, alias, type, pos, param,";
		$sql.= " align, enabled, visible, filter, width,";
		$sql.= " filterinit, sumreport, avgreport) values (";
		$sql.= $idfield.",";
		$sql.= "'".$this->field."',";
		$sql.= "'".$this->name."',";
		$sql.= "'".$this->alias."',";
		$sql.= "'".$this->type."',";
		$sql.= " ".$this->pos.",";
		$sql.= "'".$this->param."',";
		$sql.= "'".$this->align."',";
		$sql.= " ".$this->enabled.",";
		$sql.= " ".$this->visible.",";
		$sql.= " ".$this->filter.",";
		$sql.= " ".($this->width ? $this->width : 0).",";
		$sql.= "'".$this->filterinit."',";
		$sql.= "'".$this->sumreport."',";
		$sql.= "'".$this->avgreport."')";
		$resql = $this->db->query($sql);
print $sql."<br>";
		if ($resql)
		{
			$rowid=$this->db->last_insert_id(MAIN_DB_PREFIX."mylistdet");
			return $rowid;
		}
		else
			return -1;
	}

	function getlastpos($mylistid)
	{
		// gestion de la position du menu
		$sql="SELECT max(pos) as lastpos FROM ".MAIN_DB_PREFIX."mylistdet";
		$sql.=" WHERE fk_mylist =".$mylistid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);
				// on rajoute 1 à la derniere liste présente
					return $res['posmenu']+1;
			}
		}
		// on renvoie la valeur par défaut dans tous les autres cas
		return 1;
	}


	/**
	 * 	Update create mylistdet fields (for import only
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *          					-1 : SQL error
	 *          					-2 : invalid category
 	 */
	function AOTupdateFieldList($user='')
	{
		// plus d'actualité
		global $conf, $langs;

		$fieldsused=json_encode($this->listsUsed);
		// pour gérer la '' des champs calculés avant la mise à jour
		$fieldsused=str_replace("'", "''", $fieldsused);

		//print $this->listsUsed.'<br>';
		//print json_encode($this->listsUsed).'<br>';
		$sql = "UPDATE ".MAIN_DB_PREFIX."mylist";
		$sql .= " SET fieldused ='".$fieldsused."'";
		$sql .= " WHERE code = '".$this->code."'";

		dol_syslog(get_class($this)."::update sql=".$sql);
		if ($this->db->query($sql))
		{
			return 1;
		}
		else
			return $sql;
	}

	/**
	 * 	Update mylist, and create menu if activate
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *          					-1 : SQL error
	 *          					-2 : invalid category
	 */
	function update($user='')
	{
		global $conf, $langs;
		$this->db->begin();

		$error=0;
		$positionsave=0;

		// on commence par récupérer l'id du menu à supprimer
		$sql="select m.rowid from ".MAIN_DB_PREFIX."menu as m, ".MAIN_DB_PREFIX."mylist as l";
		$sql .= " WHERE code = '".$this->code."'";
		$sql .= " and l.titlemenu=m.titre";
		$sql .= " and m.module='mylist'";
		$sql .= " and l.mainmenu=m.fk_mainmenu";
		$sql .= " and l.leftmenu=m.fk_leftmenu";

		dol_syslog(get_class($this)."::update sql=".$sql);
		if ($this->db->query($sql))
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);
				$sql="delete from ".MAIN_DB_PREFIX."menu where rowid=".$res['rowid'];
				$this->db->query($sql);
			}
		}
		$this->posmenu=$this->getposmenu($this->titlemenu, $this->mainmenu, $this->leftmenu);

		// on supprime l'onglet si il est present ou pas
		$sql="DELETE FROM ".MAIN_DB_PREFIX."const where name =".$this->db->encrypt('MAIN_MODULE_MYLIST_TABS_'.$this->code,1);
		$this->db->query($sql);

		// si il y a un onglet on fait de meme
		$sql = "UPDATE ".MAIN_DB_PREFIX."mylist";
		$sql .= " SET label = '".$this->db->escape($this->label)."'";
		$sql .= ", perms ='".$this->db->escape($this->perms)."'";
		$sql .= ", langs ='".$this->db->escape($this->langs)."'";
		$sql .= ", titlemenu ='".$this->db->escape($this->titlemenu)."'";
		$sql .= ", mainmenu ='".$this->db->escape($this->mainmenu)."'";
		$sql .= ", leftmenu ='".$this->db->escape($this->leftmenu)."'";
		$sql .= ", posmenu =".$this->posmenu;
		$sql .= ", elementtab ='".$this->db->escape($this->elementtab)."'";
		$sql .= ", querylist ='".$this->db->escape($this->querylist)."'";
		$sql .= ", querydo ='".$this->db->escape($this->querydo)."'";
		$sql .= ", fieldinit ='".$this->db->escape($this->fieldinit)."'";
		$sql .= ", author ='".$this->db->escape($this->author)."'";
		$sql .= ", active =".$this->db->escape($this->active);
		$sql .= ", export =".($this->export?$this->db->escape($this->export):0);
		$sql .= ", model_pdf ='".$this->db->escape($this->model_pdf)."'";
		$sql .= " WHERE rowid =".$this->rowid;

		dol_syslog(get_class($this)."::update sql=".$sql);

		if ($this->db->query($sql))
		{
			// si la liste est active
			if ($this->active)
			{
				// on met à jour la table des menus
				// on ajoute le menu
				require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';
				$menu = new Menubase($this->db);
				$menu->menu_handler='all';
				$menu->module='mylist';
				$menu->type='left';
				$menu->fk_menu=$this->getidmenu();
				$menu->fk_mainmenu=$this->mainmenu;
				$menu->fk_leftmenu=$this->leftmenu;
				$menu->titre=$this->titlemenu;
				$menu->url='/mylist/mylist.php?rowid='.$this->rowid;
				$menu->langs=$this->langs;
				$menu->position=$this->posmenu;
				$menu->perms=$this->perms;
				$menu->target="";
				$menu->user=2;
				$menu->enabled=1;
				$result=$menu->create($user);

				// on crée l'onglet
				if ($this->elementtab)
				{
					switch($this->elementtab) {
						case 'Societe' :
							$tabinfo='thirdparty';
							break;
						case 'Product' :
							$tabinfo='product';
							break;
						case 'CategProduct' :
							$tabinfo='categories_0';
							break;
						case 'CategSociete' :
							$tabinfo='categories_2';
							break;
						case 'Affaire' :
							$tabinfo='lead';
							break;
					}
					$tabinfo.=':+mylist_'.$this->rowid.':'.$this->titlemenu.':@mylist:/mylist/mylist.php?rowid='.$this->rowid.'&id=__ID__';

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."const ";
					$sql.= " ( name, type, value, note, visible, entity)";
					$sql.= " VALUES (";
					$sql.= $this->db->encrypt('MAIN_MODULE_MYLIST_TABS_'.$this->code,1);
					$sql.= ", 'chaine'";
					$sql.= ", ".$this->db->encrypt($tabinfo,1);
					$sql.= ", null";
					$sql.= ", '0'";
					$sql.= ", ".$conf->entity;
					$sql.= ")";

					dol_syslog(get_class($this)."::update insert_const_tabs sql=".$sql);
					$resql=$this->db->query($sql);
				}
			}
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Return the last id of menu
	 *
	 *	@return	id of  menu , -1 if normal
	 */
	function getidmenu()
	{
		$sql="select min(m.rowid) as minmenu from ".MAIN_DB_PREFIX."menu as m";
		$sql .= " WHERE m.mainmenu='".$this->mainmenu."'";
		$sql .= " AND m.fk_menu != -1";

//print $sql;
		dol_syslog(get_class($this)."::getidmenu sql=".$sql);
		if ($this->db->query($sql))
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);
				if ($res['minmenu'] != 0)
					return($res['minmenu']);
			}
		}
		return -1;
	}


	/**
	 * 	Delete a list from database
	 *
	 * 	@param	User	$user		Object user that ask to delete
	 *	@return	void
	 */
	function delete($user)
	{
		global $conf, $langs;

		$error=0;

		dol_syslog(get_class($this)."::delete");

		// on vire le menu si il existe, normalement pas nécessaire (liste désactivé) mais on sait jamais
		// on commence par récupérer l'id du menu à supprimer
		$sql="select m.rowid from ".MAIN_DB_PREFIX."menu as m, ".MAIN_DB_PREFIX."mylist as l";
		$sql .= " WHERE code = '".$this->code."'";
		$sql .= " and l.titlemenu=m.titre";
		$sql .= " and m.module='mylist'";
		$sql .= " and l.mainmenu=m.fk_mainmenu";
		$sql .= " and l.leftmenu=m.fk_leftmenu";

		dol_syslog(get_class($this)."::delete sql=".$sql);
		if ($this->db->query($sql))
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);
				$sql="delete from ".MAIN_DB_PREFIX."menu where rowid=".$res['rowid'];
				$this->db->query($sql);
			}
		}

		// on vire ensuite le parametrage
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."mylistdet";
		$sql .= " WHERE fk_mylist = ".$this->rowid;
		if (!$this->db->query($sql))
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
			$error++;
		}

		// on vire ensuite le parametrage
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."mylist";
		$sql .= " WHERE rowid = ".$this->rowid;
		if (!$this->db->query($sql))
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
			$error++;
		}

	}

	/**
	 * 	Retourne toutes les listes
	 *
	 *	@return	array					Tableau d'objet list
	 */
	function get_all_mylist()
	{
		$sql = "SELECT rowid, label, perms, langs, fieldinit, fieldused, titlemenu, mainmenu, leftmenu, author, active FROM ".MAIN_DB_PREFIX."mylist";

		$res = $this->db->query($sql);
		if ($res)
		{
			$cats = array ();
			while ($rec = $this->db->fetch_array($res))
			{
				$cat = array ();
				//$cat['code']		= $rec['code'];
				$cat['rowid']		= $rec['rowid'];
				$cat['label']		= $rec['label'];
				$cat['titlemenu']	= $rec['titlemenu'];
				$cat['mainmenu']	= $rec['mainmenu'];
				$cat['leftmenu']	= $rec['leftmenu'];
				$cat['elementtab']	= $rec['elementtab'];
				$cat['perms']		= $rec['perms'];
				$cat['langs']		= $rec['langs'];
				$cat['export']		= $rec['export'];
				$cat['author']		= $rec['author'];
				$cat['active']		= $rec['active'];
				// analyse du paramétrage
				$cat['nbFieldsUsable']	= $this->nbFieldsUsable($rec['rowid']);
				$cat['nbFieldsShow']	= $this->nbFieldsShow($rec['rowid']);
				$cat['nbFilters']		= $this->nbFilters($rec['rowid']);
				$cats[$rec['rowid']] = $cat;
			}
			return $cats;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	function nbFieldsUsable($rowid)
	{
		$this->getChampsArray($rowid);
		return count($this->listsUsed);
	}

	function nbFieldsShow($rowid)
	{
		$nbFields=0;
		$this->getChampsArray($rowid);
		if(is_array($this->listsUsed))
			foreach ($this->listsUsed as $key )
			{
				if ($key['visible']=="1") $nbFields++;
			}
		return $nbFields;
	}

	function nbFilters($rowid)
	{
		$nbFields=0;
		$this->getChampsArray($rowid);
		if(is_array($this->listsUsed))
			foreach ($this->listsUsed as $key )
			{
				if ($key['filter']=="1") $nbFields++;
			}
		return $nbFields;
	}

		/**
	 * Return list fields of a mylist
	 *
	 * @return 	array				Array of fieldS
	 */
	function getChampsArray($rowid=0)
	{
		global $user;
// on récupère les champs de la liste dans un Tableau
		$sql ="select * FROM ".MAIN_DB_PREFIX."mylistdet ";
		if ($rowid > 0)
			$sql.= " WHERE fk_mylist=".$rowid;
		else
			$sql.= " WHERE fk_mylist=".$this->rowid;
		$sql.= " ORDER BY pos";

		dol_syslog(get_class($this)."::getChampsArray sql=".$sql);
//print $sql;
		$result=$this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($resql);
			$newArrays=array();

			$i = 1;
			while ($i < $num+1)
			{
				$objp = $this->db->fetch_object($result);

				$newArray=array();
				$newArray['rowid']		= $objp->rowid;
				$newArray['name']		= $objp->name;
				$newArray['alias']		= $objp->alias;
				$newArray['field']		= $objp->fieldname;
				$newArray['type']		= $objp->type;
				$newArray['pos']		= $objp->pos;
				$newArray['param']		= $objp->param;
				$newArray['align']		= $objp->align;
				$newArray['enabled']	= $objp->enabled;
				$newArray['visible']	= $objp->visible;
				$newArray['filter']		= $objp->filter;
				$newArray['width']		= $objp->width;
				$newArray['sumreport']	= $objp->sumreport;
				$newArray['avgreport']	= $objp->avgreport;
				$newArray['filterinit']	= $objp->filterinit;
				// on rajoute à la liste
				$newArrays[$i] = $newArray;

				$i++;
			}
			$this->listsUsed = $newArrays;
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return 0;
		}
	}

		/**
	 * Return list fields of a mylist
	 *
	 * @return 	array				Array of fieldS
	 */
	function FetchChampArray($rowid)
	{
// on récupère les champs de la liste dans un Tableau
		$sql ="select * FROM ".MAIN_DB_PREFIX."mylistdet ";
		$sql.= " WHERE rowid=".$rowid;

		dol_syslog(get_class($this)."::FetchChampArray sql=".$sql);

		$result=$this->db->query($sql);
		if ($result)
		{

			$objp = $this->db->fetch_object($result);

			$newArray=array();
			$newArray['rowid']		= $objp->rowid;
			$newArray['name']		= $objp->name;
			$newArray['alias']		= $objp->alias;
			$newArray['field']		= $objp->fieldname;
			$newArray['type']		= $objp->type;
			$newArray['pos']		= $objp->pos;
			$newArray['param']		= $objp->param;
			$newArray['align']		= $objp->align;
			$newArray['enabled']	= $objp->enabled;
			$newArray['visible']	= $objp->visible;
			$newArray['filter']		= $objp->filter;
			$newArray['width']		= $objp->width;
			$newArray['sumreport']	= $objp->sumreport;
			$newArray['avgreport']	= $objp->avgreport;
			$newArray['filterinit']	= $objp->filterinit;

			return $newArray;
		}
		else
		{
			dol_print_error($this->db);
			return array();
		}
	}


	function getexporttable($rowid)
	{
		$this->fetch($rowid);
		$tmp.="<?xml version='1.0' encoding='ISO-8859-1'?><mylist>\n";
		$tmp.="<label>".$this->label."</label>\n";
		$tmp.="<titlemenu>".$this->titlemenu."</titlemenu>\n";
		$tmp.="<mainmenu>".$this->mainmenu."</mainmenu>\n";
		$tmp.="<leftmenu>".$this->leftmenu."</leftmenu>\n";
		$tmp.="<elementtab>".$this->elementtab."</elementtab>\n";
		$tmp.="<perms>".$this->perms."</perms>\n";
		$tmp.="<langs>".$this->langs."</langs>\n";
		$tmp.="<export>".$this->export."</export>\n";
		$tmp.="<model_pdf>".$this->model_pdf."</model_pdf>\n";
		$tmp.="<author>".$this->author."</author>\n";
		$tmp.="<querylist>"."\n".htmlentities($this->querylist)."\n"."</querylist>"."\n";
		$tmp.="<fieldinit>"."\n".htmlentities($this->fieldinit)."\n"."</fieldinit>"."\n";
		$tmp.="<querydo>"."\n".htmlentities($this->querydo)."\n"."</querydo>"."\n";
		$tmp.="<fields>\n";
		$this->getChampsArray($rowid);
		foreach ($this->listsUsed as $key=> $value )
		{
			$tmp.="\t".'<field >'."\n";
			//$tmp.="\t \t<key>".$key."</key>\n";
			$tmp.="\t \t<name>".$value['name']."</name>\n";
			$tmp.="\t \t<field>".$value['field']."</field>\n";
			$tmp.="\t \t<alias>".$value['alias']."</alias>\n";
			$tmp.="\t \t<type>".$value['type']."</type>\n";
			$tmp.="\t \t<pos>".$value['pos']."</pos>\n";
			$tmp.="\t \t<param>".$value['param']."</param>\n";
			$tmp.="\t \t<align>".$value['align']."</align>\n";
			$tmp.="\t \t<enabled>".$value['enabled']."</enabled>\n";

			$tmp.="\t \t<sumreport>".$value['sumreport']."</sumreport>\n";
			$tmp.="\t \t<avgreport>".$value['avgreport']."</avgreport>\n";

			$tmp.="\t \t<visible>".$value['visible']."</visible>\n";
			$tmp.="\t \t<filter>".$value['filter']."</filter>\n";
			$tmp.="\t \t<width>".$value['width']."</width>\n";
			$tmp.="\t \t<filterinit>".$value['filterinit']."</filterinit>\n";

			$tmp.="\t</field>\n";
		}
		$tmp.="</fields>\n";
		$tmp.="</mylist>\n";
		return $tmp;
	}

	function importlist($xml)
	{
		global $user;
		// on récupère le fichier et on le parse
		libxml_use_internal_errors(true);
		$sxe = simplexml_load_string($xml);
		if ($sxe === false) {
			echo "Erreur lors du chargement du XML\n";
			foreach(libxml_get_errors() as $error) {
				echo "\t", $error->message;
			}
		}
		else
			$arraydata = json_decode(json_encode($sxe), true);

		$this->label=		$arraydata['label'];
		$this->titlemenu=	$arraydata['titlemenu'];
		$this->mainmenu=	$arraydata['mainmenu'];
		$this->leftmenu=	$arraydata['leftmenu'];
		$this->elementtab=	$arraydata['elementtab'];
		$this->perms=		$arraydata['perms'];
		$this->langs=		$arraydata['langs'];
		$this->author=		$arraydata['author'];
		$this->querylist=	$arraydata['querylist'];
		$this->querydo=		$arraydata['querydo'];
		$this->fieldinit=	$arraydata['fieldinit'];
		$this->export= 		(!is_array($arraydata['export'])?is_array($arraydata['export']):0);
		$this->model_pdf=	$arraydata['model_pdf'];

		// on supprime dans mylist
		// Si on part d'une ancienne liste
		if ($this->rowid)
			$this->delete($user);

		// on crée une nouvelle liste
		$fk_mylist = $this->create($user);


		$tblfields=$arraydata['fields']['field'];
		$newArrays=array();
		$nbfields=1;
		foreach($tblfields as $fields)
		{
			$this->name =		$fields['name'];
			$this->field =		$fields['field'];
			$this->alias =		(!is_array($fields['alias'])? $fields['alias']:'');
			$this->type =		$fields['type'];
			$this->param =		($fields['param']? $fields['param']:($fields['elementfield'] ? $fields['elementfield']:''));
			$this->align =		$fields['align'];
			$this->pos =		($fields['pos']? $fields['pos']:$fields['key']);
			if ($fields['enabled']==1 || $fields['enabled']='true')
				$this->enabled = 1;
			else
				$this->enabled = 0;

			$this->sumreport =	$fields['sumreport'];
			$this->avgreport =	$fields['avgreport'];
			if ($fields['visible'] ==1 || $fields['visible']='true')
				$this->visible = 1;
			else
				$this->visible = 0;
			if ($fields['filter'] ==1 || $fields['filter']='true')
				$this->filter = 1;
			else
				$this->filter = 0;
			$this->width =		(!is_array($fields['width'])? $fields['width']:'');
			$this->filterinit =	(!is_array($fields['filterinit'])? $fields['filterinit']:'');

			$this->addField($user, $fk_mylist);

		}
		$this->rowid = $fk_mylist ;
		$this->fillmylistdet();

	}

	function getSelectTypeFields($selected )
	{
		global $langs;

		;
		$tmp="<select name=type>";
		$tmp.="<option value='Text' ".($selected=="Text"?" selected ":"").">".$langs->trans("Text")."</option>";
		$tmp.="<option value='Number' ".($selected=="Number"?" selected ":"").">".$langs->trans("Number")."</option>";
		$tmp.="<option value='Price' ".($selected=="Price"?" selected ":"").">".$langs->trans("Price")."</option>";
		$tmp.="<option value='Percent' ".($selected=="Percent"?" selected ":"").">".$langs->trans("Percent")."</option>";
		$tmp.="<option value='Date' ".($selected=="Date"?" selected ":"").">".$langs->trans("Date")."</option>";
		$tmp.="<option value='Boolean' ".($selected=="Boolean"?" selected ":"").">".$langs->trans("Boolean")."</option>";
		$tmp.="<option value='Statut' ".($selected=="Statut"?" selected ":"").">".$langs->trans("Statut")."</option>";
		$tmp.="<option value='List' ".($selected=="List"?" selected ":"").">".$langs->trans("List")."</option>";
		$tmp.="<option value='Check' ".($selected=="Check"?" selected ":"").">".$langs->trans("Checkable")."</option>";
		$tmp.="</select>";
		return $tmp;
	}

	function getSelectelementTab($selected)
	{
		global $langs;

		$tmp="<select name=elementtab>";
		$tmp.="<option value='' >".$langs->trans("NotInTab")."</option>";
		$tmp.="<option value='Societe' ".($selected=="Societe"?" selected ":"").">".$langs->trans("Societe")."</option>";
		$tmp.="<option value='Product' ".($selected=="Product"?" selected ":"").">".$langs->trans("Product")."</option>";
		$tmp.="<option value='CategProduct' ".($selected=="CategProduct"?" selected ":"").">".$langs->trans("CategProduct")."</option>";
		$tmp.="<option value='CategSociete' ".($selected=="CategSociete"?" selected ":"").">".$langs->trans("CategSociete")."</option>";
                $tmp.="<option value='Affaire' ".($selected=="Affaire"?" selected ":"").">".$langs->trans("Lead")."</option>";
		$tmp.="</select>";
		return $tmp;
	}

	/* permet de convertir l'ancien paramétrage des champs vers la table des champs */
	function fillmylistdet()
	{
		// pour l'ancienne compatibilité
		if(is_array($this->OLDlistsUsed))
		{
			// on insère en base
			foreach ($this->OLDlistsUsed as $key=> $value )
			{
				//var_dump($value);
				$sql="insert into ".MAIN_DB_PREFIX."mylistdet ";
				$sql.="( fk_mylist, pos, fieldname, name, alias, type, param, align,";
				$sql.="  enabled, visible, filter, width, filterinit";
				$sql.=") values ";
				$sql.="( ". $this->rowid;
				$sql.=", ".( $key ? $key : 0); // lors de la reprise la position c'est la key
				//$sql.=", '".$this->db->escape( $value['field'])."'";
				$sql.=", '".$value['field']."'";
				$sql.=", '".$this->db->escape($value['name'])."'";
				$sql.=", '".$this->db->escape($value['alias'])."'";
				$sql.=", '".$this->db->escape($value['type'])."'";
				$sql.=", '".$this->db->escape(($value['elementfield']?$value['elementfield']:$value['param']))."'"; // pour la compatibilité
				$sql.=", '".$this->db->escape($value['align'])."'";
				$sql.=", ". $value['enabled'];
				$sql.=", ". $value['visible'];
				$sql.=", ". $value['filter'];
				$sql.=", ".($value['width']?$value['width']:0);
				$sql.=", '".$this->db->escape($value['filterinit'])."')";
//print $sql."<br>";
				$resql = $this->db->query($sql);
			}
			// on purge la variable

			// on vérifie que tout est ok avant de purger
			$sql ="select * FROM ".MAIN_DB_PREFIX."mylistdet ";
			$sql.= " WHERE fk_mylist=".$this->rowid;
			$sql.= " ORDER BY pos";

			dol_syslog(get_class($this)."::fillmylistdet sql=".$sql);

			$result=$this->db->query($sql);
			if ($result)
			{
				if ($this->db->num_rows($resql) == count($this->OLDlistsUsed))
				{
					$this->OLDlistsUsed="";
					// on purge du champs de la table
					$sql = "UPDATE ".MAIN_DB_PREFIX."mylist";
					$sql.= " SET fieldused=''";
					$sql.= " where rowid=".$this->rowid;
					$resql = $this->db->query($sql);
				}
			}
		}
		$this->getChampsArray($this->rowid);
		return 0;
	}
}

// TODO sortir les fonction lié au champs de la classe principale
class Mylistdet extends CommonObject
{

	public $element='mylistdet';
	public $table_element='mylistdet';

	// champs des champs de la liste
	var $idfield;		// clé numérique associé au champ
	var $name;			// libelle du champs dans la base
	var $field;			// nom du champs dans la base
	var $alias;
	//var $elementfield; 	// permet de gérer les liste et les clées
	var $param;		 	// permet de gérer les liste et les clées
	var $type;
	var $pos;
	var	$align;
	var $enabled;
	var	$visible;
	var $filter;
	var $sumreport;
	var $avgreport;
	var $width;			// la taille de la colonne
	var $filterinit;	// une valeur de filtrage par défaut
	var $updatekey;		// pour la mise à jour
}