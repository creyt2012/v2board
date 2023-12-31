<?php
function get_rkey($length) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$size = strlen($chars);
	$str = '';
	for ($i = 0; $i < $length; $i++) {
		$str .= $chars[rand(0, $size - 1)];
	}
	return $str;
}

function encryptDecrypt($data, $key, $mode = 'ENCRYPT') {
	if (strlen($key) < 32) {
		$key = str_pad($key, 32, 'x');
	}
	$key = substr($key, 0, 32);
	$iv = pack('C*', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	if ($mode === 'ENCRYPT') {
		return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv));
	} else {
		return openssl_decrypt(base64_decode($data), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
	}
}
function get_microtime() {
	return floor(microtime(true) * 1000);
}
function get_checksum($data, $type) {
	$checkSumSyntax = $data['phone'] . get_microtime() . '000000' . $type . (get_microtime() / 1000000000000.0) . 'E12';
	return encryptDecrypt($checkSumSyntax, encryptDecrypt($data['setupkey'], $data['ohash'], 'DECRYPT'));
}
function get_pHash($data) {
	$pHashSyntax = $data['imei'] . '|' . $data['password'];
	return encryptDecrypt($pHashSyntax, encryptDecrypt($data['setupkey'], $data['ohash'], 'DECRYPT'));
}
function get_imei() {
	$time = md5(get_microtime());
	$text = substr($time, 0, 8) . '-';
	$text .= substr($time, 8, 4) . '-';
	$text .= substr($time, 12, 4) . '-';
	$text .= substr($time, 16, 4) . '-';
	$text .= substr($time, 17, 12);
	$text = strtoupper($text);
	return $text;
}

function get_onesignal() {
	$time = md5(get_microtime() + time());
	$text = substr($time, 0, 8) . '-';
	$text .= substr($time, 8, 4) . '-';
	$text .= substr($time, 12, 4) . '-';
	$text .= substr($time, 16, 4) . '-';
	$text .= substr($time, 17, 12);
	return $text;
}
function CurlMomo($url, $header, $data_post, $type = "GET") {
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => $type,
				CURLOPT_POSTFIELDS => json_encode($data_post),
				CURLOPT_HTTPHEADER => $header,
			));
			$response = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			if (empty($response)) {
				return $http_code;
			}
	return $response;
}

function GET_OTP($data) {
	$url = "https://owa.momo.vn/public";
	$data_body = [
		'user' => $data['phone'],
		'msgType' => 'SEND_OTP_MSG',
		'cmdId' => get_microtime() . '000000',
		'lang' => "vi",
		'channel' => "APP",
		'time' => get_microtime(),
		'appVer' => "30090",
		'appCode' => "3.0.9",
		'deviceOS' => "IOS",
		'result' => true,
		'errorCode' => 0,
		'errorDesc' => '',
		'extra' => [
			'action' => 'SEND',
			'rkey' => $data['rkey'],
			'AAID' => '',
			'IDFA' => '',
			'TOKEN' => '',
			'ONESIGNAL_TOKEN' => $data['onesignal'],
			'SIMULATOR' => 'false',
			'isVoice' => 'true',
			'REQUIRE_HASH_STRING_OTP' => false,
		],
		'momoMsg' => [
			'_class' => 'mservice.backend.entity.msg.RegDeviceMsg',
			'number' => $data['phone'],
			'imei' => $data['imei'],
			'cname' => 'Vietnam',
			'ccode' => '084',
			'device' => 'iPhone',
			'firmware' => '13.5.1',
			'hardware' => 'iPhone',
			'manufacture' => 'Apple',
			'csp' => 'Viettel',
			'icc' => '',
			'mcc' => '452',
			'mnc' => '04',
			'device_os' => 'IOS',
		],
	];
	$header = array(
		'User-Agent' => "MoMoApp-Release/%s CFNetwork/978.0.7 Darwin/18.6.0",
		'Msgtype' => "USER_LOGIN_MSG",
		'Accept' => 'application/json',
		'Content-Type' => 'application/json',
		'Userhash' => md5($data['phone']),
	);
	$response = CurlMomo($url, $header, $data_body, "POST");
	return $response;
}

function Check_OTP($data) {
	$data_body = [
		'user' => $data['phone'],
		'msgType' => "REG_DEVICE_MSG",
		'cmdId' => get_microtime() . '000000',
		'lang' => "vi",
		'channel' => "APP",
		'time' => get_microtime(),
		'appVer' => "30090",
		'appCode' => "3.0.9",
		'deviceOS' => "IOS",
		'result' => true,
		'errorCode' => 0,
		'errorDesc' => '',
		'extra' => [
			'ohash' => hash('sha256', $data['phone'] . $data['rkey'] . $data['otp']),
			'AAID' => '',
			'IDFA' => '',
			'TOKEN' => '',
			'ONESIGNAL_TOKEN' => $data['onesignal'],
			'SIMULATOR' => 'false',
		],
		'momoMsg' => [
			'_class' => 'mservice.backend.entity.msg.RegDeviceMsg',
			'number' => $data['phone'],
			'imei' => $data['imei'],
			'cname' => 'Vietnam',
			'ccode' => '084',
			'device' => 'iPhone',
			'firmware' => '13.5.1',
			'hardware' => 'iPhone',
			'manufacture' => 'Apple',
			'csp' => 'Viettel',
			'icc' => '',
			'mcc' => '452',
			'mnc' => '04',
			'device_os' => 'IOS',
		],
	];
	$url = "https://owa.momo.vn/public";
	$header = array(
		'User-Agent' => "MoMoApp-Release/%s CFNetwork/978.0.7 Darwin/18.6.0",
		'Msgtype' => "USER_LOGIN_MSG",
		'Accept' => 'application/json',
		'Content-Type' => 'application/json',
		'Userhash' => md5($data['phone']),
	);
	$response = CurlMomo($url, $header, $data_body, "POST");
	return $response;
}
function LoginMomoomo($data) {
	$data_body = [
		'user' => $data['phone'],
		'pass' => $data['password'],
		'msgType' => 'USER_LOGIN_MSG',
		'cmdId' => get_microtime() . '000000',
		'lang' => "vi",
		'channel' => "APP",
		'time' => get_microtime(),
		'appVer' => "30090",
		'appCode' => "3.0.9",
		'deviceOS' => "IOS",
		'result' => true,
		'errorCode' => 0,
		'errorDesc' => '',
		'extra' => [
			'checkSum' => get_checksum($data, 'USER_LOGIN_MSG'),
			'pHash' => get_pHash($data),
			'AAID' => '',
			'IDFA' => '',
			'TOKEN' => '',
			'ONESIGNAL_TOKEN' => $data['onesignal'],
			'SIMULATOR' => false,
		],
		'momoMsg' => [
			'_class' => 'mservice.backend.entity.msg.LoginMsg'
			, 'isSetup' => true,
		],
	];
	$url = "https://owa.momo.vn/public";
	$header = array(
		'User-Agent' => "MoMoApp-Release/%s CFNetwork/978.0.7 Darwin/18.6.0",
		'Msgtype' => "USER_LOGIN_MSG",
		'Accept' => 'application/json',
		'Content-Type' => 'application/json',
		'Userhash' => md5($data['phone']),
	);
	$response = CurlMomo($url, $header, $data_body, "POST");
	if (empty($response)) {
		return false;
	}
	return $response;
}
function HistoryMomo($data, $hours = 7) {
		$data_post = [
			'user' => $data['phone'],
			'msgType' => 'QUERY_TRAN_HIS_MSG',
			'cmdId' => get_microtime() . '000000',
			'lang' => "vi",
			'channel' => "APP",
			'time' => get_microtime(),
			'appVer' => "30000",
			'appCode' => "3.0.0",
			'deviceOS' => "IOS",
			'result' => true,
			'errorCode' => 0,
			'errorDesc' => '',
			'extra' => [
				'checkSum' => get_checksum($data, "QUERY_TRAN_HIS_MSG"),
			],
			'momoMsg' => [
				'_class' => 'mservice.backend.entity.msg.QueryTranhisMsg',
				'begin' => (time() - (3600 * $hours)) * 1000,
				'end' => get_microtime(),
			],
		];
		$url = "https://owa.momo.vn/api/sync/QUERY_TRAN_HIS_MSG";
		$header = array(
			'User-Agent' => "MoMoApp-Release/%s CFNetwork/978.0.7 Darwin/18.6.0",
			'Msgtype' => "QUERY_TRAN_HIS_MSG",
			'Userhash' => md5($data['phone']),
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'Authorization: Bearer ' . trim($data['auth_token']),
		);
		$result = CurlMomo($url, $header, $data_post, "POST");
		if (empty($result)) {
			return false;
		}
		return $result;
}