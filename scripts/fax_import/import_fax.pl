#!/usr/bin/perl
# $Id$
#
# Authors:
#      Jeff Buchbinder <jeff@freemedsoftware.org>
#
# FreeMED Electronic Medical Record and Practice Management System
# Copyright (C) 1999-2011 FreeMED Software Foundation
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

# Auto-detect the path for libraries and the FreeMED install
use FindBin;
use lib "$FindBin::Bin/../lib/perl";
my $rootpath = "$FindBin::Bin/..";

# Add proper libraries for XML-RPC access and configuration data
use Frontier::Client;
use Config::IniFiles;

# First, we get the name of the tiff image
my $original = shift || die "No image presented to $0\n";
print "Fax Import ------\n";
print "Original file name = $original\n";

# Get link information from the configuration file. In future, this should
# probably be generated by the FreeMED install wizard as a special XML-RPC
# account, so internal processes can use the same configuration file.
my $config = new Config::IniFiles ( -file => $rootpath.'/data/config/xmlrpc.ini' );

# Make sure we have no leftover pbm or djvu images from a previous run
print "DEBUG: rm -f $original*jpg*\n";
`rm -f $original*jpg*`;
print "DEBUG: unlink $original.djvu\n";
unlink($original.'.djvu');

# From the hylafax-users mailing list:
# 	tifftopnm 1.tif |
# 	pnmscale -xsize 1728 -ysize 2291 |
# 	pgmtopbm |
# 	pnmtotiff -g3 -rowsperstrip 2156 > 1cf.tif
# to work around problem in faxes being sent funny...
#`tifftopnm $original | pnmscale -xsize 1728 -ysize 2291 | pgmtopbm | pnmtotiff -g3 -rowsperstrip 2156 > $original.processed.tif`;


# Run the convert program to process into jpeg images, then process
# each jpeg into pbms. This has to be done, since convert does not
# create multiple pbm images for a multipage tiff.
print "DEBUG: /usr/bin/convert $original -resample 98x98 -scale 1728x2291 $original.%03d.jpg\n";
`/usr/bin/convert $original -resample 98x98 -scale 1728x2291 $original.%03d.jpg`;
print "DEBUG: for i in $original*jpg*; do convert \$i \$i.pbm; done\n";
`for i in $original*jpg*; do convert \$i \$i.pbm; done`;

# Perform optical character recognition on the faxes, to provide a text
# layer for DJVU.

# Stub for now is a touch command
print "DEBIG: for i in $original*jpg*.pbm; do touch \$i.txt; done\n";
`for i in $original*jpg*.pbm; do touch \$i.txt; done`;

# Convert each of the pbms into a Bitonal DJVU document using the
# cjb2 encoder, then use the DJVU Simple EDitor (djvused) to put a
# background layer with the OCR annotations in the image. 
# THIS NEEDS TO BE FIXED; IT CURRENTLY DOES NOT WORK PROPERLY DUE TO
# SYNTAX ISSUES WITH DJVUSED
print "DEBIG: for i in $original*jpg*.pbm; do cjb2 -clean \$i \$i.djvu; djvused \$i.djvu -e 'set-txt \$i.txt'; done\n";
`for i in $original*jpg*.pbm; do cjb2 -clean \$i \$i.djvu; djvused \$i.djvu -e 'set-txt \$i.txt'; done`;

# Now, assemble all of the pages into a single multipage djvu document
print "DEBUG: /usr/bin/djvm -c $original.djvu $original*jpg*djvu\n";
`/usr/bin/djvm -c $original.djvu $original*jpg*djvu`;

# Remove all temporary files
print "DEBUG: rm -f $original*jpg*\n";
`rm -f $original*jpg*`;

# Store the name of the fax file
my $document = $original . '.djvu';
my $local_filename = `basename $document`;
my $path = $config->val('freemed', 'path'); 
print "DEBUG: document = $document, local filename = $local_filename, path = $path\n";

# Check to make sure we don't bork anything from lack of a document ...
if (! -f $document ) {
	print "Document for one reason or another does not exist!";
	exit;
}

# Attach via XML-RPC Lite package, using fax import method
my $client = Frontier::Client->new (
	url => $config->val('freemed', 'url'),
	username => $config->val('freemed', 'username'),
	password => $config->val('freemed', 'password'),
	debug => $config->val('freemed', 'debug')
);
print "Calling FreeMED.Fax.AddLocalFile\n";
my $result = $client->call('org.freemedsoftware.api.Fax.AddLocalFile', $local_filename);
print "DEBUG: esult = "; print($result); print "\n";

# Move document into incoming documents directory
print "Moving document into unfiled documents\n";
`mv "$document" "$path/data/documents/unfiled/" -f`;

# Update permissions for web user
my $webuser = $config->val('freemed', 'webuser');
print "Updating permissions to $webuser\n";
`chown $webuser.$webuser $path/data/documents/unfiled/$local_filename`;

# Remove *original* document (tiff)
print "Removing original\n";
unlink($original);

