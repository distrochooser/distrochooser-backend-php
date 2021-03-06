<?php
header("X-LDC: Elephantastic Distrochooser 3.0.0");
$f3 = require(__DIR__.'/vendor/bcosca/fatfree/lib/base.php');
require(__DIR__."/distribution.class.php");
require(__DIR__."/question.class.php");
require(__DIR__."/answer.class.php");
require(__DIR__."/distrochooser.class.php");

$f3->route('GET /',
    function() {
        echo "I'm a five ton API.";
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
$f3->route('POST /get/@lang',
    function($f3) {
        $distrochooser = new \Distrochooser3\Distrochooser($f3);
        echo $distrochooser->output($distrochooser->get());
    }
);
$f3->route('POST /addresult',
    function($f3) {
        $distrochooser = new \Distrochooser3\Distrochooser($f3);
        echo $distrochooser->addresult();
    }
);

$f3->route('GET /getstats',
    function($f3) {
        $distrochooser = new \Distrochooser3\Distrochooser($f3);
        echo $distrochooser->output($distrochooser->getstats());
    }
);

$f3->route('GET /getratings/@lang',
    function($f3) {
        $distrochooser = new \Distrochooser3\Distrochooser($f3);
        echo $distrochooser->output($distrochooser->getLastRatings());
    }
);

$f3->route('POST /addrating/@lang',
    function($f3) {
        $distrochooser = new \Distrochooser3\Distrochooser($f3);
        echo $distrochooser->output($distrochooser->newRatingWithComment());
    }
);

$f3->route('GET /test/@id',
    function($f3) {
        $distrochooser = new \Distrochooser3\Distrochooser($f3);
        echo $distrochooser->output($distrochooser->getTest((int)$f3->get("PARAMS.id")));
    }
);

$f3->run();