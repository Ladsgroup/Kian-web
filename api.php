<?PHP
// Partially copied from
// https://bitbucket.org/magnusmanske/mixnmatch/src/bee0de816714e87892ce304cc988acafe1499408/public_html/distributed_game_api.php
error_reporting(E_ERROR|E_CORE_ERROR|E_COMPILE_ERROR); // E_ALL|
ini_set('display_errors', 'On');
$dbmycnf = parse_ini_file("../replica.my.cnf");
$dbuser = $dbmycnf['user'];
$dbpass = $dbmycnf['password'];
unset($dbmycnf);
$dbhost = "tools-db";
$dbname = "s52709__kian_p";
$db = new PDO('mysql:host='.$dbhost.';dbname='.$dbname.';charset=utf8', $dbuser, $dbpass);
header('Content-type: application/json');

$callback = $_REQUEST['callback'] ;
$out = array () ;

$helper_array = array ('Q5' => 'human', 'Q486972' => 'human settlement', 'Q783794' => 'company', 'P31' => 'instance of', 'P17' => 'country');
if ( $_REQUEST['action'] == 'desc' ) {

	$title = "Kian game" ;

	$out = array (
		"label" => array ( "en" => $title ) ,
		"description" => array ( "en" => "[https://github.com/Ladsgroup/Kian Kian] suggestions to add statements in items based on categories in Wikipedia articles. Contact [https://www.wikidata.org/wiki/User:Ladsgroup Amir] if a model has too much incorrect suggestions. 17 languages are supported. Source code can be found in [https://github.com/Ladsgroup/Kian-web here]" ) ,
		"icon" => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/ArtificialFictionBrain.png/120px-ArtificialFictionBrain.png' ,
	) ;

} else if ( $_REQUEST['action'] == 'tiles' ) {

	// GET parameters
	$num = $_REQUEST['num'] ; // Number of games to return
	$wiki = $_REQUEST['lang'] . 'wiki' ; // The language to use, with 'en' as fallback; ignored in this game
	
	$catalogs = array() ;
	$sql = "SELECT * FROM kian WHERE wiki_name = '" . $wiki . "' and status = 0 ORDER by RAND() DESC LIMIT " . $num;
	$result = $db->query($sql);
	$result = $result->fetchAll();
	foreach ($result as $row) {
		$item = $row['qid'] ;
		$q = $row['value'] ;
		$p = $row['property'] ;
		$model = $row['model_name'] ;
		$id = $row['id'] ;
		$prob = $row['prob'] ;
		$q_text = isset($helper_array[$q]) ? $helper_array[$q]: substr($model, 2);
		$p_text =  $helper_array[$p];
		$g = array(
			'id' => $id ,
			'sections' => array () ,
			'controls' => array ()
		) ;
		$g['sections'][] = array ( 'type' => 'item' , 'q' => $item ) ;
		
		$g['sections'][] = array ( 'type' => 'text' , 'title' => $q_text , 'text' => "Is this ".$p_text.':'.$q_text."?\nModel:".$model." - Probability:".$prob, 'url'=>"https://www.wikidata.org/wiki/".$q) ;
		$g['controls'][] = array (
			'type' => 'buttons' ,
			'entries' => array (
				array ( 'type' => 'green' , 'decision' => 'yes' , 'label' => 'Yes' , 'api_action' => array ('action'=>'wbcreateclaim', 'entity'=>$item,'property'=>$p,'snaktype'=>'value','value'=> json_encode (array("entity-type"=>"item","numeric-id"=>ltrim ($q, 'Q')*1 ) ) ) ) ,
				array ( 'type' => 'white' , 'decision' => 'skip' , 'label' => 'Skip' ) ,
				array ( 'type' => 'blue' , 'decision' => 'no' , 'label' => 'No' )
			)
		) ;
		
		$out[] = $g ;
		
	}

} else if ( $_REQUEST['action'] == 'log_action' ) {

	$user = $_REQUEST['user'] ;
	$entry_id = $_REQUEST['tile']*1 ;
	$decision = $_REQUEST['decision'] ;

	$ts = date ( 'YmdHis' ) ;
	
	
	$sql = '' ;
	if ( $decision == 'yes' ) {
		$sql = "UPDATE kian SET status=1 WHERE id=$entry_id" ;
	} else if ( $decision == 'no' ) {
		$sql = "UPDATE kian SET status=2 WHERE id=$entry_id" ;
	}
	
	if ( $sql != '' ) {
		$result = $db->exec($sql);
		if(!$result) $out['error'] = 'There was an error running the query [' . $db->error . ']';
	}

} else {
	$out['error'] = "No valid action!" ;
}

print $callback . '(' ;
print json_encode ( $out ) ;
print ")\n" ;

?>
