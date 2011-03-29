#!/bin/bash

sudo chmod 777 * -R
rm www/.tmb/ -rf
rm www/application/data/cache/* -rf
rm www/application/data/session/* -rf
tar -czf ../zcmf.tar.gz www/