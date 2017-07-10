<?php
function my_curl_request($url, $post_data, $key){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_HTTPHEADER,
					array('Content-Type: application/json',
					'x-api-key: ' . $key,
				  ));
	curl_setopt($ch, CURLOPT_TRANSFERTEXT, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_PROXY, false);
	$result = @curl_exec($ch);
	curl_close($ch);
	return $result;
}

if (!$licen = get_config('local_getkey', 'keyvalue')) {
    print_error('You must specify Congrea API key');
    exit;
}

//send auth detail to server
$authusername = substr(str_shuffle(MD5(microtime())), 0, 20);
$authpassword = substr(str_shuffle(MD5(microtime())), 0, 20);

$postdata = array('authuser' => $authusername, 'authpass' => $authpassword);
$postdata = json_encode($postdata);

$rid = my_curl_request("https://api.congrea.com/auth", $postdata, $licen); // REMOVE HTTP.

if (!$rid = json_decode($rid)) {
	echo "{\"error\": \"403\"}";exit;
} elseif (isset($rid->message)) {
	echo "{\"error\": \"$rid->message\"}";exit;
} elseif (!isset($rid->result)) {
	echo "{\"error\": \"invalid\"}";exit;
}

$rid = "wss://$rid->result";

?>

<script type="text/javascript">
<?php echo "var wbUser = {};";?>
<?php echo " wbUser.auth_user='".$authusername."';"; ?>
<?php echo " wbUser.auth_pass='".$authpassword."';"; ?>
<?php echo " wbUser.path='".$rid."';";?>
</script>


