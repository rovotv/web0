<?php
$domain = "http://www.tfreeca22.com/";
$file_domain = "http://www.filetender.com/";

$method = $_GET["method"];
if ( $method == 'download' ) {
	download();
} else {
	get_rss();
}


function get_rss(){
	
	global $domain, $file_domain;
	
	$query = $_SERVER['QUERY_STRING'];
	if(empty($query)){
		//exit();
		exit();
	}
	
	//목록에서 링크와 제목을 가져오는 정규식
	$regex = '/<td class="subject">.*(?<link>board\.php\?mode=view[^"]*).*class="stitle\d">(?<title>[^<]*)/';
	
	//게시글에서 첨부파일을 찾아내는 정규식
	$article_regex = '/<a href="'.str_replace('/', '\/', $file_domain).'(?<key>[^"]*)".*target="_blank">(?<filename>[^<].*)<\/a>/';
	
	//rss item노드 구조
	$node_format = "<item><title>%s</title><link>http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?%s&amp;method=download&amp;filename=%s&amp;key=%s</link><description></description><showrss:showid></showrss:showid><showrss:showname>%s</showrss:showname></item>";
	
	header("Content-Type: application/xml");
	$headers = ['Cookie: uuoobe=on;'];
	
	//rss시작 태그
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?><rss xmlns:showrss=\"http://showrss.info/\" version=\"2.0\"><channel><title>Tfreeca - ".$_GET['b_id']."</title><link></link><description></description>";
	
	//목록 가져오기
	$url = $domain.'board.php?mode=view&'.$query;
	$data = get_html($url, $headers);
	
	//검색어가 들어간 경우 data에서 강조 태그를 지운다.
	if(!empty($_GET['sc'])){
		$data = preg_replace('/<span class=\'sc_font\'>([^<]*)<\/span>/', '$1', $data);
	}
		
	$match_count = preg_match_all($regex, $data, $match);
	
	//목록 루프
	for($i = 0 ; $i < $match_count; $i++){
		
		$title = htmlspecialchars(trim($match['title'][$i]));
		
		$link = $match['link'][$i];
		$article_data = get_html($domain.$link, $headers);
		
		$match_count_article = preg_match_all($article_regex, $article_data, $match_article);
		
		//게시글 내의 첨부파일 루프
		for($j = 0 ; $j < $match_count_article; $j++){
			
			$filename = htmlspecialchars($match_article['filename'][$j]);
			$key = $match_article['key'][$j];
			
			if( $match_count_article == 1 ){
				//첨부파일이 하나면 prefix를 추가하지 않는다.
				$disp_title = $title;
			} else {
				
				//파일명에 따른 표시 prefix 추가 
				$lower_filename = strtolower($filename);
				$prefix = 'ETC';
				if(endsWith($lower_filename, ".torrent")) {
					$prefix = 'TORRENT';
				} elseif(endsWith($lower_filename, ".smi") || endsWith($lower_filename, ".srt")|| endsWith($lower_filename, ".sub")){
					$prefix = 'SUB';
				} elseif(endsWith($lower_filename, ".idx")){
					$prefix = 'IDX';
				}
				
				if ( preg_match('/\d{3,4}p/', $lower_filename, $match3) > 0) {
					$prefix = $prefix.", ".$match3[0];
				}
				
				
				$disp_title = '['.$prefix.'] '.$title;
			}
			//rss item 생성 
			//substr($link, 20) --> 링크의 board.php?mode=view& 제거
			printf($node_format, $disp_title, htmlspecialchars(substr($link, 20)), rawurlencode($filename), $key, $title);
		}
		
	}
	//rss종료태그
	echo "</channel></rss>";
}

function download() {
	
	$data = download_impl();
	
	$filename = $_GET["filename"];
	$lower_filename = strtolower($filename);
	
	
	header("Content-Type: application/octet-stream");
	header("content-disposition: attachment; filename=\"".$filename."\"");
	
	echo $data;
}

function download_impl() {
	
	global $domain, $file_domain;
	
	$b_id=$_GET["b_id"];
	$id=$_GET["id"];
	$key = $_GET["key"];
		
	$tfreecaUrl = $domain.'board.php?mode=view&b_id='.$b_id.'&id='.$id.'&page=1';
	$headers = ['Referer: '.$tfreecaUrl];

	$fileUrl = $file_domain.$key;
	
	$data = get_html($fileUrl, $headers);
	
	preg_match("/\/(?<link>link.php\?[^\"]*)/", $data, $match);
	
	$headers = ['Referer: '.$fileUrl];
	
	$downloadUrl = $file_domain.$match[link];
	
	$data = get_html($downloadUrl,  $headers);
	
	return $data;
}

function get_html($url, $headers) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	
	$data = curl_exec($ch);

	curl_close($ch);

	return $data;
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }
   return (substr($haystack, -$length) === $needle);
}
?>