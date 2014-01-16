<?php
/*
Plugin Name: SA-Facsimile
Plugin URI: http://www.skyaperture.com/plugin/facsimile
Description: Migration DB to Kboard
Version: 0.1.0
Author: Dr.DeX
Author URI: 
License: GPLv2 (or later) 
*/

class SA_Facsimile {

	public function __construct() {
		
		require_once "lib/simple_html_dom.php";
		add_action( 'admin_menu', array( $this, 'init_the_page' ) );	
		add_action( 'admin_enqueue_scripts', array( $this, 'sa_facsimile' ) );
   	
	}

	protected static $instance;

	public static function instance(){

		if (!isset(static::$instance)) {
			$className = __CLASS__;
			static::$instance = new $className;
		}
		return static::$instance;		
	}


	public function init_the_page() {
		// Setting up option page

	    $page_title = 'DB 이동';

	    $menu_title = 'DB 이동';

	    $capability = 'edit_others_posts';

	    $menu_slug  = 'sa-facsimile';

	    $function   = array( $this, 'admin_screen' );

	    $icon_url  = '';

	    $position   = 3;

	    add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );

	}

	public function sa_facsimile() {
		    wp_enqueue_style('sa-facsimile', plugin_dir_url( __FILE__ ).'/admin-style.css') ;
		    wp_enqueue_script('sa-script-app', plugin_dir_url( __FILE__ ).'/app.js') ;
	}

	public function admin_screen() {
		
		if ( ! defined( 'KBOARD_VERSION' ) ) {
			echo "<p>KBoard가 설치되어 있지 않습니다.</p>";
			return;
		} 		
		$br = '<p></p>';


		echo '<form enctype="multipart/form-data" action="" method="post">
				<label for="file3">ZIP 파일 :</label>
				<input type="file" name="file3" id="file3"><br>'.$br;
		echo '<label for=board_id>게시판 선택 :</label><select name=board_id>';
		while( $row = $this->hasNext() ){
			echo '<option value='.$row->uid.'>'.$row->board_name.'</option>';
		}
		echo '</select>';
		echo $br;
		echo '<label for="run">업로드 :</label><span> 데이터를 
				<input type="submit" class="button-primary" name="run" value="업로드" id="run"> </input> 합니다. </span><br>
				'.$br.'										
			 ';			


		$files = array();
		if ( isset( $_FILES ) && count( $_FILES ) != 0 ){
			foreach( $_FILES as $key => $file ) {
				if ( $key == 'file3' ) {
					$echo = "<p>ZIP 파일 ";
				}	
				if ( $file['error'] == 0 ) {
					$files[] = 0;
					$this->sa_unzip( $key );
					$echo .= " 업로드 완료</p>";
				} else {
					$files[] = 1;
					$echo .= "업로드 에러</p>";
					if ( $file['error'] != 4 ){
						$echo .= "<p>에러코드 : ". $file['error'] ."</p>";
					} else {
						$echo .= "<p>파일을 선택하지 않았습니다.</p>";
					}
				}
				$echo .= "<p>=========================</p>";
				
			}
			// HTML Parse;
			$location = __DIR__ . '/upload/my/';
			$target = $this->parse_files();
			$board = $_POST['board_id'];
			$this->search = 1;
			$this->secret = false;
			foreach ( $target as $trgt ){
				$this->title = $trgt[0];
				$this->content = $trgt[1];
				$this->name = $trgt[2];
				$this->board_id = $board;
				$this->insert_kboard( $trgt, $board );
			}
			echo $echo;	
		}

	}


	public function sa_unzip( $file ) {
		$allowedExts = array( 'zip' );
			$temp = explode(".", $_FILES[$file]["name"]);
			$extension = end($temp);
			$location = __DIR__ . '/upload/';

			if ( ( $_FILES[$file]["type"] == 'application/x-zip-compressed' ) && in_array($extension, $allowedExts) ) {
			  if ($_FILES[$file]["error"] > 0)
			    {
			    echo "Return Code: " . $_FILES[$file]["error"] . "<br>";
			    }
			  else
			    {
			    echo "Upload: " . $_FILES[$file]["name"] . "<br>";
			    echo "Size: " . (int)($_FILES[$file]["size"] / 1024) . " kB<br>";

				if ( $file == 'file3' ){
					$prod = 'db.' . $extension;
				}
			    move_uploaded_file($_FILES[$file]["tmp_name"],
			    $location . $prod );

			    }
		    
			} else {
			  echo "Invalid file";
			}  

			$zip = new ZipArchive();

			$res = $zip->open( $location . 'db.zip' );
			if ( $res === TRUE ){
				$zip->extractTo( $location .'/my/');
				$zip->close();
				unlink( $location . 'db.zip' );
			}
			$this->check_charset();
	
	}


	public function check_charset() {
	    $dir = __DIR__.'/upload/my/';
	    $files = glob( $dir . '*' );
	    if ( $files !== FALSE ){
	    	$count = count( $files );
	    }
	    $files_to_change = $files;
		foreach ( $files as $k => &$file ) {
			$f = explode( '/', $file );
			$fend = array_pop( $f );
			if ( ! preg_match( '/.(html|htm)$/', $fend ) )
				continue;

			preg_match_all( '/\d/', $fend, $fres );
			$fres = implode( '', $fres[0] );
			$f[] = $fres.".html";
			$file = implode( '/', $f );
		}

		 
		for ( $i = 0; $i < count( $files ); $i++ ){
			rename( $files_to_change[$i], $files[$i] );
		}

	}

	
	public function parse_files() {
		$location = __DIR__.'/upload/my/';
		$files = glob( $location . '*' );
	
		$target = array();
		$err = 0;
		foreach ( $files as $file ){
			$f = explode( '/', $file );
			$fend = array_pop( $f );
			if ( ! preg_match( '/.html$/', $fend ) )
				continue;	
		
			$html = @file_get_html( $file );

			if ( ! empty( $html ) ){
				$table = @$html->find('table', 0);
				if ( ! empty( $table ) ){
					$title = @$table->find('td', 1)->plaintext;

					$content = @$table->find('td', 5)->plaintext;	

					$name = @$table->find('td', 2)->plaintext;

					$title = iconv("EUC-KR", "UTF-8", $title );
					$content = iconv("EUC-KR", "UTF-8", $content );
					$content = mb_substr( $content, 5, NULL, 'UTF-8' );

					$name = iconv("EUC-KR", "UTF-8", $name );
					
					$name = mb_substr( $name, 4, NULL, 'UTF-8' );

					$target[] = array( $title, $content, $name  );	

					// unlink( $file );						
				}
			}

			unlink( $file );

		}



		return $target;
	}


	public function insert_kboard( $target, $board ){
		global $user_ID;

		$userdata = get_userdata($user_ID);
		
		$data['board_id'] = $board;
		$data['member_uid'] = intval($userdata->data->ID);
		$data['member_display'] = $target[2];
		$data['title'] = $target[0];
		$data['content'] = $target[1];
		$data['date'] = date("YmdHis", current_time('timestamp'));
		$data['view'] = 0;
		$data['category1'] = '';
		$data['category2'] = '';
		$data['secret'] = false;
		$data['notice'] = '';
		$data['search'] = $this->search;
		$data['thumbnail_file'] = '';
		$data['thumbnail_name'] = '';
		$data['password'] = $this->password?$this->password:'';
		
		foreach($data as $key => $value){
			$value = addslashes($value);
			$insert_key[] = "`$key`";
			$insert_data[] = "'$value'";
		}
		mysql_query("INSERT INTO `".KBOARD_DB_PREFIX."kboard_board_content` (".implode(',', $insert_key).") VALUE (".implode(',', $insert_data).")");	
		$insert_id = mysql_insert_id();
		if(!$insert_id) list($insert_id) = mysql_fetch_row(mysql_query("SELECT LAST_INSERT_ID()"));
		
		$this->insertPost($insert_id, $data['member_uid']);			
	}

	public function insertPost($content_uid, $member_uid){
		if($content_uid && $this->search>0 && $this->search<3){
			$kboard_post = array(
				'post_author'   => $member_uid,
				'post_title'    => $this->title,
				'post_content'  => ($this->secret=='true' || $this->search==2)?'':$this->content,
				'post_status'   => 'publish',
				'comment_status'=> 'closed',
				'ping_status'   => 'closed',
				'post_name'     => $content_uid,
				'post_parent'   => $this->board_id,
				'post_type'     => 'kboard'
			);
			wp_insert_post($kboard_post);
		}
	}	

	public function getList(){
		$resource = mysql_query("SELECT COUNT(*) FROM `".KBOARD_DB_PREFIX."kboard_board_setting` WHERE 1");
		list($this->total) = mysql_fetch_row($resource);
		$this->resource = mysql_query("SELECT * FROM `".KBOARD_DB_PREFIX."kboard_board_setting` WHERE 1 ORDER BY uid DESC");
		return $this->resource;
	}

	public function hasNext(){
		if(!$this->resource) $this->getList();
		$this->row = mysql_fetch_object($this->resource);
		return $this->row;
	}
}

SA_Facsimile::instance();
