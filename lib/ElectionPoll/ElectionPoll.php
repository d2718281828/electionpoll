<?php
namespace ElectionPoll;
class ElectionPoll {

  /**
  * DB adaptor
  */
  protected $DB = null;
  /**
  * Default html formatting
  */
  protected $defaults;
  /**
  * actual html formatting
  */
  protected $options;
  /**
  * Boolean - signal if the user's vote was a success
  */
  protected $success;
  /**
  * The initial option for the party dropdown
  */
  protected $partyPrompt;
  /**
  * hardcode the list of commonest parties to avoid problems with mis-spelling
  */
  protected $parties;

  public function __construct($db){
    $this->DB = $db;
    $this->defaults = [
      "form_before" => '<div class="input_form">',
      "form_after" => '</div>',
      "form_id" => 'pollform',
      "field_before" => '<div class="input_field">',
      "field_after" => '</div>',
      "msg_before" => '<div class="message message_good">',
      "msg_after" => '</div>',
      "error_msg_before" => '<div class="message message_bad">',
      "error_msg_after" => '</div>',
      "poll_caption" => 'Vote Now',
      "report_before" => '<div class="report">',
      "report_after" => '</div>',
      "repsect_before" => '<div class="report-section">',
      "repsect_after" => '</div>',
    ];
    $this->partyPrompt = "Please select the party you are most likely to vote for";
    $this->parties = [
      $this->partyPrompt,
      "Conservative",
      "Labour",
      "Liberal Democrat",
      "Plaid Cymru",
      "Scottish National Party",
      "SDLP",
      "UKIP",
      "Independent",
      "Other",
    ];
  }
  public function html($opts = null){
    $this->options = $opts ? array_merge($this->defaults,$opts) : $this->defaults;

    $this->success = false;
    $m = "";
    if (isset($_REQUEST["register"])) {
      $m.= $this->post();
    }
    if ($this->success){
      $m.= $this->report();
    } else $m.= $this->form();
    return $m;
  }
  protected function form(){
    $form = $this->options["form_before"];
    $form.= '<form method="POST" name="'.$this->options["form_id"].'" id="'.$this->options["form_id"].'">';
    $form.= $this->options["field_before"].$this->field_constituency().$this->options["field_after"];
    $form.= $this->options["field_before"].$this->field_party().$this->options["field_after"];
    $form.= $this->options["field_before"].$this->other_party().$this->options["field_after"];
    $form.= $this->button();
    $form.= '</form>';
    $form.= $this->options["form_after"];
    return $form;
  }
  protected function field_constituency(){
    $constituencies = $this->DB->getRows("select id,name from constituencies order by name");
    $m = '<div class="label"><label for="poll_constituency">Constituency</label></div>';
    $m.= '<div class="question"><select name="poll_constituency" id="poll_constituency">';
    $m.= '<option value="0">Please choose your constituency</option>';
    foreach ($constituencies as $constituency){
      $m.= '<option value="'.$constituency["id"].'">'.$constituency["name"].'</option>';
    }
    $m.= '</select></div>';
    return $m;
  }
  protected function field_party(){
    $m = '<div class="label"><label for="poll_party">What party will you vote for?</label></div>';
    $m.= '<div class="question"><select name="poll_party" id="poll_party">';
    foreach ($this->parties as $party){
      $m.= '<option value="'.$party.'">'.$party.'</option>';
    }
    $m.= '</select></div>';
    return $m;
  }
  protected function other_party(){
    $m = '<div class="label"><label for="other_party">If you chose other, please enter the name</label></div>';
    $m.= '<div class="question"><input type="text" id="other_party" name="other_party"></div>';
    return $m;
  }
  protected function button(){
    $m = '<input type="submit" name="register" value="'.$this->options["poll_caption"].'">';
    return $m;
  }
  protected function post(){
    $cons = (int)$_REQUEST["poll_constituency"];
    $vote = $_REQUEST["poll_party"];
    if ($vote=="Other") $vote = $_REQUEST["other_party"];
    if ($cons == 0 || $vote == $this->partyPrompt){
      $m = "Please select your constituency AND the party you expect you will be voting for.";
      return $this->options["error_msg_before"].$m.$this->options["error_msg_after"];
    }
    $this->success = true;
    $ip = $_SERVER["REMOTE_ADDR"];

    $register = "insert into responses(constituency_id,party,ip_address) values (";
    $register.= $cons;
    $register.= ",".$this->DB->quote($vote);
    $register.= ",".$this->DB->quote($ip);
    $register.= ")";
    $this->DB->query($register);
    $this->chosen_constituency = $cons;
    $m = "Your intention to vote for the $vote party has been recorded.";
    return $this->options["msg_before"].$m.$this->options["msg_after"];
  }
  protected function report(){
    $overallQ = "select party, count(*) as votescast from responses group by party order by 2 desc";
    $overall = $this->DB->getRows($overallQ);
    // NOTE the chosen_constituency was already escaped by casting it to an int
    $constQ = "select party, count(*) as votescast from responses where constituency_id = ".$this->chosen_constituency." group by party order by 2 desc";
    $const = $this->DB->getRows($constQ);
    $constnameQ = "select name from constituencies where id=".$this->chosen_constituency;
    $constname = $this->DB->getRows($constnameQ);

    $m = $this->options["report_before"];

    $m.= $this->options["repsect_before"];
    $m.= '<h2>Overall results</h2>';
    $m.= $this->tabulate($overall);
    $m.= $this->options["repsect_after"];

    $m.= $this->options["repsect_before"];
    $m.= '<h2>Results for '.$constname[0]["name"].' constituency</h2>';
    $m.= $this->tabulate($const);
    $m.= $this->options["repsect_after"];

    $m.= $this->options["report_after"];
    return $m;
  }
  protected function tabulate($data){
    $total = 0;
    foreach($data as $party) $total+=$party["votescast"];
    if ($total==0) return "No votes cast";    // shouldnt happen
    $m = '<table>';
    $m.= '<tr><td>Party</td><td>Percentage</td></tr>';
    foreach($data as $party){
      $pct = $party["votescast"]*100/$total;
      $m.= '<tr><td>'.$party["party"].'</td><td>'.number_format($pct,1).'%</td></tr>';
    }
    $m.= '</table>';
    return $m;
  }
  public function init(){
    $cr1 = "create table if not exists constituencies (
      id integer not null,
      name varchar(254) not null,
      incumbent varchar(254),
      primary key (id)
      )
    ";
    $this->DB->query($cr1);
    $cr_resp = "create table if not exists responses (
      id bigint NOT NULL AUTO_INCREMENT,
      constituency_id integer not null,
      party varchar(55) not null,
      ip_address varchar(32),
      created_at datetime  DEFAULT CURRENT_TIMESTAMP,
      primary key(id)
    )";
    $this->DB->query($cr_resp);
    $this->getCons();
  }
  protected function getCons(){
    ini_set('MAX_EXECUTION_TIME', 60);
    $url1 = "http://lda.data.parliament.uk/constituencies.json?_view=Constituencies&_pageSize=50&_page=";
    $insert = "insert into constituencies(id,name) values ";
    $comma = "";
    for ($page = 0; $page<100; $page++){
      //echo "<br>page --------------------".$page;
      $datapage = $this->getJSON($url1.$page);
      if (!$datapage["result"]["items"]) break;
      foreach($datapage["result"]["items"] as $row){
        if (!isset($row["endedDate"])) {
          $insert.= $comma.$this->addConstituency($row);
          $comma = ",";
        }
      }
    }
    echo $insert;
    $this->DB->query($insert);
  }
  protected function getJSON($url){
    //echo "<br>trying <a href='$url'>$url</a>";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    //curl_setopt($ch, CURLOPT_POST, 1);
    $result = curl_exec($ch);
    //echo $result;
    curl_close($ch);
    $resultdata = json_decode($result,true);
    //echo implode("-",array_keys($resultdata));
    //echo "Result ".count($resultdata["result"]["items"]);
    //print_r($resultdata["result"]["items"][0]);
    return $resultdata;
  }
  protected function addConstituency($entry){
    $id = $entry["_about"];
    $id = (int)substr($id,strrpos($id,"/")+1);
    $name = $entry["label"]["_value"];
    //echo "<br/>".$id."=".$name;
    $stmt = "(".$id.",".$this->DB->quote($name).")";
    return $stmt;
  }
}
