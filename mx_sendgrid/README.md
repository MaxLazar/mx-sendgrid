# MX SendGrid

SendGrid mailer adapter for ExpressionEngine.

## Installation
* Download the latest version of MX SendGrid and extract the .zip to your desktop.
* Copy *inline* to */system/user/addons/*

## Compatibility

* ExpressionEngine 4
* ExpressionEngine 5
* ExpressionEngine 6

## Configuration

**apikey**

[SendGrid API Key](https://sendgrid.com/docs/ui/account-and-settings/api-keys/)

## Configuration Overrides

**Main configuration file**

The main configuration file, found at system/user/config/config.php, is loaded every time the system is run, meaning that config overrides set in config.php always affect the system’s configuration.

	$config['mx_sendgrid'] = [
	        'enable'   => true,
	        'apikey'  => ''
	];


## Support Policy
This is Communite Edition add-on.

## Contributing To MX SendGrid for ExpressionEngine

Your participation to MX SendGrid development is very welcome!

You may participate in the following ways:

* [Report issues](https://github.com/MaxLazar/mx-sendgrid/issues)
* Fix issues, develop features, write/polish documentation
Before you start, please adopt an existing issue (labelled with "ready for adoption") or start a new one to avoid duplicated efforts.
Please submit a merge request after you finish development.

# Thanks to

* [A PHP email parser](https://mail-mime-parser.org/)

### License

The MX SendGrid is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
