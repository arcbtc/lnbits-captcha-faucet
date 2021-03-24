<?php


function collectiveray_load_js_script() {
  wp_enqueue_script( 'js-file', get_template_directory_uri() . '/fau/captcha.js');
}

add_action('wp_enqueue_scripts', 'collectiveray_load_js_script');



function encrypt_decrypt($action, $string)
{
    $output = false;

    $encrypt_method = "AES-256-CBC";
    $secret_key = 'somerandomstring';
    $secret_iv = '';

    // hash
    $key = hash('sha256', $secret_key);

    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv) , 0, 16);

    if ($action == 'encrypt')
    {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    }
    else if ($action == 'decrypt')
    {
        $output = openssl_decrypt(base64_decode($string) , $encrypt_method, $key, 0, $iv);
    }

    return $output;
}

function lnurl_api($data)
{

    /////////GET USER IP/////////
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
    {
        //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $haship = wp_hash(strval($ip) . "jjjjj" . strval($data['lnurl']));
    $variable = encrypt_decrypt('decrypt', $data['lnurl']);

    $response = wp_remote_get($data['server'] . '/withdraw/api/v1/links/' . $haship . '/' . 
    $variable, array(
        'headers' => array(
            'X-Api-Key' => encrypt_decrypt('decrypt', $data['key'])
        )
    ));
    $body = wp_remote_retrieve_body($response);
    $dataa = json_decode($body);
    if ($dataa->{'lnurl'} and !$dataa->{'hash'})
    {

        $getlnurl = wp_remote_get($data['server'] . '/withdraw/api/v1/links/' . $variable, array(
            'headers' => array(
                'X-Api-Key' => encrypt_decrypt('decrypt', $data['key'])
            )
        ));
        $lnurlbody = wp_remote_retrieve_body($getlnurl);
        $lnurldata = json_decode($lnurlbody);

        $LNURL = $lnurldata->{'lnurl'};
        return $LNURL;
    }

    return "error";
}

add_action("rest_api_init", function ()
{
    register_rest_route("lnurl/v1", "/faucet", array(
        "methods" => "POST",
        "callback" => "lnurl_api",
    ));
});


function lnurlcaptcha_function($atts = array())
{
    $a = shortcode_atts(array(
        'server' => '',
        'lnurl' => '',
        'key' => ''
    ) , $atts);
    $LNURL = encrypt_decrypt('encrypt', $a['lnurl']);
    $key = encrypt_decrypt('encrypt', $a['key']);
    $server = $a['server'];
    /////////GET FILE PATHS FOR CAPTCHA/QR/////////
    $jsqr = get_template_directory_uri() . '/fau/qr.js';
    $jscaptcha = get_template_directory_uri() . '/fau/captcha.js';
    $error = get_template_directory_uri() . '/fau/error.svg';
    $cach = get_template_directory_uri() . '/fau/cach.svg';
    return <<<HTML
 <html>
  <style>
    #msg {
      width: 254px;
      text-align: center;
      line-height: 40px;
      font-size: 20px;
    }
  </style>
  <body>
    <div style="height:232px; width:170px;">
      <center><div id="captcha"></div></center>
      <div id="msg"></div>
    </div>
  </body>
  <script type="text/javascript">
    jigsaw.init({
      el: document.getElementById("captcha"),
      imgArray: [],
      onSuccess: function () {
        /////////SHOW PROCESSING/////////
        document.getElementById("msg").innerHTML =
        "Processing...";
        document.getElementById("captcha").innerHTML = "<img style='display:block; width:170px;height:170px;' src='{$cach}'>";
        /////////CHECK IF USER IS NEW/////////
        jQuery.ajax({
        type: "POST",
        url: '/wp-json/lnurl/v1/faucet',
        data: {lnurl: '{$LNURL}', server: '{$server}', key: '{$key}'},
        success: function (obj, textstatus) {
          /////////RETURN LNURL IF IP IS NEW/////////
          if(obj.substring(0, 5) == "LNURL"){
            document.getElementById("captcha").innerHTML = "<img src='https://chart.googleapis.com/chart?chs=170x170&cht=qr&chl=" + obj + "&chld=L|1&choe=UTF-8'>";
            document.getElementById("msg").innerHTML = "Scan with bitcoin lightning wallet";
          }
          else{
            document.getElementById("msg").innerHTML = "Faucet already claimed!";
            document.getElementById("captcha").innerHTML = "<img style='display:block; width:170px;height:170px;' src='{$error}'>";
          }     
        }
        });
      },
      onFail: cleanMsg,
      onRefresh: cleanMsg,
    });
    function cleanMsg() {
    }
  </script>
</html>
HTML;
    
}
add_shortcode('faucet', 'lnurlcaptcha_function');

?>
