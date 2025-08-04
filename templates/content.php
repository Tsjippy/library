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
		margin-top:10px;
		display: flex;
		flex-wrap: wrap;
	}

	.book.meta{
		margin-right: 10px;
	}

	.cat_card{
		padding: 10px;
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
			if(is_user_logged_in()){
			?>
				<div class='author'>
					Shared by: <a href='<?php echo SIM\maybeGetUserPageUrl(get_the_author_meta('ID')) ?>'><?php the_author(); ?></a>
				</div>
				<?php
				if($archive){
					?>
					<div class='picture' style='margin-top:10px;'>
						<?php
						the_post_thumbnail([250,200]);
						?>
					</div>
					<?php
				}
			}
			
			?>	
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

			<div class='book metas'>
				<div class='category book meta'>
				 <h4>Genres</h4>
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
						$url	= SIM\pathToUrl(MODULE_PATH.'pictures/category.png');
						echo "<img src='$url' alt='category' loading='lazy' class='book_icon'>";
						
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
					}
					?>
				</div>

				<?php

				foreach(METAS as $meta=>$type){
					echo "<div class='$meta book meta'>";
					
					$value		= get_post_meta(get_the_ID(),$meta,true);
					if(!empty($value)){
					 
					 if($type == 'url'){
						 $value = "<a href='$value'>$value</a>";
							}
						elseif($type == 'date'){
						 $value = $value;
							}
						$imageUrl 	= SIM\pathToUrl(MODULE_PATH."pictures/{$meta}.png");
						echo "<img src='$imageUrl' alt='$meta' loading='lazy' class='book_icon'> $value";
					}
					
					echo "</div>";
				}

				do_action('sim_inside_book_metas');
				?>
			</div>
		</div>
	</div>
</article>
