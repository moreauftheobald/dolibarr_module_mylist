<?php
/* Copyright (C) 2013-2015	Charlie benke	<charlie@patas-monkey.com>
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
 *  \defgroup   projet     Module Mylist
 *	\brief      Module to Manage Dolibarr Lists 
 *  \file       htdocs/core/modules/modMylist.class.php
 *	\ingroup    projet
 *	\brief      Fichier de description et activation du module Mylist
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 *	Classe de description et activation du module Projet
 */
class modMylist extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf, $langs;
		
		$langs->load('mylist@mylist');
		
		$this->db = $db;
		$this->numero = 160210;

		$this->family = "technic";
		
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = 'myList';
		$this->description = $langs->trans("InfoModulesMyList");
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '3.8.+2.0.1';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		//$this->config_page_url = array("mylist.php@mylist");
		$this->picto='mylist.png@mylist';

		$this->module_parts = array(
			'css' => '/mylist/css/patastools.css',       // Set this to relative path of css if module has its own css file
		);

		// Data directories to create when module is enabled
		$this->dirs = array("/mylist/temp");

		// Config pages. Put here list of php page, stored into webmail/admin directory, to use to setup module.
		$this->config_page_url = array("admin.php@mylist");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Constants
		$this->const = array();
		$r=0;
		
		// par défaut le nombre de ligne par parge est de 25
		$conf->global->MYLIST_NB_ROWS =25;
		
		// Permissions
		$this->rights = array();
		$this->rights_class = 'mylist';
		$r=0;

		$r++;
		$this->rights[$r][0] = 160211; // id de la permission
		$this->rights[$r][1] = "Lire les listes personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 160212; // id de la permission
		$this->rights[$r][1] = "Administrer les listes personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'setup';

		$r++;
		$this->rights[$r][0] = 160213; // id de la permission
		$this->rights[$r][1] = "Modifier les listes personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'c'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 160214; // id de la permission
		$this->rights[$r][1] = "Supprimer les listes personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		// Left-Menu of Equipement module
		$r=0;
		if ($this->no_topmenu())
		{
			$this->menu[$r]=array(	'fk_menu'=>0,
						'type'=>'top',	
						'titre'=>'PatasTools',
						'mainmenu'=>'patastools',
						'leftmenu'=>'mylist',
						'url'=>'/mylist/core/patastools.php?mainmenu=patastools&leftmenu=mylist',
						'langs'=>'mylist@mylist',	
						'position'=>100,
						'enabled'=>'1',		
						'perms'=>'$user->rights->mylist->lire',			                
						'target'=>'',
						'user'=>0);				             
			$r++; //1
		} 
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools',
					'type'=>'left',	
					'titre'=>'myList',
					'mainmenu'=>'patastools',
					'leftmenu'=>'mylist',
					'url'=>'/mylist/index.php',
					'langs'=>'mylist@mylist',
					'position'=>110,
					'enabled'=>'1',
					'perms'=>'$user->rights->mylist->lire',
					'target'=>'',
					'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=mylist',
					'type'=>'left',
					'titre'=>'NewList',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/mylist/card.php?action=create',
					'langs'=>'mylist@mylist',
					'position'=>110,
					'enabled'=>'1',
					'perms'=>'$user->rights->mylist->setup',
					'target'=>'',
					'user'=>2);	
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=mylist',
					'type'=>'left',
					'titre'=>'mylist',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/mylist/list.php',
					'langs'=>'mylist@mylist',
					'position'=>110,
					'enabled'=>'1',
					'perms'=>'$user->rights->mylist->setup',
					'target'=>'',
					'user'=>2);	
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=mylist',
					'type'=>'left',
					'titre'=>'ImportList',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/mylist/card.php?action=importexport',
					'langs'=>'mylist@mylist',
					'position'=>110,
					'enabled'=>'$user->rights->mylist->setup',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);	

	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();

		$result=$this->load_tables();
		
		return $this->_init($sql,$options);
	}

    /**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    function remove($options='')
    {
		$sql = array();

		return $this->_remove($sql,$options);
    }

	/**
	 *		Create tables, keys and data required by module
	 * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 		and create data commands must be stored in directory /mymodule/sql/
	 *		This function is called by this->init.
	 *
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/mylist/sql/');
	}
	
	/*  Is the top menu already exist */
	function no_topmenu()
	{
		// gestion de la position du menu
		$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."menu";
		$sql.=" WHERE mainmenu ='patastools'";
		//$sql.=" AND module ='patastools'";
		$sql.=" AND type = 'top'";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			// il y a un top menu on renvoie 0 : pas besoin d'en créer un nouveau
			if ($this->db->num_rows($resql) > 0)
				return 0;
		}
		// pas de top menu on renvoie 1
		return 1;
	}
}
?>
