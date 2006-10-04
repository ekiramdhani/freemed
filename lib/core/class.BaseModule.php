<?php
 // $Id$
 //
 // Authors:
 //      Jeff Buchbinder <jeff@freemedsoftware.org>
 //
 // Copyright (C) 1999-2006 FreeMED Software Foundation
 //
 // This program is free software; you can redistribute it and/or modify
 // it under the terms of the GNU General Public License as published by
 // the Free Software Foundation; either version 2 of the License, or
 // (at your option) any later version.
 //
 // This program is distributed in the hope that it will be useful,
 // but WITHOUT ANY WARRANTY; without even the implied warranty of
 // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 // GNU General Public License for more details.
 //
 // You should have received a copy of the GNU General Public License
 // along with this program; if not, write to the Free Software
 // Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

LoadObjectDependency('org.freemedsoftware.core.Module');

// Class: org.freemedsoftware.core.BaseModule
//
//	Basic FreeMED module class. All modules in FreeMED inheirit methods
//	from this class. It extends the phpwebtools module class.
//
class BaseModule extends Module {

	// override variables
	var $PACKAGE_NAME = PACKAGENAME;
	var $MODULE_AUTHOR = "jeff b (jeff@ourexchange.net)";
	var $MODULE_DESCRIPTION = "NO DESCRIPTION";
	var $MODULE_VENDOR = "Stock Module";

	// Set package versioning information
	var $PACKAGE_VERSION = VERSION;

	// All FreeMED modules use this one loader
	var $page_name = "module_loader.php";

	// Variable: print_template
	//
	//	Sets the print template to be used for this EMR
	//	segment. Templates are given as files in lib/tex without
	//	the .tex extension. If this is not definied, FreeMED
	//	will default to using the internal renderer.
	//
	// Example:
	//
	//	$this->print_template = '_template';
	//
	var $print_template = '';

	// Method: BaseModule constructor
	public function __construct ( ) {
		// Call parent constructor
		parent::__construct ( );
		// Load language files, if necessary
		T_textdomain( strtolower( get_class( $this ) ) );
		// Push acl information, if there is any
		if ($this->acl) { $this->_SetMetaInformation('acl', $this->acl); }
	} // end constructor BaseModule

	// Method: fax_widget
	//
	//	Callback to allow custom fax controls (for addressbook,
	//	et cetera). By default, this is disabled, and a standard
	//	text entry is used.
	//
	// Parameters:
	//
	//	$varname - Name of the variable
	//
	//	$id - Record id that is being printed
	//
	// Returns:
	//
	//	XHTML widget.
	//
	function fax_widget ( $varname, $id ) {
		return html_form::text_widget('fax_number',
			array( 'length' => '16' )
		);
	} // end method fax_widget

	// Method: printaction
	//
	//	Basic printing functionality
	//
	function printaction ( ) { $this->_print(); }

	// Method: _print
	//
	//	Provides basic printing interface
	//
	function _print ( ) {
		// Turn off the template
		global $display_buffer;
		$GLOBALS['__freemed']['no_template_display'] = true;

		// Deal with faxstatus
		if (isset($_REQUEST['faxstatus'])) {
			$fax = CreateObject('org.freemedsoftware.core.Fax', '/dev/null');
			$status = $fax->State($_REQUEST['faxstatus']);
			$display_buffer .= "<b>".$output."</b>\n";
			if ($status == 1) {
				$display_buffer .= "<div align=\"center\"><b>".__("Fax sent successfully.")."</b></div>\n";
				$display_buffer .= "<div align=\"center\"><a onClick=\"javascript:close();\" class=\"button\">".__("Close")."</div>\n";
			} else {
				$display_buffer .= "<b>".__("Fax is attempting to send: ")."</b>".$status."\n";
				$GLOBALS['__freemed']['automatic_refresh'] = 10;
			}
			return true;
			break;
		}

		// Check for selected printer
		if (!freemed::config_value('printnoselect') and !isset($_REQUEST['printer'])) {
			// select printer form
			global $display_buffer;
			$display_buffer .= "
			<form action=\"".$this->page_name."\" method=\"post\"
			 name=\"myform\">
			<div class=\"PrintContainer\">
			<input type=\"hidden\" name=\"module\" value=\"".prepare($_REQUEST['module'])."\"/>
			<input type=\"hidden\" name=\"type\" value=\"".prepare($_REQUEST['type'])."\"/>
			<input type=\"hidden\" name=\"action\" value=\"".prepare($_REQUEST['action'])."\"/>
			<input type=\"hidden\" name=\"id\" value=\"".prepare($_REQUEST['id'])."\"/>
			<input type=\"hidden\" name=\"patient\" value=\"".prepare($_REQUEST['patient'])."\"/>
			";
			if ($_REQUEST['print_template']) {
				$display_buffer .= "<input type=\"hidden\" name=\"print_template\" value=\"".prepare($_REQUEST['print_template'])."\"/>\n";
			}
			$display_buffer .= "
			<table border=\"0\" width=\"98%\" cellspacing=\"0\">
			<tr class=\"PrintContainerItem\"
			 	 onMouseOver=\"this.className='PrintContainerItemSelected'; return true;\"
				 onMouseOut=\"this.className='PrintContainerItem'; return true;\"
				 onClick=\"document.getElementById('print_method_printer').click(); return true;\">

				<td width=\"50\">
				<input type=\"radio\" 
				 name=\"print_method\"
				 value=\"printer\" checked
				 id=\"print_method_printer\" /></td>
				<td
				>".__("Printer")."</td>
				<td>".freemed::printers_widget('printer')."</td>
			</tr>
			<tr class=\"PrintContainerItem\"
			 	 onMouseOver=\"this.className='PrintContainerItemSelected'; return true;\"
				 onMouseOut=\"this.className='PrintContainerItem'; return true;\"
				 onClick=\"document.getElementById('print_method_fax').click(); return true;\">
				<td width=\"50\">
				<input type=\"radio\"
				 name=\"print_method\"
				 value=\"fax\"
				 id=\"print_method_fax\" /></td>
				<td>".__("Fax")."</td>
				<td>".$this->fax_widget('fax_number', $_REQUEST['id'])."</td>
			</tr>
			<tr class=\"PrintContainerItem\"
			 	 onMouseOver=\"this.className='PrintContainerItemSelected'; return true;\"
				 onMouseOut=\"this.className='PrintContainerItem'; return true;\"
				 onClick=\"document.getElementById('print_method_browser').click(); return true;\">
				 <td width=\"50\">
				 <input type=\"radio\"
				  name=\"print_method\"
				  value=\"browser\"
				  checked=\"checked\" 
				  id=\"print_method_browser\" /></td>
				 <td colspan=\"2\">".__("Browser-Based")."</td>
			</tr>
			";

			// For EMR, show additional attachments
			if ($this->patient_field) {
				$display_buffer .= "
				<tr>
				<td width=\"50\">&nbsp;</td>
				<td>".__("Attach")."</td>
				<td>".html_form::select_widget('attachment', array_merge( array(__("NONE") => ''), module_function('FaxMultiple', 'GetEMRAttachments', array ( $_REQUEST['patient'] ))), array('style'=>'width: 200px;'))."</td>
				</tr>
				";
			}

			// Continue with display
			$display_buffer .= "
			</table>
			<script language=\"javascript\">
			function isValid(parm) {
				var val = '0123456789';
				if (parm == '') return false;
				if (parm.length < 7) return false;
				for (i=0; i<parm.length; i++) {
					if (val.indexOf(parm.charAt(i),0) == -1) return false;
				}
				return true;
			}
			</script>
			<div align=\"center\">
			<input onClick=\"if(document.getElementById('print_method_fax').checked) { if (!isValid(document.getElementById('fax_number').value)) { alert('Invalid fax number.'); return false; } } else { this.form.submit(); }\" type=\"submit\" value=\"".__("Print")."\"
			 class=\"button\" />
			</div>
			</div>
			</form>
			";
			return true;
		}

		// Handle render
		if ($render = $this->print_override($_REQUEST['id'])) {
			// Handle this elsewhere
		} else {
			list ($title, $heading, $physician) = $this->_TeX_Information();

			// Create TeX object for patient
			$TeX = CreateObject('org.freemedsoftware.core.TeX', array (
				'title' => $title,
				'heading' => $heading,
				'physician' => $physician
			));

			// Actual renderer for formatting array
			if ($this->table_name) {
				if ($this->summary_query) {
					// If this is an EMR module with additional
					// fields, import them
					$query = "SELECT *".
						( (count($this->summary_query)>0) ? 
						",".join(",", $this->summary_query)." " : " " ).
						"FROM ".$this->table_name." ".
						"WHERE id='".addslashes($_REQUEST['id'])."'";
						$result = $GLOBALS['sql']->query($query);
						$rec = $GLOBALS['sql']->fetch_array($result);
				} else {
					$rec = $GLOBALS['sql']->get_link($this->table_name, $_REQUEST['id']);
				} // end checking for summary_query
			} else {
				$rec = array ( 'id' => $_REQUEST['id'] );
			}

			// Check for overridden template
			if (file_exists('lib/tex/'.freemed::secure_filename($_REQUEST['print_template']).'.tex')) {
				$this_template = freemed::secure_filename($_REQUEST['print_template']);
			} else {
				$this_template = $this->print_template;
			}

			$TeX->_buffer = $TeX->RenderFromTemplate(
				$this_template,
				$rec
			);
		}

		global $display_buffer;

		// Get appropriate printer from user settings
		global $this_user;
		if (!is_object($this_user)) {
			$this_user = CreateObject('org.freemedsoftware.api.User');
		}
		$printer = CreateObject('PHP.PrinterWrapper');

		$display_buffer .= __("Printing")." ... <br/>\n";

		// Figure out print method
		$_pm = $_REQUEST['print_method'];
		switch ($_pm) {
			// Handle direct to browser
			case 'browser':
			if ($render) {
				$_file = $render;
			} else {
				$_file = $TeX->RenderToPDF(!empty($this->print_template));
			}
			if ($_REQUEST['attachment']) {
				// Render second PDF
				$parts = explode('|', $_REQUEST['attachment']);

				// Composite ...
				$comp = CreateObject('org.freemedsoftware.core.MultiplePDF');
				$comp->Add( $_file );
				$comp->Add( module_function($parts[0], '_RenderToPDF', array($parts[1])) );
				$file = $comp->Composite();
			} else {
				$file = $_file;
			}
			ob_start();
			readfile($file);
			$contents = ob_get_contents();
			ob_end_clean();

			Header('Content-Type: application/x-freemed-print-pdf');
			Header('Content-Length: '.strlen($contents));
			Header('Content-Disposition: inline; filename="'.mktime().'.pdf"');
			print $contents;
			flush();
			//print "file = $file<br/>\n";
			unlink($file);
			die();
			break;

			case 'fax':
			if ($render) {
				$_file = $render;
			} else {
				$_file = $TeX->RenderToPDF(!empty($this->print_template));
			}
			// Handle attachments
			if ($_REQUEST['attachment']) {
				// Render second PDF
				$parts = explode('|', $_REQUEST['attachment']);

				// Composite ...
				$comp = CreateObject('org.freemedsoftware.core.MultiplePDF');
				$comp->Add( $_file );
				$comp->Add( module_function($parts[0], '_RenderToPDF', array($parts[1])) );
				$file = $comp->Composite();
			} else {
				// Pass through ...
				$file = $_file;
			}
			$fax = CreateObject('org.freemedsoftware.core.Fax', 
				$file, 
				array (
					'sender' => $this_user->user_descrip,
					'comments' => __("HIPPA Compliance Notice: This transmission contains confidential medical information which is protected by the patient/physician privilege. The enclosed message is being communicated to the intended recipient for the purposes of facilitating healthcare. If you have received this transmission in error, please notify the sender immediately, return the fax message and delete the message from your system.")
				)
			);
			//print ($_REQUEST['fax_number']);
			$output = $fax->Send($_REQUEST['fax_number']);
			//$display_buffer .= "<b>".$output."</b>\n";
			// TODO : Descrip call back
			if ($this->patient_field) {
				$_r = $GLOBALS['sql']->get_link( $this->table_name, $_REQUEST['id'] );
				$_p = CreateObject('org.freemedsoftware.core.Patient', $_r[$this->patient_field]);
				$descrip = $this->record_name.' for '.$_p->fullName();

				$this_user->setFaxInQueue(
					$output,
					$_r[$this->patient_field],
					$_REQUEST['fax_number'],
					get_class($this),
					$_REQUEST['id']
				);
				if ($_REQUEST['attachment']) {
					$parts = explode('|', $_REQUEST['attachment']);
					$this_user->setFaxInQueue(
						$output,
						$_r[$this->patient_field],
						$_REQUEST['fax_number'],
						$parts[0],
						$parts[1]
					);
				}
			}
			$display_buffer .= "<b>".__("Refreshing")."... </b>\n";
			//$GLOBALS['refresh'] = $this->page_name."?".
			//	"module=".urlencode($_REQUEST['module'])."&".
			//	"type=".urlencode($_REQUEST['type'])."&".
			//	"action=print&".
			//	"faxstatus=".urlencode($output);
			$GLOBALS['__freemed']['close_on_load'] = true;
			break;

			// Handle actual printer
			case 'printer': 
			if ($render) {
				$_p = CreateObject('PHP.PrinterWrapper');
				$_p->PrintFile($_REQUEST['printer'], $render);
				unlink($render);
			} else {
				// DEBUG:
				//$display_buffer .= "<pre>\n".
				//	$TeX->RenderDebug().
				//	"</pre>\n(You must disable this to print)";
				//template_display();
				$TeX->SetPrinter(
					CreateObject('PHP.PrinterWrapper'),
					//$user->getManageConfig('default_printer')
					$_REQUEST['printer']
				);
				// TODO: Handle direct PDF generation and return here
				$TeX->PrintTeX(1, !empty($this->print_template));
			}
			$GLOBALS['__freemed']['close_on_load'] = true;
			break;

			default:
			print "print_method = ".$_pm."<br/>\n";
			break;
		}
	} // end function print

	// Method: setup
	//
	//	Overrides the internal phpwebtools setup method. This causes
	//	FreeMED to run either _setup() on first run, or _update()
	//	if the module has an older version installed.
	//
	function setup () {
		if (!freemed::module_check($this->MODULE_UID, $this->MODULE_VERSION)) {
			// check if it is installed *AT ALL*
			if (!freemed::module_check($this->MODULE_UID, "0.0001")) {
				// run internal setup routine
				$val = $this->_setup();
			} else {
				// run internal update routine
				$val = $this->_update();
			} // end checking to see if installed at all

			// register module
			freemed::module_register($this->MODULE_UID, $this->MODULE_VERSION);

			return $val;
		} // end checking for module
	} // end function setup

	// _setup (in this case, wrapped in classes...)
	public function _setup () { return true; }

	// _update (in this case, wrapped in classes...)
	public function _update () { }

	//----- Internal module functions

	// Method: print_override
	//
	//	Use this to replace the default printing behavior of the
	//	system.
	//
	// Parameters:
	//
	//	$id - Record ID to be "printed" as a PDF.
	//
	// Returns:
	//
	//	Filename of PDF file containing render.
	//
	function print_override ( $id ) {
		return false; // STUB, so we don't use it most of the time
	} // end method print_override

	// Method: BaseModule->_GetAssociations
	//
	//	Get a list of associations for the current module.
	//
	// Returns:
	//
	//	Array of associations made to this module.
	//
	function _GetAssociations () {
		$index = freemed::module_cache();
		$associations = array();
		foreach ( $index AS $module ) {
			$a = $module['META_INFORMATION']['__associations'];
			foreach ($a as $_k => $_v) {
				if (strtolower($_k) == strtolower($this->MODULE_CLASS)) {
					$associations[] = $_v;
				}
			}
		}
		return $associations;
	} // end method _GetAssociations

	// Method: BaseModule->_SetAssociation
	//
	//	Creates an association with another module.
	//
	// Parameters:
	//
	//	$with - Module name (class name) of module to associate with.
	//
	protected function _SetAssociation ( $with ) {
		$this->META_INFORMATION['__associations_list'][] = $with;
		$this->META_INFORMATION['__associations']["$with"] = get_class($this);
	} // end method BaseModule->_SetAssociation

	// Method: BaseModule->_SetHandler
	//
	//	Attaches the current module to the specified system
	//	handler.
	//
	// Parameters:
	//
	//	$handler - Name of the system handler. Please note that
	//	this is case sensitive.
	//
	//	$method - Method that will be called by the specified handler.
	//	This is 'handler' by default.
	//
	protected function _SetHandler ($handler, $method = 'handler') {
		$this->META_INFORMATION['__handler']["$handler"] = $method;
	} // end method BaseModule->_SetHandler

} // end class BaseModule

?>
