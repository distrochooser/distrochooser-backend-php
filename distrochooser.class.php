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
            $question->text = $value->text;
            $question->answered = false;
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
                 $a->notags = json_decode($answer->NoTags) ?? [];
                 $a->tags = json_decode($answer->Tags) ?? [];
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
        $query = "Select Text,Val,Val as Name from dictSystem where LanguageId =  ".$this->language;
        $i18n = [];
        $stmt = $this->conn->query($query);
        $values = $stmt->fetchAll();
        foreach($values as $tuple){
            $translation = new \stdClass();
            $translation->val = $tuple->Text;
            $translation->name = $tuple->Name;
            $i18n[$translation->name] = $translation;
        }
        return $i18n;
    }

    public function newvisitor(){
        $referrer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "";
        $useragent = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : ""; 
        $dnt = isset($_POST["dnt"]) && $_POST["dnt"] === "true" ? 1 : 0;
        $adblocker = isset($_POST["adblocker"]) && $_POST["adblocker"] === "true" ? 1 : 0;
        $query = "Insert into Visitor (Date,Referrer,UserAgent,DNT,API,Adblocker) Values(CURRENT_TIMESTAMP,?,?,?,'stetler',?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$referrer);
        $stmt->bindParam(2,$useragent);
        $stmt->bindParam(3,$dnt );
        $stmt->bindParam(4,$adblocker );
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
        $response->lastRatings = []; //TODO:!!
        return $response;
    }

    public function addresult(){
        $query = "Insert into phisco_ldc3.Result (Date,UserAgent,Tags, Answers,Important) Values(CURRENT_TIMESTAMP,?,?,?,?)";
        $useragent = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : ""; 
        $tags = $this->f3->get("POST.tags");
        $distros = $this->f3->get("POST.distros");
        $answers = $this->f3->get("POST.answers");
        $important = $this->f3->get("POST.important");
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$useragent);
        $stmt->bindParam(2,$tags);
        $stmt->bindParam(3,$answers);
        $stmt->bindParam(4,$important);
        $stmt->execute();
        $id = $this->conn->lastInsertId();
        $results = json_decode($distros);
        foreach($results as $d){
            $query = "Insert into phisco_ldc3.ResultDistro (DistroId,ResultId) Values(?,?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$d->id);
            $stmt->bindParam(2,$id);
            $stmt->execute();
        }
        return $id;
    }

    public function getstats(){
        $query = "SELECT 
        COUNT( Id ) as count ,
        DATE_FORMAT(DATE, '%d/%m') AS MONTH,
        DATE_FORMAT(DATE, '%d/%m/%Y') AS FullDate,
        (
        Select count(Id) from phisco_ldc3.Visitor where DATE_FORMAT(DATE, '%d/%m/%Y')  = FullDate
        ) as hits
        FROM phisco_ldc3.Result
        WHERE YEAR( DATE ) = YEAR( CURDATE( ) )
        and MONTH(DATE) = MONTH(CURDATE())
        GROUP BY FullDate";
        return  $this->conn->query($query)->fetchAll();
    }

    public function getLastRatings(){
        $query = "Select * from Rating where Approved = 1 and Lang = ? order by ID desc limit 7";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$this->language);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function newRatingWithComment(){
        $rating = (int)$this->f3->get("POST.rating") ?? "0.0";
        $comment = $this->f3->get("POST.comment")  ?? "";
        $test = (int)$this->f3->get("POST.test")  ?? "";
        $useragent = $_SERVER['HTTP_USER_AGENT'] ??  null ;
        if ($test === 0){
            return false;
        }
        $query = "Insert into Rating (Rating,Date,UserAgent,Comment,Test,Lang) Values (?,CURRENT_TIMESTAMP,?,?,?,?);";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$rating);
        $stmt->bindParam(2,$useragent);
        $stmt->bindParam(3,strip_tags($comment));
        $stmt->bindParam(4,$test);
        $stmt->bindParam(5,$this->language);
        $stmt->execute();
        return (int)$rating;
        mail("fury224@googlemail.com","Distrochooser","Feedback");
    }

    public function getTest(int $id){
        $query = "Select Answers as answers, Important as important from Result where Id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function output($val){
		echo json_encode($val);
    }
}