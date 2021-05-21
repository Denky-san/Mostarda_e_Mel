<?php
$size = ['width' => 600, 'height' => 0];
$total_width = 600 - $options['block_padding_left'] - $options['block_padding_right'];
$column_width = $total_width / 2 - 10;
?>
<style>
    .post-title {
        font-family: <?php echo $title_font_family ?>;
        font-size: <?php echo $title_font_size ?>px;
        font-weight: <?php echo $title_font_weight ?>;
        color: <?php echo $title_font_color ?>;
        line-height: normal;
        padding: 0 0 5px 0;
    }

    .post-excerpt {
        font-family: <?php echo $text_font_family ?>;
        font-size: <?php echo $text_font_size ?>px;
        font-weight: <?php echo $text_font_weight ?>;
        color: <?php echo $text_font_color ?>;
        line-height: 1.5em;
        padding: 10px 0 15px 0;
    }

    .post-date {
        font-family: <?php echo $text_font_family ?>;
        color: <?php echo $text_font_color ?>;
        font-size: <?php echo round($text_font_size * 0.8) ?>px;
        font-weight: normal;
        padding: 0 0 5px 0;
    }

    .post-author {
        font-family: <?php echo $text_font_family ?>;
        color: <?php echo $text_font_color ?>;
        font-size: <?php echo round($text_font_size * 0.8) ?>px;
        font-weight: normal;
        padding: 0 0 5px 0;
    }
</style>


<table border="0" cellpadding="0" cellspacing="0" width="100%" class="responsive-table">

    <?php foreach ($posts as $post) { ?>
        <?php
        $url = tnp_post_permalink($post);
	    $options['button_url'] = $url;

        $media = null;
        if ($show_image) {
            $media = tnp_composer_block_posts_get_media($post, $size);
            if ($media) {
                $media->link = $url;
                $media->set_width($column_width);
            }
        }

	    $author = '';
	    if ($show_author) {
		    $author_object = get_user_by('id', $post->post_author);
		    if ($author_object) {
			    $author = $author_object->display_name;
		    }
	    }

        ?>

        <tr>

            <td valign="top" style="padding: 20px 0 0 0;" class="td-1">

                <?php if ($media) { ?>
                    <table width="<?php echo $column_width?>" cellpadding="0" cellspacing="0" border="0" align="left" class="responsive">
                        <tr>
                            <td>
                                <?php echo TNP_Composer::image($media) ?>
                            </td>
                        </tr>
                    </table>
                <?php } ?>

                <table width="<?php echo $media ? $column_width : '100%' ?>" cellpadding="0" cellspacing="0" border="0" class="responsive" align="right">
                    <tr>
                        <td>

                            <!-- ARTICLE -->
                            <table border="0" cellspacing="0" cellpadding="0" width="100%">
                                <?php if ($show_date) { ?>
                                    <tr>
                                        <td align="<?php echo $align_left ?>" inline-class="post-date">
                                            <?php echo tnp_post_date($post) ?>
                                        </td>
                                    </tr>
                                <?php } ?>

	                            <?php if ($show_author) { ?>
                                <tr>
                                    <td align="<?php echo $align_left ?>" inline-class="post-author">
			                            <?php echo $author ?>
                                    </td>
                                </tr>
	                            <?php } ?>

                                <tr>
                                    <td align="<?php echo $align_left ?>"
                                        inline-class="post-title"
                                        class="tnpc-row-edit tnpc-inline-editable"
                                        data-type="title" data-id="<?php echo $post->ID ?>" dir="<?php echo $dir ?>">
                                            <?php
                                            echo TNP_Composer::is_post_field_edited_inline($options['inline_edits'], 'title', $post->ID) ?
                                                    TNP_Composer::get_edited_inline_post_field($options['inline_edits'], 'title', $post->ID) :
                                                    tnp_post_title($post)
                                            ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="<?php echo $align_left ?>"
                                        inline-class="post-excerpt"
                                        class="padding-copy tnpc-row-edit tnpc-inline-editable"
                                        data-type="text" data-id="<?php echo $post->ID ?>" dir="<?php echo $dir ?>">
                                            <?php
                                            echo TNP_Composer::is_post_field_edited_inline($options['inline_edits'], 'text', $post->ID) ?
                                                    TNP_Composer::get_edited_inline_post_field($options['inline_edits'], 'text', $post->ID) :
                                                    tnp_post_excerpt($post, $excerpt_length)
                                            ?>
                                    </td>
                                </tr>
	                            <?php if ($show_read_more_button) { ?>
                                    <tr>
                                        <td align="<?php echo $align_left ?>" class="padding">
	                                        <?php $button_options['button_url'] = $url; ?>
	                                        <?php echo TNP_Composer::button( $button_options ) ?>
                                            <br><br>
                                        </td>
                                    </tr>
	                            <?php } ?>
                            </table>

                        </td>
                    </tr>
                </table>

            </td>
        </tr>

    <?php } ?>

</table>
