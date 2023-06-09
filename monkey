#!/usr/bin/php
<?php

if ($argc == 0) {
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';

use Monkey\Evaluator\Evaluator;
use Monkey\Lexer\Lexer;
use Monkey\Object\Environment;
use Monkey\Parser\Parser;
use Monkey\Repl\Repl;

function evalFile($filename)
{
    if (!file_exists($filename)) {
        fwrite(STDOUT, "file '{$filename}' does not exist\n");
        exit(1);
    }

    $file = file_get_contents($filename);

    $environment = new Environment();
    $lexer = new Lexer($file);
    $parser = new Parser($lexer);

    $program = $parser->parseProgam();

    if (count($parser->errors) > 0) {
        fwrite(STDOUT, "Woops! We ran into some monkey business here!\n");
        fwrite(STDOUT, " parser errors:\n");
        foreach ($parser->errors as $error) {
            fwrite(STDOUT, "\t{$error}\n");
        }
        exit(1);
    }

    $env = new Environment();
    $env->defineMacros($program);

    $environment->extend($env);
    $expanded = $environment->expandMacros($program);

    $evaluator = new Evaluator($environment);
    $evaluated = $evaluator->eval($expanded);

    if (!is_null($evaluated)) {
        fwrite(STDOUT, "{$evaluated->inspect()}\n");
    }
}

if ($argc == 1) {
    Repl::start();
} else if ($argc == 2) {
    evalFile($argv[1]);
} else {
    for ($i = 1; $i < count($argv); $i++) {
        if ($i > 1) {
            fwrite(STDOUT, "\n");
        }
        fwrite(STDOUT, "{$argv[$i]}:\n");
        evalFile($argv[$i]);
    }
}
