SMTP Development Server
=======================

**FOR USE IN DEVELOPMENT ENVIRONMENTS ONLY**

A simple and very fake SMTP server for development/testing purposes. Because, why not.

**Features**
 - SMTP server that can accept & store valid RFC822/RFC2822 email
 - HTTP server/site as a client interface to the messages received

**WARNING: Do not expose running server ports to open networks and do not run as 
`root` or an admin user! There are ZERO security features built into these 
servers.** 

Installation
------------

Globally:

```console
$ composer global require camelot/smtp-dev-server
```

As a development dependency for your project:

```console
$ composer require --dev camelot/smtp-dev-server
```

Configuration
-------------

Configuration is handled via environment variables. 

```shell
SMTP_LOG_LEVEL=debug
SMTP_LOG_FILE="/path/to/smtp.log"
SMTP_SPOOL_DIR="/path/to/spool"
HTTP_LOG_LEVEL=debug
HTTP_LOG_FILE="/path/to/http.log"
```

See the `.env` file in this directory for an example if you're cloning this 
repository, you can create a `.env.local` file to override any of the values in
the `.env` file.

Use
---

Both servers have two output targets, console and PSR logger.

Console output levels are managed by passing `-v`, `-vv`, or `-vvv` as options
on the command line.

Logger output is managed via environment variables that are used internally to 
configure the loggers.

For example: 
```
$ SMTP_LOG_LEVEL=debug vendor/bin/smtp-dev-server -vvv
``` 

### Server

![image](https://user-images.githubusercontent.com/1427081/194073098-e655ea46-bda4-4c63-8d7e-c193f4636a82.png)

To start the server, simply run:

```console
$ vendor/bin/smtp-dev-server
```

This will output internal information to STDOUT. You can specify verbosity with
the command options below.

Server can be stopped by sending a signal, e.g. `CTRL+C`.

#### Arguments

```
  backing          Storage type (null, memory, mailbox) [default: "mailbox"]
```

#### Options

```
  -i, --ip=IP            TCP/IP address [default: "127.0.0.1"]
  -p, --port=PORT        Port to listen on [default: 2525]
  -r, --retries=RETRIES  Number of times to retry connecting to the server socket address if it is currently in use [default: 10]
  -h, --help             Show help
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Client

![image](https://user-images.githubusercontent.com/1427081/194073411-604cb0b9-0901-48ee-ae0a-86459394ae3c.png)
![image](https://user-images.githubusercontent.com/1427081/194208872-28f7b627-846a-43ce-81d3-52c6c0a3b90d.png)

To start the server, simply run:

```console
$ vendor/bin/smtp-dev-client
```

This will output internal information to STDOUT. You can specify verbosity with 
the command options below.

Server can be stopped by sending a signal, e.g. `CTRL+C`.

#### Arguments

```
  backing          Storage type (null, memory) [default: "null"]
```

#### Options

```
  -i, --ip=IP            TCP/IP address [default: "127.0.0.1"]
  -p, --port=PORT        Port to listen on [default: 2580]
  -r, --retries=RETRIES  Number of times to retry connecting to the server socket address if it is currently in use [default: 10]
  -h, --help             Show help
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

Open (default) `http://127.0.0.1:2580` to view & manage messages received by the SMTP server component.
