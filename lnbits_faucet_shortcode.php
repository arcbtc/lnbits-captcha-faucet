<?php

function encrypt_decrypt($action, $string)
{
    $output = false;

    $encrypt_method = "AES-256-CBC";
    $secret_key = 'sdfgndfmndfhmdfhmd';
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
    $haship = wp_hash(strval($ip) + encrypt_decrypt('decrypt', $data['lnurl']));
    $variable = encrypt_decrypt('decrypt', $data['lnurl']);

    $response = wp_remote_get($data['server'] . '/withdraw/api/v1/links/' . $haship . '/' . 
    $variable, array(
        'headers' => array(
            'X-Api-Key' => encrypt_decrypt('decrypt', $data['key'])
        )
    ));
    $body = wp_remote_retrieve_body($response);
    $dataa = json_decode($body);
    if (!$dataa->{'lnurl'} and $dataa->{'hash'})
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
    $jsqr = get_template_directory_uri() . '/js/qr.js';
    $jscaptcha = get_template_directory_uri() . '/js/captcha.js';
    $stop = get_template_directory_uri() . '/js/stop.js';
    $faucetapi = get_template_directory_uri() . '/faucet_api.php';

    return <<<HTML
 <html>
  <style>
    input {
      display: block;
      width: 290px;
      line-height: 40px;
      margin: 10px 0;
      padding: 0 10px;
      outline: none;
      border: 1px solid #c8cccf;
      border-radius: 4px;
      color: #6a6f77;
    }
    #msg {
      width: 100%;
      line-height: 40px;
      font-size: 14px;
      text-align: center;
    }
    a:link,
    a:visited,
    a:hover,
    a:active {
      margin-left: 100px;
      color: #0366d6;
    }
  </style>
  <script type="text/javascript" src="{$jscaptcha}"></script>
  <script type="text/javascript" src="{$jsqr}"></script>
  <script type="text/javascript" src="{$stop}"></script>
  <body>
    {$faucetapi}
    <center><div id="captcha" style="margin: 0px !important"></div></center>
    <div id="msg"></div>
  </body>
  <script type="text/javascript">
    jigsaw.init({
      el: document.getElementById("captcha"),
      imgArray: [],
      onSuccess: function () {
      /////////CHECK IP/////////
      jQuery.ajax({
      type: "POST",
      url: '/wp-json/lnurl/v1/faucet',
      data: {lnurl: '{$LNURL}', server: '{$server}', key: '{$key}'},
      success: function (obj, textstatus) {
      /////////RETURN LNURL IF IP IS NEW/////////
        console.log(obj);
        if(obj.substring(0, 5) == "LNURL"){
          var typeNumber = 15;
          var errorCorrectionLevel = "H";
          var qr = qrcode(typeNumber, errorCorrectionLevel);
          qr.addData(obj);
          qr.make();
          document.getElementById(
               "captcha"
          ).innerHTML = qr.createImgTag().link("lightning://" + obj);
          document.getElementById("msg").innerHTML =
          "Scan with bitcoin lightning wallet";
        }
        else{
          document.getElementById("msg").innerHTML =
          "Faucet already claimed!";
          document.getElementById("captcha").innerHTML = "<img style='display:block; width:170px;height:170px;' id='base64image' src='" + stop + "'>";
        }    
      }
      });
      },
      onFail: cleanMsg,
      onRefresh: cleanMsg,
    });
    function cleanMsg() {
      document.getElementById("msg").innerHTML = "Faucet already claimed!";
      document.getElementById("captcha").innerHTML = "";
    }
  </script>
</html>
HTML;
    
}
add_shortcode('faucet', 'lnurlcaptcha_function');

?>
