<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Enum;

/**
 * @see RFC 5321 https://www.ietf.org/rfc/rfc5321.txt
 */
enum SmtpReply: int
{
    /** System status, or system help reply */
    case SystemStatusHelp = 211;
    /** Help message (Information on how to use the receiver or the meaning of a particular non-standard command; this reply is useful only to the human user) */
    case HelpMessage = 214;
    /** <domain> Service ready */
    case ServiceReady = 220;
    /** <domain> Service closing transmission channel */
    case ServiceClosing = 221;
    /** Requested mail action okay, completed */
    case RequestedMailActionOK = 250;
    /** User not local; will forward to <forward-path> (See Section 3.4) */
    case UserNotLocal = 251;
    /** Cannot VRFY user, but will accept message and attempt delivery (See Section 3.5.3) */
    case CannotVrfyUser = 252;

    /** Start mail input; end with <CRLF>.<CRLF> */
    case StartMailInput = 354;

    /** <domain> Service not available, closing transmission channel (This may be a reply to any command if the service knows it must shut down) */
    case ServiceNotAvailable = 421;
    /** Requested mail action not taken: mailbox unavailable (e.g., mailbox busy or temporarily blocked for policy reasons) */
    case RequestedActionMailMailboxUnavailable = 450;
    /** Requested action aborted: error in processing */
    case RequestedActionAborted = 451;
    /** Requested action not taken: insufficient system storage */
    case RequestedActionInsufficientSystemStorage = 452;
    /** Server unable to accommodate parameters */
    case ServerUnable = 455;

    /** Syntax error, command unrecognized (This may include errors such as command line too long) */
    case SyntaxErrorCommandUnrecognized = 500;
    /** Syntax error in parameters or arguments */
    case SyntaxErrorParamsArgs = 501;
    /** Command not implemented (see Section 4.2.4) */
    case CommandNotImplemented = 502;
    /** Bad sequence of commands */
    case BadSequenceOfCommands = 503;
    /** Command parameter not implemented */
    case CommandParameterNotImplemented = 504;
    /** Requested action not taken: mailbox unavailable (e.g., mailbox not found, no access, or command rejected for policy reasons) */
    case RequestedActionMailboxUnavailable = 550;
    /** User not local; please try <forward-path> (See Section 3.4) */
    case RejectedUserNotLocal = 551;
    /** Requested mail action aborted: exceeded storage allocation */
    case RequestedActionExceededStorageAllocation = 552;
    /** Requested action not taken: mailbox name not allowed (e.g., mailbox syntax incorrect) */
    case RequestedActionMailboxNameNotAllowed = 553;
    /** Transaction failed (Or, in the case of a connection-opening response, "No SMTP service here") */
    case TransactionFailed = 554;
    /** MAIL-FROM/RCPT-TO parameters not recognized or not implemented */
    case FromToParamsNotRecognizedImplemented = 555;
}
