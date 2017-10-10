<?php
// Christian Egger, zone11@mac.com
// 2017-10-09
// 
// - New UniFi API
// - Printer Exception


// Composer
require __DIR__ . '/vendor/autoload.php';

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

// Connect Printer Socket
try {
	$printerConnector = new NetworkPrintConnector($cfgPrinterNetIP, $cfgPrinterNetPort,5);
} catch (Exception $e) {
	echo('Printer Exception: '.$e->getMessage()."\n");
	exit();
}

// Prepare Printer and Logo
$printer = new Printer($printerConnector);
$logo = EscposImage::load($cfgPrintLogoFile, false);

// unifiapi
$unifi = new UniFi_API\Client($cfgUnifiUsername, $cfgUnifiPassword, $cfgUnifiBaseurl, $cfgUnifiSiteid, false);
$unifi->set_debug(false);
$login = $unifi->login();

if ($login == 1) {
	// Debug
	echo("Login: OK!<br>\n");

	//Create Voucher
	$voucher_timestamp = $unifi->create_voucher(($voucherDuration*60),1,0,$cfgUnifiVoucherNote);
	$voucher_timestamp_int = $voucher_timestamp[0]->create_time;
	echo "Voucher Timestamp: ".$voucher_timestamp_int."<br>\n";
	
	$voucher = $unifi->stat_voucher($voucher_timestamp_int);
	
	// We got a voucher as array item 0
	if(sizeof($voucher) > 0) {
		// Form strings for printing
		$voucherCodePrint = substr($voucher[0]->code,0,5)."-".substr($voucher[0]->code,5,5);
		$voucherDurationPrint = floor($voucherDuration/24);
		
		// Add day/days to Duration
		if ($voucherDurationPrint > 1) {
			$voucherDurationPrint .= " ".$cfgPrintTextDays;
		} else {
			$voucherDurationPrint .= " ".$cfgPrintTextDay;
		}
		
		// Debug
		echo "Code: ".$voucherCodePrint."<br>\n";
		echo "Duration: ".$voucherDurationPrint."<br>\n";
		
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
