<?php
namespace SIM\LIBRARY;

require( MODULE_PATH  . 'lib/vendor/autoload.php');

use Gemini;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;
use WP_Embed;
use WP_Error;

class Library{
    private $apiKey;
    private $engine;
    public  $imagePath;
    private $imageMimeType;
    private $imageData;
    public  $tableName;

    public function __construct($apiKey='', $engine='') {
        global $wpdb;

        $this->tableName        = $wpdb->prefix.'sim_books';

        $this->apiKey           = $apiKey;
        $this->engine           = strtolower($engine);
    }

    /**
	 * Creates the tables for this module
	 */
	public function createDbTable(){
		if ( !function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . '/wp-admin/install-helper.php';
		}
		
		//only create db if it does not exist
		global $wpdb;
		$charsetCollate = $wpdb->get_charset_collate();

		// Books table
		$sql = "CREATE TABLE {$this->tableName} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			title tinytext NOT NULL,
			author text,
			summary LONGTEXT,
			picture text,
			url text,
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->tableName, $sql );
	}

    public function processImage($path){
        if(!is_file($path)){
            return new WP_Error('library', 'Invalid filepath given');
        }

        $this->imagePath      = $path;
        $this->imageMimeType  = mime_content_type($this->imagePath);
        $this->imageData      = base64_encode(file_get_contents($this->imagePath));

        if($this->engine == "chatgpt"){
            return $this->chatGPT();
        }

        if($this->engine == "gemini"){
            return $this->gemini();
        }
    }

    private function chatGPT(){
        $url            = 'https://api.openai.com/v1/chat/completions';

        $requestData = [
            "model" => "GPT-4o mini",
            "input" => [
                [
                    "role" => "user",
                    "content" => [
                        [
                            "type" => "input_text",
                            "text" => "Check this bookshelf picture, give JSON output with titles, optional authors, optional summary from internet"
                        ],
                        [
                            "type" => "input_image",
                            "image_url" => "data:" . $this->imageMimeType . ";base64," . $this->imageData
                        ]
                    ]
                ]
            ],
            "temperature" => 0.2,
            "max_output_tokens" => 10000
        ];

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer $this->apiKey"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo "Fout: " . curl_error($ch);
        } else {
            $result = json_decode($response, true);

            if(!empty($result['error'])){
                echo "<div class='error'>{$result['error']['message']}</div>";
                //return new WP_Error('chatGPT Error', $result['error']['message']);
            }

            // Toon alleen het gegenereerde antwoord (verwachte JSON-string)
            echo $result['choices'][0]['message']['content'];
        }

        curl_close($ch);
    }

    private function gemini(){
        $client = Gemini::client($this->apiKey);

        $result = $client
            ->generativeModel(model: 'gemini-2.0-flash')
            ->withGenerationConfig(
                generationConfig: new GenerationConfig(
                    responseMimeType: ResponseMimeType::APPLICATION_JSON,
                    responseSchema: new Schema(
                        type: DataType::ARRAY,
                        items: new Schema(
                            type: DataType::OBJECT,
                            properties: [
                                'title'     => new Schema(type: DataType::STRING),
                                'authors'   => new Schema(type: DataType::STRING),
                                'summary'   => new Schema(type: DataType::STRING),
                            ],
                            required: ['title'],
                        )
                    )
                )
            )
            ->generateContent([
                'Check this bookshelf picture, give JSON output with titles, optional authors, optional summary from internet',
                new Blob(
                    mimeType: MimeType::from($this->imageMimeType),
                    data: $this->imageData
                )
            ]);

        return $this->getTable($result->json());
    }

    /**
     * Get book locations from the database
     */
    public function getLocations(){
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT DISTINCT pm.meta_value
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
            AND p.post_type = %s",
            'location',
            'book'
        );

        return $wpdb->get_col( $sql );
    }

    /**
     * Prints the html to select a file
     */
    public function getFileHtml(){
        ob_start();
        wp_enqueue_script('sim_library_script');
		?>
        <div class='file_upload_wrap'>
            <div class='loadergif_wrapper hidden'>
                <span class='uploadmessage'></span>
                <img class='loadergif' src='<?php echo \SIM\LOADERIMAGEURL;?>' loading='lazy' style='height: 30px;'>
            </div>

            <div id="progress-wrapper" class="hidden">
                <progress id="upload_progress" value="0" max="100"></progress>
                <span id="progress_percentage">   0%</span>
            </div>

            <div class='image-selector-wrap'>
                <h4>Book location</h4>
                <input type='text' class='book-location' placeholder='Enter the location of the books' required style='width: -webkit-fill-available;' list='book-locations'>
                <datalist id='book-locations'>
                    <?php
                        foreach($this->getLocations() as $location){
                            echo "<option value='$location'>";
                        }
                    ?>
                </datalist>

                <h4>Select picture</h4>
                <label>
                    Select one ore multiple picture(s) to check for a book or multiple books on bookshelf<br><br>
                    <input type='file' name='image-selector' accept='image/png, image/jpeg, image/webp' class='formbuilder' multiple>
                </label>
            </div>
        </div>


		<?php
        return ob_get_clean();
    }

    /**
     * Checks if a book is already in the database
     */
    private function checkForDuplicates($title){
        // Check if already there
        return get_posts(
            array(
                'post_type'              => 'book',
                'title'                  => $title,
                'post_status'            => 'all',
                'numberposts'            => -1,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,           
                'orderby'                => 'post_date ID',
                'order'                  => 'ASC',
            )
        );
    }

    public function getTable($json){
        wp_enqueue_script('sim_library_script');

        ob_start();
        ?>
        <div class='book-table-wrapper'>
            <h4>Books Identified in the picture</h4>
            <p>
                Please check the details below and change them where needed before adding them to the library.
            </p>
            <table class='sim-table'>
                <thead>
                    <tr>
                        <th>Picture</th>
                        <th>Title</th>
                        <th>Authors</th>
                        <th>Subtitle</th>
                        <th>Series</th>
                        <th>Year</th>
                        <th>Languague</th>
                        <th>Pages</th>
                        <th>Summary</th>
                        <th>URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                        foreach($json as $index=>$data){
                            if(empty($data->authors)){
                                $data->authors	= '';
                            }else{
                                $data->authors	= trim(preg_split("/([\/&,+]|\bwith\b|\band\b)/", $data->authors)[0]);
                            }

                            $posts      = $this->checkForDuplicates($data->title);

                            $location = $_REQUEST['location'];

                            if(count($posts) > 0){
                                // Already in the database, so skip
                                if(count($posts) > 1){
                                    foreach($posts as $post){
                                        if(in_array($data->authors, get_post_meta($post->ID, 'author'))){
                                            // More than one book found, we show this one
                                            break;
                                        }
                                    }
                                }else{
                                    $post           = $posts[0];
                                }

                                // More than one book found, we show the last one
                                $data->authors  = get_post_meta($post->ID, 'author');
                                $data->summary  = $post->post_content;
                                $imageId        = get_post_meta($post->ID, 'image', true);

                                $image          = "<img src='https://covers.openlibrary.org/b/id/$imageId-S.jpg' class='book-image' loading='lazy'>";

                                ?>
                                    <tr class='existing-book'>
                                        <td class='image'><?php echo $image; ?></td>
                                        <td>
                                            <?php echo $data->title; ?>
                                        </td>
                                        <td>
                                            <?php echo implode("<br>", $data->authors); ?>
                                        </td>
                                        <td>
                                            <?php echo get_post_meta($post->ID, 'subtitle', true);?>
                                        </td>
                                        <td>
                                            <?php echo get_post_meta($post->ID, 'series', true);?>
                                        </td>
                                        <td>
                                            <?php echo get_post_meta($post->ID, 'year', true);?>
                                        </td>
                                        <td>
                                            <?php echo get_post_meta($post->ID, 'language', true);?>
                                        </td>
                                        <td>
                                            <?php echo get_post_meta($post->ID, 'pages', true);?>
                                        </td>
                                        <td style='min-width: 300px;text-wrap: auto;'>
                                            <?php echo $data->summary;?>
                                        </td>
                                        <td class='url'></td>
                                        <td>
                                            This book is already in the library.<br>
                                            <?php
                                            $locations    = get_post_meta($post->ID, 'location');

                                            if(!in_array($location, $locations)){
                                                add_post_meta($post->ID, 'location', $location);

                                                ?>
                                                <br>
                                                I have added the location <strong><?php echo $location; ?></strong> to this book.<br>
                                                <?php
                                            }

                                            ?>
                                            <a href='<?php echo get_permalink($post->ID); ?>' target='_blank'>View it here.</a>
                                        </td>
                                    </tr>
                                <?php
                            }else{
                                ?>
                                    <tr>
                                        <td class='image'></td>
                                        <td>
                                            <input type='text' name='title' class='title' value="<?php echo $data->title; ?>">
                                        </td>
                                        <td>
                                            <div class="authors clone_divs_wrapper">
                                                <?php
                                                $authors = array_map('trim', explode(',', $data->authors));
                                                foreach($authors as $index => $author){
                                                    ?>
                                                    <div id="<?php echo $author;?>_div_<?php echo $index;?>" class="clone_div" data-divid="<?php echo $index;?>">
                                                        <div class='buttonwrapper'>
                                                            <input type='text' name='author[]' class='author' value="<?php echo $author; ?>">
                                                            <button type="button" class="add button" style="flex: 1;">+</button>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td class='placeholder' colspan='7' style='text-align: center;'>
                                            <img class='loadergif' src='<?php echo \SIM\LOADERIMAGEURL;?>' width=50 loading='lazy'>Fetching the book details...
                                        </td>
                                        <td>
                                            <textarea name='summary' class='summary' style='min-width: 300px;text-wrap: auto;' rows=2><?php echo $data->summary; ?></textarea>
                                        </td>
                                        <td class='url'></td>
                                        <td class='location hidden'><input type='text' name='location' value="<?php echo $location; ?>"></td>
                                        <td>
                                            <div class='loadergif_wrapper hidden'><img class='loadergif' src='<?php echo \SIM\LOADERIMAGEURL;?>' width=50 loading='lazy'>Adding the book...</div>
                                            <button type='button' class='add-book sim button'>Add book to the library</button>
                                            <button type='button' class='delete-book sim button'>Delete</button>
                                        </td>
                                    </tr>
                                <?php
                            }
                        }
                    ?>
                </tbody>
            </table>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Creates a post with type book
     */
    public function storeInDb( array $data){
        global $wpdb;

        // Check if already in the database

        $wpdb->insert(
			$this->tableName,
			$data
		);
		
		if(!empty($wpdb->last_error)){
			return new \WP_Error('error', $wpdb->print_error());
		}

		return $wpdb->insert_id;
    }

    /**
     * Processes the authors
     * 
     * @param string $authorString
     * @param int $postId
     * 
     * @return array
     */
    public function processAuthors($author, $postId){
        if(empty($author)){
            return [];
        }

        // Lastname first
        preg_match('/([a-zA-Z]+),\s*([a-zA-Z\s]+)/', $author, $matches);

        // If the author is not already in the format "Last, First"
        if(empty($matches)){
            $author         = strtolower(sanitize_text_field($author));
            $authorNames    = explode(' ', $author);
            $author         = ucfirst(trim(end($authorNames)));

            if(count($authorNames) > 1){
                // Last name first
                $author         = $author .', ';

                // Remove the last name from the array
                array_pop($authorNames);

                // Add the rest of the names
                $author         .= implode(' ', array_map('trim', array_map('ucfirst', $authorNames)));
            }
        }

        if(!empty($author)){
            $curValues  = get_post_meta($postId, 'author');

            if(!in_array($author, $curValues)){
                // Add the author to the post meta
                add_post_meta($postId, 'author', $author);
            }
        }

        wp_set_post_terms($postId, [$author], 'authors', true);
    }

    /**
     * Creates a book post in the database
     */
    public function createBook($data){
        $title          = ucfirst(strtolower(sanitize_text_field($data['title'])));
        $summary        = sanitize_textarea_field($data['summary']);

        if(!empty($this->checkForDuplicates($title ))){
            return new WP_Error('duplicate', 'This book is already in the library!');
        }

        //New post
		$post = array(
			'post_type'		=> 'book',
			'post_title'    => $title ,
			'post_content'  => $summary ,
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id()
		);

        if(!empty($data['categories'])){
            $post['post_category'] = array_map('sanitize_text_field', $_POST['categories']);
        }

        // Insert the post into the database.
        $postId 	    = wp_insert_post( $post, true, false);
        $post['ID']		= $postId;

		if(is_wp_error($postId)){
			// check for errors
			if(is_wp_error($postId)){
				// most likely some invalid data in post, try to fix it.
				if($postId->get_error_message() == "Could not update post in the database."){
					$illigalChars	= [];
					foreach(str_split($post['post_content']) as $index=>$chr){
						json_encode($chr);
						if(json_last_error() == 5){
							$illigalChars[$index] = $chr;
						}elseif(json_last_error() == 0 && !empty($illigalChars)){
							$post['post_content']	= str_replace(implode('', $illigalChars), mb_convert_encoding(implode('', $illigalChars), "UTF-8", "auto"), $post['post_content']);
							$illigalChars	= [];
						}
					}

					// try to update again
					$postId 	= wp_insert_post( $post, true, false);
					$post['ID']	= $postId;

					if(is_wp_error($postId)){
						return $postId;
					}
				}else{
					return $postId;
				}				
			}
		}elseif($postId === 0){
			return new WP_Error('Inserting post error', "Could not create the book!");
		}

        foreach(METAS as $meta => $type){
            // Add post meta
            if(!empty($_POST[$meta])){
                if(is_array($_POST[$meta])){
                    $value = array_map('sanitize_text_field', $_POST[$meta]);
                }else{
                    $value = sanitize_text_field($_POST[$meta]);
                }

                if($meta == 'location'){
                    $locations   = get_post_meta($postId, 'location');
                    
                    // only add a new location if needed
                    if(in_array($value, $locations)){
                        continue;
                    }

                    wp_set_post_terms($postId, [$value], 'book-locations', true);
                }elseif($meta == 'author'){
                    if(is_array($value)){
                        foreach($value as $index=>$author){
                            $this->processAuthors($author, $postId);
                        }
                    }

                    continue;
                }

                add_post_meta($postId, $meta, $value);
            }
        }

        $url    = get_permalink($postId);

        return "<a href='$url' target='_blank'>View the book</a>";
    }
}
