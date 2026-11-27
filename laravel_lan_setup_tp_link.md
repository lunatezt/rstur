# Laravel LAN Deployment with Laragon & TP-Link Router

A step-by-step guide for running an existing Laravel application on a
Windows computer and making it accessible to phones, tablets, and other
computers on the same local network.

> **Current project details**
>
> -   Project folder: `C:\laragon\www\resturant`
> -   Local Laragon address: `http://resturant.test`
> -   Current Wi-Fi IPv4 address reported by Windows: `192.168.0.103`
> -   Wi-Fi subnet mask: `255.255.255.0`
> -   Intended LAN address: `http://192.168.0.103`
> -   Router: TP-Link
>
> The IP address above is the address reported in the earlier `ipconfig`
> output. It can change until DHCP reservation is configured.

------------------------------------------------------------------------

## 1. Install the required software

For a typical Laravel project using Laragon on Windows, install:

1.  **Laragon** --- provides the local web server and PHP environment.
2.  **Composer** --- installs Laravel's PHP dependencies. Laragon may
    already include Composer.
3.  **Node.js (LTS)** --- required when the project uses Vite or other
    Node-based frontend tooling.
4.  **A database server** --- for example, MySQL/MariaDB, if the
    application uses one.

After installing Laragon, start it and make sure Apache and the required
database service are running.

### Check the command-line tools

Open Command Prompt and run:

``` cmd
php -v
composer -V
node -v
npm -v
```

If a command is not recognized, check that the relevant tool is
installed and available in the Windows PATH. Laragon also provides a
terminal with its configured PHP and Composer environment.

------------------------------------------------------------------------

## 2. Put the Laravel project in Laragon's `www` folder

The project is located here:

``` text
C:\laragon\www\resturant
```

The web server's document root must point to Laravel's **`public`**
directory, not the project root.

Correct public directory:

``` text
C:\laragon\www\resturant\public
```

### If creating a new Laravel project

Open Laragon Terminal, move into `www`, and create the project:

``` cmd
cd C:\laragon\www
composer create-project laravel/laravel resturant
```

If the project already exists, do not run this command over it. Use the
existing project folder.

### Install dependencies for an existing project

``` cmd
cd C:\laragon\www\resturant
composer install
```

If the project has a `package.json` and uses Vite or other frontend
assets:

``` cmd
npm install
npm run build
```

For local development, the project may instead use:

``` cmd
npm run dev
```

Keep the development process running while using it.

------------------------------------------------------------------------

## 3. Configure Laravel's `.env` file

Inside the project folder, make sure `.env` exists. If it does not, copy
`.env.example` to `.env`:

``` cmd
copy .env.example .env
```

Generate the application key if this is a new setup and the key is
missing:

``` cmd
php artisan key:generate
```

Set the local application URL:

``` env
APP_URL=http://resturant.test
```

Configure the database values to match the database and credentials
actually created on this computer. Example only:

``` env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=resturant
DB_USERNAME=root
DB_PASSWORD=
```

Do not copy the example database credentials blindly. Use the
credentials configured in your Laragon database.

If the application needs its database tables created, and this is a
new/empty database, run:

``` cmd
php artisan migrate
```

Only run migrations after confirming the correct database is selected.
Do not use destructive migration commands on a database containing data
unless you intentionally want that data removed.

Clear cached Laravel configuration after changing `.env`:

``` cmd
php artisan optimize:clear
```

------------------------------------------------------------------------

## 4. Make sure the local Laragon address works

Start Apache in Laragon. If the project uses MySQL/MariaDB, start that
service too.

Open this address on the Windows computer:

``` text
http://resturant.test
```

Do not continue until the Laravel application works at this address.

Laragon commonly creates local virtual hosts automatically. If
`resturant.test` already works, keep that working setup.

------------------------------------------------------------------------

## 5. Find the computer's Wi-Fi IP address

Open Command Prompt:

``` cmd
ipconfig
```

Find **Wireless LAN adapter Wi-Fi** and read its IPv4 Address.

The earlier output showed:

``` text
IPv4 Address: 192.168.0.103
Subnet Mask: 255.255.255.0
```

Ignore VMware adapter addresses such as `192.168.38.1` and
`192.168.77.1`. Those are virtual network adapters, not the Wi-Fi
address used by your phones.

Because the computer has both Wi-Fi and Ethernet, use the IP address of
the network interface that is connected to the same TP-Link network as
the phones. The Wi-Fi address is the intended address in this guide.

------------------------------------------------------------------------

## 6. Create an Apache virtual-host configuration for LAN access

The local address `resturant.test` working on the computer does not
automatically make the application accessible to other devices. Apache
must also accept requests addressed to the computer's LAN IP.

In Laragon, the Apache virtual-host configuration folder is commonly:

``` text
C:\laragon\etc\apache2\sites-enabled
```

Create a file named:

``` text
resturant-lan.conf
```

Make sure Windows has not added `.txt` to the filename.

Put the following configuration in the file:

``` apache
<VirtualHost *:80>
    ServerName resturant.test
    ServerAlias 192.168.0.103

    DocumentRoot "C:/laragon/www/resturant/public"

    <Directory "C:/laragon/www/resturant/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

If the computer's reserved IP is different in the future, update
`ServerAlias` to that reserved IP. The goal of the next section is to
keep the IP unchanged.

> **Important:** This file is an Apache configuration file, not a
> Laragon menu item. It may not appear as a separate entry in Laragon's
> menu. The important points are that it is saved in the correct folder,
> Apache includes that folder, and Apache successfully loads the
> configuration.

### Restart Apache

1.  Open Laragon.
2.  Stop Apache.
3.  Start Apache again.
4.  If Apache fails to start, check the Apache error log and correct the
    configuration error before continuing.

------------------------------------------------------------------------

## 7. Test LAN access on the Windows computer

On the same Windows computer, open:

``` text
http://192.168.0.103
```

Expected result: the Laravel application opens.

If it does not work, do not proceed to mobile testing yet. Check:

-   Apache is running.
-   The IP is still the current Wi-Fi IPv4 address.
-   The configuration file is in the Apache configuration folder that
    Laragon actually uses.
-   Apache successfully loaded the virtual-host configuration.
-   The `DocumentRoot` points to the project's `public` folder.

You can also test whether Apache is listening on port 80 from Command
Prompt:

``` cmd
netstat -ano | findstr :80
```

------------------------------------------------------------------------

## 8. Allow Apache through Windows Firewall

After the IP address works on the Windows computer, allow incoming
connections to Apache on the private network.

1.  Open **Windows Defender Firewall with Advanced Security**.
2.  Select **Inbound Rules**.
3.  Select **New Rule**.
4.  Choose **Port**.
5.  Choose **TCP**.
6.  Enter local port `80`.
7.  Choose **Allow the connection**.
8.  Apply the rule to **Private** networks.
9.  Name it `Laragon Apache HTTP`.

If Windows asks whether Apache should be allowed through the firewall,
allow it on private networks.

Only open the port on the trusted local network. Do not expose the
development server directly to the public internet.

------------------------------------------------------------------------

## 9. Test from phones and tablets

1.  Connect the phone or tablet to the same TP-Link router's Wi-Fi
    network.
2.  Make sure the device is not using mobile data for this test.
3.  Open the browser.
4.  Visit:

``` text
http://192.168.0.103
```

The Laravel application should appear.

Other devices do **not** need `resturant.test` configured in their hosts
files. They can use the computer's LAN IP address.

If the page does not load, check that:

-   The phone/tablet and computer are on the same local network.
-   The router does not have guest Wi-Fi isolation or client/AP
    isolation enabled.
-   Apache is running and listening on port 80.
-   The Windows Firewall inbound rule is enabled.
-   The computer's IP has not changed.

------------------------------------------------------------------------

## 10. Reserve the computer's IP address in the TP-Link router

A normal DHCP address can change. To avoid changing the Apache
configuration and the address used by phones, create a **DHCP Address
Reservation** on the TP-Link router.

This is usually better than manually setting a static IP inside Windows.

### 10.1 Open the router administration page

Find the **Default Gateway** for the Wi-Fi adapter by running:

``` cmd
ipconfig
```

Use the Default Gateway shown under **Wireless LAN adapter Wi-Fi**.
Common TP-Link addresses include `192.168.0.1` and `192.168.1.1`, but
use the value shown on your computer rather than assuming one.

Enter that gateway address in your browser and sign in with the router
administrator credentials.

### 10.2 Find Address Reservation

TP-Link menu names vary by router model and firmware. Look for a section
such as:

-   **Advanced → Network → DHCP Server → Address Reservation**
-   **Advanced → DHCP Server → Address Reservation**
-   **Network → LAN → Address Reservation**

### 10.3 Find the Wi-Fi MAC address

On Windows, run:

``` cmd
ipconfig /all
```

Under **Wireless LAN adapter Wi-Fi**, find:

``` text
Physical Address
```

This is the Wi-Fi adapter's MAC address. Use the Wi-Fi MAC address
because the intended reserved address is for the Wi-Fi connection.

### 10.4 Add the reservation

Create a reservation with these values:

  Router setting         Value
  ---------------------- --------------------------------------
  Device / MAC Address   The Wi-Fi adapter's Physical Address
  Reserved IP Address    `192.168.0.103`
  Status                 Enabled

Save the reservation.

If the router says the IP is outside its DHCP range or already assigned
to another device, do not force it. Check the router's DHCP range and
connected-device list, then choose an unused address within the router's
supported range.

### 10.5 Renew the computer's DHCP lease

After saving the reservation, you can restart the computer's Wi-Fi
connection or run these commands in Command Prompt:

``` cmd
ipconfig /release
ipconfig /renew
```

Then verify the Wi-Fi IPv4 address:

``` cmd
ipconfig
```

It should show the reserved address, `192.168.0.103`.

> If you use both Wi-Fi and Ethernet, this reservation applies only to
> the Wi-Fi adapter's MAC address. If you later connect using Ethernet
> instead, that interface may receive a different IP. Keep using Wi-Fi
> for the reserved address, or create a separate reservation for
> Ethernet if needed.

------------------------------------------------------------------------

## 11. Final access address

Once the reservation and LAN setup are working, use this address on
phones and tablets connected to the same TP-Link network:

``` text
http://192.168.0.103
```

The computer can continue using its Laragon local address:

``` text
http://resturant.test
```

### Quick checklist

-   [ ] Laragon is installed and Apache starts successfully.
-   [ ] Laravel project is in `C:\laragon\www\resturant`.
-   [ ] Laravel dependencies are installed.
-   [ ] `.env` is configured and `APP_URL=http://resturant.test`.
-   [ ] `http://resturant.test` works on the Windows computer.
-   [ ] The Apache LAN virtual-host file is saved and loaded.
-   [ ] `http://192.168.0.103` works on the Windows computer.
-   [ ] Windows Firewall allows inbound TCP port 80 on private networks.
-   [ ] Phone/tablet is connected to the same TP-Link network.
-   [ ] `http://192.168.0.103` works from the phone/tablet.
-   [ ] TP-Link DHCP Address Reservation is enabled for the computer's
    Wi-Fi MAC address.

------------------------------------------------------------------------

## Important notes

-   This setup is for access inside the same local network. It does not
    publish the Laravel application to the public internet.
-   `APP_URL` is Laravel's application URL setting; it does not
    configure the router or assign an IP address.
-   A DHCP reservation keeps the computer's LAN IP stable, provided it
    connects through the reserved network adapter.
-   If the router, subnet, or network adapter changes, verify the IP and
    gateway again with `ipconfig`.
-   Do not share the router administrator password or Laravel `.env`
    file.
