<?php
$f3 = require(__DIR__.'/vendor/bcosca/fatfree/lib/base.php');
require(__DIR__."/distribution.class.php");
require(__DIR__."/question.class.php");
require(__DIR__."/answer.class.php");
require(__DIR__."/distrochooser.class.php");

$f3->route('GET /',
    function() {
        echo 'Hello, world!';
    }
);
$f3->route('GET /distributions/@lang',
    function($f3){
        $distrochooser = new \Distrochooser3\Distrochooser($f3);
        $distros = $distrochooser->getDistributions();
        echo $distrochooser->output($distros);
    }
);
$f3->route('GET /questions/@lang',
    function($f3){
        $distrochooser = new \Distrochooser3\Distrochooser($f3);
        $questions = $distrochooser->getQuestions();
        echo $distrochooser->output($questions);
    }
);

$f3->route('GET /i18n/@lang',
    function($f3){
        $distrochooser = new \Distrochooser3\Distrochooser($f3);
        $translation = $distrochooser->geti18n();
        echo $distrochooser->output($translation);
    }
);

$f3->route('GET /newvisitor',
    function($f3) {
        $distrochooser = new \Distrochooser3\Distrochooser($f3);
        echo $distrochooser->newvisitor();
    }
);
$f3->run();