<?php
namespace SIM\LIBRARY;
use SIM;

add_action('sim_frontpage_before_main_content', __NAMESPACE__.'\beforeMainContent', 30);
function beforeMainContent(){
    if(!is_user_logged_in()){
        return;
    }

    extract(bookOfTheDay());
    if(!$title || !$image || !$url){
        return;
    }

    ?>
    <style>
        #book-of-the-day{
            padding:            20px 80px;
            font-size:          18px;
            color:              #999999;
            width:              80%;
            max-width:          800px;
            background-color: #8585790d;
        }

        @media(max-width:768px) {
            #book-of-the-day{
                padding: 0 60px;
            }
        }
    </style>
    <div id='book-of-the-day'>
        <h3 style='text-align: center;color: #444;font-weight: 500;'>
            Book of the day
        </h3>
        <p>
            <a href='<?php echo $url;?>' target='_blank'>
                <div style='text-align:center;'>
                    <img width='200' height='200' src='<?php echo $image;?>' loading='lazy' class='book-cover' alt='book cover' decoding='async'/>
                </div>

                <h4><?php echo $title;?></h4>
            </a>
            <?php echo $description;?>
        </p>
    </div>
    <?php
}