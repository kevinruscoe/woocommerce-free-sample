<?php if (has_maxed_out_sample_allowance()) : ?>

    You are only allowed <?php print allowed_max_samples() ?> samples.

<?php else : ?>

    <form action="<?php the_permalink() ?>" method="post">
        <?php wp_nonce_field('add_sample_to_cart') ?>

        <input 
            type="hidden" 
            name="add-sample-from-id" 
            value="<?php print wc_get_product()->get_id() ?>">
        <button type="submit">Add Sample</button>
    </form>

<?php endif ?>