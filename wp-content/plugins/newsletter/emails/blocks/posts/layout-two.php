<?php
$size = array('width' => 240, 'height' => 160, "crop" => true);
?>
<style>
    .title {
        font-family: <?php echo $title_font_family ?>;
        font-size: <?php echo $title_font_size ?>px;
        font-weight: <?php echo $title_font_weight ?>;
        color: <?php echo $title_font_color ?>;
        line-height: 1.3em;
        padding: 15px 0 0 0;
    }

    .excerpt {
        font-family: <?php echo $text_font_family ?>;
        font-size: <?php echo $text_font_size ?>px;
        font-weight: <?php echo $text_font_weight ?>;
        color: <?php echo $text_font_color ?>;
        line-height: 1.4em;
        padding: 5px 0 0 0;
    }

    .date {
        font-family: <?php echo $text_font_family ?>;
        color: <?php echo $text_font_color ?>;
        font-size: <?php echo round($text_font_size * 0.8) ?>px;
        font-weight: normal;
        padding: 10px 0 0 0;
    }

    .author {
        font-family: <?php echo $text_font_family ?>;
        color: <?php echo $text_font_color ?>;
        font-size: <?php echo round($text_font_size * 0.8) ?>px;
        font-weight: normal;
        padding: 10px 0 0 0;
    }
</style>

<table cellspacing="0" cellpadding="0" border="0" width="100%">

    <?php foreach (array_chunk($posts, 2) AS $row) { ?>
        <?php
        $media = null;
        if ($show_image) {
            $media = tnp_composer_block_posts_get_media($row[0], $size, $image_placeholder_url);
            $media->link = tnp_post_permalink($row[0]);
        }

        $author = '';
        if ($show_author) {
            $author_object = get_user_by('id', $row[0]->post_author);
            if ($author_object) {
                $author = $author_object->display_name;
            }
        }

        $button_options['button_url'] = tnp_post_permalink($row[0]);
        ?>
        <tr>
            <td style="padding: 10px;" class="mobile-wrapper two-columns">


                <table cellpadding="0" cellspacing="0" border="0" width="47%" align="left" class="responsive">
                    <tr>
                        <td style="padding: 20px 0 40px 0;">
                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                <?php if ($media) { ?>
                                    <tr>
                                        <td align="center" valign="middle" class="tnpc-row-edit" data-type="image">
                                            <?php echo TNP_Composer::image($media) ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <td align="center" inline-class="title" class="tnpc-row-edit tnpc-inline-editable" data-type="title" data-id="<?php echo $row[0]->ID ?>">
                                        <?php
                                        echo TNP_Composer::is_post_field_edited_inline($options['inline_edits'], 'title', $row[0]->ID) ?
                                                TNP_Composer::get_edited_inline_post_field($options['inline_edits'], 'title', $row[0]->ID) :
                                                tnp_post_title($row[0])
                                        ?>
                                    </td>
                                </tr>
                                <?php if ($show_date) { ?>
                                    <tr>
                                        <td  align="center" inline-class="date">
                                            <?php echo tnp_post_date($row[0]) ?>
                                        </td>
                                    </tr>
                                <?php } ?>

                                <?php if ($author) { ?>
                                    <tr>
                                        <td align="center" inline-class="author">
                                            <?php echo esc_html($author) ?>
                                        </td>
                                    </tr>
                                <?php } ?>

                                <tr>
                                    <td align="center" inline-class="excerpt" class="tnpc-row-edit tnpc-inline-editable" data-type="text" data-id="<?php echo $row[0]->ID ?>">
                                        <?php
                                        echo TNP_Composer::is_post_field_edited_inline($options['inline_edits'], 'text', $row[0]->ID) ?
                                                TNP_Composer::get_edited_inline_post_field($options['inline_edits'], 'text', $row[0]->ID) :
                                                tnp_post_excerpt($row[0], $excerpt_length)
                                        ?>
                                    </td>
                                </tr>
                                <?php if ($show_read_more_button) { ?>
                                    <tr>
                                        <td align="center">
                                            <br><?php echo TNP_Composer::button($button_options) ?><br><br>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </td>
                    </tr>
                </table>

                <?php
                if (!isset($row[1])) {
                    continue;
                }
                $media = null;
                if ($show_image) {
                    $media = tnp_composer_block_posts_get_media($row[1], $size, $image_placeholder_url);
                    $media->link = tnp_post_permalink($row[1]);
                }

                $author = '';
                if ($show_author) {
                    $author_object = get_user_by('id', $row[1]->post_author);
                    if ($author_object) {
                        $author = $author_object->display_name;
                    }
                }

                $button_options['button_url'] = tnp_post_permalink($row[1]);
                ?>
                <table cellpadding="0" cellspacing="0" border="0" width="47%" align="right" class="responsive">
                    <tr>
                        <td style="padding: 20px 0 40px 0;">
                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                <?php if ($media) { ?>
                                    <tr>
                                        <td align="center" valign="middle" class="tnpc-row-edit" data-type="image">
                                            <?php echo TNP_Composer::image($media) ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <td align="center" inline-class="title" class="tnpc-row-edit tnpc-inline-editable" data-type="title" data-id="<?php echo $row[1]->ID ?>">
                                        <?php
                                        echo TNP_Composer::is_post_field_edited_inline($options['inline_edits'], 'title', $row[1]->ID) ?
                                                TNP_Composer::get_edited_inline_post_field($options['inline_edits'], 'title', $row[1]->ID) :
                                                tnp_post_title($row[1])
                                        ?>
                                    </td>
                                </tr>
                                <?php if ($show_date) { ?>
                                    <tr>
                                        <td  align="center" inline-class="date">
                                            <?php echo tnp_post_date($row[1]) ?>
                                        </td>
                                    </tr>
                                <?php } ?>

                                <?php if ($author) { ?>
                                    <tr>
                                        <td align="center" inline-class="author">
                                            <?php echo esc_html($author) ?>
                                        </td>
                                    </tr>
                                <?php } ?>

                                <tr>
                                    <td align="center" inline-class="excerpt" class="tnpc-row-edit tnpc-inline-editable" data-type="text" data-id="<?php echo $row[1]->ID ?>">
                                        <?php
                                        echo TNP_Composer::is_post_field_edited_inline($options['inline_edits'], 'text', $row[1]->ID) ?
                                                TNP_Composer::get_edited_inline_post_field($options['inline_edits'], 'text', $row[1]->ID) :
                                                tnp_post_excerpt($row[1], $excerpt_length)
                                        ?>
                                    </td>
                                </tr>
                                <?php if ($show_read_more_button) { ?>
                                    <tr>
                                        <td align="center">
                                            <br><?php echo TNP_Composer::button($button_options) ?><br><br>
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

