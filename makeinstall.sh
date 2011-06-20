#!/bin/bash


sudo chmod 777 * -R
rm www/.tmb/ -rf
rm www/application/data/cache/* -rf
rm www/application/data/session/* -rf
#mysqldump -uroot -proot --compress=true --compact=true --skip-comments --extended-insert=false zcmf > www/application/data/install/dump.sql
mysqldump -uroot -proot --extended-insert=false zcmf > www/application/data/install/dump.sql
tar -czf ../`echo ${PWD##*/}`_`date +%Y_%m_%d`.tar.gz ./www