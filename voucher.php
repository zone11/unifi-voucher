<?php
// Christian Egger, zone11@mac.com
// 2017-04-17
// 
// - Added POST variable for duration.
// - Added duration on printing

// Composer
require __DIR__ . '/vendor/autoload.php';

// Unifi Class from UniFi API Browser
require("./unifi/class.unifi.php");

// Config
require("./config.php");

// POST Data
if( !isset($_POST['duration']) ) {
	echo ("POST: duration is missing!");
	exit();
} else {
	$voucherDuration = intval($_POST['duration']); // in hours
}

// Printer
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\Printer;

// Prepare Printer and Logo
$printerConnector = new NetworkPrintConnector($cfgPrinterNetIP, $cfgPrinterNetPort);
$printer = new Printer($printerConnector);
$logo = EscposImage::load($cfgPrintLogoFile, false);

// Connect!
$unifi = new unifiapi($cfgUnifiUsername, $cfgUnifiPassword, $cfgUnifiBaseurl, $cfgUnifiSiteid);
$login = $unifi->login();
if ($login == 1) {
	// Debug
	echo("Login: OK!\n");

	//Create Voucher
	$voucher = $unifi->create_voucher(($voucherDuration*60),1,0,$cfgUnifiVoucherNote);
	
	// We got a voucher as array item 0
	if(sizeof($voucher) > 0) {
		// Form strings for printing
		$voucherCodePrint = substr($voucher[0],0,5)."-".substr($voucher[0],5,5);
		$voucherDurationPrint = floor($voucherDuration/24);
		
		// Add day/days to Duration
		if ($voucherDurationPrint > 1) {
			$voucherDurationPrint .= " ".$cfgPrintTextDays;
		} else {
			$voucherDurationPrint .= " ".$cfgPrintTextDay;
		}
		
		// Debug
		echo "Code: ".$voucherCodePrint."\n";
		echo "Duration: ".$voucherDurationPrint."\n";
		
		// Init printer
		$printer -> initialize();
		
		// Logo centered
		$printer -> setJustification(Printer::JUSTIFY_CENTER);
		$printer -> graphics($logo);
		$printer -> feed();
		
		// Name and title
		$printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
		$printer -> text($cfgPrintStrWifiName);
		$printer -> selectPrintMode();
		$printer -> feed();
		$printer -> text($cfgPrintStrWifiTitle);
		$printer -> feed(2);
		
		// Voucher code
		$printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
		$printer -> text($cfgPrintTitleVoucher);
		$printer -> feed();
		$printer -> text($voucherCodePrint);
		$printer -> feed(2);
				
		// Duration
		$printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
		$printer -> text($cfgPrintTitleDuration);
		$printer -> feed();
		$printer -> text($voucherDurationPrint);
		$printer -> feed(5);

		// Cut and finish
		$printer -> cut();
		$printer -> pulse();
	}
} else {
	echo "Login: FAILED!\n";
}

// Close printer
$printer -> close();
?>
