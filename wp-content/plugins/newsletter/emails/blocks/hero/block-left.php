<style>
    /* Styles which will be removed and injected in the replacing the matching "inline-class" attribute */
    .title {
        font-family: <?php echo $title_font_family ?>;
        font-size: <?php echo $title_font_size ?>px;
        font-weight: <?php echo $title_font_weight ?>;
        color: <?php echo $title_font_color ?>;
        margin: 0;
        text-align: center;
        line-height: normal;
    }
    .text {
        font-family: <?php echo $text_font_family ?>;
        font-size: <?php echo $text_font_size ?>px;
        font-weight: <?php echo $text_font_weight ?>;
        color: <?php echo $text_font_color ?>;
        padding: 20px 0 0 0;
        line-height: 150%;
        margin: 0;
        text-align: center;
    }

    .image {
        max-width: 100%!important;
        display: block;
    }
    .image-a {
        display: block;
    }

    .button {
        padding-top: 15px;
    }
</style>

<!-- layout: left -->

<table width="49%" align="left" class="responsive" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" valign="top">
	        <?php echo TNP_Composer::image( $media, [ 'class' => 'image', 'link-class' => 'image-a' ] ); ?>
        </td>
    </tr>
</table>

<table width="49%" align="right" class="responsive" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" inline-class="title" class="hero-title">
            <span><?php echo $options['title'] ?></span>
        </td>
    </tr>
    <tr>
        <td align="center" inline-class="text">
            <span><?php echo $options['text'] ?></span>
        </td>
    </tr>

    <tr>
        <td align="center" inline-class="button">
            <?php echo TNP_Composer::button($button_options) ?>
        </td>
    </tr>

</table>
