# HOWTO Install

## Requirements
- [PHP](https://www.php.net/downloads) 7.4 or 8.0 or higher
- [inotify extension](https://www.php.net/inotify)
- [Composer](https://getcomposer.org/)

## Install the package
```
cd /opt/
wget --ask-password -O bot-blocker.zip https://git.jeyserver.com/arad/bot-blocker/-/archive/1.0.0/bot-blocker-1.0.0.zip
unzip bot-blocker.zip
cd bot-blocker
composer install
./bin/install-bot-blocker
systemctl start bot-blocker
```