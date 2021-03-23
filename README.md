# lnbits-captcha-faucet
## Little captcha widget for lnbits faucet :)
<div style="display: inline-block; width: 100%;">
<img style="float:left;" src="https://i.imgur.com/rcho8fa.png">
<img style="float:right;" src="https://i.imgur.com/DxQphoE.png">
</div>

<a href="http://jigawatt.co/wordpress-bitcoin-lnurl-withdraw-faucet-with-captcha/">Demo</a>

## Installation
Upload these files/folders to your wordpress theme

Add ```include('faucet_shortcode.php');``` to that bottom of your themes ```functions.php```

To use in a post/page use the shortcode: ```[faucet server="https://lnbits.com" lnurl="3psjRpvSose66yFYyVrN6Q" key="11b60d70f83c473089e9c1eed2511f44"]```

* server: LNbits server
* key: LNbits wallet invoice key attached to lnurl-withdraw
* lnurl: lnurl-withdraw id generated in LNbits
