<?php
namespace SIM\LIBRARY;
use SIM;
use thiagoalessio\TesseractOCR\TesseractOCR;

const MODULE_VERSION		= '1.0.0';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

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

add_filter('sim_module_library_functions', __NAMESPACE__.'\moduleFunctions', 10, 2);
function moduleFunctions($functionHtml, $settings){
	$library		= getLibrary($settings);

	ob_start();

	if(!empty($_FILES['books'])){
		echo $library->processImage($_FILES['books']['tmp_name']);
	}else{
		
        wp_enqueue_script('sim_library_script');
		?>
		<table class="sim table">
                <thead>
                    <tr>
                        <th>Picture</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Summary</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                                                    <tr>
                                    <td>
                                        <img src="https://m.media-amazon.com/images/I/51s1D0fGv1L._SL500_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="This One Thing I Do">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Jeanette Lockerbie and Franklin Graham">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-0849956211" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">Jeanette Lockerbie has been a missionary in some of the most remote and dangerous places in the world. In this book, she shares her experiences and insights on how to live a life of purpose and impact.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://m.media-amazon.com/images/I/512t8qXW2iL._SL500_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="Through My Eyes">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Tim Tebow">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-0062003010" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">In this autobiography, Tim Tebow, the Denver Broncos' quarterback, shares his story of faith, determination, and perseverance. From his childhood in the Philippines to his rise to fame in the NFL, Tebow inspires readers to live a life of passion and purpose.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1365440711i/17341616.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="Coming Back Stronger">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Drew Brees">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-0310341408" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">In Coming Back Stronger, New Orleans Saints quarterback Drew Brees shares the inspiring story of his comeback from a career-threatening injury. With honesty and humility, Brees reveals the challenges he faced and the lessons he learned on his journey to becoming one of the greatest quarterbacks of all time.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/I/41S1D+lXlXL._SX311_BO1,204,203,200_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="C.H. Spurgeon Autobiography: The Early Years">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="C.H. Spurgeon">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-1848712349" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">The Early Years is the first volume of C.H. Spurgeon's autobiography, covering his life from his birth in 1834 to his early years as a pastor. It is a fascinating account of the life of one of the most influential preachers of all time.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/I/51w-P28M6rL._SX311_BO1,204,203,200_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="Jungle Pilot">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Russell Hitt">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-0882705139" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">This is a book about Nate Saint, a missionary pilot who was killed in Ecuador in 1956. He was one of five missionaries who were killed while trying to reach the Waodani tribe with the gospel.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/I/41g5gL4g0XL._SX311_BO1,204,203,200_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="Surgeon on Call 24/7">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Harold P Padmore">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-1481299573" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">Follow Dr. Harold Padmore, an accomplished surgeon who answers God’s call to serve in West Africa. His skills are immediately tested in his makeshift operating room, where he encounters the daily challenges of war, poverty, and political unrest.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/I/51IWM9X8eZL._SX311_BO1,204,203,200_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="Neither Bomb Nor Bullet">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Andrew Boyd">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-0830774795" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">This is the story of Leah, a young woman who dedicated her life to serving God in Nigeria. She was kidnapped by Boko Haram in 2018 and has been held captive ever since. This book is a tribute to her courage and faith.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/I/41vX-sB7K9L._SX311_BO1,204,203,200_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="Preparation for Life and Ministry">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Danny McCain">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-1532605913" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">This is a book to help you prepare for life and ministry. It covers topics such as calling, character, competence, and commitment.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/I/41w8f6-W3AL._SX311_BO1,204,203,200_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="Preaching With Purpose">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Jay E. Adams">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-0310202419" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">This book offers a comprehensive guide to biblical preaching, emphasizing the importance of understanding and applying the Scriptures effectively. It provides practical insights for pastors and ministry leaders to deliver impactful sermons that transform lives and glorify God.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/I/41c5zB0XpBL._SX311_BO1,204,203,200_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="A Young Man After God" s="" own="" heart'="">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Jim George">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-0736979098" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">This book offers a guide to young men on how to live a life that is pleasing to God. It covers topics such as purity, integrity, and service.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/I/41B9R7p4EKL._SX311_BO1,204,203,200_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="The Master Plan">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Ian MacMillan">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-0060582461" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">A gripping novel by Ian MacMillan about a group of prisoners who hatch an elaborate plan to escape from a Siberian labor camp during World War II. The story follows their harrowing journey through the vast and unforgiving landscape, as they face countless challenges and dangers in their quest for freedom.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/I/51C5K7T796L._SX311_BO1,204,203,200_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="God of This City">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Aaron Boyd &amp; Craig Borlase">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-0830744194" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">This book explores the modern missions movement. It talks about how to reach people in today's world.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/I/41b8sQ3FfRL._SX311_BO1,204,203,200_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="Strange Fire in the Church">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Jon with James">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-1630703695" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">A book that explores the controversial topic of charismatic practices in the church, challenging certain manifestations and urging discernment based on biblical truth.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                                            <tr>
                                    <td>
                                        <img src="https://images-na.ssl-images-amazon.com/images/I/51jE31Vp3zL._SX311_BO1,204,203,200_.jpg" height="50">
                                    </td>
                                    <td>
                                        <input type="text" name="title" class="title" value="Drumming to a Different Beat">
                                    </td>
                                    <td>
                                        <input type="text" name="author" class="author" value="Various">
                                    </td>
                                    <td>
                                        <input type="text" name="isbn" class="isbn" value="978-0976090612" style="max-width: 120px;">
                                    </td>
                                    <td>
                                        <textarea name="summary" class="summary" style="min-width: 300px;" rows="5">This book shares stories of individuals who have served the Lord in unique and unconventional ways. It is a collection of diverse experiences and perspectives.</textarea>
                                    </td>
                                    <td>
                                        <div class="loadergif_wrapper hidden"><img class="loadergif" src="https://localhost/simnigeria/wp-content/plugins/sim-plugin/includes/pictures/loading.gif" width="50" loading="lazy">Adding the book...</div>
                                        <button type="button" class="add-book">Add book to the library</button>
                                        <button type="button" class="delete-book">Delete</button>
                                    </td>
                                </tr>
                                            </tbody>
            </table>

		<h4>Add Books</h4>
		<p>
			Upload a picture of a book or bookshelf to add book(s).
		</p>
		<form method='POST' enctype="multipart/form-data">
			<label>
				Select a picture
				<input type='file' name='books'>
			</label>
			<br>
			<button type='submit' name='import-books'>Upload the picture</button>
		</form>

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