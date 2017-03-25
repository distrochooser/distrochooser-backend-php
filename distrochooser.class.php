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
            $result[] = $question;
            $i++;
        }
        return $result;
    }

    public function output($val){
		echo json_encode($val);
    }
}