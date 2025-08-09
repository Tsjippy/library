<?php
namespace SIM\LIBRARY;
use SIM;

function addBooksModal(){
    global $Modules;

	$library		= getLibrary($Modules[MODULE_SLUG]);

    ?>
	<div id='add-books_modal' class='modal <?php if(empty($_GET['addbooks'])){echo 'hidden';}?>'>
		<div class="modal-content" style='max-width:100vw;'>
			<span id="modal_close" class="close">&times;</span>
			<div class="content">
				<?php echo $library->getFileHtml();?>
				<br>
				<br>
			</div>
		</div>
	</div>
    <?php
}

function displayLocationTax(){
    wp_enqueue_style('sim_taxonomy_style');

    global $post;
    global $wp_query;

    if($wp_query->is_embed){
        $skipWrapper	= true;
    }

    if($skipWrapper){
        displayBooks();
    }else{
        if(!isset($skipHeader) || !$skipHeader){
            get_header(); 
        }

        addBooksModal();

        ?>
        <div id="primary">
            <main id="main" class='taxonomy inside-article'>
                <button type='button' class='sim button add-books' onclick='Main.showModal(`add-books`)'>Add books</button>
                <?php displayBooks();?>
            </main>
        </div>
        <?php
        generate_construct_sidebars();

        if(!isset($skipFooter) || !$skipFooter){
            get_footer();
        }
    }
}

function displayBooks(){
	$name 				= get_queried_object()->slug;
	if ( have_posts() ){
		do_action('sim_before_archive', 'book');

		//only show the map if logged in
		if(is_user_logged_in() ){
			$mapName			= $name."_map";
			$mapId				= SIM\getModuleOption(MODULE_SLUG, $mapName);

			if(is_numeric($mapId)){
				//Show the map of this category
				echo "<div style='margin-bottom:25px;'>";
					echo do_shortcode("[ultimate_maps id='$mapId']");
				echo '</div>';
			}
		}
	
		while ( have_posts() ) :
			the_post();
			include(__DIR__.'/content.php');
		endwhile;

		the_posts_pagination();
	}else{
		//No items with this category
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="no-results not-found">
			<div class="inside-article">
				<div class="entry-content">
					<?php echo apply_filters('sim-empty-taxonomy', "There are no $name books yet", 'book'); ?>
				</div>
			</div>
		</div>
		</article>
		<?php
	}
}