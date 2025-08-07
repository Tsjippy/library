<?php
namespace SIM\LIBRARY;
use SIM;

/**
 * The content of a book shared between a single post, archive or the recipes page.
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$archive	= false;
if(is_tax() || is_archive()){
	$archive	= true;
}

?>
<style>
	.metas{
		margin-top:		10px;
		display: 		flex;
		flex-wrap: 		wrap;
	}

	.book.meta{
		margin-right: 	10px;
	}

	.cat_card{
		padding: 		10px;
	}

	div.picture{
		margin-top:		10px;
		text-align: 	center;
	}

	.book-image{
		min-width: 		150px;
		padding:		10px;
	}

	@media only screen and (min-width: 600px) {
		.entry-content{
			display: 	flex;
		}
		.book-image{
			padding-top: 	50px;
		}
	}

	@media only screen and (max-width: 600px) {
		.book.meta{
			flex-basis: 	45%;
		}
	}
</style>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="cat_card<?php if($archive){echo ' inside-article';}?>">
		
		<?php
		if($archive){
			$url = get_permalink(get_the_ID());
			echo the_title( "<h3 class='archivetitle'><a href='$url'>", '</a></h3>' );
		}else{
			do_action( 'sim_before_content');
		}
		?>
		<div class='entry-content<?php if($archive){echo ' archive';}?>'>
			<?php			
			$id			= get_post_meta(get_the_ID(), 'image', true);

			if(!empty($id)){
				$size	= 'M';
				$url	= "https://covers.openlibrary.org/b/id/$id-$size.jpg";

				?>
				<div class='picture'>
					<img src='<?php echo $url;?>' class='book-image' loading='lazy'>
				</div>
				<?php
			}

			?>
			<div class='book-description'>
				<?php

				if(is_user_logged_in()){
					?>
					<div class='author'>
						Shared by: <a href='<?php echo SIM\maybeGetUserPageUrl(get_the_author_meta('ID')) ?>'><?php echo get_the_author(); ?></a>
					</div>
					<?php
				}

				?>	
				<div class='book metas'>
					<?php
					$categories = wp_get_post_terms(
						get_the_ID(),
						'books',
						array(
							'orderby'   => 'name',
							'order'     => 'ASC',
							'fields'    => 'id=>name'
						)
					);
					
					if(!empty($categories)){
						?>
						<div class='category book meta'>
							<h4>Genres</h4><?php
							$url	= SIM\pathToUrl(MODULE_PATH.'pictures/category.png');
							echo "<img src='$url' alt='category' loading='lazy' class='book-icon'>";
							
							//First loop over the cat to see if any parent cat needs to be removed
							foreach($categories as $id=>$category){
								//Get the child categories of this category
								$children = get_term_children($id,'books');
								
								//Loop over the children to see if one of them is also in he cat array
								foreach($children as $child){
									if(isset($categories[$child])){
										unset($categories[$id]);
										break;
									}
								}
							}
							
							//now loop over the array to print the categories
							$lastKey	 = array_key_last($categories);
							foreach($categories as $id=>$category){
								//Only show the category if all of its subcats are not there
								$url = get_term_link($id);
								$category = ucfirst($category);
								echo "<a href='$url' target='_blank'>$category</a>";
								
								if($id != $lastKey){
									echo ', ';
								}
							}
						?>
						</div>
						<?php
					}

					foreach(METAS as $meta=>$type){
						if($meta == 'image'){
							continue;
						}

						$single	= true;
						if($type == 'array'){
							$single	= false;
						}
						$value		= get_post_meta(get_the_ID(), $meta, $single);

						if(!empty($value)){
							if($type == 'url'){
								$value = "<a href='$value'>$value</a>";
							}elseif($meta == 'author' || $meta == 'location'){
								$taxonomy = $meta == 'author' ? 'authors' : 'book-locations';

								$terms = wp_get_post_terms(
									get_the_ID(),
									$taxonomy,
									array(
										'orderby'   => 'name',
										'order'     => 'ASC',
										'fields'    => 'id=>name'
									)
								);
								
								$links	= [];
								foreach($terms as $id=>$termName){
									if($meta == 'author'){
										$splittedName 	= explode(', ', $termName);

										if(count($splittedName) > 1){
											$lastName 		= $splittedName[0];
											$firstnames 	= implode(' ', array_slice($splittedName, 1));
											$termName 			= "$firstnames $lastName";
										}else{	
											$termName = ucfirst($category->name);
										}
									}

									$url		= get_category_link($id);
									$links[]	= "<a href='$url' target='_blank'>$termName</a>";
								}

								$value = implode('<br>', $links);
							}elseif(is_array($value)){
								if(is_array($value[0])){
									//If the value is an array of arrays, we need to implode the inner arrays
									foreach($value as $index=>$innerValue){
										$value[$index] = implode(', ', $innerValue);
									}
								}
								$value = implode('<br>', $value);
							}	
														
							$imageUrl 	= SIM\pathToUrl(MODULE_PATH."pictures/{$meta}.png");

							echo "<div class='$meta book meta'>";
								echo "<div class='flex meta-wrapper'>";
									echo "<img src='$imageUrl' alt='$meta' loading='lazy' class='book_icon' title='$meta'><div>$value</div>";
								echo "</div>";
							echo "</div>";
						}
					}

					do_action('sim_inside_book_metas');
					?>
				</div>

				<div class="description book">
					<?php
					//Only show summary on archive pages
					if($archive){
						$excerpt = force_balance_tags(wp_kses_post( get_the_excerpt()));
						if(empty($excerpt)){
							$url = get_permalink();
							echo "<br><a href='$url'>View description »</a>";
						}else{
							echo $excerpt;
						}
					//Show everything including category specific content
					}else{
						if(empty($post->post_content)){
							echo apply_filters('sim_empty_description', 'No content found...', $post);
						}

						the_content();
					}

					wp_link_pages(
						array(
							'before' => '<div class="page-links">Pages:',
							'after'  => '</div>',
						)
					);
					?>
				</div>
			</div>
		</div>
	</div>
</article>
