<?php
/*
Plugin Name: SA-Facsimile
Plugin URI: http://www.skyaperture.com/plugin
Description: Migration DB to Kboard
Version: 0.1.0
Author: Dr.DeX
Author URI: 
License: GPLv2 (or later) 
*/

class SA_Facsimile {

	public function __construct() {
		require_once "shd/simple_html_dom.php";
		add_action( 'admin_menu', array( $this, 'init_the_page' ) );	
		add_action('admin_enqueue_scripts', array( $this, 'sa_facsimile' ) );
        	
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

	    // $menu_slug  = 'judge/judge-tool.php';
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
		$br = '<p></p>';
		echo '<form enctype="multipart/form-data" action="" method="post">
				<label for="file3">ZIP 파일</label>
				<input type="file" name="file3" id="file3"><br>'.$br.'

				<label for="run">업로드 :</label><span> 데이터를 
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

				echo $echo;	
			}
			// HTML Parse;
			$location = __DIR__ . '/upload/my/';
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
			    echo "Size: " . ($_FILES[$file]["size"] / 1024) . " kB<br>";

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
			$this->count = $this->count_files();
	}


	public function count_files() {
	    // integer starts at 0 before counting
	    $i = 0; 
	    $dir = __DIR__.'/upload/my/';
	    if ($handle = opendir($dir)) {
	        while (($file = readdir($handle)) !== false){
	            if (!in_array($file, array('.', '..')) && !is_dir($dir.$file)) 
	                $i++;
	        }
	    }
	    // prints out how many were in the directory
	    echo "There were $i files";	
	}

	public function count_files() {
	    // integer starts at 0 before counting
	    $i = 0; 
	    $dir = __DIR__.'/upload/my/';
	    if ($handle = opendir($dir)) {
	        while (($file = readdir($handle)) !== false){
	            if (!in_array($file, array('.', '..')) && !is_dir($dir.$file)) {



	            }
	        }
	    }
	}	
	
}


SA_Facsimile::instance();







    