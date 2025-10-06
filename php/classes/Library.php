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

    public function processImage($path){
        if(!is_file($path)){
            return new WP_Error('library', 'Invalid filepath given');
        }

        $this->imagePath      = apply_filters('file_upload_path', $path);
        $this->imageMimeType  = mime_content_type($this->imagePath);
        $this->imageData      = base64_encode(file_get_contents($this->imagePath));

        if($this->engine == "chatgpt"){
            $command = [
                [
                    "type" => "input_text",
                    "text" => "Check this bookshelf picture, give JSON output with titles, optional authors, optional description from internet"
                ],
                [
                    "type" => "input_image",
                    "image_url" => "data:" . $this->imageMimeType . ";base64," . $this->imageData
                ]
            ];
            
            $json   = $this->chatGPT($command);
        }

        if($this->engine == "gemini"){
            $json   = $this->gemini();

            if(is_wp_error($json)){
                return $json;
            }
        }

        return $this->getTable($json);
    }

    public function openLibrary($title = '', $author = ''){
        $url = "https://openlibrary.org/search.json?q=";

        if(!empty($title)){
            $url .= urlencode("title: $title");
        }   

        if(!empty($author)){
            $url .= urlencode(" author: $author");
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$url&fields=key,title,author_name,subtitle,alternative_subtitle,cover_i,language,number_of_pages_median,first_publish_year,description,subjects");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if(empty($data) || empty($data['docs'])){
            return [];
        }

        foreach($data['docs'] as $index => $doc){
            if(strtolower($doc['title']) != strtolower($title)){
                continue;
            }

            if(!empty($doc['key'])){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://openlibrary.org{$doc['key']}.json");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                $response = curl_exec($ch);
                curl_close($ch);

                $workData   = json_decode($response, true);

                if(!empty($workData)){
                    $doc        = array_merge($doc, $workData);
                }
            }

            return $doc;
        }

        return $data['docs'][0];
    }

    public function chatGPT(array $command){
        $url            = 'https://api.openai.com/v1/chat/completions';

        $requestData = [
            "model" => "GPT-4o mini",
            "input" => [
                [
                    "role" => "user",
                    "content" => $command
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
            return $result['choices'][0]['message']['content'];
        }

        curl_close($ch);
    }

    public function gemini(){
        try{
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
                                    'title'         => new Schema(type: DataType::STRING),
                                    'authors'       => new Schema(type: DataType::STRING),
                                    'description'   => new Schema(type: DataType::STRING),
                                ],
                                required: ['title'],
                            )
                        )
                    )
                )
                ->generateContent([
                    'Check this bookshelf picture from left to right, give JSON output with titles, optional authors, optional description from internet',
                    new Blob(
                        mimeType: MimeType::from($this->imageMimeType),
                        data: $this->imageData
                    )
                ]);

            return $result->json();
        } catch (\ErrorException $e) {
            // Handle the specific Gemini\Exceptions\ErrorException
            error_log("Gemini Error: " . $e->getMessage());
            // You might want to return an error response to the user or take other corrective actions
            return new WP_Error('Gemini Error', $e->getMessage());
        } catch (\Exception $e) {
            // Catch any other general exceptions
            error_log("General Error: " . $e->getMessage());
            return new WP_Error('Gemini Error', $e->getMessage());
        }
    }

    /**
     * Gets a chat response
     * 
     * @param string    $message    Message to send
     * @param array     $history    Array of previous and and received messsages, default empty
     * 
     * @return  string              The Response
     */
    public function chatGemini($message, $history=[]){
        $client = Gemini::client($this->apiKey);

        $chat = $client
            ->generativeModel(model: 'gemini-2.0-flash')
            ->startChat(history: $history);

        $response = $chat->sendMessage($message);

        return $response->text();
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
        <div class='file-upload-wrap'>
            <?php
            echo \SIM\loaderImage(30, '', true);
            ?>
            <div id="progress-wrapper" class="hidden">
                <progress id="upload-progress" value="0" max="100"></progress>
                <span id="progress-percentage">   0%</span>
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
                    Select one or multiple picture(s) to check for a book or multiple books on bookshelf<br><br>
                    <input type='file' name='image-selector' accept='<?php apply_filters('sim-library-accepted-files', 'image/png, image/jpeg, image/webp');?>' class='formbuilder' multiple>
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

    private function existingBookRow($posts, $data, $categories, $location){
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
        $data->authors      = get_post_meta($post->ID, 'author');
        $data->description  = $post->post_content;
        $imageId            = get_post_meta($post->ID, 'image', true);

        $image              = "<img src='https://covers.openlibrary.org/b/id/$imageId-S.jpg' class='book-image' loading='lazy'>";

        ?>
            <tr class='existing-book processed'>
                <td class='image'>
                    <?php echo $image; ?>
                </td>
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
                    <?php echo $data->description;?>
                </td>
                <td>
                    <?php
                        $postCats   = wp_get_object_terms($post->ID, 'books', ['fields' => 'ids']);
                        foreach($categories as $category){
                            if(in_array($category->term_id, $postCats)){
                                echo $category->name.'<br>';
                            }
                        }
                    ?>
                </td>
                <td class='url'>
                    <a href='<?php echo get_post_meta($post->ID, 'url', true);?>' target='_blank'>View on OpenLibrary</a>
                </td>
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
    }

    public function getTable($json){
        wp_enqueue_script('sim_library_script');

        $categories	= get_categories( array(
            'orderby' 		=> 'name',
            'order'   		=> 'ASC',
            'taxonomy'		=> 'books',
            'hide_empty' 	=> false,
        ) );

        $location   = $_REQUEST['location'];

        $icon	    = "<img class='visibility-icon visible' src='".\SIM\PICTURESURL."/visible.png' width=20 height=20 loading='lazy' >";

        ob_start();
        ?>
        <style>
        .sim-table body tr:not(:first-child) {
            display: none;
        }
        </style>
        <div class='book table-wrapper' style='max-width:100vw;'>
            <h4>Books Identified in the picture</h4>
            <p>
                Please check the details below and change them where needed before adding them to the library.
            </p>
            <button type='button' class='hide-existing-books sim button'>Hide books already in the library</button>
            <button type='button' class='hide-processed-books sim button'>Hide processed books</button>
            <table class='sim-table'>
                <thead>
                    <tr>
                        <th>Picture <?php echo $icon; ?></th>
                        <th>Title</th>
                        <th>Authors</th>
                        <th>Subtitle <?php echo $icon; ?></th>
                        <th>Series <?php echo $icon; ?></th>
                        <th>Year <?php echo $icon; ?></th>
                        <th>Languague <?php echo $icon; ?></th>
                        <th>Pages <?php echo $icon; ?></th>
                        <th>Description <?php echo $icon; ?></th>
                        <th>Categories</th>
                        <th>URL <?php echo $icon; ?></th>
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

                            if(count($posts) > 0){
                                $this->existingBookRow($posts, $data, $categories, $location);
                            }else{
                                ?>
                                    <tr>
                                        <td class='image'>
                                        </td>
                                        <td>
                                            <input type='text' name='title' class='title' value="<?php echo $data->title; ?>">
                                        </td>
                                        <td>
                                            <div class="authors clone-divs-wrapper">
                                                <?php
                                                $authors = array_map('trim', explode(',', $data->authors));
                                                foreach($authors as $index => $author){
                                                    ?>
                                                    <div id="<?php echo $author;?>_div_<?php echo $index;?>" class="clone-div" data-divid="<?php echo $index;?>">
                                                        <div class='button-wrapper'>
                                                            <input type='text' name='author[]' class='author' value="<?php echo $author; ?>">
                                                            <button type="button" class="add button" style="flex: 1;">+</button>
                                                            <button type="button" class="remove button" style="flex: 1;">-</button>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td class='placeholder' colspan='7' style='text-align: center;'>
                                            <?php 
                                            echo \SIM\loaderImage(50, 'Fetching the book details...');
                                            ?>
                                        </td>
                                        <td>
                                            <textarea name='description' class='description' style='min-width: 300px;text-wrap: auto;' rows=2><?php echo $data->description; ?></textarea>
                                        </td>
                                        <td class='categories' style='text-align: left;'>
                                            <?php
                                                foreach($categories as $category){
                                                    echo "<label class='option-label category-select'>";
                                                        echo "<input type='checkbox' name='category_id[]' value='$category->cat_ID' data-name='$category->name'>";
						                                echo "$category->name";
                                                    echo "</label><br>";
                                                }
                                            ?>
                                        </td>
                                        <td class='url'>
                                            <a href='https://www.google.com/search?q=<?php echo urlencode("$data->title $data->authors book");?>' target='_blank'>Search on Google</a>
                                        </td>
                                        <td class='location hidden'><input type='text' name='location' value="<?php echo $location; ?>"></td>
                                        <td>
                                            <?php echo \SIM\loaderImage(50, 'Adding the book...', true);?>
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
     * Processes the author
     * 
     * @param string $author
     * @param int $postId
     * 
     * @return array
     */
    public function processAuthor($author, $postId){
        if(empty($author)){
            return [];
        }

        $author = sanitize_text_field($author);

        // Lastname first
        preg_match('/([a-zA-Z]+),\s*([a-zA-Z\s]+)/', $author, $matches);

        // If the author is not already in the format "Last, First"
        if(empty($matches)){
            $author         = strtolower($author);
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

                wp_set_post_terms($postId, [$author], 'authors', true);
            }
        }

        return $author;
    }

    /**
     * Creates a book post in the database
     * 
     * @param   array   $data   Array containg title, summar and optional meta values
     * 
     * @return string
     */
    public function createBook($data){
        $title          = ucfirst(strtolower(sanitize_text_field($data['title'])));
        $description    = sanitize_textarea_field($data['description']);

        if(!empty($this->checkForDuplicates($title ))){
            return new WP_Error('duplicate', 'This book is already in the library!');
        }

        //New post
		$post = array(
			'post_type'		=> 'book',
			'post_title'    => $title ,
			'post_content'  => $description,
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id()
		);

        // Insert the post into the database.
        $postId 	    = wp_insert_post( $post, true, false);
        $post['ID']		= $postId;

        // try again if needed
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

        // Store metas
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
                            $this->processAuthor($author, $postId);
                        }
                    }

                    continue;
                }

                add_post_meta($postId, $meta, $value);
            }
        }

        if(!empty($data['category_id'])){
            // Store categories
            foreach($data['category_id'] as $categoryId){
                wp_set_object_terms($postId, intval($categoryId), 'books', true);
            }
        }

        $url    = get_permalink($postId);

        return "<a href='$url' target='_blank'>View the book</a>";
    }
}
