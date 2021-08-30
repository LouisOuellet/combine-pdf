# Combine PDF
PHP application to combine multiple PDF files into one

## Content

 - combine.php : a PHP script that reads an IMAP mailbox and combine all PDF(x) and send them back combined.
 - service : a BASH script that executes combine.php in a loop.
 - init : a SHELL script to be placed in /etc/init.d/ to launch the service script in the background.
 - settings.json : A JSON file that stores all scripts settings

## Install
### To Install

```BASH
cd /opt/
sudo git clone https://github.com/LouisOuellet/combine-pdf.git
sudo ln -s /opt/combine-pdf/init /etc/init.d/combine-pdf
sudo systemctl daemon-reload
sudo systemctl enable combine-pdf
sudo systemctl start combine-pdf
```

## combine.php
### Requirements

 - Linux
 - Apache2
 - php
 - php-common
 - php-imap
 - php-imagick
 - ghostscript

### Execute

```BASH
php combine.php
```

## service
### Execute

```BASH
./service
```

## init
### Configure service

```BASH
sudo ln -s /opt/combine-pdf/init /etc/init.d/combine-pdf
sudo systemctl daemon-reload
sudo systemctl enable combine-pdf
sudo systemctl start combine-pdf
```

## settings.json
### Create settings
To create the file simply use your favorite editor and copy/paste the example.

```BASH
nano settings.json
```

 - smtp[MANDATORY]: contains the SMTP configuration
 - imap[MANDATORY]: contains the IMAP configuration
 - destination: contains a static destination email


### Example
```JSON
{
    "destination": "default@domain.com",
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
    }
}
```
