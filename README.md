SMTP Development Server
=======================

A simple and very fake SMTP server for development/testing purposes.

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

Use
---

To start the server, simply run:

```console
$ vendor/bin/smtp-dev-server
```

This will output all incoming request data to STDOUT.

**NOTE:** Currently the server will also log transactions to `./var/log/smtp.log`

### Arguments

```
  backing          Storage type (null, memory, mailbox) [default: "mailbox"]
```

### Options

```
  -i, --ip=IP      TCP/IP address [default: "127.0.0.1"]
  -p, --port=PORT  Port [default: 2525]
  -h, --help       Show help
```
