<?php
 // $Id$
 //
 // Authors:
 //      Jeff Buchbinder <jeff@freemedsoftware.org>
 //
 // FreeMED Electronic Medical Record and Practice Management System
 // Copyright (C) 1999-2011 FreeMED Software Foundation
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

// Class: org.freemedsoftware.core.Coverage
//
//	Class encapsulating insurance coverage. This class is rather
//	unique in FreeMED, as FreeMED looks at patients as objects, and
//	their insurance data as a completely ancillary table, with all
//	insurance information being completely separate from the core
//	electronic medical record.
//
class Coverage {
	var $local_record;                // stores basic record
	var $id;                          // record ID for insurance company
	var $covpatgrpno;                  // patients group no for this payer
	var $covpatinsno;                  // patients id number for this payer
	var $covstatus;
	var $covtype;                   // payertype 1 prim, 2 sec 3 tert 4 wc
	var $coveffdt;                // effective dates for coverage
	var $covinsco;		// pointer to corresponding insco.
	var $covreldep;                 // guar relation to insured 
	var $covdep;                 // help ease the conversion
	var $covpatient;             // the patient

	// insureds info only if rel is not "S"elf

	// Method: Coverage constructor
	//
	// Parameters:
	//
	//	$coverageid - Database table identifier for the
	//	specified coverage.
	//
	public function __construct ($coverageid = "") {
		if ($coverageid=="" OR $coverageid==0) { return false; }

		// Check in the cache
		if (!isset($GLOBALS['__freemed']['cache']['coverage'][$coverageid])) {
			// Get record
			$this->local_record = $GLOBALS['sql']->get_link( 'coverage', $coverageid );

			// Cache it
			$GLOBALS['__freemed']['cache']['coverage'][$coverageid] = $this->local_record;
		} else {
			// Retrieve from cache
			$this->local_record = $GLOBALS['__freemed']['cache']['coverage'][$coverageid];
		}
		$this->covpatgrpno = $this->local_record['covpatgrpno'];	
		$this->covpatinsno = $this->local_record['covpatinsno'];	
		$this->covstatus = $this->local_record['covstatus'];	
		$this->covtype = $this->local_record['covtype'];	
		$this->coveffdt = $this->local_record['coveffdt'];	
		$this->covinsco = CreateObject('org.freemedsoftware.core.InsuranceCompany', $this->local_record['covinsco']);	
		$this->covreldep = $this->local_record['covrel'];	
		$this->id = $this->local_record['id'];
		$this->covpatient = $this->local_record['covpatient'];	
		if ($this->covreldep != "S") {
			// you pass this to the guarantor class
			$this->covdep = $this->id;
		} else {
			$this->covdep = 0;
		}

	} // end constructor Coverage

	// Method: GetProceduresToBill
	//
	//	Returns the list of procedures that should be billed
	//	based on the information given.
	//
	// Parameters:
	//
	//	$pat - (optional)
	//
	//	$id - (optional)
	//
	//	$type - (optional)
	//
	//	$forpat - (optional)
	//
	public function GetProceduresToBill ( $pat=-1, $id=-1, $type=-1, $forpat=0 ) {
		//print "GetProceduresToBill (pat = $pat, id = $id, type = $type, forpat = $forpat)\n";

		if ($forpat == 0) {
			if (!$id) {
				//print "Coverage::GetProceduresToBill - no id present.<br/>\n";
				return 0;
			}
			if (!$type) {
				//print "Coverage::GetProceduresToBill - no type present.<br/>\n";
				return 0;
			}
		}

		if (!$pat) {
			//print "Coverage::GetProceduresToBill - no patient present.<br/>\n";
			return 0;
		}

		$query = "SELECT * FROM procrec ".
			"WHERE (proccurcovtp='".addslashes($type)."' AND ".
			"proccurcovid='".addslashes($id)."' AND ".
			"procbalcurrent > '0' AND ".
			"procpatient = ".addslashes($pat)." AND ".
			"procbillable='0' AND procbilled='0') ".
			"ORDER BY procpos,procphysician,procrefdoc,proceoc,".
				"procclmtp,procauth,proccov1,proccov2,".
				"procdt";
		//print "query = \"$query\"\n";
		$result = $GLOBALS['sql']->queryAll($query);
		if (count($result) < 1) {
			return 0;
		} else {
			return $result;
		}
	} // end method GetProceduresToBill

	// Method: get_coverage
	public function get_coverage ( ) {
		return $this->local_record;
	} // end method get_coverage

	// Method: get_payers
	//
	//	Get entire list of payers for the system
	//
	// Returns:
	//
	//	Array of payer id keys
	//
	public function get_payers ( ) {
		$query = "SELECT id FROM insco ORDER BY insconame, inscocity, inscostate";
		$payers = $GLOBALS['sql']->queryCol( $query );
		return $payers;
	} // end method get_payers

} // end class Coverage

?>
