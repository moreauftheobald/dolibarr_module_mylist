<?php
/* Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2013		Charles-Fr BENKE		<charles.fr@benke.fr>
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
 *
 * Javascript code to activate drag and drop on lines
 */
?>

<!-- BEGIN PHP TEMPLATE FOR JQUERY -->
<?php
$rowid=$object->rowid;
$ListOfLine=str_replace("'", "\'", json_encode($object->listsUsed));
$nboflines=count($object->listsUsed);
$forcereloadpage=$conf->global->MAIN_FORCE_RELOAD_PAGE;

if ( $nboflines > 1) { ?>
<script type="text/javascript">
function cleanLine(expr) {
	if (typeof(expr) != 'string') return '';
	liste=expr.replace(/tablelines\[\]=/g, ",");
	liste=liste.replace(/&/g, "");
	liste=liste.replace(/,,/, "");
	return liste;
}

$(document).ready(function(){
	$(".imgup").hide();
	$(".imgdown").hide();
	$(".lineupdown").removeAttr('href');
	$(".tdlineupdown").css("background-image",'url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png'; ?>)');
	$(".tdlineupdown").css("background-repeat","no-repeat");
	$(".tdlineupdown").css("background-position","center center");

	$("#tablelines").tableDnD({
		onDrop: function(table, row) {
			var reloadpage = "<?php echo $forcereloadpage; ?>";
			var roworder = cleanLine($("#tablelines").tableDnDSerialize());
			var table_element_line = '<?php echo $ListOfLine; ?>';
			var rowid = "<?php echo $rowid; ?>";
			$.post("<?php echo DOL_URL_ROOT; ?>/mylist/ajax/row.php",
					{
						roworder: roworder,
						table_element_line: table_element_line,
						rowid: rowid
					},
					function() {
						if (reloadpage == 1) {
							location.href = '<?php echo $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']; ?>';
						} else {
							$("#tablelines .drag").each(
									function( intIndex ) {
										$(this).removeClass("pair impair");
										if (intIndex % 2 == 0) $(this).addClass('impair');
										if (intIndex % 2 == 1) $(this).addClass('pair');
									});
						}
					});
		},
		onDragClass: "dragClass",
		dragHandle: "tdlineupdown"
	});
	$(".tdlineupdown").hover( function() { $(this).addClass('showDragHandle'); },
		function() { $(this).removeClass('showDragHandle'); }
	);
});
</script>
<?php } else { ?>
<script>
$(document).ready(function(){
	$(".imgup").hide();
	$(".imgdown").hide();
	$(".lineupdown").removeAttr('href');
});
</script>
<?php } ?>
<!-- END PHP TEMPLATE -->