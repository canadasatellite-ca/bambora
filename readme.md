The Bambora North America (Beamstream) payment module for [canadasatellite.ca](https://www.canadasatellite.ca) (Magento 2).  

## How to install
```posh             
sudo service cron stop           
bin/magento maintenance:enable  
composer require canadasatellite/bambora:* 
rm -rf var/di var/generation generated/*
bin/magento setup:upgrade
bin/magento cache:enable
bin/magento setup:di:compile
bin/magento cache:clean
rm -rf pub/static/*
bin/magento setup:static-content:deploy \
	--area adminhtml \
	--theme Magento/backend \
	-f en_US en_CA es_ES fr_FR pt_BR zh_Hans_CN
bin/magento setup:static-content:deploy \
	--area frontend \
	--theme MageSuper/magestylish \
	-f en_US en_CA es_ES fr_FR pt_BR zh_Hans_CN
bin/magento cache:clean
bin/magento maintenance:disable
sudo service cron start
rm -rf var/log/*
```      

## How to upgrade
```posh              
sudo service cron stop           
bin/magento maintenance:enable  
composer remove canadasatellite/bambora
composer clear-cache
composer require canadasatellite/bambora:*    
rm -rf var/di var/generation generated/*
bin/magento setup:upgrade
bin/magento cache:enable
bin/magento setup:di:compile
bin/magento cache:clean
rm -rf pub/static/*
bin/magento setup:static-content:deploy \
	--area adminhtml \
	--theme Magento/backend \
	-f en_US en_CA es_ES fr_FR pt_BR zh_Hans_CN
bin/magento setup:static-content:deploy \
	--area frontend \
	--theme MageSuper/magestylish \
	-f en_US en_CA es_ES fr_FR pt_BR zh_Hans_CN
bin/magento cache:clean
bin/magento maintenance:disable
sudo service cron start
rm -rf var/log/*
```