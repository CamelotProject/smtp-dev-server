<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Enum;

enum Signal: int
{
    case SIGHUP = 1;
    case SIGINT = 2;
    case SIGQUIT = 3;
    case SIGILL = 4;
    case SIGTRAP = 5;
    case SIGABRT = 6;
    case SIGBUS = 7;
    case SIGFPE = 8;
    case SIGKILL = 9;
    case SIGUSR1 = 10;
    case SIGSEGV = 11;
    case SIGUSR2 = 12;
    case SIGPIPE = 13;
    case SIGALRM = 14;
    case SIGTERM = 15;
    case SIGSTKFLT = 16;
    case SIGCHLD = 17;
    case SIGCONT = 18;
    case SIGSTOP = 19;
    case SIGTSTP = 20;
    case SIGTTIN = 21;
    case SIGTTOU = 22;
    case SIGURG = 23;
    case SIGXCPU = 24;
    case SIGXFSZ = 25;
    case SIGVTALRM = 26;
    case SIGPROF = 27;
    case SIGWINCH = 28;
    case SIGPOLL = 29;
    case SIGPWR = 30;
    case SIGSYS = 31;
}
