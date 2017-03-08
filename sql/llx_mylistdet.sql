-- ===================================================================
-- Copyright (C) 2015	Charlie Benke	<charlie@patas-monkey.com>
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

create table llx_mylistdet
(
	rowid			integer AUTO_INCREMENT PRIMARY KEY, -- clé principale
	fieldname		varchar(255) NOT NULL,				-- description du champ dans la table
	alias			varchar(64) NOT NULL,				-- alias du champ
	fk_mylist		integer  NOT NULL,					-- clé de la liste
	tms				timestamp,							-- date of last update
	name			varchar(255) NOT NULL,				-- label du champs
	type			varchar(20) NULL DEFAULT NULL ,		-- indique le type du champs
	pos				integer NULL DEFAULT NULL, 			-- position du champ ds le tableau
	param			text ,								-- permet de gérer les fonction sup
	align			varchar(10) NULL DEFAULT 100 ,		-- aligment dans le tableau
	enabled			varchar(255) NULL DEFAULT NULL ,	-- accès au champ par habilitation
	visible			integer NULL DEFAULT NULL ,			-- affiché par défaut dans la liste
	filter			integer NULL DEFAULT NULL ,			-- autorise le filtrage
	width			integer NULL DEFAULT NULL ,			-- Largeur du champs ds le tableau
	sumreport		integer NULL DEFAULT NULL ,			-- totalise la valeur du champ
	avgreport		integer NULL DEFAULT NULL ,			-- moyenne la valeur du champ
	filterinit		varchar(255)  NULL DEFAULT NULL ,	-- filtrage/valeur  par défaut
	updatekey		varchar(255)  NULL DEFAULT NULL 	-- clé pour la mise à jour
)ENGINE=innodb;