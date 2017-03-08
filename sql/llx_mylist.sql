-- ===================================================================
-- Copyright (C) 2013-2015	Charlie Benke	<charlie@patas-monkey.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================

create table llx_mylist
(
	rowid		integer AUTO_INCREMENT PRIMARY KEY, -- clé principale
	code		varchar(30) NOT NULL,				-- clé d'accès à la liste   ---> obsolet
	label		varchar(50) NOT NULL,				-- titre de la page du menu
	description	text NULL DEFAULT NULL,				-- description de la page du menu
	titlemenu	varchar(20) NULL DEFAULT NULL ,		-- titre du menu
	mainmenu	varchar(50) NULL DEFAULT NULL ,		-- menu principal
	leftmenu	varchar(50) NULL DEFAULT NULL ,		-- menu à gauche
	posmenu		varchar(50) NULL DEFAULT 100 ,		-- position dans le menu
	elementtab	varchar(50) NULL DEFAULT NULL ,		-- tab dans un element
	author		varchar(50) NULL DEFAULT NULL ,		-- auteur de la liste
	active		integer NULL DEFAULT NULL ,			-- la liste est active ou pas
	perms		text NULL DEFAULT NULL ,			-- droit d'accès associé au menu
	langs		text NULL DEFAULT NULL ,			-- langue du menu et de la liste
	fieldinit	text NULL DEFAULT NULL ,			-- champs utilisable par défaut
	fieldused	text NULL DEFAULT NULL ,			-- liste des champs de la liste
	querylist	text NULL DEFAULT NULL ,			-- requete de sélection des champs
	querydo		text NULL DEFAULT NULL ,			-- requete de mise à jour/ajout/suppression 
	export		integer NULL DEFAULT NULL ,			-- autorise l'exportation de la liste ou non
	model_pdf	varchar(255)						-- modèle d'édition dédié
	
)ENGINE=innodb;