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
			isbn text,
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
                            "text" => "Check this bookshelf picture, give JSON output with titles, optional authors, optional ISBN from internet"
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
                                'author'    => new Schema(type: DataType::STRING),
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
            'locations',
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
                <input type='text' class='book-location' placeholder='Enter the location of the books' style='width: -webkit-fill-available;' list='book-locations'>
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

    public function getTable($json){
        wp_enqueue_script('sim_library_script');

        ob_start();
        ?>   
        <h4>Books Identified in the picture</h4>
        <p>
            Please check the details below and chnage them where needed before approving them to be added to the library.
        </p>
            <table class='sim-table'>
                <thead>
                    <tr>
                        <th>Picture</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Subtitle</th>
                        <th class='hidden'>ISBN 13</th>
                        <th class='hidden'>ISBN 10</th>
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
                            if(empty($data->author)){
                                $data->author	= '';
                            }

                            // Check if already there
                            $posts = get_posts(
                                array(
                                    'post_type'              => 'book',
                                    'title'                  => $data->title,
                                    'post_status'            => 'all',
                                    'numberposts'            => -1,
                                    'update_post_term_cache' => false,
                                    'update_post_meta_cache' => false,           
                                    'orderby'                => 'post_date ID',
                                    'order'                  => 'ASC',
                                )
                            );

                            $location = $_REQUEST['location'];

                            if(count($posts) > 0){
                                // Already in the database, so skip
                                if(count($posts) > 1){
                                    foreach($posts as $post){
                                        if(get_post_meta($post->ID, 'author', true) ==  $data->author){
                                            // More than one book found, we show this one
                                            break;
                                        }
                                    }
                                }else{
                                    $post           = $posts[0];
                                }

                                // More than one book found, we show the last one
                                $data->author   = get_post_meta($post->ID, 'author', true);
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
                                            <?php echo $data->author; ?>
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
                                            $locations    = get_post_meta($post->ID, 'locations');

                                            if(!in_array($location, $locations)){
                                                add_post_meta($post->ID, 'locations', $location);

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
                                            <input type='text' name='title' class='title' value='<?php echo $data->title; ?>'>
                                        </td>
                                        <td>
                                            <input type='text' name='author' class='author' value='<?php echo $data->author; ?>'>
                                        </td>
                                        <td class='placeholder' colspan='7' style='text-align: center;'>
                                            <img class='loadergif' src='<?php echo \SIM\LOADERIMAGEURL;?>' width=50 loading='lazy'>Fetching the book details...
                                        </td>
                                        <td>
                                            <textarea name='summary' class='summary' style='min-width: 300px;text-wrap: auto;' rows=2><?php echo $data->summary; ?></textarea>
                                        </td>
                                        <td class='url'></td>
                                        <td class='locations hidden'><input type='text' name='locations' value='<?php echo $location; ?>'></td>
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

    public function createBook($data){
        //New post
		$post = array(
			'post_type'		=> 'book',
			'post_title'    => $data['title'],
			'post_content'  => $data['summary'],
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id()
		);

        if(!empty($data['categories'])){
            $post['post_category'] = $data['categories'];
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
                $value = sanitize_text_field($_POST[$meta]);

                if($meta == 'location'){
                    $locations   = get_post_meta($postId, 'locations');
                    
                    // only add a new location if needed
                    if(in_array($value, $locations)){
                        continue;
                    }
                }

                add_post_meta($postId, $meta, $value);
            }
        }

        $url    = get_permalink($postId);

        return "<a href='$url' target='_blank'>View the book</a>";
    }
}
