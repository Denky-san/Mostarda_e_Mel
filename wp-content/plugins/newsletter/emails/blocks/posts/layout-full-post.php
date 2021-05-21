<style>
    .title {
        font-family: <?php echo $title_font_family ?>;
        font-size: <?php echo $title_font_size ?>px;
        font-weight: <?php echo $title_font_weight ?>;
        color: <?php echo $title_font_color ?>;
        line-height: normal;
        margin: 0;
        padding-bottom: 20px;
    }

    .paragraph {
        font-family: <?php echo $text_font_family ?>;
        font-size: <?php echo $text_font_size ?>px;
        font-weight: <?php echo $text_font_weight ?>;
        color: <?php echo $text_font_color ?>;
        line-height: 1.5em;
        text-align: left;
    }

    .post-date {
        font-family: <?php echo $text_font_family ?>;
        font-size: <?php echo round($text_font_size * 0.8) ?>px;
        font-weight: <?php echo $text_font_weight ?>;
        color: <?php echo $text_font_color ?>;
        line-height: normal;
        padding-bottom: 10px;
        text-align: center;
    }

    .post-author {
        font-family: <?php echo $text_font_family ?>;
        font-size: <?php echo round($text_font_size * 0.8) ?>px;
        font-weight: <?php echo $text_font_weight ?>;
        color: <?php echo $text_font_color ?>;
        line-height: normal;
        padding-bottom: 10px;
        text-align: center;
    }

    .post-button {
        padding: 15px 0;
    }

</style>

<?php foreach ( $posts as $post ) : ?>

	<?php
	$size = [ 'width' => 600, 'height' => 0 ];
	$url  = tnp_post_permalink( $post );

	$media = null;
	if ( $show_image ) {
		$media = tnp_composer_block_posts_get_media( $post, $size );
		if ( $media ) {
			$media->link = $url;
		}
	}

	$author = '';
	if ( $show_author ) {
		$author_object = get_user_by( 'id', $post->post_author );
		if ( $author_object ) {
			$author = $author_object->display_name;
		}
	}

	?>


    <table border="0" cellpadding="0" align="center" cellspacing="0" width="100%" class="responsive-table">
        <tr>
            <td inline-class="title">
				<?php echo $post->post_title ?>
            </td>
        </tr>

		<?php if ( $show_date ) { ?>
            <tr>
                <td inline-class="post-date">
					<?php echo tnp_post_date( $post ) ?>
                </td>
            </tr>
		<?php } ?>

		<?php if ( $show_author ) { ?>
            <tr>
                <td inline-class="post-author">
					<?php echo $author ?>
                </td>
            </tr>
		<?php } ?>

        <tr>
            <td>

				<?php if ( $media ) { ?>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px">
                        <tr>
                            <td align="center">
								<?php echo TNP_Composer::image( $media ) ?>
                            </td>
                        </tr>
                    </table>
				<?php } ?>

            </td>
        </tr>
        <tr>
            <td>
				<?php echo TNP_Composer::post_content( $post ) ?>
            </td>
        </tr>
	    <?php if ($show_read_more_button) { ?>
            <tr>
                <td align="center" inline-class="post-button">
	                <?php $button_options['button_url'] = $url; ?>
	                <?php echo TNP_Composer::button( $button_options ) ?>
                </td>
            </tr>
	    <?php } ?>
    </table>

<?php endforeach; ?>
