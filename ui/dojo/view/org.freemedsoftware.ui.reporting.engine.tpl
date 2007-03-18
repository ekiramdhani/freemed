<!--{* Smarty *}-->
<!--{*
 // $Id$
 //
 // Authors:
 //      Jeff Buchbinder <jeff@freemedsoftware.org>
 //
 // FreeMED Electronic Medical Record and Practice Management System
 // Copyright (C) 1999-2007 FreeMED Software Foundation
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
*}-->

<script language="javascript">
	dojo.require("dojo.event.*");
	dojo.require("dojo.widget.Form");
	dojo.require("dojo.widget.DropdownDatePicker");
	dojo.require("dojo.widget.FilteringTable");

	var reportingEngine = {
		populateReportsList: function ( ) {
			dojo.io.bind({
				method: 'POST',
				content: {
					param0: 'en_US' // TODO: FIXME!
				},
				url: '<!--{$relay}-->/org.freemedsoftware.module.Reporting.GetReports',
				load: function(type, data, evt) {
					if (data) {
						dojo.widget.byId('reportsList').store.setData( data );
					}
				},
				mimetype: "text/json"
			});
		},
		selectReport: function ( ) {
			var myReport = dojo.widget.byId('reportsList').getSelectedData().report_uuid;
			dojo.io.bind({
				method: 'POST',
				content: {
					param0: myReport
				},
				url: '<!--{$relay}-->/org.freemedsoftware.module.Reporting.GetReportParameters',
				load: function(type, data, evt) {
					if (!data) { return false; }
					document.getElementById('reportEngineForm').style.display = 'block';
					document.getElementById('reportEngineFormContent').innerHTML = '';
					if ( data.params.length > 0 ) {
						reportingEngine.populateForm( data );
					}
				},
				mimetype: "text/json"
			});
		},
		populateForm: function ( data ) {
			//alert(dojo.json.serialize(data));

			// Initialize
			document.getElementById('reportEngineFormContent').innerHTML = '';

			var tT = document.createElement('table');
			var tTr = new Array ( );
			var tTd = new Array ( );
			var tDiv = new Array ( );
			var tHidden = new Array ( );

			// Save a copy of the parameters structure
			this.reportParameters = data;

			for (var i=0; i<data.params.length; i++) {
				tTr[ i ] = document.createElement( 'tr' );
				tTd[ (i * 2) ] = document.createElement( 'td' );
				tTd[ (i * 2) + 1 ] = document.createElement( 'td' );
				tDiv[ i ] = document.createElement( 'div' );

				tTd[ (i * 2) ].innerHTML = '<b>' + data.params[i].name + '</b>';

				// Add container div to TD cell
				tTd[ (i * 2) + 1 ].appendChild( tDiv[ i ] );

				// Depending on what kind of element we have, determine which element to create
				switch ( data.params[i].type ) {
					case 'Date':
					// DropdownDatePicker element
					dojo.widget.createWidget(
						'DropdownDatePicker',
						{
							name: 'param' + i.toString(),
							id: 'param' + i.toString()
						},
						tDiv[ i ]
					);
					break; // Date

					case 'Provider':
					dojo.widget.createWidget(
						'Select',
						{
							name: 'param' + i.toString(),
							id: 'param' + i.toString() + '_widget',
							width: '300px',
							dataUrl: "<!--{$relay}-->/org.freemedsoftware.module.ProviderModule.picklist?param0=%{searchString}",
							mode: 'remote',
							autocomplete: false,
							iteration: i,
							setValue: function ( ) { if (arguments[0]) { document.getElementById('param" + this.iteration.toString() + "').value = arguments[0]; } }
						},
						tDiv[ i ]
					);
					// Keep track of the data here ...
					tHidden[ i ] = document.createElement( 'input' );
					tHidden[ i ].type = 'hidden';
					tHidden[ i ].id = "param" + i.toString();
					tHidden[ i ].name = "param" + i.toString();
					tDiv[ i ].appendChild( tHidden[ i ] );
					break; // Provider

					default:
					tDiv[ i ].innerHTML = "<!--{t}-->Unknown element.<!--{/t}-->";
					break; // default / unknown
				}

				// Add TD cells to TR row
				tTr[ i ].appendChild( tTd[ (i * 2) ] );
				tTr[ i ].appendChild( tTd[ (i * 2) + 1 ] );

				// Append row to table
				tT.appendChild( tTr[ i ] );
			}

			// Append entire table to container DIV
			document.getElementById('reportEngineFormContent').appendChild( tT );
		},
		buildParameters: function ( ) {
			var b = new Array ( );
			if ( this.reportParameters.params.length < 1 ) {
				return b;
			}
			for ( var i=0; i<this.reportParameters.params.length; i++) {
				switch ( this.reportParameters.params[i].type ) {
					case 'Date':
					b[ i ] = dojo.widget.byId( 'param' + i.toString() ).inputNode.value;
					break;

					default:
					b[ i ] = document.getElementById( 'param' + i.toString() ).value;
					//alert("DEFAULT b[ " + i.toString() + " ] = " + b[i] );
					break;
				}
			}
			return b;
		},
		generate: function ( type ) {
			var myReport = dojo.widget.byId('reportsList').getSelectedData().report_uuid;
			var params = this.buildParameters( );
			var uri = "<!--{$relay}-->/org.freemedsoftware.module.Reporting.GenerateReport?param0=" + encodeURIComponent( myReport ) + "&param1=" + type.toLowerCase() + "&param2=" + encodeURIComponent( dojo.json.serialize( params ) );

			document.getElementById('reportView').src = uri;
		},

		// Individual button callbacks
		generateCSV: function ( ) { this.generate('CSV'); },
		generateHTML: function ( ) { this.generate('HTML'); },
		generateXML: function ( ) { this.generate('XML'); }
	};

	_container_.addOnLoad(function() {
		reportingEngine.populateReportsList();
		dojo.event.connect(dojo.widget.byId('reportsList'), "onSelect", reportingEngine, 'selectReport');
		dojo.event.connect(dojo.widget.byId('reportSubmitCSV'), "onClick", reportingEngine, 'generateCSV');
		dojo.event.connect(dojo.widget.byId('reportSubmitHTML'), "onClick", reportingEngine, 'generateHTML');
		dojo.event.connect(dojo.widget.byId('reportSubmitXML'), "onClick", reportingEngine, 'generateXML');
	});

	_container_.addOnUnLoad(function() {
		dojo.event.disconnect(dojo.widget.byId('reportsList'), "onSelect", reportingEngine, 'selectReport');
		dojo.event.disconnect(dojo.widget.byId('reportSubmitCSV'), "onClick", reportingEngine, 'generateCSV');
		dojo.event.disconnect(dojo.widget.byId('reportSubmitHTML'), "onClick", reportingEngine, 'generateHTML');
		dojo.event.disconnect(dojo.widget.byId('reportSubmitXML'), "onClick", reportingEngine, 'generateXML');
	});

</script>

<div dojoType="SplitContainer" orientation="vertical" activesizing="0" layoutAlign="client" sizerWidth="2" style="height: 100%;">

	<div dojoType="ContentPane" layoutAlign="top" sizeShare="60" style="width: 100%; overflow: auto;">

	<h3><!--{t}-->Reporting Engine<!--{/t}--></h3>

	<div class="tableContainer">
		<table dojoType="FilteringTable" id="reportsList" widgetId="reportsList" headClass="fixedHeader" tbodyClass="scrollContent" enableAlternateRows="true" rowAlterateClass="alternateRow" valueField="report_uuid" border="0" multiple="false">
			<thead>
				<tr>
					<th field="report_name" dataType="String"><!--{t}-->Name<!--{/t}--></th>
					<th field="report_desc" dataType="String"><!--{t}-->Description<!--{/t}--></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>

	</div> <!--{* ContentPane for FilteringTable *}-->

	<div dojoType="ContentPane" layoutAlign="bottom" sizeShare="60" style="width: 100%; overflow: auto;">

		<div id="reportEngineForm" style="display: none;">

			<!-- Generated report parameters thrown into this DIV -->
			<div id="reportEngineFormContent" align="center"></div>

			<div id="reportEngineFormStatic" align="center">

				<table border="0" style="width: auto;"><tr>
					<td><div dojoType="Button" id="reportSubmitCSV">CSV</div></td>
					<td><div dojoType="Button" id="reportSubmitHTML">HTML</div></td>
					<td><div dojoType="Button" id="reportSubmitXML">XML</div></td>
				</tr></table>

			</div>

		</div>

	</div>

	<!-- Hidden iFrame for passing reports. ContentPane does not work for this. -->
	<iframe id="reportView" style="display: none;"></iframe>

</div>
