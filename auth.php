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
 * Congrea module for authentication
 *
 * @package    mod_congrea
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
/**
 * This function authenticate the user with required
 * detail and request for sever connection
 *
 * @param string $url congrea auth server url
 * @param array $postdata
 * @param string $key
 * @param string $secret
 *
 * @return string $resutl json_encoded object
 */
function congrea_curl_request($url, $postdata, $key, $secret) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
        'x-api-key: ' . $key,
        'x-congrea-secret: ' . $secret,
    ));
    curl_setopt($ch, CURLOPT_TRANSFERTEXT, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXY, false);
    $result = @curl_exec($ch);
    curl_close($ch);
    return $result;
}

// Send auth detail to server.
$authusername = substr(str_shuffle(md5(microtime())), 0, 20);
$authpassword = substr(str_shuffle(md5(microtime())), 0, 20);
$licensekey = $cgapi;
$secret = $cgsecret;
$recording = $recordingstatus;
$userrole = !empty($role) ? $role : 's';
$room = !empty($course->id) && !empty($cm->id) ? $course->id . '_' . $cm->id : 0;
$postdata = array('authuser' => $authusername, 'authpass' => $authpassword, 'role' => $userrole,
            'room' => $room, 'recording' => $recording);
$postdata = json_encode($postdata);
$rid = congrea_curl_request("https://api.congrea.net/backend/auth", $postdata, $licensekey, $secret);
if (!$rid = json_decode($rid)) {
    echo "{\"error\": \"403\"}";
    exit;
} else if (isset($rid->message)) {
    echo "{\"error\": \"$rid->message\"}";
    exit;
} else if (!isset($rid->result)) {
    echo "{\"error\": \"invalid\"}";
    exit;
}
$rid = "wss://$rid->result";
?>
<script type="text/javascript">
<?php echo "var wbUser = {};"; ?>
<?php echo " wbUser.auth_user='" . $authusername . "';"; ?>
<?php echo " wbUser.auth_pass='" . $authpassword . "';"; ?>
<?php echo " wbUser.path='" . $rid . "';"; ?>
<?php echo " wbUser.rm='" . $room . "';"; ?>
<?php echo " wbUser.lkey='" . $licensekey . "';"; ?>
</script>