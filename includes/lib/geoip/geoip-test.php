<?php

// include the php script
include("geoip.inc");

// open the geoip database
$gi = geoip_open("GeoIP.dat",GEOIP_STANDARD);

// to get country code
$country_code = geoip_country_code_by_addr($gi, $_SERVER['REMOTE_ADDR']);
echo "Your country code is: $country_code \n";

// to get country name
$country_name = geoip_country_name_by_addr($gi, $_SERVER['REMOTE_ADDR']);
echo "Your country name is: $country_name \n";

// close the database
geoip_close($gi);

?>