<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Response;

use Camelot\SmtpDevServer\Enum\SmtpResponseCode;
use Camelot\SmtpDevServer\Exception\SmtpNegotiationException;
use DateTimeImmutable;

final class SmtpResponseFactory
{
    public static function ready(string $hostAddress): SmtpResponse
    {
        return SmtpResponse::create(SmtpResponseCode::ServiceReady, $hostAddress . ' ready at ' . (new DateTimeImmutable('now'))->format('r'));
    }

    public static function hello(string $hostAddress, ?string $callingHost): SmtpResponse
    {
        return SmtpResponse::create(SmtpResponseCode::RequestedMailActionOK, "{$hostAddress} Hello {$callingHost} [{$hostAddress}]");
    }

    public static function eHello(string $hostAddress, ?string $callingHost): SmtpResponse
    {
        return SmtpResponse::create(SmtpResponseCode::RequestedMailActionOK)
            ->addLine($hostAddress . ' Hello ' . $callingHost . ' [' . $hostAddress . ']')
            ->addLine('SIZE 1000000')
            ->addLine('AUTH PLAIN')
        ;
    }

    public static function mailFrom(): SmtpResponse
    {
        return self::ok();
    }

    public static function rcptTo(): SmtpResponse
    {
        return self::ok();
    }

    public static function data(): SmtpResponse
    {
        return SmtpResponse::create(SmtpResponseCode::StartMailInput, 'Send message content; end with <CRLF>.<CRLF>');
    }

    public static function reset(): SmtpResponse
    {
        return self::ok();
    }

    public static function quit(): SmtpResponse
    {
        return SmtpResponse::create(SmtpResponseCode::ServiceClosing, 'So long, and thanks for all the messages');
    }

    public static function ok(string $message = null): SmtpResponse
    {
        return SmtpResponse::create(SmtpResponseCode::RequestedMailActionOK, trim('OK ' . $message));
    }

    public static function help(): SmtpResponse
    {
        return SmtpResponse::create(SmtpResponseCode::SystemStatusHelp)
            ->addLine('Available commands')
            ->addLine('HELP - This is what you get')
            ->addLine('HELO - Start conversation wth a HELO')
            ->addLine('EHLO - Start conversation wth a EHLO')
            ->addLine('MAIL FROM:<sender@domain.tld>')
            ->addLine('RCPT TO:<recipient@domain.tld>')
            ->addLine('DATA')
            ->addLine('QUIT - Terminate the connection')
            ->addLine('')
        ;
    }

    public static function badSequenceOfCommands(SmtpNegotiationException $e = null): SmtpResponse
    {
        return SmtpResponse::create(SmtpResponseCode::BadSequenceOfCommands, $e?->getMessage());
    }

    public static function notImplemented(string $command): SmtpResponse
    {
        return SmtpResponse::create(SmtpResponseCode::CommandNotImplemented, 'Command ' . $command . ' not implemented.');
    }

    public static function syntaxError(string $data = null): SmtpResponse
    {
        $message = 'Syntax error, command unrecognized: ' . $data;
        return SmtpResponse::create(SmtpResponseCode::SyntaxErrorCommandUnrecognized, $message);
    }
}
