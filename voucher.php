<?php
// Christian Egger, zone11@mac.com
// 2017-02-12

// Composer
require __DIR__ . '/vendor/autoload.php';

// Unifi Class from UniFi API Browser
require("./unifi/class.unifi.php");

// Printer
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\Printer;

// Configs
$unifi_siteid = 'default';
$unifi_username = 'admin';
$unifi_password = 'password';
$unifi_baseurl = 'https://1.2.3.4:8443';
$unifi_wifi_name = 'My WiFi';
$unifi_wifi_title = 'Free as beer!';
$unifi_voucher_note = "POS Voucher using API: ".date("Y.m.d H:i:s");
$unifi_voucher_duration = '1440'; // 1 day

// Prepare Printer and Logo
$connector = new NetworkPrintConnector("1.2.3.4",9100);
$printer = new Printer($connector);
$logo = EscposImage::load("resources/logo.png", false);

// Connect!
$unifi = new unifiapi($unifi_username, $unifi_password, $unifi_baseurl, $unifi_siteid);
$login = $unifi->login();
if ($login == 1) {
	echo("Login: OK!\n");

	//Create Voucher
	$voucher = $unifi->create_voucher($unifi_voucher_time, 1,0,$unifi_voucher_note);
	
	// We got a voucher as array item 0
	if(sizeof($voucher) > 0) {
		$voucherPrint = substr($voucher[0],0,5)."-".substr($voucher[0],5,5);
		echo "Voucher: ".$voucherPrint."\n";

		$printer -> initialize();
		$printer -> setJustification(Printer::JUSTIFY_CENTER);
		$printer -> graphics($logo);
		$printer -> feed();

		$printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
		$printer -> text($unifi_wifi_name);
		$printer -> selectPrintMode();
		$printer -> feed();
		$printer -> text($unifi_wifi_title);
		$printer -> feed(2);

		$printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
		$printer -> text("Voucher");
		$printer -> feed();
		$printer -> text($voucherPrint);
		$printer -> feed(5);

		$printer -> cut();
		$printer -> pulse();

	}
} else {
	echo "Login: FAILED!\n";
}

// Close printer
$printer -> close();
?>
