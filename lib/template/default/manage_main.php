<?php
 // $Id$
 // $Author$
 // note: template for patient management functions
 // lic : GPL, v2

//----- Pull configuration for this user
if (!is_object($this_user)) $this_user = CreateObject('FreeMED.User');

//----- Make sure all module functions are loaded
LoadObjectDependency('PHP.module');

//----- Extract all configuration data
if (is_array($this_user->manage_config)) extract($this_user->manage_config);

//----- Check for a *reasonable* refresh time and summary items
if ($automatic_refresh_time > 14) {
	$GLOBALS['__freemed']['automatic_refresh'] = $automatic_refresh_time;
}
if ($num_summary_items < 1) $num_summary_items = 5;

//----- Display patient information box...
$display_buffer .= freemed::patient_box($this_patient);

//----- Suck in management panels
//-- Static first...
foreach ($static_components AS $garbage => $component) {
	if (!$already_set[$component]) {
	switch ($component) {
		case "appointments": // Appointments static component
		include_once("lib/calendar-functions.php");
		// Add header and strip at top
		$modules[__("Appointments")] = "appointments";
		$panel[__("Appointments")] .= "
			<table WIDTH=\"100%\" BORDER=\"0\" CELLSPACING=\"0\"
			 CELLPADDING=\"3\" CLASS=\"thinbox\"
			><tr><td COLSPAN=\"3\" VALIGN=\"MIDDLE\" ALIGN=\"CENTER\"
			 CLASS=\"menubar_items\">
			<A HREF=\"book_appointment.php?patient=$id&type=pat\"
			>".__("Add")."</A> |
			<A HREF=\"manage_appointments.php?patient=$id\"
			>".__("View/Manage")."</A> |
			<A HREF=\"show_appointments.php?patient=$id&type=pat\"
			>".__("Show Today")."</A>
			</TD></tr>
		";

		// Get last few appointments
		$query =
			"SELECT * FROM scheduler WHERE ".
			"calpatient='".addslashes($id)."' AND ".
			"caltype='pat' AND ".
			"( caldateof > '".date("Y-m-d")."' OR ".
			  "( caldateof = '".date("Y-m-d")."' AND ".
			  "  calhour >= '".date("H")."' )".
			") LIMIT ".$num_summary_items;
		if ($debug) print "query = $query<BR>\n";
		$appoint_result = $sql->query($query);
		if (!$sql->results($appoint_result)) {
			$panel[__("Appointments")] .= "
			<tr><TD COLSPAN=\"3\" VALIGN=\"MIDDLE\" ALIGN=\"CENTER\">
			<B>".__("NONE")."</B>
			</TD></tr>
			";
		} else {
			$panel[__("Appointments")] .= "
			<tr><TD COLSPAN=\"3\" VALIGN=\"MIDDLE\" ALIGN=\"CENTER\">
			<table WIDTH=\"100%\" CELLSPACING=0 CELLPADDING=0
			 BORDER=0 CLASS=\"thinbox\"><tr>
			<TD VALIGN=\"MIDDLE\" ALIGN=\"LEFT\"
			 CLASS=\"menubar_info\">
				<B>".__("Date")."</B>
			</TD><TD VALIGN=\"MIDDLE\" ALIGN=\"LEFT\"
			 CLASS=\"menubar_info\">
				<B>".__("Time")."</B>
			</TD><TD VALIGN=\"MIDDLE\" CLASS=\"menubar_info\">
				<!-- <B>".__("Room")."</B> -->
			</TD><TD VALIGN=\"MIDDLE\" CLASS=\"menubar_info\">
				<B>".__("Description")."</B>
			</TD></tr>
			";
			while ($appoint_r=$sql->fetch_array($appoint_result)) {
				$panel[__("Appointments")] .= "
				<tr>
				<TD VALIGN=\"MIDDLE\" ALIGN=\"LEFT\">
				<SMALL>".prepare(fm_date_print(
					$appoint_r["caldateof"]
				))."</SMALL>
				</TD><TD VALIGN=\"MIDDLE\" ALIGN=\"LEFT\">
				<SMALL>".prepare(fc_get_time_string(
					$appoint_r["calhour"],
					$appoint_r["calminute"]
				))."</SMALL>
				</TD><TD VALIGN=\"MIDDLE\" ALIGN=\"LEFT\">
				</TD><TD VALIGN=\"MIDDLE\" ALIGN=\"LEFT\">
				<SMALL>".prepare(stripslashes($appoint_r["calprenote"]))."</SMALL>
				</TD></tr>
				";
			} // end of looping through results
			// Show last few appointments
			$panel[__("Appointments")] .= "
			</table>
			</TD></tr>
			";
		} // end of checking for results
		

		// Footer
		$panel[__("Appointments")] .= "
			</table>
		";
		break; // end appointments

		case "custom_reports":
		$f_results = $sql->query("SELECT * FROM patrectemplate ".
			"ORDER BY prtname");
		$modules[__("Custom Records")] = "custom_reports";
		if ($sql->results($f_results)) {
			$panel[__("Custom Records")] .= "
			<table WIDTH=\"100%\" BORDER=0 CELLSPACING=0
			 CELLPADDING=3 CLASS=\"thinbox\"
			<tr><TD COLSPAN=3 VALIGN=MIDDLE ALIGN=CENTER
			 CLASS=\"menubar_items\">
			</TD></tr>
			<tr><TD ALIGN=\"CENTER\" VALIGN=\"MIDDLE\">
			<DIV ALIGN=\"CENTER\">
          		<FORM ACTION=\"custom_records.php\" METHOD=POST>
        		<INPUT TYPE=HIDDEN NAME=\"patient\" VALUE=\"".prepare($id)."\">
			<INPUT TYPE=HIDDEN NAME=\"action\" VALUE=\"addform\">
			<select NAME=\"form\">
			";
			while ($f_r = $sql->fetch_array ($f_results)) 
			$panel[__("Custom Records")] .= "<option VALUE=\"".$f_r["id"]."\">".
				$f_r["prtname"]."</option>\n"; 
			$panel[__("Custom Records")] .= "
				</select>
				<input class=\"button\" TYPE=\"SUBMIT\" ".
				"VALUE=\"".__("Add")."\"/>
				</form>
				</div>
				</td></tr></table>
			";
		} else {
			// Quick null panel
			$panel[__("Custom Records")] .= "
				<table WIDTH=\"100%\" BORDER=\"0\"
				 CELLSPACING=\"0\" CELLPADDING=\"3\"
				 CLASS=\"thinbox\"
				<tr><TD VALIGN=MIDDLE ALIGN=CENTER
				 CLASS=\"menubar_items\">
				<A HREF=\"custom_records.php?patient=$id\" 
				>".__("View/Manage")."</A>
				</TD></tr>
				<tr><TD ALIGN=\"CENTER\" VALIGN=\"MIDDLE\">
				<B>".__("NONE")."</B>
				</TD></tr></table>
			";
		} // end checking for results
		break; // end custom_reports

		case "medical_information":
		$modules[__("Medical Information")] = "medical_information";
		$panel[__("Medical Information")] = "
		<table WIDTH=\"100%\" BORDER=\"0\" CELLSPACING=\"0\"
		 CELLPADDING=\"3\" CLASS=\"thinbox\"
		<tr><TD VALIGN=\"MIDDLE\" ALIGN=\"CENTER\"
		 CLASS=\"menubar_items\">
		(".__("no actions").")
		</TD></tr>
		<tr><TD ALIGN=\"CENTER\" VALIGN=\"MIDDLE\">
		<DIV ALIGN=\"CENTER\">
		<table WIDTH=\"100%\" BORDER=\"0\">
		<tr><TD ALIGN=\"LEFT\"><B>".__("Blood Type")."</B></TD> 
		<TD ALIGN=\"RIGHT\">".prepare($this_patient->local_record['ptblood'])."</TD></tr>
		</table>
		</TD></tr></table>";
		break; // end medical_information

		case "messages":
		$modules[__("Messages")] = "messages";
		$panel[__("Messages")] = "
		<table WIDTH=\"100%\" BORDER=\"0\" CELLSPACING=\"0\"
		 CELLPADDING=3 CLASS=\"thinbox\"
		<tr><TD VALIGN=\"MIDDLE\" ALIGN=\"CENTER\"
		 CLASS=\"menubar_items\">
		<A HREF=\"messages.php?action=addform\">".__("Add")."</A>
		</TD></tr>
		<tr><TD ALIGN=\"CENTER\" VALIGN=\"MIDDLE\">
		<DIV ALIGN=\"CENTER\">
		<table WIDTH=\"100%\" BORDER=\"0\" CELLSPACING=\"0\">
		";
		$my_result = $sql->query("SELECT * FROM messages WHERE ".
			"msgpatient='".urlencode($id)."' ".
			"ORDER BY msgtime DESC ".
			"LIMIT ".$num_summary_items);
		if ($sql->results($my_result)) {
			$panel[__("Messages")] .= "<tr CLASS=\"menubar_info\">".
				"<TD><b>".__("Date")."</b></TD>".
				"<TD><b>".__("Time")."</b></TD>".
				"<TD><b>".__("User")."</b></TD>".
				"<TD><b>".__("Action")."</b></TD>".
				"</tr>\n";
			while ($my_r = $sql->fetch_array($my_result)) {
				// Transformations for date and time
				$y = $m = $d = $hour = $min = '';
				$y = substr($my_r['msgtime'], 0, 4);
				$m = substr($my_r['msgtime'], 4, 2);
				$d = substr($my_r['msgtime'], 6, 2);
				$hour = substr($my_r['msgtime'], 8, 2);
				$min  = substr($my_r['msgtime'], 10, 2);

				// Get User object
				$this_user = CreateObject('FreeMED.User', $my_r[msgfor]);

				// Form the panel
				$panel[__("Messages")] .= "<tr>".
					"<TD ALIGN=\"LEFT\"><SMALL>$y-$m-$d</SMALL></TD>".
					"<TD ALIGN=\"LEFT\"><SMALL>".fc_get_time_string($hour,$min)."</SMALL></TD>".
					"<TD ALIGN=\"LEFT\"><SMALL>".$this_user->getDescription()."</SMALL></TD>".
					"<TD ALIGN=\"LEFT\">".
					template::summary_delete_link(NULL,
					"messages.php?action=remove&id=".$my_r['id'].
					"&return=manage").
					"</tr>\n".
					"<tr><TD COLSPAN=4 CLASS=\"infobox\"><SMALL>".
					prepare($my_r['msgtext']).
					"</SMALL></TD></tr>\n";			
			}
		} else {
			// If there are no messages regarding this patient
			$panel[__("Messages")] .= "<tr><TD ALIGN=\"CENTER\">".
			__("There are currently no messages.").
			"</TD></tr>\n";
		}
		$panel[__("Messages")] .= "
		</table>
		</DIV>
		</TD></tr></table>";
		break; // end medical_information

		case "photo_id":
		// If there is a file with that name, show it, else box
		if (file_exists(freemed::image_filename(
				$id,
				'identification',
				'djvu'))) {
			$modules[__("Photo ID")] = "photo_id";
			$panel[__("Photo ID")] = "
			<table WIDTH=\"100%\" BORDER=\"0\" CELLSPACING=\"0\"
			 CELLPADDING=\"3\" CLASS=\"thinbox\"
			<tr><TD VALIGN=MIDDLE ALIGN=CENTER
			 CLASS=\"menubar_items\">
			<A HREF=\"photo_id.php?patient=".urlencode($id)."&".
			"return=manage\"
			 >".__("Update")."</A> |
			<A HREF=\"photo_id.php?patient=".urlencode($id)."&".
			"action=remove&return=manage\"
			 >".__("Remove")."</A>
			</TD></tr>
			<tr><TD ALIGN=\"CENTER\" VALIGN=\"MIDDLE\">
			<DIV ALIGN=\"CENTER\">
			<A HREF=\"patient_image_handler.php?".
			"patient=".urlencode($patient)."&".
			"id=identification\" TARGET=\"new\"
			onMouseOver=\"window.status='".__("Enlarge image")."'; return true;\"
			onMouseOut=\"window.status=''; return true;\"
			><EMBED SRC=\"patient_image_handler.php?".
			"patient=".urlencode($id)."&id=identification\"
			 BORDER=\"0\" ALT=\"Photographic Identification\"
			 WIDTH=\"200\" HEIGHT=\"150\"
			 TYPE=\"image/x.djvu\"
			 PLUGINSPAGE=\"".COMPLETE_URL."support/\"
			 ></EMBED></A>
			</DIV>
			</TD></tr>
			</table>
			";

		} else {
			$panel[__("Photo ID")] = "
			<table WIDTH=\"100%\" BORDER=\"0\" CELLSPACING=\"0\"
			 CELLPADDING=\"3\" CLASS=\"thinbox\"
			<tr><TD VALIGN=\"MIDDLE\" ALIGN=\"CENTER\"
			 CLASS=\"menubar_items\">
			<A HREF=\"photo_id.php?patient=".urlencode($id)."\"
			 >".__("Update")."</A>
			</TD></tr>
			<tr><TD ALIGN=\"CENTER\" VALIGN=\"MIDDLE\">
			<DIV ALIGN=\"CENTER\">
			".__("No photographic identification on file.")."
			<BR><BR>
			</DIV>
			</TD></tr>
			</table>
			";
		}
		break; // end photo_id

		case "patient_information":
		//----- Determine date of last visit
		$dolv_result = $sql->query(
			"SELECT * FROM scheduler WHERE ".
			"id='".addslashes($id)."' AND ".
			"(caldateof < '".date("Y-m-d")."' OR ".
			"(caldateof = '".date("Y-m-d")."' AND ".
			"calhour < '".date("H")."'))".
			"ORDER BY caldateof DESC, calhour DESC"
		);
		if (!$sql->results($dolv_result)) {
			$dolv = __("NONE");
		} else {
			$dolv_r = $sql->fetch_array($dolv_result);
			$dolv = prepare(fm_date_print($dolv_r["caldateof"]));
		} // end if there is no result
		//----- Create the panel
		$modules[__("Patient Information")] = "patient_information";
		$panel[__("Patient Information")] .= "
			<table WIDTH=\"100%\" BORDER=\"0\" CELLSPACING=\"0\"
			 CELLPADDING=\"3\" CLASS=\"thinbox\"
			<tr><TD VALIGN=MIDDLE ALIGN=CENTER
			 CLASS=\"menubar_items\" COLSPAN=2>
			<A HREF=\"patient.php?action=modform&id=$id\" 
			>".__("Modify")."</A>
			</TD></tr>
			<tr><TD ALIGN=RIGHT VALIGN=MIDDLE WIDTH=\"50%\">
				<B>".__("Date of Last Visit")."</B> :
			</TD><TD ALIGN=LEFT VALIGN=MIDDLE WIDTH=\"50%\">
				".$dolv."
			</tr><tr><TD ALIGN=RIGHT VALIGN=MIDDLE WIDTH=\"50%\">
				<B>".__("Phone Number")."</B> :
			</TD><TD ALIGN=LEFT VALIGN=MIDDLE WIDTH=\"50%\">
				".$this_patient->local_record["pthphone"]."
			</TD></tr></table>
		";
		break; // end patient information

		default: // Everything else.... do nothing (ERROR)
		break; // end default
	} // end component switch
	} // end checking for already set

	$already_set[$component] = true;
} // end static components

//-- ... then modular
foreach ($modular_components AS $garbage => $component) {
	// Determine if the class exists
	if (!is_object($module_list)) {
		$module_list = CreateObject(
			'PHP.module_list',
			PACKAGENAME,
			array(
				'cache_file' => 'data/cache/modules'	
			)
		);
	}
	
	// End checking for component
	if ($module_list->check_for($component) and (!$already_set[$component])) {
		// Execute proper portion and add to panel
		$modules[__($module_list->get_module_name($component))] =
			$component;
		$panel[__($module_list->get_module_name($component))] .= "
			<table WIDTH=\"100%\" BORDER=\"0\" CELLSPACING=\"0\"
			 CELLPADDING=\"3\" CLASS=\"thinbox\"
			<tr><td VALIGN=\"MIDDLE\" ALIGN=\"CENTER\"
			 CLASS=\"menubar_items\">".
			module_function($component, "summary_bar", array ( $id )).
			"</td></tr>
			<tr><td ALIGN=\"CENTER\" VALIGN=\"MIDDLE\">
			".module_function($component, "summary",
				array (
					$id, // patient ID
					$num_summary_items // items per panel
				)
			)."</td></tr></table>
		";

		$already_set[$component] = true;
	} else {
		// Don't do anything if it doesn't exist
	} // end checking for component existing
} // end static components

//----- Determine column requirements
if ($display_columns < 1) $display_columns = 1;
if (count($panel) > 0) {
	$column_cutoff = ceil ( count($panel) / $display_columns );
} // check for ability to display panels

//----- Display tables

if (count($panel) > 0) {
	// Sort by panel names
	ksort($panel);

	// Table header
	$display_buffer .= "
	<table WIDTH=\"100%\" CELLSPACING=\"3\" CELLPADDING=\"0\" BORDER=\"0\">
	<tr VALIGN=MIDDLE ALIGN=CENTER>
	";

	$column = 1; reset ($panel);
	foreach ($panel AS $k => $v) {
		// Check to see if we're on a new row yet
		if ($column > $display_columns) {
			$column = 1;

			// Display footer and new header
			$display_buffer .= "
			</tr><tr VALIGN=MIDDLE ALIGN=CENTER>
			";
		}

		// Add panel
		$myk = str_replace(" ", "_", $k);
		$display_buffer .= "
		<TD VALIGN=\"TOP\" ALIGN=\"CENTER\" WIDTH=\"".
			( (int) (100 / $display_columns) )."%\">
		<table WIDTH=\"100%\" BORDER=\"0\" CELLSPACING=\"0\"
		 CELLPADDING=\"0\">
		<tr><TD CLASS=\"reverse\" VALIGN=\"MIDDLE\" ALIGN=\"CENTER\">
		<B>".prepare($k)."</B>
		</TD><TD CLASS=\"reverse\" VALIGN=\"MIDDLE\" ALIGN=\"RIGHT\">
		<A HREF=\"manage.php?id=".urlencode($id)."&".
		"action=remove&module=".urlencode($modules[$k])."\"
		onMouseOver=\"document.images.".$myk."_close.src='lib/template/default/img/close_x_pressed.png'; return true;\"
		onMouseOut=\"document.images.".$myk."_close.src='lib/template/default/img/close_x.png'; return true;\"
		><IMG NAME=\"".$myk."_close\"
		SRC=\"lib/template/default/img/close_x.png\"
		BORDER=\"0\" ALT=\"X\"></A></TD></tr>
		<tr><TD VALIGN=\"MIDDLE\" ALIGN=\"CENTER\" COLSPAN=\"2\">
		<CENTER>$v</CENTER>
		</TD></tr></table>
		</TD>
		";

		// Move to the next column
		$column += 1;
	} // end looping

	// Fill up empty space
	if ($column < $display_columns) {
		for ($i=1; $i<=($display_columns-$column); $i++)
			$display_buffer .= "<TD>&nbsp;</TD>\n";
	} // end filling up empty space

	// Table footer
	$display_buffer .= "
	</tr></table>
	";

} else {
	// Display warning if no panels
	$display_buffer .= "
	<p/>
	<div align=\"CENTER\">
	<b>".__("Please configure panels through \"Configure\" in the sidebar.")."</b>
	</div>
	<p/>
	";
} // end checking for *any* panels

// **************************************************** STATIC MODULES

//      $display_buffer .= "
//        <tr><TD ALIGN=RIGHT>
//         <B>Dependent Information</B> : 
//        </TD><TD ALIGN=LEFT>
//     ";
//      removed as part of coverage overhaul
//     if (!$this_patient->isDependent()) {
//      $dep_query = "SELECT COUNT(*) FROM patient WHERE ptdep='".
//                   $this_patient->id."'";
//      $dep_result = $sql->query($dep_query);
//      $dep_r = $sql->fetch_array($dep_result);
//      $num_deps = $dep_r[0];
//      if ($num_deps<1)
//        $display_buffer .= "No Dependents";
//      else
//        $display_buffer .= "
//	 <A HREF=\"patient.php?action=find&criteria=".
//	 "dependants&f1=$id\">".__("Dependents")."</A> [$num_deps]
//        ";
//      } else {
//      $guarantor = CreateObject('FreeMED.Patient',$this_patient->ptdep);
//      $display_buffer .= "
//         <A HREF=\"manage.php?action=view&id=".$this_patient->ptdep."\"
//         >".__("Guarantor")."</A>
//	</TD><TD>[".$guarantor->fullName()."]</TD></tr>
//     ";
//    }

// Add configure to the menu bar
if ($action != "config") {
	$menu_bar[__("Configure")] = "manage.php?id=$id&action=config";
}


//----- Add to menu bar
if (!is_object($module_list)) {
	$module_list = CreateObject(
		'PHP.module_list',
		PACKAGENAME,
		array(
			'cache_file' => 'data/cache/modules'
		)
	);
}
// Form template for menubar
$menu_bar = array_merge (
	$menu_bar,
	$module_list->generate_array(
		"Electronic Medical Record",
		0,
		"#name#",
		"module_loader.php?module=#class#&patient=$id"
	)
);

?>
