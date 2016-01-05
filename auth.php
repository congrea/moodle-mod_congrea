<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of congrea
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_congrea
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function mod_congrea_mycurlrequest($url, $postdata) {
	global $CFG;
	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HEADER, 'content-type: text/plain;');
    curl_setopt($ch, CURLOPT_TRANSFERTEXT, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXY, false);
    //curl_setopt($ch, CURLOPT_SSLVERSION, 1);
    curl_setopt($ch, CURLOPT_CAINFO, "$CFG->libdir/cacert.pem");

    $result = @curl_exec($ch);
    if ($result === false) {
        echo 'Curl error: ' . curl_error($ch);
        exit;
    }
    curl_close($ch);
    return $result;
}

if (!$licen = get_config('local_getkey', 'keyvalue')) {
    print_error('You must specify Congrea API key');
    exit;
}
// Send auth detail to server
$authusername = substr(str_shuffle(md5(microtime())), 0, 12);
$authpassword = substr(str_shuffle(md5(microtime())), 0, 12);
$postdata = array('authuser' => $authusername, 'authpass' => $authpassword, 'licensekey' => $licen);
$postdata = json_encode($postdata);

if (true) { // False for local server deployment
    $rid = mod_congrea_mycurlrequest("https://c.vidya.io", $postdata); // REMOVE HTTP.
    if (empty($rid) or strlen($rid) > 32) {
        print_error("Chat server is unavailable!");
        exit;
    } else if(substr($rid, -9) !== '.vidya.io') {
        print_error($rid);
        exit;
    }
    $rid = "wss://$rid";
} else {
    $rid = "ws://127.0.0.1:8080";
}
?>
<script type="text/javascript">
<?php echo "var wbUser = {};";?>
<?php echo " wbUser.auth_user='".$authusername."';"; ?>
<?php echo " wbUser.auth_pass='".$authpassword."';"; ?>
<?php echo " wbUser.path='".$rid."';";?>
</script>
