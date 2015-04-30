#!/usr/bin/perl
use File::Copy;
$naviname=$ARGV[0];
$filename=$ARGV[1];
chomp($ARGV[1]);
copy($filename, $filename.".bak");
open NAVI, "<".$naviname; 
open HTML, "<".$filename;
open TMP, ">temp.htm";
while (defined ($_=<HTML>)){
	s/\s+$//;
	print TMP $_."\n";
	if ($_ eq "<!-- Placeholder Begin Menu -->") {last}
}
if ($_  ne "<!-- Placeholder Begin Menu -->"){
	close TMP;
	close HTML;
	close NAVI;
	exit;
}
while (defined ($_=<HTML>)){
	s/\s+$//;
	if ($_ eq "<!-- Placeholder End Menu -->") {last}
}
while (defined ($_=<NAVI>)){
	@a = split(/;/,$_);
	chomp($a[1]);
	if ($a[0] eq $filename) {
    	print TMP "<li><A id=\"sichtbar\" HREF=\"".$a[0]."\">&gt;&gt;".$a[1]." </A></li>\n";
	}else{
    	print TMP "<li><A HREF=\"".$a[0]."\">&gt;&gt;".$a[1]." </A></li>\n";		
	}
}
print TMP "\n<!-- Placeholder End Menu -->\n";
while (defined ($_=<HTML>)){
	print TMP $_;
}
close TMP;
close HTML;
close NAVI;
copy("temp.htm", $filename);
