#!/bin/bash

if [ -v SMTP_ROOT ]
then
    echo "root=${SMTP_ROOT}" >> /etc/ssmtp/ssmtp.conf
fi

if [ -v SMTP_MAILHUB ]
then
    echo "mailhub=${SMTP_MAILHUB}" >> /etc/ssmtp/ssmtp.conf
fi

if [ -v SMTP_EMAIL ]
then
    echo "AuthUser=${SMTP_EMAIL}" >> /etc/ssmtp/ssmtp.conf
fi

if [ -v SMTP_PASSWORD ]
then
    echo "AuthPass=${SMTP_PASSWORD}" >> /etc/ssmtp/ssmtp.conf
fi

if [ -v SMTP_DOMAIN ]
then
    echo "FromLineOverride=yes" >> /etc/ssmtp/ssmtp.conf
    echo "rewriteDomain=${SMTP_DOMAIN}" >> /etc/ssmtp/ssmtp.conf
fi

if [ ! -v SMTP_NOTLS ]
then
    echo "UseTLS=YES" >> /etc/ssmtp/ssmtp.conf
    echo "UseSTARTTLS=YES" >> /etc/ssmtp/ssmtp.conf
fi

# Set up php sendmail config
echo "sendmail_path=sendmail -i -t" >> /usr/local/etc/php/conf.d/php-sendmail.ini

if [ ! -v COMPOSER_NO_INSTALL ]
then
    eval "composer install --prefer-source --no-interaction"
fi

echo "baby poo"

exec "php-fpm"



