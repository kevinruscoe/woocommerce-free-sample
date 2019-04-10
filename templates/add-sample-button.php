<?php if (wc_get_product()->get_meta('_has_sample') == 'yes') : ?>

    <form action="<?php the_permalink() ?>" method="post">
        <?php wp_nonce_field('add_sample_to_cart') ?>

        <input 
            type="hidden" 
            name="add-sample-from-id" 
            value="<?php print wc_get_product()->get_id() ?>">
        <button type="submit">Add Sample</button>
    </form>

<?php endif ?>