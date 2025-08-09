<?php
namespace SIM\LIBRARY;
use SIM;

/**
 * The layout specific for the page with the slug 'books' i.e. sim.org/books.
 * Displays all the post of the book type
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Redirect to the first term if no term is specified
$exploded 	= explode('/books/', $_SERVER['REQUEST_URI']);
if(!empty($exploded[1])){
	$taxonomy	= trim($exploded[1], '/');
	
	// get all terms in the taxonomy
	$terms = get_terms( $taxonomy ); 

	if(!empty($terms)){
		$link	= get_category_link($terms[0]->term_id);
		wp_redirect($link);
		exit;
	}
}

global $wp_query;

if($wp_query->is_embed){
	$skipWrapper	= true;
}

wp_enqueue_style('sim_taxonomy_style');

if($skipWrapper){
	displayBookArchive();
}else{
	if(!isset($skipHeader) || !$skipHeader){
		get_header();
	}

	require_once( __DIR__.'/shared.php');
	addBooksModal();

	?>
	<div id="primary">
		<style>
			@media (min-width: 991px){
				#primary:not(:only-child){
					width: 70%;
				}
			}
		</style>
		<main id="main" class='inside-article'>
			<button type='button' class='sim button add-books' onclick='Main.showModal(`add-books`)'>Add books</button>
			<?php displayBookArchive();?>
		</main>
	</div>
	<?php
	get_sidebar();

	if(!isset($skipFooter) || !$skipFooter){
		get_footer();
	}
}

function displayBookArchive(){
	//Variable containing the current books page we are on
	$paged 		= (get_query_var('paged')) ? get_query_var('paged') : 1;

	$args		= [
		'post_type'			=> 'book',
		'post_status'		=> 'publish',
		'paged'           	=> $paged,
		'posts_per_page'  	=> -1, // Show all books
	];

	$booksQuery = new \WP_Query($args);
	
	if ( $booksQuery->have_posts() ){
		do_action('sim_before_archive', 'book');
		
		while ( $booksQuery->have_posts() ) :
			$booksQuery->the_post();
			include(__DIR__.'/content.php');
		endwhile;
		
		//Add pagination
		$totalPages = $books_query->max_num_pages;

		if ($totalPages > 1){
			$currentPage = max(1, get_query_var('paged'));

			echo paginate_links(array(
				'base' 		=> get_pagenum_link(1) . '%_%',
				'format' 	=> '/page/%#%',
				'current' 	=> $currentPage,
				'total' 	=> $totalPages,
				'prev_text' => __('« prev'),
				'next_text' => __('next »'),
			));
		}
	}else{
		//No books to show yet
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php generate_do_microdata( 'article' ); ?>>
		<div class="no-results not-found">
			<div class="inside-article">
				<div class="entry-content">
					<?php echo apply_filters('sim-empty-taxonomy', 'There are no books submitted yet.', 'book'); ?>
				</div>
			</div>
		</div>
		</article>
		<?php
	}
}