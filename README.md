# PHP PDF
PHP application to combine multiple PDF files into one. It also support compressions if your recipient does not support files above a certain size. This application does not yet support Compact PDF. It uses different techniques to reduce the size of your PDF file such as removing all OCR and converting all pages to simple images.

## Updates

 * [2022-03-10]: A new logging system has been added.
 * [2022-03-10]: Additional configurations have been added.
 * [2022-03-10]: You can now filter using Regular Expression which attachments to keep.
 * [2022-03-10]: Configuration file moved from settings.json to config/config.json.
 * [2022-03-10]: Added support for languages. Currently English and Francais only. But additional one can be created in dist/languages/.
 * [2022-03-10]: Added support for timezones.

## Planned

 - Adding OCR using TesseractOCR.
 - Adding support for OCR templates.
 - Adding support for document type recognition.
 - Adding installation script.

## Content

 - service.php : a PHP script that reads an IMAP mailbox and combine all PDF(x) and send them back combined.
 - service : a BASH script that executes service.php in a loop.
 - init : a SHELL script to be placed in /etc/init.d/ to launch the service script in the background.
 - config/config.json : A JSON file that stores all scripts settings

## Install
### Requirements
 - Linux
 - Apache2
 - php
 - php-common
 - php-imap
 - php-imagick
 - ghostscript
 - imagegick
 - imagegick-common
 - qpdf
### To Install

#### Install Requirements

```BASH
sudo apt-get install -y apache2 php php-common php-imap php-imagick ghostscript imagemagick imagemagick-common
sudo nano /etc/ImageMagick-6/policy.xml
```

Replace `<policy domain="coder" rights="none" pattern="PDF" />` to `<!-- <policy domain="coder" rights="none" pattern="PDF" /> -->`

#### Setup Service
```BASH
cd /opt/
sudo git clone https://github.com/LouisOuellet/php-pdf.git
sudo ln -s /opt/php-pdf/init /etc/init.d/php-pdf
sudo systemctl daemon-reload
sudo systemctl enable php-pdf
sudo systemctl start php-pdf
```

### Execute
```BASH
php app.php
```
or
```BASH
./service
```

## config/config.json
### Create settings
To create the file simply use your favorite editor and copy/paste the example.

```BASH
nano settings.json
```

 - smtp[MANDATORY]: contains the SMTP configurations
 - imap[MANDATORY]: contains the IMAP configurations
 - destination: contains a static destination email for the service
 - pdf: contains the PDF configurations, currently only support compression settings
 - debug: controls if log information is being sent to terminal and enables error_reporting
 - timezone: set your timezone
 - language: set your language
 - log: enable and set your log file
 - attachments: controls wether to just forward the attachments or merge them. Also include a REGEX pattern.

### Example
```JSON
{
    "debug": false,
    "timezone": "America\/Toronto",
    "language": "english",
    "destination": "default@domain.com",
    "log": {
        "status": "enable",
        "file": "tmp/php-pdf.log"
    },
    "attachments": {
        "merge": false,
        "pattern": "/^md10013/i"
    },
    "smtp":{
        "host": "smtp.domain.com",
        "port": "465",
        "encryption": "SSL",
        "username": "username@domain.com",
        "password": "password"
    },
    "imap":{
        "host": "imap.domain.com",
        "port": "993",
        "encryption": "SSL",
        "isSelfSigned": true,
        "username": "username@domain.com",
        "password": "password"
    },
    "pdf":{
        "scale": 80,
        "maxFileSize": 10000000,
        "compression": false
    }
}
```
