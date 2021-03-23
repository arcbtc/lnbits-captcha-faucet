# lnbits-captcha-faucet
## Wordpress captcha widget shortcode lnbits faucet
<div style="display: inline-block; width: 100%;">
<img style="float:left;" src="https://i.imgur.com/rcho8fa.png">
<img style="float:right;" src="https://i.imgur.com/DxQphoE.png">
</div>

## Installation
Upload these files/folders to your wordpress theme

Add ```include('faucet_shortcode.php');``` to your themes ```functions.php``` (before ```?>```)

To use in a post/page use the shortcode: ```[faucet server="https://lnbits.com" lnurl="3psjRpvSose66yFYyVrN6Q" key="11b60d70f83c473089e9c1eed2511f44"]```

* server: an LNbits server
* key: LNbits wallet invoice key attached to lnurl-withdraw
* lnurl: lnurl-withdraw id generated in LNbits



