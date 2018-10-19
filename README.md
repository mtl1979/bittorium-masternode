### How To Install

DISCLAIMER: This guide assumes basic knowledge of git and Apache configuration

#### Prerequisites

- You will need virtual private server or dedicated PC running Ubuntu 16.04 with minimum of 2 GB physical memory and 2 GB swap, or 4 GB physical memory
- You will need the following packages: boost (1.55 or higher), cmake, git, gcc (4.9 or higher), g++ (4.9 or higher), make, python, nano, apache2, curl, php, php-curl, libapache2-mod-php and python-certbot-apache.
```
apt-get -y install build-essential python-dev gcc g++ git cmake libboost-all-dev libgflags-dev libsnappy-dev zlib1g-dev libbz2-dev liblz4-dev libzstd-dev nano apache2 curl php php-curl libapache2-mod-php
```
- To get python-certbot-apache, you need to run as root user:
```
add-apt-repository ppa:certbot/certbot
apt-get update
apt-get install python-certbot-apache
```

#### Directory structure

- Run again as root user:
```
cd /
mkdir masternode
mkdir masternode/data
mkdir masternode/daemon
mkdir masternode/website
```

#### Compiling daemon
- Run following commands
```
cd /masternode
git clone https://github.com/mtl1979/bittorium.git daemon
cd daemon
git checkout masternode
mkdir build
cd build
cmake -DDO_TESTS=OFF ..
cmake --build .
```

#### Configuring daemon
- Change to directory containing daemon binaries:
```
cd /masternode/daemon/build/src
screen ./Bittoriumd --p2p-bind-port 34903 --rpc-bind-port 34917 --data-dir /masternode/data
```
- Wait until daemon has synced the blockchain, then press CTRL-A, then press CTRL-D to detach the screen session
- Type ```./simplewallet --remote-daemon 127.0.0.1:34917```
- When simplewallet starts, it asks what you want to do, you will have to select "G" to generate a new wallet
- When it asks for wallet name, answer ```masternode```
- Write down the wallet password it asks and wallet address it prints after generating is finished
- Type ```export_keys``` and write down both private view and spend keys, you will need the view key later in this guide
- Type ```exit``` to close simplewallet
- Move the wallet file to /masternode/data
```
mv masternode.wallet /masternode/data
```
- Type ```screen -r``` to return to daemon, type ```exit``` to shutdown the daemon
- Type ```nano start.sh``` to create file containing following text:
```
#!/bin/bash
screen -S masternode-daemon -d -m /masternode/daemon/build/src/Bittoriumd --p2p-bind-ip 0.0.0.0 --p2p-bind-port 34903 --rpc-bind-ip 0.0.0.0 --rpc-bind-port 34917 --enable-cors "*" --data-dir /masternode/data --fee-address wallet-address --view-key private-view-key
screen -S masternode-walletd -d -m /masternode/daemon/build/src/walletd --container-file /masternode/data/masternode.wallet --container-password wallet-password --daemon-port 34917 --bind-port 8071 --rpc-password your-rpc-password
```
- Replace ```wallet-address``` with the address you wrote down earlier
- Replace ```private-view-key``` with the private view key of the wallet you created
- Replace ```wallet-password``` with the password you wrote down earlier
- Replace ```your-rpc-password``` with any password, but write it down again
- Press CTRL-O to save the file and CTRL-X to close nano
- Give the script execution permission:
```
chmod +x start.sh
```

#### Starting the daemons
- To start both daemons, run:
```
/masternode/daemon/build/src/start.sh
```

#### Configuring frontend
- Change to /masternode again
```
cd /masternode
git clone https://github.com/mtl1979/bittorium-masternode.git website
cd website
nano config.php
```
- Change the contents of ```$daemonHost``` variable in the file to your external IPv4 address
- Change the contents of ```$walletPassword``` variable in the file to what you used as ```your-rpc-password``` above
- Press CTRL-O and CTRL-X to exit nano
- Change to Apache configuration directory and edit ```apache2.conf```:
```
cd /etc/apache2
nano apache2.conf
```
- Find text block containing
```
<Directory /var/www/>
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
</Directory>
```
- After that add the following text:
```

<Directory /masternode/website/>
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
</Directory>
```
- Save the file and exit nano
- Change to Apache site configurations directory
```
cd /etc/apache2/sites-available
ls
```
- Make note of the largest number used in the filenames...
- Create file NNN-masternode.conf, where NNN is one more than the highest existing number, for example 
```
nano 001-masternode.conf
```
- Add following lines to the file:
```
<VirtualHost masternode.mydomain.com:80>
	ServerName masternode.mydomain.com
	ServerAdmin admin@mydomain.com
	DocumentRoot /masternode/website

	ErrorLog ${APACHE_LOG_DIR}/error.log
	CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```
- Save the file and exit nano
- Enable the configuration:
```
cd ../sites-enabled
ln -s ../sites-available/001-masternode.conf 001-masternode.conf
```
- Install SSL certificate for the masternode
```
certbot --apache -d masternode.mydomain.com
```
- Edit the generated configuration file, for example: 
```
nano 001-masternode-le-ssl.conf
```
- After line containing ```CustomLog```, add the following lines:
```

        RewriteEngine on
        LogLevel alert rewrite:trace3

        <Directory /masternode/website/>
          Options +FollowSymLinks
          RewriteRule ^config.php /index.php [R]
        </Directory>

        <Directory /masternode/website/api/>
          Header set Access-Control-Allow-Origin "*"
          Options +FollowSymLinks
          RewriteCond %{DOCUMENT_ROOT}/$1 !-f
          RewriteRule ^([a-z]+)$ $1.php
        </Directory>
```
- Save the file and exit nano
- Enable ```headers``` and ```rewrite``` modules on Apache:
```
a2enmod headers rewrite
apachectl -t
```
- If apachectl gives only warnings and no errors, restart Apache:
```
service apache2 restart
```
- Try to open your frontend using browser by visiting ```https://masternode.mydomain.com/```
- When you are all done, it should print:
```
Daemon address: 111.222.333.444:34917
Status: OK
Collected fees: 0.00 BTOR
```
- ```111.222.333.444``` should be your external IPv4 address as you entered for ```$daemonHost``` in ```config.php```
- Try visiting ```https://masternode.mydomain.com/api/fees```
- It should print formatted as JSON, variable "status" with value "OK", it won't print collected fees until there is at least one relayed transaction
```
{
"status" : "OK"
}
```
