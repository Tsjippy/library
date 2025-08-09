<?php
namespace SIM\LIBRARY;
use SIM;
use thiagoalessio\TesseractOCR\TesseractOCR;

const MODULE_VERSION		= '1.1.0';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

const METAS = [
    'subtitle'  		=> 'text',
    'author'   			=> 'array',
    'series'    		=> 'text',
    'year'      		=> 'number',
    'languague' 		=> 'text',
    'age'       		=> 'text',
    'pages'     		=> 'number',
	'image'				=> 'text',
	'location'			=> 'array',
	'url'				=> 'url',
];

require( MODULE_PATH  . 'lib/vendor/autoload.php');

//run on module activation
add_action('sim_module_activated', __NAMESPACE__.'\moduleActivated');
function moduleActivated($options){
	$library = new Library();
	$library->createDbTable();
}

add_filter('sim_module_library_after_save', __NAMESPACE__.'\moduleUpdated', 10, 3);
function moduleUpdated($newOptions, $moduleSlug, $oldOptions){
	//scheduleTasks();

	return $newOptions;
}

//run on module deactivation
add_action('sim_module_library_deactivated', __NAMESPACE__.'\moduleDeActivated');
function moduleDeActivated($options){
	
}

add_filter('sim_submenu_library_description', __NAMESPACE__.'\moduleDescription', 10, 3);
function moduleDescription($description, $moduleName){
	ob_start();
	?>
	<p>
		This module makes it possible to upload Account statements to the website.<br>
		These statements will be visible on the dashboard page of an user.<br>
		<br>
		This module adds one shortcode: <code>[account_statements]</code>
		<br>
		This module depends on the postie plugin and the user management module.<br>
	</p>
	<?php

	return $description.ob_get_clean();
}

add_filter('sim_submenu_library_options', __NAMESPACE__.'\moduleOptions', 10, 3);
function moduleOptions($optionsHtml, $settings, $moduleName){
	ob_start();
	
    ?>
		<label>
			<h4>ChatGPT Api Key</h4>
			<input type='text' name='chatgpt-api-key' value='<?php echo $settings['chatgpt-api-key'];?>' style='width: -webkit-fill-available;'>
		</label>
		<br>
		<label>
			<h4>Gemini Api Key</h4>
			<input type='text' name='gemini-api-key' value='<?php echo $settings['gemini-api-key'];?>' style='width: -webkit-fill-available;'>
		</label>
		<br>		
	<?php

	return $optionsHtml.ob_get_clean();
}

add_filter('sim_module_library_data', __NAMESPACE__.'\moduleData', 10, 2);
function moduleData($dataHtml, $settings){
	$library		= getLibrary($settings);

	return $dataHtml;
}

add_filter('sim_email_library_settings', __NAMESPACE__.'\emailSettings', 10, 2);
function emailSettings($optionsHtml, $settings){
	ob_start();

	?>
	<label>
		Define the e-mail people get when they need to fill in some mandatory form information.<br>
		There is one e-mail to adults, and one to parents of children with missing info.<br>
	</label>
	<br>

	<?php
	$emails    = new Email(wp_get_current_user());
	$emails->printPlaceholders();
	?>

	<h4>E-mail to adults</h4>
	<?php

	$emails->printInputs($settings);

	return $optionsHtml.ob_get_clean();
}

function updateBookMetas(){
	SIM\printArray('Starting Updating Metas');
	$library		= new Library();

	$books = get_posts(
		array(
			'orderby' 		=> 'post_title',
			'order' 		=> 'asc',
			'post_status' 	=> 'any',
			'post_type'     => 	'book',
			'posts_per_page'=> -1
		)
	);

	foreach($books as $book){
		$title		= $book->post_title;
		$authors	= get_post_meta($book->ID, 'author');
		$data		= $library->openLibrary($title, $authors[0] ?? '');

		if(empty($data) || empty($data['author_name'])){
			$data		= $library->openLibrary($title);

			if(empty($data) || empty($data['author_name'])){
				continue;
			}
		}

		if($data['author_name'] != $authors){
			delete_post_meta($book->ID, 'author');
			wp_delete_object_term_relationships($book->ID, 'authors');

			foreach($data['author_name'] as $author){
				$library->processAuthor($author, $book->ID);
			}

			update_post_meta($book->ID, 'image', $data['cover_i']);

			if($title != $data['title']){
				wp_update_post(
					array(
						'ID'			=> $book->ID,
						'post_title'	=> $data['title']
					)
				);
			}

			delete_post_meta($book->ID, 'subtitle');
			if(!empty($data['subtitle'])){
				update_post_meta($book->ID, 'subtitle', $data['subtitle']);
			}
		}

		update_post_meta($book->ID, 'url', 'https://openlibrary.org/'.$data['key']);
	}

	SIM\printArray('Finished Updating Metas');
}

add_filter('sim_module_library_functions', __NAMESPACE__.'\moduleFunctions', 10, 2);
function moduleFunctions($functionHtml, $settings){
	$library		= getLibrary($settings);

	ob_start();

	if(!empty($_FILES['books'])){
		echo $library->processImage($_FILES['books']['tmp_name']);
	}else{
		echo $library->getFileHtml();
	}
	
	if(!empty($_REQUEST['updatemeta'])){
		wp_schedule_single_event(time(), 'sim-updatemetas');

		?>
		<div class='success'>
			<p>Updating book metas in the background. This can take a while.</p>
		</div>
		<?php	
	}else{
		?>
		<br>
		<br>
		<h4>Sync Books with OpenLibrary.org</h4>
		<form method='post'>
			<input type='hidden' name='updatemeta' value='updatemeta'>
			<button class='button sim small'>Update Book Metas</button>
		<?php
	}

	return $functionHtml.ob_get_clean();
}

add_action('sim_module_actions', __NAMESPACE__.'\moduleActions' );
function moduleActions(){
	if(empty($_POST['books'])){
		return;
	}

	global $Modules;

	$library		= getLibrary($Modules[MODULE_SLUG]);

	foreach($_POST['books'] as $book){
		$library->createBook($book);
	}
}

function getLibrary($settings){

	if(!empty($settings['chatgpt-api-key'])){
		$engine	= 'chatgpt';
	}

	if(!empty($settings['gemini-api-key'])){
		$engine	= 'gemini';
	}
	
	return new Library(apiKey: $settings["$engine-api-key"], engine: $engine);
}