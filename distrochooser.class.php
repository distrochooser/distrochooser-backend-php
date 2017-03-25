<?php
namespace Distrochooser3;
class Distrochooser{
    private $conn = null;
    private $languages = [
        "de" => 1,
        "en" => 2
    ];
    private $language = -1;
    private $f3 = null;
    public function __construct($f3){
        header("Content-Type: text/json");
        require __DIR__."/database.config.php";
        $options  = array
                    (
                        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ
                    );
        $this->conn   = new \PDO($server, $user, $password, $options);
        $this->f3 = $f3;
        $this->language = $this->languages[$f3->get("PARAMS.lang")];
    }

    public function getDistributions(){
        $result = [];
        $query = "Select d.Id,d.Name,d.Homepage,d.Image, (
        Select dd.Description from dictDistribution dd where  dd.DistributionId = d.Id and dd.LanguageId = ".$this->language." limit 1
        ) as Description,d.ImageSource,d.TextSource,d.ColorCode,d.Characteristica from Distribution d";
        $stmt = $this->conn->query($query);
        $distros = $stmt->fetchAll();
        foreach ($distros as $key => $value) {
            $distro = new \Distrochooser3\Distribution();
            $distro->id = $value->Id;
            $distro->name = $value->Name;
            $distro->homepage = $value->Homepage;
            $distro->image = $value->Image;
            $distro->description = $value->Description;
            $distro->imagesource = $value->ImageSource;
            $distro->textsource = $value->TextSource;
            $distro->colorcode = $value->ColorCode;
            $distro->excluded = false;
            $distro->percentage = 0;
            $distro->tags = (json_decode($value->Characteristica));
            $result[] = $distro;
        }
        return $result;
    }

    public function getQuestions(){
        $result = [];
        $query = "Select q.Id as id,q.OrderIndex, dq.Text as text,q.Single as single, dq.Help as help,q.* 
        from Question q INNER JOIN dictQuestion dq
			ON LanguageId = ".$this->language." and QuestionId= q.Id order by q.OrderIndex";
        $stmt = $this->conn->query($query);
        $questions = $stmt->fetchAll();
        $i = 1;
        foreach ($questions as $key => $value) {
            $question = new \Distrochooser3\Question();
            $question->id = (int)$value->Id;
            $question->help = $value->help;
            $question->buttontext = "";
            $question->important = false;
            $question->number = $i;
            $question->single = (int)$value->single === 1;
            $question->text = (int)$value->text === 1;
            $query = "Select a.Id as id,(
							Select da.Text from dictAnswer da where da.AnswerId = a.Id and da.LanguageId = ".$this->language."
						)as text,a.Tags,a.NoTags,a.IsText as istext from Answer a where a.QuestionId = ".$question->id;
            $stmt = $this->conn->query($query);
            $answers = $stmt->fetchAll();
            $question->answers = [];
            foreach($answers as $answer){
                 $a = new \Distrochooser3\Answer();
                 $a->id = (int)$answer->id;
                 $a->text = $answer->text;
                 $a->notags = json_decode($answer->NoTags);
                 $a->tags = json_decode($answer->Tags);
                 $a->image = null;
                 $a->istext = (int)$answer->istext === 1;
                 $a->selected = false;
                 $question->answers[] = $a;
            }
            $result[] = $question;
            $i++;
        }
        return $result;
    }

    public function geti18n(){
        $query = "Select Text,Val,Val as Name from phisco_ldc3.dictSystem where LanguageId =  ".$this->language;
        $i18n = [];
        $stmt = $this->conn->query($query);
        $values = $stmt->fetchAll();
        foreach($values as $tuple){
            $translation = new \stdClass();
            $translation->val = $tuple->Val;
            $translation->name = $tuple->Name;
            $i18n[$translation->name] = $translation;
        }
        return $i18n;
    }

    public function newvisitor(){
        $referrer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "";
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        $dnt = isset($_POST["dnt"]) && $_POST["dnt"] === "true" ? 1 : 0;
        $query = "Insert into Visitor (Date,Referrer,UserAgent,DNT) Values(CURRENT_TIMESTAMP,?,?,?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$referrer);
        $stmt->bindParam(2,$useragent);
        $stmt->bindParam(3,$dnt );
        $stmt->execute();
        $id = $this->conn->lastInsertId();
        return $id;
    }

    public function get(){
        $response = new \stdClass();
        $response->questions = $this->getQuestions();
        $response->distros = $this->getDistributions();
        $response->i18n = $this->geti18n();
        $response->visitor = $this->newvisitor();
        return $response;
    }

    public function output($val){
		echo json_encode($val);
    }
}